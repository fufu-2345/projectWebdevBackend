<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\User;

class ProfileController extends Controller
{
    // get user profile
    public function show(Request $request)
    {
        return response()->json([
            'user' => $request->user()
        ]);
    }

    // update
    public function update(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email',
            'address' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'birthday' => 'nullable|date',
            'profilepic' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        if ($request->hasFile('profilepic')) {
            if ($user->profilepic && Storage::disk('public')->exists($user->profilepic)) {
                Storage::disk('public')->delete($user->profilepic);
            }
            $path = $request->file('profilepic')->store('profile_pics', 'public');
            $validated['profilepic'] = $path;
        }
        $user->update($validated);

        return response()->json([
            'message' => 'Profile updated successfully.',
            'user' => $user
        ]);
    }
}
