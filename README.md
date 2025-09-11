# LKYPT_Web
聖公會梁季彜中學攝影隊官方網站 / S.K.H. Leung Kwai Yee Secondary School Photography Team Official Website

## 功能特色 / Features
- 支援繁體中文和英文多語言切換  
	Multilingual: Traditional Chinese & English
- Google OAuth 登入，僅允許學校郵箱和指定郵箱  
	Google OAuth login, only school and specified emails allowed
- 管理員後台，權限校驗  
	Admin dashboard, permission check
- 統計組件（訪問次數、照片數）  
	Statistics (visit count, photo count)
- 完全開源，歡迎貢獻  
	Open source, contributions welcome

## 技術細節 / Technical Details
- 前端：純 HTML / CSS / JavaScript，響應式設計  
	Frontend: Pure HTML / CSS / JavaScript, responsive design
- 後端：PHP 7+，PDO 連接 MySQL  
	Backend: PHP 7+, PDO for MySQL
- 數據庫：MySQL，主要表結構如下：  
	Database: MySQL, main tables:
	- `photos`：存儲照片（url、上傳時間、上傳者）  
		Stores photo info (url, upload time, uploader)
	- `login_logs`：記錄所有登入嘗試（成功/失敗、用戶名、郵箱、IP、原因等）  
		Records all login attempts (success/fail, username, email, IP, reason, etc.)
	- `admin_users`：管理員郵箱列表  
		Admin email list
- 第三方集成：Google OAuth 2.0 登入，自動校驗郵箱後綴  
	Third-party: Google OAuth 2.0 login, auto verify email domain
- 多語言：所有頁面和提示均支援繁體中文和英文，配置於 `config/lang.php`  
	Multilingual: All pages/messages support Traditional Chinese & English, configured in `config/lang.php`
- 權限控制：相冊頁面需登入，管理員後台僅管理員可訪問  
	Permission: Album page requires login, admin dashboard only for admins
- 統計功能：訪問次數本地文件計數，照片數即時讀取數據庫  
	Statistics: Visit count by local file, photo count from database

## 快速部署 / Quick Start
1. 克隆本項目到服務器  
	 Clone this repo to your server
2. 配置 `config/config.php` 數據庫連接信息  
	 Configure `config/config.php` for database
3. 配置 Google OAuth 應用並填寫 Client ID/Secret  
	 Setup Google OAuth and fill in Client ID/Secret
4. 導入數據庫表結構（見下方SQL）  
	 Import database tables (see below)
5. 啟動 PHP 環境，訪問首頁即可  
	 Start PHP environment, visit homepage

## 數據庫建表 SQL / Database Table SQL

```sql
CREATE TABLE photos (
		id INT AUTO_INCREMENT PRIMARY KEY,
		url VARCHAR(512) NOT NULL,
		uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
		uploader VARCHAR(100)
);

CREATE TABLE login_logs (
		id INT AUTO_INCREMENT PRIMARY KEY,
		username VARCHAR(255),
		email VARCHAR(255),
		ip_address VARCHAR(45) NOT NULL,
		login_time DATETIME DEFAULT CURRENT_TIMESTAMP,
		status VARCHAR(20) NOT NULL,
		error_reason VARCHAR(255),
		input_email VARCHAR(255)
);

CREATE TABLE admin_users (
		id INT AUTO_INCREMENT PRIMARY KEY,
		email VARCHAR(255) NOT NULL UNIQUE
);

-- 插入初始管理員
INSERT INTO admin_users (email) VALUES ('YOUR_EMAIL');
```

## 項目地址 / Project URL

[https://github.com/adminlby/LKYPT_Web](https://github.com/adminlby/LKYPT_Web)

## 使用授權 / License

本項目由倉庫所有者（adminlby）擁有所有權利。僅供學術、公益及校內用途。
如需克隆、二次部署或商業用途，請發送郵件至 liub6696@gmail.com 取得授權。

This project is owned by the repository owner (adminlby). For academic, public welfare, and school use only.
For cloning, redeployment, or commercial use, please email liub6696@gmail.com for permission.
