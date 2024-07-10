<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Personal extends Model
{
    use HasFactory;

    protected $table = 'personal';

    public function nombre_personal()
    {
        return $this->belongsToMany(Departamentos::class, 'actividades_personal');
    }
}
