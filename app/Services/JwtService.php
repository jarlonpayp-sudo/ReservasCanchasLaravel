<?php

namespace App\Services;

use RuntimeException;

/**
 * Implementación mínima de JWT HS256 usando funciones nativas de PHP.
 * No sustituye una librería general: únicamente permite emitir y verificar
 * los tokens HS256 utilizados por esta aplicación.
 */
class JwtService
{
    public function generate(array $claims): string
    {
        $secret = $this->secret();
        $now = time();
        $ttl = max(1, (int) config('jwt.ttl', 60));

        $header = [
            'typ' => 'JWT',
            'alg' => 'HS256',
        ];

        $payload = array_merge($claims, [
            'iss' => (string) config('jwt.issuer'),
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + ($ttl * 60),
        ]);

        $encodedHeader = $this->base64UrlEncode($this->jsonEncode($header));
        $encodedPayload = $this->base64UrlEncode($this->jsonEncode($payload));
        $signingInput = $encodedHeader . '.' . $encodedPayload;
        $signature = hash_hmac('sha256', $signingInput, $secret, true);

        return $signingInput . '.' . $this->base64UrlEncode($signature);
    }

    /** @return array<string, mixed> */
    public function validate(string $token): array
    {
        $secret = $this->secret();
        $parts = explode('.', trim($token));

        if (count($parts) !== 3) {
            throw new RuntimeException('El token JWT tiene un formato inválido.');
        }

        [$encodedHeader, $encodedPayload, $encodedSignature] = $parts;
        $header = $this->jsonDecode($this->base64UrlDecode($encodedHeader));
        $payload = $this->jsonDecode($this->base64UrlDecode($encodedPayload));
        $signature = $this->base64UrlDecode($encodedSignature);

        if (($header['alg'] ?? null) !== 'HS256') {
            throw new RuntimeException('El algoritmo del token no está permitido.');
        }

        if (($header['typ'] ?? null) !== 'JWT') {
            throw new RuntimeException('El tipo de token no es válido.');
        }

        $expected = hash_hmac(
            'sha256',
            $encodedHeader . '.' . $encodedPayload,
            $secret,
            true
        );

        if (!hash_equals($expected, $signature)) {
            throw new RuntimeException('La firma del token JWT no es válida.');
        }

        if (!isset($payload['exp']) || !is_numeric($payload['exp']) || (int) $payload['exp'] <= time()) {
            throw new RuntimeException('El token JWT ha expirado o no contiene expiración.');
        }

        if (isset($payload['nbf']) && (int) $payload['nbf'] > time()) {
            throw new RuntimeException('El token JWT todavía no es válido.');
        }

        $issuer = (string) config('jwt.issuer');
        if (!isset($payload['iss']) || !hash_equals($issuer, (string) $payload['iss'])) {
            throw new RuntimeException('El emisor del token JWT no es válido.');
        }

        if (isset($payload['iat']) && (int) $payload['iat'] > time() + 60) {
            throw new RuntimeException('La fecha de emisión del token JWT no es válida.');
        }

        return $payload;
    }

    private function secret(): string
    {
        $secret = (string) config('jwt.secret');

        if (strlen($secret) < 32) {
            throw new RuntimeException('JWT_SECRET debe contener al menos 32 caracteres aleatorios.');
        }

        if (strtoupper((string) config('jwt.algorithm', 'HS256')) !== 'HS256') {
            throw new RuntimeException('La aplicación solo permite el algoritmo HS256.');
        }

        return $secret;
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $value): string
    {
        if ($value === '' || preg_match('/[^A-Za-z0-9_\-]/', $value)) {
            throw new RuntimeException('El token JWT contiene datos codificados inválidos.');
        }

        $decoded = base64_decode(strtr($value, '-_', '+/') . str_repeat('=', (4 - strlen($value) % 4) % 4), true);

        if ($decoded === false) {
            throw new RuntimeException('No fue posible decodificar el token JWT.');
        }

        return $decoded;
    }

    /** @return array<string, mixed> */
    private function jsonDecode(string $value): array
    {
        $decoded = json_decode($value, true);

        if (!is_array($decoded)) {
            throw new RuntimeException('El contenido del token JWT no es JSON válido.');
        }

        return $decoded;
    }

    private function jsonEncode(array $value): string
    {
        $encoded = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if ($encoded === false) {
            throw new RuntimeException('No fue posible generar el contenido del token JWT.');
        }

        return $encoded;
    }
}
