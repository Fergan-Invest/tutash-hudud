<?php

namespace Database\Seeders;

use App\Models\District;
use App\Models\Mahalla;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->seedAddresses();
        $this->seedUsers();
    }

    private function seedAddresses(): void
    {
        $path = public_path('dataset/address/addresses.csv');
        if (! is_file($path)) {
            return;
        }

        $handle = fopen($path, 'r');
        fgetcsv($handle, 0, ';');

        while (($row = fgetcsv($handle, 0, ';')) !== false) {
            [$districtName, $mahallaName, $externalId] = array_pad($row, 3, null);
            $districtName = trim((string) $districtName);
            $mahallaName = trim((string) $mahallaName);
            $externalId = (int) $externalId;

            if ($districtName === '' || $mahallaName === '' || $externalId === 0) {
                continue;
            }

            $district = District::updateOrCreate(
                ['external_id' => $externalId],
                ['name' => $districtName]
            );

            Mahalla::updateOrCreate(
                ['district_id' => $district->id, 'name' => $mahallaName],
                []
            );
        }

        fclose($handle);
    }

    private function seedUsers(): void
    {
        $password = env('SEED_PASSWORD', 'Password123!');

        User::updateOrCreate(
            ['email' => env('SEED_HOKIM_EMAIL', 'viloyat.hokimi@tutash.local')],
            [
                'name' => 'Viloyat hokimi',
                'password' => Hash::make($password),
                'role' => 'viloyat_hokimi',
                'district_id' => null,
            ]
        );

        User::updateOrCreate(
            ['email' => env('SEED_INVEST_EMAIL', 'invest@tutash.local')],
            [
                'name' => 'Invest',
                'password' => Hash::make($password),
                'role' => 'invest',
                'district_id' => null,
            ]
        );

        District::orderBy('external_id')->each(function (District $district) use ($password) {
            User::updateOrCreate(
                ['email' => "district-{$district->external_id}@tutash.local"],
                [
                    'name' => $district->name.' operatori',
                    'password' => Hash::make($password),
                    'role' => 'tuman',
                    'district_id' => $district->id,
                ]
            );
        });
    }
}
