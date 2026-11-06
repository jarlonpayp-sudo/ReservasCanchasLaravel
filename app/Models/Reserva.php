<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reserva extends Model
{
    protected $table = 'reservas';

    public $timestamps = false;

    protected $fillable = [
        'cancha_id',
        'usuario_id',
        'fecha',
        'hora',
        'estado'
    ];
}