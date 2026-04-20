<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FieldVerification extends Model
{
    protected $table = 'client_loan_field_verifications';

    protected $fillable = [
        'application_id', 'verified_by', 'visit_start', 'visit_end',
        // KYC
        'idv', 'idv_status',
        'pvs', 'pvs_status',
        'avs', 'avs_status',
        'years_residence_v', 'res_landmark_seen', 'home_door_color_seen',
        'next_of_kin_v', 'next_of_kin_status',
        // In-person integrity
        'gps_capture', 'device_id',
        'client_home_photo', 'client_business_photo', 'customer_unposed_photo',
        'officer_selfie_client', 'live_business_stock_photo',
        'on_site_question', 'on_site_answer',
        // Business & cash flow
        'v_monthly_sales', 'v_other_income', 'v_cogs', 'v_opex', 'v_household_expenses', 'v_loan_installment',
        'sales_record_seen', 'mobile_money_seen', 'supplier_confirmed', 'supplier_confirmed_name',
        'business_open_v', 'business_open_days_v', 'peak_hours_v', 'avg_customers_v',
        // CRB
        'crb_defaults', 'crb_arrears', 'crb_nxt_count', 'crb_ext_inst', 'crb_skip_flag',
        // Collateral 1
        'coll_1_vmv', 'coll_1_enc', 'coll_1_physically_inspected', 'coll_1_ownership_accepted',
        'coll_1_pledge_signed', 'coll_1_photo', 'coll_1_customary_verified',
        // Collateral 2
        'coll_2_vmv', 'coll_2_enc', 'coll_2_physically_inspected', 'coll_2_ownership_accepted',
        'coll_2_pledge_signed', 'coll_2_photo', 'coll_2_customary_verified',
        // Social/Community
        'lc1_name_confirmed', 'lc1_contact_confirmed', 'lc1_letter_sighted',
        'clan_name_confirmed', 'clan_contact_confirmed', 'clan_letter_sighted',
        'ref1_contacted', 'ref2_contacted', 'ref_consistent_count', 'disputes_reported',
        'residence_stability_evi',
        // Guarantor 1
        'g1_contact_verified', 'g1_income_verified', 'g1_asset_verified',
        'g1_relationship_confirmed', 'g1_willing', 'g1_signed',
        // Guarantor 2
        'g2_contact_verified', 'g2_income_verified', 'g2_asset_verified',
        'g2_relationship_confirmed', 'g2_willing', 'g2_signed',
        // Policy controls
        'contradiction_count', 'time_constraint', 'temp_trigger',
        'field_recommendation', 'physical_visit_confirmed', 'remote_risk_note', 'officer_notes',
    ];

    protected $casts = [
        'visit_start'                   => 'datetime',
        'visit_end'                     => 'datetime',
        'idv'                           => 'boolean',
        'pvs'                           => 'boolean',
        'avs'                           => 'boolean',
        'next_of_kin_v'                 => 'boolean',
        'sales_record_seen'             => 'boolean',
        'mobile_money_seen'             => 'boolean',
        'supplier_confirmed'            => 'boolean',
        'business_open_v'               => 'boolean',
        'crb_skip_flag'                 => 'boolean',
        'coll_1_physically_inspected'   => 'boolean',
        'coll_1_ownership_accepted'     => 'boolean',
        'coll_1_pledge_signed'          => 'boolean',
        'coll_1_customary_verified'     => 'boolean',
        'coll_2_physically_inspected'   => 'boolean',
        'coll_2_ownership_accepted'     => 'boolean',
        'coll_2_pledge_signed'          => 'boolean',
        'coll_2_customary_verified'     => 'boolean',
        'lc1_name_confirmed'            => 'boolean',
        'lc1_contact_confirmed'         => 'boolean',
        'lc1_letter_sighted'            => 'boolean',
        'clan_name_confirmed'           => 'boolean',
        'clan_contact_confirmed'        => 'boolean',
        'clan_letter_sighted'           => 'boolean',
        'ref1_contacted'                => 'boolean',
        'ref2_contacted'                => 'boolean',
        'disputes_reported'             => 'boolean',
        'g1_contact_verified'           => 'boolean',
        'g1_relationship_confirmed'     => 'boolean',
        'g1_willing'                    => 'boolean',
        'g1_signed'                     => 'boolean',
        'g2_contact_verified'           => 'boolean',
        'g2_relationship_confirmed'     => 'boolean',
        'g2_willing'                    => 'boolean',
        'g2_signed'                     => 'boolean',
        'time_constraint'               => 'boolean',
        'temp_trigger'                  => 'boolean',
        'physical_visit_confirmed'      => 'boolean',
        'v_monthly_sales'               => 'decimal:2',
        'v_other_income'                => 'decimal:2',
        'v_cogs'                        => 'decimal:2',
        'v_opex'                        => 'decimal:2',
        'v_household_expenses'          => 'decimal:2',
        'v_loan_installment'            => 'decimal:2',
        'coll_1_vmv'                    => 'decimal:2',
        'coll_2_vmv'                    => 'decimal:2',
        'g1_income_verified'            => 'decimal:2',
        'g1_asset_verified'             => 'decimal:2',
        'g2_income_verified'            => 'decimal:2',
        'g2_asset_verified'             => 'decimal:2',
        'crb_ext_inst'                  => 'decimal:2',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(ClientLoanApplication::class, 'application_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
