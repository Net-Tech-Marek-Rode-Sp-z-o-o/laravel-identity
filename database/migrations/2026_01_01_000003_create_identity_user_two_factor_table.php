<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('identity_user_two_factor', function (Blueprint $table): void {
            $table->uuid('user_id')->primary();
            $table->text('secret');
            $table->jsonb('recovery_codes');
            $table->timestamp('confirmed_at')->nullable();

            $table->foreign('user_id')->references('id')->on('identity_users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('identity_user_two_factor');
    }
};
