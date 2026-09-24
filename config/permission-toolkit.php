<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Web Panel Route Prefix & Middleware
    |--------------------------------------------------------------------------
    |
    | Define the base URI path and middleware group used to protect the
    | visual Permission Manager panel (Matrix, Simulator, Audit Logs, Doctor).
    |
    */
    'prefix' => env('PERMISSION_TOOLKIT_PREFIX', 'permission-manager'),

    'middleware' => ['web', 'auth'],

    /*
    |--------------------------------------------------------------------------
    | Authorization Gate
    |--------------------------------------------------------------------------
    |
    | The Gate ability used to authorize viewing the Permission Toolkit panel.
    | By default in production, access is denied unless this gate is explicitly
    | defined or PermissionToolkit::auth(Closure) is configured.
    |
    */
    'gate' => env('PERMISSION_TOOLKIT_GATE', 'viewPermissionToolkit'),

    /*
    |--------------------------------------------------------------------------
    | Localization / Language
    |--------------------------------------------------------------------------
    |
    | Default language for the Permission Toolkit web interface ('it' or 'en').
    | If set to null, it will default to the current application locale.
    | Users can also toggle the language in the top navbar.
    |
    */
    'locale' => env('PERMISSION_TOOLKIT_LOCALE', 'it'),

    /*
    |--------------------------------------------------------------------------
    | User Model
    |--------------------------------------------------------------------------
    |
    | The Eloquent model representing the authenticatable user in your application.
    | Defaults to the model configured in your auth.providers.users.model.
    |
    */
    'user_model' => env('AUTH_MODEL', 'App\\Models\\User'),

    /*
    |--------------------------------------------------------------------------
    | Super Admin Configuration
    |--------------------------------------------------------------------------
    |
    | When enabled, users with this role will be recognized by the simulator
    | as having implicit access to all abilities (matching Gate::before conventions).
    | Supports a string ('Super Admin' or 'super-admin') or an array of roles.
    |
    */
    'super_admin' => [
        'enabled' => env('PERMISSION_TOOLKIT_SUPER_ADMIN_ENABLED', true),
        'role_name' => env('PERMISSION_TOOLKIT_SUPER_ADMIN_ROLE', 'super-admin'),
    ],

    /*
    |--------------------------------------------------------------------------
    | User Password Reset & Security Date Field
    |--------------------------------------------------------------------------
    |
    | Allows admins to reset user passwords from the panel and optionally
    | set a timestamp / date column on the user model (e.g. 'password_reset',
    | 'password_expires_at', 'expires_at', etc.).
    | Defaults to null (no date column will be modified unless configured).
    |
    */
    'password_reset' => [
        'enabled' => env('PERMISSION_TOOLKIT_PASSWORD_RESET_ENABLED', true),
        'date_field' => env('PERMISSION_TOOLKIT_PASSWORD_DATE_FIELD', null),
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit Trail & Retention Policy
    |--------------------------------------------------------------------------
    |
    | Automatically records every role/permission assignment and revocation,
    | tracking actor, target user, IP address, and timestamp.
    |
    | 'retention_days': Number of days to keep logs before pruning.
    | Set to `null` or `0` if you NEVER want logs to be deleted (indefinite retention).
    |
    */
    'audit' => [
        'enabled' => env('PERMISSION_TOOLKIT_AUDIT_ENABLED', true),
        'table' => 'permission_audit_logs',
        'retention_days' => env('PERMISSION_TOOLKIT_AUDIT_RETENTION_DAYS', 90),
    ],

    /*
    |--------------------------------------------------------------------------
    | Matrix Display Settings
    |--------------------------------------------------------------------------
    |
    | Settings for the interactive Role-Permission Matrix.
    | 'group_separator' allows automatic grouping of permissions by prefix
    | (e.g. 'invoices.create' and 'invoices.edit' grouped under 'invoices').
    |
    */
    'matrix' => [
        'group_separator' => '.',
        'per_page' => 100,
    ],

    /*
    |--------------------------------------------------------------------------
    | Integrity Doctor
    |--------------------------------------------------------------------------
    |
    | Rules and exclusions for the 'permission:doctor' health check command.
    |
    */
    'doctor' => [
        'ignore_roles' => [],
        'ignore_permissions' => [],
    ],
];
