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
            'district-2@tutash.local' => 'Tutash-Qoqon#02!26',
            'district-3@tutash.local' => 'Tutash-Quvasoy#03!26',
            'district-4@tutash.local' => 'Tutash-Margilon#04!26',
            'district-5@tutash.local' => 'Tutash-Oltiariq#05!26',
            'district-6@tutash.local' => 'Tutash-Bogdod#06!26',
            'district-7@tutash.local' => 'Tutash-Buvayda#07!26',
            'district-8@tutash.local' => 'Tutash-Beshariq#08!26',
            'district-9@tutash.local' => 'Tutash-Quva#09!26',
            'district-10@tutash.local' => 'Tutash-Uchkoprik#10!26',
            'district-11@tutash.local' => 'Tutash-Rishton#11!26',
            'district-12@tutash.local' => 'Tutash-Sox#12!26',
            'district-13@tutash.local' => 'Tutash-Toshloq#13!26',
            'district-14@tutash.local' => 'Tutash-Uzbekiston#14!26',
            'district-15@tutash.local' => 'Tutash-Fargona#15!26',
            'district-16@tutash.local' => 'Tutash-Dangara#16!26',
            'district-17@tutash.local' => 'Tutash-Furqat#17!26',
            'district-18@tutash.local' => 'Tutash-Yozyovon#18!26',
            'district-19@tutash.local' => 'Tutash-Qoshtepa#19!26',
        ];

        foreach ($accounts as $email => $password) {
            User::where('email', $email)->update([
                'password' => Hash::make($password),
            ]);
        }
    }
}
