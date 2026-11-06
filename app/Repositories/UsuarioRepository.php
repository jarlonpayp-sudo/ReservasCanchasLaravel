<?php

namespace App\Repositories;

use App\Models\Usuario;

class UsuarioRepository
{
    public function registrar(array $datos)
    {
        return Usuario::create($datos);
    }

    public function consultarPorDocumento(string $documento)
    {
        return Usuario::where('documento', $documento)->first();
    }

    public function consultarPorId(int $id)
    {
        return Usuario::find($id);
    }

    public function listar()
    {
        return Usuario::all();
    }
}