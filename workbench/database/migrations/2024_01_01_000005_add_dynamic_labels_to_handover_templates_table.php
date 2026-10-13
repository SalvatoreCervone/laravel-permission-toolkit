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
                if (! Schema::hasColumn($tableName, 'doc_title')) {
                    $table->string('doc_title')->nullable()->after('name');
                }
                if (! Schema::hasColumn($tableName, 'doc_subtitle')) {
                    $table->text('doc_subtitle')->nullable()->after('doc_title');
                }
                if (! Schema::hasColumn($tableName, 'signer_label')) {
                    $table->string('signer_label')->nullable()->after('signer_name');
                }
                if (! Schema::hasColumn($tableName, 'second_signer_label')) {
                    $table->string('second_signer_label')->nullable()->after('second_signer_name');
                }
                if (! Schema::hasColumn($tableName, 'user_signer_label')) {
                    $table->string('user_signer_label')->nullable()->after('second_signer_label');
                }
                if (! Schema::hasColumn($tableName, 'user_role_label')) {
                    $table->string('user_role_label')->nullable()->after('user_signer_label');
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
                $columns = ['doc_title', 'doc_subtitle', 'signer_label', 'second_signer_label', 'user_signer_label', 'user_role_label'];
                foreach ($columns as $column) {
                    if (Schema::hasColumn($tableName, $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
