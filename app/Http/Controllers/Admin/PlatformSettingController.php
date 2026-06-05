<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlatformSetting;
use Illuminate\Http\Request;

class PlatformSettingController extends Controller
{
    public function index()
    {
        $settings = PlatformSetting::orderBy('key')->get();
        return view('admin.settings.index', compact('settings'));
    }

    public function edit(PlatformSetting $setting)
    {
        return view('admin.settings.edit', compact('setting'));
    }

    public function update(Request $request, PlatformSetting $setting)
    {
        $validated = $request->validate([
            'value' => 'nullable|string|max:1000',
        ]);

        $setting->update($validated);

        return redirect()->route('admin.settings.index')
            ->with('success', 'Setting updated successfully.');
    }
}