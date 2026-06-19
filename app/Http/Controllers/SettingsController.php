<?php

namespace App\Http\Controllers;

use App\Support\Theme;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:admin-access');
    }

    /** Visual-identity (theme) settings for the acting user's company. */
    public function theme(Request $request)
    {
        $company = $request->user()->company;
        abort_if(! $company, 403, 'هذه الإعدادات خاصة بالشركات. اختر شركة من إدارة الشركات.');

        return view('settings.theme', [
            'company' => $company,
            'presets' => Theme::PRESETS,
            'defaults' => [
                'primary' => Theme::DEFAULT_PRIMARY,
                'sidebar' => Theme::DEFAULT_SIDEBAR,
                'bg' => Theme::DEFAULT_BG,
            ],
        ]);
    }

    public function updateTheme(Request $request)
    {
        $company = $request->user()->company;
        abort_if(! $company, 403);

        $data = $request->validate([
            'primary_color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'sidebar_color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'bg_color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,svg,webp', 'max:2048'],
            'remove_logo' => ['nullable', 'boolean'],
        ], [], [
            'primary_color' => 'اللون الأساسي',
            'sidebar_color' => 'لون الشريط الجانبي',
            'bg_color' => 'لون الخلفية',
            'logo' => 'الشعار',
        ]);

        $company->primary_color = $data['primary_color'] ?? null;
        $company->sidebar_color = $data['sidebar_color'] ?? null;
        $company->bg_color = $data['bg_color'] ?? null;

        // Logo: replace or remove.
        if ($request->boolean('remove_logo') && $company->logo) {
            Storage::disk('public')->delete($company->logo);
            $company->logo = null;
        }
        if ($request->hasFile('logo')) {
            if ($company->logo) {
                Storage::disk('public')->delete($company->logo);
            }
            $company->logo = $request->file('logo')->store('logos', 'public');
        }

        $company->save();

        return back()->with('success', 'تم تحديث الهوية البصرية بنجاح.');
    }

    /** Reset to the default theme. */
    public function resetTheme(Request $request)
    {
        $company = $request->user()->company;
        abort_if(! $company, 403);

        $company->update(['primary_color' => null, 'sidebar_color' => null, 'bg_color' => null]);

        return back()->with('success', 'تمت إعادة الهوية البصرية إلى الوضع الافتراضي.');
    }
}
