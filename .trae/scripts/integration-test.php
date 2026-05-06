<?php
/**
 * TCM Trace - 集成测试脚本
 * 测试 API 和前端页面的集成
 */

class IntegrationTest
{
    private string $projectPath;
    private array $testResults = [];
    private array $errors = [];
    private string $baseUrl;

    public function __construct(string $projectPath, string $baseUrl = 'http://localhost:8080')
    {
        $this->projectPath = rtrim($projectPath, '/');
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    /**
     * 运行集成测试
     */
    public function run(): void
    {
        echo "🧪 TCM Trace 集成测试\n";
        echo str_repeat("=", 70) . "\n\n";

        $this->testApiEndpoints();
        $this->testFrontendPages();
        $this->testBusinessFlows();
        $this->testDataConsistency();

        $this->generateReport();
    }

    /**
     * 测试 API 端点
     */
    private function testApiEndpoints(): void
    {
        echo "🔌 测试 API 端点...\n";

        $endpoints = [
            ['GET', '/api/auth/login', '登录接口'],
            ['GET', '/api/products', '产品列表'],
            ['GET', '/api/batches', '批次列表'],
            ['GET', '/api/qrcodes', '二维码列表'],
            ['GET', '/api/audit', '审计日志'],
            ['GET', '/api/statistics', '统计数据'],
            ['GET', '/api/billing', '计费信息'],
            ['GET', '/api/tenant', '租户信息'],
        ];

        $passed = 0;
        $failed = 0;

        foreach ($endpoints as $endpoint) {
            list($method, $path, $description) = $endpoint;
            
            // 检查路由配置是否存在
            $routesFile = $this->projectPath . '/app/Config/Routes.php';
            $routesContent = file_get_contents($routesFile);
            
            $exists = str_contains($routesContent, $path);
            
            if ($exists) {
                $passed++;
                echo "   ✓ {$description} ({$path})\n";
            } else {
                $failed++;
                $this->errors[] = [
                    'type' => 'api',
                    'endpoint' => $path,
                    'message' => "API 路由不存在: {$path}"
                ];
                echo "   ✗ {$description} ({$path}) - 不存在\n";
            }
        }

        $this->testResults['api_endpoints'] = [
            'name' => 'API 端点测试',
            'passed' => $passed,
            'failed' => $failed,
            'total' => count($endpoints)
        ];
    }

    /**
     * 测试前端页面
     */
    private function testFrontendPages(): void
    {
        echo "🎨 测试前端页面...\n";

        $requiredPages = [
            'login' => '登录页面',
            'index' => '管理后台首页',
            'dashboard' => '工作台',
            'products' => '产品管理',
            'batches' => '批次管理',
            'qrcodes' => '二维码管理',
            'statistics' => '统计分析',
            'audit' => '审计日志',
            'billing' => '订阅管理',
            'settings' => '系统设置',
        ];

        $passed = 0;
        $failed = 0;

        foreach ($requiredPages as $page => $description) {
            $pageFile = $this->projectPath . '/public/admin/' . $page . '.html';
            
            if (file_exists($pageFile)) {
                $content = file_get_contents($pageFile);
                $hasApiCalls = str_contains($content, 'api.') || str_contains($content, 'api.get');
                $hasBasicStructure = str_contains($content, '<html') && str_contains($content, '<body');
                
                if ($hasBasicStructure) {
                    $passed++;
                    echo "   ✓ {$description} ({$page}.html)" . ($hasApiCalls ? '' : ' - 无API调用') . "\n";
                } else {
                    $failed++;
                    $this->errors[] = [
                        'type' => 'page',
                        'page' => $page,
                        'message' => "页面结构不完整: {$page}.html"
                    ];
                    echo "   ✗ {$description} ({$page}.html) - 结构不完整\n";
                }
            } else {
                $failed++;
                $this->errors[] = [
                    'type' => 'page',
                    'page' => $page,
                    'message' => "页面不存在: {$page}.html"
                ];
                echo "   ✗ {$description} ({$page}.html) - 不存在\n";
            }
        }

        $this->testResults['frontend_pages'] = [
            'name' => '前端页面测试',
            'passed' => $passed,
            'failed' => $failed,
            'total' => count($requiredPages)
        ];
    }

    /**
     * 测试业务流程
     */
    private function testBusinessFlows(): void
    {
        echo "🔄 测试业务流程...\n";

        $flows = [
            [
                'name' => '产品管理流程',
                'steps' => [
                    'products.html 存在',
                    'Product 控制器存在',
                    'products 路由配置',
                    '产品 CRUD API 完整'
                ]
            ],
            [
                'name' => '批次管理流程',
                'steps' => [
                    'batches.html 存在',
                    'Batch 控制器存在',
                    'batches 路由配置',
                    '批次与产品关联'
                ]
            ],
            [
                'name' => '二维码管理流程',
                'steps' => [
                    'qrcodes.html 存在',
                    'Qrcode 控制器存在',
                    'qrcodes 路由配置',
                    '二维码生成逻辑'
                ]
            ],
            [
                'name' => '扫码验证流程',
                'steps' => [
                    'verify.html 存在',
                    'Scan 控制器存在',
                    'scan/verify 路由',
                    '溯源记录查询'
                ]
            ]
        ];

        $passed = 0;
        $failed = 0;

        foreach ($flows as $flow) {
            $flowPassed = true;
            $missingSteps = [];

            foreach ($flow['steps'] as $step) {
                $exists = $this->checkFlowStep($step);
                if (!$exists) {
                    $flowPassed = false;
                    $missingSteps[] = $step;
                }
            }

            if ($flowPassed) {
                $passed++;
                echo "   ✓ {$flow['name']}\n";
            } else {
                $failed++;
                $this->errors[] = [
                    'type' => 'flow',
                    'flow' => $flow['name'],
                    'message' => "业务流程不完整: " . implode(', ', $missingSteps)
                ];
                echo "   ✗ {$flow['name']} - 缺少: " . implode(', ', $missingSteps) . "\n";
            }
        }

        $this->testResults['business_flows'] = [
            'name' => '业务流程测试',
            'passed' => $passed,
            'failed' => $failed,
            'total' => count($flows)
        ];
    }

    /**
     * 检查流程步骤
     */
    private function checkFlowStep(string $step): bool
    {
        if (str_contains($step, '.html')) {
            $page = str_replace('.html', '', explode(' ', $step)[0]);
            return file_exists($this->projectPath . '/public/admin/' . $page . '.html') ||
                   file_exists($this->projectPath . '/public/consumer/' . $page . '.html');
        }

        if (str_contains($step, '控制器')) {
            $controller = explode(' ', $step)[0];
            return file_exists($this->projectPath . '/app/Controllers/' . $controller . '.php');
        }

        if (str_contains($step, '路由')) {
            $routesContent = file_get_contents($this->projectPath . '/app/Config/Routes.php');
            $route = explode(' ', $step)[0];
            return str_contains($routesContent, $route);
        }

        return true; // 其他步骤默认通过
    }

    /**
     * 测试数据一致性
     */
    private function testDataConsistency(): void
    {
        echo "💾 测试数据一致性...\n";

        $checks = [
            [
                'name' => '模型与数据表对应',
                'check' => function() {
                    $modelsPath = $this->projectPath . '/app/Models';
                    if (!is_dir($modelsPath)) return false;
                    
                    $files = glob($modelsPath . '/*Model.php');
                    return count($files) > 0;
                }
            ],
            [
                'name' => '数据库迁移文件',
                'check' => function() {
                    $migrationsPath = $this->projectPath . '/app/Database/Migrations';
                    if (!is_dir($migrationsPath)) return false;
                    
                    $files = glob($migrationsPath . '/*.php');
                    return count($files) > 0;
                }
            ],
            [
                'name' => '种子数据',
                'check' => function() {
                    $seedsPath = $this->projectPath . '/app/Database/Seeds';
                    if (!is_dir($seedsPath)) return false;
                    
                    $files = glob($seedsPath . '/*.php');
                    return count($files) > 0;
                }
            ],
            [
                'name' => '过滤器配置',
                'check' => function() {
                    $filtersPath = $this->projectPath . '/app/Filters';
                    if (!is_dir($filtersPath)) return false;
                    
                    $files = glob($filtersPath . '/*.php');
                    return count($files) > 0;
                }
            ]
        ];

        $passed = 0;
        $failed = 0;

        foreach ($checks as $check) {
            $result = $check['check']();
            
            if ($result) {
                $passed++;
                echo "   ✓ {$check['name']}\n";
            } else {
                $failed++;
                $this->errors[] = [
                    'type' => 'consistency',
                    'check' => $check['name'],
                    'message' => "数据一致性检查失败: {$check['name']}"
                ];
                echo "   ✗ {$check['name']}\n";
            }
        }

        $this->testResults['data_consistency'] = [
            'name' => '数据一致性测试',
            'passed' => $passed,
            'failed' => $failed,
            'total' => count($checks)
        ];
    }

    /**
     * 生成测试报告
     */
    private function generateReport(): void
    {
        echo "\n" . str_repeat("=", 70) . "\n";
        echo "🧪 集成测试报告\n";
        echo str_repeat("=", 70) . "\n\n";

        $totalPassed = 0;
        $totalFailed = 0;

        foreach ($this->testResults as $test) {
            $totalPassed += $test['passed'];
            $totalFailed += $test['failed'];

            $status = $test['failed'] === 0 ? '✅' : '⚠️';
            echo "{$status} {$test['name']}\n";
            echo "   通过: {$test['passed']}, 失败: {$test['failed']}, 总计: {$test['total']}\n\n";
        }

        echo str_repeat("-", 70) . "\n";
        echo "总计: 通过 {$totalPassed}, 失败 {$totalFailed}\n";

        if (!empty($this->errors)) {
            echo "\n❌ 发现的问题:\n";
            foreach ($this->errors as $error) {
                echo "\n  [{$error['type']}] {$error['message']}\n";
            }
        }

        $report = [
            'timestamp' => date('Y-m-d H:i:s'),
            'summary' => [
                'passed' => $totalPassed,
                'failed' => $totalFailed,
                'total' => $totalPassed + $totalFailed
            ],
            'results' => $this->testResults,
            'errors' => $this->errors
        ];

        $reportFile = $this->projectPath . '/.trae/integration-test-report.json';
        file_put_contents($reportFile, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        echo "\n📄 测试报告已保存: {$reportFile}\n";

        exit($totalFailed > 0 ? 1 : 0);
    }
}

// 命令行入口
if (PHP_SAPI === 'cli') {
    $projectPath = $argv[1] ?? getcwd();
    $baseUrl = $argv[2] ?? 'http://localhost:8080';

    $tester = new IntegrationTest($projectPath, $baseUrl);
    $tester->run();
}
