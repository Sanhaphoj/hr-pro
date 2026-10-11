<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Front-camera photos at the punch, switched on per role from the
 * permissions screen.
 */
class SelfieTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->seed(RolePermissionSeeder::class);
    }

    private function user(string $roleSlug = 'employee'): User
    {
        $user = User::factory()->create();
        $user->roles()->sync([Role::where('slug', $roleSlug)->value('id')]);
        Employee::factory()->create(['user_id' => $user->id]);

        return $user->fresh();
    }

    private function requireOnRole(string $roleSlug, string $permission): void
    {
        Role::where('slug', $roleSlug)->first()
            ->permissions()->attach(Permission::where('name', $permission)->value('id'));
    }

    /** A real JPEG, so the upload passes the "it must parse as a JPEG" check. */
    private function jpeg(): string
    {
        if (function_exists('imagecreatetruecolor')) {
            $img = imagecreatetruecolor(8, 6);
            ob_start();
            imagejpeg($img);
            $bytes = ob_get_clean();
        } else {
            $bytes = base64_decode('/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////wgALCAABAAEBAREA/8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABPxA=');
        }

        return 'data:image/jpeg;base64,'.base64_encode($bytes);
    }

    // ---------------------------------------------------------- off by default --

    public function test_no_role_requires_a_photo_out_of_the_box(): void
    {
        foreach (['employee', 'manager', 'hr-manager', 'super-admin'] as $slug) {
            $user = $this->user($slug);
            $this->assertFalse($user->requiresSelfie('in'), $slug);
            $this->assertFalse($user->requiresSelfie('out'), $slug);
        }
    }

    public function test_without_the_requirement_no_photo_is_needed_or_kept(): void
    {
        $user = $this->user();

        // A photo sent anyway is ignored rather than stored.
        $this->actingAs($user)->post(route('time-clock.store'), ['action' => 'in', 'photo' => $this->jpeg()])
            ->assertSessionHas('success');

        $this->assertNull(Attendance::first()->clock_in_photo);
        $this->assertEmpty(Storage::disk('local')->allFiles());
    }

    // --------------------------------------------------------- when required --

    public function test_a_required_photo_that_is_missing_refuses_the_punch(): void
    {
        $this->requireOnRole('employee', 'attendance.selfie-in');
        $user = $this->user();

        $this->actingAs($user)->post(route('time-clock.store'), ['action' => 'in'])
            ->assertSessionHas('error');

        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_a_required_photo_is_stored_on_the_private_disk(): void
    {
        $this->requireOnRole('employee', 'attendance.selfie-in');
        $user = $this->user();

        $this->actingAs($user)->post(route('time-clock.store'), ['action' => 'in', 'photo' => $this->jpeg()])
            ->assertSessionHas('success');

        $path = Attendance::first()->clock_in_photo;
        $this->assertNotNull($path);
        $this->assertStringStartsWith('attendance-photos/', $path);
        Storage::disk('local')->assertExists($path);
    }

    public function test_something_that_is_not_a_jpeg_is_refused(): void
    {
        $this->requireOnRole('employee', 'attendance.selfie-in');
        $user = $this->user();

        $this->actingAs($user)->post(route('time-clock.store'), [
            'action' => 'in',
            'photo' => 'data:image/jpeg;base64,'.base64_encode('<?php echo "not a photo"; ?>'),
        ])->assertSessionHas('error');

        $this->assertDatabaseCount('attendances', 0);
        $this->assertEmpty(Storage::disk('local')->allFiles());
    }

    public function test_requiring_it_for_clock_in_does_not_require_it_for_clock_out(): void
    {
        $this->requireOnRole('employee', 'attendance.selfie-in');
        $user = $this->user();

        $this->actingAs($user)->post(route('time-clock.store'), ['action' => 'in', 'photo' => $this->jpeg()])
            ->assertSessionHas('success');
        $this->actingAs($user)->post(route('time-clock.store'), ['action' => 'out'])
            ->assertSessionHas('success');

        $row = Attendance::first();
        $this->assertNotNull($row->clock_in_photo);
        $this->assertNull($row->clock_out_photo);
    }

    public function test_clock_out_can_be_required_on_its_own(): void
    {
        $this->requireOnRole('employee', 'attendance.selfie-out');
        $user = $this->user();

        $this->actingAs($user)->post(route('time-clock.store'), ['action' => 'in'])->assertSessionHas('success');
        $this->actingAs($user)->post(route('time-clock.store'), ['action' => 'out'])->assertSessionHas('error');
        $this->actingAs($user)->post(route('time-clock.store'), ['action' => 'out', 'photo' => $this->jpeg()])
            ->assertSessionHas('success');

        $this->assertNotNull(Attendance::first()->clock_out_photo);
    }

    public function test_a_refused_punch_leaves_no_photo_behind(): void
    {
        $this->requireOnRole('employee', 'attendance.selfie-in');
        $user = $this->user();

        $this->actingAs($user)->post(route('time-clock.store'), ['action' => 'in', 'photo' => $this->jpeg()])
            ->assertSessionHas('success');
        // Second clock-in the same day is refused — its photo must not linger.
        $this->actingAs($user)->post(route('time-clock.store'), ['action' => 'in', 'photo' => $this->jpeg()])
            ->assertSessionHas('error');

        $this->assertCount(1, Storage::disk('local')->allFiles());
    }

    public function test_a_super_admin_is_only_asked_when_their_role_is_ticked(): void
    {
        $admin = $this->user('super-admin');
        $this->assertFalse($admin->requiresSelfie('in'));

        $this->requireOnRole('super-admin', 'attendance.selfie-in');
        $this->assertTrue($admin->fresh()->requiresSelfie('in'));
    }

    // ------------------------------------------------------------ who may see --

    public function test_the_photo_is_served_only_to_its_owner_and_attendance_viewers(): void
    {
        $this->requireOnRole('employee', 'attendance.selfie-in');
        $owner = $this->user();
        $this->actingAs($owner)->post(route('time-clock.store'), ['action' => 'in', 'photo' => $this->jpeg()]);
        $row = Attendance::first();
        $url = route('attendance.photo', [$row, 'in']);

        $this->actingAs($owner)->get($url)->assertOk()->assertHeader('Content-Type', 'image/jpeg');
        $this->actingAs($this->user())->get($url)->assertForbidden();
        $this->actingAs($this->user('manager'))->get($url)->assertForbidden();
        $this->actingAs($this->user('hr-manager'))->get($url)->assertOk();
    }

    public function test_a_punch_without_a_photo_has_nothing_to_serve(): void
    {
        $user = $this->user();
        $this->actingAs($user)->post(route('time-clock.store'), ['action' => 'in']);

        $this->actingAs($user)->get(route('attendance.photo', [Attendance::first(), 'in']))->assertNotFound();
    }

    // -------------------------------------------------------------- the switch --

    public function test_the_role_editor_offers_both_switches(): void
    {
        $this->actingAs($this->user('super-admin'))
            ->get(route('settings.roles.edit', Role::where('slug', 'employee')->first()))
            ->assertOk()
            ->assertSee('บังคับถ่ายภาพหน้าตอนเข้างาน')
            ->assertSee('บังคับถ่ายภาพหน้าตอนออกงาน');
    }

    public function test_the_time_clock_tells_the_page_which_punches_need_a_photo(): void
    {
        $this->requireOnRole('employee', 'attendance.selfie-in');

        $this->actingAs($this->user())->get(route('time-clock.show'))
            ->assertOk()
            ->assertSee("'in': true", false)
            ->assertSee("'out': false", false)
            ->assertSee('id="cam-shutter"', false);
    }
}
