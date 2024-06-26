<?php

namespace App\Http\Controllers;

use App\Models\Personal;
use App\Models\User;
use Tymon\JWTAuth\Facades\JWTAuth;

class AdminAuthController extends Controller
{
    public function getUsers()
    {
        $user = JWTAuth::parseToken()->authenticate();
        $allPeople = Personal::all();
        return response()->json(['message' => 'Session is active', 'user' => $allPeople]);
    }
}
