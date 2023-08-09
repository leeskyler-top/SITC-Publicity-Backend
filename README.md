# 上海信息技术学校 团委学生会 宣传部 管理系统 后端git仓库

主要技术栈：
PHP 8.2.3 + Laravel 10

## 部署与项目更新

### 环境要求
Ubuntu 20.04 或 CentOS 8 或 Windows Server 2019

Nginx或Apache

composer 2.5.5

PHP 8.2.3 以及以下PHP拓展:

- Ctype PHP 扩展
- cURL PHP 扩展
- DOM PHP 扩展
- Fileinfo PHP 扩展
- Filter PHP 扩展
- Hash PHP 扩展
- Mbstring PHP 扩展
- OpenSSL PHP 扩展
- PCRE PHP 扩展
- PDO PHP 扩展
- Session PHP 扩展
- Tokenizer PHP 扩展
- XML PHP 扩展

### 部署大致步骤

#### 安装环境
以Ubuntu 20.04为例

##### 安装PHP 8.2
在执行任何其他操作之前检查更新并安装它们。
```
sudo apt update && sudo apt -y upgrade
```
在更新后清理APT缓存。
```
sudo apt autoremove
```
重启。
```
reboot
```
在添加php8.2 apt镜像之前，安装一些依赖。
```
sudo apt update
sudo apt install -y lsb-release gnupg2 ca-certificates apt-transport-https software-properties-common wget curl git
```
添加Surý镜像
```
sudo add-apt-repository ppa:ondrej/php
sudo apt update
```
安装PHP8.2
```
sudo apt install php8.2 php8.2-fpm php8.2-curl php8.2-bz2 php8.2-xml php8.2-json php8.2-mysqli php8.2-zip php8.2-fileinfo php8.2-dom php8.2-opcache php8.2-mbstring php8.2-hash
```

##### 安装Nginx
安装Nginx
```
apt install nginx
```

/var/www/html/xxx下目录结构（网站根目录）：
- frontend (前端目录)
- backend (后端目录)

编辑配置（含SSL、前端）
```
vim /etc/nginx/sites-enabled/xxx.conf
```
示例配置
```
server {
    listen 80;
    server_name 域名;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl;
    server_name 域名;
    root 网站根目录目录（不要写API目录或是前端目录）;

    index index.php;

    # SSL证书可以使用acme.sh 实施0成本申请、续期。
    ssl_certificate #公钥地址;
    ssl_certificate_key #私钥地址;

    ssl_protocols TLSv1.2 TLSv1.3;  # 指定主流的 TLS 版本

    location ^~ /backend/public/files/images/ {
        deny all;
    }


    location ^~ /backend/app/ {
        deny all;
    }

    location ^~ /backend/bootstrap/ {
        deny all;
    }

    location ^~ /backend/config/ {
        deny all;
    }

    location ^~ /backend/database/ {
        deny all;
    }

    location ^~ /backend/resource/ {
        deny all;
    }

    location ^~ /backend/routes/ {
        deny all;
    }

    location ^~ /backend/tests/ {
        deny all;
    }

    location ^~ /backend/vendor/ {
        deny all;
    }

    location ^~ /backend/storage/framework/ {
        deny all;
    }

    location ^~ /backend/storage/logs/ {
        deny all;
    }

    location ~ /(artisan|composer\.json|composer\.lock|package\.json|phpunit\.xml|README\.md|vite\.config\.js) {
        deny all;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
    
    location ~ /\.(?!well-known).* {
        deny all;
    }


    # 可选配置，使得一些HTTP状态码返回为JSON格式。
    error_page 403 /403.json;
    location = /403.json {
        default_type application/json;
        return 403 '{"code":"403","status":"error","msg":"拒绝访问 Forbidden."}';
    }

    error_page 404 /404.json;
    location = /404.json {
        default_type application/json;
        return 404 '{"code":"404","status":"error","msg":"未找到 Not Found."}';
    }

    error_page 413 /413.json;
    location = /413.json {
        default_type application/json;
        return 413 '{"code":"413","status":"error","msg":"数据大小受限 Content Too Large."}';
    }

    error_page 429 /429.json;
    location = /429.json {
        default_type application/json;
        return 429 '{"code":"429","status":"error","msg":"请求过多 Too Many Request."}';
    }

    error_page 500 /500.json;
    location = /500.json {
        default_type application/json;
        return 500 '{"code":"500","status":"error","msg":"服务器内部错误 Internal Server Error."}';
    }

    error_page 502 /502.json;
    location = /502.json {
        default_type application/json;
    }

    # 对API目录配置伪静态
    location /backend/ {
        try_files $uri $uri/ /backend/index.php?$query_string;
    }

    # 配置前端目录自动重定向。
    location / {
        root 前端目录;
    }

    # PHP8.2-FPM配置
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # 设置服务器最大支持的上传实体数据
    client_max_body_size 20M;
}

```

##### 安装MySQL 5.7
添加MySQL镜像源
```
sudo apt update
sudo apt install wget -y
wget https://dev.mysql.com/get/mysql-apt-config_0.8.12-1_all.deb
```
运行下方命令后，选择Ubuntu Bionic，选择MySQL5.7，选择By Default，选择OK

```
sudo dpkg -i mysql-apt-config_0.8.12-1_all.deb
```
```
sudo apt-get update
```
```
sudo apt install -f mysql-client=5.7* mysql-community-server=5.7* mysql-server=5.7*
```
初始化配置MySQL，配置数据库、用户密码权限等信息，不做示例。
```
sudo mysql_secure_installation
```

##### 安装Composer 2
注意尽量不要使用apt安装，默认安装的都是1，不是2！
```
cd ~
curl -sS https://getcomposer.org/installer -o /tmp/composer-setup.php
```
获取最新签名。
```
HASH=`curl -sS https://composer.github.io/installer.sig`
```
执行下列PHP代码，确认安装器是完整的，可以运行的，执行下列命令后应返回Installer verified。
```
php -r "if (hash_file('SHA384', '/tmp/composer-setup.php') === '$HASH') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
```
下载并安装composer。
```
php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer
```
查看composer是否安装成功，并查看版本号是否为2开头。
```
composer -V
```

##### 部署后端代码
```
cd #到你想要的网站目录
```
克隆项目，并安装composer依赖
```
git clone https://github.com/leeskyler-top/SITC-Publicity-Backend.git
cd SITC-Publicity-Backend
mv ./* ../
cd ../
rm -rf SITC-Publicity-Backend
mkdir -p ./public/files/images/assigned
mkdir -p ./public/files/images/damaged
mkdir -p ./public/files/images/returned
mkdir -p ./public/files/images/activity
composer install
chown -R www-data:www-data ./*
chmod -R 755 ./*
```
依照env.example配置.env文件
编辑完成后，生成base64 APP_KEY（此步骤也需要联网）
```
php artisan key:genreate
```

***

## 数据迁移、备份
登录服务器后，如果没有安装phpMyAdmin、Adminer、SQLyog(仅Windows系统)等图形化数据库操作界面，可以通过MySQL命令行操作。

##### 备份
```
mysqldump -u 账户 -p 数据库名 > 你想要的路径/文件名.sql
```

##### 迁移
```
mysql -u 账户 -p
CREATE DATABASE sitc_publicity;
quit;
mysql -u 账户 -p sitc_publicity < SQL文件所在路径
```

***

##### 跨域与反代说明
对于信任的Origin，在部署项目后需要修改app/Http/Middleware/CorsMiddleware.php中的allowedDomains数组内容
格式举例：

```
$allowedDomains = [
    'https://example.com',
    'http://localhost:5173',
];
```

如果想要放行所有的Origin，您可以将handle方法中的代码修改为
```
if ($request->is('api/v1/files/*')) {
    return $next($request);
}
return $next($request)
        ->header('Access-Control-Allow-Origin', '*')
        ->header('Access-Control-Allow-Methods', 'GET, POST, PATCH, PUT, DELETE')
        ->header('Access-Control-Allow-Headers', 'Content-Type');
```

想要为服务添加反向代理，请参照laravel官方文档，按需修改文件，如TrustProxies.php等。
***

## 项目API功能 To-do List


### AuthController 鉴权控制器

- [x] login 登录
- [x] logout 登出


### UserController

#### 用户功能
- [x] show 显示自己的信息
- [x] changePwd 修改密码

#### 管理员功能
- [x] index 列出所有人
- [x] show 查找某个设备的信息
- [x] store 添加用户
- [x] update 更新用户信息（含角色）
- [x] destroy 删除用户（软删除）
- [x] resetPwd 重置用户密码
- [x] batchStore 批量添加用户


### EquipmentController 设备控制器

#### 用户功能
- [x] showMyEquipment 显示所有设备，分为不同的状态：
    - 申请中、已归还、申请遭拒、申请延期、已延期、已上报的受损、已上报的丢失
- [x] indexUnassignedEquipments 列出空闲状态的设备
- [x] equipmentApply 设备申请
    - [ ] 完成面向管理员通知广播
- [x] back 归还设备
- [x] delayApply 延期申报
    - [ ] 完成面向管理员通知广播
- [x] reportEquipment 设备异常报告（丢失、损坏）
    - [ ] 完成面向管理员通知广播
#### 管理员功能
- [x] index 列出所有未删除的设备
- [x] store 添加设备
- [x] show 显示某个设备详情
- [x] update 更新设备与设备状态，或手动将设备分配给用户
- [x] destroy 删除某个设备（软删除）
- [x] batchStore 使用csv批量添加设备
- [x] indexApplicationList 列出审批列表
- [x] agreeApplication 同意设备申请
- [x] rejectApplication 拒绝设备申请
- [x] indexDelayApplication 列出待延期申报
- [x] indexAllDelayApplicationByERID 列出此设备申请的所有延期申报（通过租借ID）
- [x] agreeEquipmentDelayApplication 同意延期
- [x] rejectEquipmentDelayApplication 拒绝延期
- [x] indexReports 列出主动上报的设备异常
- [x] indexRentHistory 设备出借历史

### ActivityController 活动控制器

#### 用户功能
- [x] listActivityByType 通过状态列出所有活动信息
- [x] EnrollActivity 报名活动 
  - [ ] 完成面向管理员通知广播

#### 管理员功能
- [x] index 列出所有活动
- [x] show 显示活动具体信息
- [x] store 新增活动 
  - [ ] 完成通知广播，对当场指派的用户发送通知
- [x] update 更新活动信息 
  - [ ] 完成对新增指派用户的通知
- [x] destroy 删除活动（软删除）
  - [ ] 完成将未进行的签到删除
  - [x] removeUser 将某人从某个活动中移出 
    - [ ] 完成同步将用户从未开始的签到组中删除，已开始的不会移除。
    - [ ] 完成通知用户
- [x] searchUserNotInActivityByName 模糊搜索不在某个活动中的用户
- [ ] listCheckIns 列出当前活动所有签到信息
- [x] listEnrollments 列出报名
- [x] AgreeActivityEnrollments 同意报名 
  - [ ] 完成同步将用户添加至正在进行或已开始的签到 
  - [ ] 完成通知用户
- [x] RejectActivityEnrollments 拒绝报名  
  - [x] 完成通知用户


### CheckInController 签到控制器

#### 用户功能
- [x] checkIn 签到

#### 管理员功能
- [x] index 列出所有活动
- [x] store 新增签到
- [x] show 显示签到具体信息
- [x] update 更新签到信息
- [x] destroy 删除活动（软删除）
- [x] revokeCheckInUser 撤销某人在某次签到中的签到行为
  - [ ] 完成通知用户
- [ ] addCheckInUser 为签到组内新增参加活动的人员
  - [ ] 完成通知用户
- [ ] removeCheckInUser 将人员从签到组内移出
  - [ ] 完成通知用户
### MessageController 消息控制器
- [ ] getMyMsg 获取新消息
- [ ] readMsg 已读某个消息
- [ ] readAllMsg 全部已读


***

本项目遵循MIT开源许可。