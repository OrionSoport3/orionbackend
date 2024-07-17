<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class File extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'mime_type', 'content'];

    public function carpeta()
    {
        return $this->belongsTo(Carpetas::class, 'id_carpeta', 'id_carpetas');
    }
}
