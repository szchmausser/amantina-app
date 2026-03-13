<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            ...$this->profileRules(userId: null, requireContactInfo: true),
            'password' => $this->passwordRules(),
        ])->validate();

        return User::create([
            'cedula' => $input['cedula'],
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => $input['password'],
            'phone' => $input['phone'],
            'address' => $input['address'],
            'is_transfer' => $input['is_transfer'] ?? false,
            'institution_origin' => ($input['is_transfer'] ?? false)
                                        ? ($input['institution_origin'] ?? null)
                                        : Institution::first()?->name,
            'is_active' => true,
        ]);
    }
}
