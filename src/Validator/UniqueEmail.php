<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
class UniqueEmail extends Constraint
{
    public string $message = 'L\'email "{{ value }}" est déjà utilisé.';

    public function validatedBy(): string
    {
        return static::class . 'Validator';
    }
}
