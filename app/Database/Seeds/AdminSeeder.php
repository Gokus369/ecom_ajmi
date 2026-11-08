<?php

namespace App\Database\Seeds;

use CodeIgniter\CLI\CLI;
use CodeIgniter\Database\Seeder;
use CodeIgniter\Shield\Entities\User;

/**
 * Creates the first admin. Set admin.email in .env to choose the address. The
 * password is generated and shown once; only its hash is stored. Safe to re-run.
 */
class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('admin.email', 'admin@example.com');
        $users = auth()->getProvider();

        if ($users->findByCredentials(['email' => $email]) !== null) {
            CLI::write("Admin {$email} already exists, nothing to do.", 'yellow');

            return;
        }

        $password = rtrim(strtr(base64_encode(random_bytes(12)), '+/', 'xy'), '=');

        $users->save(new User([
            'username' => 'admin',
            'email'    => $email,
            'password' => $password,
        ]));

        $user = $users->findById($users->getInsertID());
        $user->activate();
        $user->addGroup('superadmin');

        CLI::write("Admin created: {$email} / {$password}", 'green');
        CLI::write('Save this password now. It is not stored anywhere and will not be shown again.', 'yellow');
    }
}
