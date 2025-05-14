<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjectsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id(); // Первичный ключ
            $table->unsignedBigInteger('user_id'); // ID клиента (связь с таблицей clients через user_id)
            $table->string('name'); // Название проекта
            $table->string('link')->nullable(); // Ссылка на проект
            $table->string('yandex_metric_id')->nullable(); // Идентификатор Яндекс Метрики
            $table->string('goal_id')->nullable(); // Идентификатор цели
            $table->string('measurement_protocol_token')->nullable(); // Токен Measurement Protocol метрики
            $table->string('landing_url')->nullable(); // URL рекламного лендинга
            $table->timestamps(); // Поля created_at и updated_at

            // Внешний ключ на таблицу clients
            $table->foreign('user_id')->references('user_id')->on('clients')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('projects');
    }
}
