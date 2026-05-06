<?php

namespace App\Libraries;

class AuditService
{
    protected $db;
    protected string $traceId;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        $this->traceId = uniqid('trace_', true);
    }

    public function getTraceId(): string
    {
        return $this->traceId;
    }

    public function log(
        string $action,
        string $resourceType,
        ?string $resourceId = null,
        ?array $beforeData = null,
        ?array $afterData = null,
        ?int $tenantId = null,
        ?int $userId = null,
        ?string $userName = null
    ): bool {
        $request = service('request');
        
        $data = [
            'tenant_id'      => $tenantId ?? $request->tenantId ?? 0,
            'actor_type'     => $userId ? 'user' : 'system',
            'actor_id'       => (string) ($userId ?? $request->userId ?? 0),
            'actor_name'     => $userName,
            'action'         => $action,
            'resource_type'  => $resourceType,
            'resource_id'    => $resourceId,
            'before_data'    => $beforeData ? json_encode($beforeData, JSON_UNESCAPED_UNICODE) : null,
            'after_data'     => $afterData ? json_encode($afterData, JSON_UNESCAPED_UNICODE) : null,
            'source_ip'      => $request->getIPAddress(),
            'user_agent'     => $request->getUserAgent()->getAgentString(),
            'geo_location'   => null,
            'occurred_at'    => date('Y-m-d H:i:s'),
            'trace_id'       => $this->traceId,
            'request_id'     => $this->traceId,
            'result'         => 'success',
            'error_message'  => null,
        ];

        return $this->db->table('audit_logs')->insert($data);
    }

    public function logError(
        string $action,
        string $resourceType,
        string $errorMessage,
        ?string $resourceId = null,
        ?array $beforeData = null,
        ?int $tenantId = null,
        ?int $userId = null,
        ?string $userName = null
    ): bool {
        $request = service('request');
        
        $data = [
            'tenant_id'      => $tenantId ?? $request->tenantId ?? 0,
            'actor_type'     => $userId ? 'user' : 'system',
            'actor_id'       => (string) ($userId ?? $request->userId ?? 0),
            'actor_name'     => $userName,
            'action'         => $action,
            'resource_type'  => $resourceType,
            'resource_id'    => $resourceId,
            'before_data'    => $beforeData ? json_encode($beforeData, JSON_UNESCAPED_UNICODE) : null,
            'after_data'     => null,
            'source_ip'      => $request->getIPAddress(),
            'user_agent'     => $request->getUserAgent()->getAgentString(),
            'geo_location'   => null,
            'occurred_at'    => date('Y-m-d H:i:s'),
            'trace_id'       => $this->traceId,
            'request_id'     => $this->traceId,
            'result'         => 'failed',
            'error_message'  => $errorMessage,
        ];

        return $this->db->table('audit_logs')->insert($data);
    }

    public function logLogin(int $userId, string $userName, int $tenantId, bool $success = true, ?string $errorMessage = null): bool
    {
        return $success
            ? $this->log('login', 'user', (string) $userId, null, [
                'login_time' => date('Y-m-d H:i:s'),
                'ip' => service('request')->getIPAddress(),
            ], $tenantId, $userId, $userName)
            : $this->logError('login', 'user', $errorMessage ?? '登录失败', (string) $userId, null, $tenantId, $userId, $userName);
    }

    public function logLogout(int $userId, string $userName, int $tenantId): bool
    {
        return $this->log('logout', 'user', (string) $userId, null, null, $tenantId, $userId, $userName);
    }

    public function logCreate(string $resourceType, string $resourceId, array $data, int $tenantId, int $userId, ?string $userName = null): bool
    {
        return $this->log('create', $resourceType, $resourceId, null, $data, $tenantId, $userId, $userName);
    }

    public function logUpdate(string $resourceType, string $resourceId, array $beforeData, array $afterData, int $tenantId, int $userId, ?string $userName = null): bool
    {
        return $this->log('update', $resourceType, $resourceId, $beforeData, $afterData, $tenantId, $userId, $userName);
    }

    public function logDelete(string $resourceType, string $resourceId, array $beforeData, int $tenantId, int $userId, ?string $userName = null): bool
    {
        return $this->log('delete', $resourceType, $resourceId, $beforeData, null, $tenantId, $userId, $userName);
    }
}
