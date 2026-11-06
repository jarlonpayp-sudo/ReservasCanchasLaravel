<?php

namespace App\Repositories;

use App\Models\Cancha;

class CanchaRepository
{
    public function registrar(array $datos)
    {
        return Cancha::create($datos);
    }

    public function consultarPorCodigo(string $codigo)
    {
        return Cancha::where('codigo', $codigo)->first();
    }

    public function listar()
    {
        return Cancha::all();
    }

    public function actualizar(string $codigo, array $datos)
    {
        $cancha = $this->consultarPorCodigo($codigo);

        if (!$cancha) {
            return null;
        }

        $cancha->update($datos);

        return $cancha;
    }

    public function consultarPorId(int $id)
    {
        return Cancha::find($id);
    }
}