<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSubscribersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('subscribers', function (Blueprint $table) {
            $table->id(); // Автоинкрементный первичный ключ

            // Поле project_id и связь с таблицей projects
            $table->foreignId('project_id')->constrained('projects')->onDelete('cascade');

            // Поле user_id и связь с таблицей users (nullable)
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');

            // Поле telegram_user_id с корректным типом и индексом
            $table->unsignedBigInteger('telegram_user_id')->comment('Уникальный ID пользователя в Telegram');

            // Основные поля из массива "participants"
            $table->string('first_name')->nullable()->comment('Имя пользователя');
            $table->string('last_name')->nullable()->comment('Фамилия пользователя');
            $table->string('username')->nullable()->comment('Юзернейм пользователя');
            $table->string('phone')->nullable()->comment('Телефон пользователя'); // Строка для телефонов

            // Роль участника
            $table->enum('role', ['channelParticipantAdmin', 'channelParticipantCreator', 'channelParticipantMember'])
                ->default('channelParticipantMember')
                ->comment('Роль участника в канале/группе');

            // Флаг: является ли пользователь ботом
            $table->boolean('is_bot')->default(false)->comment('Флаг: является ли пользователь ботом');

            // Статус активности пользователя
            $table->enum('status', [
                'userStatusOnline',
                'userStatusOffline',
                'userStatusRecently',
                'userStatusLastWeek',
                'userStatusLastMonth',
                'userStatusEmpty'
            ])->nullable()->comment('Статус активности пользователя');
            $table->timestampTz('last_online_at')->nullable()->comment('Время последнего входа пользователя в Telegram');

            // Дополнительная информация
            $table->json('user_additional_info')->nullable()->comment('Дополнительная информация о пользователе (JSON)');

            // Для подписчиков
            $table->boolean('is_active')->default(true)->comment('Флаг активности подписки');
            $table->timestamp('subscribed_at')->nullable()->comment('Дата и время подписки');

            // Уникальные ключи
            $table->unique(['project_id', 'telegram_user_id'], 'unique_project_telegram_user');

            // Часто используемые индексы
            $table->index('telegram_user_id', 'idx_telegram_user_id');
            $table->index(['project_id', 'is_active'], 'idx_project_is_active');
            $table->index('status', 'idx_status');

            $table->timestamps(); // Добавляет created_at и updated_at
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('subscribers');
    }
}
