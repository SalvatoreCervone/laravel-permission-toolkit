@extends('permission-toolkit::layouts.app')

@section('title', 'Diagnostic Simulator')

@section('content')
<div style="display: grid; grid-template-columns: 380px 1fr; gap: 1.5rem;">
    <!-- Simulator Form -->
    <div class="card" style="height: fit-content;">
        <div class="card-header">
            <h2 class="card-title">🔍 Configura Test</h2>
        </div>

        <form method="GET" action="{{ route('permission-toolkit.simulator') }}">
            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.35rem; font-weight: 500;">
                    1. Utente di Test
                </label>
                <select name="user_id" class="input-control" required>
                    <option value="">-- Seleziona Utente --</option>
                    @foreach($users as $u)
                        <option value="{{ $u->id }}" {{ $selectedUserId == $u->id ? 'selected' : '' }}>
                            {{ $u->name ?? $u->email }} (ID: {{ $u->id }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.35rem; font-weight: 500;">
                    2. Abilità / Permesso da Testare
                </label>
                <input 
                    type="text" 
                    name="ability" 
                    list="permissionsList" 
                    class="input-control" 
                    value="{{ $selectedAbility }}" 
                    placeholder="es. invoices.edit o view" 
                    required
                >
                <datalist id="permissionsList">
                    @foreach($permissions as $perm)
                        <option value="{{ $perm->name }}">
                    @endforeach
                </datalist>
            </div>

            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.35rem; font-weight: 500;">
                    3. Modello Target (Opzionale per Policy)
                </label>
                <input 
                    type="text" 
                    name="model_class" 
                    class="input-control" 
                    value="{{ $modelClass }}" 
                    placeholder="es. App\Models\Invoice"
                >
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.35rem; font-weight: 500;">
                    4. ID Record Modello (Opzionale)
                </label>
                <input 
                    type="number" 
                    name="model_id" 
                    class="input-control" 
                    value="{{ $modelId }}" 
                    placeholder="es. 42"
                >
            </div>

            <button type="submit" class="btn" style="width: 100%; justify-content: center;">
                ⚡ Esegui Simulazione
            </button>
        </form>
    </div>

    <!-- Diagnostic Breakdown Results -->
    <div>
        @if($simulationResult)
            <div class="card" style="border-left: 4px solid {{ $simulationResult['is_allowed'] ? 'var(--success)' : 'var(--danger)' }};">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <div>
                        <span class="badge {{ $simulationResult['is_allowed'] ? 'badge-success' : 'badge-danger' }}" style="font-size: 0.9rem; padding: 0.35rem 0.75rem;">
                            {{ $simulationResult['verdict'] }}
                        </span>
                        <div style="font-size: 1.1rem; font-weight: 600; margin-top: 0.5rem;">
                            {{ $simulationResult['reason'] }}
                        </div>
                    </div>
                    <div style="text-align: right; font-size: 0.8rem; color: var(--text-muted);">
                        Simulato alle: {{ \Carbon\Carbon::parse($simulationResult['timestamp'])->format('H:i:s') }}
                    </div>
                </div>

                <div style="background: rgba(255, 255, 255, 0.02); border: 1px solid var(--border); border-radius: 0.375rem; padding: 0.75rem 1rem; margin-bottom: 1.5rem; font-size: 0.85rem; display: flex; gap: 2rem;">
                    <div><strong>Utente:</strong> {{ $simulationResult['user']['name'] }} (ID: {{ $simulationResult['user']['id'] }})</div>
                    <div><strong>Ruoli Spatie:</strong> {{ implode(', ', $simulationResult['user']['roles']) ?: 'Nessun ruolo' }}</div>
                    <div><strong>Abilità:</strong> <code>{{ $simulationResult['ability'] }}</code></div>
                </div>

                <h3 style="font-size: 0.95rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); margin-bottom: 0.75rem;">
                    Tracciamento Passo-Passo (IAM Trace)
                </h3>

                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 50px;">#</th>
                                <th style="width: 200px;">Fase di Ispezione</th>
                                <th style="width: 100px; text-align: center;">Esito</th>
                                <th>Dettaglio Diagnostico</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($simulationResult['steps'] as $idx => $step)
                                <tr>
                                    <td style="color: var(--text-muted);">{{ $idx + 1 }}</td>
                                    <td style="font-weight: 500;">{{ $step['step'] }}</td>
                                    <td style="text-align: center;">
                                        @if($step['status'] === 'PASS')
                                            <span class="badge badge-success">PASS</span>
                                        @elseif($step['status'] === 'FAIL')
                                            <span class="badge badge-danger">FAIL</span>
                                        @else
                                            <span class="badge badge-warning">SKIP</span>
                                        @endif
                                    </td>
                                    <td style="color: var(--text-muted);">{{ $step['detail'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <div class="card" style="text-align: center; padding: 4rem 2rem; color: var(--text-muted);">
                <div style="font-size: 2.5rem; margin-bottom: 1rem;">🛡️</div>
                <h3 style="font-size: 1.1rem; color: var(--text-main); margin-bottom: 0.5rem;">Nessuna simulazione attiva</h3>
                <p style="font-size: 0.875rem; max-width: 500px; margin: 0 auto;">
                    Seleziona un utente e specifica un'abilità dal form a sinistra per diagnosticare passo-passo perché l'accesso viene autorizzato o negato con codice 403.
                </p>
            </div>
        @endif
    </div>
</div>
@endsection
