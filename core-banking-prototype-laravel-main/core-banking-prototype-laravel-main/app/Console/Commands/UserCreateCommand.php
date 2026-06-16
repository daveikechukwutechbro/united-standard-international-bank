<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

class UserCreateCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'user:create
                            {--name= : Full name}
                            {--email= : Email address}
                            {--password= : Password (min 8 chars — prefer omitting to be prompted securely)}
                            {--admin : Assign admin role}';

    /**
     * @var string
     */
    protected $description = 'Create a new user account (production-safe alternative to web registration)';

    public function handle(): int
    {
        $name = $this->option('name');
        $name = is_string($name) && $name !== '' ? $name : $this->ask('Full name');

        $email = $this->option('email');
        $email = is_string($email) && $email !== '' ? $email : $this->ask('Email address');

        $password = $this->option('password');
        if (is_string($password) && $password !== '') {
            $this->warn('Password visible in shell history. Prefer omitting --password to be prompted securely.');
        } else {
            $password = $this->secret('Password (min 8 characters)');
        }

        $makeAdmin = (bool) $this->option('admin');

        $validator = Validator::make([
            'name'     => $name,
            'email'    => $email,
            'password' => $password,
        ], [
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return 1;
        }

        $user = User::create([
            'name'     => $name,
            'email'    => $email,
            'password' => Hash::make((string) $password),
        ]);

        if ($makeAdmin) {
            Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
            $user->assignRole('admin');
        }

        Log::info('User created via CLI', [
            'user_id' => $user->id,
            'email'   => $user->email,
            'admin'   => $makeAdmin,
        ]);

        $this->info("User created: {$user->email} (ID: {$user->id})");
        if ($makeAdmin) {
            $this->info('Admin role assigned — can access /admin dashboard.');
        }

        return 0;
    }
}
