<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class IsCurrentPasswordRule implements ValidationRule
{
    protected bool $errWhen;
    protected $errMsg;

    public function __construct(bool $errWhen = false, ?string $errMsg = null)
    {
        $this->errWhen = $errWhen;
        $this->errMsg = $errMsg;
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $user = Auth::user();

        if (Hash::check($value, $user->password) === $this->errWhen) {
            $fail($this->errMsg ?? ($this->errWhen ? 'Current password matched.' : 'Current password is incorrect.'));
        }
    }
}