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
                $table->string('application_name')->nullable();
                $table->string('company_name')->nullable();
                $table->string('department')->nullable();
                $table->string('signer_title');
                $table->string('signer_name');
                $table->string('second_signer_title')->nullable();
                $table->string('second_signer_name')->nullable();
                $table->text('disclaimer_text');
                $table->text('notes')->nullable();
                $table->boolean('is_default')->default(false)->index();
                $table->timestamps();
            });
        } else {
            Schema::table($tableName, function (Blueprint $table) {
                if (! Schema::hasColumn($table->getTable(), 'application_name')) {
                    $table->string('application_name')->nullable()->after('name');
                }
                if (! Schema::hasColumn($table->getTable(), 'second_signer_title')) {
                    $table->string('second_signer_title')->nullable()->after('signer_name');
                }
                if (! Schema::hasColumn($table->getTable(), 'second_signer_name')) {
                    $table->string('second_signer_name')->nullable()->after('second_signer_title');
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
        Schema::dropIfExists($tableName);
    }
};
