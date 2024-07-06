<?php

namespace App\Http\Controllers;

use App\Models\Actividades;
use App\Models\ActivityPersonal;
use App\Models\Empresas;
use App\Models\Personal;
use App\Models\Sucursales;
use App\Models\User;
use App\Models\Vehiculos;
use Exception;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;

class AdminAuthController extends Controller
{
    public function __construct() {
        $this->middleware('jwt:api', ['except' => ['login', 'register']]);
    }

    public function logout() {
        JWTAuth::parseToken()->invalidate();
        return response()->json(['message' => 'Logged out successfully'],200);
    }

    public function fetchAllInfo() {

        if(!JWTAuth::parseToken()->authenticate()) {
            return response()->json(['message' => 'No se ha podido autenticar el usuario']);
        }
        $allPeople = Personal::all();

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
        return response()->json(['empresas_sucursales' => $resultado, 'personal' => $allPeople]);

    }

    function fetchFotos() {
        $vehiculos = Vehiculos::all();

        $resultadoFotos = [];

        foreach ($vehiculos as $vehiculo) {
            $fotoPath = $vehiculo->ruta;
    
            $fotoUrl = asset($fotoPath);
    
            if (file_exists(public_path($fotoPath))) {
                $resultadoFotos[] = [
                    'modelo' => $vehiculo->modelo,
                    'ruta' => $fotoUrl,
                    'descripcion' => $vehiculo->descripcion,
                ];
            }
        }
    
        return response()->json(['fotos' => $resultadoFotos]);
    }

    function postActivitie(Request $request) {
        if (!JWTAuth::parseToken($request->token)) {
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

    public function guardarFoto(Request $request)
    {
        if (!$request->hasFile('foto')) {
            return response()->json(["message" => 'No se ha encontrado ninguna imagen'], 400);
        }

        $imageName = time().'.'.$request->foto->extension();
        $request->foto->move(public_path('vehiculos'), $imageName);
        $photoPath = 'vehiculos/'.$imageName;

        $vehiculo = Vehiculos::create([
            'modelo' => $request->modelo,
            'ruta' => $photoPath,
            'descripcion' => $request->descripcion,
        ]);

        return response()->json(["message" => 'Foto guardada con éxito', "vehiculo" => $vehiculo], 200);
    }
}
