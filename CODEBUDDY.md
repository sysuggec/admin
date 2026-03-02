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

# Run development server
php artisan serve

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

# Run development server (port 3000, proxies /api to backend at :8000)
npm run dev

# Build for production
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
│   ├── user.ts
│   ├── role.ts
│   ├── permission.ts
│   └── operation-log.ts
├── stores/
│   └── user.ts             # Pinia store for auth state
├── router/
│   └── index.ts            # Vue Router with permission guards
├── views/
│   ├── login/
│   ├── dashboard/
│   ├── system/
│   │   ├── user/
│   │   ├── role/
│   │   ├── permission/
│   │   └── log/
│   └── error/
├── layouts/
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

## Default Credentials

After running `./scripts/init-database.sh`:
- Username: `admin`
- Password: `Admin@123456`
