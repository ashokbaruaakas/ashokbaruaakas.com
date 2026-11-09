<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Fortify\ResetUserPassword;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

use function Laravel\Prompts\password;

final class ResetOwnerPassword extends Command
{
    /**
     * @var string
     */
    protected $signature = 'owner:reset-password';

    /**
     * @var string
     */
    protected $description = 'Set a new password for the owner account, for when you cannot sign in';

    public function handle(ResetUserPassword $resetUserPassword): int
    {
        $owner = User::query()->first();

        if ($owner === null) {
            $this->error('No owner account exists yet. Create one with owner:create.');

            return self::FAILURE;
        }

        $this->info("Resetting the password for {$owner->email}.");

        try {
            $resetUserPassword->reset($owner, [
                'password' => password('New password'),
                'password_confirmation' => password('Confirm new password'),
            ]);
        } catch (ValidationException $exception) {
            foreach ($exception->validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $owner->forceFill(['remember_token' => Str::random(60)])->save();

        $this->info('Password reset. Sign in at ' . route('login') . ' with the new password. Every other signed-in browser is signed out.');

        return self::SUCCESS;
    }
}
