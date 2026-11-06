# Autenticación JWT entre Laravel y SOAP

Esta implementación agrega registro/login con JWT HS256 y validación del token en el servicio SOAP que ya forma parte de esta aplicación.

## 1. Requisitos y estructura

- PHP 8.3 o superior (según `composer.json`).
- Composer.
- MySQL y la base de datos del proyecto.
- NuSOAP 0.9.20 (ya declarado en `composer.json`).
- Extensiones PHP necesarias para SOAP/XML: `openssl`, `dom`, `xml`, `mbstring` y `pdo_mysql`. Verifícalas en Laragon desde `php -m`.

El proyecto contiene un solo Laravel que publica el endpoint SOAP en `/soap`. No se recibió un segundo proyecto SOAP separado.

## 2. Instalar dependencias y configurar entorno

Desde la carpeta `ReservasCanchasLaravel`:

```bash
composer install
copy .env.example .env
php artisan key:generate
```

En Linux/macOS, sustituye `copy` por `cp`.

Genera una clave secreta JWT aleatoria de 64 caracteres hexadecimales:

```bash
php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
```

Copia el resultado en `.env`:

```env
JWT_SECRET=PEGA_AQUI_LA_CLAVE_GENERADA
JWT_ALGORITHM=HS256
JWT_TTL=60
JWT_ISSUER=http://127.0.0.1:8000
SOAP_ENDPOINT=http://127.0.0.1:8000/soap
```

No compartas ni subas el archivo `.env` a GitHub. Si posteriormente separas el servidor SOAP en otro proyecto, configura exactamente el mismo `JWT_SECRET`, `JWT_ALGORITHM` y `JWT_ISSUER` en ambos proyectos.

Configura también los datos de MySQL en `.env`, según tu instalación. El ZIP original incluye el respaldo de la base de datos en `ReservasCanchasLaravel-main/database/sql/reservascanchas.sql` (está un nivel por encima de la carpeta del proyecto Laravel).

Si todavía no tienes la base de datos, crea/importa `reservascanchas.sql` desde phpMyAdmin o MySQL. **Ese respaldo elimina y vuelve a crear las tablas `canchas`, `reservas` y `usuarios`; no lo importes sobre una base que tenga información que quieras conservar sin hacer antes una copia de seguridad.**

Después, desde la carpeta Laravel, ejecuta las migraciones:

```bash
php artisan migrate
```

Esto crea las tablas propias de Laravel, incluida `users`, que se utiliza para el registro y el login. No se agregó una tabla nueva para las cuentas: se reutiliza `users`. La generación/verificación HS256 se implementó con `hash_hmac` y funciones nativas de PHP para no introducir una dependencia Composer nueva ni alterar el `composer.lock` que ya tenía el proyecto.

## 3. Iniciar Laravel

```bash
php artisan config:clear
php artisan route:clear
```

Inicia el proyecto desde Apache de Laragon o desde el servidor web que uses. Ajusta `APP_URL` y `SOAP_ENDPOINT` a la URL real de esa instalación. Como el endpoint Laravel reenvía la solicitud a su propio endpoint SOAP por HTTP, evita probar `/api/soap/validate-token` desde el servidor PHP integrado de un solo proceso si queda esperando la llamada interna; Apache de Laragon es la opción recomendada.

## 4. Registrar un usuario

**POST** `http://127.0.0.1:8000/api/register`

Body → raw → JSON:

```json
{
  "name": "Usuario de prueba",
  "email": "prueba@example.com",
  "password": "ClaveSegura123",
  "password_confirmation": "ClaveSegura123"
}
```

La respuesta exitosa es HTTP 201 y devuelve el ID, nombre y correo. La contraseña se almacena mediante el cast `hashed` del modelo `User`.

## 5. Iniciar sesión y obtener el JWT

**POST** `http://127.0.0.1:8000/api/login`

Body → raw → JSON:

```json
{
  "email": "prueba@example.com",
  "password": "ClaveSegura123"
}
```

La respuesta incluye `access_token`, `token_type` y `expires_in`. Copia el valor de `access_token` para las pruebas SOAP.

## 6. Validar el token desde Laravel hacia SOAP

**POST** `http://127.0.0.1:8000/api/soap/validate-token`

En Postman, Authorization → Bearer Token, pega el JWT recibido en el login. Laravel utiliza `SoapConnectionService.php` para invocar `ValidateTokenService` en el endpoint SOAP.

También puedes consumir directamente el WSDL desde SoapUI:

`http://127.0.0.1:8000/soap?wsdl`

Crea un proyecto SOAP nuevo a partir de esa URL o actualiza el WSDL del proyecto SoapUI anterior para que cargue las operaciones y parámetros nuevos.

## 7. Operación SOAP de validación

`ValidateTokenService` recibe un parámetro `token` y responde un JSON serializado dentro de la respuesta SOAP. Si la firma HS256 coincide y el token no ha expirado, devuelve `exito: true`; en caso contrario, `exito: false`.

Ejemplo del cuerpo de una solicitud SOAP 1.1:

```xml
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:urn="urn:ReservasCanchas">
  <soapenv:Header/>
  <soapenv:Body>
    <urn:ValidateTokenService>
      <token>PEGAR_JWT_AQUI</token>
    </urn:ValidateTokenService>
  </soapenv:Body>
</soapenv:Envelope>
```

## 8. Operaciones SOAP protegidas

Las seis operaciones de negocio existentes ahora reciben `token` como primer parámetro:

- `registrarCancha(token, codigo, nombre, tipo, estado)`
- `consultarCancha(token, id)`
- `listarCanchas(token)`
- `actualizarCancha(token, id, nombre, tipo, estado)`
- `registrarReserva(token, canchaId, usuarioId, fecha, hora, estado)`
- `consultarReservas(token)`

SOAP verifica el JWT antes de ejecutar la lógica de negocio. Un token incorrecto o expirado impide la operación. Para cada solicitud desde SoapUI, incluye el parámetro `token` en el Body.

## 9. Pruebas negativas

1. Token correcto: `ValidateTokenService` debe devolver `exito: true`.
2. Token alterado: cambia un carácter de la parte final; debe devolver `exito: false`.
3. Firma con otra clave: debe devolver `exito: false`.
4. Token expirado: utiliza un token cuyo `exp` ya haya pasado; debe devolver `exito: false`.
5. Operación de negocio sin token válido: debe devolver `exito: false` y no ejecutar la operación.

No intentes fabricar un token editando el payload: al modificarlo la firma deja de coincidir, que es precisamente lo que debe detectar SOAP.

## 10. Consideraciones

- Se utiliza HS256 con `hash_hmac` y comparación segura `hash_equals`.
- La clave secreta debe tener al menos 32 caracteres y mantenerse privada.
- El token dura 60 minutos por defecto (`JWT_TTL=60`).
- La operación de validación SOAP no requiere token para poder recibir y validar el propio token. Las operaciones de negocio sí lo requieren.
- El proyecto original tiene un modelo `User` para las cuentas de acceso y otro modelo `Usuario` asociado a la tabla `usuarios` que usa el módulo de reservas. Son tablas diferentes; no se cambió esa relación de datos.
- El ZIP recibido no incluye `vendor/` ni un archivo `.env`, por lo que las pruebas de ejecución requieren instalar dependencias y configurar el entorno local.
