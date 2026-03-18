<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ProfileController extends Controller
{
    public function getProfile(Request $request)
    {
        return response()->json($request->user());
    }

    public function updateProfile(Request $request)
    {
        $request->validate([
            'dietary_tags' => 'array',
            'dietary_tags.*' => 'string'
        ]);

        $user = $request->user();
        $user->dietary_tags = $request->dietary_tags;
        $user->save();

        return response()->json($user);
    }
}
