@extends('permission-toolkit::layouts.app')

@section('title', __('permission-toolkit::messages.users_title'))

@section('content')
<div class="card">
    <div class="card-header">
        <div>
            <h1 class="card-title">{{ __('permission-toolkit::messages.users_heading') }}</h1>
            <p style="color: var(--text-muted); font-size: 0.85rem; margin-top: 0.25rem;">
                {{ __('permission-toolkit::messages.users_subtitle') }}
            </p>
        </div>
        <form method="GET" action="{{ route('permission-toolkit.users.index') }}" style="width: 320px; display: flex; gap: 0.5rem;">
            <input type="text" name="search" class="input-control" value="{{ request('search') }}" placeholder="{{ __('permission-toolkit::messages.users_search_placeholder') }}">
            <button type="submit" class="btn">{{ __('permission-toolkit::messages.users_search_btn') }}</button>
            @if(request('search'))
                <a href="{{ route('permission-toolkit.users.index') }}" class="btn" style="background: var(--border);">X</a>
            @endif
        </form>
    </div>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th style="width: 80px;">{{ __('permission-toolkit::messages.users_th_id') }}</th>
                    <th>{{ __('permission-toolkit::messages.users_th_user') }}</th>
                    <th>{{ __('permission-toolkit::messages.users_th_email') }}</th>
                    <th>{{ __('permission-toolkit::messages.users_th_roles') }}</th>
                    <th style="text-align: center;">{{ __('permission-toolkit::messages.users_th_direct_perms') }}</th>
                    <th style="width: 140px; text-align: center;">{{ __('permission-toolkit::messages.users_th_action') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                    <tr>
                        <td style="color: var(--text-muted); font-weight: 500;">#{{ $user->id }}</td>
                        <td style="font-weight: 600;">{{ $user->name ?? __('permission-toolkit::messages.users_th_user') . ' #' . $user->id }}</td>
                        <td style="color: var(--text-muted); font-size: 0.85rem;">{{ $user->email ?? 'N/D' }}</td>
                        <td>
                            @forelse($user->roles as $role)
                                <span class="badge badge-info" style="margin-right: 0.25rem;">{{ $role->name }}</span>
                            @empty
                                <span style="color: var(--text-muted); font-size: 0.8rem;">{{ __('permission-toolkit::messages.users_no_roles') }}</span>
                            @endforelse
                        </td>
                        <td style="text-align: center;">
                            @if($user->permissions->count() > 0)
                                <span class="badge badge-success">{{ __('permission-toolkit::messages.users_direct_count', ['count' => $user->permissions->count()]) }}</span>
                            @else
                                <span style="color: var(--text-muted); font-size: 0.8rem;">0</span>
                            @endif
                        </td>
                        <td style="text-align: center;">
                            <a href="{{ route('permission-toolkit.users.edit', $user->id) }}" class="btn" style="padding: 0.35rem 0.75rem; font-size: 0.8rem;">
                                {{ __('permission-toolkit::messages.users_manage_btn') }}
                            </a>
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
