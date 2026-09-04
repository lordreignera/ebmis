<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\Staff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffEnrollmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_generated_staff_ids_are_unique_across_schools(): void
    {
        $firstSchool = $this->school('first@example.test');
        $secondSchool = $this->school('second@example.test');

        $firstStaff = $this->staff($firstSchool->id, 'Grace');
        $secondStaff = $this->staff($secondSchool->id, 'Paul');

        $this->assertNotSame($firstStaff->staff_id, $secondStaff->staff_id);
        $this->assertStringContainsString(str_pad((string) $firstSchool->id, 4, '0', STR_PAD_LEFT), $firstStaff->staff_id);
        $this->assertStringContainsString(str_pad((string) $secondSchool->id, 4, '0', STR_PAD_LEFT), $secondStaff->staff_id);
    }

    public function test_next_of_kin_relationship_is_persisted(): void
    {
        $school = $this->school('kin@example.test');

        $staff = $this->staff($school->id, 'Jane', [
            'next_of_kin_name' => 'John Doe',
            'next_of_kin_phone' => '0772000000',
            'next_of_kin_relationship' => 'Brother',
        ]);

        $this->assertSame('Brother', $staff->fresh()->next_of_kin_relationship);
    }

    private function school(string $email): School
    {
        return School::create([
            'school_name' => 'Test School ' . $email,
            'contact_person' => 'Head Teacher',
            'email' => $email,
            'phone' => '0771000000',
            'admin_password' => 'secret',
            'physical_address' => 'Kampala',
            'district' => 'Kampala',
            'status' => 'approved',
        ]);
    }

    private function staff(int $schoolId, string $firstName, array $overrides = []): Staff
    {
        return Staff::create(array_merge([
            'school_id' => $schoolId,
            'first_name' => $firstName,
            'last_name' => 'Teacher',
            'gender' => 'Female',
            'date_of_birth' => '1990-01-01',
            'phone_number' => '0772000000',
            'staff_type' => 'Teaching',
            'position' => 'Teacher',
            'date_joined' => now()->toDateString(),
            'employment_type' => 'Full-Time',
            'basic_salary' => 500000,
            'allowances' => 100000,
            'payment_frequency' => 'Monthly',
            'status' => 'active',
        ], $overrides));
    }
}
