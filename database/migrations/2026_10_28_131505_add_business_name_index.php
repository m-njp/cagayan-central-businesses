<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// The Businesses directory orders and searches by lower-cased name (D39).
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('create index businesses_name_lower_index on businesses (lower(business_name), id)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('drop index if exists businesses_name_lower_index');
    }
};
