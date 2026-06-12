<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'role')) {
                $table->string('role', 40)->default('tuman')->after('password')->index();
            }
            if (! Schema::hasColumn('users', 'district_id')) {
                $table->foreignId('district_id')->nullable()->after('role')->index();
            }
            if (! Schema::hasColumn('users', 'last_seen_at')) {
                $table->timestamp('last_seen_at')->nullable()->after('remember_token')->index();
            }
            if (! Schema::hasColumn('users', 'last_ip')) {
                $table->string('last_ip', 45)->nullable()->after('last_seen_at');
            }
            if (! Schema::hasColumn('users', 'last_user_agent')) {
                $table->text('last_user_agent')->nullable()->after('last_ip');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach (['last_user_agent', 'last_ip', 'last_seen_at'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
