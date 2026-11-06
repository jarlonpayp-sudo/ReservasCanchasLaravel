<?php

namespace App\Services;

use App\Repositories\ReservaRepository;
use App\Repositories\CanchaRepository;
use App\Repositories\UsuarioRepository;

class ReservaService
{
    private ReservaRepository $reservaRepository;
    private CanchaRepository $canchaRepository;
    private UsuarioRepository $usuarioRepository;

    public function __construct(
        ReservaRepository $reservaRepository,
        CanchaRepository $canchaRepository,
        UsuarioRepository $usuarioRepository
    ) {
        $this->reservaRepository = $reservaRepository;
        $this->canchaRepository = $canchaRepository;
        $this->usuarioRepository = $usuarioRepository;
    }

    public function registrarReserva(array $datos)
    {
        // 1. Verificar que exista la cancha
        $cancha = $this->canchaRepository->consultarPorId(
            $datos['cancha_id']
        );

        if (!$cancha) {
            throw new \Exception(
                'La cancha no existe'
            );
        }

        // 2. Verificar que la cancha esté activa
        if ($cancha->estado !== 'ACTIVA') {
            throw new \Exception(
                'La cancha está inactiva'
            );
        }

        // 3. Verificar que exista el usuario
        $usuario = $this->usuarioRepository->consultarPorId(
            $datos['usuario_id']
        );

        if (!$usuario) {
            throw new \Exception(
                'El usuario no existe'
            );
        }

        // 4. Verificar que no exista otra reserva
        $reservaExistente = $this->reservaRepository->buscarReserva(
            $datos['cancha_id'],
            $datos['fecha'],
            $datos['hora']
        );

        if ($reservaExistente) {
            throw new \Exception(
                'La cancha ya tiene una reserva para esa fecha y hora'
            );
        }

        // 5. Registrar la reserva
        return $this->reservaRepository->registrar($datos);
    }

    public function consultarReserva(int $id)
    {
        $reserva = $this->reservaRepository->consultarPorId($id);

        if (!$reserva) {
            throw new \Exception(
                'La reserva no existe'
            );
        }

        return $reserva;
    }

    public function listarReservas()
    {
        return $this->reservaRepository->listar();
    }
}