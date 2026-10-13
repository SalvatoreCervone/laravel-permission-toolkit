<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('permission-toolkit::messages.handover_doc_title') }} - {{ \SalvatoreCervone\PermissionToolkit\PermissionToolkit::getUserDisplayName($user) }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
    <style>
        :root {
            --bg-page: #0f172a;
            --bg-sheet: #ffffff;
            --text-dark: #0f172a;
            --text-muted: #64748b;
            --border-color: #cbd5e1;
            --primary: #4f46e5;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            margin: 0;
            padding: 0;
            background-color: var(--bg-page);
            color: var(--text-dark);
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        .screen-toolbar {
            background: #1e293b;
            color: #f8fafc;
            padding: 0.75rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.2);
            flex-wrap: wrap;
        }

        .toolbar-group {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .btn-toolbar {
            background-color: var(--primary);
            color: #fff;
            border: none;
            padding: 0.45rem 0.9rem;
            border-radius: 0.375rem;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            transition: opacity 0.15s;
        }
        .btn-toolbar:hover { opacity: 0.9; }

        .btn-toolbar-secondary {
            background-color: #334155;
            color: #f1f5f9;
        }

        .sheet-wrapper {
            padding: 2rem 1rem;
            display: flex;
            justify-content: center;
        }

        .document-sheet {
            background: var(--bg-sheet);
            width: 100%;
            max-width: 800px;
            min-height: 1050px;
            padding: 2.5rem 3rem;
            box-shadow: 0 10px 25px rgba(0,0,0,0.3);
            border-radius: 4px;
            box-sizing: border-box;
            color: #0f172a;
            position: relative;
        }

        .doc-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 1rem;
            margin-bottom: 1.5rem;
        }

        .doc-company {
            font-size: 1.25rem;
            font-weight: 700;
            letter-spacing: -0.01em;
            color: #0f172a;
        }

        .doc-dept {
            font-size: 0.85rem;
            color: #475569;
            font-weight: 500;
            margin-top: 0.15rem;
        }

        .doc-meta {
            text-align: right;
            font-size: 0.78rem;
            color: #64748b;
            line-height: 1.4;
        }

        .doc-title-block {
            text-align: center;
            margin-bottom: 2rem;
        }

        .doc-title {
            font-size: 1.25rem;
            font-weight: 800;
            letter-spacing: 0.02em;
            text-transform: uppercase;
            color: #0f172a;
            margin: 0 0 0.4rem 0;
        }

        .doc-subtitle {
            font-size: 0.8rem;
            color: #475569;
            max-width: 600px;
            margin: 0 auto;
            line-height: 1.35;
        }

        .section-box {
            margin-bottom: 1.5rem;
        }

        .section-title {
            font-size: 0.82rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #1e293b;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 0.3rem;
            margin-bottom: 0.65rem;
            display: flex;
            justify-content: space-between;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.6rem 1.5rem;
            font-size: 0.85rem;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 0.75rem 1rem;
        }

        .info-item {
            display: flex;
            gap: 0.5rem;
        }

        .info-label {
            font-weight: 600;
            color: #475569;
            min-width: 110px;
        }

        .info-value {
            color: #0f172a;
            font-weight: 500;
        }

        .tag {
            display: inline-block;
            background: #e2e8f0;
            color: #1e293b;
            padding: 0.15rem 0.5rem;
            border-radius: 3px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-right: 0.35rem;
            margin-bottom: 0.35rem;
            border: 1px solid #cbd5e1;
        }

        .tag-role {
            background: #e0e7ff;
            color: #3730a3;
            border-color: #c7d2fe;
        }

        .table-doc {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.8rem;
            margin-top: 0.4rem;
        }

        .table-doc th {
            background: #f1f5f9;
            color: #334155;
            text-align: left;
            padding: 0.4rem 0.6rem;
            font-weight: 600;
            border: 1px solid #cbd5e1;
            font-size: 0.75rem;
            text-transform: uppercase;
        }

        .table-doc td {
            padding: 0.35rem 0.6rem;
            border: 1px solid #e2e8f0;
            color: #1e293b;
            line-height: 1.3;
        }

        .disclaimer-box {
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-left: 4px solid var(--primary);
            border-radius: 4px;
            padding: 0.85rem 1rem;
            font-size: 0.8rem;
            line-height: 1.5;
            color: #334155;
            text-align: justify;
            margin-top: 0.5rem;
        }

        .signatures-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-top: 2.5rem;
            page-break-inside: avoid;
        }

        .signature-card {
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 1rem;
            background: #ffffff;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 140px;
        }

        .signature-header {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #475569;
            margin-bottom: 0.35rem;
        }

        .signature-signer {
            font-size: 0.88rem;
            font-weight: 600;
            color: #0f172a;
        }

        .signature-title {
            font-size: 0.75rem;
            color: #64748b;
        }

        .signature-line-block {
            margin-top: 2.5rem;
            border-top: 1px dashed #94a3b8;
            padding-top: 0.35rem;
            display: flex;
            justify-content: space-between;
            font-size: 0.72rem;
            color: #64748b;
        }

        .doc-footer {
            margin-top: 2rem;
            padding-top: 0.75rem;
            border-top: 1px solid #e2e8f0;
            font-size: 0.7rem;
            color: #94a3b8;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        @media print {
            body {
                background: #ffffff !important;
                color: #000000 !important;
            }
            .screen-toolbar {
                display: none !important;
            }
            .sheet-wrapper {
                padding: 0 !important;
            }
            .document-sheet {
                box-shadow: none !important;
                padding: 0 !important;
                max-width: 100% !important;
                border-radius: 0 !important;
            }
            .disclaimer-box {
                border-left-color: #000000 !important;
                background: #f8fafc !important;
            }
            .info-grid {
                background: #f8fafc !important;
            }
            @page {
                size: A4 portrait;
                margin: 1.5cm 1.5cm 1.5cm 1.5cm;
            }
        }
    </style>
</head>
<body>

    <!-- Screen Toolbar (Hidden during print) -->
    <div class="screen-toolbar no-print">
        <div class="toolbar-group">
            <a href="{{ route('permission-toolkit.users.edit', $user->id) }}" class="btn-toolbar btn-toolbar-secondary">
                ← {{ __('permission-toolkit::messages.handover_back_to_user') }}
            </a>
            <div style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.85rem;">
                <span>{{ __('permission-toolkit::messages.handover_select_template') }}:</span>
                <select onchange="window.location.search = '?template_id=' + this.value" style="background: #0f172a; color: #fff; border: 1px solid #475569; padding: 0.35rem 0.6rem; border-radius: 0.375rem; font-size: 0.85rem;">
                    @foreach($templates as $tmpl)
                        <option value="{{ $tmpl->id }}" {{ $template && $template->id === $tmpl->id ? 'selected' : '' }}>
                            {{ $tmpl->name }} ({{ $tmpl->signer_name }}) {{ $tmpl->is_default ? '⭐' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <a href="{{ route('permission-toolkit.handover.templates.index') }}" class="btn-toolbar btn-toolbar-secondary" style="font-size: 0.8rem;">
                ⚙️ {{ __('permission-toolkit::messages.nav_handover') }}
            </a>
        </div>

        <div class="toolbar-group">
            <button type="button" onclick="window.print()" class="btn-toolbar">
                🖨️ {{ __('permission-toolkit::messages.handover_btn_print') }}
            </button>
        </div>
    </div>

    <!-- Printable A4 Document Sheet -->
    <div class="sheet-wrapper">
        <div class="document-sheet">
            <!-- Header -->
            <div class="doc-header">
                <div>
                    @if(!empty($template->application_name))
                        <div style="font-size: 0.8rem; font-weight: 700; color: var(--primary); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.25rem;">
                            🖥️ {{ $template->application_name }}
                        </div>
                    @endif
                    <div class="doc-company">
                        {{ $template->company_name ?? config('permission-toolkit.handover.default_company_name', config('app.name', 'Enterprise Corp')) }}
                    </div>
                    @if($template->department)
                        <div class="doc-dept">
                            {{ $template->department }}
                        </div>
                    @endif
                </div>
                <div class="doc-meta">
                    @if(!empty($template->reference_code))
                        <div><strong>REF:</strong> {{ $template->reference_code }}-{{ str_pad($user->id, 5, '0', STR_PAD_LEFT) }}-{{ date('Ymd') }}</div>
                    @endif
                    <div><strong>{{ __('permission-toolkit::messages.handover_doc_date') }}:</strong> {{ now()->format('d/m/Y H:i') }}</div>
                    @if(!empty($template->status_label))
                        <div><strong>Status:</strong> {{ $template->status_label }}</div>
                    @endif
                </div>
            </div>

            <!-- Title -->
            <div class="doc-title-block">
                <h1 class="doc-title">{{ __('permission-toolkit::messages.handover_doc_title') }}</h1>
                <div class="doc-subtitle">
                    {{ __('permission-toolkit::messages.handover_doc_subtitle') }}
                </div>
            </div>

            <!-- User Info Box -->
            <div class="section-box">
                <div class="section-title">
                    <span>1. {{ __('permission-toolkit::messages.handover_sec_user') }}</span>
                    <span style="font-size: 0.72rem; color: #64748b; font-weight: normal;">ID #{{ $user->id }}</span>
                </div>
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">{{ __('permission-toolkit::messages.handover_lbl_fullname') }}:</span>
                        <span class="info-value">{{ \SalvatoreCervone\PermissionToolkit\PermissionToolkit::getUserDisplayName($user) }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">{{ __('permission-toolkit::messages.users_th_email') }}:</span>
                        <span class="info-value">{{ $user->email ?? 'N/A' }}</span>
                    </div>
                    @if(!empty($template->application_name))
                        <div class="info-item">
                            <span class="info-label">{{ __('permission-toolkit::messages.handover_lbl_application') }}:</span>
                            <span class="info-value" style="font-weight: 600; color: #4338ca;">{{ $template->application_name }}</span>
                        </div>
                    @endif
                    <div class="info-item">
                        <span class="info-label">{{ __('permission-toolkit::messages.handover_lbl_account_created') }}:</span>
                        <span class="info-value">{{ $user->created_at ? $user->created_at->format('d/m/Y') : 'N/A' }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">{{ __('permission-toolkit::messages.handover_lbl_account_activated') }}:</span>
                        <span class="info-value" style="font-weight: 600;">{{ $user->updated_at ? $user->updated_at->format('d/m/Y H:i') : ($user->created_at ? $user->created_at->format('d/m/Y') : now()->format('d/m/Y')) }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">{{ __('permission-toolkit::messages.users_th_status') }}:</span>
                        <span class="info-value" style="color: #059669; font-weight: 700;">● {{ __('permission-toolkit::messages.users_status_active') }}</span>
                    </div>
                </div>
            </div>

            <!-- Assigned Roles -->
            <div class="section-box">
                <div class="section-title">
                    <span>2. {{ __('permission-toolkit::messages.handover_sec_roles') }} ({{ $user->roles->count() }})</span>
                </div>
                <div style="margin-top: 0.35rem;">
                    @forelse($user->roles as $role)
                        <span class="tag tag-role">
                            🛡️ {{ $role->name }} <small style="opacity: 0.75;">({{ $role->guard_name }})</small>
                        </span>
                    @empty
                        <span style="font-size: 0.85rem; color: #64748b; font-style: italic;">
                            {{ __('permission-toolkit::messages.users_no_roles') }}
                        </span>
                    @endforelse
                </div>
            </div>

            <!-- Permissions Breakdown -->
            <div class="section-box">
                <div class="section-title">
                    <span>3. {{ __('permission-toolkit::messages.handover_sec_permissions') }}</span>
                </div>

                <!-- Direct Permissions -->
                @if($directPermissions->isNotEmpty())
                    <div style="font-size: 0.78rem; font-weight: 700; color: #047857; margin-bottom: 0.3rem;">
                        ● {{ __('permission-toolkit::messages.handover_sec_direct_perms') }} ({{ $directPermissions->count() }})
                    </div>
                    <table class="table-doc" style="margin-bottom: 0.75rem;">
                        <thead>
                            <tr>
                                <th style="width: 50%;">{{ __('permission-toolkit::messages.handover_th_perm_name') }}</th>
                                <th style="width: 25%;">Guard</th>
                                <th style="width: 25%;">{{ __('permission-toolkit::messages.handover_th_type') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($directPermissions as $perm)
                                <tr>
                                    <td><strong style="font-family: monospace;">{{ $perm->name }}</strong></td>
                                    <td>{{ $perm->guard_name }}</td>
                                    <td><span style="color: #047857; font-weight: 600;">{{ __('permission-toolkit::messages.users_has_perm_direct') }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif

                <!-- Inherited Permissions -->
                <div style="font-size: 0.78rem; font-weight: 700; color: #3730a3; margin-bottom: 0.3rem; margin-top: 0.5rem;">
                    ● {{ __('permission-toolkit::messages.handover_sec_inherited_perms') }} ({{ $inheritedPermissions->count() }})
                </div>
                @if($inheritedPermissions->isNotEmpty())
                    <table class="table-doc">
                        <thead>
                            <tr>
                                <th style="width: 45%;">{{ __('permission-toolkit::messages.handover_th_perm_name') }}</th>
                                <th style="width: 15%;">Guard</th>
                                <th style="width: 40%;">{{ __('permission-toolkit::messages.handover_th_inherited_via') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($inheritedPermissions as $item)
                                <tr>
                                    <td><strong style="font-family: monospace;">{{ $item['permission']->name }}</strong></td>
                                    <td>{{ $item['permission']->guard_name }}</td>
                                    <td>
                                        @foreach($item['roles'] as $rName)
                                            <span class="tag" style="font-size: 0.7rem; padding: 0.1rem 0.35rem;">{{ $rName }}</span>
                                        @endforeach
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div style="font-size: 0.8rem; color: #64748b; font-style: italic; padding: 0.4rem 0;">
                        {{ __('permission-toolkit::messages.handover_no_inherited_perms') }}
                    </div>
                @endif
            </div>

            <!-- Disclaimer & Legal Text -->
            <div class="section-box" style="page-break-inside: avoid;">
                <div class="section-title">
                    <span>4. {{ __('permission-toolkit::messages.handover_sec_disclaimer') }}</span>
                </div>
                <div class="disclaimer-box">
                    {!! nl2br(e($template->disclaimer_text)) !!}
                </div>
                @if($template->notes)
                    <div style="font-size: 0.75rem; color: #64748b; margin-top: 0.4rem; font-style: italic;">
                        <strong>Note:</strong> {{ $template->notes }}
                    </div>
                @endif
            </div>

            <!-- Signatures Block (Dual or Triple) -->
            @php
                $hasSecondSigner = !empty($template->second_signer_name);
            @endphp
            <div class="signatures-grid" style="{{ $hasSecondSigner ? 'grid-template-columns: repeat(3, 1fr); gap: 1rem;' : '' }}">
                <!-- 1. Direttore Utente / Responsabile -->
                <div class="signature-card">
                    <div>
                        <div class="signature-header">
                            {{ __('permission-toolkit::messages.handover_sig_management_title') }}
                        </div>
                        <div class="signature-signer">
                            {{ $template->signer_name }}
                        </div>
                        <div class="signature-title">
                            {{ $template->signer_title }}
                        </div>
                    </div>
                    <div class="signature-line-block">
                        <span>{{ __('permission-toolkit::messages.handover_sig_sign') }}</span>
                        <span>{{ __('permission-toolkit::messages.handover_sig_date') }} ____/____/________</span>
                    </div>
                </div>

                @if($hasSecondSigner)
                    <!-- 2. Altro Direttore / Incaricato Firmatario (CISO, DPO, Amm. Sistema) -->
                    <div class="signature-card">
                        <div>
                            <div class="signature-header">
                                {{ __('permission-toolkit::messages.handover_sig_second_management_title') }}
                            </div>
                            <div class="signature-signer">
                                {{ $template->second_signer_name }}
                            </div>
                            <div class="signature-title">
                                {{ $template->second_signer_title ?? __('permission-toolkit::messages.handover_sig_second_default_title') }}
                            </div>
                        </div>
                        <div class="signature-line-block">
                            <span>{{ __('permission-toolkit::messages.handover_sig_sign') }}</span>
                            <span>{{ __('permission-toolkit::messages.handover_sig_date') }} ____/____/________</span>
                        </div>
                    </div>
                @endif

                <!-- 3. Utente Assegnatario per Ricevuta -->
                <div class="signature-card">
                    <div>
                        <div class="signature-header">
                            {{ __('permission-toolkit::messages.handover_sig_user_title') }}
                        </div>
                        <div class="signature-signer">
                            {{ \SalvatoreCervone\PermissionToolkit\PermissionToolkit::getUserDisplayName($user) }}
                        </div>
                        <div class="signature-title">
                            {{ __('permission-toolkit::messages.handover_sig_user_role') }}
                        </div>
                    </div>
                    <div class="signature-line-block">
                        <span>{{ __('permission-toolkit::messages.handover_sig_sign_receipt') }}</span>
                        <span>{{ __('permission-toolkit::messages.handover_sig_date') }} ____/____/________</span>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="doc-footer">
                <div>
                    @if(!empty($template->footer_text))
                        {{ $template->footer_text }}
                    @else
                        {{ ($template->company_name ?? config('permission-toolkit.handover.default_company_name', config('app.name', 'Enterprise Corp'))) }} • {{ !empty($template->application_name) ? $template->application_name . ' • ' : '' }}Compliance & Access Governance System
                    @endif
                </div>
                <div>
                    Page 1 / 1
                </div>
            </div>
        </div>
    </div>

</body>
</html>
