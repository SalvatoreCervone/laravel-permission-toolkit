@extends('permission-toolkit::layouts.app')

@section('title', 'Gestione Utenti')

@section('content')
<div class="card">
    <div class="card-header">
        <div>
            <h1 class="card-title">👥 Gestione Accesso Utenti</h1>
            <p style="color: var(--text-muted); font-size: 0.85rem; margin-top: 0.25rem;">
                Visualizza gli utenti dell'applicazione, assegna o revoca ruoli e permessi diretti Spatie.
            </p>
        </div>
        <form method="GET" action="{{ route('permission-toolkit.users.index') }}" style="width: 320px; display: flex; gap: 0.5rem;">
            <input type="text" name="search" class="input-control" value="{{ request('search') }}" placeholder="Cerca utente per nome o email...">
            <button type="submit" class="btn">Cerca</button>
            @if(request('search'))
                <a href="{{ route('permission-toolkit.users.index') }}" class="btn" style="background: var(--border);">X</a>
            @endif
        </form>
    </div>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th style="width: 80px;">ID</th>
                    <th>Utente</th>
                    <th>Email</th>
                    <th>Ruoli Spatie Assegnati</th>
                    <th style="text-align: center;">Permessi Diretti</th>
                    <th style="width: 140px; text-align: center;">Azione</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                    <tr>
                        <td style="color: var(--text-muted); font-weight: 500;">#{{ $user->id }}</td>
                        <td style="font-weight: 600;">{{ $user->name ?? 'Utente #' . $user->id }}</td>
                        <td style="color: var(--text-muted); font-size: 0.85rem;">{{ $user->email ?? 'N/D' }}</td>
                        <td>
                            @forelse($user->roles as $role)
                                <span class="badge badge-info" style="margin-right: 0.25rem;">{{ $role->name }}</span>
                            @empty
                                <span style="color: var(--text-muted); font-size: 0.8rem;">Nessun ruolo</span>
                            @endforelse
                        </td>
                        <td style="text-align: center;">
                            @if($user->permissions->count() > 0)
                                <span class="badge badge-success">{{ $user->permissions->count() }} diretti</span>
                            @else
                                <span style="color: var(--text-muted); font-size: 0.8rem;">0</span>
                            @endif
                        </td>
                        <td style="text-align: center;">
                            <a href="{{ route('permission-toolkit.users.edit', $user->id) }}" class="btn" style="padding: 0.35rem 0.75rem; font-size: 0.8rem;">
                                ⚙️ Gestisci
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 2.5rem;">
                            Nessun utente trovato.
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
