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
        // Add signature columns to personal_loans table
        if (Schema::hasTable('personal_loans')) {
            Schema::table('personal_loans', function (Blueprint $table) {
                if (!Schema::hasColumn('personal_loans', 'otp_code')) {
                    $table->string('otp_code', 10)->nullable()->after('verified');
                }
                if (!Schema::hasColumn('personal_loans', 'otp_expires_at')) {
                    $table->timestamp('otp_expires_at')->nullable()->after('otp_code');
                }
                if (!Schema::hasColumn('personal_loans', 'signature_status')) {
                    $table->enum('signature_status', ['pending', 'signed', 'expired'])->nullable()->after('otp_expires_at');
                }
                if (!Schema::hasColumn('personal_loans', 'signature_date')) {
                    $table->timestamp('signature_date')->nullable()->after('signature_status');
                }
                if (!Schema::hasColumn('personal_loans', 'signature_comments')) {
                    $table->text('signature_comments')->nullable()->after('signature_date');
                }
            });
        }

        // Add signature columns to group_loans table
        if (Schema::hasTable('group_loans')) {
            Schema::table('group_loans', function (Blueprint $table) {
                if (!Schema::hasColumn('group_loans', 'otp_code')) {
                    $table->string('otp_code', 10)->nullable()->after('verified');
                }
                if (!Schema::hasColumn('group_loans', 'otp_expires_at')) {
                    $table->timestamp('otp_expires_at')->nullable()->after('otp_code');
                }
                if (!Schema::hasColumn('group_loans', 'signature_status')) {
                    $table->enum('signature_status', ['pending', 'signed', 'expired'])->nullable()->after('otp_expires_at');
                }
                if (!Schema::hasColumn('group_loans', 'signature_date')) {
                    $table->timestamp('signature_date')->nullable()->after('signature_status');
                }
                if (!Schema::hasColumn('group_loans', 'signature_comments')) {
                    $table->text('signature_comments')->nullable()->after('signature_date');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove signature columns from personal_loans table
        if (Schema::hasTable('personal_loans')) {
            Schema::table('personal_loans', function (Blueprint $table) {
                $columnsToRemove = ['otp_code', 'otp_expires_at', 'signature_status', 'signature_date', 'signature_comments'];
                foreach ($columnsToRemove as $column) {
                    if (Schema::hasColumn('personal_loans', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        // Remove signature columns from group_loans table
        if (Schema::hasTable('group_loans')) {
            Schema::table('group_loans', function (Blueprint $table) {
                $columnsToRemove = ['otp_code', 'otp_expires_at', 'signature_status', 'signature_date', 'signature_comments'];
                foreach ($columnsToRemove as $column) {
                    if (Schema::hasColumn('group_loans', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};