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
            'invest@tutash.local' => '@ew1411ADBiQ#@!a',
            'viloyat.hokimi@tutash.local' => '@3224ew1411ADBiQ#@!a',
            'district-1@tutash.local' => '@3456@#gfh#@!a',
        ];

        foreach ($accounts as $email => $password) {
            User::where('email', $email)->update([
                'password' => Hash::make($password),
            ]);
        }
    }
}
