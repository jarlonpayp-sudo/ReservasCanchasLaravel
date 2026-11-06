<?php

namespace App\Services;

use App\Repositories\CanchaRepository;

class CanchaService
{
    private CanchaRepository $repository;

    public function __construct(CanchaRepository $repository)
    {
        $this->repository = $repository;
    }

    public function registrarCancha(array $datos)
    {
        $canchaExistente = $this->repository->consultarPorCodigo(
            $datos['codigo']
        );

        if ($canchaExistente) {
            throw new \Exception(
                'La cancha ya existe con ese código'
            );
        }

        return $this->repository->registrar($datos);
    }

    public function consultarCancha(string $codigo)
    {
        $cancha = $this->repository->consultarPorCodigo($codigo);

        if (!$cancha) {
            throw new \Exception(
                'La cancha no existe'
            );
        }

        return $cancha;
    }

    public function listarCanchas()
    {
        return $this->repository->listar();
    }

    public function actualizarCancha(
        string $codigo,
        array $datos
    ) {
        $cancha = $this->repository->actualizar(
            $codigo,
            $datos
        );

        if (!$cancha) {
            throw new \Exception(
                'La cancha no existe'
            );
        }

        return $cancha;
    }
    public function consultarCanchaPorId(int $id)
{
    $cancha = $this->repository->consultarPorId($id);

    if (!$cancha) {
        throw new \Exception(
            'La cancha no existe'
        );
    }

    return $cancha;
}

public function actualizarCanchaPorId(
    int $id,
    array $datos
) {
    $cancha = $this->repository->consultarPorId($id);

    if (!$cancha) {
        throw new \Exception(
            'La cancha no existe'
        );
    }

    $cancha->update($datos);

    return $cancha;
}
}