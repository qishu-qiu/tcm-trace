# TCM Trace 自动化代码检查与修复工作流

针对 **TCM Trace（中药溯源系统）** 的完整自动化解决方案，实现代码检查、Bug 修复、业务逻辑补全和前端页面修复的全流程自动化。

## 🎯 核心功能

| 功能 | 描述 |
|------|------|
| 🔍 **代码分析** | 检测语法错误、路由-控制器-模型对应关系 |
| 📊 **业务分析** | 检查业务逻辑完整性、API-页面对应关系 |
| 🎨 **前端修复** | 自动创建缺失页面、修复 API 调用 |
| 🐛 **自动修复** | 修复缺失视图、空方法、未实现路由 |
| 🧪 **集成测试** | 测试 API、页面、业务流程的完整性 |
| 🔄 **Git 集成** | 提交前自动检查，阻止问题代码入库 |
| 🤖 **AI 修复** | 集成 Trae AI，智能修复复杂问题 |

## 📁 文件结构

```
.tcm-trace/.trae/
├── scripts/
│   ├── code-analyzer.php          # 代码分析器
│   ├── business-analyzer.php      # 业务逻辑分析器
│   ├── auto-fixer.php             # 自动修复脚本
│   ├── frontend-fixer.php         # 前端页面修复
│   ├── test-runner.php            # 测试运行器
│   ├── integration-test.php       # 集成测试
│   └── setup-git-hooks.sh         # Git 钩子安装
├── prompts/
│   ├── bug-fix-template.md        # Bug 修复提示词
│   └── business-fix-template.md   # 业务修复提示词
├── config.json                    # 配置文件
└── README.md                      # 使用说明
```

## 🚀 快速开始

### 1. 安装 Git 钩子（自动检查）

```bash
chmod +x .trae/scripts/setup-git-hooks.sh
./.trae/scripts/setup-git-hooks.sh
```

### 2. 运行代码分析

```bash
# 基础代码分析
php .trae/scripts/code-analyzer.php

# 业务逻辑分析
php .trae/scripts/business-analyzer.php
```

### 3. 运行自动修复

```bash
# 修复代码问题
php .trae/scripts/auto-fixer.php

# 修复前端页面
php .trae/scripts/frontend-fixer.php
```

### 4. 运行测试

```bash
# 基础测试
php .trae/scripts/test-runner.php

# 集成测试
php .trae/scripts/integration-test.php
```

## 📋 检查项清单

### 代码级别
- [ ] PHP 语法错误
- [ ] 未定义变量/方法
- [ ] 缺失的视图文件
- [ ] 空方法实现
- [ ] 代码重复

### 业务逻辑
- [ ] API 路由完整性
- [ ] 控制器方法实现
- [ ] 数据验证规则
- [ ] 审计日志记录
- [ ] 租户权限检查

### 前端页面
- [ ] 页面文件存在
- [ ] API 调用正确
- [ ] 表单验证
- [ ] 列表展示
- [ ] 交互功能

### 业务流程
- [ ] 产品创建流程
- [ ] 批次创建流程
- [ ] 二维码生成流程
- [ ] 扫码验证流程
- [ ] 溯源查询流程

## 🔍 业务模块检查

### 1. 认证授权 (Auth)
- [ ] 登录/注册 API
- [ ] Token 刷新
- [ ] 权限验证
- [ ] 登录页面

### 2. 产品管理 (Product)
- [ ] CRUD API
- [ ] 分类管理
- [ ] 图片上传
- [ ] 产品列表页
- [ ] 产品表单页

### 3. 批次管理 (Batch)
- [ ] CRUD API
- [ ] 产品关联
- [ ] 批次列表页
- [ ] 批次表单页

### 4. 二维码管理 (Qrcode)
- [ ] 生成 API
- [ ] 批量操作
- [ ] 打印下载
- [ ] 二维码列表页

### 5. 溯源记录 (TraceRecord)
- [ ] 记录 API
- [ ] 批次关联
- [ ] 溯源查询页

### 6. 审计日志 (AuditLog)
- [ ] 记录 API
- [ ] 查询导出
- [ ] 审计日志页

### 7. 统计分析 (Statistics)
- [ ] 统计 API
- [ ] 图表展示
- [ ] Dashboard
- [ ] 统计页面

### 8. 订阅计费 (Billing)
- [ ] 套餐 API
- [ ] 升级流程
- [ ] 计费页面

### 9. 租户管理 (Tenant)
- [ ] 租户 API
- [ ] 设置页面

### 10. 用户管理 (User)
- [ ] CRUD API
- [ ] 权限管理
- [ ] 用户管理页

## 🤖 Trae AI 集成

### 方式1: 使用提示词模板

1. **运行业务分析**
   ```bash
   php .trae/scripts/business-analyzer.php
   ```

2. **在 Trae 中打开项目**

3. **复制提示词**
   
   打开 `.trae/prompts/business-fix-template.md`，复制相应的提示词模板

4. **粘贴到 Trae AI 对话**

5. **让 AI 自动修复**

### 方式2: 手动修复流程

```bash
# 1. 分析
php .trae/scripts/business-analyzer.php

# 2. 查看报告
cat .trae/business-analysis.json

# 3. 自动修复
php .trae/scripts/frontend-fixer.php

# 4. 测试验证
php .trae/scripts/integration-test.php
```

## 🔄 完整工作流

### 场景: 检查并修复业务逻辑

```bash
# 1. 业务分析
php .trae/scripts/business-analyzer.php

# 2. 查看分析结果
cat .trae/business-analysis.json

# 3. 使用 AI 修复（推荐）
# 复制 .trae/prompts/business-fix-template.md 到 Trae AI

# 4. 或自动修复
php .trae/scripts/frontend-fixer.php

# 5. 运行集成测试
php .trae/scripts/integration-test.php

# 6. 提交代码
git add .
git commit -m "修复业务逻辑和前端页面"
```

## 📊 报告文件

| 文件 | 说明 |
|------|------|
| `analysis-result.json` | 代码分析结果 |
| `business-analysis.json` | 业务逻辑分析 |
| `fix-report.json` | 自动修复记录 |
| `frontend-fix-report.json` | 前端修复记录 |
| `test-report.json` | 测试报告 |
| `integration-test-report.json` | 集成测试报告 |

## ⚙️ 配置说明

编辑 `.trae/config.json`：

```json
{
    "checks": {
        "syntax": true,           // 语法检查
        "routes": true,           // 路由检查
        "controllers": true,      // 控制器检查
        "business_logic": true,   // 业务逻辑检查
        "frontend_pages": true,   // 前端页面检查
        "api_consistency": true,  // API 一致性检查
        "security": true          // 安全检查
    }
}
```

## 🛠️ 故障排除

### 问题: PHP 命令未找到

```bash
# 安装 PHP 8.2
# Ubuntu/Debian:
sudo apt install php8.2 php8.2-cli

# macOS:
brew install php@8.2
```

### 问题: 权限错误

```bash
chmod +x .trae/scripts/*.sh
chmod +x .trae/scripts/*.php
```

### 问题: Git 钩子未触发

```bash
# 检查钩子
ls -la .git/hooks/pre-commit

# 重新安装
./.trae/scripts/setup-git-hooks.sh
```

## 📈 效果对比

| 指标 | 使用前 | 使用后 |
|------|--------|--------|
| Bug 发现时间 | 生产环境 | 提交前 |
| 缺失页面发现 | 用户反馈 | 自动检测 |
| 业务逻辑检查 | 人工审查 | 自动分析 |
| 代码审查时间 | 数小时 | 数分钟 |

## 🎉 总结

这个工作流帮你实现：

1. **预防问题** - 提交前自动拦截 Bug
2. **业务检查** - 自动检测缺失的功能
3. **前端修复** - 自动创建缺失页面
4. **AI 辅助** - 复杂问题 AI 智能处理
5. **质量保证** - 修复后自动测试验证

开始使用，让你的 TCM Trace 项目代码质量和业务完整性飞跃提升！
