<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loan_collateral_documents', function (Blueprint $table) {
            $table->decimal('estimated_value', 15, 2)->nullable()->after('document_type');
            $table->decimal('forced_sale_value', 15, 2)->nullable()->after('estimated_value');
        });
    }

    public function down(): void
    {
        Schema::table('loan_collateral_documents', function (Blueprint $table) {
            $table->dropColumn(['estimated_value', 'forced_sale_value']);
        });
    }
};
