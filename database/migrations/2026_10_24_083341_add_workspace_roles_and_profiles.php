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
        DB::statement('ALTER TABLE users DROP CONSTRAINT users_role_check');
        DB::table('users')->where('role', 'capitol_admin')->update(['role' => 'admin']);
        DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role IN ('super_admin', 'admin', 'staff'))");
        Schema::table('users', function (Blueprint $table): void {
            $table->uuid('public_id')->nullable()->unique();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('auth_version')->default(1);
            $table->unsignedSmallInteger('birthday_month')->nullable();
            $table->unsignedSmallInteger('birthday_day')->nullable();
        });
        DB::table('users')->select('id')->orderBy('id')->each(function (object $user): void {
            DB::table('users')->where('id', $user->id)->update(['public_id' => (string) Str::uuid()]);
        });
        DB::statement('ALTER TABLE users ALTER COLUMN public_id SET NOT NULL');
        DB::statement('ALTER TABLE users ADD CONSTRAINT users_birthday_check CHECK ((birthday_month IS NULL AND birthday_day IS NULL) OR (birthday_month IS NOT NULL AND birthday_day IS NOT NULL AND birthday_month BETWEEN 1 AND 12 AND birthday_day BETWEEN 1 AND CASE WHEN birthday_month IN (4,6,9,11) THEN 30 WHEN birthday_month = 2 THEN 29 ELSE 31 END))');
        Schema::create('municipality_user', function (Blueprint $table): void {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('municipality_id')->constrained()->restrictOnDelete();
            $table->primary(['user_id', 'municipality_id']);
            $table->index('municipality_id');
        });
        Schema::create('activity_logs', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('actor_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('event', 40)->index();
            $table->timestampTz('occurred_at')->index();
        });
        DB::unprepared("CREATE OR REPLACE FUNCTION prevent_activity_log_mutation() RETURNS trigger LANGUAGE plpgsql AS $$ BEGIN RAISE EXCEPTION 'Activity records are append only'; END; $$;
            CREATE TRIGGER activity_logs_append_only BEFORE UPDATE OR DELETE ON activity_logs FOR EACH ROW EXECUTE FUNCTION prevent_activity_log_mutation();");
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
        DB::statement('DROP FUNCTION IF EXISTS prevent_activity_log_mutation()');
        Schema::dropIfExists('municipality_user');
        DB::statement('ALTER TABLE users DROP CONSTRAINT users_birthday_check');
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['public_id', 'is_active', 'auth_version', 'birthday_month', 'birthday_day']);
        });
        DB::statement('ALTER TABLE users DROP CONSTRAINT users_role_check');
        DB::table('users')->whereIn('role', ['super_admin', 'admin'])->update(['role' => 'capitol_admin']);
        DB::table('users')->where('role', 'staff')->update(['role' => null]);
        DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role IN ('capitol_admin'))");
    }
};
