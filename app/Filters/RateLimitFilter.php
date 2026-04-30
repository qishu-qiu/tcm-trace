<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class RateLimitFilter implements FilterInterface
{
    protected int $maxRequests = 100;
    protected int $windowSeconds = 60;

    public function before(RequestInterface $request, $arguments = null)
    {
        $tenantId = $request->tenantId ?? 0;
        $ip = $request->getIPAddress();
        
        $key = $tenantId > 0 ? "tenant_{$tenantId}" : "ip_" . md5($ip);
        $cacheKey = "rate_limit_{$key}";
        $cacheFile = WRITEPATH . "cache/" . md5($cacheKey) . ".php";

        $currentTime = time();
        $windowStart = $currentTime - $this->windowSeconds;
        $count = 0;

        if (file_exists($cacheFile)) {
            $data = @include $cacheFile;
            if (is_array($data) && isset($data['expires']) && $data['expires'] > $currentTime) {
                $count = $data['count'] ?? 0;
            }
        }

        $count++;

        $cacheData = [
            'count' => $count,
            'expires' => $currentTime + $this->windowSeconds,
        ];

        $cacheContent = "<?php\nreturn " . var_export($cacheData, true) . ";\n";
        @file_put_contents($cacheFile, $cacheContent);

        if ($count > $this->maxRequests) {
            return service('response')
                ->setStatusCode(429)
                ->setJSON([
                    'code'    => 429,
                    'message' => '请求过于频繁，请稍后再试',
                    'data'    => [
                        'retry_after' => $this->windowSeconds,
                    ],
                ]);
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response;
    }
}
