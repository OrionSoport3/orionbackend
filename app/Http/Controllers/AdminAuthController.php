<?php

namespace App\Http\Controllers;

use App\Models\Empresas;
use App\Models\Personal;
use App\Models\Sucursales;
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

    function companies() {
        $usuario = JWTAuth::parseToken()->authenticate();
        if(!$usuario) {
            return response()->json(['message' => 'No se ha podido auntenticar el usuario']);
        }

        $companis = Empresas::all();
        foreach ($companis as $compani) {
            $sucursales = Sucursales::where('id_empresa', $compani->id_empresa)->get();

            $empresas_sucursales = [
                'id_empresa' => $compani->id_empresa,
                'nombre_empresa' => $compani->nombre,
                'sucursales' => $sucursales,
            ];

            $resultado[] = $empresas_sucursales;
        }

        return response()->json(['empresas_sucursales' => $resultado]);
    }
}
