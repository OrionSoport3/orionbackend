<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class File extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = ['name', 'mime_type', 'content'];

    public function carpeta()
    {
        return $this->belongsTo(Carpetas::class, 'id_carpeta', 'id_carpetas');
    }
}
