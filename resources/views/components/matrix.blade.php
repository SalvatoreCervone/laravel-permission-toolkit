<div class="permission-matrix-widget" id="permMatrixWidget-{{ uniqid() }}">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; gap: 1rem; flex-wrap: wrap;">
        <div style="flex: 1; min-width: 240px;">
            <input type="text" class="form-control matrix-filter-input" placeholder="{{ __('permission-toolkit::messages.matrix_search_placeholder') ?? 'Search permissions...' }}" style="width: 100%; padding: 0.5rem 0.75rem; border-radius: 0.375rem; border: 1px solid var(--border, #374151); background: var(--bg-card, #1f2937); color: var(--text-main, #f3f4f6);">
        </div>
        <div style="font-size: 0.85rem; color: var(--text-muted, #9ca3af);">
            <span>{{ count($roles) }} {{ __('permission-toolkit::messages.nav_roles') }}</span> • 
            <span>{{ count($permissions) }} {{ __('permission-toolkit::messages.nav_permissions') }}</span>
        </div>
    </div>

    <div style="max-height: 600px; overflow: auto; border: 1px solid var(--border, #374151); border-radius: 0.5rem; background: var(--bg-card, #111827);">
        <table style="width: 100%; border-collapse: separate; border-spacing: 0; text-align: left; font-size: 0.875rem;" class="widget-matrix-table">
            <thead>
                <tr style="background: var(--bg-header, #1f2937); color: var(--text-main, #f9fafb);">
                    <th style="position: sticky; top: 0; left: 0; z-index: 30; background: #1f2937; padding: 0.75rem 1rem; border-bottom: 2px solid var(--border, #374151); border-right: 2px solid var(--border, #374151); min-width: 240px;">
                        {{ __('permission-toolkit::messages.matrix_col_permission') }}
                    </th>
                    @foreach($roles as $role)
                        <th style="position: sticky; top: 0; z-index: 20; background: #1f2937; padding: 0.75rem 1rem; border-bottom: 2px solid var(--border, #374151); text-align: center; min-width: 130px;">
                            <div style="font-weight: 600;">{{ $role->name }}</div>
                            <div style="font-size: 0.7rem; color: #9ca3af;">{{ $role->guard_name }}</div>
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($modules as $moduleName => $modulePermissions)
                    <tr style="background: rgba(79, 70, 229, 0.12);" class="widget-module-row">
                        <td style="position: sticky; left: 0; z-index: 10; background: #1e1b4b; padding: 0.5rem 1rem; font-weight: 700; color: #a5b4fc; text-transform: uppercase; font-size: 0.75rem; border-top: 1px solid var(--border, #374151); border-right: 2px solid var(--border, #374151);">
                            📂 {{ $moduleName }} ({{ count($modulePermissions) }})
                        </td>
                        @foreach($roles as $role)
                            @php
                                $permIds = collect($modulePermissions)->pluck('id')->all();
                            @endphp
                            <td style="padding: 0.35rem; text-align: center; border-top: 1px solid var(--border, #374151);">
                                <div style="display: inline-flex; gap: 4px; align-items: center;">
                                    <button type="button" 
                                            class="widget-bulk-btn"
                                            data-role-id="{{ $role->id }}"
                                            data-permission-ids="{{ json_encode($permIds) }}"
                                            data-action="assign"
                                            title="{{ __('permission-toolkit::messages.matrix_bulk_assign_all') ?? 'Assign all' }}"
                                            style="border: 1px solid rgba(16, 185, 129, 0.4); background: rgba(16, 185, 129, 0.15); color: #34d399; border-radius: 3px; font-size: 0.65rem; padding: 1px 5px; cursor: pointer;">
                                        ✓
                                    </button>
                                    <button type="button" 
                                            class="widget-bulk-btn"
                                            data-role-id="{{ $role->id }}"
                                            data-permission-ids="{{ json_encode($permIds) }}"
                                            data-action="revoke"
                                            title="{{ __('permission-toolkit::messages.matrix_bulk_revoke_all') ?? 'Revoke all' }}"
                                            style="border: 1px solid rgba(239, 68, 68, 0.4); background: rgba(239, 68, 68, 0.15); color: #f87171; border-radius: 3px; font-size: 0.65rem; padding: 1px 5px; cursor: pointer;">
                                        ✕
                                    </button>
                                </div>
                            </td>
                        @endforeach
                    </tr>
                    @foreach($modulePermissions as $permission)
                        <tr class="widget-perm-row" data-name="{{ strtolower($permission->name) }}">
                            <td style="position: sticky; left: 0; z-index: 10; background: #111827; padding: 0.6rem 1rem; border-bottom: 1px solid #1f2937; border-right: 2px solid var(--border, #374151); color: #e5e7eb;">
                                <code>{{ $permission->name }}</code>
                            </td>
                            @foreach($roles as $role)
                                @php
                                    $hasPerm = $role->hasPermissionTo($permission->name, $permission->guard_name);
                                    $isSuperAdmin = ($role->name === 'super-admin' || $role->name === 'Super Admin') && config('permission-toolkit.super_admin.enabled', true);
                                @endphp
                                <td style="padding: 0.6rem; text-align: center; border-bottom: 1px solid #1f2937;">
                                    <input type="checkbox"
                                           class="widget-perm-checkbox"
                                           data-role-id="{{ $role->id }}"
                                           data-permission-id="{{ $permission->id }}"
                                           {{ $hasPerm ? 'checked' : '' }}
                                           {{ $isSuperAdmin ? 'disabled' : '' }}
                                           style="width: 1.1rem; height: 1.1rem; cursor: pointer; accent-color: #4f46e5;">
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>
    </div>

    @if($includeScripts)
    <script>
    (function() {
        const toggleUrl = @json(route('permission-toolkit.matrix.toggle'));
        const bulkUrl = @json(route('permission-toolkit.matrix.bulk-toggle'));
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        // Search Filter
        document.querySelectorAll('.matrix-filter-input').forEach(input => {
            input.addEventListener('input', function() {
                const q = this.value.toLowerCase().trim();
                const container = this.closest('.permission-matrix-widget');
                container.querySelectorAll('.widget-perm-row').forEach(row => {
                    const name = row.getAttribute('data-name') || '';
                    row.style.display = name.includes(q) ? '' : 'none';
                });
            });
        });

        // Single Toggle
        document.querySelectorAll('.widget-perm-checkbox').forEach(chk => {
            chk.addEventListener('change', function() {
                const roleId = this.dataset.roleId;
                const permissionId = this.dataset.permissionId;
                const assigned = this.checked;
                this.disabled = true;

                fetch(toggleUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ role_id: roleId, permission_id: permissionId, assigned: assigned })
                })
                .then(r => r.json())
                .then(data => {
                    this.disabled = false;
                    if (!data.success) {
                        this.checked = !assigned;
                        alert(data.message || 'Error updating permission');
                    }
                })
                .catch(err => {
                    this.disabled = false;
                    this.checked = !assigned;
                    console.error(err);
                });
            });
        });

        // Bulk Toggle
        document.querySelectorAll('.widget-bulk-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const roleId = this.dataset.roleId;
                const permIds = JSON.parse(this.dataset.permissionIds || '[]');
                const action = this.dataset.action;
                if (!permIds.length) return;

                btn.disabled = true;
                fetch(bulkUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ role_id: roleId, permission_ids: permIds, action: action })
                })
                .then(r => r.json())
                .then(data => {
                    btn.disabled = false;
                    if (data.success) {
                        const isAssigned = (action === 'assign');
                        permIds.forEach(pId => {
                            const chk = document.querySelector(`.widget-perm-checkbox[data-role-id="${roleId}][data-permission-id="${pId}"]`);
                            if (chk && !chk.disabled) chk.checked = isAssigned;
                        });
                    } else {
                        alert(data.message || 'Error executing bulk operation');
                    }
                })
                .catch(err => {
                    btn.disabled = false;
                    console.error(err);
                });
            });
        });
    })();
    </script>
    @endif
</div>
