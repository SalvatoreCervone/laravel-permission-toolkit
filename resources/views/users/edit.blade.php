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

    @if($errors->any())
        <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid var(--danger); color: #fca5a5; padding: 1rem; border-radius: 0.375rem; margin-bottom: 1.5rem;">
            <div style="font-weight: 600; margin-bottom: 0.5rem;">Si sono verificati dei problemi durante il salvataggio:</div>
            <ul style="margin: 0; padding-left: 1.25rem; font-size: 0.85rem;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
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

        <div style="display: flex; justify-content: flex-end; gap: 1rem; margin-bottom: 2rem;">
            <a href="{{ route('permission-toolkit.users.index') }}" class="btn" style="background: var(--border);">Annulla</a>
            <button type="submit" class="btn" style="padding: 0.75rem 2rem; font-size: 0.95rem;">
                💾 Salva Ruoli & Permessi
            </button>
        </div>
    </form>

    @if($passwordResetEnabled)
    <!-- Sezione 3: Reset Password & Data Associata -->
    <div class="card" style="border-top: 3px solid #6366f1;">
        <div class="card-header">
            <div>
                <h2 class="card-title">🔑 3. Reimposta Password & Data Reset</h2>
                <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.2rem;">
                    Reimposta istantaneamente la password dell'utente. Puoi opzionalmente impostare o aggiornare un campo data/ora sul modello utente (es. scadenza, data di reset, obbligo cambio password).
                </p>
            </div>
            <span class="badge badge-info">Sicurezza</span>
        </div>

        <form method="POST" action="{{ route('permission-toolkit.users.password', $user->id) }}">
            @csrf

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; margin-bottom: 1.5rem;">
                <!-- Password Input & Generator -->
                <div style="background: #171f2e; padding: 1.25rem; border-radius: 0.5rem; border: 1px solid var(--border);">
                    <label style="display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 0.5rem;">
                        Nuova Password <span style="color: #ef4444;">*</span>
                    </label>
                    <div style="display: flex; gap: 0.5rem; margin-bottom: 0.5rem;">
                        <input 
                            type="password" 
                            name="password" 
                            id="new_password_input" 
                            class="input-control" 
                            required 
                            minlength="6"
                            placeholder="Inserisci nuova password..." 
                            autocomplete="new-password"
                        >
                        <button type="button" class="btn btn-secondary" onclick="togglePasswordVisibility()" title="Mostra/Nascondi password" style="padding: 0.5rem 0.75rem;">
                            👁️
                        </button>
                    </div>

                    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                        <button type="button" class="btn btn-secondary" onclick="generateRandomPassword()" style="font-size: 0.8rem; padding: 0.35rem 0.75rem;">
                            🎲 Genera Casuale
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="copyGeneratedPassword()" id="copy_btn" style="font-size: 0.8rem; padding: 0.35rem 0.75rem; display: none;">
                            📋 Copia
                        </button>
                    </div>

                    <div id="password_display_box" style="display: none; margin-top: 0.75rem; background: #0f172a; padding: 0.5rem 0.75rem; border-radius: 0.375rem; border: 1px dashed #6366f1; font-size: 0.8rem; word-break: break-all;">
                        <span style="color: var(--text-muted);">Generata:</span> <strong id="generated_password_text" style="color: #a5b4fc; font-family: monospace;"></strong>
                    </div>
                </div>

                <!-- Optional Date Field Input -->
                <div style="background: #171f2e; padding: 1.25rem; border-radius: 0.5rem; border: 1px solid var(--border);">
                    <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 0.5rem;">
                        <label style="font-weight: 600; font-size: 0.85rem;">
                            Aggiorna Campo Data (Opzionale)
                        </label>
                        <span style="font-size: 0.75rem; color: #a5b4fc;">es. <code>{{ $defaultDateField }}</code></span>
                    </div>

                    <div style="margin-bottom: 0.75rem;">
                        <label style="display: block; font-size: 0.75rem; color: var(--text-muted); margin-bottom: 0.25rem;">
                            Nome colonna nel DB:
                        </label>
                        <input 
                            type="text" 
                            name="date_field" 
                            id="date_field_input" 
                            value="{{ $defaultDateField }}" 
                            class="input-control" 
                            placeholder="password_reset"
                            style="font-family: monospace; font-size: 0.8rem;"
                        >
                    </div>

                    <div style="margin-bottom: 0.5rem;">
                        <label style="display: block; font-size: 0.75rem; color: var(--text-muted); margin-bottom: 0.25rem;">
                            Valore Data/Ora da impostare:
                        </label>
                        <input 
                            type="datetime-local" 
                            name="date_value" 
                            id="date_value_input" 
                            class="input-control" 
                            style="font-size: 0.85rem;"
                        >
                    </div>

                    <div style="display: flex; gap: 0.35rem; flex-wrap: wrap;">
                        <button type="button" class="btn btn-secondary" onclick="setDateNow()" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;">
                            ⚡ Adesso
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="addDaysToDate(30)" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;">
                            +30 gg
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="addDaysToDate(90)" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;">
                            +90 gg
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="clearDateInput()" style="font-size: 0.75rem; padding: 0.25rem 0.5rem; color: #fca5a5;">
                            ❌ Svuota
                        </button>
                    </div>

                    @if(!empty($defaultDateField) && isset($user->{$defaultDateField}))
                        <div style="margin-top: 0.75rem; font-size: 0.75rem; color: var(--text-muted); background: rgba(255,255,255,0.02); padding: 0.4rem 0.6rem; border-radius: 0.25rem;">
                            Valore attuale nel DB ({{ $defaultDateField }}): 
                            <strong style="color: #6ee7b7;">{{ $user->{$defaultDateField} }}</strong>
                        </div>
                    @endif
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end;">
                <button type="submit" class="btn" style="background: #4f46e5; padding: 0.75rem 1.75rem;" onclick="return confirm('Sei sicuro di voler reimpostare la password per questo utente?');">
                    🔒 Conferma Reset Password
                </button>
            </div>
        </form>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    function generateRandomPassword() {
        const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789!@#$%&*';
        let pass = '';
        const array = new Uint32Array(14);
        window.crypto.getRandomValues(array);
        for (let i = 0; i < 14; i++) {
            pass += chars[array[i] % chars.length];
        }

        const input = document.getElementById('new_password_input');
        input.value = pass;
        input.type = 'text';

        const displayBox = document.getElementById('password_display_box');
        const textSpan = document.getElementById('generated_password_text');
        const copyBtn = document.getElementById('copy_btn');

        textSpan.textContent = pass;
        displayBox.style.display = 'block';
        copyBtn.style.display = 'inline-flex';

        if (typeof showToast === 'function') {
            showToast('Password casuale generata!');
        }
    }

    function togglePasswordVisibility() {
        const input = document.getElementById('new_password_input');
        input.type = input.type === 'password' ? 'text' : 'password';
    }

    function copyGeneratedPassword() {
        const input = document.getElementById('new_password_input');
        if (input.value) {
            navigator.clipboard.writeText(input.value).then(() => {
                if (typeof showToast === 'function') {
                    showToast('Password copiata negli appunti!');
                }
            });
        }
    }

    function setDateNow() {
        const now = new Date();
        now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
        document.getElementById('date_value_input').value = now.toISOString().slice(0, 16);
    }

    function addDaysToDate(days) {
        const d = new Date();
        d.setDate(d.getDate() + days);
        d.setMinutes(d.getMinutes() - d.getTimezoneOffset());
        document.getElementById('date_value_input').value = d.toISOString().slice(0, 16);
    }

    function clearDateInput() {
        document.getElementById('date_value_input').value = '';
    }
</script>
@endpush
