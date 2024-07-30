<?php

namespace App\Http\Controllers;

use App\Models\Actividades;
use App\Models\Carpetas;
use App\Models\Empresas;
use App\Models\File;
use App\Models\Sucursales;
use App\Models\Vehiculos;
use Exception;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ServiceController extends Controller
{
    function getService(Request $request) {

        $servicio = [];
        
        try {
            $validated = $request->validate([
                'id' => 'required|integer',
            ]);

            $service_id = Actividades::find($validated['id']);
            $sucursal = Sucursales::find($service_id->id_sucursal);
            $empresa = Empresas::find($sucursal->id_empresa);
            $vehiculo = Vehiculos::find($service_id->id_vehiculo);

            $servicio = [
                'id_actividad' => $service_id->id_actividades,
                'titulo' => $service_id->titulo,
                'sucursal' => $sucursal->nombre,
                'empresa' => $empresa->nombre,
                'inconvenientes' => $service_id->inconvenientes,
                'resumen' => $service_id->resumen,
                'vendedor' => $service_id->vendedor,
                'vehiculo' => $vehiculo->modelo,
                'fecha_inicial' => $service_id->fecha_inicio,
                'fecha_final' => $service_id->fecha_final,
                'estado' => $service_id->estado
            ];

            return response()->json(['respuesta' => $servicio], 200);
            
        } catch (Exception $th) {
            return response()->json(['error' => $th->getMessage()]);
        }
    }

    function postFile(Request $request) {
        try {
            $validation = $request->validate([
                'id' => 'required|integer',
                'nombre' => 'required|string'
            ], [
                'id.required' => 'No se ha encontrado un id de la actividad',
                'nombre.required' => 'El nombre de la carpeta no puede quedar vacío'
            ]);

            $actividadId = Actividades::find($validation['id']);
    
            $carpeta = Carpetas::create([
                'id_actividad' => $actividadId->id_actividades,
                'nombre' => $validation['nombre'],
            ]);

            return response()->json(['Carpeta creada con éxito', $carpeta], 202);
            
        } catch (ValidationException $ve) {
            return response()->json(['error' => 'File has not been created', 'message' => $ve->errors()], 422);
        } catch (Exception $th) {
            return response()->json(['Ocurrió un error al crear la carpeta', $th, $request['id'], $request->nombre], 500);
        }

    }

    function getCarpetas(Request $request) {

        $carpetas = Carpetas::where('id_actividad', $request->id)->get();
        return response()->json(['carpetas' => $carpetas], 200);
    }


    function deleteCarpetaAndDocuments(Request $request) {
        $validation = $request->validate([
            'id_actividad' => 'required|integer',
            'id_carpeta' => 'required|integer'
        ]);

        $carpetas = Carpetas::where('id_actividad', $validation['id_actividad'])->where('id_carpetas', $validation['id_carpeta'])->first();

        if (!$carpetas) {
            return response()->json(['No se ha encontrado la carpeta con los datos proporcionados'], 404);
        }

        $actividadId = $carpetas->id_actividad;
        $nombreCARPETA = $carpetas->nombre;
        $carpetaId = $carpetas->id_carpetas;


        try {
            $documentos = File::where('id_carpeta', $carpetaId)->get();

            if ($documentos) {

                if (!Storage::exists("deleted/$actividadId")) {
                    Storage::makeDirectory("deleted/$actividadId");
                }

                foreach ($documentos as $documento) {
                    $sourcePath = $documento->content;
                    $destinationPath = "deleted/$actividadId/" . basename($sourcePath);
        
                    if (Storage::exists($sourcePath)) {
                        Storage::move($sourcePath, $destinationPath);
                    } else {
                        return response()->json(['message' => 'No se ha encontrado el archivo'], 400);
                    }
                }

                DB::transaction(function () use ($carpetas) {
                    File::where('id_carpeta', $carpetas->id_carpeta)->delete();
                    $carpetas->delete();
                });

            }
            if (Storage::exists("public/files/$actividadId/$nombreCARPETA")) {
                Storage::deleteDirectory("public/files/$actividadId/$nombreCARPETA");
            }
        
            return response()->json(['message' => 'Archivos eliminados exitosamente.'], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e], 500);
        }
    }

    function updateCarpeta(Request $request) {

        $validateData = $request->validate([
            'id_actividad' => 'required|integer',
            'id_carpeta' => 'required|integer',
            'nuevo_nombre' => 'required|string|max:10'
        ]);

        $carpeta = Carpetas::where('id_actividad', $validateData['id_actividad'])->where('id_carpetas', $validateData['id_carpeta'])->first();

        if (!$carpeta) {
             return response()->json(['message' => 'No se ha encontrado la carpeta'], 404);
        }

        try {
            $idActividad = $carpeta->id_actividad;
            $id_carpeta = $carpeta->id_carpetas;
            $nuevoNombre = str_replace(' ', '_', $validateData['nuevo_nombre']);
            $archivos = File::where('id_carpeta', $id_carpeta)->get();
            $archivito = File::where('id_carpeta', $id_carpeta)->first();

            if (!$archivos->isEmpty()) {
                foreach ($archivos as $archivo) {

                    if (!Storage::exists($archivo->content . "/" . basename($archivo->url))) {
                        return response()->json(['message' => 'El archivo guardado en la base de datos no existe en el almacenamiento local', $archivo->content], 404);
                    }
    
                    $nombreArchivo = str_replace(' ', '_', $archivo->name);
                    $path = $archivo->content . "/" . basename($nombreArchivo);
                    $newPath = "public/files/$idActividad/$nuevoNombre/" . basename($nombreArchivo);
    
                    Storage::move($path, $newPath);
                    $archivo->content = "public/files/$idActividad/$nuevoNombre";
                    $archivo->url = "/storage/files/$idActividad/$nuevoNombre/". basename($nombreArchivo);
                    $archivo->save();
                    
        
                    Storage::deleteDirectory($newPath);
                }

                Storage::deleteDirectory($archivito->content);

            }

            $carpeta->nombre = $validateData['nuevo_nombre'];
            $carpeta->save();

            return response()->json(['Nombre de la carpeta actualizada exitosamente'], 200);

        } catch (Exception $th) {
            return response()->json(['message' => 'Ha ocurrido un error con el archivo', 'mensajito' =>$th->getMessage()]);
        }
    }
}
