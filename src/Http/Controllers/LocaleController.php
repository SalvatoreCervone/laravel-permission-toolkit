<?php

namespace SalvatoreCervone\PermissionToolkit\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class LocaleController extends Controller
{
    /**
     * Switch language between Italian and English and redirect back.
     */
    public function switch(Request $request, string $locale): RedirectResponse
    {
        $locale = strtolower($locale);

        if (in_array($locale, ['it', 'en'])) {
            if ($request->hasSession()) {
                $request->session()->put('permission_toolkit_locale', $locale);
            }
            app()->setLocale($locale);
        }

        return redirect()->back(fallback: route('permission-toolkit.matrix'));
    }
}
