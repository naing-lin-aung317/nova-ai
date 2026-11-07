<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PreferencesController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'theme' => ['sometimes', Rule::in(['light', 'dark'])],
            'preferred_language' => ['sometimes', Rule::in(['en', 'my'])],
        ]);

        $request->user()->update($validated);

        return back();
    }
}
