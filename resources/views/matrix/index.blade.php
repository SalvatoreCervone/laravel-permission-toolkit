@extends('permission-toolkit::layouts.app')

@section('title', 'Matrice Ruoli & Permessi')

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
                Matrice Ruoli & Permessi
                <span class="badge badge-info" style="font-size: 0.75rem;">{{ $roles->count() }} Ruoli</span>
                <span class="badge badge-success" style="font-size: 0.75rem;">{{ $permissions->count() }} Permessi</span>
            </h1>
            <p style="color: var(--text-muted); font-size: 0.8rem; margin-top: 0.2rem;">
                💡 <strong>Filtro rapido</strong>: clicca sul nome di un <strong>permesso</strong> per visualizzare solo i ruoli che lo hanno, oppure clicca sull'etichetta di un <strong>ruolo</strong> per visualizzare solo i permessi assegnati.
            </p>
        </div>
        <div style="display: flex; gap: 0.75rem; align-items: center;">
            <input type="text" id="permissionFilter" class="input-control" style="width: 240px;" placeholder="🔍 Filtra permesso o modulo...">
            <button type="button" class="btn" onclick="openModal('modalRole')">
                + Nuovo Ruolo
            </button>
            <button type="button" class="btn btn-secondary" onclick="openModal('modalPerm')">
                + Nuovo Permesso
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
        ✕ Rimuovi Filtro
    </button>
</div>

<div class="matrix-container">
    <table id="matrixTable">
        <thead>
            <tr>
                <th style="min-width: 320px; width: 320px;">
                    Modulo / Permesso Spatie
                </th>
                @foreach($roles as $role)
                    <th class="role-col-header" data-role-id="{{ $role->id }}" style="text-align: center; min-width: 130px; width: 130px;">
                        <div style="display: flex; align-items: center; justify-content: center; gap: 0.35rem;">
                            <span class="badge badge-info role-filter-btn" 
                                  onclick="filterByRole({{ $role->id }}, '{{ addslashes($role->name) }}')"
                                  title="🔍 Clicca per mostrare solo i permessi assegnati a questo ruolo"
                                  style="font-size: 0.7rem; white-space: nowrap;">
                                {{ $role->name }}
                            </span>
                            <button type="button" onclick="event.stopPropagation(); deleteRole({{ $role->id }}, '{{ addslashes($role->name) }}')" style="background: none; border: none; color: #ef4444; cursor: pointer; font-size: 0.75rem;" title="Elimina ruolo">✕</button>
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
                        📂 Modulo: {{ $group }} ({{ count($perms) }})
                    </td>
                    <td class="module-row-spacer" colspan="{{ count($roles) }}" style="background: rgba(79, 70, 229, 0.08); border-bottom: 1px solid var(--border);"></td>
                </tr>
                @foreach($perms as $permission)
                    <tr class="perm-row" data-perm-id="{{ $permission->id }}" data-perm-name="{{ strtolower($permission->name) }}" data-group-name="{{ strtolower($group) }}">
                        <td style="font-family: monospace; font-size: 0.85rem; display: flex; align-items: center; justify-content: space-between; min-width: 320px;">
                            <span class="perm-name-clickable" 
                                  onclick="filterByPermission({{ $permission->id }}, '{{ addslashes($permission->name) }}')"
                                  title="🔍 Clicca per mostrare solo i ruoli che hanno questo permesso">
                                <strong>{{ $permission->name }}</strong>
                                <span style="font-size: 0.7rem; color: var(--text-muted); margin-left: 0.4rem;">({{ $permission->guard_name }})</span>
                            </span>
                            <button type="button" onclick="event.stopPropagation(); deletePermission({{ $permission->id }}, '{{ addslashes($permission->name) }}')" style="background: none; border: none; color: #ef4444; cursor: pointer; font-size: 0.75rem; padding: 0.2rem;" title="Elimina permesso">✕</button>
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
                        Nessun permesso trovato nel database Spatie.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<!-- Modal Crea Ruolo -->
<div id="modalRole" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.7); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: var(--bg-card); border: 1px solid var(--border); padding: 1.5rem; border-radius: 0.5rem; width: 100%; max-width: 400px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.5);">
        <h3 style="margin-bottom: 1rem; font-size: 1.1rem;">Crea Nuovo Ruolo Spatie</h3>
        <div style="margin-bottom: 1rem;">
            <label style="font-size: 0.8rem; color: var(--text-muted); display: block; margin-bottom: 0.35rem;">Nome Ruolo</label>
            <input type="text" id="newRoleName" class="input-control" placeholder="es. manager o supervisor">
        </div>
        <div style="margin-bottom: 1.5rem;">
            <label style="font-size: 0.8rem; color: var(--text-muted); display: block; margin-bottom: 0.35rem;">Guard (opzionale)</label>
            <input type="text" id="newRoleGuard" class="input-control" value="web">
        </div>
        <div style="display: flex; justify-content: flex-end; gap: 0.75rem;">
            <button type="button" class="btn btn-secondary" onclick="closeModal('modalRole')">Annulla</button>
            <button type="button" class="btn" onclick="submitCreateRole()">Crea Ruolo</button>
        </div>
    </div>
</div>

<!-- Modal Crea Permesso -->
<div id="modalPerm" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.7); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: var(--bg-card); border: 1px solid var(--border); padding: 1.5rem; border-radius: 0.5rem; width: 100%; max-width: 400px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.5);">
        <h3 style="margin-bottom: 1rem; font-size: 1.1rem;">Crea Nuovo Permesso Spatie</h3>
        <div style="margin-bottom: 1rem;">
            <label style="font-size: 0.8rem; color: var(--text-muted); display: block; margin-bottom: 0.35rem;">Nome Permesso</label>
            <input type="text" id="newPermName" class="input-control" placeholder="es. invoices.delete o users.export">
        </div>
        <div style="margin-bottom: 1.5rem;">
            <label style="font-size: 0.8rem; color: var(--text-muted); display: block; margin-bottom: 0.35rem;">Guard (opzionale)</label>
            <input type="text" id="newPermGuard" class="input-control" value="web">
        </div>
        <div style="display: flex; justify-content: flex-end; gap: 0.75rem;">
            <button type="button" class="btn btn-secondary" onclick="closeModal('modalPerm')">Annulla</button>
            <button type="button" class="btn" onclick="submitCreatePerm()">Crea Permesso</button>
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
        filterText.innerHTML = `Filtro Permesso: <strong style="color: #a5b4fc; font-family: monospace;">${permName}</strong> — Mostrando solo i <strong>${matchingRoleIds.size}</strong> ruoli che lo possiedono.`;

        if (typeof showToast === 'function') {
            showToast(`Filtrati ${matchingRoleIds.size} ruoli con permesso [${permName}]`);
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
        filterText.innerHTML = `Filtro Ruolo: <strong style="color: #a5b4fc;">${roleName}</strong> — Mostrando solo i <strong>${matchCount}</strong> permessi assegnati a questo ruolo.`;

        if (typeof showToast === 'function') {
            showToast(`Filtrati ${matchCount} permessi per il ruolo [${roleName}]`);
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
        if (!name) return alert('Inserisci il nome del ruolo');

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
                showToast(d.message || 'Errore', 'danger');
            }
        });
    }

    function submitCreatePerm() {
        const name = document.getElementById('newPermName').value.trim();
        const guard = document.getElementById('newPermGuard').value.trim();
        if (!name) return alert('Inserisci il nome del permesso');

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
                showToast(d.message || 'Errore', 'danger');
            }
        });
    }

    function deleteRole(id, name) {
        if (!confirm(`Sei sicuro di voler eliminare il ruolo [${name}]?`)) return;

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
            else showToast(d.message || 'Errore', 'danger');
        });
    }

    function deletePermission(id, name) {
        if (!confirm(`Sei sicuro di voler eliminare il permesso [${name}]?`)) return;

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
            else showToast(d.message || 'Errore', 'danger');
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
                        showToast('Errore durante l\'aggiornamento', 'danger');
                    }
                })
                .catch(err => {
                    this.disabled = false;
                    this.checked = !isChecked;
                    showToast('Errore di connessione al server', 'danger');
                });
            });
        });
    });
</script>
@endpush
