<?php

namespace App\Libraries;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;

class JwtService
{
    private string $secret;
    private string $algorithm = 'HS256';
    private int $expireSeconds = 604800;

    public function __construct()
    {
        $this->secret = env('jwt.secret');
        if (empty($this->secret)) {
            throw new \RuntimeException('JWT secret is not configured. Please set jwt.secret in your environment configuration.');
        }
        
        if (strlen($this->secret) < 32) {
            throw new \RuntimeException('JWT secret must be at least 32 characters long for HS256 algorithm.');
        }
        
        $expireDays = (int) env('jwt.expire_days', 7);
        $this->expireSeconds = $expireDays * 86400;
    }

    public function generateToken(int $userId, int $tenantId, string $role): string
    {
        $now = time();
        $payload = [
            'iss' => base_url(),
            'iat' => $now,
            'exp' => $now + $this->expireSeconds,
            'userId' => $userId,
            'tenantId' => $tenantId,
            'role' => $role,
        ];

        return JWT::encode($payload, $this->secret, $this->algorithm);
    }

    public function validateToken(string $token): ?object
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secret, $this->algorithm));
            return $decoded;
        } catch (ExpiredException $e) {
            return null;
        } catch (SignatureInvalidException $e) {
            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getTokenPayload(string $token): ?array
    {
        $decoded = $this->validateToken($token);
        if ($decoded === null) {
            return null;
        }

        return [
            'userId' => $decoded->userId,
            'tenantId' => $decoded->tenantId,
            'role' => $decoded->role,
            'exp' => $decoded->exp,
            'iat' => $decoded->iat,
        ];
    }

    public function refresh(string $token): ?string
    {
        $payload = $this->getTokenPayload($token);
        if ($payload === null) {
            return null;
        }

        return $this->generateToken(
            $payload['userId'],
            $payload['tenantId'],
            $payload['role']
        );
    }
}
