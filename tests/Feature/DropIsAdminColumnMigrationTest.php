<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    Schema::table('users', function (Blueprint $table): void {
        $table->boolean('is_admin')->default(false);
    });

    $this->migration = require File::glob(database_path('migrations/*_drop_is_admin_column_from_users_table.php'))[0];
});

test('it stops rather than give other accounts access to the administration', function () {
    User::factory()->create();

    expect(fn () => $this->migration->up())->toThrow(RuntimeException::class, 'Remove the 1 non-administrator account(s) first');
    expect(Schema::hasColumn('users', 'is_admin'))->toBeTrue();
});

test('it drops the column when only administrators exist', function () {
    DB::table('users')->where('id', User::factory()->create()->id)->update(['is_admin' => true]);

    $this->migration->up();

    expect(Schema::hasColumn('users', 'is_admin'))->toBeFalse()
        ->and(User::query()->count())->toBe(1);
});
