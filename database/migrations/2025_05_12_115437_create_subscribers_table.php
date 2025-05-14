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
            $table->id(); // Первичный ключ (auto-increment ID)
            $table->bigInteger('user_id')->unique(); // Telegram User ID (уникальный идентификатор пользователя)
            $table->boolean('is_active')->default(true); // Статус активности пользователя (подписан/отписан)
            $table->timestamp('subscribed_at')->nullable(); // Время подписки (может быть NULL)
            $table->timestamps(); // created_at и updated_at (Laravel автоматически обновляет их)
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
