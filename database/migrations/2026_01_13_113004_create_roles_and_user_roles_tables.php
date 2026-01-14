<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement('CREATE SCHEMA IF NOT EXISTS administracion');

        Schema::create('administracion.roles', function (Blueprint $table) {
            $table->smallIncrements('id');
            $table->string('code', 50)->unique();
            $table->string('name', 120);
            $table->boolean('is_central')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('administracion.user_roles', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id');
            $table->unsignedSmallInteger('role_id');

            $table->primary(['user_id','role_id']);

            $table->foreign('user_id')
                ->references('id')->on('administracion.users')
                ->onDelete('cascade');

            $table->foreign('role_id')
                ->references('id')->on('administracion.roles')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('administracion.user_roles');
        Schema::dropIfExists('administracion.roles');
    }
};
