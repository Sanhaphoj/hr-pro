<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;

class LocaleController extends Controller
{
    public const SUPPORTED = ['th', 'en'];

    /**
     * Switch the UI language and remember it in the session.
     * Applied per-request by the SetLocale middleware.
     */
    public function update(string $locale): RedirectResponse
    {
        if (in_array($locale, self::SUPPORTED, true)) {
            session(['locale' => $locale]);
        }

        return back();
    }
}
