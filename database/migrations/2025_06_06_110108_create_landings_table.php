<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLandingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('landings', function (Blueprint $table) {
            $table->id(); // Первичный ключ
            $table->unsignedBigInteger('project_id'); // ID проекта (внешний ключ)
            $table->string('name'); // Название лендинга
            $table->string('url'); // URL лендинга
            $table->text('description')->nullable(); // Полное описание
            $table->string('short_description')->nullable(); // Краткое описание
            $table->boolean('is_active')->default(false); // Флаг активности

            $table->string('goal_click_id')->nullable(); // ID цели Яндекс Метрики на переход
            $table->string('goal_subscribe_id')->nullable(); // ID цели Яндекс Метрики на подписку

            $table->timestamps(); // created_at и updated_at

            // Внешний ключ
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('landings');
    }
}
