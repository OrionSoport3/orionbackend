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
            return response()->json(['message' => 'Ocurrió un error al registrar el usuario', 'error' => $e->getMessage()], 500);
        }
    }

    public function login(LoginRequest $request) {
        
        $credentials = $request->only('email', 'password');

        if(!$token = JWTAuth::attempt($credentials)){
            if(!(User::where('email', $request->email)->first())) {
                return response()->json(['error' => 'Usuario no encontrado'], 422);
            }
            return response()->json(['error' => 'Las credenciales no son correctas'], 401);
        }
        $user = User::where('email',$request->email)->first();

        return response()->json(compact('user', 'token'), 200);
    }


    function createToken (Request $request) {
        Tokens::create([
            'token' => Hash::make($request->input('token')),
        ]);
    }

    function update_password(Request $request) {

        $request->validate([
            'email' => 'required|email|exists:users,email',
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);


        $userToken = Tokens::orderBy('created_at')->skip(1)->take(1)->first();

        if (!$userToken || !Hash::check($request->token, $userToken->token)) {
            return response()->json(['message' => 'Token inválido'], 400);
        }
        
        $usuario = User::where('email', $request->email)->first();
    
        if (!$usuario) {
            return response()->json(['message' => 'No se ha encontrado el usuario'], 404);
        }
        
        $usuario->update([
            'password' => Hash::make($request->password)
        ]);
    
        return response()->json(['message' => 'Contraseña actualizada exitosamente'], 200);          
    }
}
