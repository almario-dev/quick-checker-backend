<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class IsExistsRule implements ValidationRule
{
    protected mixed $query;
    protected ?string $column;
    protected bool $errWhen;
    protected ?string $err;

    public function __construct(mixed $query, ?string $column = null, bool $errWhen = true, ?string $err = null)
    {
        $this->query = $query;
        $this->column = $column;
        $this->errWhen = $errWhen;
        $this->err = $err;
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $q = $this->query;

        if ($this->column !== null) {
            $q = $q->where($this->column, $value);
        }

        $result = $q->exists();

        if ($result === $this->errWhen) {
            $errClause = $result ? 'already exists.' : 'does not exist.';
            $fail($this->err ?? "{$value} {$errClause}");
        }
    }
}