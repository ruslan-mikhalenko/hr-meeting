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
        Schema::create('clients', function (Blueprint $table) {
            $table->id(); // Автоинкрементируемый первичный ключ
            $table->unsignedBigInteger('user_id'); // Поле для связи с пользователем
            $table->string('name'); // Название агентства

            $table->string('original_email')->nullable(); // Исходный email
            $table->string('original_password')->nullable(); // Исходный пароль, возможно, стоит зашифровать
            $table->timestamps(); // Время создания и обновления записей

            // Добавление внешнего ключа для id_user
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Важно сначала удалить внешний ключ перед удалением таблицы
        Schema::table('clients', function (Blueprint $table) {
            $table->dropForeign(['user_id']); // Удаляем внешний ключ
        });

        Schema::dropIfExists('clients'); // Удаляем таблицу
    }
};
