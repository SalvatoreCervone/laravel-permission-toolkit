<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tableName = config('permission-toolkit.handover.table', 'permission_handover_templates');

        if (! Schema::hasTable($tableName)) {
            Schema::create($tableName, function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('company_name')->nullable();
                $table->string('department')->nullable();
                $table->string('signer_title');
                $table->string('signer_name');
                $table->text('disclaimer_text');
                $table->text('notes')->nullable();
                $table->boolean('is_default')->default(false)->index();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tableName = config('permission-toolkit.handover.table', 'permission_handover_templates');
        Schema::dropIfExists($tableName);
    }
};
