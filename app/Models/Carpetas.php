<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Carpetas extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'carpetas';

    protected $fillable = [
        'nombre',
        'id_actividad'
    ];

    public $timestamps = false;

    protected $primaryKey = 'id_carpetas';
    
    public $incrementing = false;

    public function files()
    {
        return $this->hasMany(File::class, 'id_carpeta', 'id_carpetas');
    }
}
