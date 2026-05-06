<?php
/**
 * TCM Trace - CodeIgniter 4 代码分析器
 * 针对中药溯源系统的定制化代码检查
 */

class CodeAnalyzer
{
    private string $projectPath;
    private array $issues = [];
    private array $controllers = [];
    private array $models = [];
    private array $views = [];
    private array $routes = [];
    private array $filters = [];
    private array $services = [];

    public function __construct(string $projectPath)
    {
        $this->projectPath = rtrim($projectPath, '/');
    }

    /**
     * 运行完整分析
     */
    public function analyze(): array
    {
        echo "🔍 TCM Trace 代码分析器\n";
        echo str_repeat("=", 60) . "\n\n";

        $this->scanRoutes();
        $this->scanControllers();
        $this->scanModels();
        $this->scanViews();
        $this->scanFilters();
        $this->scanServices();

        $this->checkUnusedControllers();
        $this->checkMissingViews();
        $this->checkUnimplementedRoutes();
        $this->checkDuplicateCode();
        $this->checkLogicCompleteness();
        $this->checkApiConsistency();
        $this->checkSecurityIssues();
        $this->checkBusinessLogic();

        return $this->issues;
    }

    /**
     * 扫描路由配置
     */
    private function scanRoutes(): void
    {
        echo "📋 扫描路由配置...\n";

        $routesFile = $this->projectPath . '/app/Config/Routes.php';
        if (!file_exists($routesFile)) {
            $this->issues[] = [
                'type' => 'error',
                'category' => 'routes',
                'message' => 'Routes.php 文件不存在',
                'file' => $routesFile
            ];
            return;
        }

        $content = file_get_contents($routesFile);

        // 匹配路由定义 - 支持多种路由定义方式
        preg_match_all('/\$routes->(get|post|put|delete|patch|options|cli)\([\'"]([^\'"]+)[\'"]/', $content, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $method = $match[1];
            $route = $match[2];

            // 提取控制器和方法
            $pattern = '/\$routes->' . $method . '\([\'"]' . preg_quote($route, '/') . '[\'"],\s*[\'"]([^\'"]+)/';
            preg_match($pattern, $content, $controllerMatch);

            $this->routes[] = [
                'method' => $method,
                'route' => $route,
                'handler' => $controllerMatch[1] ?? null
            ];
        }

        echo "   ✓ 发现 " . count($this->routes) . " 条路由\n";
    }

    /**
     * 扫描控制器
     */
    private function scanControllers(): void
    {
        echo "🎮 扫描控制器...\n";

        $controllersPath = $this->projectPath . '/app/Controllers';
        if (!is_dir($controllersPath)) {
            $this->issues[] = [
                'type' => 'error',
                'category' => 'controllers',
                'message' => 'Controllers 目录不存在',
                'file' => $controllersPath
            ];
            return;
        }

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($controllersPath)
        );

        foreach ($files as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $this->analyzeController($file->getPathname());
            }
        }

        echo "   ✓ 发现 " . count($this->controllers) . " 个控制器\n";
    }

    /**
     * 分析单个控制器
     */
    private function analyzeController(string $filePath): void
    {
        $content = file_get_contents($filePath);
        $className = basename($filePath, '.php');

        // 提取命名空间
        preg_match('/namespace\s+([^;]+);/', $content, $namespaceMatch);
        $namespace = $namespaceMatch[1] ?? 'App\\Controllers';
        $fullClassName = $namespace . '\\' . $className;

        // 提取公共方法
        preg_match_all('/public\s+function\s+(\w+)\s*\(/', $content, $methodMatches);

        $methods = [];
        foreach ($methodMatches[1] as $method) {
            if ($method === '__construct') continue;

            // 分析方法内容
            $methodPattern = '/public\s+function\s+' . $method . '\s*\([^)]*\)\s*\{([^}]+(?:\{[^}]*\}[^}]*)*)\}/s';
            preg_match($methodPattern, $content, $methodContent);

            $methodBody = $methodContent[1] ?? '';

            $methods[$method] = [
                'has_return' => str_contains($methodBody, 'return'),
                'has_view' => str_contains($methodBody, 'view(') || str_contains($methodBody, 'load->view'),
                'has_json' => str_contains($methodBody, 'json(') || str_contains($methodBody, 'setJSON') || str_contains($methodBody, 'respond'),
                'has_redirect' => str_contains($methodBody, 'redirect'),
                'has_model' => preg_match('/\$this->\w+Model/', $methodBody),
                'has_service' => preg_match('/\$this->\w+Service/', $methodBody) || preg_match('/service\([\'"]\w+[\'"]\)/', $methodBody),
                'has_validation' => str_contains($methodBody, 'validate') || str_contains($methodBody, 'Validation'),
                'has_auth_check' => str_contains($methodBody, 'auth') || str_contains($methodBody, 'user') || str_contains($methodBody, 'tenant'),
                'is_empty' => empty(trim($methodBody)) || trim($methodBody) === '{}',
                'body' => $methodBody
            ];
        }

        $this->controllers[$fullClassName] = [
            'file' => $filePath,
            'class' => $className,
            'namespace' => $namespace,
            'methods' => $methods
        ];
    }

    /**
     * 扫描模型
     */
    private function scanModels(): void
    {
        echo "💾 扫描模型...\n";

        $modelsPath = $this->projectPath . '/app/Models';
        if (!is_dir($modelsPath)) {
            return;
        }

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($modelsPath)
        );

        foreach ($files as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $className = basename($file->getPathname(), '.php');
                $content = file_get_contents($file->getPathname());
                
                // 检查模型配置
                $hasTable = preg_match('/protected\s+\$table\s*=\s*[\'"]([^\'"]+)/', $content, $tableMatch);
                $hasPrimaryKey = preg_match('/protected\s+\$primaryKey\s*=\s*[\'"]([^\'"]+)/', $content);
                $hasAllowedFields = str_contains($content, '$allowedFields');
                
                $this->models[$className] = [
                    'file' => $file->getPathname(),
                    'table' => $tableMatch[1] ?? null,
                    'has_table' => $hasTable,
                    'has_primary_key' => $hasPrimaryKey,
                    'has_allowed_fields' => $hasAllowedFields
                ];
            }
        }

        echo "   ✓ 发现 " . count($this->models) . " 个模型\n";
    }

    /**
     * 扫描视图
     */
    private function scanViews(): void
    {
        echo "🎨 扫描视图...\n";

        $viewsPath = $this->projectPath . '/app/Views';
        if (!is_dir($viewsPath)) {
            return;
        }

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($viewsPath)
        );

        foreach ($files as $file) {
            if ($file->isFile()) {
                $relativePath = str_replace($viewsPath . '/', '', $file->getPathname());
                $this->views[] = [
                    'path' => $relativePath,
                    'full_path' => $file->getPathname()
                ];
            }
        }

        echo "   ✓ 发现 " . count($this->views) . " 个视图\n";
    }

    /**
     * 扫描过滤器
     */
    private function scanFilters(): void
    {
        echo "🔒 扫描过滤器...\n";

        $filtersPath = $this->projectPath . '/app/Filters';
        if (!is_dir($filtersPath)) {
            return;
        }

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($filtersPath)
        );

        foreach ($files as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $className = basename($file->getPathname(), '.php');
                $this->filters[] = [
                    'class' => $className,
                    'file' => $file->getPathname()
                ];
            }
        }

        echo "   ✓ 发现 " . count($this->filters) . " 个过滤器\n";
    }

    /**
     * 扫描服务层
     */
    private function scanServices(): void
    {
        echo "⚙️  扫描服务层...\n";

        $servicesPath = $this->projectPath . '/app/Libraries';
        if (!is_dir($servicesPath)) {
            return;
        }

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($servicesPath)
        );

        foreach ($files as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $className = basename($file->getPathname(), '.php');
                $content = file_get_contents($file->getPathname());
                
                // 检查是否是服务类（以 Service 结尾）
                if (str_ends_with($className, 'Service')) {
                    $this->services[$className] = [
                        'file' => $file->getPathname(),
                        'has_construct' => str_contains($content, '__construct')
                    ];
                }
            }
        }

        echo "   ✓ 发现 " . count($this->services) . " 个服务类\n";
    }

    /**
     * 检查未使用的控制器
     */
    private function checkUnusedControllers(): void
    {
        echo "\n🔍 检查未使用的控制器...\n";

        foreach ($this->controllers as $className => $controller) {
            $isUsed = false;

            foreach ($this->routes as $route) {
                if ($route['handler'] && str_contains($route['handler'], $controller['class'])) {
                    $isUsed = true;
                    break;
                }
            }

            if (!$isUsed) {
                $this->issues[] = [
                    'type' => 'warning',
                    'category' => 'unused_controller',
                    'message' => "控制器 {$className} 没有对应的路由",
                    'file' => $controller['file'],
                    'suggestion' => "在 Routes.php 中添加路由配置，或删除未使用的控制器"
                ];
            }
        }
    }

    /**
     * 检查缺失的视图
     */
    private function checkMissingViews(): void
    {
        echo "🔍 检查缺失的视图...\n";

        foreach ($this->controllers as $className => $controller) {
            foreach ($controller['methods'] as $methodName => $method) {
                if (!$method['has_view']) continue;

                // 提取视图名称
                preg_match_all('/view\([\'"]([^\'"]+)[\'"]/', $method['body'], $viewMatches);

                foreach ($viewMatches[1] as $viewName) {
                    $viewExists = false;
                    foreach ($this->views as $view) {
                        if (str_contains($view['path'], $viewName)) {
                            $viewExists = true;
                            break;
                        }
                    }

                    if (!$viewExists) {
                        $this->issues[] = [
                            'type' => 'error',
                            'category' => 'missing_view',
                            'message' => "方法 {$className}::{$methodName} 引用的视图 '{$viewName}' 不存在",
                            'file' => $controller['file'],
                            'suggestion' => "创建视图文件 app/Views/{$viewName}.php"
                        ];
                    }
                }
            }
        }
    }

    /**
     * 检查未实现的路由
     */
    private function checkUnimplementedRoutes(): void
    {
        echo "🔍 检查未实现的路由...\n";

        foreach ($this->routes as $route) {
            if (!$route['handler']) continue;

            $parts = explode('::', $route['handler']);
            $controllerName = $parts[0] ?? '';
            $methodName = $parts[1] ?? 'index';

            // 移除命名空间前缀
            $shortName = substr($controllerName, strrpos($controllerName, '\\') + 1);

            $found = false;
            foreach ($this->controllers as $className => $controller) {
                if ($controller['class'] === $shortName) {
                    if (isset($controller['methods'][$methodName])) {
                        $found = true;

                        // 检查方法是否为空
                        if ($controller['methods'][$methodName]['is_empty']) {
                            $this->issues[] = [
                                'type' => 'warning',
                                'category' => 'empty_method',
                                'message' => "路由 {$route['route']} 对应的方法 {$methodName} 为空实现",
                                'file' => $controller['file'],
                                'suggestion' => "补充 {$methodName} 方法的业务逻辑"
                            ];
                        }
                    }
                    break;
                }
            }

            if (!$found) {
                $this->issues[] = [
                    'type' => 'error',
                    'category' => 'unimplemented_route',
                    'message' => "路由 {$route['route']} 指向的控制器方法不存在: {$route['handler']}",
                    'file' => $this->projectPath . '/app/Config/Routes.php',
                    'suggestion' => "创建控制器 {$controllerName} 并添加方法 {$methodName}"
                ];
            }
        }
    }

    /**
     * 检查代码重复
     */
    private function checkDuplicateCode(): void
    {
        echo "🔍 检查代码重复...\n";

        $codeBlocks = [];

        foreach ($this->controllers as $className => $controller) {
            foreach ($controller['methods'] as $methodName => $method) {
                // 提取关键代码模式
                $patterns = [
                    'validation' => '/\$this->validate\([^)]+\)/',
                    'db_query' => '/\$this->db->\w+\(/',
                    'session' => '/\$this->session->\w+\(/',
                    'model_call' => '/\$this->\w+Model->\w+\(/',
                    'json_response' => '/return\s+\$this->respond\(/',
                    'auth_check' => '/\$this->request->user|getUser|auth/',
                    'tenant_check' => '/tenant|getTenant/',
                    'audit_log' => '/audit|log/'
                ];

                foreach ($patterns as $type => $pattern) {
                    preg_match_all($pattern, $method['body'], $matches);
                    foreach ($matches[0] as $match) {
                        $key = $type . ':' . $match;
                        if (!isset($codeBlocks[$key])) {
                            $codeBlocks[$key] = [];
                        }
                        $codeBlocks[$key][] = [
                            'controller' => $className,
                            'method' => $methodName,
                            'file' => $controller['file']
                        ];
                    }
                }
            }
        }

        // 报告重复超过3次的代码
        foreach ($codeBlocks as $pattern => $occurrences) {
            if (count($occurrences) >= 3) {
                $this->issues[] = [
                    'type' => 'info',
                    'category' => 'duplicate_code',
                    'message' => "代码模式 '{$pattern}' 在 " . count($occurrences) . " 处重复出现",
                    'locations' => $occurrences,
                    'suggestion' => "考虑提取为公共方法或基类方法"
                ];
            }
        }
    }

    /**
     * 检查逻辑完整性
     */
    private function checkLogicCompleteness(): void
    {
        echo "🔍 检查逻辑完整性...\n";

        foreach ($this->controllers as $className => $controller) {
            foreach ($controller['methods'] as $methodName => $method) {
                // 检查表单提交方法是否有验证
                if (str_contains($methodName, 'create') || str_contains($methodName, 'store') ||
                    str_contains($methodName, 'update') || str_contains($methodName, 'save')) {

                    if (!$method['has_validation']) {
                        $this->issues[] = [
                            'type' => 'warning',
                            'category' => 'missing_validation',
                            'message' => "方法 {$className}::{$methodName} 可能缺少输入验证",
                            'file' => $controller['file'],
                            'suggestion' => "添加 \$this->validate() 进行输入验证"
                        ];
                    }
                }

                // 检查是否有返回值
                if (!$method['has_return'] && !$method['has_view'] && !$method['has_json'] && !$method['has_redirect']) {
                    $this->issues[] = [
                        'type' => 'warning',
                        'category' => 'no_output',
                        'message' => "方法 {$className}::{$methodName} 没有输出或返回",
                        'file' => $controller['file'],
                        'suggestion' => "添加 return \$this->respond() 或 return view()"
                    ];
                }

                // 检查 API 方法是否返回 JSON
                if (str_contains($className, 'Api') || str_contains($methodName, 'api')) {
                    if (!$method['has_json']) {
                        $this->issues[] = [
                            'type' => 'info',
                            'category' => 'api_no_json',
                            'message' => "API 方法 {$className}::{$methodName} 可能未返回 JSON 响应",
                            'file' => $controller['file'],
                            'suggestion' => "使用 return \$this->respond() 返回 JSON"
                        ];
                    }
                }
            }
        }
    }

    /**
     * 检查 API 一致性
     */
    private function checkApiConsistency(): void
    {
        echo "🔍 检查 API 一致性...\n";

        // 检查 RESTful 资源路由是否完整
        $resourcePatterns = [
            'users' => ['index', 'create', 'show', 'update', 'delete'],
            'products' => ['index', 'create', 'show', 'update', 'delete'],
            'batches' => ['index', 'create', 'show', 'update', 'delete'],
            'qrcodes' => ['index', 'generate', 'show', 'updateStatus', 'disable'],
            'trace-records' => ['byBatch', 'create', 'update', 'delete'],
            'audit' => ['index', 'show', 'resource', 'export'],
            'statistics' => ['index', 'scan', 'product', 'risk'],
            'billing' => ['index', 'plans', 'current', 'upgrade', 'checkExpiration']
        ];

        foreach ($resourcePatterns as $resource => $expectedMethods) {
            $foundMethods = [];
            
            foreach ($this->routes as $route) {
                if (str_contains($route['route'], $resource)) {
                    if ($route['handler']) {
                        $parts = explode('::', $route['handler']);
                        $methodName = $parts[1] ?? '';
                        $foundMethods[] = $methodName;
                    }
                }
            }

            $missingMethods = array_diff($expectedMethods, $foundMethods);
            if (!empty($missingMethods)) {
                $this->issues[] = [
                    'type' => 'info',
                    'category' => 'incomplete_api',
                    'message' => "资源 '{$resource}' 可能缺少方法: " . implode(', ', $missingMethods),
                    'suggestion' => "检查是否需要实现这些方法"
                ];
            }
        }
    }

    /**
     * 检查安全问题
     */
    private function checkSecurityIssues(): void
    {
        echo "🔍 检查安全问题...\n";

        foreach ($this->controllers as $className => $controller) {
            foreach ($controller['methods'] as $methodName => $method) {
                // 检查敏感操作是否有权限验证
                $sensitiveOperations = ['delete', 'update', 'create', 'reset', 'upgrade'];
                $isSensitive = false;
                foreach ($sensitiveOperations as $op) {
                    if (str_contains($methodName, $op)) {
                        $isSensitive = true;
                        break;
                    }
                }

                if ($isSensitive && !$method['has_auth_check']) {
                    $this->issues[] = [
                        'type' => 'warning',
                        'category' => 'security_no_auth',
                        'message' => "敏感操作 {$className}::{$methodName} 可能缺少权限检查",
                        'file' => $controller['file'],
                        'suggestion' => "添加用户身份验证和权限检查"
                    ];
                }

                // 检查是否有 SQL 注入风险
                if (preg_match('/query\s*\(\s*[\'"][^\'"]*\$\w+/', $method['body'])) {
                    $this->issues[] = [
                        'type' => 'error',
                        'category' => 'security_sql_injection',
                        'message' => "方法 {$className}::{$methodName} 可能存在 SQL 注入风险",
                        'file' => $controller['file'],
                        'suggestion' => "使用参数化查询或查询构建器"
                    ];
                }
            }
        }
    }

    /**
     * 检查业务逻辑完整性
     */
    private function checkBusinessLogic(): void
    {
        echo "🔍 检查业务逻辑...\n";

        // 检查模型配置
        foreach ($this->models as $modelName => $model) {
            if (!$model['has_table']) {
                $this->issues[] = [
                    'type' => 'warning',
                    'category' => 'model_no_table',
                    'message' => "模型 {$modelName} 未配置数据表",
                    'file' => $model['file'],
                    'suggestion' => "添加 protected \$table = 'table_name'"
                ];
            }

            if (!$model['has_primary_key']) {
                $this->issues[] = [
                    'type' => 'info',
                    'category' => 'model_no_primary_key',
                    'message' => "模型 {$modelName} 未配置主键",
                    'file' => $model['file'],
                    'suggestion' => "添加 protected \$primaryKey = 'id'"
                ];
            }
        }

        // 检查服务类
        foreach ($this->services as $serviceName => $service) {
            if (!$service['has_construct']) {
                $this->issues[] = [
                    'type' => 'info',
                    'category' => 'service_no_construct',
                    'message' => "服务类 {$serviceName} 缺少构造函数",
                    'file' => $service['file'],
                    'suggestion' => "考虑添加构造函数初始化依赖"
                ];
            }
        }
    }

    /**
     * 生成报告
     */
    public function generateReport(): string
    {
        $report = "\n" . str_repeat("=", 60) . "\n";
        $report .= "📊 TCM Trace 代码分析报告\n";
        $report .= str_repeat("=", 60) . "\n\n";

        $errors = array_filter($this->issues, fn($i) => $i['type'] === 'error');
        $warnings = array_filter($this->issues, fn($i) => $i['type'] === 'warning');
        $infos = array_filter($this->issues, fn($i) => $i['type'] === 'info');

        $report .= "统计:\n";
        $report .= "  ❌ 错误: " . count($errors) . "\n";
        $report .= "  ⚠️  警告: " . count($warnings) . "\n";
        $report .= "  ℹ️  信息: " . count($infos) . "\n\n";

        if (!empty($errors)) {
            $report .= "❌ 错误:\n";
            foreach ($errors as $issue) {
                $report .= $this->formatIssue($issue);
            }
        }

        if (!empty($warnings)) {
            $report .= "\n⚠️  警告:\n";
            foreach ($warnings as $issue) {
                $report .= $this->formatIssue($issue);
            }
        }

        if (!empty($infos)) {
            $report .= "\nℹ️  信息:\n";
            foreach ($infos as $issue) {
                $report .= $this->formatIssue($issue);
            }
        }

        if (empty($this->issues)) {
            $report .= "✅ 未发现明显问题！\n";
        }

        return $report;
    }

    /**
     * 格式化问题输出
     */
    private function formatIssue(array $issue): string
    {
        $output = "\n  [{$issue['category']}] {$issue['message']}\n";
        $output .= "  文件: {$issue['file']}\n";
        if (isset($issue['suggestion'])) {
            $output .= "  建议: {$issue['suggestion']}\n";
        }
        return $output;
    }

    /**
     * 导出 JSON 格式结果（供 AI 修复使用）
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
            'models' => $this->models,
            'views' => $this->views,
            'filters' => $this->filters,
            'services' => $this->services,
            'issues' => $this->issues
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}

// 命令行入口
if (PHP_SAPI === 'cli') {
    $projectPath = $argv[1] ?? getcwd();

    $analyzer = new CodeAnalyzer($projectPath);
    $issues = $analyzer->analyze();

    echo $analyzer->generateReport();

    // 导出 JSON 供后续处理
    $jsonOutput = $analyzer->exportJson();
    $outputDir = $projectPath . '/.trae';
    if (!is_dir($outputDir)) {
        mkdir($outputDir, 0755, true);
    }
    file_put_contents($outputDir . '/analysis-result.json', $jsonOutput);

    echo "\n📄 详细结果已保存到: .trae/analysis-result.json\n";

    // 如果有错误，返回非零退出码
    $errors = array_filter($issues, fn($i) => $i['type'] === 'error');
    exit(count($errors) > 0 ? 1 : 0);
}
