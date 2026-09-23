@extends('permission-toolkit::layouts.app')

@section('title', 'Modifica Accesso Utente: ' . ($user->name ?? $user->email))

@section('content')
<div style="max-width: 1000px; margin: 0 auto;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <div>
            <a href="{{ route('permission-toolkit.users.index') }}" style="color: #a5b4fc; text-decoration: none; font-size: 0.85rem;">
                ← Torna alla lista utenti
            </a>
            <h1 style="font-size: 1.5rem; font-weight: 700; margin-top: 0.25rem;">
                👤 Gestione Accessi: {{ $user->name ?? $user->email }}
            </h1>
        </div>
        <a href="{{ route('permission-toolkit.simulator', ['user_id' => $user->id, 'ability' => '']) }}" class="btn" style="background: #1e1b4b; border: 1px solid #4338ca;">
            🔍 Testa con il Simulatore
        </a>
    </div>

    @if(session('status'))
        <div style="background: rgba(16, 185, 129, 0.1); border: 1px solid var(--success); color: #6ee7b7; padding: 1rem; border-radius: 0.375rem; margin-bottom: 1.5rem;">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('permission-toolkit.users.update', $user->id) }}">
        @csrf

        <!-- Sezione 1: Ruoli Spatie -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">1. Ruoli Spatie</h2>
                <span style="font-size: 0.8rem; color: var(--text-muted);">Seleziona i ruoli da assegnare a questo utente</span>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 1rem;">
                @forelse($roles as $role)
                    @php $isAssigned = in_array($role->id, $userRoleIds); @endphp
                    <label style="display: flex; align-items: center; gap: 0.75rem; background: #171f2e; border: 1px solid {{ $isAssigned ? 'var(--primary)' : 'var(--border)' }}; padding: 0.75rem 1rem; border-radius: 0.375rem; cursor: pointer;">
                        <input 
                            type="checkbox" 
                            name="roles[]" 
                            value="{{ $role->id }}" 
                            {{ $isAssigned ? 'checked' : '' }}
                            style="width: 1.15rem; height: 1.15rem; accent-color: var(--primary);"
                        >
                        <div>
                            <div style="font-weight: 600; font-size: 0.9rem;">{{ $role->name }}</div>
                            <div style="font-size: 0.7rem; color: var(--text-muted);">{{ $role->guard_name }} • {{ $role->permissions->count() }} permessi</div>
                        </div>
                    </label>
                @empty
                    <div style="color: var(--text-muted); font-size: 0.85rem;">Nessun ruolo presente nel database.</div>
                @endforelse
            </div>
        </div>

        <!-- Sezione 2: Permessi Diretti -->
        <div class="card">
            <div class="card-header">
                <div>
                    <h2 class="card-title">2. Permessi Diretti (Opzionali)</h2>
                    <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.2rem;">
                        Permessi specifici assegnati direttamente all'utente (al di fuori di quelli ereditati dai ruoli).
                    </p>
                </div>
            </div>

            @foreach($groupedPermissions as $group => $perms)
                <div style="margin-bottom: 1.25rem;">
                    <div style="font-weight: 700; color: #a5b4fc; text-transform: uppercase; font-size: 0.75rem; margin-bottom: 0.5rem; letter-spacing: 0.05em;">
                        📂 {{ $group }} ({{ count($perms) }})
                    </div>
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 0.5rem;">
                        @foreach($perms as $permission)
                            @php $isDirect = in_array($permission->id, $userPermissionIds); @endphp
                            <label style="display: flex; align-items: center; gap: 0.5rem; background: rgba(255,255,255,0.02); border: 1px solid var(--border); padding: 0.5rem 0.75rem; border-radius: 0.25rem; cursor: pointer; font-size: 0.85rem;">
                                <input 
                                    type="checkbox" 
                                    name="permissions[]" 
                                    value="{{ $permission->id }}" 
                                    {{ $isDirect ? 'checked' : '' }}
                                    style="accent-color: var(--primary);"
                                >
                                <span style="font-family: monospace;">{{ $permission->name }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        <div style="display: flex; justify-content: flex-end; gap: 1rem;">
            <a href="{{ route('permission-toolkit.users.index') }}" class="btn" style="background: var(--border);">Annulla</a>
            <button type="submit" class="btn" style="padding: 0.75rem 2rem; font-size: 0.95rem;">
                💾 Salva Assegnazioni
            </button>
        </div>
    </form>
</div>
@endsection
