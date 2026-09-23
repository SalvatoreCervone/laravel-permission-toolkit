@extends('permission-toolkit::layouts.app')

@section('title', 'Matrice Ruoli & Permessi')

@section('content')
<div class="card">
    <div class="card-header" style="flex-wrap: wrap; gap: 1rem;">
        <div>
            <h1 class="card-title">Matrice Ruoli & Permessi</h1>
            <p style="color: var(--text-muted); font-size: 0.85rem; margin-top: 0.25rem;">
                Modifica istantanea delle associazioni Spatie. Tutte le modifiche vengono tracciate nell'Audit Log.
            </p>
        </div>
        <div style="display: flex; gap: 0.75rem; align-items: center;">
            <input type="text" id="permissionFilter" class="input-control" style="width: 220px;" placeholder="Filtra permessi...">
            <button type="button" class="btn" onclick="openModal('modalRole')">
                + Nuovo Ruolo
            </button>
            <button type="button" class="btn btn-secondary" onclick="openModal('modalPerm')">
                + Nuovo Permesso
            </button>
        </div>
    </div>

    <div class="table-responsive">
        <table id="matrixTable">
            <thead>
                <tr>
                    <th style="min-width: 250px;">Modulo / Permesso Spatie</th>
                    @foreach($roles as $role)
                        <th style="text-align: center; min-width: 140px;">
                            <div style="display: flex; align-items: center; justify-content: center; gap: 0.35rem;">
                                <span class="badge badge-info">{{ $role->name }}</span>
                                <button type="button" onclick="deleteRole({{ $role->id }}, '{{ $role->name }}')" style="background: none; border: none; color: #ef4444; cursor: pointer; font-size: 0.75rem;" title="Elimina ruolo">✕</button>
                            </div>
                            <div style="font-size: 0.7rem; color: var(--text-muted); margin-top: 0.2rem;">{{ $role->guard_name }}</div>
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse($groupedPermissions as $group => $perms)
                    <tr style="background: rgba(79, 70, 229, 0.08);">
                        <td colspan="{{ count($roles) + 1 }}" style="font-weight: 700; color: #a5b4fc; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em; padding: 0.5rem 1rem;">
                            📂 Modulo: {{ $group }} ({{ count($perms) }})
                        </td>
                    </tr>
                    @foreach($perms as $permission)
                        <tr class="perm-row" data-perm-name="{{ strtolower($permission->name) }}">
                            <td style="font-family: monospace; font-size: 0.85rem; display: flex; align-items: center; justify-content: space-between;">
                                <span>
                                    {{ $permission->name }}
                                    <span style="font-size: 0.7rem; color: var(--text-muted); margin-left: 0.5rem;">({{ $permission->guard_name }})</span>
                                </span>
                                <button type="button" onclick="deletePermission({{ $permission->id }}, '{{ $permission->name }}')" style="background: none; border: none; color: #ef4444; cursor: pointer; font-size: 0.75rem;" title="Elimina permesso">✕</button>
                            </td>
                            @foreach($roles as $role)
                                @php
                                    $hasPerm = $role->hasPermissionTo($permission->name);
                                @endphp
                                <td style="text-align: center;">
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
                        <td colspan="{{ count($roles) + 1 }}" style="text-align: center; color: var(--text-muted); padding: 2rem;">
                            Nessun permesso trovato nel database Spatie.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Crea Ruolo -->
<div id="modalRole" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.7); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: var(--bg-card); border: 1px solid var(--border); padding: 1.5rem; border-radius: 0.5rem; width: 100%; max-width: 400px;">
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
    <div style="background: var(--bg-card); border: 1px solid var(--border); padding: 1.5rem; border-radius: 0.5rem; width: 100%; max-width: 400px;">
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
                const name = row.getAttribute('data-perm-name');
                row.style.display = name.includes(query) ? '' : 'none';
            });
        });

        document.querySelectorAll('.perm-toggle').forEach(checkbox => {
            checkbox.addEventListener('change', function () {
                const roleId = this.dataset.roleId;
                const permId = this.dataset.permId;
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
