<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Actividades extends Model
{
    use HasFactory;

    protected $table = 'actividades';


    public function puente() {
        return $this->belongsToMany(ActivityPersonal::class, 'actividades_personal');
    }

    public $timestamps = false;

    protected $primaryKey = null;
    
    public $incrementing = false;
}
