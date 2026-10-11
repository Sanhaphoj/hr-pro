<?php

namespace Tests\Unit;

use App\Models\WorkLocation;
use App\Services\GeofenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GeofenceServiceTest extends TestCase
{
    use RefreshDatabase;

    private GeofenceService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new GeofenceService();
    }

    public function test_distance_between_identical_points_is_zero(): void
    {
        $this->assertSame(0.0, $this->service->distance(13.7248, 100.5340, 13.7248, 100.5340));
    }

    public function test_distance_matches_a_known_pair(): void
    {
        // Bangkok (Silom) to Chiang Mai city centre — roughly 580 km.
        $distance = $this->service->distance(13.7248, 100.5340, 18.7883, 98.9853);

        $this->assertEqualsWithDelta(580_000, $distance, 10_000);
    }

    public function test_one_thousandth_of_a_degree_of_latitude_is_about_111_metres(): void
    {
        $distance = $this->service->distance(13.7248, 100.5340, 13.7258, 100.5340);

        $this->assertEqualsWithDelta(111.2, $distance, 1.0);
    }

    public function test_match_returns_the_zone_containing_the_point(): void
    {
        WorkLocation::factory()->create([
            'name' => 'สำนักงานใหญ่',
            'latitude' => 13.7248,
            'longitude' => 100.5340,
            'radius_meters' => 200,
        ]);

        // ~111 m north of the centre — comfortably inside the 200 m radius.
        $match = $this->service->match(13.7258, 100.5340);

        $this->assertNotNull($match);
        $this->assertSame('สำนักงานใหญ่', $match['location']->name);
        $this->assertTrue($match['inside']);
    }

    public function test_match_is_null_outside_every_zone(): void
    {
        WorkLocation::factory()->create([
            'latitude' => 13.7248,
            'longitude' => 100.5340,
            'radius_meters' => 100,
        ]);

        // ~333 m north — outside the 100 m radius.
        $this->assertNull($this->service->match(13.7278, 100.5340));
    }

    public function test_inactive_zones_are_ignored(): void
    {
        WorkLocation::factory()->inactive()->create([
            'latitude' => 13.7248,
            'longitude' => 100.5340,
            'radius_meters' => 500,
        ]);

        $this->assertNull($this->service->match(13.7248, 100.5340));
        $this->assertFalse($this->service->hasZones());
    }

    public function test_nearest_reports_the_closest_zone_even_when_outside(): void
    {
        WorkLocation::factory()->create([
            'name' => 'ไกล',
            'latitude' => 13.9000,
            'longitude' => 100.5340,
            'radius_meters' => 100,
        ]);
        WorkLocation::factory()->create([
            'name' => 'ใกล้',
            'latitude' => 13.7300,
            'longitude' => 100.5340,
            'radius_meters' => 100,
        ]);

        $nearest = $this->service->nearest(13.7248, 100.5340);

        $this->assertSame('ใกล้', $nearest['location']->name);
        $this->assertFalse($nearest['inside']);
    }
}
