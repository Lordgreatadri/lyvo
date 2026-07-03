<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * BrandingController
 * ------------------
 * Lets an approved operator upload their own storefront cover banner and logo.
 * Both are single-file Spatie media collections, so a new upload replaces the
 * old one; the public profile falls back to the LYVO gradient when empty.
 */
class BrandingController extends Controller
{
    public function edit(Request $request): View
    {
        return view('operator.branding', [
            'profile' => $request->user()->operatorProfile,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'cover' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'logo'  => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $profile = $request->user()->operatorProfile;

        if ($request->hasFile('cover')) {
            $profile->addMediaFromRequest('cover')->toMediaCollection('cover');
        }

        if ($request->hasFile('logo')) {
            $profile->addMediaFromRequest('logo')->toMediaCollection('logo');
        }

        return back()->with('success', 'Storefront branding updated.');
    }
}
