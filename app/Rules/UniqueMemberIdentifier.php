<?php

namespace App\Rules;

use App\Models\Member;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UniqueMemberIdentifier implements ValidationRule
{
    protected $field;
    protected $excludeId;

    public function __construct(string $field, ?int $excludeId = null)
    {
        $this->field = $field;
        $this->excludeId = $excludeId;
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $exists = false;
        $existingMember = null;

        switch ($this->field) {
            case 'nin':
                $exists = Member::checkNinExists($value, $this->excludeId);
                if ($exists) {
                    $existingMember = Member::notDeleted()->where('nin', $value)->first();
                }
                break;
                
            case 'contact':
                $exists = Member::checkContactExists($value, $this->excludeId);
                if ($exists) {
                    $existingMember = Member::notDeleted()->where('contact', $value)->first();
                }
                break;
                
            case 'email':
                if (!empty($value)) {
                    $exists = Member::checkEmailExists($value, $this->excludeId);
                    if ($exists) {
                        $existingMember = Member::notDeleted()->where('email', $value)->first();
                    }
                }
                break;
        }

        if ($exists && $existingMember) {
            $fieldName = match($this->field) {
                'nin' => 'National ID Number',
                'contact' => 'Contact Number',
                'email' => 'Email Address',
                default => ucfirst($this->field)
            };

            $fail("This {$fieldName} is already registered to member: {$existingMember->fname} {$existingMember->lname} (Code: {$existingMember->code})");
        }
    }
}
