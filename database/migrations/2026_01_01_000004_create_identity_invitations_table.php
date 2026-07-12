<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('identity_invitations', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('realm_id')->nullable();
            $table->string('email');
            $table->string('token_hash')->unique();
            $table->jsonb('metadata');
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->index(['realm_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('identity_invitations');
    }
};
