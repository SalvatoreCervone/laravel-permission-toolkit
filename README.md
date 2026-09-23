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

---

## 📋 Requirements

- **PHP**: `^8.2`
- **Laravel**: `^10.0 | ^11.0 | ^12.0`
- **Spatie Laravel Permission**: `^5.0 | ^6.0`

---

## 📦 Installation

### 1. Require the package via Composer

```bash
composer require salvatorecervone/laravel-permission-toolkit
```

### 2. Publish Configuration & (Optional) Audit Migration

```bash
# Publish configuration
php artisan vendor:publish --tag="permission-toolkit-config"

# (Optional) Publish audit logs migration for security history
php artisan vendor:publish --tag="permission-toolkit-migrations"
php artisan migrate
```

---

## 🌐 Web Panel Navigation

Protected by your standard `['web', 'auth']` middleware by default (configurable in `config/permission-toolkit.php`):

```text
https://your-app.test/permission-manager
```

- **Matrice Ruoli**: `/permission-manager/matrix` (toggle asincrono + creazione rapida di ruoli e permessi)
- **Gestione Utenti**: `/permission-manager/users` (elenco utenti e form assegnazione ruoli/permessi)
- **Diagnostic Simulator**: `/permission-manager/simulator` (test interattivo di autorizzazione)
- **Audit Trail**: `/permission-manager/audit-logs` (registro di sicurezza)
- **Integrity Doctor**: `/permission-manager/doctor` (diagnostica database)

---

## 📄 License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
