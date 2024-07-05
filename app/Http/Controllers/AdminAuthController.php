<?php

namespace App\Http\Controllers;

use App\Models\Actividades;
use App\Models\ActivityPersonal;
use App\Models\Empresas;
use App\Models\Personal;
use App\Models\Sucursales;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;

class AdminAuthController extends Controller
{
    public function __construct() {
        $this->middleware('auth:api', ['except' => ['login', 'register']]);
    }

    public function logout() {
        JWTAuth::parseToken()->invalidate();
        return response()->json(['message' => 'Logged out successfully'],200);
    }

    public function isStillLogged() {
       
    }

    public function getUsers()
    {
        if(!JWTAuth::parseToken()->authenticate()) {
            return response()->json(['message' => 'No se ha podido autenticar el usuario']);
        }
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

    function postActivitie(Request $request) {
        if (!JWTAuth::parseToken()->authenticate()) {
            return response()->json(['message' => 'No se ha podido auntenticar el usuario']);
        }

        $sucursal = Sucursales::where('nombre', $request->sucursal)->first();
        $empresa = Empresas::where('id_empresa', $sucursal->id_empresa)->first();

        $actividad = new Actividades;
        


        foreach ($request->personal as $persona) {

            $encargado = Personal::where('nombre', $persona)->get();

            $puente = new ActivityPersonal;
            $puente->id_personal = $encargado->id;
            $puente->save();
        }

        return response()->json(['message' => 'autenticado con exito', 'sucursal' => $sucursal, 'empresa' => $empresa], 200);
    }
}
