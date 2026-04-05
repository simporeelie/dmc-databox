<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'dmc', 'rmc', 'visiteur'])->default('visiteur')->after('email');
            $table->foreignId('entity_id')->nullable()->constrained()->onDelete('set null')->after('role');
            $table->foreignId('filiale_id')->nullable()->constrained()->onDelete('set null')->after('entity_id');
            $table->boolean('is_active')->default(true)->after('filiale_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['entity_id']);
            $table->dropForeign(['filiale_id']);
            $table->dropColumn(['role', 'entity_id', 'filiale_id', 'is_active']);
        });
    }
};
