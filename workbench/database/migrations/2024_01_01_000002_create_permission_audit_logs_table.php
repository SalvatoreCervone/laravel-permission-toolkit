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
        $tableName = config('permission-toolkit.audit.table', 'permission_audit_logs');

        Schema::create($tableName, function (Blueprint $table) {
            $table->id();
            $table->nullableMorphs('causer'); // Who made the change (Admin/User)
            $table->morphs('user');          // Target user affected
            $table->string('action');        // 'assigned', 'revoked', 'synced'
            $table->string('type');          // 'role', 'permission'
            $table->string('target_name');   // Name of role or permission
            $table->unsignedBigInteger('team_id')->nullable()->index(); // Spatie Teams support
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tableName = config('permission-toolkit.audit.table', 'permission_audit_logs');
        Schema::dropIfExists($tableName);
    }
};
