<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Define all permissions
        $allPermissions = [
            // SUPER ADMIN PERMISSIONS (8)
            'view-all-data', 'manage-schools', 'manage-branches', 'manage-regions',
            'system-settings', 'user-management', 'generate-financial-reports', 'backup-restore',

            // SCHOOL MANAGEMENT PERMISSIONS (11)
            'view-all-schools', 'register-school', 'approve-schools', 'reject-schools', 
            'suspend-schools', 'activate-schools', 'delete-schools', 'view-school-details', 
            'edit-school-details', 'view-school-reports', 'manage-school-users',

            // SCHOOL PORTAL PERMISSIONS (35)
            'view-own-dashboard', 'view-own-students', 'add-student', 'edit-student', 
            'delete-student', 'view-student-details', 'view-own-teachers', 'add-teacher', 
            'edit-teacher', 'delete-teacher', 'view-teacher-details', 'view-own-classes', 
            'create-class', 'edit-class', 'delete-class', 'assign-class-teacher', 
            'view-own-subjects', 'create-subject', 'edit-subject', 'delete-subject', 
            'assign-subject-teacher', 'view-marks-reports', 'enter-marks', 'edit-marks', 
            'generate-report-cards', 'view-attendance', 'mark-attendance', 'edit-attendance', 
            'generate-attendance-reports', 'view-fee-structure', 'set-fee-structure', 
            'record-fee-payment', 'view-fee-reports', 'send-fee-reminders', 
            'view-school-calendar', 'manage-school-events',

            // BRANCH MANAGEMENT PERMISSIONS (7)
            'view-all-branches', 'create-branch', 'edit-branch', 'delete-branch', 
            'assign-branch-manager', 'view-branch-reports', 'manage-branch-users',

            // BRANCH PORTAL PERMISSIONS (25)
            'view-own-clients', 'add-client', 'edit-client', 'delete-client', 
            'view-client-details', 'create-loan-application', 'edit-loan-application', 
            'approve-loan', 'reject-loan', 'disburse-loan', 'view-loan-schedule', 
            'record-repayment', 'view-repayment-history', 'generate-loan-reports', 
            'manage-savings-accounts', 'record-savings-deposit', 'record-savings-withdrawal', 
            'view-savings-reports', 'create-group', 'manage-group-members', 
            'create-group-loan', 'view-group-reports', 'generate-member-statements', 
            'send-sms-notifications', 'view-branch-dashboard',

            // REGIONAL PERMISSIONS (6)
            'view-region-dashboard', 'view-region-branches', 'view-region-reports', 
            'manage-region-staff', 'approve-region-loans', 'view-region-performance',

            // STAFF LOANS PERMISSIONS (6)
            'apply-staff-loan', 'view-own-staff-loans', 'approve-staff-loan', 
            'disburse-staff-loan', 'view-staff-loan-reports', 'manage-staff-loan-products',

            // PAYROLL PERMISSIONS (8)
            'view-payroll-dashboard', 'process-payroll', 'calculate-salaries', 
            'generate-payslips', 'manage-salary-structures', 'record-deductions', 
            'view-payroll-reports', 'approve-salary-payments',
        ];

        // Create permissions only if they don't exist
        foreach ($allPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $this->command->info('✅ All permissions created successfully!');
        $this->command->info('📊 Total permissions: ' . count($allPermissions));
    }
}
