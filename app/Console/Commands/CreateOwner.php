<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

final class CreateOwner extends Command
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * @var string
     */
    protected $signature = 'owner:create';

    /**
     * @var string
     */
    protected $description = 'Create the account that owns this portfolio install';

    public function handle(): int
    {
        if (User::query()->exists()) {
            $this->error('An owner account already exists.');

            return self::FAILURE;
        }

        $attributes = [
            'name' => $this->ask('Name'),
            'email' => $this->ask('Email'),
            'password' => $this->secret('Password'),
            'password_confirmation' => $this->secret('Confirm password'),
        ];

        $validator = Validator::make($attributes, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        /** @var array<string, mixed> $attributes */
        $attributes = $validator->safe()->only(['name', 'email', 'password']);

        $owner = new User($attributes);
        $owner->is_admin = true;
        $owner->save();
        $owner->markEmailAsVerified();

        $this->info('Owner account created.');

        return self::SUCCESS;
    }
}
