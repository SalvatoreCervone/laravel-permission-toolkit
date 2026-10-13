<?php

namespace SalvatoreCervone\PermissionToolkit\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class HandoverTemplate extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function getTable()
    {
        return config('permission-toolkit.handover.table', 'permission_handover_templates');
    }

    /**
     * Scope query to only default template.
     */
    public function scopeDefault(Builder $query): Builder
    {
        return $query->where('is_default', true);
    }

    /**
     * Get the default template or a fallback.
     */
    public static function getDefault(): self
    {
        $default = static::where('is_default', true)->first()
            ?? static::first();

        if ($default) {
            return $default;
        }

        // Return an in-memory fallback template with default configuration values
        return new static([
            'id' => null,
            'name' => 'Standard',
            'company_name' => config('permission-toolkit.handover.default_company_name', config('app.name', 'Enterprise Corp')),
            'department' => null,
            'signer_title' => config('permission-toolkit.handover.default_signer_title', 'Il Responsabile della Sicurezza IT'),
            'signer_name' => config('permission-toolkit.handover.default_signer_name', 'Amministratore di Sistema'),
            'disclaimer_text' => config('permission-toolkit.handover.default_disclaimer', "Il sottoscritto dichiara di aver ricevuto le credenziali di accesso al sistema informativo e la presente attestazione relativa ai ruoli e permessi associati alla propria utenza. Dichiara altresì di essere a conoscenza delle proprie responsabilità in materia di riservatezza, custodia delle credenziali e trattamento dei dati ai sensi della normativa vigente (Regolamento UE 2016/679 - GDPR) e delle policy aziendali."),
            'notes' => null,
            'is_default' => true,
        ]);
    }

    /**
     * Set this template as the default.
     */
    public function makeDefault(): self
    {
        static::where('id', '!=', $this->id)->where('is_default', true)->update(['is_default' => false]);
        $this->update(['is_default' => true]);

        return $this;
    }
}
