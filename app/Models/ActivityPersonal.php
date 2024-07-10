<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityPersonal extends Model
{
    use HasFactory;

    protected $table = 'actividades_personal';

    protected $fillable = ['id_personal'];

    public function muchas_actividades () {
        return $this->belongsTo(Actividades::class,'', '' );
    }

    public function mucho_personal() {
        return $this->hasMany(Personal::class, 'id_personal', 'id');
    }

    public $timestamps = false;

    protected $primaryKey = null;
    
    public $incrementing = false;

}
