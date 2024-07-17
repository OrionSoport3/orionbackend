<?php

namespace App\Http\Controllers;

use App\Models\File;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class FileController extends Controller
{
    public function upload(Request $request)
    {
        try {
            $request->validate([
                'file' => 'required|file',
                'id_carpeta' => 'exists:carpetas,id_carpetas',
            ]);
    
            $file = $request->file('file');
            $path = $file->store('public/files'); 
            $fileModel = new File();
            $fileModel->name = $file->getClientOriginalName();
            $fileModel->mime_type = $file->getMimeType();
            $fileModel->content = $path;
            $fileModel->id_carpeta = $request->input('id_carpeta');
            $fileModel->save();
            $fileUrl = Storage::url($path);
    
            return response()->json(['message' => 'File uploaded successfully', 'path' => $fileUrl], 201);
    
        } catch (Exception $th) {
            Log::error($th->getMessage());
            return response()->json(['error' => 'File upload failed', $th], 500);
        }

    }


}
