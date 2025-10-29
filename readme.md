# 💡 Eye Light Opticals - Invoice Management Web Application

A modern web-based **Invoice Management System** built for **Eye Light Opticals** to simplify customer billing, track invoices, and manage user operations securely.  
This application offers a clean UI, token-based authentication (JWT), and automatic request/response logging for complete operational transparency.

---

## 🚀 Features

- 🧾 **Invoice Management**
  - Create, update, delete, and view invoices
  - Auto-generate unique invoice IDs
- 👥 **User Management**
  - CRUD operations for users (create, edit, delete, fetch)
  - Role-based access using JWT authentication
- 🔒 **Authentication**
  - Secure login API using JSON Web Tokens
- 📊 **Request & Response Logging**
  - Logs every API request/response with timestamps and user tracking
- 🧰 **Modular Middleware**
  - JWT validation middleware
  - Request-Response logger middleware for analytics
- 🕒 **Timestamp & Database Helpers**
  - Consistent date-time handling across the application
  - Structured database helper for PDO connection

---

## 🧱 Tech Stack

| Category          | Technology Used                          |
| ----------------- | ---------------------------------------- |
| Backend Framework | **Slim 4 (PHP)**                         |
| Database          | **9.1.0 - MySQL Community Server - GPL** |
| Authentication    | **JWT (JSON Web Token)**                 |
| Web Server        | **Apache / Nginx**                       |
| Language          | **PHP 8+**                               |
| Frontend UI       | **HTML, CSS, JS, Bootstrap 5**           |
| Logging           | **Custom ReqRes Logger Middleware**      |

---

## 🗂️ Project Structure

📦 Eye-Light-Opticals/  
│  
├── 📁 app/ # Main application files  
│ ├── 📁 assets/ # Frontend assets  
│ │ ├── 📁 css/ # Stylesheets  
│ │ ├── 📁 icon/ # App icons  
│ │ ├── 📁 img/ # Images  
│ │ ├── 📁 js/ # JavaScript files  
│ │ └── 📁 lib/ # JS/CSS libraries  
│ ├── 📁 backend/ # Slim 4 backend app  
│ │ ├── 📁 public/ # Slim public entry (index.php)  
│ │ ├── 📁 src/ # Core source (Controllers, Middleware, Helpers)  
│ │ ├── 📄 composer.json # Composer dependencies  
│ │ └── 📄 .htaccess  
│ │  
│ ├── 📁 backend/ # Slim 4 backend app  
│ │── 📁 database/ # SQL migrations, seeds, etc.  
│ │── 📁 assets/ # Frontend assets  
│ │ ├── 📁 css/ # Stylesheets  
│ │ ├── 📁 icon/ # App icons  
│ │ ├── 📁 img/ # Images  
│ │ ├── 📁 js/ # JavaScript files  
│ │ └── 📁 lib/ # JS/CSS libraries  
│ │── 📁 logs/ # Request/response logs  
│ │── 📁 public/ # Slim public entry (index.php)  
│ │── 📁 src/ # Core source (Controllers, Middleware, Helpers)  
│ │── 📁 storage/ # Local storage / cache  
│ └── 📁 uploads/ # Uploaded invoices or images  
│  
├── 📄 .env # Environment variables  
├── 📄 composer.json # Composer dependencies  
├── 📄 composer.lock  
├── 📄 php.ini  
├── 📄 .gitignore  
├── 📄 .htaccess  
├── 📄 .user.ini  
│  
├── 📄 common.php # Shared PHP utilities  
├── 📄 dashboard.php # Main dashboard page  
├── 📄 footer.php # Common footer  
├── 📄 header.php # Common header/navigation  
├── 📄 index.php # Entry page  
├── 📄 invoices.php # Invoice management page  
├── 📄 login.php # User login page  
├── 📄 logout.php # Logout handler  
└── 📄 users.php # User management page

# 🔧 NGINX CONFIGURATION — PHP-FPM & CACHE CONTROL SETTINGS

🧩 Purpose:
This configuration is designed for PHP-based web applications
(such as Laravel or Slim) running on Nginx with PHP-FPM.  
It ensures:

- Proper routing of PHP requests via PHP-FPM
- Strict cache disabling for dynamic content
- Prevention of outdated or cached data being served
- Security hardening by denying access to hidden files

⚙️ Key Components:

## 1️⃣ Cache-Control Headers

The following headers ensure the browser and any intermediate
proxies do not cache responses. This is especially useful for
development, dashboards, or admin panels where fresh data is
always required.

```bash
  add_header Cache-Control 'no-store, no-cache, must-revalidate, proxy-revalidate, max-age=0' always;
  add_header Pragma 'no-cache' always;
  add_header Expires '0' always;
```

These directives:

- "no-store" prevents storing any part of the response.
- "no-cache" ensures validation before reuse.
- "must-revalidate" and "proxy-revalidate" enforce cache rules.
- "max-age=0" makes cached content immediately stale.

## 2️⃣ PHP-FPM Configuration

This section routes PHP requests to PHP-FPM (FastCGI Process Manager),
which executes PHP scripts and returns the output to Nginx.

```bash
  location ~ \.php$ {
      include fastcgi_params;
      fastcgi_pass unix:/run/php/php8.3-fpm.sock;  Update socket path if needed
      fastcgi_index index.php;
      fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
  }
```

Adjust PHP-FPM socket or version as per your system setup.

## 3️⃣ Root Request Handling

Ensures that Nginx looks for files or directories first, and if not found,
attempts to serve the corresponding PHP file.

```bash
  location / {
      if_modified_since off;
      expires off;
      etag off;
      try_files $uri $uri/ /$uri.php?$args;
  }
```

This disables caching and helps dynamic frameworks handle routing properly.

## 4️⃣ Public Directory Routing

Handles specific application directories (e.g., `/app/backend/public/`
or `/backend/public/`) with strict cache control and fallback to `index.php`.

```bash
  location /app/backend/public/ {
      add_header Cache-Control 'no-store, no-cache, must-revalidate, proxy-revalidate, max-age=0' always;
      add_header Pragma 'no-cache' always;
      add_header Expires '0' always;
      if_modified_since off;
      expires off;
      etag off;
      try_files $uri $uri/ /app/backend/public/index.php?$query_string;
  }
```

Similar logic is applied for `/backend/public/` routes.

## 5️⃣ Security: Block Hidden Files

Denies access to `.htaccess` and similar hidden files that might
expose configuration details if uploaded accidentally.

```bash
  location ~ /\.ht {
      deny all;
  }
```

🧱 Summary:
This setup ensures:  
 ✅ No caching issues for dynamic PHP apps  
 ✅ Proper PHP-FPM integration  
 ✅ Secure, clean URL handling  
 ✅ Restricted access to sensitive files

_Recommended for:_ Development, testing, or dynamic production apps
where fresh data and correct routing are critical.

## 🧾 Project Information

- **Name:** Eye Light Opticals - Invoice Management Web Application
- **Version:** 1.0
- **Author:** Abishaek Duresh
- **Language:** HTML, JavaScript, CSS, PHP
- **Library:** Bootstrap 5, Sweetalert2, Tabular JS, Font Awsome
