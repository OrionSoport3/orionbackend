<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sucursales extends Model
{
    use HasFactory;

    protected $table = 'sucursales';

    protected $primaryKey = 'id_sucursales';

    public function sucursales_muchas() {
        return $this->belongsTo(Empresas::class, 'id_empresa', 'id_empresa');
    }
}
