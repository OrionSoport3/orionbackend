<?php

namespace App\Http\Controllers;

use App\Models\Actividades;
use App\Models\Empresas;
use App\Models\Sucursales;
use App\Models\Vehiculos;
use Exception;
use Illuminate\Http\Request;

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
            return response()->json(['error' => $th]);
        }


    }
}
