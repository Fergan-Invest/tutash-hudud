<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserPasswordSeeder extends Seeder
{
    /**
     * Update the passwords for the application's login accounts.
     */
    public function run(): void
    {
        $accounts = [

        ];

        foreach ($accounts as $email => $password) {
            User::where('email', $email)->update([
                'password' => Hash::make($password),
            ]);
        }
    }
}
