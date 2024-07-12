<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Actividades extends Model
{
    use HasFactory;

    protected $table = 'actividades';


    public function puente() {
        return $this->hasMany(ActivityPersonal::class, 'id_actividades', 'id_actividades');
    }

    public $timestamps = false;

    protected $primaryKey = 'id_actividades';

    public $incrementing = true;
    
}
