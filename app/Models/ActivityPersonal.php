<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityPersonal extends Model
{
    use HasFactory;

    protected $table = 'actividades_personal';

    protected $fillable = ['id_actividades', 'id_personal']; 

    public function mucho_personal() {
        return $this->belongsTo(Actividades::class, 'id_actividades', 'id_actividades_personal');
    }

    public $timestamps = false;

    protected $primaryKey = 'id_actividades_personal';  
    public $incrementing = true;
}