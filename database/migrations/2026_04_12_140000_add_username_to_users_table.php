<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 64)->nullable()->unique();
        });

        $users = DB::table('users')->whereNull('username')->get();
        foreach ($users as $u) {
            $local = Str::before((string) $u->email, '@');
            $base = Str::slug($local) !== '' ? Str::slug($local) : 'user';
            $username = Str::lower($base);
            $n = 0;
            while (DB::table('users')->where('username', $username)->where('id', '!=', $u->id)->exists()) {
                $username = Str::lower($base).(++$n);
            }
            DB::table('users')->where('id', $u->id)->update(['username' => $username]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropColumn('username');
        });
    }
};
