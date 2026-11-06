<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cancha extends Model
{
    protected $table = 'canchas';

    public $timestamps = false;

    protected $fillable = [
        'codigo',
        'nombre',
        'tipo',
        'estado'
    ];
}