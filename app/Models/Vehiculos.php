<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vehiculos extends Model
{
    use HasFactory;

    protected $table = 'vehiculos';

    protected $fillable = [
        'modelo',
        'ruta',
        'descripcion',
    ];

    public $timestamps = false;

    protected $primaryKey = 'id_vehiculo';
    
    public $incrementing = false;


}
