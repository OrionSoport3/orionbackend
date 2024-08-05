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
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
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

                if ($request->all()) {
        
                    $fecha_inicio = $request->input('fecha_inicio');
                    $fecha_final = $request->input('fecha_final');
                    $busqueda = $request->input('nombre_actividad');
                    $empresas = $request->input('empresas');
                    $estado = $request->input('estado');
                                        
                    $case = ($fecha_inicio ? '1' : '0') .
                    ($fecha_final ? '1' : '0') .
                    ($busqueda ? '1' : '0') .
                    ($empresas ? '1' : '0').
                    ($estado ? '1' : '0');

                    // Obtén el estado de la solicitud

                    // Inicializa los valores de estado
                    $TODOS = false;
                    $EN_CURSO = false;
                    $CANCELADO = false;
                    $FINALIZADO = false;

                    // Verifica si $estado es un array o una cadena
                    if (is_array($estado)) {
                        // Si es un array, actualiza las variables correspondientes
                        $TODOS = in_array('TODOS', $estado);
                        $EN_CURSO = in_array('EN CURSO', $estado);
                        $CANCELADO = in_array('CANCELADO', $estado);
                        $FINALIZADO = in_array('FINALIZADO', $estado);
                    } else {
                        // Si es una cadena, verifica si coincide con cada estado
                        $TODOS = ($estado === 'TODOS');
                        $EN_CURSO = ($estado === 'EN CURSO');
                        $CANCELADO = ($estado === 'CANCELADO');
                        $FINALIZADO = ($estado === 'FINALIZADO');
                    }

                    // Genera el string binario
                    $casosEstados = ($TODOS ? '1' : '0')
                                .($EN_CURSO ? '1' : '0')
                                .($CANCELADO ? '1' : '0')
                                .($FINALIZADO ? '1' : '0');


                    function BuscarActividades(EloquentCollection $activities) {
                        if ($activities->isEmpty()) {
                            return response()->json(['actividad' => []], 200);
                        }
                    
                        $result = [];
                    
                        foreach ($activities as $actividad) {
                            // Obtener personas relacionadas
                            $puente = ActivityPersonal::where('id_actividades', $actividad->id_actividades)->get();
                            $resultado_personas = [];
                    
                            foreach ($puente as $personitas) {
                                $persona = Personal::find($personitas->id_personal);
                                if ($persona) {
                                    $resultado_personas[] = ['nombre' => $persona->nombre];
                                }
                            }
                    
                            // Obtener sucursal, empresa y vehículo relacionados
                            $sucursal = Sucursales::find($actividad->id_sucursal);
                            $empresa = $sucursal ? Empresas::find($sucursal->id_empresa) : null;
                            $vehiculo = Vehiculos::find($actividad->id_vehiculo);
                    
                            $result[] = [
                                'id_actividad' => $actividad->id_actividades,
                                'sucursal' => $sucursal ? $sucursal->nombre : 'No disponible',
                                'empresa' => $empresa ? $empresa->nombre : 'No disponible',
                                'titulo' => $actividad->titulo,
                                'resumen' => $actividad->resumen,
                                'fecha_inicio' => $actividad->fecha_inicio,
                                'fecha_final' => $actividad->fecha_final,
                                'vendedor' => $actividad->vendedor,
                                'inconvenientes' => $actividad->inconvenientes,
                                'vehiculo' => $vehiculo ? $vehiculo->modelo : 'No disponible',
                                'estado' => $actividad->estado,
                                'personal' => $resultado_personas,
                            ];
                        }
                    
                        // Emitir evento y retornar respuesta
                        event(new ActivitiesFetched(['actividad' => $result]));
                        return response()->json(['actividad' => $result], 200);
                    }

                    function BuscarSucursales(array $empresasitas) {
                        $sucursalesBuscar = collect(); // Usar una colección vacía para almacenar los resultados

                        foreach ($empresasitas as $empresa) {
                            $sucursales = Sucursales::where('id_empresa', $empresa)
                                ->select('id_sucursales', 'id_empresa', 'nombre')
                                ->get();

                            $sucursalesBuscar = $sucursalesBuscar->merge($sucursales); // Fusionar los resultados
                        }

                        if ($sucursalesBuscar->isEmpty()) {
                            return response()->json([
                                'message' => 'No se han encontrado sucursales con los datos proporcionados',
                                'empresas' => $sucursalesBuscar, // Mostrar las sucursales y empresas
                                'empresasOriginales' => $empresasitas
                            ], 404);
                        }

                        return $sucursalesBuscar;
                    }
                    
                                        
                    switch ($case) {
                        case '11111':
                            $sucursalesBuscar = BuscarSucursales($empresas);
                            $actividadesBusqueda = EloquentCollection::make();;

                            foreach ($sucursalesBuscar as $suc) {

                                $actividades = Actividades::where('fecha_inicio', '>=', $fecha_inicio)->where('fecha_final', '<=', $fecha_final)->where('titulo', 'ilike', "%$busqueda%")->where('id_sucursal',$suc->id_sucursales)->where('estado', $estado)->get();
                                $actividadesBusqueda = $actividadesBusqueda->merge($actividades);
                            }

                            if ($actividadesBusqueda->isEmpty()) {
                                return response()->json(['message' => 'No se han encontrado actividades con los datos proporcionados',], 404);
                            }

                            return BuscarActividades($actividadesBusqueda);

                        case '11000':

                            $actividades = Actividades::where('fecha_inicio', '>=', $fecha_inicio)->where('fecha_final', '<=', $fecha_final)->get();

                            foreach ($actividades as $actividad) {
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
            
                            event(new ActivitiesFetched(['actividad' => $result]));
            
                            return response()->json(['actividad' => $result], 200);
                        case '00100':
                            $actividades = Actividades::where('titulo', 'ilike', "%$busqueda%")->get();

                            foreach ($actividades as $actividad) {
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
            
                            event(new ActivitiesFetched(['actividad' => $result]));
            
                            return response()->json(['actividad' => $result], 200);
                        case '00010':
                            $sucursalesBuscar = [];
                            $actividadesBusqueda = [];

                            foreach ($empresas as $empresa) {
                                $sucursales = Sucursales::where('id_empresa', $empresa)->select('id_sucursales', 'id_empresa', 'nombre')->get();

                                foreach ($sucursales as $sucursal) {
                                    $sucursalesBuscar[] = $sucursal;
                                }

                            }

                            
                            if (empty($sucursalesBuscar)) {
                                return response()->json(['message' => 'No se han encontrado sucursales con los datos proporcionados', 'empresas' => [$sucursalesBuscar, $empresas]], 404);
                            }

                            foreach ($sucursalesBuscar as $suc) {

                                $busquedaFechas = Actividades::where('id_sucursal',$suc->id_sucursales)->first();
                                
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


                                    $resultado_personas[] = $persona;

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


                            event(new ActivitiesFetched(['actividad' => $result]));

                            return response()->json(['actividad' => $result],200);
                        case '00001':
                            switch ($casosEstados) {
                                case '1000':
                                    $actividades = Actividades::all();
                                    return BuscarActividades($actividades);
                                case '0100':
                                case '0110':
                                case '0111':
                                case '0101':
                                case '0011':
                            }
                        case '11100':
                            $actividades = Actividades::where('fecha_inicio', '>=', $fecha_inicio)->where('fecha_final', '<=', $fecha_final)->where('titulo', 'ilike', "%$busqueda%")->get();
                            return BuscarActividades($actividades);
                        case '00110':
                            $sucursalesBuscar = [];
                            $actividadesBusqueda = [];

                            foreach ($empresas as $empresa) {
                                $sucursales = Sucursales::where('id_empresa', $empresa)->select('id_sucursales', 'id_empresa', 'nombre')->get();

                                foreach ($sucursales as $sucursal) {
                                    $sucursalesBuscar[] = $sucursal;
                                }

                            }

                            
                            if (empty($sucursalesBuscar)) {
                                return response()->json(['message' => 'No se han encontrado sucursales con los datos proporcionados', 'empresas' => [$sucursalesBuscar, $empresas]], 404);
                            }

                            foreach ($sucursalesBuscar as $suc) {

                                $busquedaFechas = Actividades::where('id_sucursal',$suc->id_sucursales)->where('titulo', 'ilike', $busqueda)->first();
                                
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


                                    $resultado_personas[] = $persona;

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


                            event(new ActivitiesFetched(['actividad' => $result]));

                            return response()->json(['actividad' => $result],200);
                        case '11010':

                        default:
                        return response()->json(['Los datos obtenidos no han arrojado ninguna busqueda', $case]);
                    }

                }

                // $activities = Actividades::all();
                // foreach ($activities as $actividad) {
                //     $puente = ActivityPersonal::where('id_actividades', $actividad->id_actividades)->get();
                //     $resultado_personas = [];
                    
                //     foreach ($puente as $personitas) {
                        
                //         $persona = Personal::where('id', $personitas->id_personal)->first();
                //         $nombre_personas = $persona->nombre;

                //         $nombres_array = [
                //             'nombre' => $nombre_personas,
                //         ];
                        
                //         $resultado_personas[] = $nombres_array;
                //     }

                //     $sucursal = Sucursales::where('id_sucursales', $actividad->id_sucursal)->first();
                //     $empresa = Empresas::where('id_empresa', $sucursal->id_empresa)->first();
                //     $vehiculo = Vehiculos::where('id_vehiculo', $actividad->id_vehiculo)->first();
                    
                //     $actividad = [
                //         'id_actividad' => $actividad->id_actividades,
                //         'sucursal' => $sucursal->nombre,
                //         'empresa' => $empresa->nombre,
                //         'titulo' => $actividad->titulo,
                //         'resumen' => $actividad->resumen,
                //         'fecha_inicio' => $actividad->fecha_inicio,
                //         'fecha_final' => $actividad->fecha_final,
                //         'vendedor' => $actividad->vendedor,
                //         'inconvenientes' => $actividad->inconvenientes,
                //         'vehiculo' => $vehiculo->modelo,
                //         'estado' => $actividad->estado,
                //         'personal' => $resultado_personas,
                //     ]; 

                //     $result[] = $actividad;
                // }

                // event(new ActivitiesFetched(['actividad' => $result]));

                // return response()->json(['actividad' => $result],200);

            } catch (\Throwable $th) {
                return response()->json(['message' => $th->getMessage()], 500);
            }
        
        }
        
        public function getUserIdAUTH(Request $request)
        {
            try {
                $user = JWTAuth::parseToken()->authenticate();
                return response()->json(['user_id' => $user->id]);
            } catch (Exception $e) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
        }

}
