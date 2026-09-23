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
                Tabella a scorrimento bidirezionale fluido con colonna dei permessi e intestazione dei ruoli bloccate.
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

<div class="matrix-container">
    <table id="matrixTable">
        <thead>
            <tr>
                <th style="min-width: 320px; width: 320px;">
                    Modulo / Permesso Spatie
                </th>
                @foreach($roles as $role)
                    <th style="text-align: center; min-width: 130px; width: 130px;">
                        <div style="display: flex; align-items: center; justify-content: center; gap: 0.35rem;">
                            <span class="badge badge-info" style="font-size: 0.7rem; white-space: nowrap;">{{ $role->name }}</span>
                            <button type="button" onclick="deleteRole({{ $role->id }}, '{{ $role->name }}')" style="background: none; border: none; color: #ef4444; cursor: pointer; font-size: 0.75rem;" title="Elimina ruolo">✕</button>
                        </div>
                        <div style="font-size: 0.65rem; color: var(--text-muted); margin-top: 0.15rem;">{{ $role->guard_name }}</div>
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($groupedPermissions as $group => $perms)
                <tr style="background: rgba(79, 70, 229, 0.08);">
                    <td class="module-header-sticky" style="padding: 0.4rem 1rem;">
                        📂 Modulo: {{ $group }} ({{ count($perms) }})
                    </td>
                    <td colspan="{{ count($roles) }}" style="background: rgba(79, 70, 229, 0.08); border-bottom: 1px solid var(--border);"></td>
                </tr>
                @foreach($perms as $permission)
                    <tr class="perm-row" data-perm-name="{{ strtolower($permission->name) }}" data-group-name="{{ strtolower($group) }}">
                        <td style="font-family: monospace; font-size: 0.85rem; display: flex; align-items: center; justify-content: space-between; min-width: 320px;">
                            <span>
                                <strong>{{ $permission->name }}</strong>
                                <span style="font-size: 0.7rem; color: var(--text-muted); margin-left: 0.4rem;">({{ $permission->guard_name }})</span>
                            </span>
                            <button type="button" onclick="deletePermission({{ $permission->id }}, '{{ $permission->name }}')" style="background: none; border: none; color: #ef4444; cursor: pointer; font-size: 0.75rem; padding: 0.2rem;" title="Elimina permesso">✕</button>
                        </td>
                        @foreach($roles as $role)
                            @php
                                $hasPerm = $role->hasPermissionTo($permission->name);
                            @endphp
                            <td style="text-align: center; min-width: 130px;">
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
    function openModal(id) {
        document.getElementById(id).style.display = 'flex';
    }
    function closeModal(id) {
        document.getElementById(id).style.display = 'none';
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
