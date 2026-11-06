<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SoapController extends Controller
{
    public function handle(Request $request)
    {
        $nusoap = base_path('vendor/econea/nusoap/src/nusoap.php');
        if (!is_file($nusoap)) {
            return response('NuSOAP no está instalado. Ejecuta composer install.', 500);
        }

        require_once $nusoap;
        require_once app_path('Soap/SoapFunctions.php');

        $server = new \soap_server();
        $server->configureWSDL('ReservasCanchas', 'urn:ReservasCanchas');
        $server->wsdl->schemaTargetNamespace = 'urn:ReservasCanchas';
        $server->soap_defencoding = 'UTF-8';
        $server->xml_encoding = 'UTF-8';

        // Esta operación es pública: recibe el JWT y verifica su firma.
        $server->register(
            'ValidateTokenService',
            ['token' => 'xsd:string'],
            ['return' => 'xsd:string'],
            'urn:ReservasCanchas',
            'urn:ReservasCanchas#ValidateTokenService',
            'rpc',
            'encoded',
            'Valida la firma, algoritmo y expiración de un JWT.'
        );

        // Las operaciones de negocio exigen un JWT como primer parámetro.
        $server->register('registrarCancha', [
            'token' => 'xsd:string', 'codigo' => 'xsd:string', 'nombre' => 'xsd:string',
            'tipo' => 'xsd:string', 'estado' => 'xsd:string',
        ], ['return' => 'xsd:string'], 'urn:ReservasCanchas', 'urn:ReservasCanchas#registrarCancha', 'rpc', 'encoded', 'Registra una nueva cancha deportiva.');

        $server->register('consultarCancha', [
            'token' => 'xsd:string', 'id' => 'xsd:int',
        ], ['return' => 'xsd:string'], 'urn:ReservasCanchas', 'urn:ReservasCanchas#consultarCancha', 'rpc', 'encoded', 'Consulta una cancha por su identificador.');

        $server->register('listarCanchas', [
            'token' => 'xsd:string',
        ], ['return' => 'xsd:string'], 'urn:ReservasCanchas', 'urn:ReservasCanchas#listarCanchas', 'rpc', 'encoded', 'Lista todas las canchas registradas.');

        $server->register('actualizarCancha', [
            'token' => 'xsd:string', 'id' => 'xsd:int', 'nombre' => 'xsd:string',
            'tipo' => 'xsd:string', 'estado' => 'xsd:string',
        ], ['return' => 'xsd:string'], 'urn:ReservasCanchas', 'urn:ReservasCanchas#actualizarCancha', 'rpc', 'encoded', 'Actualiza los datos de una cancha.');

        $server->register('registrarReserva', [
            'token' => 'xsd:string', 'canchaId' => 'xsd:int', 'usuarioId' => 'xsd:int',
            'fecha' => 'xsd:string', 'hora' => 'xsd:string', 'estado' => 'xsd:string',
        ], ['return' => 'xsd:string'], 'urn:ReservasCanchas', 'urn:ReservasCanchas#registrarReserva', 'rpc', 'encoded', 'Registra una reserva de cancha.');

        $server->register('consultarReservas', [
            'token' => 'xsd:string',
        ], ['return' => 'xsd:string'], 'urn:ReservasCanchas', 'urn:ReservasCanchas#consultarReservas', 'rpc', 'encoded', 'Consulta todas las reservas registradas.');

        ob_start();
        $server->service($request->getContent());
        $response = ob_get_clean();

        return response($response, 200)->header('Content-Type', 'text/xml; charset=UTF-8');
    }
}
