<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityPersonal extends Model
{
    use HasFactory;

    protected $table = 'actividades_personal';

    public function personal_activities() {
        
        return $this->belongsTo(Actividades::class, 'id_actividades', 'id_actividades');
    }

    public function personal() {
        
        return $this->hasMany(Personal::class, 'id_personal', 'id');
    }
}
