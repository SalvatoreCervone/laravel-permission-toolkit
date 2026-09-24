@extends('permission-toolkit::layouts.app')

@section('title', __('permission-toolkit::messages.sim_title'))

@section('content')
<div style="display: grid; grid-template-columns: 380px 1fr; gap: 1.5rem;">
    <!-- Simulator Form -->
    <div class="card" style="height: fit-content;">
        <div class="card-header">
            <h2 class="card-title">{{ __('permission-toolkit::messages.sim_config_title') }}</h2>
        </div>

        <form method="GET" action="{{ route('permission-toolkit.simulator') }}">
            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.35rem; font-weight: 500;">
                    {{ __('permission-toolkit::messages.sim_user_label') }}
                </label>
                <select name="user_id" class="input-control" required>
                    <option value="">{{ __('permission-toolkit::messages.sim_user_placeholder') }}</option>
                    @foreach($users as $u)
                        <option value="{{ $u->id }}" {{ $selectedUserId == $u->id ? 'selected' : '' }}>
                            {{ $u->name ?? $u->email }} (ID: {{ $u->id }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.35rem; font-weight: 500;">
                    {{ __('permission-toolkit::messages.sim_ability_label') }}
                </label>
                <input 
                    type="text" 
                    name="ability" 
                    list="permissionsList" 
                    class="input-control" 
                    value="{{ $selectedAbility }}" 
                    placeholder="{{ __('permission-toolkit::messages.sim_ability_placeholder') }}" 
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
                    {{ __('permission-toolkit::messages.sim_model_label') }}
                </label>
                <input 
                    type="text" 
                    name="model_class" 
                    class="input-control" 
                    value="{{ $modelClass }}" 
                    placeholder="{{ __('permission-toolkit::messages.sim_model_placeholder') }}"
                >
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.35rem; font-weight: 500;">
                    {{ __('permission-toolkit::messages.sim_id_label') }}
                </label>
                <input 
                    type="number" 
                    name="model_id" 
                    class="input-control" 
                    value="{{ $modelId }}" 
                    placeholder="{{ __('permission-toolkit::messages.sim_id_placeholder') }}"
                >
            </div>

            <button type="submit" class="btn" style="width: 100%; justify-content: center;">
                {{ __('permission-toolkit::messages.sim_btn_run') }}
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
                        {{ __('permission-toolkit::messages.sim_at', ['time' => \Carbon\Carbon::parse($simulationResult['timestamp'])->format('H:i:s')]) }}
                    </div>
                </div>

                <div style="background: rgba(255, 255, 255, 0.02); border: 1px solid var(--border); border-radius: 0.375rem; padding: 0.75rem 1rem; margin-bottom: 1.5rem; font-size: 0.85rem; display: flex; gap: 2rem;">
                    <div><strong>{{ __('permission-toolkit::messages.sim_info_user') }}</strong> {{ $simulationResult['user']['name'] }} (ID: {{ $simulationResult['user']['id'] }})</div>
                    <div><strong>{{ __('permission-toolkit::messages.sim_info_roles') }}</strong> {{ implode(', ', $simulationResult['user']['roles']) ?: __('permission-toolkit::messages.sim_info_no_roles') }}</div>
                    <div><strong>{{ __('permission-toolkit::messages.sim_info_ability') }}</strong> <code>{{ $simulationResult['ability'] }}</code></div>
                </div>

                <h3 style="font-size: 0.95rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); margin-bottom: 0.75rem;">
                    {{ __('permission-toolkit::messages.sim_trace_heading') }}
                </h3>

                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 50px;">#</th>
                                <th style="width: 200px;">{{ __('permission-toolkit::messages.sim_th_step') }}</th>
                                <th style="width: 100px; text-align: center;">{{ __('permission-toolkit::messages.sim_th_status') }}</th>
                                <th>{{ __('permission-toolkit::messages.sim_th_detail') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($simulationResult['steps'] as $idx => $step)
                                @php
                                    $stepKey = match($step['step']) {
                                        'User Identity' => 'sim_step_user_identity',
                                        'Super Admin Bypass' => 'sim_step_super_admin',
                                        'Direct Permission' => 'sim_step_direct_permission',
                                        'Role Permission Inheritance' => 'sim_step_role_inheritance',
                                        'Policy / Gate Evaluation' => 'sim_step_policy_gate',
                                        default => null,
                                    };
                                    $stepLabel = $stepKey ? __('permission-toolkit::messages.' . $stepKey) : $step['step'];
                                @endphp
                                <tr>
                                    <td style="color: var(--text-muted);">{{ $idx + 1 }}</td>
                                    <td style="font-weight: 500;">{{ $stepLabel }}</td>
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
                <h3 style="font-size: 1.1rem; color: var(--text-main); margin-bottom: 0.5rem;">{{ __('permission-toolkit::messages.sim_empty_title') }}</h3>
                <p style="font-size: 0.875rem; max-width: 500px; margin: 0 auto;">
                    {{ __('permission-toolkit::messages.sim_empty_desc') }}
                </p>
            </div>
        @endif
    </div>
</div>
@endsection
