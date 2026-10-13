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

        if (Schema::hasTable($tableName)) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (! Schema::hasColumn($tableName, 'application_name')) {
                    $table->string('application_name')->nullable()->after('name');
                }
                if (! Schema::hasColumn($tableName, 'second_signer_title')) {
                    $table->string('second_signer_title')->nullable()->after('signer_name');
                }
                if (! Schema::hasColumn($tableName, 'second_signer_name')) {
                    $table->string('second_signer_name')->nullable()->after('second_signer_title');
                }
                if (! Schema::hasColumn($tableName, 'reference_code')) {
                    $table->string('reference_code')->nullable()->after('second_signer_name');
                }
                if (! Schema::hasColumn($tableName, 'status_label')) {
                    $table->string('status_label')->nullable()->after('reference_code');
                }
                if (! Schema::hasColumn($tableName, 'footer_text')) {
                    $table->string('footer_text')->nullable()->after('notes');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tableName = config('permission-toolkit.handover.table', 'permission_handover_templates');

        if (Schema::hasTable($tableName)) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $columns = ['application_name', 'second_signer_title', 'second_signer_name', 'reference_code', 'status_label', 'footer_text'];
                foreach ($columns as $column) {
                    if (Schema::hasColumn($tableName, $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
