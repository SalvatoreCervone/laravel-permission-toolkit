@extends('permission-toolkit::layouts.app')

@section('title', 'Integrity Doctor')

@section('content')
<div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; margin-bottom: 1.5rem;">
    <div class="card" style="margin-bottom: 0;">
        <div style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase;">Ruoli a Database</div>
        <div style="font-size: 2rem; font-weight: 700; margin-top: 0.25rem;">{{ $report['summary']['total_roles'] }}</div>
    </div>
    <div class="card" style="margin-bottom: 0;">
        <div style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase;">Permessi a Database</div>
        <div style="font-size: 2rem; font-weight: 700; margin-top: 0.25rem;">{{ $report['summary']['total_permissions'] }}</div>
    </div>
    <div class="card" style="margin-bottom: 0;">
        <div style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase;">Stato Integrità</div>
        <div style="margin-top: 0.5rem;">
            @php
                $hasIssues = !empty($report['orphaned_pivot_records']) || !empty($report['guard_mismatches']);
            @endphp
            @if($hasIssues)
                <span class="badge badge-danger" style="font-size: 0.9rem; padding: 0.4rem 0.8rem;">ANOMALIE RILEVATE</span>
            @else
                <span class="badge badge-success" style="font-size: 0.9rem; padding: 0.4rem 0.8rem;">DATABASE INTEGRO ✔</span>
            @endif
        </div>
    </div>
</div>

<!-- Orphaned Pivots -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">1. Record Pivot Orfani</h2>
    </div>
    @if(empty($report['orphaned_pivot_records']))
        <div style="color: var(--success); font-size: 0.9rem;">
            ✔ Nessuna relazione orfana trovata nelle tabelle pivot Spatie.
        </div>
    @else
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Tabella</th>
                        <th>Problema Rilevato</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($report['orphaned_pivot_records'] as $orphan)
                        <tr>
                            <td><code>{{ $orphan['table'] }}</code></td>
                            <td style="color: var(--danger);">{{ $orphan['issue'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

<!-- Guard Mismatches -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">2. Discrepanze di Guard (Web vs API)</h2>
    </div>
    @if(empty($report['guard_mismatches']))
        <div style="color: var(--success); font-size: 0.9rem;">
            ✔ Tutte le guardie tra ruoli e permessi associati sono coerenti.
        </div>
    @else
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Ruolo</th>
                        <th>Permesso</th>
                        <th>Diagnosi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($report['guard_mismatches'] as $mismatch)
                        <tr>
                            <td><strong>{{ $mismatch['role'] }}</strong></td>
                            <td><code>{{ $mismatch['permission'] }}</code></td>
                            <td style="color: var(--danger);">{{ $mismatch['issue'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

<!-- Unused Permissions -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">3. Permessi Inutilizzati ({{ count($report['unused_permissions']) }})</h2>
        <span style="font-size: 0.8rem; color: var(--text-muted);">Non associati ad alcun ruolo o utente</span>
    </div>
    @if(empty($report['unused_permissions']))
        <div style="color: var(--success); font-size: 0.9rem;">
            ✔ Tutti i permessi a database risultano attivi e assegnati.
        </div>
    @else
        <div class="table-responsive" style="max-height: 250px;">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome Permesso</th>
                        <th>Guard</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($report['unused_permissions'] as $unused)
                        <tr>
                            <td style="color: var(--text-muted);">{{ $unused['id'] }}</td>
                            <td><code>{{ $unused['name'] }}</code></td>
                            <td><span class="badge badge-info">{{ $unused['guard_name'] }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

<!-- Empty Roles -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">4. Ruoli Vuoti ({{ count($report['empty_roles']) }})</h2>
        <span style="font-size: 0.8rem; color: var(--text-muted);">0 permessi e 0 utenti assegnati</span>
    </div>
    @if(empty($report['empty_roles']))
        <div style="color: var(--success); font-size: 0.9rem;">
            ✔ Nessun ruolo vuoto rilevato.
        </div>
    @else
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome Ruolo</th>
                        <th>Guard</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($report['empty_roles'] as $emptyRole)
                        <tr>
                            <td style="color: var(--text-muted);">{{ $emptyRole['id'] }}</td>
                            <td><strong>{{ $emptyRole['name'] }}</strong></td>
                            <td><span class="badge badge-info">{{ $emptyRole['guard_name'] }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
