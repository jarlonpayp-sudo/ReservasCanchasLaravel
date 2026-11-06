<?php

namespace App\Http\Controllers;

use App\Services\SoapConnectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class SoapAuthController extends Controller
{
    public function validateToken(Request $request, SoapConnectionService $soap): JsonResponse
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'exito' => false,
                'mensaje' => 'Debes enviar el JWT en el encabezado Authorization: Bearer.',
            ], 401);
        }

        try {
            $result = $soap->validateToken($token);
            $status = ($result['exito'] ?? false) === true ? 200 : 401;

            return response()->json($result, $status);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'exito' => false,
                'mensaje' => 'No fue posible validar el token mediante SOAP.',
                'detalle' => config('app.debug') ? $e->getMessage() : null,
            ], 502);
        }
    }
}
