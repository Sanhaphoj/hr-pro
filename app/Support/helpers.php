<?php

use Illuminate\Support\Str;

if (! function_exists('initials')) {
    /**
     * Build up to two uppercase initials from a full name.
     */
    function initials(?string $name): string
    {
        $name = trim((string) $name);
        if ($name === '') {
            return '–';
        }

        $parts = preg_split('/\s+/', $name);
        $first = Str::substr($parts[0] ?? '', 0, 1);
        $second = count($parts) > 1 ? Str::substr(end($parts), 0, 1) : '';

        return Str::upper($first.$second);
    }
}

if (! function_exists('thb')) {
    /**
     * Format a number as Thai Baht currency.
     */
    function thb(int|float|null $amount): string
    {
        return '฿'.number_format((float) ($amount ?? 0), 2);
    }
}

if (! function_exists('avatar_color')) {
    /**
     * Deterministic background colour for an avatar from a seed string.
     */
    function avatar_color(?string $seed): string
    {
        $palette = ['#1e3a5f', '#2563eb', '#7c3aed', '#0f766e', '#b45309', '#be123c', '#0369a1', '#4d7c0f'];

        return $palette[crc32((string) $seed) % count($palette)];
    }
}
