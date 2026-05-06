<?php

namespace Tests\Unit\Libraries;

use CodeIgniter\Test\CIUnitTestCase;
use App\Libraries\JwtService;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

final class JwtServiceTest extends CIUnitTestCase
{
    protected JwtService $jwtService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->jwtService = new JwtService();
    }

    public function testGenerateToken(): void
    {
        $token = $this->jwtService->generateToken(1, 10, 'admin');
        
        $this->assertNotNull($token);
        $this->assertIsString($token);
        $this->assertNotEmpty($token);
        
        $parts = explode('.', $token);
        $this->assertCount(3, $parts);
    }

    public function testGenerateTokenContainsCorrectPayload(): void
    {
        $token = $this->jwtService->generateToken(123, 456, 'user');
        
        $payload = $this->jwtService->getTokenPayload($token);
        
        $this->assertNotNull($payload);
        $this->assertEquals(123, $payload['userId']);
        $this->assertEquals(456, $payload['tenantId']);
        $this->assertEquals('user', $payload['role']);
        $this->assertArrayHasKey('exp', $payload);
        $this->assertArrayHasKey('iat', $payload);
    }

    public function testValidateTokenWithValidToken(): void
    {
        $token = $this->jwtService->generateToken(1, 1, 'admin');
        $decoded = $this->jwtService->validateToken($token);
        
        $this->assertNotNull($decoded);
        $this->assertEquals(1, $decoded->userId);
        $this->assertEquals(1, $decoded->tenantId);
        $this->assertEquals('admin', $decoded->role);
    }

    public function testValidateTokenWithInvalidToken(): void
    {
        $invalidToken = 'invalid.token.string';
        $decoded = $this->jwtService->validateToken($invalidToken);
        
        $this->assertNull($decoded);
    }

    public function testValidateTokenWithExpiredToken(): void
    {
        $secret = env('jwt.secret', 'tcm_trace_jwt_secret_key_must_be_long_enough_for_hs256_algorithm');
        $payload = [
            'iss' => 'http://example.com',
            'iat' => time() - 86400,
            'exp' => time() - 1,
            'userId' => 1,
            'tenantId' => 1,
            'role' => 'admin',
        ];
        $expiredToken = JWT::encode($payload, $secret, 'HS256');
        
        $decoded = $this->jwtService->validateToken($expiredToken);
        
        $this->assertNull($decoded);
    }

    public function testValidateTokenWithWrongSignature(): void
    {
        $secret = 'wrong_secret_key_that_is_long_enough_for_hs256_algorithm';
        $payload = [
            'iss' => 'http://example.com',
            'iat' => time(),
            'exp' => time() + 3600,
            'userId' => 1,
            'tenantId' => 1,
            'role' => 'admin',
        ];
        $wrongSigToken = JWT::encode($payload, $secret, 'HS256');
        
        $decoded = $this->jwtService->validateToken($wrongSigToken);
        
        $this->assertNull($decoded);
    }

    public function testGetTokenPayload(): void
    {
        $token = $this->jwtService->generateToken(789, 321, 'editor');
        $payload = $this->jwtService->getTokenPayload($token);
        
        $this->assertNotNull($payload);
        $this->assertEquals(789, $payload['userId']);
        $this->assertEquals(321, $payload['tenantId']);
        $this->assertEquals('editor', $payload['role']);
    }

    public function testGetTokenPayloadWithInvalidToken(): void
    {
        $payload = $this->jwtService->getTokenPayload('invalid.token');
        
        $this->assertNull($payload);
    }

    public function testRefreshToken(): void
    {
        $token = $this->jwtService->generateToken(100, 200, 'user');
        $newToken = $this->jwtService->refresh($token);
        
        $this->assertNotNull($newToken);
        $this->assertIsString($newToken);
        
        $payload = $this->jwtService->getTokenPayload($newToken);
        $this->assertEquals(100, $payload['userId']);
        $this->assertEquals(200, $payload['tenantId']);
        $this->assertEquals('user', $payload['role']);
    }

    public function testRefreshTokenWithInvalidToken(): void
    {
        $newToken = $this->jwtService->refresh('invalid.token');
        
        $this->assertNull($newToken);
    }

    public function testTokenExpirationTime(): void
    {
        $token = $this->jwtService->generateToken(1, 1, 'admin');
        $payload = $this->jwtService->getTokenPayload($token);
        
        $expectedExp = time() + (7 * 86400);
        $this->assertEqualsWithDelta($expectedExp, $payload['exp'], 5);
    }
}