<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Add state_cd to saved_stations.
     *
     * Stores the two-letter lowercase state code selected in the StreamGauge
     * component at the time the station was saved. Used to build the HydroWatch
     * deep-link (?state=va&site=01646500) from the Dashboard without needing a
     * separate lookup — usgs_stations.state is nullable for IV-API-sourced records.
     */
    public function up(): void
    {
        Schema::table('saved_stations', function (Blueprint $table) {
            $table->char('state_cd', 2)->nullable()->after('station_id');
        });
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        Schema::table('saved_stations', function (Blueprint $table) {
            $table->dropColumn('state_cd');
        });
    }
};
