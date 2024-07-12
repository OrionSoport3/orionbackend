<?php

namespace App\Http\Controllers;

use App\Models\Actividades;
use App\Models\ActivityPersonal;
use App\Models\Empresas;
use App\Models\Personal;
use App\Models\Sucursales;
use App\Models\User;
use App\Models\Vehiculos;
use App\Models\Vendedores;
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

        $vendedores = Vendedores::all();

        return response()->json(['empresas_sucursales' => $resultado, 'personal' => $allPeople, 'vendedor' => $vendedores]);

    }

    function fetchFotos() {
        $vehiculos = Vehiculos::all();

        $resultadoFotos = [];

        foreach ($vehiculos as $vehiculo) {
            $fotoPath = $vehiculo->ruta;
    
            $fotoUrl = asset($fotoPath);
    
            if (file_exists(public_path($fotoPath))) {
                $resultadoFotos[] = [
                    'carro_id' => $vehiculo->id_vehiculo,
                    'modelo' => $vehiculo->modelo,
                    'ruta' => $fotoUrl,
                    'descripcion' => $vehiculo->descripcion,
                ];
            }
        }
    
        return response()->json(['fotos' => $resultadoFotos]);
    }

    function postActivitie(Request $request) {

        $resultado = [];

            $empresa = Empresas::where('nombre', $request->empresa)->first();
            $sucursal = Sucursales::where('nombre', $request->sucursal)->first();
            $vehiculo = Vehiculos::where('modelo', $request->vehiculo)->first();
            // return response()->json(['empresa encontrada:' => $empresa, 'sucursal de la empresa encontrada:' => $sucursal, 'vehiculo encontrado' => $vehiculo]);
            
            $actividad = new Actividades();
            $actividad->id_sucursal = $sucursal->id_sucursales;
            $actividad->id_vehiculo = $vehiculo->id_vehiculo;
            $actividad->titulo = $request->nombre_proyecto;
            $actividad->resumen = $request->resume;
            $actividad->fecha_inicio = $request->fecha_inicial;
            $actividad->fecha_final = $request->fecha_final;
            $actividad->inconvenientes = $request->inconveniente;
            $actividad->vendedor = $request->vendedor;
            $actividad->estado = 'EN CURSO';
            $actividad->save();

            foreach ($request->personal as $persona) {
                
                $encargado = Personal::where('nombre', $persona)->first();

                $puente = ActivityPersonal::create([
                    'id_actividades' => $actividad->id_actividades,
                    'id_personal' => $encargado->id,
                ]);
                $puente->save();
                $resultado[] = $puente;
            }            

            return response()->json(['message' => 'autenticado con exito', 'sucursal' => $sucursal, 'empresa' => $empresa, 'solicitud recibida' => $request], 200);
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

    function fetchActivities() {
            try {
                $resultado_personas = [];
            $result = [];
                $activities = Actividades::all();
                foreach ($activities as $actividad) {
                    $puente = ActivityPersonal::where('id_actividades', $actividad->id_actividades)->get();
                    
                    foreach ($puente as $personitas) {
                        
                        $persona = Personal::where('id', $personitas->id_personal)->first();
                        $nombre_personas = $persona->nombre;

                        $nombres_array = [
                            'nombre' => $nombre_personas,
                        ];
                        
                        $resultado_personas[] = $nombres_array;
                    }

                    $sucursal = Sucursales::where('id_sucursales', $actividad->id_sucursal)->first();
                    $empresa = Empresas::where('id_empresa', $sucursal->id_empresa)->first();
                    $vehiculo = Vehiculos::where('id_vehiculo', $actividad->id_vehiculo)->first();
                    
                    $actividad = [
                        'id_actividad' => $actividad->id_actividades,
                        'sucursal' => $sucursal->nombre,
                        'empresa' => $empresa->nombre,
                        'titulo' => $actividad->titulo,
                        'resumen' => $actividad->resumen,
                        'fecha_inicio' => $actividad->fecha_inicio,
                        'fecha_final' => $actividad->fecha_final,
                        'vendedor' => $actividad->vendedor,
                        'inconvenientes' => $actividad->inconvenientes,
                        'vehiculo' => $vehiculo->modelo,
                        'estado' => $actividad->estado,
                        'personal' => $resultado_personas,
                    ]; 

                    $result[] = $actividad;
                }
                return response()->json(['actividad' => $result], 200);
            } catch (Exception $th) {
                return response()->json(['Hubo un error al obtener la información' => $th]);
            }
        
        }

}
