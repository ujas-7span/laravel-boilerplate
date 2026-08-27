# 🚀 Laravel 13 REST API Boilerplate

[![PHP Version](https://img.shields.io/badge/PHP-8.3%20%7C%208.4-777BB4?logo=php&logoColor=white)](https://php.net)
[![Laravel](https://img.shields.io/badge/Laravel-13.x-FF2D20?logo=laravel&logoColor=white)](https://laravel.com)
[![PHPStan Level 6](https://img.shields.io/badge/PHPStan-Level%206-2F5C8F?logo=php&logoColor=white)](https://phpstan.org)
[![Pest Tests](https://img.shields.io/badge/Tests-57%20Passed-22c55e?logo=pest&logoColor=white)](https://pestphp.com)
[![OpenAPI 3.1](https://img.shields.io/badge/OpenAPI-3.1-85EA2D?logo=openapiinitiative&logoColor=black)](http://localhost:8000/developer/docs/api)
[![Code Style](https://img.shields.io/badge/Pint-PSR--12-F43F5E)](https://github.com/laravel/pint)

A clean, production-ready **Laravel 13 REST API Boilerplate** with zero N+1 queries, pre-signed cloud uploads, automated OpenAPI 3.1 & TypeScript types, unified developer tools, strict mode safeguards, distributed log tracing, and topic-driven localization.

> 📚 **Complete Architecture & Engineering Skills Playbook**: [docs/ARCHITECTURE_PLAYBOOK.md](docs/ARCHITECTURE_PLAYBOOK.md)

---

## ⚡ Quick Start

```bash
# 1. Clone & install dependencies
git clone https://github.com/your-org/laravel-boilerplate.git
cd laravel-boilerplate
composer run setup

# 2. Start development servers
npm run dev
php artisan serve
```

---

## 🛠️ Developer Suite (`/developer/*`)

Protected by environment credentials (`DEVELOPER_USERNAME` / `DEVELOPER_PASSWORD`):

| Tool | URL | Description |
|---|---|---|
| 📊 **Dashboard** | [`/developer/dashboard`](http://localhost:8000/developer/dashboard) | Architecture overview & system metrics |
| 🔭 **Telescope** | [`/developer/telescope`](http://localhost:8000/developer/telescope) | Request, query, job, and exception inspection |
| 🌅 **Horizon** | [`/developer/horizon`](http://localhost:8000/developer/horizon) | Redis background queue monitoring |
| 📜 **Log Viewer** | [`/developer/log-viewer`](http://localhost:8000/developer/log-viewer) | Real-time application log streaming & search |
| 📖 **API Docs** | [`/developer/docs/api`](http://localhost:8000/developer/docs/api) | Interactive Scramble OpenAPI 3.1 playground |
| 📄 **OpenAPI Spec** | [`/developer/docs/api.json`](http://localhost:8000/developer/docs/api.json) | Raw OpenAPI 3.1 specification JSON |

---

## 🎯 Core Features

### 1. REST API Query Pipeline (`QueryBuilder`)
Declarative filtering, sorting, relation inclusion, and sparse fieldsets out of the box:
```http
GET /api/v1/users?filter[name]=John&sort=-created_at&include=tokens&fields=id,name,email&append=latest_token_name
```
- **Zero N+1 Queries**: Relations required by computed accessors are declared via `#[RequiresRelation('tokens')]` and eager-loaded automatically.
- **Pagination**: Supports standard length-aware (`?page=1&per_page=15`), cursor pagination (`?cursor=...`), and single envelope (`?limit=-1`).

### 2. Pre-Signed Media Uploads (Cloud & Local)
1. Request signed URL: `POST /api/v1/signed-url` `{ "filename": "avatar.jpg", "tag": "profile" }`
2. Direct client PUT upload to S3 / cloud storage (bypasses server bottleneck).
3. Attach to model: `POST /api/v1/users` `{ "name": "...", "profile": { ... } }`
4. Automated orphan pruning: `php artisan media:prune-temp --days=2`

### 3. Production Safeguards & Strict Dev Mode
- **`Model::shouldBeStrict()`**: Throws on lazy-loading, unfillable assignments, and missing attributes in local/testing; relaxed in production.
- **`DB::prohibitDestructiveCommands()`**: Blocks `migrate:fresh`, `migrate:reset`, and `db:wipe` in production.

### 4. Distributed Tracing & Security
- **Request Tracing**: Auto-assigns `X-Request-Id` UUID and binds to `Log::shareContext()`.
- **Security Headers**: `nosniff`, `SAMEORIGIN`, `1; mode=block`, `strict-origin-when-cross-origin`.
- **Health Check**: Liveness probe at `GET /api/v1/health` (checks DB, Cache, Storage).

### 5. Domain-Driven Localization (`lang/en/`)
Topic-based translation structure in root `lang/en/` (`entity.php`, `message.php`, `status.php`, `email.php`, `notification.php`) with dynamic `Accept-Language` / `X-Locale` negotiation.

---

## 💻 Commands & Quality Pipeline

```bash
# Export OpenAPI specification & regenerate TypeScript types
npm run types

# Run test suite
php artisan test

# Run PHPStan static analysis (Level 6)
composer run phpstan

# Format code with Laravel Pint
./vendor/bin/pint

# Run full pre-commit check (Pint + Types + Arch + Pest + PHPStan)
./.husky/pre-commit
```

---

## 📄 License

Open-sourced software licensed under the [MIT License](LICENSE).
