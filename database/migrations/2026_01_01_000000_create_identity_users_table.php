<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('identity_users', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('realm_id')->nullable();
            $table->string('email');
            $table->string('name');
            $table->string('password_hash')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        DB::statement('CREATE UNIQUE INDEX identity_users_realm_email_unique ON identity_users (realm_id, email) WHERE deleted_at IS NULL AND realm_id IS NOT NULL');
        DB::statement('CREATE UNIQUE INDEX identity_users_global_email_unique ON identity_users (email) WHERE deleted_at IS NULL AND realm_id IS NULL');
    }

    public function down(): void
    {
        Schema::dropIfExists('identity_users');
    }
};
