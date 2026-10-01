<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('identity_invitations', function (Blueprint $table): void {
            $table->uuid('invited_by')->nullable()->after('email');
            $table->index('invited_by');
        });
    }

    public function down(): void
    {
        Schema::table('identity_invitations', function (Blueprint $table): void {
            $table->dropIndex(['invited_by']);
            $table->dropColumn('invited_by');
        });
    }
};
