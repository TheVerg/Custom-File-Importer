// database/migrations/xxxx_xx_xx_create_database_connections_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('database_connections', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('driver', ['mysql', 'pgsql', 'sqlite', 'sqlsrv']);
            $table->string('host')->nullable();
            $table->integer('port')->nullable();
            $table->string('database');
            $table->string('username')->nullable();
            $table->string('password')->nullable();
            $table->json('options')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('database_connections');
    }
};