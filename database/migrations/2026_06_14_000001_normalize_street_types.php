<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('registry_requests')) {
            DB::table('registry_requests')
                ->whereIn('street_type', ['shohkocha', 'tor_kocha', 'berk_kocha', 'mavjud_emas'])
                ->update(['street_type' => 'kocha']);
        }

        if (Schema::hasTable('streets')) {
            DB::table('streets')
                ->whereIn('type', ['shohkocha', 'tor_kocha', 'berk_kocha', 'mavjud_emas'])
                ->update(['type' => 'kocha']);
        }
    }

    public function down(): void
    {
        // Old street type values cannot be restored safely after normalization.
    }
};
