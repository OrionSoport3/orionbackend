<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vendedores extends Model
{
    use HasFactory;


    protected $table = 'vendedores';

    protected $fillable = [
        'nombre_vendedor',
    ];

    public $timestamps = false;

    protected $primaryKey = null;
    
    public $incrementing = false;
}
