<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('search_keys', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->morphs('keyable');
            $table->string('key')->nullable();
            $table->string('engine');
            $table->bigInteger('engine_key_id')->nullable();
            $table->text('scoped_key')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            // Add indexes for the keyable morph
            $table->index(['keyable_id', 'keyable_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('search_keys');
    }
};
