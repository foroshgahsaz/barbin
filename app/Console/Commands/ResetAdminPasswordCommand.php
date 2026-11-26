<?php

namespace App\Console\Commands;

use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class ResetAdminPasswordCommand extends Command
{
    protected $signature = 'barbin:reset-admin
                            {email=admin@barbin.local : ایمیل مدیر}
                            {--password=password : رمز جدید}';

    protected $description = 'ایجاد یا بازنشانی کاربر مدیر کل و نقش super_admin';

    public function handle(): int
    {
        $email = (string) $this->argument('email');
        $password = (string) $this->option('password');

        $user = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => 'مدیر کل',
                'password' => Hash::make($password),
                'is_active' => true,
            ],
        );

        $user->syncRoles([RoleName::SuperAdmin->value]);

        $this->info("مدیر آماده است: {$email}");

        return self::SUCCESS;
    }
}