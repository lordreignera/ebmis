<?php

namespace Database\Factories;

use App\Models\Member;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Member>
 */
class MemberFactory extends Factory
{
    protected $model = Member::class;

    public function definition(): array
    {
        return [
            'code' => 'CL-' . fake()->unique()->numerify('######'),
            'fname' => fake()->firstName(),
            'lname' => fake()->lastName(),
            'mname' => null,
            'nin' => fake()->unique()->bothify('CM##########??'),
            'contact' => fake()->unique()->numerify('07########'),
            'alt_contact' => null,
            'email' => fake()->unique()->safeEmail(),
            'country_id' => fn () => $this->countryId(),
            'gender' => fake()->randomElement(['Male', 'Female']),
            'verified' => true,
            'status' => 'approved',
            'member_type' => fn () => $this->memberTypeId(),
            'branch_id' => fn () => $this->branchId(),
            'soft_delete' => false,
            'added_by' => fn () => User::factory()->create()->id,
            'datecreated' => now(),
        ];
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

    private function memberTypeId(): int
    {
        return DB::table('member_types')->value('id')
            ?? DB::table('member_types')->insertGetId([
                'name' => 'Individual',
                'description' => 'Individual member',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
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
}
