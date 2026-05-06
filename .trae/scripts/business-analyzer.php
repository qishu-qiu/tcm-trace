<?php
/**
 * TCM Trace - 业务逻辑与前端页面分析器
 * 检查业务逻辑完整性、API与页面对应关系
 */

class BusinessAnalyzer
{
    private string $projectPath;
    private array $issues = [];
    private array $routes = [];
    private array $controllers = [];
    private array $frontendPages = [];
    private array $apiEndpoints = [];
    private array $businessModules = [];

    public function __construct(string $projectPath)
    {
        $this->projectPath = rtrim($projectPath, '/');
    }

    /**
     * 运行完整业务分析
     */
    public function analyze(): array
    {
        echo "🔍 TCM Trace 业务逻辑与前端页面分析器\n";
        echo str_repeat("=", 70) . "\n\n";

        $this->scanRoutes();
        $this->scanControllers();
        $this->scanFrontendPages();
        $this->analyzeBusinessModules();
        
        $this->checkApiPageMapping();
        $this->checkBusinessLogicCompleteness();
        $this->checkFrontendFunctionality();
        $this->checkDataFlow();
        $this->checkMissingFeatures();

        return $this->issues;
    }

    /**
     * 扫描路由
     */
    private function scanRoutes(): void
    {
        echo "📋 扫描 API 路由...\n";

        $routesFile = $this->projectPath . '/app/Config/Routes.php';
        if (!file_exists($routesFile)) {
            $this->issues[] = [
                'type' => 'error',
                'category' => 'routes',
                'message' => 'Routes.php 不存在'
            ];
            return;
        }

        $content = file_get_contents($routesFile);

        // 匹配 API 路由
        preg_match_all('/\$routes->(get|post|put|delete|patch)\([\'"]([^\'"]+)[\'"],\s*[\'"]([^\'"]+)/', $content, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $this->routes[] = [
                'method' => strtoupper($match[1]),
                'path' => $match[2],
                'handler' => $match[3]
            ];
        }

        echo "   ✓ 发现 " . count($this->routes) . " 个 API 路由\n";
    }

    /**
     * 扫描控制器
     */
    private function scanControllers(): void
    {
        echo "🎮 扫描控制器...\n";

        $controllersPath = $this->projectPath . '/app/Controllers';
        if (!is_dir($controllersPath)) {
            return;
        }

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($controllersPath)
        );

        foreach ($files as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $this->analyzeControllerFile($file->getPathname());
            }
        }

        echo "   ✓ 发现 " . count($this->controllers) . " 个控制器\n";
    }

    /**
     * 分析单个控制器
     */
    private function analyzeControllerFile(string $filePath): void
    {
        $content = file_get_contents($filePath);
        $className = basename($filePath, '.php');

        // 提取公共方法
        preg_match_all('/public\s+function\s+(\w+)\s*\(/', $content, $methodMatches);

        $methods = [];
        foreach ($methodMatches[1] as $method) {
            if ($method === '__construct' || $method === 'initController') continue;

            // 分析方法内容
            $methodPattern = '/public\s+function\s+' . $method . '\s*\([^)]*\)\s*\{([^}]+(?:\{[^}]*\}[^}]*)*)\}/s';
            preg_match($methodPattern, $content, $methodContent);
            $methodBody = $methodContent[1] ?? '';

            $methods[$method] = [
                'has_return' => str_contains($methodBody, 'return'),
                'has_validation' => str_contains($methodBody, 'validate'),
                'has_audit' => str_contains($methodBody, 'audit') || str_contains($methodBody, 'Audit'),
                'has_error_handling' => str_contains($methodBody, 'try') || str_contains($methodBody, 'catch'),
                'has_tenant_check' => str_contains($methodBody, 'tenant') || str_contains($methodBody, 'Tenant'),
                'is_empty' => empty(trim($methodBody)) || trim($methodBody) === '{}',
                'body' => $methodBody
            ];
        }

        $this->controllers[$className] = [
            'file' => $filePath,
            'methods' => $methods
        ];
    }

    /**
     * 扫描前端页面
     */
    private function scanFrontendPages(): void
    {
        echo "🎨 扫描前端页面...\n";

        $adminPath = $this->projectPath . '/public/admin';
        if (!is_dir($adminPath)) {
            return;
        }

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($adminPath)
        );

        foreach ($files as $file) {
            if ($file->isFile() && $file->getExtension() === 'html') {
                $pageName = basename($file->getPathname(), '.html');
                $content = file_get_contents($file->getPathname());
                
                // 分析页面功能
                $this->frontendPages[$pageName] = [
                    'file' => $file->getPathname(),
                    'has_api_calls' => str_contains($content, 'api.') || str_contains($content, 'api.get') || str_contains($content, 'api.post'),
                    'has_form' => str_contains($content, '<form') || str_contains($content, 'lay-submit'),
                    'has_table' => str_contains($content, 'table.render') || str_contains($content, 'layui-table'),
                    'has_chart' => str_contains($content, 'echarts') || str_contains($content, 'chart'),
                    'has_upload' => str_contains($content, 'upload.render') || str_contains($content, 'type="file"'),
                    'apis' => $this->extractApisFromPage($content),
                    'missing_apis' => []
                ];
            }
        }

        echo "   ✓ 发现 " . count($this->frontendPages) . " 个前端页面\n";
    }

    /**
     * 从页面提取 API 调用
     */
    private function extractApisFromPage(string $content): array
    {
        $apis = [];
        
        // 匹配 api.get/post/put/delete 调用
        preg_match_all('/api\.(get|post|put|del|delete)\([\'"]([^\'"]+)/', $content, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $apis[] = [
                'method' => strtoupper($match[1] === 'del' ? 'delete' : $match[1]),
                'endpoint' => $match[2]
            ];
        }

        return $apis;
    }

    /**
     * 分析业务模块
     */
    private function analyzeBusinessModules(): void
    {
        echo "📊 分析业务模块...\n";

        $this->businessModules = [
            'auth' => [
                'name' => '认证授权',
                'routes' => ['auth/register', 'auth/login', 'auth/profile', 'auth/change-password', 'auth/logout', 'auth/refresh'],
                'pages' => ['login'],
                'required_apis' => ['POST /auth/login', 'POST /auth/register', 'GET /auth/profile']
            ],
            'product' => [
                'name' => '产品管理',
                'routes' => ['products', 'products/categories', 'upload/image'],
                'pages' => ['products'],
                'required_apis' => ['GET /products', 'POST /products', 'PUT /products/{id}', 'DELETE /products/{id}']
            ],
            'batch' => [
                'name' => '批次管理',
                'routes' => ['batches', 'batches/{id}/trace-records'],
                'pages' => ['batches'],
                'required_apis' => ['GET /batches', 'POST /batches', 'PUT /batches/{id}', 'DELETE /batches/{id}']
            ],
            'qrcode' => [
                'name' => '二维码管理',
                'routes' => ['qrcodes', 'qrcodes/generate', 'qrcodes/print'],
                'pages' => ['qrcodes'],
                'required_apis' => ['GET /qrcodes', 'POST /qrcodes/generate', 'PUT /qrcodes/{id}/status']
            ],
            'trace' => [
                'name' => '溯源记录',
                'routes' => ['trace-records', 'batches/{id}/trace-records'],
                'pages' => [], // 内嵌在批次页面
                'required_apis' => ['GET /trace-records', 'POST /trace-records', 'PUT /trace-records/{id}']
            ],
            'audit' => [
                'name' => '审计日志',
                'routes' => ['audit', 'audit/export'],
                'pages' => ['audit'],
                'required_apis' => ['GET /audit', 'GET /audit/export']
            ],
            'statistics' => [
                'name' => '统计分析',
                'routes' => ['statistics', 'statistics/scan', 'statistics/product', 'statistics/risk'],
                'pages' => ['statistics', 'dashboard'],
                'required_apis' => ['GET /statistics', 'GET /statistics/scan']
            ],
            'billing' => [
                'name' => '订阅计费',
                'routes' => ['billing', 'billing/plans', 'billing/upgrade'],
                'pages' => ['billing'],
                'required_apis' => ['GET /billing', 'POST /billing/upgrade']
            ],
            'tenant' => [
                'name' => '租户管理',
                'routes' => ['tenant', 'tenant/usage', 'tenant/upload-logo'],
                'pages' => ['settings'],
                'required_apis' => ['GET /tenant', 'PUT /tenant']
            ],
            'user' => [
                'name' => '用户管理',
                'routes' => ['users', 'users/{id}/status', 'users/{id}/reset-password'],
                'pages' => [], // 内嵌在设置页面
                'required_apis' => ['GET /users', 'POST /users', 'PUT /users/{id}']
            ]
        ];

        echo "   ✓ 识别 " . count($this->businessModules) . " 个业务模块\n";
    }

    /**
     * 检查 API 与页面对应关系
     */
    private function checkApiPageMapping(): void
    {
        echo "\n🔍 检查 API 与页面对应关系...\n";

        foreach ($this->businessModules as $moduleKey => $module) {
            // 检查页面是否存在
            foreach ($module['pages'] as $page) {
                if (!isset($this->frontendPages[$page])) {
                    $this->issues[] = [
                        'type' => 'error',
                        'category' => 'missing_page',
                        'module' => $module['name'],
                        'message' => "业务模块 '{$module['name']}' 缺少前端页面: {$page}.html",
                        'suggestion' => "创建页面 public/admin/{$page}.html"
                    ];
                }
            }

            // 检查 API 是否实现
            foreach ($module['routes'] as $route) {
                $found = false;
                foreach ($this->routes as $r) {
                    if (str_contains($r['path'], $route)) {
                        $found = true;
                        break;
                    }
                }

                if (!$found) {
                    $this->issues[] = [
                        'type' => 'warning',
                        'category' => 'missing_api',
                        'module' => $module['name'],
                        'message' => "业务模块 '{$module['name']}' 可能缺少 API: {$route}",
                        'suggestion' => "在 Routes.php 中添加路由配置"
                    ];
                }
            }
        }
    }

    /**
     * 检查业务逻辑完整性
     */
    private function checkBusinessLogicCompleteness(): void
    {
        echo "🔍 检查业务逻辑完整性...\n";

        foreach ($this->controllers as $controllerName => $controller) {
            foreach ($controller['methods'] as $methodName => $method) {
                // 检查敏感操作是否有审计日志
                $sensitiveOperations = ['create', 'update', 'delete', 'resetPassword', 'upgrade'];
                $isSensitive = false;
                foreach ($sensitiveOperations as $op) {
                    if (str_contains($methodName, $op)) {
                        $isSensitive = true;
                        break;
                    }
                }

                if ($isSensitive && !$method['has_audit']) {
                    $this->issues[] = [
                        'type' => 'warning',
                        'category' => 'missing_audit',
                        'controller' => $controllerName,
                        'method' => $methodName,
                        'message' => "敏感操作 {$controllerName}::{$methodName} 缺少审计日志记录",
                        'suggestion' => "添加 \$this->auditService->logXxx() 调用"
                    ];
                }

                // 检查是否有租户隔离
                if (!$method['has_tenant_check'] && !in_array($methodName, ['login', 'register', 'refreshToken'])) {
                    $this->issues[] = [
                        'type' => 'info',
                        'category' => 'missing_tenant_check',
                        'controller' => $controllerName,
                        'method' => $methodName,
                        'message' => "方法 {$controllerName}::{$methodName} 可能缺少租户隔离检查",
                        'suggestion' => "确保数据按租户隔离"
                    ];
                }

                // 检查空方法
                if ($method['is_empty']) {
                    $this->issues[] = [
                        'type' => 'error',
                        'category' => 'empty_method',
                        'controller' => $controllerName,
                        'method' => $methodName,
                        'message' => "方法 {$controllerName}::{$methodName} 为空实现",
                        'suggestion' => "补充业务逻辑实现"
                    ];
                }
            }
        }
    }

    /**
     * 检查前端功能完整性
     */
    private function checkFrontendFunctionality(): void
    {
        echo "🔍 检查前端功能完整性...\n";

        foreach ($this->frontendPages as $pageName => $page) {
            // 检查页面调用的 API 是否存在
            foreach ($page['apis'] as $api) {
                $found = false;
                foreach ($this->routes as $route) {
                    // 简化匹配，实际应该更精确
                    $apiPath = ltrim($api['endpoint'], '/');
                    if (str_contains($route['path'], explode('/', $apiPath)[0])) {
                        $found = true;
                        break;
                    }
                }

                if (!$found) {
                    $this->issues[] = [
                        'type' => 'error',
                        'category' => 'page_api_mismatch',
                        'page' => $pageName,
                        'message' => "页面 {$pageName}.html 调用的 API 不存在: {$api['method']} {$api['endpoint']}",
                        'suggestion' => "检查 API 路径或创建对应的 API"
                    ];
                }
            }

            // 检查列表页是否有完整的 CRUD
            if ($page['has_table'] && !$page['has_form']) {
                $this->issues[] = [
                    'type' => 'info',
                    'category' => 'incomplete_crud',
                    'page' => $pageName,
                    'message' => "页面 {$pageName}.html 有表格但可能没有表单（新增/编辑）",
                    'suggestion' => "检查是否实现了完整的 CRUD 功能"
                ];
            }
        }
    }

    /**
     * 检查数据流
     */
    private function checkDataFlow(): void
    {
        echo "🔍 检查数据流完整性...\n";

        // 检查关键业务流程
        $criticalFlows = [
            [
                'name' => '产品创建流程',
                'steps' => ['产品表单页面', 'API 验证', '数据保存', '审计记录']
            ],
            [
                'name' => '批次创建流程',
                'steps' => ['批次表单页面', '产品选择', '批次保存', '二维码生成']
            ],
            [
                'name' => '扫码验证流程',
                'steps' => ['扫码页面', '二维码验证', '溯源记录查询', '记录展示']
            ]
        ];

        foreach ($criticalFlows as $flow) {
            // 这里可以添加更详细的流程检查
            $this->issues[] = [
                'type' => 'info',
                'category' => 'data_flow',
                'flow' => $flow['name'],
                'message' => "请手动检查业务流程: {$flow['name']}",
                'steps' => $flow['steps'],
                'suggestion' => "确保每个步骤都有对应的实现"
            ];
        }
    }

    /**
     * 检查缺失功能
     */
    private function checkMissingFeatures(): void
    {
        echo "🔍 检查缺失功能...\n";

        // 检查系统设置页面
        if (isset($this->frontendPages['settings'])) {
            $content = file_get_contents($this->frontendPages['settings']['file']);
            
            $requiredFeatures = [
                'tenant_info' => '租户信息设置',
                'user_management' => '用户管理',
                'notification_settings' => '通知设置',
                'security_settings' => '安全设置'
            ];

            foreach ($requiredFeatures as $key => $feature) {
                if (!str_contains($content, $key) && !str_contains($content, $feature)) {
                    $this->issues[] = [
                        'type' => 'info',
                        'category' => 'missing_feature',
                        'page' => 'settings',
                        'feature' => $feature,
                        'message' => "设置页面可能缺少功能: {$feature}",
                        'suggestion' => "检查是否需要添加 {$feature}"
                    ];
                }
            }
        }

        // 检查 dashboard 是否完整
        if (isset($this->frontendPages['dashboard'])) {
            $content = file_get_contents($this->frontendPages['dashboard']['file']);
            
            $dashboardFeatures = [
                'statistics' => '统计数据',
                'charts' => '图表展示',
                'recent_activities' => '最近活动',
                'usage' => '套餐使用'
            ];

            foreach ($dashboardFeatures as $key => $feature) {
                if (!str_contains($content, $key)) {
                    $this->issues[] = [
                        'type' => 'info',
                        'category' => 'incomplete_dashboard',
                        'feature' => $feature,
                        'message' => "Dashboard 可能缺少: {$feature}",
                        'suggestion' => "检查 Dashboard 完整性"
                    ];
                }
            }
        }
    }

    /**
     * 生成报告
     */
    public function generateReport(): string
    {
        $report = "\n" . str_repeat("=", 70) . "\n";
        $report .= "📊 TCM Trace 业务逻辑分析报告\n";
        $report .= str_repeat("=", 70) . "\n\n";

        $errors = array_filter($this->issues, fn($i) => $i['type'] === 'error');
        $warnings = array_filter($this->issues, fn($i) => $i['type'] === 'warning');
        $infos = array_filter($this->issues, fn($i) => $i['type'] === 'info');

        $report .= "统计:\n";
        $report .= "  ❌ 错误: " . count($errors) . "\n";
        $report .= "  ⚠️  警告: " . count($warnings) . "\n";
        $report .= "  ℹ️  信息: " . count($infos) . "\n\n";

        // 按模块分组
        $byModule = [];
        foreach ($this->issues as $issue) {
            $module = $issue['module'] ?? $issue['controller'] ?? $issue['page'] ?? '通用';
            if (!isset($byModule[$module])) {
                $byModule[$module] = [];
            }
            $byModule[$module][] = $issue;
        }

        foreach ($byModule as $module => $issues) {
            $report .= "📁 {$module}\n";
            $report .= str_repeat("-", 50) . "\n";
            
            foreach ($issues as $issue) {
                $icon = $issue['type'] === 'error' ? '❌' : ($issue['type'] === 'warning' ? '⚠️' : 'ℹ️');
                $report .= "  {$icon} {$issue['message']}\n";
                if (isset($issue['suggestion'])) {
                    $report .= "     建议: {$issue['suggestion']}\n";
                }
                $report .= "\n";
            }
        }

        if (empty($this->issues)) {
            $report .= "✅ 未发现明显问题！\n";
        }

        return $report;
    }

    /**
     * 导出 JSON
     */
    public function exportJson(): string
    {
        return json_encode([
            'summary' => [
                'total_issues' => count($this->issues),
                'errors' => count(array_filter($this->issues, fn($i) => $i['type'] === 'error')),
                'warnings' => count(array_filter($this->issues, fn($i) => $i['type'] === 'warning')),
                'infos' => count(array_filter($this->issues, fn($i) => $i['type'] === 'info'))
            ],
            'routes' => $this->routes,
            'controllers' => $this->controllers,
            'frontend_pages' => $this->frontendPages,
            'business_modules' => $this->businessModules,
            'issues' => $this->issues
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}

// 命令行入口
if (PHP_SAPI === 'cli') {
    $projectPath = $argv[1] ?? getcwd();

    $analyzer = new BusinessAnalyzer($projectPath);
    $issues = $analyzer->analyze();

    echo $analyzer->generateReport();

    $jsonOutput = $analyzer->exportJson();
    $outputDir = $projectPath . '/.trae';
    if (!is_dir($outputDir)) {
        mkdir($outputDir, 0755, true);
    }
    file_put_contents($outputDir . '/business-analysis.json', $jsonOutput);

    echo "\n📄 详细结果已保存到: .trae/business-analysis.json\n";

    $errors = array_filter($issues, fn($i) => $i['type'] === 'error');
    exit(count($errors) > 0 ? 1 : 0);
}
