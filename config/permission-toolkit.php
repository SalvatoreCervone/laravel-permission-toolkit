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
    | Users Display & Ordering Configuration
    |--------------------------------------------------------------------------
    |
    | Configure how users are displayed and sorted in the User Management list.
    | Often applications have 'first_name' / 'last_name' or 'cognome' / 'nome'
    | instead of just 'name'.
    |
    | - 'display_columns': Array of column / attribute names on the User model.
    |   Multiple columns are concatenated with 'display_separator' (space by default).
    |   Example: ['cognome', 'nome'] or ['name'] or ['last_name', 'first_name'].
    |
    | - 'display_separator': The string used to join display_columns (default: ' ').
    |
    | - 'order_by': Column(s) used for sorting users. If null (default), users are
    |   automatically ordered by 'display_columns' in the order specified!
    |   Active users are ALWAYS sorted before deactivated/trashed users.
    |   Example: ['cognome' => 'asc', 'nome' => 'asc'] or null
    |
    */
    'users' => [
        'display_columns' => env('PERMISSION_TOOLKIT_USERS_DISPLAY_COLUMNS')
            ? array_map('trim', explode(',', env('PERMISSION_TOOLKIT_USERS_DISPLAY_COLUMNS')))
            : ['name'],
        'display_separator' => ' ',
        'order_by' => null,
    ],

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
    | Diagnostic Simulator
    |--------------------------------------------------------------------------
    |
    | Configuration options for the diagnostic authorization simulator.
    | 'users_limit': Maximum number of users to load in the forward simulator
    | dropdown. By default is null (all users are loaded without truncation).
    |
    */
    'simulator' => [
        'users_limit' => env('PERMISSION_TOOLKIT_SIMULATOR_USERS_LIMIT', null),
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
