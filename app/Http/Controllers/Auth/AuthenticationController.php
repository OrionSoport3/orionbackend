<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\Tokens;
use Illuminate\Http\Request;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthenticationController extends Controller
{
    public function register(RegisterRequest $request)
    {
        try {
            $userToken = Tokens::first();
            // Verificar si el token no existe o si la comparación falla
            if (!$userToken || !Hash::check($request->token, $userToken->token)) {
                
                return response()->json(['message' => 'Token inválido'], 400);
            }
            $user = User::create([
                'name' => $request->name,
                'lastname' => $request->lastname,
                'birthday' => $request->birthday,
                'departamento' => $request->departamento,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            $token = JWTAuth::fromUser($user);
        
        return response()->json(['message' => 'Registro exitoso', 'token' => $token], 201);            
        

        } catch (Exception $e) {
            // Manejar cualquier excepción inesperada
            return response()->json(['message' => 'Ocurrió un error al registrar el usuario', 'error' => $e->getMessage()], 500);
        }
    }

    public function login(LoginRequest $request) {
        $credentials = $request->only('email', 'password');

        if(!$token = JWTAuth::attempt($credentials)){
            return response()->json(['error' => 'invalid_credentials'], 401);
        }

        $user = User::where('email',$request->email)->first();

        return response()->json(compact('user', 'token'), 200);
    }


    function createToken (Request $request) {
        $token = Tokens::create([
            'token' => Hash::make($request->input('token')),
        ]);
    }
}
