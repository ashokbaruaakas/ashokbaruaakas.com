<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    /**
     * Run the migrations.
     *
     * Every account can use the administration once this column is gone, so an account that
     * isn't an administrator has to be removed first rather than being promoted by this migration.
     */
    public function up(): void
    {
        $otherAccounts = DB::table('users')->where('is_admin', false)->count();

        throw_if($otherAccounts > 0, RuntimeException::class, sprintf(
            'Remove the %d non-administrator account(s) first: without is_admin they would get full access to the administration.',
            $otherAccounts,
        ));

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('is_admin');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('is_admin')
                ->after('password')
                ->default(false);
        });

        DB::table('users')->update(['is_admin' => true]);
    }
};
