<?php

namespace Tests\Unit\Libraries;

use CodeIgniter\Test\CIUnitTestCase;
use App\Libraries\JwtService;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;

final class JwtServiceTest extends CIUnitTestCase
{
    protected JwtService $jwtService;

    protected function setUp(): void
    {
        parent::setUp();
        putenv('jwt.secret=tcm_trace_jwt_secret_key_must_be_long_enough_for_hs256_algorithm');
        putenv('jwt.expire_days=7');
        $this->jwtService = new JwtService();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        putenv('jwt.secret');
        putenv('jwt.expire_days');
    }

    public function testGenerateToken(): void
    {
        $token = $this->jwtService->generateToken(1, 10, 'admin');
        
        $this->assertNotEmpty($token);
        $this->assertIsString($token);
        $this->assertStringContainsString('.', $token);
    }

    public function testValidateTokenSuccess(): void
    {
        $token = $this->jwtService->generateToken(1, 10, 'admin');
        $decoded = $this->jwtService->validateToken($token);
        
        $this->assertNotNull($decoded);
        $this->assertEquals(1, $decoded->userId);
        $this->assertEquals(10, $decoded->tenantId);
        $this->assertEquals('admin', $decoded->role);
    }

    public function testValidateTokenInvalidSignature(): void
    {
        $invalidToken = 'invalid.token.signature';
        $decoded = $this->jwtService->validateToken($invalidToken);
        
        $this->assertNull($decoded);
    }

    public function testValidateTokenMalformed(): void
    {
        $malformedToken = 'not.a.valid.token';
        $decoded = $this->jwtService->validateToken($malformedToken);
        
        $this->assertNull($decoded);
    }

    public function testGetTokenPayloadSuccess(): void
    {
        $token = $this->jwtService->generateToken(5, 20, 'staff');
        $payload = $this->jwtService->getTokenPayload($token);
        
        $this->assertNotNull($payload);
        $this->assertEquals(5, $payload['userId']);
        $this->assertEquals(20, $payload['tenantId']);
        $this->assertEquals('staff', $payload['role']);
        $this->assertArrayHasKey('exp', $payload);
        $this->assertArrayHasKey('iat', $payload);
    }

    public function testGetTokenPayloadInvalidToken(): void
    {
        $payload = $this->jwtService->getTokenPayload('invalid.token');
        
        $this->assertNull($payload);
    }

    public function testRefreshTokenSuccess(): void
    {
        $originalToken = $this->jwtService->generateToken(1, 10, 'admin');
        $newToken = $this->jwtService->refresh($originalToken);
        
        $this->assertNotNull($newToken);
        $this->assertNotEmpty($newToken);
        
        $payload = $this->jwtService->getTokenPayload($newToken);
        $this->assertNotNull($payload);
        $this->assertEquals(1, $payload['userId']);
        $this->assertEquals(10, $payload['tenantId']);
        $this->assertEquals('admin', $payload['role']);
    }

    public function testRefreshTokenInvalid(): void
    {
        $newToken = $this->jwtService->refresh('invalid.token');
        
        $this->assertNull($newToken);
    }

    public function testTokenExpiration(): void
    {
        putenv('jwt.expire_days=-1');
        
        $expiredService = new JwtService();
        $token = $expiredService->generateToken(1, 10, 'admin');
        
        $decoded = $expiredService->validateToken($token);
        
        $this->assertNull($decoded);
        
        putenv('jwt.expire_days=7');
    }

    public function testDifferentRoles(): void
    {
        $roles = ['admin', 'staff', 'viewer'];
        
        foreach ($roles as $role) {
            $token = $this->jwtService->generateToken(1, 10, $role);
            $payload = $this->jwtService->getTokenPayload($token);
            
            $this->assertNotNull($payload);
            $this->assertEquals($role, $payload['role']);
        }
    }

    public function testTokenContainsIssuer(): void
    {
        $token = $this->jwtService->generateToken(1, 10, 'admin');
        $decoded = $this->jwtService->validateToken($token);
        
        $this->assertNotNull($decoded);
        $this->assertEquals(base_url(), $decoded->iss);
    }
}