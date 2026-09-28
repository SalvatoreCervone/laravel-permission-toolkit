@extends('permission-toolkit::layouts.app')

@section('title', __('permission-toolkit::messages.users_title'))

@section('content')
@if(session('status'))
    <div style="background: rgba(16, 185, 129, 0.15); border: 1px solid #10b981; color: #6ee7b7; padding: 0.75rem 1.25rem; border-radius: 0.375rem; margin-bottom: 1.25rem; font-size: 0.9rem;">
        ✔ {{ session('status') }}
    </div>
@endif

@if(session('error'))
    <div style="background: rgba(239, 68, 68, 0.15); border: 1px solid #ef4444; color: #fca5a5; padding: 0.75rem 1.25rem; border-radius: 0.375rem; margin-bottom: 1.25rem; font-size: 0.9rem;">
        ⚠️ {{ session('error') }}
    </div>
@endif

<div class="card">
    <div class="card-header" style="flex-wrap: wrap; gap: 1rem;">
        <div>
            <h1 class="card-title">{{ __('permission-toolkit::messages.users_heading') }}</h1>
            <p style="color: var(--text-muted); font-size: 0.85rem; margin-top: 0.25rem;">
                {{ __('permission-toolkit::messages.users_subtitle') }}
            </p>
        </div>

        <!-- Filter & Search Toolbar -->
        <form method="GET" action="{{ route('permission-toolkit.users.index') }}" style="display: flex; gap: 0.5rem; flex-wrap: wrap; align-items: center;">
            <!-- Text Search -->
            <input 
                type="text" 
                name="search" 
                class="input-control" 
                value="{{ request('search') }}" 
                placeholder="{{ __('permission-toolkit::messages.users_search_placeholder') }}"
                style="min-width: 200px; width: auto;"
            >

            <!-- Role Filter -->
            <select name="role" class="input-control" style="width: auto;">
                <option value="">-- {{ __('permission-toolkit::messages.users_filter_role_all') }} --</option>
                @foreach($roles as $r)
                    <option value="{{ $r->name }}" {{ ($selectedRole === $r->name || $selectedRole == $r->id) ? 'selected' : '' }}>
                        {{ $r->name }}
                    </option>
                @endforeach
            </select>

            <!-- Permission Filter -->
            <select name="permission" class="input-control" style="width: auto;">
                <option value="">-- {{ __('permission-toolkit::messages.users_filter_perm_all') }} --</option>
                @foreach($permissions as $p)
                    <option value="{{ $p->name }}" {{ ($selectedPermission === $p->name || $selectedPermission == $p->id) ? 'selected' : '' }}>
                        {{ $p->name }}
                    </option>
                @endforeach
            </select>

            <!-- Soft-Delete Status Filter -->
            @if($supportsSoftDeletes)
                <select name="status" class="input-control" style="width: auto;">
                    <option value="all" {{ ($statusFilter ?? 'all') === 'all' ? 'selected' : '' }}>{{ __('permission-toolkit::messages.users_filter_status_all') }}</option>
                    <option value="active" {{ ($statusFilter ?? '') === 'active' ? 'selected' : '' }}>{{ __('permission-toolkit::messages.users_filter_status_active') }}</option>
                    <option value="trashed" {{ ($statusFilter ?? '') === 'trashed' ? 'selected' : '' }}>{{ __('permission-toolkit::messages.users_filter_status_trashed') }}</option>
                </select>
            @endif

            <button type="submit" class="btn">{{ __('permission-toolkit::messages.btn_filter') }}</button>

            @if(request('search') || request('role') || request('permission') || (request('status') && request('status') !== 'all'))
                <a href="{{ route('permission-toolkit.users.index') }}" class="btn" style="background: var(--border);" title="{{ __('permission-toolkit::messages.btn_reset') }}">✕ {{ __('permission-toolkit::messages.btn_reset') }}</a>
            @endif
        </form>
    </div>

    <!-- Active Filters Feedback Banner -->
    @if($selectedRole || $selectedPermission || request('search') || (isset($statusFilter) && $statusFilter !== 'all'))
        <div style="background: var(--bg-card-hover); border: 1px solid var(--border); border-radius: 0.375rem; padding: 0.6rem 1rem; margin-bottom: 1.25rem; font-size: 0.85rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem;">
            <div style="display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
                <span style="font-weight: 600; color: var(--accent-heading);">🔍 {{ __('permission-toolkit::messages.btn_filter') }}:</span>

                @if($selectedRole)
                    <span class="badge badge-info" style="font-size: 0.8rem; padding: 0.25rem 0.5rem;">
                        {{ __('permission-toolkit::messages.users_filter_active_role', ['name' => $selectedRole]) }}
                    </span>
                @endif

                @if($selectedPermission)
                    <span class="badge badge-success" style="font-size: 0.8rem; padding: 0.25rem 0.5rem;">
                        {{ __('permission-toolkit::messages.users_filter_active_perm', ['name' => $selectedPermission]) }}
                    </span>
                @endif

                @if(request('search'))
                    <span class="badge" style="background: var(--bg-item); border: 1px solid var(--border); color: var(--text-main); font-size: 0.8rem; padding: 0.25rem 0.5rem;">
                        "{{ request('search') }}"
                    </span>
                @endif

                @if(isset($statusFilter) && $statusFilter !== 'all')
                    <span class="badge badge-warning" style="font-size: 0.8rem; padding: 0.25rem 0.5rem;">
                        {{ __('permission-toolkit::messages.users_filter_active_status', ['status' => $statusFilter === 'trashed' ? __('permission-toolkit::messages.users_filter_status_trashed') : __('permission-toolkit::messages.users_filter_status_active')]) }}
                    </span>
                @endif

                <span style="color: var(--text-muted); font-size: 0.8rem;">
                    ({{ method_exists($users, 'total') ? $users->total() : $users->count() }} {{ __('permission-toolkit::messages.users_title') }})
                </span>
            </div>

            <a href="{{ route('permission-toolkit.users.index') }}" style="color: #ef4444; font-size: 0.8rem; text-decoration: none; font-weight: 600;">
                ✕ {{ __('permission-toolkit::messages.btn_reset') }}
            </a>
        </div>
    @endif

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th style="width: 80px;">{{ __('permission-toolkit::messages.users_th_id') }}</th>
                    <th>{{ __('permission-toolkit::messages.users_th_user') }}</th>
                    <th>{{ __('permission-toolkit::messages.users_th_email') }}</th>
                    <th>{{ __('permission-toolkit::messages.users_th_roles') }}</th>
                    <th style="text-align: center;">
                        @if($selectedPermission)
                            {{ __('permission-toolkit::messages.users_filter_active_perm', ['name' => $selectedPermission]) }}
                        @else
                            {{ __('permission-toolkit::messages.users_th_direct_perms') }}
                        @endif
                    </th>
                    <th style="width: 220px; text-align: center;">{{ __('permission-toolkit::messages.users_th_action') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                    @php
                        $isTrashed = $supportsSoftDeletes && method_exists($user, 'trashed') && $user->trashed();
                    @endphp
                    <tr style="{{ $isTrashed ? 'opacity: 0.75; background: rgba(239, 68, 68, 0.04);' : '' }}">
                        <td style="color: var(--text-muted); font-weight: 500;">#{{ $user->id }}</td>
                        <td style="font-weight: 600;">
                            <span style="{{ $isTrashed ? 'text-decoration: line-through; color: var(--text-muted);' : '' }}">
                                {{ $user->name ?? __('permission-toolkit::messages.users_th_user') . ' #' . $user->id }}
                            </span>
                            @if($supportsSoftDeletes)
                                @if($isTrashed)
                                    <span class="badge badge-danger" style="margin-left: 0.4rem; font-size: 0.65rem;">
                                        {{ __('permission-toolkit::messages.users_status_deactivated') }}
                                    </span>
                                @else
                                    <span class="badge badge-success" style="margin-left: 0.4rem; font-size: 0.65rem;">
                                        {{ __('permission-toolkit::messages.users_status_active') }}
                                    </span>
                                @endif
                            @endif
                        </td>
                        <td style="color: var(--text-muted); font-size: 0.85rem;">{{ $user->email ?? 'N/D' }}</td>
                        <td>
                            @forelse($user->roles as $role)
                                <a href="{{ route('permission-toolkit.users.index', ['role' => $role->name]) }}" 
                                   class="badge badge-info" 
                                   title="{{ __('permission-toolkit::messages.matrix_view_users_role') }}"
                                   style="margin-right: 0.25rem; margin-bottom: 0.25rem; display: inline-block; word-break: break-word; overflow-wrap: anywhere; white-space: normal; line-height: 1.25; text-decoration: none;">
                                    {{ $role->name }}
                                </a>
                            @empty
                                <span style="color: var(--text-muted); font-size: 0.8rem;">{{ __('permission-toolkit::messages.users_no_roles') }}</span>
                            @endforelse
                        </td>
                        <td style="text-align: center;">
                            @if($selectedPermission)
                                @php
                                    $hasDirect = $user->permissions->contains('name', $selectedPermission) || $user->permissions->contains('id', $selectedPermission);
                                    $matchingRoles = $user->roles->filter(function($r) use ($selectedPermission) {
                                        return method_exists($r, 'hasPermissionTo') && $r->hasPermissionTo($selectedPermission);
                                    })->pluck('name')->implode(', ');
                                @endphp
                                @if($hasDirect && $matchingRoles)
                                    <span class="badge badge-success" style="font-size: 0.7rem; margin-bottom: 2px;">{{ __('permission-toolkit::messages.users_has_perm_direct') }}</span>
                                    <span class="badge badge-info" style="font-size: 0.7rem;">{{ __('permission-toolkit::messages.users_has_perm_via_role', ['role' => $matchingRoles]) }}</span>
                                @elseif($hasDirect)
                                    <span class="badge badge-success" style="font-size: 0.7rem;">{{ __('permission-toolkit::messages.users_has_perm_direct') }}</span>
                                @elseif($matchingRoles)
                                    <span class="badge badge-info" style="font-size: 0.7rem;">{{ __('permission-toolkit::messages.users_has_perm_via_role', ['role' => $matchingRoles]) }}</span>
                                @else
                                    <span style="color: var(--text-muted); font-size: 0.8rem;">—</span>
                                @endif
                            @else
                                @if($user->permissions->count() > 0)
                                    <span class="badge badge-success">{{ __('permission-toolkit::messages.users_direct_count', ['count' => $user->permissions->count()]) }}</span>
                                @else
                                    <span style="color: var(--text-muted); font-size: 0.8rem;">0</span>
                                @endif
                            @endif
                        </td>
                        <td style="text-align: center;">
                            <div style="display: inline-flex; align-items: center; gap: 0.35rem; justify-content: center;">
                                @if($isTrashed)
                                    <!-- Restore Form -->
                                    <form method="POST" action="{{ route('permission-toolkit.users.restore', $user->id) }}" onsubmit="return confirm('{{ addslashes(__('permission-toolkit::messages.users_confirm_restore', ['name' => $user->name ?? $user->id])) }}');">
                                        @csrf
                                        <button type="submit" class="btn" style="padding: 0.3rem 0.6rem; font-size: 0.75rem; background: #064e3b; color: #a7f3d0; border-color: #047857;">
                                            {{ __('permission-toolkit::messages.users_restore_btn') }}
                                        </button>
                                    </form>

                                    <!-- Force Delete Form -->
                                    <form method="POST" action="{{ route('permission-toolkit.users.force-delete', $user->id) }}" onsubmit="return confirm('{{ addslashes(__('permission-toolkit::messages.users_confirm_force_delete', ['name' => $user->name ?? $user->id])) }}');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn" style="padding: 0.3rem 0.6rem; font-size: 0.75rem; background: #7f1d1d; color: #fecaca; border-color: #991b1b;">
                                            {{ __('permission-toolkit::messages.users_force_delete_btn') }}
                                        </button>
                                    </form>
                                @else
                                    <!-- Manage Button -->
                                    <a href="{{ route('permission-toolkit.users.edit', $user->id) }}" class="btn" style="padding: 0.3rem 0.6rem; font-size: 0.75rem;">
                                        {{ __('permission-toolkit::messages.users_manage_btn') }}
                                    </a>

                                    <!-- Deactivate / Delete Form -->
                                    <form method="POST" action="{{ route('permission-toolkit.users.destroy', $user->id) }}" onsubmit="return confirm('{{ addslashes($supportsSoftDeletes ? __('permission-toolkit::messages.users_confirm_deactivate', ['name' => $user->name ?? $user->id]) : __('permission-toolkit::messages.users_confirm_delete', ['name' => $user->name ?? $user->id])) }}');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-secondary" style="padding: 0.3rem 0.6rem; font-size: 0.75rem; color: #ef4444; border-color: rgba(239, 68, 68, 0.4);" title="{{ $supportsSoftDeletes ? __('permission-toolkit::messages.users_deactivate_btn') : __('permission-toolkit::messages.users_delete_btn') }}">
                                            {{ $supportsSoftDeletes ? __('permission-toolkit::messages.users_deactivate_btn') : __('permission-toolkit::messages.users_delete_btn') }}
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 2.5rem;">
                            {{ __('permission-toolkit::messages.users_empty') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if(method_exists($users, 'links'))
        <div style="margin-top: 1.5rem;">
            {{ $users->links() }}
        </div>
    @endif
</div>
@endsection
