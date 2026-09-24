@extends('permission-toolkit::layouts.app')

@section('title', __('permission-toolkit::messages.doctor_title'))

@section('content')
<div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; margin-bottom: 1.5rem;">
    <div class="card" style="margin-bottom: 0;">
        <div style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase;">{{ __('permission-toolkit::messages.doctor_stat_roles') }}</div>
        <div style="font-size: 2rem; font-weight: 700; margin-top: 0.25rem;">{{ $report['summary']['total_roles'] }}</div>
    </div>
    <div class="card" style="margin-bottom: 0;">
        <div style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase;">{{ __('permission-toolkit::messages.doctor_stat_perms') }}</div>
        <div style="font-size: 2rem; font-weight: 700; margin-top: 0.25rem;">{{ $report['summary']['total_permissions'] }}</div>
    </div>
    <div class="card" style="margin-bottom: 0;">
        <div style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase;">{{ __('permission-toolkit::messages.doctor_stat_status') }}</div>
        <div style="margin-top: 0.5rem;">
            @php
                $hasIssues = !empty($report['orphaned_pivot_records']) || !empty($report['guard_mismatches']);
            @endphp
            @if($hasIssues)
                <span class="badge badge-danger" style="font-size: 0.9rem; padding: 0.4rem 0.8rem;">{{ __('permission-toolkit::messages.doctor_status_issues') }}</span>
            @else
                <span class="badge badge-success" style="font-size: 0.9rem; padding: 0.4rem 0.8rem;">{{ __('permission-toolkit::messages.doctor_status_healthy') }}</span>
            @endif
        </div>
    </div>
</div>

<!-- Orphaned Pivots -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">{{ __('permission-toolkit::messages.doctor_sec1_title') }}</h2>
    </div>
    @if(empty($report['orphaned_pivot_records']))
        <div style="color: var(--success); font-size: 0.9rem;">
            {{ __('permission-toolkit::messages.doctor_sec1_healthy') }}
        </div>
    @else
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>{{ __('permission-toolkit::messages.doctor_th_table') }}</th>
                        <th>{{ __('permission-toolkit::messages.doctor_th_issue') }}</th>
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
        <h2 class="card-title">{{ __('permission-toolkit::messages.doctor_sec2_title') }}</h2>
    </div>
    @if(empty($report['guard_mismatches']))
        <div style="color: var(--success); font-size: 0.9rem;">
            {{ __('permission-toolkit::messages.doctor_sec2_healthy') }}
        </div>
    @else
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>{{ __('permission-toolkit::messages.doctor_th_role') }}</th>
                        <th>{{ __('permission-toolkit::messages.doctor_th_perm') }}</th>
                        <th>{{ __('permission-toolkit::messages.doctor_th_diagnosis') }}</th>
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
        <h2 class="card-title">{{ __('permission-toolkit::messages.doctor_sec3_title', ['count' => count($report['unused_permissions'])]) }}</h2>
        <span style="font-size: 0.8rem; color: var(--text-muted);">{{ __('permission-toolkit::messages.doctor_sec3_subtitle') }}</span>
    </div>
    @if(empty($report['unused_permissions']))
        <div style="color: var(--success); font-size: 0.9rem;">
            {{ __('permission-toolkit::messages.doctor_sec3_healthy') }}
        </div>
    @else
        <div class="table-responsive" style="max-height: 250px;">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>{{ __('permission-toolkit::messages.doctor_th_perm_name') }}</th>
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
        <h2 class="card-title">{{ __('permission-toolkit::messages.doctor_sec4_title', ['count' => count($report['empty_roles'])]) }}</h2>
        <span style="font-size: 0.8rem; color: var(--text-muted);">{{ __('permission-toolkit::messages.doctor_sec4_subtitle') }}</span>
    </div>
    @if(empty($report['empty_roles']))
        <div style="color: var(--success); font-size: 0.9rem;">
            {{ __('permission-toolkit::messages.doctor_sec4_healthy') }}
        </div>
    @else
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>{{ __('permission-toolkit::messages.doctor_th_role_name') }}</th>
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
