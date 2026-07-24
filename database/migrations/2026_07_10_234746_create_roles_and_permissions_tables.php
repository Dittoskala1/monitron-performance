<?php
// database/migrations/2026_07_10_create_roles_and_permissions_tables.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ==========================================
        // 1. Tabel Roles
        // ==========================================
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // AFET Bandara
            $table->string('slug')->unique(); // afet_bandara
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // ==========================================
        // 2. Tabel Permissions
        // ==========================================
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // idle.create
            $table->string('display_name'); // Input Pengajuan Idle
            $table->string('group')->index(); // peralatan_idle
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // ==========================================
        // 3. Tabel Pivot: role_has_permissions
        // ==========================================
        Schema::create('role_has_permissions', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained('roles')->onDelete('cascade');
            $table->foreignId('permission_id')->constrained('permissions')->onDelete('cascade');
            $table->primary(['role_id', 'permission_id']);
        });

        // ==========================================
        // 4. Tabel Pivot: user_has_roles
        // ==========================================
        Schema::create('user_has_roles', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained('pengguna', 'id_pengguna')->onDelete('cascade');
            $table->foreignId('role_id')->constrained('roles')->onDelete('cascade');
            $table->primary(['user_id', 'role_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_has_roles');
        Schema::dropIfExists('role_has_permissions');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};