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
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->text('content');
            $table->foreignId('room_id')->nullable()->constrained();
            $table->foreignId('receiver_id')->nullable()->constrained('users');
            $table->boolean('is_read')->default(false);
            $table->timestamps();
            
            // Uma mensagem pode ser para uma sala OU para um usuário específico (mensagem direta)
            $table->index(['user_id', 'room_id']);
            $table->index(['user_id', 'receiver_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
