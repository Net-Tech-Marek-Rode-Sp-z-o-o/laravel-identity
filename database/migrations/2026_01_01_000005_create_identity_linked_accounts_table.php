<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('identity_linked_accounts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->string('provider');
            $table->string('provider_id');
            $table->timestamp('created_at');

            $table->unique(['provider', 'provider_id']);
            $table->index('user_id');

            $table->foreign('user_id')->references('id')->on('identity_users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('identity_linked_accounts');
    }
};
