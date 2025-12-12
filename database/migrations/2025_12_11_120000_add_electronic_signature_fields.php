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
        // Add electronic signature fields to personal_loans table
        if (Schema::hasTable('personal_loans')) {
            Schema::table('personal_loans', function (Blueprint $table) {
                // Loan Purpose - what the loan will be used for
                if (!Schema::hasColumn('personal_loans', 'loan_purpose')) {
                    $table->text('loan_purpose')->nullable()->after('comments');
                }
                
                // Cash Account Information
                if (!Schema::hasColumn('personal_loans', 'cash_account_number')) {
                    $table->string('cash_account_number')->nullable()->after('loan_purpose');
                }
                if (!Schema::hasColumn('personal_loans', 'cash_account_name')) {
                    $table->string('cash_account_name')->nullable()->after('cash_account_number');
                }
                
                // Collateral Details - manually entered non-cash collateral
                if (!Schema::hasColumn('personal_loans', 'immovable_assets')) {
                    $table->text('immovable_assets')->nullable()->after('cash_account_name');
                }
                if (!Schema::hasColumn('personal_loans', 'moveable_assets')) {
                    $table->text('moveable_assets')->nullable()->after('immovable_assets');
                }
                if (!Schema::hasColumn('personal_loans', 'intellectual_property')) {
                    $table->text('intellectual_property')->nullable()->after('moveable_assets');
                }
                if (!Schema::hasColumn('personal_loans', 'stocks_collateral')) {
                    $table->text('stocks_collateral')->nullable()->after('intellectual_property');
                }
                if (!Schema::hasColumn('personal_loans', 'livestock_collateral')) {
                    $table->text('livestock_collateral')->nullable()->after('stocks_collateral');
                }
                
                // Group-specific fields (for group loans, but keeping in personal for consistency)
                if (!Schema::hasColumn('personal_loans', 'group_banker_name')) {
                    $table->string('group_banker_name')->nullable()->after('livestock_collateral');
                }
                if (!Schema::hasColumn('personal_loans', 'group_banker_nin')) {
                    $table->string('group_banker_nin')->nullable()->after('group_banker_name');
                }
                if (!Schema::hasColumn('personal_loans', 'group_banker_occupation')) {
                    $table->string('group_banker_occupation')->nullable()->after('group_banker_nin');
                }
                if (!Schema::hasColumn('personal_loans', 'group_banker_residence')) {
                    $table->string('group_banker_residence')->nullable()->after('group_banker_occupation');
                }
                
                // Witness Details
                if (!Schema::hasColumn('personal_loans', 'witness_name')) {
                    $table->string('witness_name')->nullable()->after('group_banker_residence');
                }
                if (!Schema::hasColumn('personal_loans', 'witness_nin')) {
                    $table->string('witness_nin')->nullable()->after('witness_name');
                }
                if (!Schema::hasColumn('personal_loans', 'witness_signature')) {
                    $table->text('witness_signature')->nullable()->after('witness_nin');
                }
                if (!Schema::hasColumn('personal_loans', 'witness_signature_type')) {
                    $table->enum('witness_signature_type', ['drawn', 'uploaded'])->nullable()->after('witness_signature');
                }
                if (!Schema::hasColumn('personal_loans', 'witness_signature_date')) {
                    $table->timestamp('witness_signature_date')->nullable()->after('witness_signature_type');
                }
                
                // Borrower Signature
                if (!Schema::hasColumn('personal_loans', 'borrower_signature')) {
                    $table->text('borrower_signature')->nullable()->after('witness_signature_type');
                }
                if (!Schema::hasColumn('personal_loans', 'borrower_signature_type')) {
                    $table->enum('borrower_signature_type', ['drawn', 'uploaded'])->nullable()->after('borrower_signature');
                }
                if (!Schema::hasColumn('personal_loans', 'borrower_signature_date')) {
                    $table->timestamp('borrower_signature_date')->nullable()->after('borrower_signature_type');
                }
                
                // Lender Signature
                if (!Schema::hasColumn('personal_loans', 'lender_signature')) {
                    $table->text('lender_signature')->nullable()->after('borrower_signature_date');
                }
                if (!Schema::hasColumn('personal_loans', 'lender_signature_type')) {
                    $table->enum('lender_signature_type', ['drawn', 'uploaded'])->nullable()->after('lender_signature');
                }
                if (!Schema::hasColumn('personal_loans', 'lender_signature_date')) {
                    $table->timestamp('lender_signature_date')->nullable()->after('lender_signature_type');
                }
                if (!Schema::hasColumn('personal_loans', 'lender_signed_by')) {
                    $table->unsignedBigInteger('lender_signed_by')->nullable()->after('lender_signature_date');
                }
                if (!Schema::hasColumn('personal_loans', 'lender_title')) {
                    $table->string('lender_title')->default('Branch Manager')->after('lender_signed_by');
                }
                
                // Agreement Finalization
                if (!Schema::hasColumn('personal_loans', 'signed_agreement_path')) {
                    $table->string('signed_agreement_path')->nullable()->after('lender_title');
                }
                if (!Schema::hasColumn('personal_loans', 'agreement_finalized_at')) {
                    $table->timestamp('agreement_finalized_at')->nullable()->after('signed_agreement_path');
                }
            });
        }

        // Add electronic signature fields to group_loans table
        if (Schema::hasTable('group_loans')) {
            Schema::table('group_loans', function (Blueprint $table) {
                // Loan Purpose - what the loan will be used for (group-specific)
                if (!Schema::hasColumn('group_loans', 'loan_purpose')) {
                    $table->text('loan_purpose')->nullable()->after('comments');
                }
                
                // Cash Account Information
                if (!Schema::hasColumn('group_loans', 'cash_account_number')) {
                    $table->string('cash_account_number')->nullable()->after('loan_purpose');
                }
                if (!Schema::hasColumn('group_loans', 'cash_account_name')) {
                    $table->string('cash_account_name')->nullable()->after('cash_account_number');
                }
                
                // Collateral Details - manually entered non-cash collateral
                if (!Schema::hasColumn('group_loans', 'immovable_assets')) {
                    $table->text('immovable_assets')->nullable()->after('cash_account_name');
                }
                if (!Schema::hasColumn('group_loans', 'moveable_assets')) {
                    $table->text('moveable_assets')->nullable()->after('immovable_assets');
                }
                if (!Schema::hasColumn('group_loans', 'intellectual_property')) {
                    $table->text('intellectual_property')->nullable()->after('moveable_assets');
                }
                if (!Schema::hasColumn('group_loans', 'stocks_collateral')) {
                    $table->text('stocks_collateral')->nullable()->after('intellectual_property');
                }
                if (!Schema::hasColumn('group_loans', 'livestock_collateral')) {
                    $table->text('livestock_collateral')->nullable()->after('stocks_collateral');
                }
                
                // Group Banker Details (specific to group loans)
                if (!Schema::hasColumn('group_loans', 'group_banker_name')) {
                    $table->string('group_banker_name')->nullable()->after('livestock_collateral');
                }
                if (!Schema::hasColumn('group_loans', 'group_banker_nin')) {
                    $table->string('group_banker_nin')->nullable()->after('group_banker_name');
                }
                if (!Schema::hasColumn('group_loans', 'group_banker_occupation')) {
                    $table->string('group_banker_occupation')->nullable()->after('group_banker_nin');
                }
                if (!Schema::hasColumn('group_loans', 'group_banker_residence')) {
                    $table->string('group_banker_residence')->nullable()->after('group_banker_occupation');
                }
                
                // Group Representative Details
                if (!Schema::hasColumn('group_loans', 'group_representative_name')) {
                    $table->string('group_representative_name')->nullable()->after('group_banker_residence');
                }
                if (!Schema::hasColumn('group_loans', 'group_representative_phone')) {
                    $table->string('group_representative_phone')->nullable()->after('group_representative_name');
                }
                
                // Witness Details
                if (!Schema::hasColumn('group_loans', 'witness_name')) {
                    $table->string('witness_name')->nullable()->after('group_representative_phone');
                }
                if (!Schema::hasColumn('group_loans', 'witness_nin')) {
                    $table->string('witness_nin')->nullable()->after('witness_name');
                }
                if (!Schema::hasColumn('group_loans', 'witness_signature')) {
                    $table->text('witness_signature')->nullable()->after('witness_nin');
                }
                if (!Schema::hasColumn('group_loans', 'witness_signature_type')) {
                    $table->enum('witness_signature_type', ['drawn', 'uploaded'])->nullable()->after('witness_signature');
                }
                if (!Schema::hasColumn('group_loans', 'witness_signature_date')) {
                    $table->timestamp('witness_signature_date')->nullable()->after('witness_signature_type');
                }
                
                // Borrower Signature
                if (!Schema::hasColumn('group_loans', 'borrower_signature')) {
                    $table->text('borrower_signature')->nullable()->after('witness_signature_type');
                }
                if (!Schema::hasColumn('group_loans', 'borrower_signature_type')) {
                    $table->enum('borrower_signature_type', ['drawn', 'uploaded'])->nullable()->after('borrower_signature');
                }
                if (!Schema::hasColumn('group_loans', 'borrower_signature_date')) {
                    $table->timestamp('borrower_signature_date')->nullable()->after('borrower_signature_type');
                }
                
                // Lender Signature
                if (!Schema::hasColumn('group_loans', 'lender_signature')) {
                    $table->text('lender_signature')->nullable()->after('borrower_signature_date');
                }
                if (!Schema::hasColumn('group_loans', 'lender_signature_type')) {
                    $table->enum('lender_signature_type', ['drawn', 'uploaded'])->nullable()->after('lender_signature');
                }
                if (!Schema::hasColumn('group_loans', 'lender_signature_date')) {
                    $table->timestamp('lender_signature_date')->nullable()->after('lender_signature_type');
                }
                if (!Schema::hasColumn('group_loans', 'lender_signed_by')) {
                    $table->unsignedBigInteger('lender_signed_by')->nullable()->after('lender_signature_date');
                }
                if (!Schema::hasColumn('group_loans', 'lender_title')) {
                    $table->string('lender_title')->default('Branch Manager')->after('lender_signed_by');
                }
                
                // Agreement Finalization
                if (!Schema::hasColumn('group_loans', 'signed_agreement_path')) {
                    $table->string('signed_agreement_path')->nullable()->after('lender_title');
                }
                if (!Schema::hasColumn('group_loans', 'agreement_finalized_at')) {
                    $table->timestamp('agreement_finalized_at')->nullable()->after('signed_agreement_path');
                }
            });
        }

        // Add signature fields to guarantors table
        if (Schema::hasTable('guarantors')) {
            Schema::table('guarantors', function (Blueprint $table) {
                if (!Schema::hasColumn('guarantors', 'signature')) {
                    $table->text('signature')->nullable()->after('member_id');
                }
                if (!Schema::hasColumn('guarantors', 'signature_type')) {
                    $table->enum('signature_type', ['drawn', 'uploaded'])->nullable()->after('signature');
                }
                if (!Schema::hasColumn('guarantors', 'signature_date')) {
                    $table->timestamp('signature_date')->nullable()->after('signature_type');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove electronic signature fields from personal_loans table
        if (Schema::hasTable('personal_loans')) {
            Schema::table('personal_loans', function (Blueprint $table) {
                $columnsToRemove = [
                    'loan_purpose', 'cash_account_number', 'cash_account_name', 'immovable_assets', 'moveable_assets', 'intellectual_property',
                    'stocks_collateral', 'livestock_collateral', 'group_banker_name', 'group_banker_nin',
                    'group_banker_occupation', 'group_banker_residence', 'witness_name', 'witness_nin',
                    'witness_signature', 'witness_signature_type', 'witness_signature_date', 'borrower_signature', 'borrower_signature_type',
                    'borrower_signature_date', 'lender_signature', 'lender_signature_type', 'lender_signature_date',
                    'lender_signed_by', 'lender_title', 'signed_agreement_path', 'agreement_finalized_at'
                ];
                
                foreach ($columnsToRemove as $column) {
                    if (Schema::hasColumn('personal_loans', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        // Remove electronic signature fields from group_loans table
        if (Schema::hasTable('group_loans')) {
            Schema::table('group_loans', function (Blueprint $table) {
                $columnsToRemove = [
                    'loan_purpose', 'cash_account_number', 'cash_account_name', 'immovable_assets', 'moveable_assets', 'intellectual_property',
                    'stocks_collateral', 'livestock_collateral', 'group_banker_name', 'group_banker_nin',
                    'group_banker_occupation', 'group_banker_residence', 'group_representative_name',
                    'group_representative_phone', 'witness_name', 'witness_nin', 'witness_signature',
                    'witness_signature_type', 'witness_signature_date', 'borrower_signature', 'borrower_signature_type',
                    'borrower_signature_date', 'lender_signature', 'lender_signature_type', 'lender_signature_date',
                    'lender_signed_by', 'lender_title', 'signed_agreement_path', 'agreement_finalized_at'
                ];
                
                foreach ($columnsToRemove as $column) {
                    if (Schema::hasColumn('group_loans', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        // Remove signature fields from guarantors table
        if (Schema::hasTable('guarantors')) {
            Schema::table('guarantors', function (Blueprint $table) {
                $columnsToRemove = ['signature', 'signature_type', 'signature_date'];
                
                foreach ($columnsToRemove as $column) {
                    if (Schema::hasColumn('guarantors', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
