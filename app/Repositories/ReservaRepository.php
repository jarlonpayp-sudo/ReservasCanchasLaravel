<?php

namespace App\Repositories;

use App\Models\Reserva;

class ReservaRepository
{
    public function registrar(array $datos)
    {
        return Reserva::create($datos);
    }

    public function consultarPorId(int $id)
    {
        return Reserva::find($id);
    }

    public function listar()
    {
        return Reserva::all();
    }

    public function buscarReserva(
        int $canchaId,
        string $fecha,
        string $hora
    ) {
        return Reserva::where('cancha_id', $canchaId)
            ->where('fecha', $fecha)
            ->where('hora', $hora)
            ->first();
    }
}