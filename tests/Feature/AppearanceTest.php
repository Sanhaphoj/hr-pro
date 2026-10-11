<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppearanceTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->roles()->sync([Role::where('slug', 'employee')->value('id')]);

        return $user;
    }

    public function test_a_new_account_starts_on_the_default_appearance(): void
    {
        $user = $this->user();

        $this->assertSame('teal', $user->theme);
        $this->assertSame('light', $user->color_scheme);
    }

    public function test_the_appearance_screen_renders_for_any_signed_in_user(): void
    {
        // No permission gate: appearance is self-service.
        $this->actingAs($this->user())
            ->get(route('settings.appearance.edit'))
            ->assertOk()
            ->assertSee('ธีมสี')
            ->assertSee('โหมดการแสดงผล');
    }

    public function test_a_choice_is_saved_against_the_account(): void
    {
        $user = $this->user();

        $this->actingAs($user)
            ->put(route('settings.appearance.update'), ['theme' => 'indigo', 'color_scheme' => 'dark'])
            ->assertRedirect(route('settings.appearance.edit'))
            ->assertSessionHas('success');

        $user->refresh();
        $this->assertSame('indigo', $user->theme);
        $this->assertSame('dark', $user->color_scheme);
    }

    public function test_the_saved_choice_reaches_the_root_element(): void
    {
        $user = $this->user();
        $user->forceFill(['theme' => 'plum', 'color_scheme' => 'dark'])->save();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('data-theme="plum"', false)
            ->assertSee('data-scheme="dark"', false);
    }

    public function test_an_unknown_theme_is_rejected(): void
    {
        $user = $this->user();

        $this->actingAs($user)
            ->put(route('settings.appearance.update'), ['theme' => 'chartreuse', 'color_scheme' => 'light'])
            ->assertSessionHasErrors('theme');

        $this->assertSame('teal', $user->refresh()->theme);
    }

    public function test_an_unknown_colour_scheme_is_rejected(): void
    {
        $user = $this->user();

        $this->actingAs($user)
            ->put(route('settings.appearance.update'), ['theme' => 'teal', 'color_scheme' => 'sepia'])
            ->assertSessionHasErrors('color_scheme');

        $this->assertSame('light', $user->refresh()->color_scheme);
    }

    public function test_the_sign_in_screen_falls_back_to_the_default_for_guests(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('data-theme="teal"', false)
            ->assertSee('data-scheme="light"', false);
    }
}
