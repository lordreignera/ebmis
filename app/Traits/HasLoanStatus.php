<?php

namespace App\Traits;

trait HasLoanStatus
{
    /**
     * Get actual loan status based on database status and schedules validation
     * 
     * This method provides runtime validation of loan status to detect data inconsistencies
     * from legacy systems that may have incorrectly marked loans as closed.
     * 
     * Status codes:
     * 0 = Pending (Application submitted, not yet approved)
     * 1 = Approved (Approved but not disbursed)
     * 2 = Disbursed/Active (Money given out, being repaid)
     * 3 = Closed (Fully paid)
     * 4 = Rejected (Application rejected)
     * 5 = Restructured (Original loan replaced by restructured loan)
     * 6 = Stopped (Manually stopped by administrator)
     * 
     * @return string 'pending'|'approved'|'running'|'closed'|'rejected'|'restructured'|'stopped'|'completed'|'disbursed'|'unknown'
     */
    public function getActualStatus()
    {
        // Rejected loans
        if ($this->status == 4) {
            return 'rejected';
        }
        
        // Restructured loans (original loan replaced)
        if ($this->status == 5) {
            return 'restructured';
        }
        
        // Stopped loans (manually stopped by admin)
        if ($this->status == 6) {
            return 'stopped';
        }
        
        // Pending loans (not yet approved)
        if ($this->status == 0) {
            return 'pending';
        }
        
        // Approved loans (approved but not yet disbursed)
        if ($this->status == 1) {
            return 'approved';
        }
        
        // Status 3 (Closed) - VALIDATE AGAINST SCHEDULES
        // This is critical for detecting incorrectly closed loans from legacy systems
        if ($this->status == 3) {
            // Use loaded relationship if available, otherwise query
            $schedules = $this->relationLoaded('schedules') ? $this->schedules : $this->schedules()->get();
            
            if ($schedules->isEmpty()) {
                return 'closed'; // No schedules = truly closed
            }
            
            // Check for unpaid schedules (status != 1 means unpaid)
            $unpaidSchedules = $schedules->where('status', '!=', 1)->count();
            
            if ($unpaidSchedules > 0) {
                // INCORRECTLY CLOSED - has unpaid schedules
                // This loan was marked as closed but still has outstanding payments
                return 'running';
            }
            
            return 'closed'; // Truly closed - all schedules paid
        }
        
        // Status 2 (Disbursed) - check if all schedules are paid
        if ($this->status == 2) {
            // Use loaded relationship if available, otherwise query
            $schedules = $this->relationLoaded('schedules') ? $this->schedules : $this->schedules()->get();
            
            if ($schedules->isEmpty()) {
                return 'disbursed'; // No schedules generated yet
            }
            
            // Check for unpaid schedules
            $unpaidSchedules = $schedules->where('status', '!=', 1)->count();
            
            if ($unpaidSchedules == 0) {
                // All paid but status not updated to closed yet
                // Should be auto-closed by system
                return 'completed';
            }
            
            return 'running'; // Has unpaid schedules - actively being repaid
        }
        
        return 'unknown';
    }
    
    /**
     * Accessor for actual_status attribute
     * Allows using $loan->actual_status
     */
    public function getActualStatusAttribute()
    {
        return $this->getActualStatus();
    }
    
    /**
     * Get status badge HTML for display
     * 
     * @return string HTML badge element
     */
    public function getStatusBadgeAttribute()
    {
        $actualStatus = $this->actual_status;

        switch ($actualStatus) {
            case 'pending':
                return '<span class="badge bg-warning">Pending</span>';
            case 'approved':
                return '<span class="badge bg-info">Approved</span>';
            case 'running':
                return '<span class="badge bg-success">Running</span>';
            case 'completed':
                return '<span class="badge bg-primary">Completed</span>';
            case 'closed':
                return '<span class="badge bg-secondary">Closed</span>';
            case 'rejected':
                return '<span class="badge bg-danger">Rejected</span>';
            case 'restructured':
                return '<span class="badge bg-purple">Restructured</span>';
            case 'stopped':
                return '<span class="badge bg-dark">Stopped</span>';
            case 'disbursed':
                return '<span class="badge bg-cyan">Disbursed</span>';
            default:
                return '<span class="badge bg-light text-dark">Unknown</span>';
        }
    }
    
    /**
     * Check if loan is active (running)
     * 
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->getActualStatus() === 'running';
    }
    
    /**
     * Check if loan is closed
     * 
     * @return bool
     */
    public function isClosed(): bool
    {
        return in_array($this->getActualStatus(), ['closed', 'completed']);
    }
    
    /**
     * Check if loan can receive payments
     * 
     * @return bool
     */
    public function canReceivePayments(): bool
    {
        $status = $this->getActualStatus();
        return in_array($status, ['running', 'completed', 'disbursed']);
    }
}
