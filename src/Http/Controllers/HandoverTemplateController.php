<?php

namespace SalvatoreCervone\PermissionToolkit\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use SalvatoreCervone\PermissionToolkit\Models\HandoverTemplate;
use SalvatoreCervone\PermissionToolkit\Services\AuditLogger;

class HandoverTemplateController extends Controller
{
    /**
     * Display a listing of handover signoff templates.
     */
    public function index()
    {
        if (! config('permission-toolkit.handover.enabled', true)) {
            abort(404);
        }

        $templates = HandoverTemplate::orderByDesc('is_default')->orderBy('name')->get();

        return view('permission-toolkit::handover.templates', compact('templates'));
    }

    /**
     * Store a newly created handover template in storage.
     */
    public function store(Request $request)
    {
        if (! config('permission-toolkit.handover.enabled', true)) {
            abort(404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'application_name' => 'nullable|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:255',
            'signer_title' => 'required|string|max:255',
            'signer_name' => 'required|string|max:255',
            'second_signer_title' => 'nullable|string|max:255',
            'second_signer_name' => 'nullable|string|max:255',
            'reference_code' => 'nullable|string|max:255',
            'status_label' => 'nullable|string|max:255',
            'disclaimer_text' => 'required|string',
            'notes' => 'nullable|string',
            'footer_text' => 'nullable|string|max:500',
            'is_default' => 'nullable|boolean',
        ]);

        $isDefault = $request->boolean('is_default');
        if ($isDefault) {
            HandoverTemplate::where('is_default', true)->update(['is_default' => false]);
        } elseif (HandoverTemplate::count() === 0) {
            $isDefault = true;
        }

        $validated['is_default'] = $isDefault;
        $template = HandoverTemplate::create($validated);

        if (auth()->check() && AuditLogger::isEnabled()) {
            AuditLogger::log(
                targetUser: auth()->user(),
                action: 'created',
                type: 'handover_template',
                targetName: $template->name,
                metadata: ['template_id' => $template->id]
            );
        }

        return redirect()
            ->route('permission-toolkit.handover.templates.index')
            ->with('status', __('permission-toolkit::messages.handover_template_created'));
    }

    /**
     * Update the specified handover template in storage.
     */
    public function update(Request $request, $id)
    {
        if (! config('permission-toolkit.handover.enabled', true)) {
            abort(404);
        }

        $template = HandoverTemplate::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'application_name' => 'nullable|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:255',
            'signer_title' => 'required|string|max:255',
            'signer_name' => 'required|string|max:255',
            'second_signer_title' => 'nullable|string|max:255',
            'second_signer_name' => 'nullable|string|max:255',
            'reference_code' => 'nullable|string|max:255',
            'status_label' => 'nullable|string|max:255',
            'disclaimer_text' => 'required|string',
            'notes' => 'nullable|string',
            'footer_text' => 'nullable|string|max:500',
            'is_default' => 'nullable|boolean',
        ]);

        if ($request->boolean('is_default')) {
            HandoverTemplate::where('id', '!=', $template->id)->where('is_default', true)->update(['is_default' => false]);
            $validated['is_default'] = true;
        }

        $template->update($validated);

        if (auth()->check() && AuditLogger::isEnabled()) {
            AuditLogger::log(
                targetUser: auth()->user(),
                action: 'updated',
                type: 'handover_template',
                targetName: $template->name,
                metadata: ['template_id' => $template->id]
            );
        }

        return redirect()
            ->route('permission-toolkit.handover.templates.index')
            ->with('status', __('permission-toolkit::messages.handover_template_updated'));
    }

    /**
     * Set the specified template as the default.
     */
    public function setDefault($id)
    {
        if (! config('permission-toolkit.handover.enabled', true)) {
            abort(404);
        }

        $template = HandoverTemplate::findOrFail($id);
        $template->makeDefault();

        if (auth()->check() && AuditLogger::isEnabled()) {
            AuditLogger::log(
                targetUser: auth()->user(),
                action: 'set_default',
                type: 'handover_template',
                targetName: $template->name,
                metadata: ['template_id' => $template->id]
            );
        }

        return redirect()
            ->route('permission-toolkit.handover.templates.index')
            ->with('status', __('permission-toolkit::messages.handover_template_set_default'));
    }

    /**
     * Remove the specified handover template from storage.
     */
    public function destroy($id)
    {
        if (! config('permission-toolkit.handover.enabled', true)) {
            abort(404);
        }

        $template = HandoverTemplate::findOrFail($id);
        $wasDefault = $template->is_default;
        $name = $template->name;

        $template->delete();

        if ($wasDefault) {
            $next = HandoverTemplate::first();
            if ($next) {
                $next->update(['is_default' => true]);
            }
        }

        if (auth()->check() && AuditLogger::isEnabled()) {
            AuditLogger::log(
                targetUser: auth()->user(),
                action: 'deleted',
                type: 'handover_template',
                targetName: $name,
                metadata: ['template_id' => $id]
            );
        }

        return redirect()
            ->route('permission-toolkit.handover.templates.index')
            ->with('status', __('permission-toolkit::messages.handover_template_deleted'));
    }
}
