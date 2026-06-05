<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UserLevel;
use Illuminate\Http\Request;

class UserLevelController extends Controller
{
    public function index()
    {
        $levels = UserLevel::orderBy('sort_order')->get();
        return view('admin.user-levels.index', compact('levels'));
    }

    public function create()
    {
        return view('admin.user-levels.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:user_levels',
            'min_validated' => 'required|integer|min:0',
            'vote_weight' => 'required|integer|min:1',
            'sort_order' => 'required|integer|min:0',
        ]);

        UserLevel::create($validated);

        return redirect()->route('admin.user-levels.index')
            ->with('success', 'Level created successfully.');
    }

    public function edit(UserLevel $userLevel)
    {
        return view('admin.user-levels.edit', compact('userLevel'));
    }

    public function update(Request $request, UserLevel $userLevel)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:user_levels,name,' . $userLevel->id,
            'min_validated' => 'required|integer|min:0',
            'vote_weight' => 'required|integer|min:1',
            'sort_order' => 'required|integer|min:0',
        ]);

        $userLevel->update($validated);

        return redirect()->route('admin.user-levels.index')
            ->with('success', 'Level updated successfully.');
    }

    public function destroy(UserLevel $userLevel)
    {
        if ($userLevel->users()->count() > 0) {
            return redirect()->route('admin.user-levels.index')
                ->with('error', 'Cannot delete a level that has users assigned to it.');
        }

        $userLevel->delete();

        return redirect()->route('admin.user-levels.index')
            ->with('success', 'Level deleted successfully.');
    }
}