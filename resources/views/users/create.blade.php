@extends('permission-toolkit::layouts.app')

@section('title', __('permission-toolkit::messages.user_create_title'))

@section('content')
<div style="max-width: 900px; margin: 0 auto;">
    <!-- Breadcrumb & Header -->
    <div style="margin-bottom: 1.5rem;">
        <a href="{{ route('permission-toolkit.users.index') }}" style="color: var(--accent-heading); text-decoration: none; font-size: 0.85rem; font-weight: 500;">
            {{ __('permission-toolkit::messages.user_create_back') }}
        </a>
        <h1 style="font-size: 1.5rem; font-weight: 700; margin-top: 0.25rem;">
            {{ __('permission-toolkit::messages.user_create_heading') }}
        </h1>
        <p style="color: var(--text-muted); font-size: 0.85rem; margin-top: 0.2rem;">
            {{ __('permission-toolkit::messages.user_create_subtitle') }}
        </p>
    </div>

    @if($errors->any())
        <div style="background: rgba(239, 68, 68, 0.12); border: 1px solid var(--danger); color: var(--danger); padding: 1rem 1.25rem; border-radius: 0.375rem; margin-bottom: 1.5rem; font-size: 0.9rem;">
            <div style="font-weight: 700; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.5rem;">
                <span>⚠️</span> {{ __('permission-toolkit::messages.user_edit_errors_title') }}
            </div>
            <ul style="margin: 0; padding-left: 1.25rem; font-size: 0.85rem; line-height: 1.5;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('permission-toolkit.users.store') }}">
        @csrf

        <!-- Section 1: User Account Details -->
        <div class="card">
            <div class="card-header">
                <div>
                    <h2 class="card-title">{{ __('permission-toolkit::messages.user_create_sec_info') }}</h2>
                    <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.2rem;">
                        {{ __('permission-toolkit::messages.user_create_sec_info_desc') }}
                    </p>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.25rem;">
                @foreach($fields as $fieldName => $field)
                    @php
                        $fieldType = $field['type'] ?? 'text';
                        $fieldLabel = $field['label'] ?? ucwords(str_replace(['_', '-'], ' ', $fieldName));
                        $fieldPlaceholder = $field['placeholder'] ?? '';
                        $fieldRules = (array) ($field['rules'] ?? []);
                        $isRequired = in_array('required', $fieldRules) || (is_string($field['rules'] ?? '') && str_contains($field['rules'], 'required'));
                    @endphp

                    <div>
                        <label for="field_{{ $fieldName }}" style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.4rem; color: var(--text-main);">
                            {{ $fieldLabel }}
                            @if($isRequired)
                                <span style="color: var(--danger);">*</span>
                            @endif
                        </label>

                        @if($fieldType === 'select')
                            <select 
                                name="{{ $fieldName }}" 
                                id="field_{{ $fieldName }}" 
                                class="input-control" 
                                style="width: 100%;"
                                {{ $isRequired ? 'required' : '' }}
                            >
                                <option value="">-- Seleziona --</option>
                                @foreach($field['options'] ?? [] as $optValue => $optLabel)
                                    <option value="{{ $optValue }}" {{ old($fieldName) == $optValue ? 'selected' : '' }}>
                                        {{ $optLabel }}
                                    </option>
                                @endforeach
                            </select>
                        @else
                            <input 
                                type="{{ $fieldType }}" 
                                name="{{ $fieldName }}" 
                                id="field_{{ $fieldName }}" 
                                class="input-control" 
                                value="{{ old($fieldName) }}" 
                                placeholder="{{ $fieldPlaceholder }}"
                                style="width: 100%;"
                                {{ $isRequired ? 'required' : '' }}
                            >
                        @endif

                        @error($fieldName)
                            <div style="color: var(--danger); font-size: 0.78rem; margin-top: 0.3rem;">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>
                @endforeach
            </div>

            <!-- Password Field with Instant Generator -->
            @if($requirePassword)
                <div style="margin-top: 1.5rem; padding-top: 1.25rem; border-top: 1px solid var(--border);">
                    <label for="userPasswordInput" style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.4rem; color: var(--text-main);">
                        {{ __('permission-toolkit::messages.user_create_password') }} <span style="color: var(--danger);">*</span>
                    </label>

                    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; align-items: center;">
                        <div style="flex: 1; min-width: 220px; position: relative;">
                            <input 
                                type="password" 
                                name="password" 
                                id="userPasswordInput" 
                                class="input-control" 
                                placeholder="Minimo 6 caratteri..."
                                required
                                autocomplete="new-password"
                                style="width: 100%; font-family: monospace; font-size: 0.95rem; letter-spacing: 0.05em;"
                            >
                        </div>

                        <!-- Generator Action Buttons -->
                        <button type="button" class="btn btn-secondary" onclick="generateUserPassword()" title="{{ __('permission-toolkit::messages.user_create_generate_pwd') }}">
                            {{ __('permission-toolkit::messages.user_create_generate_pwd') }}
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="togglePasswordVisibility()" title="{{ __('permission-toolkit::messages.user_create_toggle_pwd') }}">
                            {{ __('permission-toolkit::messages.user_create_toggle_pwd') }}
                        </button>
                        <button type="button" id="copyPasswordBtn" class="btn btn-secondary" onclick="copyPasswordToClipboard()" title="{{ __('permission-toolkit::messages.user_create_copy_pwd') }}">
                            {{ __('permission-toolkit::messages.user_create_copy_pwd') }}
                        </button>
                    </div>

                    <div id="passwordToastMessage" style="display: none; margin-top: 0.5rem; font-size: 0.8rem; color: var(--success); font-weight: 600;">
                        ✔ {{ __('permission-toolkit::messages.user_create_copied') }}
                    </div>

                    <p style="color: var(--text-muted); font-size: 0.78rem; margin-top: 0.45rem;">
                        {{ __('permission-toolkit::messages.user_create_pwd_hint') }}
                    </p>

                    @error('password')
                        <div style="color: var(--danger); font-size: 0.78rem; margin-top: 0.3rem;">
                            {{ $message }}
                        </div>
                    @enderror
                </div>
            @endif
        </div>

        <!-- Section 2: Spatie Role Assignment -->
        @if($assignRoles)
            <div class="card">
                <div class="card-header">
                    <div>
                        <h2 class="card-title">{{ __('permission-toolkit::messages.user_create_sec_roles') }}</h2>
                        <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.2rem;">
                            {{ __('permission-toolkit::messages.user_create_sec_roles_desc') }}
                        </p>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 0.85rem;">
                    @forelse($roles as $role)
                        @php
                            $isPreChecked = is_array(old('roles')) && in_array($role->id, old('roles'));
                        @endphp
                        <label class="item-card {{ $isPreChecked ? 'is-assigned' : '' }}" style="cursor: pointer; user-select: none;">
                            <input 
                                type="checkbox" 
                                name="roles[]" 
                                value="{{ $role->id }}" 
                                {{ $isPreChecked ? 'checked' : '' }}
                                style="width: 1.15rem; height: 1.15rem; accent-color: var(--primary); flex-shrink: 0;"
                                onchange="this.closest('.item-card').classList.toggle('is-assigned', this.checked);"
                            >
                            <div style="min-width: 0; flex: 1;">
                                <div style="font-weight: 600; font-size: 0.9rem; word-break: break-word; overflow-wrap: anywhere; line-height: 1.3;" title="{{ $role->name }}">
                                    {{ $role->name }}
                                </div>
                                <div style="font-size: 0.7rem; color: var(--text-muted); margin-top: 0.2rem;">
                                    {{ $role->guard_name }} • {{ __('permission-toolkit::messages.user_edit_perms_count', ['count' => $role->permissions->count()]) }}
                                </div>
                            </div>
                        </label>
                    @empty
                        <div style="color: var(--text-muted); font-size: 0.85rem;">
                            {{ __('permission-toolkit::messages.user_create_no_roles') }}
                        </div>
                    @endforelse
                </div>
            </div>
        @endif

        <!-- Action Buttons -->
        <div style="display: flex; gap: 0.75rem; justify-content: flex-end; align-items: center; margin-top: 1.5rem; margin-bottom: 2rem;">
            <a href="{{ route('permission-toolkit.users.index') }}" class="btn btn-secondary">
                {{ __('permission-toolkit::messages.user_create_btn_cancel') }}
            </a>
            <button type="submit" class="btn" style="background-color: var(--success); font-weight: 600; padding: 0.6rem 1.5rem;">
                <span>✔</span> {{ __('permission-toolkit::messages.user_create_btn_submit') }}
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
    function generateUserPassword() {
        const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789!@#$%&*';
        let pass = '';
        const array = new Uint32Array(14);
        window.crypto.getRandomValues(array);
        for (let i = 0; i < 14; i++) {
            pass += chars[array[i] % chars.length];
        }

        const input = document.getElementById('userPasswordInput');
        input.value = pass;
        input.type = 'text'; // Make visible immediately so user can read it

        showPasswordToast("✔ Password generata!");
    }

    function togglePasswordVisibility() {
        const input = document.getElementById('userPasswordInput');
        input.type = input.type === 'password' ? 'text' : 'password';
    }

    function copyPasswordToClipboard() {
        const input = document.getElementById('userPasswordInput');
        if (input.value) {
            navigator.clipboard.writeText(input.value).then(() => {
                showPasswordToast("✔ {{ __('permission-toolkit::messages.user_create_copied') }}");
            });
        }
    }

    function showPasswordToast(msg) {
        const toast = document.getElementById('passwordToastMessage');
        if (toast) {
            toast.textContent = msg;
            toast.style.display = 'block';
            setTimeout(() => {
                toast.style.display = 'none';
            }, 3000);
        }
    }
</script>
@endpush
@endsection
