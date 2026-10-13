@extends('permission-toolkit::layouts.app')

@section('title', __('permission-toolkit::messages.handover_templates_title'))

@push('styles')
<style>
    .template-card {
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: 0.5rem;
        padding: 1.25rem;
        transition: transform 0.15s ease, border-color 0.15s ease;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }
    .template-card:hover {
        border-color: var(--primary);
        background: var(--bg-card-hover);
    }
    .template-card.is-default {
        border-color: var(--primary);
        box-shadow: 0 0 0 1px var(--primary);
    }
    .modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.65);
        backdrop-filter: blur(2px);
        z-index: 9999;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1rem;
    }
    .modal-box {
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: 0.5rem;
        width: 100%;
        max-width: 680px;
        max-height: 90vh;
        overflow-y: auto;
        padding: 1.5rem;
        box-shadow: 0 20px 25px -5px rgba(0,0,0,0.5);
    }
</style>
@endpush

@section('content')
<div style="max-width: 1100px; margin: 0 auto;">
    @if(session('status'))
        <div style="background: rgba(16, 185, 129, 0.15); border: 1px solid #10b981; color: #6ee7b7; padding: 0.75rem 1.25rem; border-radius: 0.375rem; margin-bottom: 1.25rem; font-size: 0.9rem;">
            ✔ {{ session('status') }}
        </div>
    @endif

    @if($errors->any())
        <div style="background: rgba(239, 68, 68, 0.15); border: 1px solid #ef4444; color: #fca5a5; padding: 0.75rem 1.25rem; border-radius: 0.375rem; margin-bottom: 1.25rem; font-size: 0.9rem;">
            <div style="font-weight: 600; margin-bottom: 0.25rem;">{{ __('permission-toolkit::messages.error') }}</div>
            <ul style="margin: 0; padding-left: 1.25rem; font-size: 0.85rem;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card" style="margin-bottom: 1.5rem;">
        <div class="card-header" style="flex-wrap: wrap; gap: 1rem;">
            <div>
                <h1 class="card-title">{{ __('permission-toolkit::messages.handover_templates_heading') }}</h1>
                <p style="color: var(--text-muted); font-size: 0.85rem; margin-top: 0.25rem;">
                    {{ __('permission-toolkit::messages.handover_templates_subtitle') }}
                </p>
            </div>
            <div>
                <button type="button" class="btn" onclick="openCreateModal()">
                    ➕ {{ __('permission-toolkit::messages.handover_btn_new_template') }}
                </button>
            </div>
        </div>

        <div style="padding: 1.25rem;">
            @if($templates->isEmpty())
                <div style="text-align: center; padding: 3rem 1rem; color: var(--text-muted);">
                    <div style="font-size: 2.5rem; margin-bottom: 0.75rem;">📄</div>
                    <div style="font-weight: 600; font-size: 1rem; margin-bottom: 0.25rem; color: var(--text-main);">
                        {{ __('permission-toolkit::messages.handover_no_templates') }}
                    </div>
                    <p style="font-size: 0.85rem; max-width: 480px; margin: 0 auto 1.25rem;">
                        {{ __('permission-toolkit::messages.handover_no_templates_desc') }}
                    </p>
                    <button type="button" class="btn" onclick="openCreateModal()">
                        ➕ {{ __('permission-toolkit::messages.handover_btn_new_template') }}
                    </button>
                </div>
            @else
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 1rem;">
                    @foreach($templates as $tmpl)
                        <div class="template-card {{ $tmpl->is_default ? 'is-default' : '' }}">
                            <div>
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 0.5rem; margin-bottom: 0.5rem;">
                                    <div>
                                        @if($tmpl->application_name)
                                            <div style="font-size: 0.72rem; font-weight: 700; color: var(--primary); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.15rem;">
                                                🖥️ {{ $tmpl->application_name }}
                                            </div>
                                        @endif
                                        <h3 style="font-size: 1.05rem; font-weight: 700; margin: 0; color: var(--text-main);">
                                            {{ $tmpl->name }}
                                        </h3>
                                    </div>
                                    @if($tmpl->is_default)
                                        <span class="badge badge-success" style="font-size: 0.7rem; padding: 0.2rem 0.5rem; display: inline-flex; align-items: center; gap: 0.25rem;">
                                            ⭐ {{ __('permission-toolkit::messages.handover_badge_default') }}
                                        </span>
                                    @endif
                                </div>

                                @if($tmpl->company_name || $tmpl->department)
                                    <div style="font-size: 0.78rem; color: var(--accent-heading); margin-bottom: 0.5rem;">
                                        🏢 {{ $tmpl->company_name ?? '' }} 
                                        @if($tmpl->company_name && $tmpl->department) • @endif
                                        {{ $tmpl->department ?? '' }}
                                    </div>
                                @endif

                                <!-- Badges metadata -->
                                <div style="display: flex; gap: 0.35rem; flex-wrap: wrap; margin-bottom: 0.65rem;">
                                    @if($tmpl->reference_code)
                                        <span class="badge badge-info" style="font-size: 0.68rem; padding: 0.15rem 0.4rem;">
                                            REF: {{ $tmpl->reference_code }}
                                        </span>
                                    @endif
                                    @if($tmpl->status_label)
                                        <span class="badge badge-success" style="font-size: 0.68rem; padding: 0.15rem 0.4rem;">
                                            Status: {{ $tmpl->status_label }}
                                        </span>
                                    @endif
                                </div>

                                <!-- Box Firmatari -->
                                <div style="background: var(--bg-item); border: 1px solid var(--border); border-radius: 0.375rem; padding: 0.75rem; margin-bottom: 0.75rem; display: flex; flex-direction: column; gap: 0.5rem;">
                                    <div>
                                        <div style="font-size: 0.72rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.03em;">
                                            1° Firmatario (Direttore Utente):
                                        </div>
                                        <div style="font-weight: 600; font-size: 0.88rem; color: var(--text-main);">
                                            {{ $tmpl->signer_name }}
                                        </div>
                                        <div style="font-size: 0.75rem; color: var(--text-muted);">
                                            {{ $tmpl->signer_title }}
                                        </div>
                                    </div>

                                    @if($tmpl->second_signer_name)
                                        <div style="border-top: 1px dashed var(--border); padding-top: 0.4rem;">
                                            <div style="font-size: 0.72rem; color: var(--accent-heading); text-transform: uppercase; letter-spacing: 0.03em;">
                                                2° Firmatario (Altro Direttore / Incaricato):
                                            </div>
                                            <div style="font-weight: 600; font-size: 0.88rem; color: var(--text-main);">
                                                {{ $tmpl->second_signer_name }}
                                            </div>
                                            <div style="font-size: 0.75rem; color: var(--text-muted);">
                                                {{ $tmpl->second_signer_title ?? 'Incaricato' }}
                                            </div>
                                        </div>
                                    @endif
                                </div>

                                <div style="font-size: 0.75rem; color: var(--text-muted); line-height: 1.4; max-height: 3.5em; overflow: hidden; text-overflow: ellipsis; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; margin-bottom: 0.75rem;" title="{{ $tmpl->disclaimer_text }}">
                                    "{{ $tmpl->disclaimer_text }}"
                                </div>

                                @if($tmpl->footer_text)
                                    <div style="font-size: 0.7rem; color: var(--text-muted); font-style: italic; margin-bottom: 0.5rem;">
                                        📄 Footer: "{{ $tmpl->footer_text }}"
                                    </div>
                                @endif
                            </div>

                            <div style="display: flex; gap: 0.5rem; align-items: center; justify-content: flex-end; border-top: 1px solid var(--border); padding-top: 0.75rem;">
                                @if(! $tmpl->is_default)
                                    <form method="POST" action="{{ route('permission-toolkit.handover.templates.set-default', $tmpl->id) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-secondary" style="font-size: 0.75rem; padding: 0.3rem 0.6rem;" title="{{ __('permission-toolkit::messages.handover_btn_set_default') }}">
                                            ⭐ {{ __('permission-toolkit::messages.handover_btn_set_default') }}
                                        </button>
                                    </form>
                                @endif

                                <button type="button" class="btn btn-secondary" style="font-size: 0.75rem; padding: 0.3rem 0.6rem;" onclick='openEditModal(@json($tmpl))'>
                                    ✏️ {{ __('permission-toolkit::messages.btn_edit') ?? 'Modifica' }}
                                </button>

                                <form method="POST" action="{{ route('permission-toolkit.handover.templates.destroy', $tmpl->id) }}" onsubmit="return confirm('{{ addslashes(__('permission-toolkit::messages.handover_confirm_delete', ['name' => $tmpl->name])) }}');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-secondary" style="font-size: 0.75rem; padding: 0.3rem 0.6rem; color: #ef4444; border-color: rgba(239, 68, 68, 0.4);" title="{{ __('permission-toolkit::messages.btn_delete') }}">
                                        🗑️
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Modal Form Create/Edit -->
<div id="template-modal" class="modal-overlay" style="display: none;">
    <div class="modal-box">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem;">
            <h2 id="modal-title" style="font-size: 1.25rem; font-weight: 700; margin: 0; color: var(--text-main);">
                {{ __('permission-toolkit::messages.handover_modal_create_title') }}
            </h2>
            <button type="button" onclick="closeModal()" style="background: none; border: none; font-size: 1.25rem; color: var(--text-muted); cursor: pointer;">✕</button>
        </div>

        <form id="template-form" method="POST" action="{{ route('permission-toolkit.handover.templates.store') }}">
            @csrf
            <div id="method-container"></div>

            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                    <div>
                        <label style="display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 0.35rem; color: var(--text-main);">
                            {{ __('permission-toolkit::messages.handover_lbl_name') }} *
                        </label>
                        <input type="text" name="name" id="form-name" class="input-control" required placeholder="es. Direzione Commerciale">
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 0.35rem; color: var(--text-main);">
                            {{ __('permission-toolkit::messages.handover_lbl_application') }}
                        </label>
                        <input type="text" name="application_name" id="form-application" class="input-control" placeholder="es. ERP Gestionale, CRM Cloud">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                    <div>
                        <label style="display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 0.35rem; color: var(--text-main);">
                            {{ __('permission-toolkit::messages.handover_lbl_company') }}
                        </label>
                        <input type="text" name="company_name" id="form-company" class="input-control" placeholder="es. Acme S.p.A.">
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 0.35rem; color: var(--text-main);">
                            {{ __('permission-toolkit::messages.handover_lbl_dept') }}
                        </label>
                        <input type="text" name="department" id="form-department" class="input-control" placeholder="es. Reparto IT, Sales">
                    </div>
                </div>

                <!-- 1° Firmatario (Direttore Utente) -->
                <div style="background: var(--bg-item); border: 1px solid var(--border); border-radius: 0.375rem; padding: 0.85rem;">
                    <div style="font-size: 0.78rem; font-weight: 700; color: var(--accent-heading); margin-bottom: 0.5rem; text-transform: uppercase;">
                        👤 1° Firmatario (Direttore / Responsabile dell'Utente) *
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                        <div>
                            <label style="display: block; font-size: 0.78rem; font-weight: 600; margin-bottom: 0.3rem; color: var(--text-main);">
                                {{ __('permission-toolkit::messages.handover_lbl_signer_title') }} *
                            </label>
                            <input type="text" name="signer_title" id="form-signer-title" class="input-control" required placeholder="es. Il Responsabile della Sicurezza IT">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.78rem; font-weight: 600; margin-bottom: 0.3rem; color: var(--text-main);">
                                {{ __('permission-toolkit::messages.handover_lbl_signer_name') }} *
                            </label>
                            <input type="text" name="signer_name" id="form-signer-name" class="input-control" required placeholder="es. Ing. Roberto Mancini">
                        </div>
                    </div>
                </div>

                <!-- 2° Firmatario (Altro Direttore / Incaricato opzionale) -->
                <div style="background: var(--bg-item); border: 1px solid var(--border); border-radius: 0.375rem; padding: 0.85rem;">
                    <div style="font-size: 0.78rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.5rem; text-transform: uppercase;">
                        👥 2° Firmatario (Opzionale: CISO, DPO, Amm. Sistema o Altro Direttore)
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                        <div>
                            <label style="display: block; font-size: 0.78rem; font-weight: 600; margin-bottom: 0.3rem; color: var(--text-main);">
                                {{ __('permission-toolkit::messages.handover_lbl_second_signer_title') }}
                            </label>
                            <input type="text" name="second_signer_title" id="form-second-signer-title" class="input-control" placeholder="es. Data Protection Officer (DPO)">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.78rem; font-weight: 600; margin-bottom: 0.3rem; color: var(--text-main);">
                                {{ __('permission-toolkit::messages.handover_lbl_second_signer_name') }}
                            </label>
                            <input type="text" name="second_signer_name" id="form-second-signer-name" class="input-control" placeholder="es. Avv. Mario Rossi">
                        </div>
                    </div>
                </div>

                <!-- Metadati Intestazione (REF e Status) -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                    <div>
                        <label style="display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 0.35rem; color: var(--text-main);">
                            {{ __('permission-toolkit::messages.handover_lbl_ref') }}
                        </label>
                        <input type="text" name="reference_code" id="form-reference-code" class="input-control" placeholder="es. DOC-ACR (lascia vuoto per non mostrare)">
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 0.35rem; color: var(--text-main);">
                            {{ __('permission-toolkit::messages.handover_lbl_status') }}
                        </label>
                        <input type="text" name="status_label" id="form-status-label" class="input-control" placeholder="es. Valido / Conforme (lascia vuoto per non mostrare)">
                    </div>
                </div>

                <div>
                    <label style="display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 0.35rem; color: var(--text-main);">
                        {{ __('permission-toolkit::messages.handover_lbl_disclaimer') }} *
                    </label>
                    <textarea name="disclaimer_text" id="form-disclaimer" class="input-control" rows="4" required style="resize: vertical;"></textarea>
                    <span style="font-size: 0.72rem; color: var(--text-muted); margin-top: 0.2rem; display: block;">
                        {{ __('permission-toolkit::messages.handover_lbl_disclaimer_tip') }}
                    </span>
                </div>

                <div>
                    <label style="display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 0.35rem; color: var(--text-main);">
                        {{ __('permission-toolkit::messages.handover_lbl_notes') }}
                    </label>
                    <textarea name="notes" id="form-notes" class="input-control" rows="2" placeholder="Note facoltative o istruzioni aggiuntive..." style="resize: vertical;"></textarea>
                </div>

                <div>
                    <label style="display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 0.35rem; color: var(--text-main);">
                        {{ __('permission-toolkit::messages.handover_lbl_footer_text') }}
                    </label>
                    <input type="text" name="footer_text" id="form-footer-text" class="input-control" placeholder="es. Acme S.p.A. • Sistema Informativo Integrato (lascia vuoto per default dinamico)">
                </div>

                <div>
                    <label style="display: inline-flex; align-items: center; gap: 0.5rem; font-size: 0.85rem; cursor: pointer; color: var(--text-main);">
                        <input type="checkbox" name="is_default" value="1" id="form-default" style="accent-color: var(--primary);">
                        <span>{{ __('permission-toolkit::messages.handover_lbl_set_as_default') }}</span>
                    </label>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1.5rem; border-top: 1px solid var(--border); padding-top: 1rem;">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">
                    {{ __('permission-toolkit::messages.btn_cancel') }}
                </button>
                <button type="submit" class="btn">
                    {{ __('permission-toolkit::messages.btn_save') }}
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    const defaultDisclaimer = @json(config('permission-toolkit.handover.default_disclaimer'));
    const defaultCompany = @json(config('permission-toolkit.handover.default_company_name', config('app.name', 'Enterprise Corp')));
    const defaultSignerTitle = @json(config('permission-toolkit.handover.default_signer_title', 'Il Responsabile della Sicurezza IT'));
    const defaultSignerName = @json(config('permission-toolkit.handover.default_signer_name', 'Amministratore di Sistema'));

    function openCreateModal() {
        document.getElementById('modal-title').innerText = "{{ __('permission-toolkit::messages.handover_modal_create_title') }}";
        const form = document.getElementById('template-form');
        form.action = "{{ route('permission-toolkit.handover.templates.store') }}";
        document.getElementById('method-container').innerHTML = '';

        document.getElementById('form-name').value = '';
        document.getElementById('form-application').value = '';
        document.getElementById('form-company').value = defaultCompany;
        document.getElementById('form-department').value = '';
        document.getElementById('form-signer-title').value = defaultSignerTitle;
        document.getElementById('form-signer-name').value = defaultSignerName;
        document.getElementById('form-second-signer-title').value = '';
        document.getElementById('form-second-signer-name').value = '';
        document.getElementById('form-reference-code').value = 'DOC-ACR';
        document.getElementById('form-status-label').value = 'Valido / Conforme';
        document.getElementById('form-disclaimer').value = defaultDisclaimer;
        document.getElementById('form-notes').value = '';
        document.getElementById('form-footer-text').value = '';
        document.getElementById('form-default').checked = false;

        document.getElementById('template-modal').style.display = 'flex';
    }

    function openEditModal(tmpl) {
        document.getElementById('modal-title').innerText = "{{ __('permission-toolkit::messages.handover_modal_edit_title') }}";
        const form = document.getElementById('template-form');
        form.action = "{{ url(config('permission-toolkit.prefix', 'permission-manager') . '/handover-templates') }}/" + tmpl.id;
        document.getElementById('method-container').innerHTML = '@method("PUT")';

        document.getElementById('form-name').value = tmpl.name || '';
        document.getElementById('form-application').value = tmpl.application_name || '';
        document.getElementById('form-company').value = tmpl.company_name || '';
        document.getElementById('form-department').value = tmpl.department || '';
        document.getElementById('form-signer-title').value = tmpl.signer_title || '';
        document.getElementById('form-signer-name').value = tmpl.signer_name || '';
        document.getElementById('form-second-signer-title').value = tmpl.second_signer_title || '';
        document.getElementById('form-second-signer-name').value = tmpl.second_signer_name || '';
        document.getElementById('form-reference-code').value = tmpl.reference_code || '';
        document.getElementById('form-status-label').value = tmpl.status_label || '';
        document.getElementById('form-disclaimer').value = tmpl.disclaimer_text || '';
        document.getElementById('form-notes').value = tmpl.notes || '';
        document.getElementById('form-footer-text').value = tmpl.footer_text || '';
        document.getElementById('form-default').checked = !!tmpl.is_default;

        document.getElementById('template-modal').style.display = 'flex';
    }

    function closeModal() {
        document.getElementById('template-modal').style.display = 'none';
    }

    document.getElementById('template-modal').addEventListener('click', function(e) {
        if (e.target === this) closeModal();
    });
</script>
@endsection
