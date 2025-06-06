<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->bigInteger('channel_id')->nullable()->after('landing_url')->comment('ID канала');
            $table->string('title')->nullable()->after('channel_id')->comment('Название канала');
            $table->string('username')->nullable()->after('title')->comment('Юзернейм канала');
            $table->string('photo')->nullable()->after('username')->comment('Фото канала');
            $table->integer('participants_count')->nullable()->after('photo')->comment('Количество участников');
            $table->text('about')->nullable()->after('participants_count')->comment('Описание канала');
            $table->string('type')->nullable()->after('about')->comment('Тип канала, например, channel');
            $table->string('privacy')->nullable()->after('type')->comment('Приватность канала, например, public/private');
        });
    }

    public function down()
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn([
                'channel_id',
                'title',
                'username',
                'photo',                  // Забыл это поле раньше, теперь добавлено
                'participants_count',
                'about',
                'type',
                'privacy',
            ]);
        });
    }
};
