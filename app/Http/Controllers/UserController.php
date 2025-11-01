<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class UserController extends Controller
{
    public function showUser()
    {
        log::info('users/show');

        $users = User::where('role', '!=', 'admin')->get()->map(function ($user) {
            $user->profilepic = $user->profilepic ? asset('storage/' . $user->profilepic) : null;
            $user->address = $user->address ?? null;
            $user->phone = $user->phone ?? null;
            $user->birthday = $user->birthday ?? null;

            return $user;
        });

        return response()->json([
            "status" => true,
            "users" => $users
        ]);
    }
}
