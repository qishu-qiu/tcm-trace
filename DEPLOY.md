# 中药材溯源SaaS平台部署指南 v2.0

> 适用环境：CentOS + Apache/Nginx + PHP 8.2 + MySQL 5.7

---

## 目录

1. [环境要求](#环境要求)
2. [快速开始](#快速开始)
3. [详细部署步骤](#详细部署步骤)
4. [配置文件说明](#配置文件说明)
5. [安全加固](#安全加固)
6. [故障排除](#故障排除)

---

## 环境要求

| 组件 | 版本要求 | 说明 |
|------|---------|------|
| 操作系统 | CentOS 7/8/9 | 推荐 CentOS 7 |
| Web服务器 | Apache 2.4+ 或 Nginx 1.20+ | 二选一 |
| PHP版本 | 8.2 | 必须 |
| 数据库 | MySQL 5.7+ 或 MariaDB 10.2+ | **已适配MySQL 5.7.25** |
| PHP扩展 | pdo_mysql, json, mbstring, gd, fileinfo, openssl | 必须 |

### MySQL 5.7 兼容性说明

本系统已完全适配 MySQL 5.7，关键兼容点：

| 功能 | MySQL 5.7 版本要求 | 状态 |
|------|-------------------|------|
| utf8mb4 字符集 | 5.5.3+ | ✅ 完全支持 |
| JSON 字段 | 5.7.8+ | ⚠️ 使用TEXT替代 |
| ENUM 类型 | 完全支持 | ✅ |
| DECIMAL 类型 | 完全支持 | ✅ |
| CURRENT_TIMESTAMP | 完全支持 | ✅ |

---

## 快速开始

### 方式一：使用迁移命令（推荐）

```bash
# 1. 上传代码到服务器
cd /var/www/tcm-trace

# 2. 配置环境变量
cp .env.example .env
# 编辑 .env 文件，填写数据库信息

# 3. 执行数据库迁移
php spark migrate

# 4. 设置目录权限
chmod -R 755 writable/
chown -R www-data:www-data writable/
```

### 方式二：手动导入SQL（100%兼容）

```bash
# 1. 创建数据库
mysql -u root -p
CREATE DATABASE tcm_trace CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EXIT;

# 2. 导入SQL脚本
mysql -u root -p tcm_trace < database/mysql57_schema.sql

# 3. 配置环境变量
cp .env.example .env
# 编辑 .env 文件
```

---

## 详细部署步骤

### 步骤1：上传代码

将项目代码上传到服务器web目录：

```bash
# 推荐目录结构
/var/www/tcm-trace/          # 项目根目录
├── app/                     # 应用代码
├── public/                  # 网站根目录（虚拟主机指向此处）
├── vendor/                  # Composer依赖
├── writable/                # 可写目录
└── database/                # 数据库脚本
```

### 步骤2：设置PHP版本

**cPanel 用户：**
1. 登录 cPanel → 选择PHP版本
2. 切换到 PHP 8.2
3. 点击"设为当前"

**DirectAdmin 用户：**
1. 登录 DirectAdmin → PHP版本选择
2. 选择 PHP 8.2

### 步骤3：启用PHP扩展

在PHP版本设置页面启用以下扩展：
- `pdo_mysql` - MySQL数据库驱动
- `json` - JSON处理
- `mbstring` - 中文处理
- `gd` - 图片处理（二维码生成）
- `fileinfo` - 文件类型检测
- `openssl` - 加密功能

### 步骤4：创建数据库

**通过phpMyAdmin：**
1. 登录 phpMyAdmin
2. 点击"新建数据库"
3. 数据库名：`tcm_trace`
4. 排序规则：`utf8mb4_unicode_ci`

**通过命令行：**
```bash
mysql -u root -p
CREATE DATABASE tcm_trace CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'tcm_user'@'localhost' IDENTIFIED BY 'your_password';
GRANT ALL PRIVILEGES ON tcm_trace.* TO 'tcm_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 步骤5：导入数据库

**方式A - 使用迁移命令：**
```bash
cd /var/www/tcm-trace
php spark migrate
```

**方式B - 手动导入SQL：**
```bash
cd /var/www/tcm-trace
mysql -u tcm_user -p tcm_trace < database/mysql57_schema.sql
```

### 步骤6：配置 .env 文件

```bash
cd /var/www/tcm-trace
cp env .env  # 如果没有.env文件
```

编辑 `.env` 文件：

```env
# 生产环境
CI_ENVIRONMENT = production

# 基础URL（修改为你的域名）
app.baseURL = 'https://your-domain.com/'
app.indexPage = ''

# 数据库配置
database.default.hostname = localhost
database.default.database = tcm_trace
database.default.username = your_db_username
database.default.password = your_db_password
database.default.DBDriver = MySQLi
database.default.port = 3306

# JWT配置（生成随机密钥）
jwt.secret = 'your-32-character-random-secret-key'
jwt.expiresIn = 604800

# 上传配置
uploads.path = 'uploads/'
qrcode.path = 'uploads/qrcodes/'

# 日志配置
log.threshold = 1
log.path = 'writable/logs/'

# 安全配置
security.csrfProtection = 'cookie'
security.tokenRandomize = true
```

**生成JWT密钥：**
```bash
openssl rand -hex 32
```

### 步骤7：设置目录权限

```bash
cd /var/www/tcm-trace

# 设置writable目录
chmod -R 755 writable/
chmod -R 777 writable/logs/
chmod -R 777 writable/cache/
chmod -R 777 writable/session/
chmod -R 777 writable/uploads/

# 创建二维码目录
mkdir -p public/uploads/qrcodes
chmod -R 777 public/uploads/qrcodes

# 设置所有者（Apache）
chown -R apache:apache writable/
chown -R apache:apache public/uploads/
```

### 步骤8：配置Web服务器

#### Apache (.htaccess)

项目已包含 `public/.htaccess`，主要配置：

```apache
# URL重写
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php/$1 [L]

# 静态资源缓存
ExpiresByType image/jpeg "access plus 1 week"
ExpiresByType image/png "access plus 1 week"

# 二维码缓存
Header set Cache-Control "max-age=604800, public" "expr=%{REQUEST_URI} =~ m#^/uploads/qrcodes/#"

# Gzip压缩
AddOutputFilterByType DEFLATE text/plain text/html text/css application/javascript

# 安全头
Header set X-Frame-Options "SAMEORIGIN"
Header set X-Content-Type-Options "nosniff"

# 禁止访问敏感文件
<FilesMatch "\.(env|log)$">
    Order Allow,Deny
    Deny from all
</FilesMatch>
```

#### Nginx 配置参考

参考项目中的 `nginx.conf.example` 文件：

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/tcm-trace/public;
    index index.php;

    # Gzip
    gzip on;
    gzip_types text/plain text/css application/javascript application/json;

    # 静态资源缓存
    location ~* \.(jpg|png|gif|css|js)$ {
        expires 1w;
        add_header Cache-Control "public, max-age=604800";
    }

    # 二维码缓存
    location ~* ^/uploads/qrcodes/ {
        expires 1w;
        add_header Cache-Control "public, max-age=604800";
    }

    # PHP处理
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # 禁止访问敏感目录
    location ~ /\. { deny all; }
    location ~* (app|vendor|writable)/ { deny all; }
}
```

### 步骤9：配置Cron任务

设置日志清理任务（每天凌晨执行）：

```bash
# 编辑crontab
crontab -e

# 添加以下行
0 0 * * * /usr/bin/php /var/www/tcm-trace/spark clean:logs >> /var/log/tcm-trace-cron.log 2>&1
```

### 步骤10：配置SSL证书

**使用Let's Encrypt（推荐）：**

```bash
# 安装Certbot
yum install certbot python3-certbot-nginx -y

# 获取证书
certbot --nginx -d your-domain.com

# 自动续期
certbot renew --dry-run
```

---

## 配置文件说明

### 核心配置文件

| 文件路径 | 说明 | 必需配置 |
|---------|------|---------|
| `.env` | 环境变量 | ✅ 数据库、JWT密钥 |
| `public/.htaccess` | Apache配置 | 一般无需修改 |
| `nginx.conf.example` | Nginx配置参考 | 根据服务器调整 |
| `database/mysql57_schema.sql` | 数据库SQL脚本 | 手动部署时使用 |

### 数据库迁移文件

| 文件 | 说明 |
|------|------|
| `CreateTenants.php` | 租户表 |
| `CreateUsers.php` | 用户表 |
| `CreateProducts.php` | 产品表 |
| `CreateBatches.php` | 批次表 |
| `CreateTraceRecords.php` | 溯源记录表 |
| `CreateQrcodes.php` | 二维码表 |
| `CreateScanLogs.php` | 扫码日志表 |
| `CreateAuditLogs.php` | 审计日志表 |

---

## 安全加固

### 1. 生产环境配置

```env
CI_ENVIRONMENT = production
```

这会自动：
- 关闭PHP错误显示
- 启用CSRF保护
- 启用安全头

### 2. 文件访问控制

`.htaccess` 已配置禁止直接访问：
- `.env` - 环境配置文件
- `writable/` - 可写目录
- `app/` - 应用代码
- `vendor/` - Composer依赖

### 3. 上传安全

- 文件类型白名单：`jpg`, `jpeg`, `png`, `gif`, `pdf`
- 文件大小限制：5MB
- 文件名随机化

### 4. SQL注入防护

所有数据库操作使用 CodeIgniter Query Builder，自动转义。

### 5. XSS防护

所有输出使用 `htmlspecialchars()` 转义。

### 6. CSRF保护

CodeIgniter内置CSRF Token保护已启用。

### 7. JWT安全

- Token有效期：7天
- 密钥长度：32字符以上
- 使用HS256算法

---

## 故障排除

### 常见问题

#### 1. 页面空白或500错误

```bash
# 检查日志
tail -f writable/logs/log-$(date +%Y-%m-%d).log

# 检查PHP错误
php -i | grep error_log
```

#### 2. 数据库连接失败

- 检查 `.env` 数据库配置
- 确认数据库服务运行：`systemctl status mysqld`
- 测试连接：`mysql -u username -p database`

#### 3. 二维码生成失败

```bash
# 检查GD扩展
php -m | grep gd

# 检查目录权限
ls -la public/uploads/qrcodes/
```

#### 4. 404错误

- 确认Apache `mod_rewrite` 已启用
- 确认 `.htaccess` 文件存在
- 检查虚拟主机配置 `AllowOverride All`

#### 5. 权限问题

```bash
# 重置权限
chown -R apache:apache /var/www/tcm-trace
chmod -R 755 /var/www/tcm-trace
chmod -R 777 /var/www/tcm-trace/writable
chmod -R 777 /var/www/tcm-trace/public/uploads
```

---

## 访问地址

| 页面 | URL |
|------|-----|
| 管理后台登录 | `https://your-domain.com/admin/login.html` |
| API基础路径 | `https://your-domain.com/api/` |
| 消费者验证页 | `https://your-domain.com/scan/{qr_serial}` |

### 默认账号

首次部署后，通过API注册管理员账号，或在数据库中手动创建。

---

## 技术支持

- GitHub仓库：https://github.com/qishu-qiu/tcm-trace
- 问题反馈：https://github.com/qishu-qiu/tcm-trace/issues

---

**版本**: v2.0
**更新日期**: 2026-04-30
**兼容MySQL版本**: 5.7+
