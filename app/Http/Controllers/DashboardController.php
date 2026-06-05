<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        return view('dashboard');
    }

    public function updatePreferences(Request $request)
    {
        $validated = $request->validate([
            'preferred_task' => 'required|in:georef,validate,both',
            'email_notifications' => 'nullable|boolean',
        ]);

        auth()->user()->update([
            'preferred_task' => $validated['preferred_task'],
            'email_notifications' => $request->boolean('email_notifications'),
        ]);

        return redirect()->route('dashboard')->with('success', __('Preferences saved.'));
    }
}