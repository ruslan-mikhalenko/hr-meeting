<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddClickLandGoalIdToProjectsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('click_land_goal_id')->nullable()->after('goal_id')->comment('Идентификатор цели клика по лендингу');
            $table->string('unsubscribe_goal_id')->nullable()->after('click_land_goal_id')->comment('Идентификатор цели отписки');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['click_land_goal_id', 'unsubscribe_goal_id']);
        });
    }
}
