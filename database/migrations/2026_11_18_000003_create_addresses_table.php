<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('label', 100);
            $table->string('street');
            $table->string('city', 100);
            $table->string('postal_code', 10);
            $table->string('province', 100)->nullable();
            $table->string('country', 2)->default('ES');
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            $table->index('client_id');
        });

        DB::statement('CREATE UNIQUE INDEX addresses_one_default_per_client ON addresses (client_id) WHERE is_default');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};
