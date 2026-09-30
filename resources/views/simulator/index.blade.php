@extends('permission-toolkit::layouts.app')

@section('title', __('permission-toolkit::messages.sim_title'))

@section('content')
<!-- Mode Switcher Tabs -->
<div style="display: flex; gap: 0.5rem; margin-bottom: 1.5rem; border-bottom: 1px solid var(--border); padding-bottom: 0.75rem; flex-wrap: wrap;">
    <a href="{{ route('permission-toolkit.simulator', ['mode' => 'forward']) }}" 
       class="btn {{ ($mode ?? 'forward') !== 'reverse' ? '' : 'btn-secondary' }}" 
       style="text-decoration: none; font-size: 0.85rem; padding: 0.45rem 1rem;">
        {{ __('permission-toolkit::messages.sim_tab_forward') }}
    </a>
    <a href="{{ route('permission-toolkit.simulator', ['mode' => 'reverse']) }}" 
       class="btn {{ ($mode ?? '') === 'reverse' ? '' : 'btn-secondary' }}" 
       style="text-decoration: none; font-size: 0.85rem; padding: 0.45rem 1rem;">
        {{ __('permission-toolkit::messages.sim_tab_reverse') }}
    </a>
</div>

@if(($mode ?? 'forward') === 'reverse')
    <!-- ==================== REVERSE LOOKUP MODE ==================== -->
    <div style="display: grid; grid-template-columns: 380px 1fr; gap: 1.5rem; align-items: start;">
        <!-- Reverse Configuration Form -->
        <div class="card" style="height: fit-content;">
            <div class="card-header">
                <h2 class="card-title">{{ __('permission-toolkit::messages.sim_reverse_title') }}</h2>
            </div>
            <p style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 1.25rem;">
                {{ __('permission-toolkit::messages.sim_reverse_desc') }}
            </p>

            <form method="GET" action="{{ route('permission-toolkit.simulator') }}">
                <input type="hidden" name="mode" value="reverse">

                <div style="margin-bottom: 1.25rem;">
                    <label style="display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.35rem; font-weight: 500;">
                        {{ __('permission-toolkit::messages.sim_reverse_type_label') }}
                    </label>
                    <div style="display: flex; gap: 1rem; align-items: center;">
                        <label style="font-size: 0.85rem; display: flex; align-items: center; gap: 0.35rem; cursor: pointer;">
                            <input type="radio" name="reverse_type" value="permission" {{ ($reverseType ?? 'permission') === 'permission' ? 'checked' : '' }} onchange="updateDatalist(this.value)">
                            {{ __('permission-toolkit::messages.sim_reverse_type_perm') }}
                        </label>
                        <label style="font-size: 0.85rem; display: flex; align-items: center; gap: 0.35rem; cursor: pointer;">
                            <input type="radio" name="reverse_type" value="role" {{ ($reverseType ?? '') === 'role' ? 'checked' : '' }} onchange="updateDatalist(this.value)">
                            {{ __('permission-toolkit::messages.sim_reverse_type_role') }}
                        </label>
                    </div>
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.35rem; font-weight: 500;">
                        {{ __('permission-toolkit::messages.sim_reverse_target_label') }}
                    </label>
                    <input 
                        type="text" 
                        name="reverse_target" 
                        id="reverseTargetInput"
                        list="reverseOptionsList" 
                        class="input-control" 
                        value="{{ $reverseTarget ?? '' }}" 
                        placeholder="{{ __('permission-toolkit::messages.sim_reverse_target_placeholder') }}" 
                        required
                    >
                    <datalist id="reverseOptionsList">
                        @if(($reverseType ?? 'permission') === 'role')
                            @foreach($roles as $r)
                                <option value="{{ $r->name }}">
                            @endforeach
                        @else
                            @foreach($permissions as $p)
                                <option value="{{ $p->name }}">
                            @endforeach
                        @endif
                    </datalist>
                </div>

                <button type="submit" class="btn" style="width: 100%; justify-content: center;">
                    {{ __('permission-toolkit::messages.sim_btn_analyze') }}
                </button>
            </form>
        </div>

        <!-- Reverse Diagnostics Results -->
        <div>
            @if(isset($reverseResult))
                <div class="card" style="border-left: 4px solid var(--primary);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 0.5rem;">
                        <div>
                            <span class="badge badge-info" style="font-size: 0.75rem; text-transform: uppercase;">
                                {{ $reverseResult['type'] === 'role' ? __('permission-toolkit::messages.sim_reverse_type_role') : __('permission-toolkit::messages.sim_reverse_type_perm') }}
                            </span>
                            <h2 style="font-size: 1.25rem; font-weight: 600; margin-top: 0.35rem;">
                                {{ __('permission-toolkit::messages.sim_reverse_results_title', ['target' => $reverseResult['target']]) }}
                            </h2>
                        </div>
                        <div>
                            @if($reverseResult['type'] === 'role')
                                <a href="{{ route('permission-toolkit.users.index', ['role' => $reverseResult['target']]) }}" class="btn btn-secondary" style="font-size: 0.8rem;">
                                    {{ __('permission-toolkit::messages.sim_reverse_view_in_users') }}
                                </a>
                            @else
                                <a href="{{ route('permission-toolkit.users.index', ['permission' => $reverseResult['target']]) }}" class="btn btn-secondary" style="font-size: 0.8rem;">
                                    {{ __('permission-toolkit::messages.sim_reverse_view_in_users') }}
                                </a>
                            @endif
                        </div>
                    </div>

                    <!-- Stats KPI Grid -->
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
                        <div style="background: var(--bg-item); border: 1px solid var(--border); border-radius: 0.375rem; padding: 0.75rem 1rem; text-align: center;">
                            <div style="font-size: 1.4rem; font-weight: 700; color: var(--primary);">{{ $reverseResult['stats']['total'] }}</div>
                            <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.2rem;">{{ __('permission-toolkit::messages.sim_stat_authorized') }}</div>
                        </div>

                        <div style="background: var(--bg-item); border: 1px solid var(--border); border-radius: 0.375rem; padding: 0.75rem 1rem; text-align: center;">
                            <div style="font-size: 1.4rem; font-weight: 700; color: #10b981;">{{ $reverseResult['stats']['direct'] }}</div>
                            <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.2rem;">{{ __('permission-toolkit::messages.sim_stat_direct') }}</div>
                        </div>

                        <div style="background: var(--bg-item); border: 1px solid var(--border); border-radius: 0.375rem; padding: 0.75rem 1rem; text-align: center;">
                            <div style="font-size: 1.4rem; font-weight: 700; color: #6366f1;">{{ $reverseResult['stats']['role'] }}</div>
                            <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.2rem;">{{ __('permission-toolkit::messages.sim_stat_role') }}</div>
                        </div>

                        <div style="background: var(--bg-item); border: 1px solid var(--border); border-radius: 0.375rem; padding: 0.75rem 1rem; text-align: center;">
                            <div style="font-size: 1.4rem; font-weight: 700; color: #f59e0b;">{{ $reverseResult['stats']['super_admin'] }}</div>
                            <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.2rem;">{{ __('permission-toolkit::messages.sim_stat_super_admin') }}</div>
                        </div>
                    </div>

                    <!-- Authorized Users Table -->
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th style="width: 60px;">ID</th>
                                    <th>{{ __('permission-toolkit::messages.users_th_user') }}</th>
                                    <th>{{ __('permission-toolkit::messages.users_th_email') }}</th>
                                    <th>{{ __('permission-toolkit::messages.sim_th_detail') }}</th>
                                    <th>{{ __('permission-toolkit::messages.users_th_roles') }}</th>
                                    <th style="width: 100px; text-align: center;">{{ __('permission-toolkit::messages.users_th_action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($reverseResult['authorized_users'] as $entry)
                                    <tr>
                                        <td style="color: var(--text-muted); font-weight: 500;">#{{ $entry['user']['id'] }}</td>
                                        <td style="font-weight: 600;">{{ $entry['user']['name'] }}</td>
                                        <td style="color: var(--text-muted); font-size: 0.85rem;">{{ $entry['user']['email'] }}</td>
                                        <td>
                                            @if($entry['grant_type'] === 'BOTH')
                                                <span class="badge badge-success" style="font-size: 0.7rem;">{{ __('permission-toolkit::messages.users_has_perm_direct') }}</span>
                                                <span class="badge badge-info" style="font-size: 0.7rem;">{{ __('permission-toolkit::messages.users_has_perm_via_role', ['role' => implode(', ', $entry['roles_granting'])]) }}</span>
                                            @elseif($entry['grant_type'] === 'DIRECT')
                                                <span class="badge badge-success" style="font-size: 0.7rem;">{{ __('permission-toolkit::messages.users_has_perm_direct') }}</span>
                                            @elseif($entry['grant_type'] === 'SUPER_ADMIN')
                                                <span class="badge badge-warning" style="font-size: 0.7rem;">{{ __('permission-toolkit::messages.sim_step_super_admin') }}</span>
                                            @else
                                                <span class="badge badge-info" style="font-size: 0.7rem;">{{ __('permission-toolkit::messages.users_has_perm_via_role', ['role' => implode(', ', $entry['roles_granting'])]) }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            @forelse($entry['user']['roles'] as $rName)
                                                <span class="badge badge-info" style="margin-right: 0.2rem; font-size: 0.7rem;">{{ $rName }}</span>
                                            @empty
                                                <span style="color: var(--text-muted); font-size: 0.75rem;">—</span>
                                            @endforelse
                                        </td>
                                        <td style="text-align: center;">
                                            <a href="{{ route('permission-toolkit.users.edit', $entry['user']['id']) }}" class="btn" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;">
                                                {{ __('permission-toolkit::messages.users_manage_btn') }}
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 2.5rem;">
                                            {{ __('permission-toolkit::messages.sim_reverse_empty') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @else
                <div class="card" style="text-align: center; padding: 4rem 2rem; color: var(--text-muted);">
                    <div style="font-size: 2.5rem; margin-bottom: 1rem;">👥</div>
                    <h3 style="font-size: 1.1rem; color: var(--text-main); margin-bottom: 0.5rem;">{{ __('permission-toolkit::messages.sim_reverse_title') }}</h3>
                    <p style="font-size: 0.875rem; max-width: 500px; margin: 0 auto;">
                        {{ __('permission-toolkit::messages.sim_reverse_desc') }}
                    </p>
                </div>
            @endif
        </div>
    </div>

    <script>
        const permList = @json($permissions->pluck('name'));
        const roleList = @json($roles->pluck('name'));

        function updateDatalist(type) {
            const datalist = document.getElementById('reverseOptionsList');
            if (!datalist) return;
            datalist.innerHTML = '';
            const items = type === 'role' ? roleList : permList;
            items.forEach(item => {
                const opt = document.createElement('option');
                opt.value = item;
                datalist.appendChild(opt);
            });
        }
    </script>
@else
    <!-- ==================== FORWARD SIMULATION MODE ==================== -->
    <div style="display: grid; grid-template-columns: 380px 1fr; gap: 1.5rem; align-items: start;">
        <!-- Simulator Form -->
        <div class="card" style="height: fit-content;">
            <div class="card-header">
                <h2 class="card-title">{{ __('permission-toolkit::messages.sim_config_title') }}</h2>
            </div>

            <form method="GET" action="{{ route('permission-toolkit.simulator') }}">
                <input type="hidden" name="mode" value="forward">

                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.35rem; font-weight: 500;">
                        {{ __('permission-toolkit::messages.sim_user_label') }}
                    </label>
                    <select name="user_id" class="input-control" required>
                        <option value="">{{ __('permission-toolkit::messages.sim_user_placeholder') }}</option>
                        @foreach($users as $u)
                            @php
                                $uDisplayName = \SalvatoreCervone\PermissionToolkit\PermissionToolkit::getUserDisplayName($u);
                                $uKey = method_exists($u, 'getKey') ? $u->getKey() : ($u->id ?? '');
                            @endphp
                            <option value="{{ $uKey }}" {{ (string) $selectedUserId === (string) $uKey ? 'selected' : '' }}>
                                {{ $uDisplayName }} (ID: {{ $uKey }})
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
                                {{ __('permission-toolkit::messages.sim_verdict_' . strtolower($simulationResult['verdict'])) }}
                            </span>
                            <div style="font-size: 1.1rem; font-weight: 600; margin-top: 0.5rem;">
                                {{ $simulationResult['reason'] }}
                            </div>
                        </div>
                        <div style="text-align: right; font-size: 0.8rem; color: var(--text-muted);">
                            {{ __('permission-toolkit::messages.sim_at', ['time' => \Carbon\Carbon::parse($simulationResult['timestamp'])->format('H:i:s')]) }}
                        </div>
                    </div>

                    <div style="background: rgba(255, 255, 255, 0.02); border: 1px solid var(--border); border-radius: 0.375rem; padding: 0.75rem 1rem; margin-bottom: 1.5rem; font-size: 0.85rem; display: flex; gap: 2rem; flex-wrap: wrap;">
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
@endif
@endsection
