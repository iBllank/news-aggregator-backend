<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PreferenceController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'categories' => 'nullable|array',
            'sources'    => 'nullable|array',
            'authors'    => 'nullable|array',
        ]);

        $user = auth('sanctum')->user();

        $user->preferences()->updateOrCreate([], [
            'categories' => $data['categories'] ?? [],
            'sources'    => $data['sources'] ?? [],
            'authors'    => $data['authors'] ?? [],
        ]);

        return response()->json(['message' => 'Preferences saved']);
    }

    public function show()
    {
        return response()->json(auth('sanctum')->user()->preferences);
    }
}
