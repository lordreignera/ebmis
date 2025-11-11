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
        Schema::table('group_loans', function (Blueprint $table) {
            $table->integer('sign_code')->default(0)->after('date_closed');
            $table->string('OLoanID', 100)->nullable()->after('sign_code');
            $table->text('Rcomments')->nullable()->after('OLoanID');
            $table->integer('restructured')->default(0)->after('Rcomments');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('group_loans', function (Blueprint $table) {
            $table->dropColumn(['sign_code', 'OLoanID', 'Rcomments', 'restructured']);
        });
    }
};
