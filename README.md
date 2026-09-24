# 🛡️ Laravel Permission Toolkit — Spatie Supercharger

[![Latest Version on Packagist](https://img.shields.io/packagist/v/salvatorecervone/laravel-permission-toolkit.svg?style=flat-square)](https://packagist.org/packages/salvatorecervone/laravel-permission-toolkit)
[![Total Downloads](https://img.shields.io/packagist/dt/salvatorecervone/laravel-permission-toolkit.svg?style=flat-square)](https://packagist.org/packages/salvatorecervone/laravel-permission-toolkit)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![PHP Version](https://img.shields.io/badge/php-%5E8.2-blue.svg?style=flat-square)](https://php.net)
[![Laravel Version](https://img.shields.io/badge/laravel-10.x%20%7C%2011.x%20%7C%2012.x-red.svg?style=flat-square)](https://laravel.com)

**Supercharge your existing `spatie/laravel-permission` setup** with an AWS IAM-style Diagnostic Simulator, an interactive standalone Role-Permission Matrix, a full User Access Manager, a Compliance Audit Trail, and a Database Integrity Doctor.

---

## 💡 Why this package?

`spatie/laravel-permission` is the undisputed industry standard for Laravel RBAC. However, in production applications, developers and security leads constantly hit five major limitations:

1. **The "403 Black Box":** When a user gets HTTP 403 Forbidden, debugging *why* is painful. Spatie doesn't explain if it was a missing role, direct permission, policy check, or guard mismatch.
2. **Missing Standalone UI:** Spatie provides no visual management panel unless you install an entire admin framework like Filament.
3. **No Direct User Access UI:** Assigning or reviewing roles and permissions for individual users requires manual Tinker commands or building custom admin forms.
4. **No Compliance Audit Trail:** Spatie doesn't record *who* assigned or revoked a role/permission, when, or from which IP.
5. **Data Drift & Integrity Anomalies:** Orphaned pivot records, unused permissions, and guard mismatches quietly pile up over years of development.

**Laravel Permission Toolkit solves all of this without changing your database schema.** It runs 100% seamlessly on top of your existing Spatie tables.

---

## ✨ Features

- 🔍 **AWS IAM-Style Diagnostic Simulator (`permission:simulate` & Web UI)**  
  Simulate and trace step-by-step why an authorization passed or failed (User identity ➔ Super Admin bypass ➔ Direct permissions ➔ Role inheritance ➔ Laravel Policy check).
- 🔲 **Interactive Role-Permission Matrix (`/permission-manager/matrix`)**  
  Spreadsheet-style pivot matrix with real-time AJAX toggling, inline role/permission creation & deletion, and automatic Spatie cache invalidation.
- 👥 **User Access Management (`/permission-manager/users`)**  
  List users with live search, inspect their assigned roles, and assign/revoke roles and direct permissions with one click.
- 📜 **Security & Compliance Audit Trail (`/permission-manager/audit-logs`)**  
  Immutable activity log recording who created, deleted, assigned, or revoked roles and permissions with actor, target user, IP address, and timestamp.
- 🩺 **Integrity Doctor (`permission:doctor` & `/permission-manager/doctor`)**  
  Scans your database for orphaned pivot records, empty roles, unused permissions, and Web vs API guard mismatches.
- 💾 **JSON Export & Import (`permission:export` / `permission:import`)**  
  Effortlessly sync role-permission definitions between Local, Staging, and Production environments without manual DB dumps.
- 🌍 **Bilingual Support (Italian 🇮🇹 & English 🇬🇧)**  
  Built-in full localization with a 1-click language switcher in the web panel header, publishable translation files (`permission-toolkit-translations`), and `.env` / session support.
- 🚀 **Interactive Local Demo (Orchestra Workbench)**  
  Pre-packaged demo with realistic seeders, demo users, roles, and audit trail ready to launch in 1 command.

---

## 🚀 Try the Live Demo (Workbench)

To preview and test the complete visual panel locally:

```bash
git clone https://github.com/SalvatoreCervone/laravel-permission-toolkit.git
cd laravel-permission-toolkit
composer install
composer run serve
```

Open your browser at:
```text
http://127.0.0.1:8000/permission-manager
```

### Pre-seeded Demo Data:
- **Mario Rossi**: `admin@demo.test` (Role: `super-admin`)
- **Laura Bianchi**: `manager@demo.test` (Role: `manager`)
- **Giuseppe Verdi**: `accountant@demo.test` (Role: `accountant` + Direct Permission: `reports.special-audit`)
- **Anna Neri**: `viewer@demo.test` (Role: `viewer`)

---

## 📦 Installation in Your Application

### 1. Require the package via Composer

```bash
composer require salvatorecervone/laravel-permission-toolkit
```

### 2. Publish Configuration, Translations & (Optional) Audit Migration

```bash
# Publish configuration
php artisan vendor:publish --tag="permission-toolkit-config"

# (Optional) Publish translation language files (Italian & English)
php artisan vendor:publish --tag="permission-toolkit-translations"

# (Optional) Publish audit logs migration for security history
php artisan vendor:publish --tag="permission-toolkit-migrations"
php artisan migrate
```

---

## 🔐 Security & Access Control (Production-Ready)

By default in **local** and **testing** environments, any authenticated user can view the toolkit. In **production**, access is strictly forbidden unless authorized via a Gate or custom callback (matching Laravel Horizon/Telescope conventions):

### Option A: Define the Gate in `AuthServiceProvider`
```php
use Illuminate\Support\Facades\Gate;

Gate::define('viewPermissionToolkit', function ($user) {
    return $user->hasRole('super-admin');
});
```

### Option B: Use the `PermissionToolkit::auth` Callback
```php
use SalvatoreCervone\PermissionToolkit\PermissionToolkit;

PermissionToolkit::auth(function ($request) {
    return $request->user()?->can('manage-permissions');
});
```

### 🛡️ Built-in Guardrails:
- **Super Admin Protection**: Deleting roles configured as `super_admin` (`super-admin` / `Super Admin`) is strictly prohibited and returns `403 Forbidden`.
- **Self-Lockout Prevention**: Authenticated users cannot delete a role they are currently assigned to.
- **Multi-Guard Mismatch Guard**: Toggling permissions with mismatched guards (`web` vs `api`) is validated before Spatie throws an exception.

---

## 🔲 Embeddable Blade Component

Want to embed the permission matrix directly inside your custom dashboard without using the standalone layout?

```blade
{{-- In any Blade view: --}}
<x-permission-toolkit-matrix />

{{-- Or filter by specific guard or module: --}}
<x-permission-toolkit-matrix guard="web" module="Invoices" />
```

---

## 📢 Domain Events & Webhook Integrations

The package dispatches real-time domain events for all state changes, allowing you to easily trigger webhooks, log to SIEM systems, or notify administrators:

| Event | Dispatched When | Payload |
|---|---|---|
| `SalvatoreCervone\PermissionToolkit\Events\PermissionToggled` | Single or bulk matrix toggle | `$role`, `$permission`, `$action` (`assigned`\|`revoked`), `$causer` |
| `SalvatoreCervone\PermissionToolkit\Events\UserAccessUpdated` | User roles/permissions updated | `$user`, `$addedRoles`, `$removedRoles`, `$addedPerms`, `$removedPerms` |
| `SalvatoreCervone\PermissionToolkit\Events\RoleCreated` | New role created | `$role` |
| `SalvatoreCervone\PermissionToolkit\Events\RoleDeleted` | Role deleted | `$roleName`, `$roleId` |
| `SalvatoreCervone\PermissionToolkit\Events\PermissionCreated` | New permission created | `$permission` |
| `SalvatoreCervone\PermissionToolkit\Events\PermissionDeleted` | Permission deleted | `$permissionName`, `$permissionId` |
| `SalvatoreCervone\PermissionToolkit\Events\PermissionsExported` | Permissions exported to JSON | `$rolesCount`, `$permissionsCount` |
| `SalvatoreCervone\PermissionToolkit\Events\PermissionsImported` | Permissions imported from JSON | `$rolesCount`, `$permissionsCount`, `$fresh` |
| `SalvatoreCervone\PermissionToolkit\Events\AuditLogsPruned` | Audit logs cleaned | `$deletedCount`, `$days` |

Example listener in your application:
```php
use SalvatoreCervone\PermissionToolkit\Events\PermissionToggled;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

Event::listen(PermissionToggled::class, function (PermissionToggled $event) {
    Log::notice("Permission [{$event->permission->name}] {$event->action} for role [{$event->role->name}]");
});
```

---

## 🌐 Web Panel Navigation & Features

Protected by `['web', 'auth', Authorize::class]` middleware:

```text
https://your-app.test/permission-manager
```

- **Matrice Ruoli**: `/permission-manager/matrix`
  - **Bulk Actions**: One-click mass assignment (`✓`) and revocation (`✕`) per role across entire modules.
  - **Module Filter**: Group permissions by feature (`Users`, `Billing`, `Settings`) and filter quickly.
  - **1-Click Web Export & Import**: Download and upload role/permission JSON packages directly from the UI.
- **Gestione Utenti**: `/permission-manager/users` (supporto per ID numerici, UUID e ULID, ricerca e reset password controllato)
- **Diagnostic Simulator**: `/permission-manager/simulator` (test interattivo con supporto per Gate globali, Policy e Spatie Teams)
- **Audit Trail**: `/permission-manager/audit-logs` (registro di conformità transazionale)
- **Integrity Doctor**: `/permission-manager/doctor` (diagnostica senza query N+1)
- **🌓 Dark / Light Mode**: Seamless theme toggle in the header with persistent state.

---

## 🛠️ CLI Commands

```bash
# Diagnostic Simulator (AWS IAM style with Teams support)
php artisan permission:simulate 42 "invoices.create"
php artisan permission:simulate mario@demo.test "update" --model="App\Models\Invoice" --id=15 --team=3

# Database Health Check (Optimized set-based queries)
php artisan permission:doctor

# Audit Trail Pruning (Pass --days=0 to keep indefinitely)
php artisan permission:audit-prune
php artisan permission:audit-prune --days=30

# Sync across environments (JSON Export & Import)
php artisan permission:export --file=permissions.json
php artisan permission:import --file=permissions.json --fresh
```

---

## 📄 License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
