<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Grandfather every pre-existing account as email-verified so the new
     * verification grace period never locks out users created before the
     * feature shipped. Only accounts created after this point start unverified.
     */
    public function up(): void
    {
        DB::table('users')
            ->whereNull('email_verified_at')
            ->update(['email_verified_at' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        // Intentionally not reversible — verification state is not restorable.
    }
};
