<?php

namespace App\Http\Controllers;

use App\Events\ActivitiesFetched;
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
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Facades\JWTAuth;

class AdminAuthController extends Controller
{
    public function __construct() {
        $this->middleware('jwt:api', ['except' => ['login', 'register', 'get_activities']]);
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

        return response()->json(['empresas_sucursales' => $resultado, 'personal' => $allPeople, 'vendedor' => $vendedores], 200);

    }

    function filtrarInfo (Request $request) {
        $validation = $request->validate([
            'fecha' => 'date|string|max:10',
            'nombre_proyecto' => 'string',
            'empresa_nombre' => 'array|string',
        ]);

        $fecha_Info = $validation['fecha'];
        $nombreInfo = $validation['nombre_proyecto'];
        $empresaInfo = $validation['empresa_nombre'];
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

    function fetchActivities(Request $request) {
            try {
                $result = [];

                if ($request->all()) {
                    $validates = $request->validate([
                        'fecha_inicio' => 'string',
                        'fecha_final' => 'string',
                        'nombre_actividad' => 'string',
                        'empresas' => 'array',
                    ]);
        
                    $fecha_inicio = $validates['fecha_inicio'];
                    $fecha_final = $validates['fecha_final'];
                    $busqueda = $validates['nombre_actividad'];
                    $empresas = $validates['empresas'];


        
                    if ($fecha_inicio && $fecha_final && $busqueda && $empresas) {

                        $sucursalesBuscar = [];
                        $actividadesBusqueda = [];

                        foreach ($empresas as $empresa) {
                            $sucursales = Sucursales::where('id_empresa', $empresa)->select('id_sucursales', 'id_empresa', 'nombre')->get();

                            foreach ($sucursales as $sucursal) {
                                $sucursalesBuscar[] = $sucursal;
                            }

                        }
                        
                        if (empty($sucursalesBuscar)) {
                            return response()->json(['sin_sucursales' => 'No se han encontrado sucursales con los datos proporcionados']);
                        }
                        

                        foreach ($sucursalesBuscar as $suc) {

                            $busquedaFechas = Actividades::where('fecha_inicio', '>=', $fecha_inicio)->where('fecha_final', '<=', $fecha_final)->where('titulo', 'ilike', "%$busqueda%")->where('id_sucursal',$suc->id_sucursales)->first();
                            
                            if ($busquedaFechas) {
                                # code...
                                $actividadesBusqueda[] = $busquedaFechas;
                            }

                        }

                        if (empty($actividadesBusqueda)) {
                            return response()->json(['message' => 'No se han encontrado actividades con los datos proporcionados'], 404);
                        }
                        

                        foreach ($actividadesBusqueda as $activity) {

                            $puente = ActivityPersonal::where('id_actividades', $activity->id_actividades)->get();
                            $resultado_personas = [];

                            foreach ($puente as $personaX) {
                                $persona = Personal::where('id', $personaX->id_personal)->select('id','nombre')->first();

                                $nombres_array = [
                                    'nombre' => $persona
                                ];

                                $resultado_personas[] = $nombres_array;

                            }

                            $sucursal = Sucursales::where('id_sucursales',$activity->id_sucursal)->select('id_sucursales', 'id_empresa', 'nombre')->first();
                            $empresa = Empresas::where('id_empresa', $sucursal->id_empresa)->first();
                            $vehiculo = Vehiculos::where('id_vehiculo', $activity->id_vehiculo)->select('id_vehiculo', 'modelo')->first();

                           $resultado_actividades = [
                            'id_actividad' => $activity->id_actividades,
                            'sucursal' => $sucursal->nombre,
                            'empresa' => $empresa->nombre,
                            'titulo' => $activity->titulo,
                            'resumen' => $activity->resumen,
                            'fecha_inicio' => $activity->fecha_inicio,
                            'fecha_final' => $activity->fecha_final,
                            'vendedor' => $activity->vendedor,
                            'inconvenientes' => $activity->inconvenientes,
                            'vehiculo' => $vehiculo->modelo,
                            'estado' => $activity->estado,
                            'personal' => $resultado_personas,
                           ]; 
                           
                           $result[] = $resultado_actividades;
                            
                        }

                        Log::info('Emitiendo evento ActivitiesFetched con datos:', $result);

                        event(new ActivitiesFetched($result));

                        return response()->json(['actividades' => $result],200);
                    }
                }

                $activities = Actividades::all();
                foreach ($activities as $actividad) {
                    $puente = ActivityPersonal::where('id_actividades', $actividad->id_actividades)->get();
                    $resultado_personas = [];
                    
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

                Log::info('Emitiendo evento ActivitiesFetched con datos:', $result);

                event(new ActivitiesFetched($result));

                return response()->json(['actividades' => $result],200);

            } catch (Exception $th) {
                return response()->json(['error' => $th->getMessage()], 500);
            }
        
        }

}
