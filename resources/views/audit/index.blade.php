@extends('permission-toolkit::layouts.app')

@section('title', __('permission-toolkit::messages.audit_title'))

@section('content')
<div class="card">
    <div class="card-header">
        <div>
            <h1 class="card-title">{{ __('permission-toolkit::messages.audit_title') }}</h1>
            <p style="color: var(--text-muted); font-size: 0.85rem; margin-top: 0.25rem;">
                {{ __('permission-toolkit::messages.audit_subtitle') }}
            </p>
        </div>
    </div>

    @if(! $tableExists)
        <div style="background: rgba(245, 158, 11, 0.1); border: 1px solid var(--warning); padding: 1.25rem; border-radius: 0.375rem; margin-bottom: 1.5rem;">
            <h3 style="color: var(--warning); font-size: 1rem; margin-bottom: 0.5rem;">{{ __('permission-toolkit::messages.audit_migration_title') }}</h3>
            <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 0.75rem;">
                {{ __('permission-toolkit::messages.audit_migration_desc') }}
            </p>
            <code style="background: #000; padding: 0.4rem 0.8rem; border-radius: 0.25rem; font-size: 0.8rem; display: block; width: fit-content;">
                php artisan vendor:publish --tag=permission-toolkit-migrations && php artisan migrate
            </code>
        </div>
    @else
        <form method="GET" action="{{ route('permission-toolkit.audit') }}" style="display: flex; gap: 1rem; margin-bottom: 1.5rem; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 200px;">
                <input type="text" name="search" class="input-control" value="{{ request('search') }}" placeholder="{{ __('permission-toolkit::messages.audit_search_placeholder') }}">
            </div>
            <div style="width: 160px;">
                <select name="action" class="input-control">
                    <option value="">{{ __('permission-toolkit::messages.audit_all_actions') }}</option>
                    <option value="assigned" {{ request('action') == 'assigned' ? 'selected' : '' }}>{{ __('permission-toolkit::messages.audit_action_assigned') }}</option>
                    <option value="revoked" {{ request('action') == 'revoked' ? 'selected' : '' }}>{{ __('permission-toolkit::messages.audit_action_revoked') }}</option>
                </select>
            </div>
            <div style="width: 160px;">
                <select name="type" class="input-control">
                    <option value="">{{ __('permission-toolkit::messages.audit_all_types') }}</option>
                    <option value="role" {{ request('type') == 'role' ? 'selected' : '' }}>{{ __('permission-toolkit::messages.audit_type_role') }}</option>
                    <option value="permission" {{ request('type') == 'permission' ? 'selected' : '' }}>{{ __('permission-toolkit::messages.audit_type_perm') }}</option>
                    <option value="role_permission" {{ request('type') == 'role_permission' ? 'selected' : '' }}>{{ __('permission-toolkit::messages.audit_type_role_perm') }}</option>
                </select>
            </div>
            <button type="submit" class="btn">{{ __('permission-toolkit::messages.audit_btn_filter') }}</button>
            @if(request()->hasAny(['search', 'action', 'type']))
                <a href="{{ route('permission-toolkit.audit') }}" class="btn" style="background: var(--border);">{{ __('permission-toolkit::messages.audit_btn_reset') }}</a>
            @endif
        </form>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>{{ __('permission-toolkit::messages.audit_th_datetime') }}</th>
                        <th>{{ __('permission-toolkit::messages.audit_th_causer') }}</th>
                        <th>{{ __('permission-toolkit::messages.audit_th_action') }}</th>
                        <th>{{ __('permission-toolkit::messages.audit_th_type') }}</th>
                        <th>{{ __('permission-toolkit::messages.audit_th_target') }}</th>
                        <th>{{ __('permission-toolkit::messages.audit_th_user') }}</th>
                        <th>{{ __('permission-toolkit::messages.audit_th_ip') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td style="color: var(--text-muted); font-size: 0.8rem;">
                                {{ $log->created_at->format('Y-m-d H:i:s') }}
                            </td>
                            <td>
                                @if($log->causer)
                                    <strong>{{ $log->causer->name ?? $log->causer->email ?? 'User #' . $log->causer_id }}</strong>
                                @else
                                    <span style="color: var(--text-muted);">{{ __('permission-toolkit::messages.audit_system_cli') }}</span>
                                @endif
                            </td>
                            <td>
                                @if($log->action === 'assigned')
                                    <span class="badge badge-success">Assigned</span>
                                @else
                                    <span class="badge badge-danger">Revoked</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge badge-info">{{ $log->type }}</span>
                            </td>
                            <td style="font-family: monospace; font-size: 0.85rem;">
                                {{ $log->target_name }}
                            </td>
                            <td>
                                @if($log->user)
                                    {{ $log->user->name ?? $log->user->email ?? 'User #' . $log->user_id }}
                                @else
                                    <span style="color: var(--text-muted);">-</span>
                                @endif
                            </td>
                            <td style="color: var(--text-muted); font-size: 0.8rem;">
                                {{ $log->ip_address ?? 'CLI' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 2rem;">
                                {{ __('permission-toolkit::messages.audit_empty') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(method_exists($logs, 'links'))
            <div style="margin-top: 1.5rem;">
                {{ $logs->links() }}
            </div>
        @endif
    @endif
</div>
@endsection
