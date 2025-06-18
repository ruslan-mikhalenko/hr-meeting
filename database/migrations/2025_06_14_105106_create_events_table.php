<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Запуск миграции.
     */
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id(); // Primary key
            $table->unsignedBigInteger('project_id'); // Связь с таблицей projects
            $table->unsignedBigInteger('landing_id')->nullable(); // Поле landing_id (может быть NULL)
            $table->unsignedBigInteger('telegram_user_id')->nullable(); // Теперь поле telegram_user_id имеет возможность быть NULL
            $table->string('event'); // Поле event
            $table->timestamp('when'); // Поле времени "when"
            $table->timestamps(); // Поля created_at и updated_at

            // Внешние ключи
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade'); // Связь с таблицей projects
            $table->foreign('landing_id')->references('id')->on('landings')->nullOnDelete(); // Связь с таблицей landings, но допускает NULL
        });
    }

    /**
     * Откат миграции.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
