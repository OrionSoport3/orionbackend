<?php

namespace App\Http\Controllers;

use App\Models\Actividades;
use App\Models\Carpetas;
use App\Models\File;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class FileController extends Controller
{
    function upload(Request $request)
    {
        try {

            $validated = $request->validate([
                'file' => 'required|file',
                'id_carpeta' => 'required|string|integer',
                'nombre_carpeta' => 'required|string'
            ]);

            $file = $validated['file'];

            $nameFile = $file->getClientOriginalName();
            $archivo = File::where('name', $nameFile)->where('id_carpeta', $validated['id_carpeta'])->where('mime_type', $validated['file']->getMimeType())->first();

            if ($archivo) {
                return response()->json(['error' => 'Document already exists in the specified folder'], 409);
            }

            $saveOriginalName = str_replace(' ', '_', $nameFile);
            $carpeta = Carpetas::find($validated['id_carpeta']);
            $activividad = Actividades::find($carpeta->id_actividad);

            $id_actividad = $activividad->id_actividades;
            $nombreCarpeta = str_replace(' ', '_', $validated['nombre_carpeta']);
            $path = $file->storeAs("public/files/$id_actividad/$nombreCarpeta", $saveOriginalName);

            if (!Storage::exists("public/files/$id_actividad/$nombreCarpeta")) {
                Storage::makeDirectory("public/files/$id_actividad/$nombreCarpeta");
            }
            
            $fileUrl = Storage::url($path);
            $fileModel = new File();
            $fileModel->name = $file->getClientOriginalName();
            $fileModel->mime_type = $file->getMimeType();
            $fileModel->content = $path;
            $fileModel->id_carpeta = $request->input('id_carpeta');
            $fileModel->url = $fileUrl;
            $fileModel->save();
    
            return response()->json(['message' => 'File uploaded successfully', 'path' => $fileUrl], 201);
    
        } catch (ValidationException $ve) {
            return response()->json(['error' => 'Validation failed', 'messages' => [$ve->errors()], 'valores de request' => ['id' => $request->id_carpeta, 'nombre de la carpeta' => $request->nombre_carpeta, 'documento' => $request->file]], 422);

        } catch (Exception $th) {
            Log::error($th->getMessage());
            return response()->json(['error' => 'File upload failed', 'message' => [$th->getMessage(), $validated]], 500);

        }
    }

    function replaceDocument (Request $request) {
        try {

            $validation = $request->validate([
                'file' => 'required|file',
                'id_carpeta' => 'required|string',
                'nombre_carpeta' => 'required|string'
            ]);

            $getFileName = $validation['file']->getClientOriginalName();

            $archivo = File::where('name', $getFileName)->where('id_carpeta', $validation['id_carpeta'])->where('mime_type', $validation['file']->getMimeType())->first();

            if (!$archivo && !Storage::exists($archivo->content)) {
                return response()->json(['message' => 'No se ha encontrado el archivo'], 400);
            }

            $archivo->delete();
            Storage::delete($archivo->content);
    
            return response()->json(['message' => 'File deleted successfully'], 200);

        } catch (Exception $th) {
            return response()->json(['error' => 'File upload failed', 'message' => [$th->getMessage()]], 500);
        }
    }

    function fetchDocuments(Request $request) {

        try {
            //code...
            $actividad = Actividades::where('id_actividades',$request->id_actividad)->first();
            $carpetas = Carpetas::where('id_actividad', $actividad->id_actividades)->get();

            $carpetasArray = [];
            foreach ($carpetas as $carpeta) {
                $documentos = File::where('id_carpeta', $carpeta->id_carpetas)->get();
                $documentosArray = [];
                
                foreach ($documentos as $documento) {
                    $documentPath = $documento->url;
    
                    $carpeta = Carpetas::find($documento->id_carpeta);
                    $id_carpeta = $carpeta->id_carpeta;
                    
                    $documentUrl = asset($documentPath);
        
                    if (file_exists(public_path($documentPath))) {
                       $documentito = [
                        'documento_id' => $documento->id,
                        'nombre' => $documento->name,
                        'documento_url' => $documentUrl,
                        'mime_type' => $documento->mime_type,
                        'id_carpeta' => $id_carpeta,
                       ];
                    }

                    $documentosArray[] = $documentito;
                }

                $carpetasDocumentosArray = [
                    'id_carpeta' => $carpeta->id_carpetas,
                    'id_actividad' => $carpeta->id_actividad,
                    'nombre' => $carpeta->nombre,
                    'documentos' => $documentosArray,
                ];

                $carpetasArray[] = $carpetasDocumentosArray;

            }

            return response()->json($carpetasArray, 200);

        } catch (Exception $th) {
            return response()->json(['error' => 'Failed to fetch documents', 'message' => $th->getMessage()], 500);
        }

    }


}
