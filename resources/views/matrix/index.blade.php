@extends('permission-toolkit::layouts.app')

@section('title', __('permission-toolkit::messages.matrix_title'))

@push('styles')
<style>
    /* Spreadsheet-style Sticky Matrix */
    .matrix-container {
        max-height: calc(100vh - 180px);
        overflow: auto;
        position: relative;
        border: 1px solid var(--border);
        border-radius: 0.5rem;
        background: var(--bg-card);
    }
    
    /* Scrollbar personalizzata elegante */
    .matrix-container::-webkit-scrollbar {
        width: 10px;
        height: 10px;
    }
    .matrix-container::-webkit-scrollbar-track {
        background: #0b0f19;
    }
    .matrix-container::-webkit-scrollbar-thumb {
        background: #374151;
        border-radius: 5px;
    }
    .matrix-container::-webkit-scrollbar-thumb:hover {
        background: #4f46e5;
    }

    /* Riga di intestazione fissa in alto */
    #matrixTable thead th {
        position: sticky;
        top: 0;
        z-index: 30;
        background-color: #171f2e;
        border-bottom: 2px solid var(--border);
        box-shadow: 0 2px 4px rgba(0,0,0,0.5);
    }

    /* Colonna permessi fissa a sinistra */
    #matrixTable tbody td:first-child {
        position: sticky;
        left: 0;
        z-index: 20;
        background-color: #111827;
        border-right: 2px solid var(--border);
        box-shadow: 3px 0 6px rgba(0,0,0,0.4);
    }

    /* Angolo in alto a sinistra (intersezione riga e colonna) */
    #matrixTable thead th:first-child {
        position: sticky;
        top: 0;
        left: 0;
        z-index: 60;
        background-color: #171f2e;
        border-right: 2px solid var(--border);
        box-shadow: 3px 3px 6px rgba(0,0,0,0.5);
    }

    /* Intestazione modulo sticky a sinistra */
    .module-header-sticky {
        position: sticky;
        left: 0;
        z-index: 15;
        background: rgba(79, 70, 229, 0.15) !important;
        color: #a5b4fc !important;
        font-weight: 700;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.05em;
        border-right: 2px solid var(--border);
        box-shadow: 3px 0 6px rgba(0,0,0,0.4);
    }

    /* Hover evidenziato su riga */
    #matrixTable tbody tr:hover td {
        background-color: rgba(79, 70, 229, 0.06);
    }
    #matrixTable tbody tr:hover td:first-child {
        background-color: #1e2538;
    }

    /* Elementi Cliccabili per Filtro Rapido */
    .perm-name-clickable {
        cursor: pointer;
        padding: 0.15rem 0.45rem;
        border-radius: 0.25rem;
        transition: all 0.15s ease;
        display: inline-block;
        user-select: none;
    }
    .perm-name-clickable:hover {
        background: rgba(99, 102, 241, 0.25);
        color: #c7d2fe;
    }
    .role-filter-btn {
        cursor: pointer;
        transition: all 0.15s ease;
        user-select: none;
    }
    .role-filter-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 0 10px rgba(99, 102, 241, 0.7);
    }

    /* Evidenziazione Elementi Filtrati */
    .perm-row-selected td {
        background-color: rgba(99, 102, 241, 0.22) !important;
    }
    .perm-row-selected td:first-child {
        background-color: #1f2740 !important;
        border-left: 4px solid #6366f1;
    }
    .role-col-selected {
        background-color: #312e81 !important;
        box-shadow: inset 0 0 0 2px #6366f1;
    }
    td.role-cell-selected {
        background-color: rgba(99, 102, 241, 0.14) !important;
    }
</style>
@endpush

@section('content')
<div class="card" style="padding: 1rem 1.25rem; margin-bottom: 1rem;">
    <div class="card-header" style="margin-bottom: 0; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h1 class="card-title" style="display: flex; align-items: center; gap: 0.75rem;">
                {{ __('permission-toolkit::messages.matrix_title') }}
                <span class="badge badge-info" style="font-size: 0.75rem;">{{ $roles->count() }} {{ __('permission-toolkit::messages.matrix_roles_count') }}</span>
                <span class="badge badge-success" style="font-size: 0.75rem;">{{ $permissions->count() }} {{ __('permission-toolkit::messages.matrix_perms_count') }}</span>
            </h1>
            <p style="color: var(--text-muted); font-size: 0.8rem; margin-top: 0.2rem;">
                {!! __('permission-toolkit::messages.matrix_quick_filter_tip') !!}
            </p>
        </div>
        <div style="display: flex; gap: 0.75rem; align-items: center; flex-wrap: wrap;">
            @if(count($availableGuards) > 1)
                <select onchange="window.location.href = '{{ route('permission-toolkit.matrix') }}?guard=' + encodeURIComponent(this.value)" class="input-control" style="width: auto; padding: 0.45rem 0.75rem; font-size: 0.85rem; border-color: #4f46e5;">
                    @foreach($availableGuards as $g)
                        <option value="{{ $g }}" {{ $selectedGuard === $g ? 'selected' : '' }}>Guard: {{ $g }}</option>
                    @endforeach
                    <option value="all" {{ $selectedGuard === 'all' ? 'selected' : '' }}>{{ __('permission-toolkit::messages.all_guards') }}</option>
                </select>
            @endif
            <input type="text" id="permissionFilter" class="input-control" style="width: 220px;" placeholder="{{ __('permission-toolkit::messages.matrix_search_placeholder') }}">
            <button type="button" class="btn" onclick="openModal('modalRole')">
                {{ __('permission-toolkit::messages.matrix_new_role_btn') }}
            </button>
            <button type="button" class="btn btn-secondary" onclick="openModal('modalPerm')">
                {{ __('permission-toolkit::messages.matrix_new_perm_btn') }}
            </button>
        </div>
    </div>
</div>

<!-- Barra Filtro Attivo -->
<div id="matrixActiveFilterBar" style="display: none; align-items: center; justify-content: space-between; background: rgba(79, 70, 229, 0.15); border: 1px solid #4f46e5; border-radius: 0.5rem; padding: 0.6rem 1.25rem; margin-bottom: 1rem;">
    <div style="display: flex; align-items: center; gap: 0.75rem; font-size: 0.85rem;">
        <span style="font-size: 1.15rem;">🎯</span>
        <span id="matrixActiveFilterText" style="color: #e0e7ff;"></span>
    </div>
    <button type="button" class="btn btn-secondary" onclick="resetMatrixFilter()" style="padding: 0.25rem 0.75rem; font-size: 0.8rem; border-color: #6366f1;">
        {{ __('permission-toolkit::messages.matrix_clear_filter') }}
    </button>
</div>

<div class="matrix-container">
    <table id="matrixTable">
        <thead>
            <tr>
                <th style="min-width: 320px; width: 320px;">
                    {{ __('permission-toolkit::messages.matrix_th_module_perm') }}
                </th>
                @foreach($roles as $role)
                    <th class="role-col-header" data-role-id="{{ $role->id }}" style="text-align: center; min-width: 130px; width: 130px;">
                        <div style="display: flex; align-items: center; justify-content: center; gap: 0.35rem;">
                            <span class="badge badge-info role-filter-btn" 
                                  onclick="filterByRole({{ $role->id }}, '{{ addslashes($role->name) }}')"
                                  title="{{ __('permission-toolkit::messages.matrix_role_filter_tooltip') }}"
                                  style="font-size: 0.7rem; white-space: nowrap;">
                                {{ $role->name }}
                            </span>
                            <button type="button" onclick="event.stopPropagation(); deleteRole({{ $role->id }}, '{{ addslashes($role->name) }}')" style="background: none; border: none; color: #ef4444; cursor: pointer; font-size: 0.75rem;" title="{{ __('permission-toolkit::messages.matrix_delete_role_title') }}">✕</button>
                        </div>
                        <div style="font-size: 0.65rem; color: var(--text-muted); margin-top: 0.15rem;">{{ $role->guard_name }}</div>
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($groupedPermissions as $group => $perms)
                <tr class="module-row" data-group-name="{{ strtolower($group) }}" style="background: rgba(79, 70, 229, 0.08);">
                    <td class="module-header-sticky" style="padding: 0.4rem 1rem;">
                        {{ __('permission-toolkit::messages.matrix_module_prefix', ['group' => $group, 'count' => count($perms)]) }}
                    </td>
                    <td class="module-row-spacer" colspan="{{ count($roles) }}" style="background: rgba(79, 70, 229, 0.08); border-bottom: 1px solid var(--border);"></td>
                </tr>
                @foreach($perms as $permission)
                    <tr class="perm-row" data-perm-id="{{ $permission->id }}" data-perm-name="{{ strtolower($permission->name) }}" data-group-name="{{ strtolower($group) }}">
                        <td style="font-family: monospace; font-size: 0.85rem; display: flex; align-items: center; justify-content: space-between; min-width: 320px;">
                            <span class="perm-name-clickable" 
                                  onclick="filterByPermission({{ $permission->id }}, '{{ addslashes($permission->name) }}')"
                                  title="{{ __('permission-toolkit::messages.matrix_perm_filter_tooltip') }}">
                                <strong>{{ $permission->name }}</strong>
                                <span style="font-size: 0.7rem; color: var(--text-muted); margin-left: 0.4rem;">({{ $permission->guard_name }})</span>
                            </span>
                            <button type="button" onclick="event.stopPropagation(); deletePermission({{ $permission->id }}, '{{ addslashes($permission->name) }}')" style="background: none; border: none; color: #ef4444; cursor: pointer; font-size: 0.75rem; padding: 0.2rem;" title="{{ __('permission-toolkit::messages.matrix_delete_perm_title') }}">✕</button>
                        </td>
                        @foreach($roles as $role)
                            @php
                                $hasPerm = $role->hasPermissionTo($permission->name);
                            @endphp
                            <td class="role-cell" data-role-id="{{ $role->id }}" data-has-perm="{{ $hasPerm ? '1' : '0' }}" style="text-align: center; min-width: 130px;">
                                <input 
                                    type="checkbox" 
                                    class="perm-toggle" 
                                    data-role-id="{{ $role->id }}" 
                                    data-perm-id="{{ $permission->id }}" 
                                    data-role-name="{{ $role->name }}"
                                    data-perm-name="{{ $permission->name }}"
                                    {{ $hasPerm ? 'checked' : '' }}
                                    style="cursor: pointer; width: 1.15rem; height: 1.15rem; accent-color: var(--primary);"
                                >
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            @empty
                <tr>
                    <td colspan="{{ count($roles) + 1 }}" style="text-align: center; color: var(--text-muted); padding: 3rem;">
                        {{ __('permission-toolkit::messages.matrix_empty') }}
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<!-- Modal Crea Ruolo -->
<div id="modalRole" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.7); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: var(--bg-card); border: 1px solid var(--border); padding: 1.5rem; border-radius: 0.5rem; width: 100%; max-width: 400px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.5);">
        <h3 style="margin-bottom: 1rem; font-size: 1.1rem;">{{ __('permission-toolkit::messages.matrix_modal_role_title') }}</h3>
        <div style="margin-bottom: 1rem;">
            <label style="font-size: 0.8rem; color: var(--text-muted); display: block; margin-bottom: 0.35rem;">{{ __('permission-toolkit::messages.matrix_modal_role_name') }}</label>
            <input type="text" id="newRoleName" class="input-control" placeholder="{{ __('permission-toolkit::messages.matrix_modal_role_placeholder') }}">
        </div>
        <div style="margin-bottom: 1.5rem;">
            <label style="font-size: 0.8rem; color: var(--text-muted); display: block; margin-bottom: 0.35rem;">{{ __('permission-toolkit::messages.matrix_modal_guard_label') }}</label>
            <input type="text" id="newRoleGuard" class="input-control" value="web">
        </div>
        <div style="display: flex; justify-content: flex-end; gap: 0.75rem;">
            <button type="button" class="btn btn-secondary" onclick="closeModal('modalRole')">{{ __('permission-toolkit::messages.btn_cancel') }}</button>
            <button type="button" class="btn" onclick="submitCreateRole()">{{ __('permission-toolkit::messages.matrix_modal_create_role_btn') }}</button>
        </div>
    </div>
</div>

<!-- Modal Crea Permesso -->
<div id="modalPerm" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.7); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: var(--bg-card); border: 1px solid var(--border); padding: 1.5rem; border-radius: 0.5rem; width: 100%; max-width: 400px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.5);">
        <h3 style="margin-bottom: 1rem; font-size: 1.1rem;">{{ __('permission-toolkit::messages.matrix_modal_perm_title') }}</h3>
        <div style="margin-bottom: 1rem;">
            <label style="font-size: 0.8rem; color: var(--text-muted); display: block; margin-bottom: 0.35rem;">{{ __('permission-toolkit::messages.matrix_modal_perm_name') }}</label>
            <input type="text" id="newPermName" class="input-control" placeholder="{{ __('permission-toolkit::messages.matrix_modal_perm_placeholder') }}">
        </div>
        <div style="margin-bottom: 1.5rem;">
            <label style="font-size: 0.8rem; color: var(--text-muted); display: block; margin-bottom: 0.35rem;">{{ __('permission-toolkit::messages.matrix_modal_guard_label') }}</label>
            <input type="text" id="newPermGuard" class="input-control" value="web">
        </div>
        <div style="display: flex; justify-content: flex-end; gap: 0.75rem;">
            <button type="button" class="btn btn-secondary" onclick="closeModal('modalPerm')">{{ __('permission-toolkit::messages.btn_cancel') }}</button>
            <button type="button" class="btn" onclick="submitCreatePerm()">{{ __('permission-toolkit::messages.matrix_modal_create_perm_btn') }}</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    let currentMatrixFilter = null; // { type: 'perm' | 'role', id: number, name: string }
    const totalRolesCount = {{ count($roles) }};

    function openModal(id) {
        document.getElementById(id).style.display = 'flex';
    }
    function closeModal(id) {
        document.getElementById(id).style.display = 'none';
    }

    function filterByPermission(permId, permName) {
        if (currentMatrixFilter && currentMatrixFilter.type === 'perm' && currentMatrixFilter.id === permId) {
            resetMatrixFilter();
            return;
        }

        resetMatrixFilter(false);
        currentMatrixFilter = { type: 'perm', id: permId, name: permName };

        const targetRow = document.querySelector(`.perm-row[data-perm-id="${permId}"]`);
        if (!targetRow) return;

        // Trova i ruoli che hanno questo permesso attivo (spuntato)
        const matchingRoleIds = new Set();
        targetRow.querySelectorAll('.perm-toggle').forEach(checkbox => {
            if (checkbox.checked) {
                matchingRoleIds.add(checkbox.dataset.roleId);
            }
        });

        // Filtra le colonne: mostra solo i ruoli che hanno il permesso
        document.querySelectorAll('.role-col-header').forEach(th => {
            const roleId = th.dataset.roleId;
            th.style.display = matchingRoleIds.has(roleId) ? '' : 'none';
        });

        document.querySelectorAll('.role-cell').forEach(td => {
            const roleId = td.dataset.roleId;
            td.style.display = matchingRoleIds.has(roleId) ? '' : 'none';
        });

        // Aggiorna colspan delle righe modulo
        document.querySelectorAll('.module-row-spacer').forEach(td => {
            td.colSpan = Math.max(matchingRoleIds.size, 1);
        });

        // Evidenzia la riga del permesso cliccato
        targetRow.classList.add('perm-row-selected');

        // Mostra la barra filtro attivo
        const filterBar = document.getElementById('matrixActiveFilterBar');
        const filterText = document.getElementById('matrixActiveFilterText');
        filterBar.style.display = 'flex';
        filterText.innerHTML = `{!! addslashes(__('permission-toolkit::messages.matrix_filter_perm_text', ['name' => '__NAME__', 'count' => '__COUNT__'])) !!}`
            .replace('__NAME__', permName)
            .replace('__COUNT__', matchingRoleIds.size);

        if (typeof showToast === 'function') {
            showToast(`{{ __('permission-toolkit::messages.matrix_toast_perm_filtered', ['count' => '__COUNT__', 'perm' => '__PERM__']) }}`
                .replace('__COUNT__', matchingRoleIds.size)
                .replace('__PERM__', permName));
        }
    }

    function filterByRole(roleId, roleName) {
        if (currentMatrixFilter && currentMatrixFilter.type === 'role' && currentMatrixFilter.id === roleId) {
            resetMatrixFilter();
            return;
        }

        resetMatrixFilter(false);
        currentMatrixFilter = { type: 'role', id: roleId, name: roleName };

        // Evidenzia la colonna del ruolo cliccato
        const roleTh = document.querySelector(`.role-col-header[data-role-id="${roleId}"]`);
        if (roleTh) roleTh.classList.add('role-col-selected');

        document.querySelectorAll(`.role-cell[data-role-id="${roleId}"]`).forEach(td => {
            td.classList.add('role-cell-selected');
        });

        // Filtra le righe: mostra solo i permessi assegnati a questo ruolo
        let matchCount = 0;
        document.querySelectorAll('.perm-row').forEach(row => {
            const checkbox = row.querySelector(`.perm-toggle[data-role-id="${roleId}"]`);
            const hasPerm = checkbox && checkbox.checked;
            row.style.display = hasPerm ? '' : 'none';
            if (hasPerm) matchCount++;
        });

        // Nasconde i moduli vuoti
        updateModuleRowsVisibility();

        // Mostra la barra filtro attivo
        const filterBar = document.getElementById('matrixActiveFilterBar');
        const filterText = document.getElementById('matrixActiveFilterText');
        filterBar.style.display = 'flex';
        filterText.innerHTML = `{!! addslashes(__('permission-toolkit::messages.matrix_filter_role_text', ['name' => '__NAME__', 'count' => '__COUNT__'])) !!}`
            .replace('__NAME__', roleName)
            .replace('__COUNT__', matchCount);

        if (typeof showToast === 'function') {
            showToast(`{{ __('permission-toolkit::messages.matrix_toast_role_filtered', ['count' => '__COUNT__', 'role' => '__ROLE__']) }}`
                .replace('__COUNT__', matchCount)
                .replace('__ROLE__', roleName));
        }
    }

    function updateModuleRowsVisibility() {
        document.querySelectorAll('.module-row').forEach(moduleRow => {
            let hasVisiblePerms = false;
            let sibling = moduleRow.nextElementSibling;
            while (sibling && sibling.classList.contains('perm-row')) {
                if (sibling.style.display !== 'none') {
                    hasVisiblePerms = true;
                    break;
                }
                sibling = sibling.nextElementSibling;
            }
            moduleRow.style.display = hasVisiblePerms ? '' : 'none';
        });
    }

    function resetMatrixFilter(clearState = true) {
        // Ripristina tutte le colonne
        document.querySelectorAll('.role-col-header').forEach(th => {
            th.style.display = '';
            th.classList.remove('role-col-selected');
        });

        document.querySelectorAll('.role-cell').forEach(td => {
            td.style.display = '';
            td.classList.remove('role-cell-selected');
        });

        document.querySelectorAll('.module-row-spacer').forEach(td => {
            td.colSpan = totalRolesCount;
        });

        // Ripristina tutte le righe
        document.querySelectorAll('.perm-row').forEach(row => {
            row.style.display = '';
            row.classList.remove('perm-row-selected');
        });

        document.querySelectorAll('.module-row').forEach(row => {
            row.style.display = '';
        });

        if (clearState) {
            const filterBar = document.getElementById('matrixActiveFilterBar');
            if (filterBar) filterBar.style.display = 'none';
            currentMatrixFilter = null;

            // Riapplica eventuale ricerca testuale
            const searchInput = document.getElementById('permissionFilter');
            if (searchInput && searchInput.value.trim()) {
                searchInput.dispatchEvent(new Event('input'));
            }
        }
    }

    function submitCreateRole() {
        const name = document.getElementById('newRoleName').value.trim();
        const guard = document.getElementById('newRoleGuard').value.trim();
        if (!name) return alert("{{ __('permission-toolkit::messages.matrix_enter_role_name') }}");

        fetch("{{ route('permission-toolkit.roles.store') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: JSON.stringify({ name: name, guard_name: guard })
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                location.reload();
            } else {
                showToast(d.message || "{{ __('permission-toolkit::messages.error') }}", 'danger');
            }
        });
    }

    function submitCreatePerm() {
        const name = document.getElementById('newPermName').value.trim();
        const guard = document.getElementById('newPermGuard').value.trim();
        if (!name) return alert("{{ __('permission-toolkit::messages.matrix_enter_perm_name') }}");

        fetch("{{ route('permission-toolkit.permissions.store') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: JSON.stringify({ name: name, guard_name: guard })
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                location.reload();
            } else {
                showToast(d.message || "{{ __('permission-toolkit::messages.error') }}", 'danger');
            }
        });
    }

    function deleteRole(id, name) {
        const msg = `{{ __('permission-toolkit::messages.matrix_confirm_delete_role', ['name' => '__NAME__']) }}`.replace('__NAME__', name);
        if (!confirm(msg)) return;

        fetch(`{{ url(config('permission-toolkit.prefix', 'permission-manager')) }}/roles/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) location.reload();
            else showToast(d.message || "{{ __('permission-toolkit::messages.error') }}", 'danger');
        });
    }

    function deletePermission(id, name) {
        const msg = `{{ __('permission-toolkit::messages.matrix_confirm_delete_perm', ['name' => '__NAME__']) }}`.replace('__NAME__', name);
        if (!confirm(msg)) return;

        fetch(`{{ url(config('permission-toolkit.prefix', 'permission-manager')) }}/permissions/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) location.reload();
            else showToast(d.message || "{{ __('permission-toolkit::messages.error') }}", 'danger');
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        const filterInput = document.getElementById('permissionFilter');
        filterInput.addEventListener('input', function () {
            const query = this.value.toLowerCase().trim();
            document.querySelectorAll('.perm-row').forEach(row => {
                const permName = row.getAttribute('data-perm-name');
                const groupName = row.getAttribute('data-group-name');
                const matches = permName.includes(query) || groupName.includes(query);
                row.style.display = matches ? '' : 'none';
            });
            updateModuleRowsVisibility();
        });

        document.querySelectorAll('.perm-toggle').forEach(checkbox => {
            checkbox.addEventListener('change', function () {
                const roleId = this.dataset.roleId;
                const permId = this.dataset.permId;
                const roleName = this.dataset.roleName;
                const permName = this.dataset.permName;
                const isChecked = this.checked;

                this.disabled = true;

                fetch("{{ route('permission-toolkit.matrix.toggle') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        role_id: roleId,
                        permission_id: permId
                    })
                })
                .then(res => res.json())
                .then(data => {
                    this.disabled = false;
                    if (data.success) {
                        showToast(data.message);
                    } else {
                        this.checked = !isChecked;
                        showToast(data.message || "{{ __('permission-toolkit::messages.update_error') }}", 'danger');
                    }
                })
                .catch(err => {
                    this.disabled = false;
                    this.checked = !isChecked;
                    showToast("{{ __('permission-toolkit::messages.server_error') }}", 'danger');
                });
            });
        });
    });
</script>
@endpush
