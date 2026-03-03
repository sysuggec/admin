# CODEBUDDY.md

This file provides guidance to CodeBuddy Code when working with code in this repository.

## Project Overview

This is an admin management system with a Laravel 11 backend API and Vue 3 frontend. The system provides user authentication (JWT), role-based access control (RBAC), and operation logging.

## Architecture

### Backend (Laravel 11)
- **Location**: `/workspace/admin-system`
- **PHP Version**: ^8.2
- **Key Packages**: Laravel Framework, JWT Auth (tymon/jwt-auth)

### Frontend (Vue 3 + TypeScript)
- **Location**: `/workspace/admin-system/frontend`
- **Tech Stack**: Vue 3, TypeScript, Vite, Pinia, Vue Router, Element Plus, Axios

## Common Commands

### Backend (run from `/workspace/admin-system`)

```bash
# Install dependencies
composer install

# Initialize project (first time setup)
./scripts/init-project.sh

# Or manually:
cp .env.example .env
php artisan key:generate
php artisan jwt:secret
php artisan migrate --seed

# Run development server (persistent)
nohup php artisan serve --host=0.0.0.0 --port=8000 > /tmp/laravel.log 2>&1 &

# Stop development server
pkill -f "php artisan serve"

# Run tests
php artisan test
# or
./vendor/bin/phpunit

# Run specific test file
php artisan test --filter TestClassName

# Database migrations
php artisan migrate
php artisan migrate:fresh --seed

# Initialize database (fresh install with seed data)
./scripts/init-database.sh

# Clear caches
php artisan cache:clear
php artisan config:clear

# Code style (Laravel Pint)
./vendor/bin/pint
```

### Frontend (run from `/workspace/admin-system/frontend`)

```bash
# Install dependencies
npm install

# Build for production (outputs to ../public/)
npm run build

# Preview production build
npm run preview
```

## Database

- Default: SQLite (`database/database.sqlite`)
- Tables use prefix `t_` (e.g., `t_user`, `t_role`, `t_permission`)
- Pivot tables: `t_user_role`, `t_role_permission`
- Models use SoftDeletes on main entities

## Key Backend Structure

```
app/
├── Http/
│   ├── Controllers/        # API Controllers
│   │   ├── Auth/           # AuthController (login, logout, refresh, me)
│   │   ├── DashboardController  # Dashboard statistics
│   │   ├── UserController
│   │   ├── RoleController
│   │   ├── PermissionController
│   │   └── OperationLogController
│   └── Middleware/
│       ├── PermissionMiddleware.php  # RBAC permission check
│       └── OperationLogMiddleware.php # Auto-log operations
├── Models/
│   ├── User.php            # JWTSubject, has roles, getPermissions()
│   ├── Role.php            # belongsToMany users/permissions
│   ├── Permission.php      # Tree structure support (buildTree)
│   ├── LoginLog.php
│   └── OperationLog.php
└── Providers/

routes/
└── api.php                 # All API routes with permission middleware
```

## Key Frontend Structure

```
frontend/src/
├── api/                    # Axios API modules
│   ├── request.ts          # Axios instance with interceptors
│   ├── auth.ts
│   ├── dashboard.ts        # Dashboard statistics API
│   ├── user.ts
│   ├── role.ts
│   ├── permission.ts
│   ├── operation-log.ts
│   └── login-log.ts
├── stores/
│   └── user.ts             # Pinia store for auth state, menus, permissions
├── router/
│   └── index.ts            # Vue Router with permission guards
├── views/
│   ├── login/
│   ├── dashboard/
│   ├── system/
│   │   ├── user/
│   │   ├── role/
│   │   ├── permission/
│   │   ├── log/            # Operation log
│   │   └── login-log/      # Login log
│   └── error/
├── layouts/
│   └── MainLayout.vue      # Dynamic menu rendering from API
├── components/
├── styles/
└── main.ts
```

## Authentication & Authorization

- **Auth**: JWT tokens via `auth:api` guard
- **Permission Format**: `resource:action` (e.g., `user:list`, `user:create`, `user:edit`, `user:delete`)
- **Super Admin**: Role named `super_admin` bypasses all permission checks
- **Permission Middleware**: Applied via `middleware('permission:resource:action')` in routes
- **Permission Caching**: User permissions cached for 1 hour, cleared via `PermissionMiddleware::clearUserPermissionCache()`

## API Response Format

All API responses follow this structure:
```json
{
  "code": 200,
  "message": "Success message",
  "data": { ... }
}
```

## Deployment

### Production Build (Combined Deployment)

The frontend and backend are deployed together. The frontend builds to Laravel's `public/` directory.

**Quick Start:**
```bash
# From project root (/workspace/admin-system)

# 1. Install dependencies
composer install
cd frontend && npm install && cd ..

# 2. Build frontend (outputs to public/)
cd frontend && npm run build && cd ..

# 3. Start server
nohup php artisan serve --host=0.0.0.0 --port=8000 > /tmp/laravel.log 2>&1 &
```

**Full Production Build:**
```bash
# From project root (/workspace/admin-system)

# 1. Install backend dependencies
composer install --optimize-autoloader --no-dev

# 2. Install frontend dependencies
cd frontend && npm install && cd ..

# 3. Build frontend (outputs to public/)
cd frontend && npm run build && cd ..

# 4. Optimize Laravel
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Nginx Configuration

Use the provided `nginx.conf.example` as a reference. Key points:

- Static files (JS/CSS) are served directly by Nginx
- API requests (`/api/*`) are forwarded to PHP-FPM
- SPA fallback: all non-file requests return `index.php` (Vue Router handles routing)

## Dynamic Menu System

The navigation menu is dynamically rendered based on user permissions:

- **API**: `/api/auth/me` returns user's `menus` array with hierarchical structure
- **Store**: `userStore.menus` computed property in `stores/user.ts`
- **Rendering**: `MainLayout.vue` iterates over `userStore.menus` to render menu items
- **Icons**: Mapped via `iconMap` in `MainLayout.vue` (Setting, User, UserFilled, Lock, Document, Odometer, Postcard)

Menu data structure:
```json
{
  "id": 1,
  "name": "system",
  "display_name": "系统管理",
  "icon": "Setting",
  "path": "/system",
  "children": [...]
}
```

To add a new menu item:
1. Add permission in database (type: `menu`)
2. Assign to role
3. Add route in `router/index.ts`
4. Add icon to `iconMap` in `MainLayout.vue` if using new icon

## Default Credentials

After running `./scripts/init-database.sh`:
- Username: `admin`
- Password: `Admin@123456`
