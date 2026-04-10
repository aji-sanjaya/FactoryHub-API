<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('dpk_web_portal_role_menus', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('menu_id');
            $table->timestamps();

            // $table->foreign('role_id')->references('id')->on('dpk_web_portal_roles')->onDelete('cascade');
            // $table->foreign('menu_id')->references('id')->on('dpk_web_portal_menus')->onDelete('cascade');

            $table->unique(['role_id', 'menu_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dpk_web_portal_role_menus');
    }
};
