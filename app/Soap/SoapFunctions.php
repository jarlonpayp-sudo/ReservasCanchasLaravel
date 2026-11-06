<?php

use App\Services\CanchaService;
use App\Services\JwtService;
use App\Services\ReservaService;

if (!function_exists('respuestaSoap')) {
    function respuestaSoap(array $datos): string
    {
        return json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';
    }
}

if (!function_exists('validarTokenSoap')) {
    /** Devuelve los claims del JWT o false si no es válido. */
    function validarTokenSoap(string $token): array|false
    {
        try {
            return app(JwtService::class)->validate($token);
        } catch (Throwable $e) {
            return false;
        }
    }
}

if (!function_exists('ValidateTokenService')) {
    function ValidateTokenService(string $token): string
    {
        try {
            $claims = app(JwtService::class)->validate($token);

            return respuestaSoap([
                'exito' => true,
                'mensaje' => 'Token JWT válido. Firma verificada correctamente.',
                'usuario' => [
                    'id' => $claims['user_id'] ?? $claims['sub'] ?? null,
                    'email' => $claims['email'] ?? null,
                    'name' => $claims['name'] ?? null,
                ],
                'expira' => $claims['exp'] ?? null,
            ]);
        } catch (Throwable $e) {
            return respuestaSoap([
                'exito' => false,
                'mensaje' => $e->getMessage(),
            ]);
        }
    }
}

if (!function_exists('registrarCancha')) {
    function registrarCancha(string $token, string $codigo, string $nombre, string $tipo, string $estado): string
    {
        if (!validarTokenSoap($token)) {
            return respuestaSoap(['exito' => false, 'mensaje' => 'JWT inválido o expirado. Operación no autorizada.']);
        }

        try {
            $resultado = app(CanchaService::class)->registrarCancha([
                'codigo' => $codigo, 'nombre' => $nombre, 'tipo' => $tipo, 'estado' => $estado,
            ]);
            return respuestaSoap(['exito' => true, 'datos' => $resultado]);
        } catch (Throwable $e) {
            return respuestaSoap(['exito' => false, 'mensaje' => $e->getMessage()]);
        }
    }
}

if (!function_exists('consultarCancha')) {
    function consultarCancha(string $token, int $id): string
    {
        if (!validarTokenSoap($token)) {
            return respuestaSoap(['exito' => false, 'mensaje' => 'JWT inválido o expirado. Operación no autorizada.']);
        }

        try {
            $cancha = app(CanchaService::class)->consultarCanchaPorId($id);
            return respuestaSoap(['exito' => true, 'datos' => $cancha]);
        } catch (Throwable $e) {
            return respuestaSoap(['exito' => false, 'mensaje' => $e->getMessage()]);
        }
    }
}

if (!function_exists('listarCanchas')) {
    function listarCanchas(string $token): string
    {
        if (!validarTokenSoap($token)) {
            return respuestaSoap(['exito' => false, 'mensaje' => 'JWT inválido o expirado. Operación no autorizada.']);
        }

        try {
            $resultado = app(CanchaService::class)->listarCanchas();
            return respuestaSoap(['exito' => true, 'datos' => $resultado]);
        } catch (Throwable $e) {
            return respuestaSoap(['exito' => false, 'mensaje' => $e->getMessage()]);
        }
    }
}

if (!function_exists('actualizarCancha')) {
    function actualizarCancha(string $token, int $id, string $nombre, string $tipo, string $estado): string
    {
        if (!validarTokenSoap($token)) {
            return respuestaSoap(['exito' => false, 'mensaje' => 'JWT inválido o expirado. Operación no autorizada.']);
        }

        try {
            $resultado = app(CanchaService::class)->actualizarCanchaPorId($id, [
                'nombre' => $nombre, 'tipo' => $tipo, 'estado' => $estado,
            ]);
            return respuestaSoap(['exito' => true, 'datos' => $resultado]);
        } catch (Throwable $e) {
            return respuestaSoap(['exito' => false, 'mensaje' => $e->getMessage()]);
        }
    }
}

if (!function_exists('registrarReserva')) {
    function registrarReserva(string $token, int $canchaId, int $usuarioId, string $fecha, string $hora, string $estado): string
    {
        if (!validarTokenSoap($token)) {
            return respuestaSoap(['exito' => false, 'mensaje' => 'JWT inválido o expirado. Operación no autorizada.']);
        }

        try {
            $resultado = app(ReservaService::class)->registrarReserva([
                'cancha_id' => $canchaId, 'usuario_id' => $usuarioId, 'fecha' => $fecha,
                'hora' => $hora, 'estado' => $estado,
            ]);
            return respuestaSoap(['exito' => true, 'datos' => $resultado]);
        } catch (Throwable $e) {
            return respuestaSoap(['exito' => false, 'mensaje' => $e->getMessage()]);
        }
    }
}

if (!function_exists('consultarReservas')) {
    function consultarReservas(string $token): string
    {
        if (!validarTokenSoap($token)) {
            return respuestaSoap(['exito' => false, 'mensaje' => 'JWT inválido o expirado. Operación no autorizada.']);
        }

        try {
            $resultado = app(ReservaService::class)->listarReservas();
            return respuestaSoap(['exito' => true, 'datos' => $resultado]);
        } catch (Throwable $e) {
            return respuestaSoap(['exito' => false, 'mensaje' => $e->getMessage()]);
        }
    }
}
