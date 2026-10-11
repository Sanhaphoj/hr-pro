<?php

namespace App\Services;

use App\Models\WorkLocation;
use Illuminate\Support\Collection;

/**
 * Decides whether a GPS reading falls inside one of the areas an
 * administrator has approved for clocking in and out.
 */
class GeofenceService
{
    /** Mean Earth radius in metres (IUGG). */
    private const EARTH_RADIUS = 6_371_008.8;

    /**
     * Great-circle distance between two points, in metres.
     *
     * Haversine is accurate to ~0.5% treating the Earth as a sphere, which is
     * an order of magnitude better than a phone's GPS fix — good enough for
     * geofences measured in tens of metres.
     */
    public function distance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return self::EARTH_RADIUS * 2 * asin(min(1.0, sqrt($a)));
    }

    /**
     * Every active zone ordered by how far the point sits from its centre.
     *
     * @return Collection<int, array{location: WorkLocation, distance: float, inside: bool}>
     */
    public function rank(float $latitude, float $longitude): Collection
    {
        return WorkLocation::active()
            ->orderBy('name')
            ->get()
            ->map(function (WorkLocation $location) use ($latitude, $longitude) {
                $distance = $this->distance($latitude, $longitude, $location->latitude, $location->longitude);

                return [
                    'location' => $location,
                    'distance' => $distance,
                    'inside' => $distance <= $location->radius_meters,
                ];
            })
            ->sortBy('distance')
            ->values();
    }

    /**
     * The closest zone that actually contains the point, or null when the
     * reading falls outside every approved area.
     *
     * @return array{location: WorkLocation, distance: float, inside: bool}|null
     */
    public function match(float $latitude, float $longitude): ?array
    {
        return $this->rank($latitude, $longitude)->firstWhere('inside', true);
    }

    /**
     * The closest zone regardless of its radius — used to tell an employee
     * how far off they are when a punch is rejected.
     *
     * @return array{location: WorkLocation, distance: float, inside: bool}|null
     */
    public function nearest(float $latitude, float $longitude): ?array
    {
        return $this->rank($latitude, $longitude)->first();
    }

    /**
     * True when no administrator has defined a zone yet. Enforcing an empty
     * geofence would lock every employee out, so callers fall back to
     * location-optional behaviour in that case.
     */
    public function hasZones(): bool
    {
        return WorkLocation::active()->exists();
    }
}
