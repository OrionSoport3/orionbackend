<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tokens extends Model
{
    use HasFactory;

    // Define the table if it doesn't follow the Laravel convention
    protected $table = 'tokens';

    protected $fillable = [ 'token' ];

}
