<?php

namespace App\Services;

use RuntimeException;

class SoapConnectionService
{
    public function validateToken(string $token): array
    {
        $library = base_path('vendor/econea/nusoap/src/nusoap.php');

        if (!is_file($library)) {
            throw new RuntimeException('No se encontró NuSOAP. Ejecuta composer install dentro del proyecto.');
        }

        require_once $library;

        $endpoint = (string) config('services.soap.endpoint');
        if ($endpoint === '') {
            throw new RuntimeException('SOAP_ENDPOINT no está configurado.');
        }

        $client = new \nusoap_client($endpoint, false);
        $client->soap_defencoding = 'UTF-8';
        $client->decode_utf8 = false;

        $result = $client->call(
            'ValidateTokenService',
            ['token' => $token],
            'urn:ReservasCanchas',
            'urn:ReservasCanchas#ValidateTokenService'
        );

        if ($client->fault) {
            throw new RuntimeException('SOAP devolvió un error: ' . (is_scalar($result) ? (string) $result : 'fallo SOAP'));
        }

        $error = $client->getError();
        if ($error) {
            throw new RuntimeException('No fue posible comunicarse con SOAP: ' . $error);
        }

        if (is_string($result)) {
            $decoded = json_decode($result, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        if (is_array($result)) {
            return $result;
        }

        return ['exito' => false, 'mensaje' => 'SOAP devolvió una respuesta inesperada.'];
    }
}
