@extends('permission-toolkit::layouts.app')

@section('title', __('permission-toolkit::messages.user_edit_title', ['name' => $user->name ?? $user->email]))

@section('content')
<div style="max-width: 1000px; margin: 0 auto;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
        <div>
            <a href="{{ route('permission-toolkit.users.index') }}" style="color: #a5b4fc; text-decoration: none; font-size: 0.85rem;">
                {{ __('permission-toolkit::messages.user_edit_back') }}
            </a>
            <h1 style="font-size: 1.5rem; font-weight: 700; margin-top: 0.25rem;">
                {{ __('permission-toolkit::messages.user_edit_heading', ['name' => $user->name ?? $user->email]) }}
            </h1>
        </div>
        <div style="display: flex; gap: 0.75rem; align-items: center; flex-wrap: wrap;">
            @if($passwordResetEnabled)
                <button type="button" class="btn" onclick="openPasswordModal()" style="background: #4f46e5; border: 1px solid #6366f1;">
                    {{ __('permission-toolkit::messages.user_edit_btn_reset_pwd') }}
                </button>
            @endif
            <a href="{{ route('permission-toolkit.simulator', ['user_id' => $user->id, 'ability' => '']) }}" class="btn" style="background: #1e1b4b; border: 1px solid #4338ca;">
                {{ __('permission-toolkit::messages.user_edit_btn_simulator') }}
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
            <div style="font-weight: 600; margin-bottom: 0.5rem;">{{ __('permission-toolkit::messages.user_edit_errors_title') }}</div>
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
                <h2 class="card-title">{{ __('permission-toolkit::messages.user_edit_sec1_title') }}</h2>
                <span style="font-size: 0.8rem; color: var(--text-muted);">{{ __('permission-toolkit::messages.user_edit_sec1_subtitle') }}</span>
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
                            <div style="font-size: 0.7rem; color: var(--text-muted);">{{ $role->guard_name }} • {{ __('permission-toolkit::messages.user_edit_perms_count', ['count' => $role->permissions->count()]) }}</div>
                        </div>
                    </label>
                @empty
                    <div style="color: var(--text-muted); font-size: 0.85rem;">{{ __('permission-toolkit::messages.user_edit_no_roles') }}</div>
                @endforelse
            </div>
        </div>

        <!-- Sezione 2: Permessi Diretti -->
        <div class="card">
            <div class="card-header">
                <div>
                    <h2 class="card-title">{{ __('permission-toolkit::messages.user_edit_sec2_title') }}</h2>
                    <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.2rem;">
                        {{ __('permission-toolkit::messages.user_edit_sec2_subtitle') }}
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
            <a href="{{ route('permission-toolkit.users.index') }}" class="btn" style="background: var(--border);">{{ __('permission-toolkit::messages.btn_cancel') }}</a>
            <button type="submit" class="btn" style="padding: 0.75rem 2rem; font-size: 0.95rem;">
                {{ __('permission-toolkit::messages.user_edit_btn_save') }}
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
                    {{ __('permission-toolkit::messages.user_pwd_modal_title') }}
                </h3>
                <p style="font-size: 0.8rem; color: var(--text-muted); margin: 0.25rem 0 0 0;">
                    {{ __('permission-toolkit::messages.user_pwd_modal_user', ['name' => $user->name ?? $user->email]) }}
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
                        {{ __('permission-toolkit::messages.user_pwd_new_label') }} <span style="color: #ef4444;">*</span>
                    </label>
                    <div style="display: flex; gap: 0.5rem; margin-bottom: 0.5rem;">
                        <input 
                            type="password" 
                            name="password" 
                            id="modal_password_input" 
                            class="input-control" 
                            required 
                            minlength="6"
                            placeholder="{{ __('permission-toolkit::messages.user_pwd_new_placeholder') }}" 
                            autocomplete="new-password"
                            oninput="checkPasswordMatch()"
                        >
                        <button type="button" class="btn btn-secondary" onclick="toggleModalPasswordVisibility()" title="Mostra/Nascondi password" style="padding: 0.5rem 0.75rem;">
                            👁️
                        </button>
                    </div>

                    <!-- Password Confirmation Field -->
                    <label style="display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 0.4rem; color: #e5e7eb;">
                        {{ __('permission-toolkit::messages.user_pwd_confirm_label') }} <span style="color: #ef4444;">*</span>
                    </label>
                    <div style="margin-bottom: 0.5rem;">
                        <input 
                            type="password" 
                            name="password_confirmation" 
                            id="modal_password_confirmation_input" 
                            class="input-control" 
                            required 
                            minlength="6"
                            placeholder="{{ __('permission-toolkit::messages.user_pwd_confirm_placeholder') }}" 
                            autocomplete="new-password"
                            oninput="checkPasswordMatch()"
                        >
                    </div>

                    <div id="password_match_feedback" style="font-size: 0.75rem; margin-bottom: 0.5rem; display: none;"></div>

                    <div style="display: flex; gap: 0.5rem;">
                        <button type="button" class="btn btn-secondary" onclick="generateModalPassword()" style="font-size: 0.8rem; padding: 0.35rem 0.75rem;">
                            {{ __('permission-toolkit::messages.user_pwd_btn_generate') }}
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="copyModalPassword()" id="modal_copy_btn" style="font-size: 0.8rem; padding: 0.35rem 0.75rem; display: none;">
                            {{ __('permission-toolkit::messages.user_pwd_btn_copy') }}
                        </button>
                    </div>

                    <div id="modal_password_display" style="display: none; margin-top: 0.5rem; background: #0f172a; padding: 0.5rem 0.75rem; border-radius: 0.375rem; border: 1px dashed #6366f1; font-size: 0.8rem; word-break: break-all;">
                        <span style="color: var(--text-muted);">{{ __('permission-toolkit::messages.user_pwd_generated') }}</span> <strong id="modal_password_text" style="color: #a5b4fc; font-family: monospace;"></strong>
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
                        <span>{{ __('permission-toolkit::messages.user_pwd_chk_update_date') }}</span>
                    </label>

                    <div style="font-size: 0.75rem; color: #9ca3af; margin-bottom: 0.75rem;">
                        {{ __('permission-toolkit::messages.user_pwd_configured_field') }} <code style="color: #a5b4fc; background: rgba(0,0,0,0.3); padding: 0.15rem 0.35rem; border-radius: 0.25rem;">{{ $defaultDateField }}</code>
                        <span style="color: var(--text-muted);">{{ __('permission-toolkit::messages.user_pwd_from_config') }}</span>
                    </div>

                    <div id="date_input_container">
                        <label style="display: block; font-size: 0.75rem; color: var(--text-muted); margin-bottom: 0.25rem;">
                            {{ __('permission-toolkit::messages.user_pwd_datetime_label') }}
                        </label>
                        <input 
                            type="datetime-local" 
                            name="date_value" 
                            id="modal_date_value" 
                            value="{{ now()->format('Y-m-d\T00:00') }}" 
                            class="input-control" 
                            style="font-size: 0.85rem; margin-bottom: 0.5rem;"
                        >

                        <div style="display: flex; gap: 0.35rem; flex-wrap: wrap;">
                            <button type="button" class="btn btn-secondary" onclick="setModalDateToday()" style="font-size: 0.75rem; padding: 0.2rem 0.5rem;">
                                {{ __('permission-toolkit::messages.user_pwd_quick_today') }}
                            </button>
                            <button type="button" class="btn btn-secondary" onclick="addModalDays(30)" style="font-size: 0.75rem; padding: 0.2rem 0.5rem;">
                                {{ __('permission-toolkit::messages.user_pwd_quick_30d') }}
                            </button>
                            <button type="button" class="btn btn-secondary" onclick="addModalDays(90)" style="font-size: 0.75rem; padding: 0.2rem 0.5rem;">
                                {{ __('permission-toolkit::messages.user_pwd_quick_90d') }}
                            </button>
                        </div>
                    </div>

                    @if(!empty($defaultDateField) && isset($user->{$defaultDateField}))
                        <div style="margin-top: 0.75rem; font-size: 0.75rem; color: var(--text-muted); background: rgba(0,0,0,0.25); padding: 0.35rem 0.5rem; border-radius: 0.25rem;">
                            {{ __('permission-toolkit::messages.user_pwd_current_db_val') }} <strong style="color: #6ee7b7;">{{ $user->{$defaultDateField} }}</strong>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Modal Footer -->
            <div style="display: flex; justify-content: flex-end; gap: 0.75rem; padding: 1rem 1.5rem; background: #0f172a; border-top: 1px solid #1f2937;">
                <button type="button" class="btn btn-secondary" onclick="closePasswordModal()">{{ __('permission-toolkit::messages.btn_cancel') }}</button>
                <button type="submit" class="btn" style="background: #4f46e5; border: 1px solid #6366f1;">
                    {{ __('permission-toolkit::messages.user_pwd_btn_save') }}
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

    @if($errors->has('password') || $errors->has('password_confirmation'))
        document.addEventListener('DOMContentLoaded', function() {
            openPasswordModal();
        });
    @endif

    function generateModalPassword() {
        const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789!@#$%&*';
        let pass = '';
        const array = new Uint32Array(14);
        window.crypto.getRandomValues(array);
        for (let i = 0; i < 14; i++) {
            pass += chars[array[i] % chars.length];
        }

        const input = document.getElementById('modal_password_input');
        const confirmInput = document.getElementById('modal_password_confirmation_input');
        
        input.value = pass;
        input.type = 'text';
        
        if (confirmInput) {
            confirmInput.value = pass;
            confirmInput.type = 'text';
        }

        const displayBox = document.getElementById('modal_password_display');
        const textSpan = document.getElementById('modal_password_text');
        const copyBtn = document.getElementById('modal_copy_btn');

        textSpan.textContent = pass;
        displayBox.style.display = 'block';
        copyBtn.style.display = 'inline-flex';

        checkPasswordMatch();

        if (typeof showToast === 'function') {
            showToast("{{ __('permission-toolkit::messages.user_pwd_toast_generated') }}");
        }
    }

    function toggleModalPasswordVisibility() {
        const input = document.getElementById('modal_password_input');
        const confirmInput = document.getElementById('modal_password_confirmation_input');
        const newType = input.type === 'password' ? 'text' : 'password';
        input.type = newType;
        if (confirmInput) {
            confirmInput.type = newType;
        }
    }

    function checkPasswordMatch() {
        const input = document.getElementById('modal_password_input');
        const confirmInput = document.getElementById('modal_password_confirmation_input');
        const feedback = document.getElementById('password_match_feedback');

        if (!feedback || !confirmInput) return;

        if (!confirmInput.value) {
            feedback.style.display = 'none';
            return;
        }

        feedback.style.display = 'block';
        if (input.value === confirmInput.value) {
            feedback.textContent = "{{ __('permission-toolkit::messages.user_pwd_match') }}";
            feedback.style.color = '#6ee7b7';
        } else {
            feedback.textContent = "{{ __('permission-toolkit::messages.user_pwd_mismatch') }}";
            feedback.style.color = '#fca5a5';
        }
    }

    function copyModalPassword() {
        const input = document.getElementById('modal_password_input');
        if (input.value) {
            navigator.clipboard.writeText(input.value).then(() => {
                if (typeof showToast === 'function') {
                    showToast("{{ __('permission-toolkit::messages.user_pwd_toast_copied') }}");
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
        const year = now.getFullYear();
        const month = String(now.getMonth() + 1).padStart(2, '0');
        const day = String(now.getDate()).padStart(2, '0');
        document.getElementById('modal_date_value').value = year + '-' + month + '-' + day + 'T00:00';
    }

    function addModalDays(days) {
        const d = new Date();
        d.setDate(d.getDate() + days);
        const year = d.getFullYear();
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        document.getElementById('modal_date_value').value = year + '-' + month + '-' + day + 'T00:00';
    }
</script>
@endpush
