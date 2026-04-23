<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Make usgs_stations.state nullable.
     *
     * State is not available from the USGS IV (instantaneous values) API — it
     * requires a separate call to the USGS Site Service. Until that is wired up,
     * the column is null for stations upserted via the detail page.
     */
    public function up(): void
    {
        Schema::table('usgs_stations', function (Blueprint $table) {
            $table->char('state', 2)->nullable()->change();
        });
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        Schema::table('usgs_stations', function (Blueprint $table) {
            $table->char('state', 2)->nullable(false)->change();
        });
    }
};
