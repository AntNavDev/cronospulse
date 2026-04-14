<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('usgs_stations', function (Blueprint $table) {
            $table->id();
            $table->string('site_no', 20)->unique();    // USGS site number, e.g. "09380000"
            $table->string('name');
            $table->char('state', 2);                   // State abbreviation, e.g. "AZ"
            $table->string('county')->nullable();
            $table->string('huc', 16)->nullable();      // Hydrologic unit code
            $table->string('site_type', 10);            // ST (stream), LK (lake), GW (groundwater)
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->decimal('elevation_ft', 10, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usgs_stations');
    }
};
