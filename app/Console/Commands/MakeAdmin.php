<?php

/**
 * Khaneh Romantic — Create or promote a panel admin
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 *   php artisan khaneh:admin ali@example.com --name="Ali Fasihi"
 */

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

use function Laravel\Prompts\password;

class MakeAdmin extends Command
{
    protected $signature = 'khaneh:admin {email} {--name=} {--password=}';

    protected $description = 'Create an admin user (or promote an existing one) for /admin';

    public function handle(): int
    {
        $email = Str::lower($this->argument('email'));
        $user = User::query()->firstWhere('email', $email);

        if ($user) {
            $user->forceFill(['is_admin' => true])->save();
            $this->components->info("{$email} is now an admin.");

            return self::SUCCESS;
        }

        $secret = $this->option('password') ?: password('Password', required: true, validate: fn ($v) => strlen($v) < 10 ? 'At least 10 characters.' : null);

        User::query()->forceCreate([
            'name' => $this->option('name') ?: Str::before($email, '@'),
            'email' => $email,
            'password' => $secret,
            'is_admin' => true,
            'email_verified_at' => now(),
        ]);

        $this->components->info("Admin {$email} created. Sign in at ".url('/admin'));

        return self::SUCCESS;
    }
}
