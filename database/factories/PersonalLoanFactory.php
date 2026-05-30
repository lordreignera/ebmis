<?php

namespace Database\Factories;

use App\Models\Member;
use App\Models\PersonalLoan;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PersonalLoan>
 */
class PersonalLoanFactory extends Factory
{
    protected $model = PersonalLoan::class;

    public function definition(): array
    {
        return [
            'member_id' => Member::factory(),
            'product_type' => fn () => $this->productId(),
            'code' => 'LN-' . fake()->unique()->numerify('######'),
            'interest' => '5',
            'period' => '12',
            'principal' => '100000',
            'installment' => '10000',
            'status' => 2,
            'verified' => 1,
            'added_by' => fn () => User::factory()->create()->id,
            'approved_by' => null,
            'date_approved' => now(),
            'branch_id' => fn () => $this->branchId(),
            'charge_type' => 1,
            'sign_code' => fake()->numberBetween(100000, 999999),
            'restructured' => 0,
            'assigned_to' => fn () => User::factory()->create()->id,
            'datecreated' => now(),
        ];
    }

    private function productId(): int
    {
        $existingProductId = Product::query()->value('id');

        if ($existingProductId) {
            return $existingProductId;
        }

        return Product::create([
            'code' => 'LP001',
            'name' => 'Test Loan Product',
            'type' => 1,
            'loan_type' => 1,
            'description' => 'Test personal loan product',
            'max_amt' => '10000000',
            'interest' => '5',
            'period_type' => 3,
            'cash_sceurity' => '25',
            'account' => 1,
            'isactive' => 1,
            'added_by' => User::factory()->create()->id,
        ])->id;
    }

    private function branchId(): int
    {
        return DB::table('branches')->value('id')
            ?? DB::table('branches')->insertGetId([
                'name' => 'Main Branch',
                'address' => 'Kampala',
                'phone' => '0700000000',
                'email' => 'branch@example.test',
                'country_id' => $this->countryId(),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
    }

    private function countryId(): int
    {
        return DB::table('countries')->value('id')
            ?? DB::table('countries')->insertGetId([
                'name' => 'Uganda',
                'code' => 'UG',
                'is_active' => true,
                'date_created' => now(),
            ]);
    }
}
