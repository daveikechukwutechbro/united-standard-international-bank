<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class UserDemoteCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'user:demote
                            {email : Email of the user to demote}
                            {--role=admin : Role to remove (admin, super_admin)}';

    /**
     * @var string
     */
    protected $description = 'Remove admin or super_admin role from a user';

    public function handle(): int
    {
        $email = (string) $this->argument('email');
        $roleName = (string) $this->option('role');

        $allowedRoles = ['admin', 'super_admin'];
        if (! in_array($roleName, $allowedRoles, true)) {
            $this->error("Invalid role: {$roleName}. Allowed: " . implode(', ', $allowedRoles));

            return 1;
        }

        $user = User::where('email', $email)->first();
        if ($user === null) {
            $this->error("User not found: {$email}");

            return 1;
        }

        if (! $user->hasRole($roleName)) {
            $this->info("{$email} does not have the '{$roleName}' role.");

            return 0;
        }

        // Prevent removing the last admin — would lock everyone out of /admin
        if ($roleName === 'admin') {
            $adminCount = User::role('admin')->count();
            if ($adminCount <= 1) {
                $this->error('Cannot remove the last admin. Promote another user first.');

                return 1;
            }
        }

        $user->removeRole($roleName);

        Log::info('Admin role removed via CLI', [
            'user_id' => $user->id,
            'email'   => $email,
            'role'    => $roleName,
        ]);

        $this->info("Removed '{$roleName}' role from {$email}.");

        return 0;
    }
}
