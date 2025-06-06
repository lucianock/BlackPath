<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PreferencesController extends Controller
{
    public function updateLanguage(Request $request)
    {
        $locale = $request->locale;
        if (in_array($locale, ['en', 'es'])) {
            session(['locale' => $locale]);
            app()->setLocale($locale);
        }
        return back();
    }

    public function updateTheme(Request $request)
    {
        $theme = $request->theme;
        if (in_array($theme, ['light', 'dark'])) {
            session(['theme' => $theme]);
        }
        return back();
    }
}
