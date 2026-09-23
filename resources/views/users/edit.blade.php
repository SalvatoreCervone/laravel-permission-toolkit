@extends('permission-toolkit::layouts.app')

@section('title', 'Modifica Accesso Utente: ' . ($user->name ?? $user->email))

@section('content')
<div style="max-width: 1000px; margin: 0 auto;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
        <div>
            <a href="{{ route('permission-toolkit.users.index') }}" style="color: #a5b4fc; text-decoration: none; font-size: 0.85rem;">
                ← Torna alla lista utenti
            </a>
            <h1 style="font-size: 1.5rem; font-weight: 700; margin-top: 0.25rem;">
                👤 Gestione Accessi: {{ $user->name ?? $user->email }}
            </h1>
        </div>
        <div style="display: flex; gap: 0.75rem; align-items: center; flex-wrap: wrap;">
            @if($passwordResetEnabled)
                <button type="button" class="btn" onclick="openPasswordModal()" style="background: #4f46e5; border: 1px solid #6366f1;">
                    🔑 Reset Password
                </button>
            @endif
            <a href="{{ route('permission-toolkit.simulator', ['user_id' => $user->id, 'ability' => '']) }}" class="btn" style="background: #1e1b4b; border: 1px solid #4338ca;">
                🔍 Testa con il Simulatore
            </a>
        </div>
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
</div>

@if($passwordResetEnabled)
<!-- Modale Reset Password & Data -->
<div id="passwordModal" style="display: none; position: fixed; inset: 0; z-index: 9999; background: rgba(0, 0, 0, 0.75); backdrop-filter: blur(4px); align-items: center; justify-content: center; padding: 1rem;" onclick="if(event.target === this) closePasswordModal();">
    <div style="background: #111827; border: 1px solid #374151; border-radius: 0.75rem; width: 100%; max-width: 520px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.75); overflow: hidden; animation: modalFadeIn 0.2s ease-out;">
        <!-- Modal Header -->
        <div style="display: flex; justify-content: space-between; align-items: center; padding: 1.25rem 1.5rem; border-bottom: 1px solid #1f2937;">
            <div>
                <h3 style="font-size: 1.15rem; font-weight: 700; color: #f9fafb; margin: 0; display: flex; align-items: center; gap: 0.5rem;">
                    🔑 Reimposta Password Utente
                </h3>
                <p style="font-size: 0.8rem; color: var(--text-muted); margin: 0.25rem 0 0 0;">
                    Utente: <strong style="color: #a5b4fc;">{{ $user->name ?? $user->email }}</strong>
                </p>
            </div>
            <button type="button" onclick="closePasswordModal()" style="background: transparent; border: none; color: #9ca3af; font-size: 1.5rem; cursor: pointer; line-height: 1; padding: 0.25rem;">
                &times;
            </button>
        </div>

        <form method="POST" action="{{ route('permission-toolkit.users.password', $user->id) }}">
            @csrf

            <!-- Modal Body -->
            <div style="padding: 1.5rem;">
                <!-- Password Field -->
                <div style="margin-bottom: 1.25rem;">
                    <label style="display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 0.4rem; color: #e5e7eb;">
                        Nuova Password <span style="color: #ef4444;">*</span>
                    </label>
                    <div style="display: flex; gap: 0.5rem; margin-bottom: 0.5rem;">
                        <input 
                            type="password" 
                            name="password" 
                            id="modal_password_input" 
                            class="input-control" 
                            required 
                            minlength="6"
                            placeholder="Inserisci o genera una nuova password..." 
                            autocomplete="new-password"
                        >
                        <button type="button" class="btn btn-secondary" onclick="toggleModalPasswordVisibility()" title="Mostra/Nascondi password" style="padding: 0.5rem 0.75rem;">
                            👁️
                        </button>
                    </div>

                    <div style="display: flex; gap: 0.5rem;">
                        <button type="button" class="btn btn-secondary" onclick="generateModalPassword()" style="font-size: 0.8rem; padding: 0.35rem 0.75rem;">
                            🎲 Genera Casuale
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="copyModalPassword()" id="modal_copy_btn" style="font-size: 0.8rem; padding: 0.35rem 0.75rem; display: none;">
                            📋 Copia
                        </button>
                    </div>

                    <div id="modal_password_display" style="display: none; margin-top: 0.5rem; background: #0f172a; padding: 0.5rem 0.75rem; border-radius: 0.375rem; border: 1px dashed #6366f1; font-size: 0.8rem; word-break: break-all;">
                        <span style="color: var(--text-muted);">Generata:</span> <strong id="modal_password_text" style="color: #a5b4fc; font-family: monospace;"></strong>
                    </div>
                </div>

                <!-- Date Option Section -->
                <div style="background: #171f2e; padding: 1.15rem; border-radius: 0.5rem; border: 1px solid var(--border);">
                    <label style="display: flex; align-items: center; gap: 0.5rem; font-weight: 600; font-size: 0.85rem; cursor: pointer; color: #f3f4f6; margin-bottom: 0.5rem;">
                        <input 
                            type="checkbox" 
                            name="update_date" 
                            id="modal_update_date" 
                            value="1" 
                            checked 
                            onchange="toggleDateSection(this.checked)"
                            style="accent-color: #6366f1; width: 1.05rem; height: 1.05rem;"
                        >
                        <span>Aggiorna campo data (default: oggi)</span>
                    </label>

                    <div style="font-size: 0.75rem; color: #9ca3af; margin-bottom: 0.75rem;">
                        Campo configurato: <code style="color: #a5b4fc; background: rgba(0,0,0,0.3); padding: 0.15rem 0.35rem; border-radius: 0.25rem;">{{ $defaultDateField }}</code>
                        <span style="color: var(--text-muted);">(configurato da config / .env)</span>
                    </div>

                    <div id="date_input_container">
                        <label style="display: block; font-size: 0.75rem; color: var(--text-muted); margin-bottom: 0.25rem;">
                            Data e ora di reset:
                        </label>
                        <input 
                            type="datetime-local" 
                            name="date_value" 
                            id="modal_date_value" 
                            value="{{ now()->format('Y-m-d\TH:i') }}" 
                            class="input-control" 
                            style="font-size: 0.85rem; margin-bottom: 0.5rem;"
                        >

                        <div style="display: flex; gap: 0.35rem; flex-wrap: wrap;">
                            <button type="button" class="btn btn-secondary" onclick="setModalDateToday()" style="font-size: 0.75rem; padding: 0.2rem 0.5rem;">
                                ⚡ Oggi
                            </button>
                            <button type="button" class="btn btn-secondary" onclick="addModalDays(30)" style="font-size: 0.75rem; padding: 0.2rem 0.5rem;">
                                +30 gg
                            </button>
                            <button type="button" class="btn btn-secondary" onclick="addModalDays(90)" style="font-size: 0.75rem; padding: 0.2rem 0.5rem;">
                                +90 gg
                            </button>
                        </div>
                    </div>

                    @if(!empty($defaultDateField) && isset($user->{$defaultDateField}))
                        <div style="margin-top: 0.75rem; font-size: 0.75rem; color: var(--text-muted); background: rgba(0,0,0,0.25); padding: 0.35rem 0.5rem; border-radius: 0.25rem;">
                            Valore attuale nel DB: <strong style="color: #6ee7b7;">{{ $user->{$defaultDateField} }}</strong>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Modal Footer -->
            <div style="display: flex; justify-content: flex-end; gap: 0.75rem; padding: 1rem 1.5rem; background: #0f172a; border-top: 1px solid #1f2937;">
                <button type="button" class="btn btn-secondary" onclick="closePasswordModal()">Annulla</button>
                <button type="submit" class="btn" style="background: #4f46e5; border: 1px solid #6366f1;">
                    🔒 Salva Nuova Password
                </button>
            </div>
        </form>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
    function openPasswordModal() {
        const modal = document.getElementById('passwordModal');
        if (modal) {
            modal.style.display = 'flex';
            setTimeout(() => {
                const input = document.getElementById('modal_password_input');
                if (input) input.focus();
            }, 50);
        }
    }

    function closePasswordModal() {
        const modal = document.getElementById('passwordModal');
        if (modal) {
            modal.style.display = 'none';
        }
    }

    window.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closePasswordModal();
        }
    });

    function generateModalPassword() {
        const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789!@#$%&*';
        let pass = '';
        const array = new Uint32Array(14);
        window.crypto.getRandomValues(array);
        for (let i = 0; i < 14; i++) {
            pass += chars[array[i] % chars.length];
        }

        const input = document.getElementById('modal_password_input');
        input.value = pass;
        input.type = 'text';

        const displayBox = document.getElementById('modal_password_display');
        const textSpan = document.getElementById('modal_password_text');
        const copyBtn = document.getElementById('modal_copy_btn');

        textSpan.textContent = pass;
        displayBox.style.display = 'block';
        copyBtn.style.display = 'inline-flex';

        if (typeof showToast === 'function') {
            showToast('Password casuale generata!');
        }
    }

    function toggleModalPasswordVisibility() {
        const input = document.getElementById('modal_password_input');
        input.type = input.type === 'password' ? 'text' : 'password';
    }

    function copyModalPassword() {
        const input = document.getElementById('modal_password_input');
        if (input.value) {
            navigator.clipboard.writeText(input.value).then(() => {
                if (typeof showToast === 'function') {
                    showToast('Password copiata negli appunti!');
                }
            });
        }
    }

    function toggleDateSection(isChecked) {
        const container = document.getElementById('date_input_container');
        if (container) {
            container.style.opacity = isChecked ? '1' : '0.4';
            container.style.pointerEvents = isChecked ? 'auto' : 'none';
        }
    }

    function setModalDateToday() {
        const now = new Date();
        now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
        document.getElementById('modal_date_value').value = now.toISOString().slice(0, 16);
    }

    function addModalDays(days) {
        const d = new Date();
        d.setDate(d.getDate() + days);
        d.setMinutes(d.getMinutes() - d.getTimezoneOffset());
        document.getElementById('modal_date_value').value = d.toISOString().slice(0, 16);
    }
</script>
@endpush
