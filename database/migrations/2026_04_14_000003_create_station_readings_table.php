<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     *
     * Unified table for USGS time-series readings. USGS identifies data by parameter
     * code — e.g. 00060 (discharge/streamflow), 00065 (gage height/water level),
     * 00010 (water temperature). One parameterised table keeps the schema flexible
     * as new datasets are added without new migrations.
     */
    public function up(): void
    {
        Schema::create('station_readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('station_id')->constrained('usgs_stations')->cascadeOnDelete();
            $table->string('parameter_code', 10);     // USGS parameter code, e.g. "00060"
            $table->string('parameter_name');          // Human label, e.g. "Discharge"
            $table->decimal('value', 12, 4);
            $table->string('unit', 50);               // e.g. "ft3/s", "ft", "degC"
            $table->string('qualifier', 10)->nullable(); // P (provisional), A (approved), e (estimated)
            $table->timestamp('recorded_at');

            $table->timestamps();

            $table->unique(['station_id', 'parameter_code', 'recorded_at']); // prevent duplicate ingestion
            $table->index(['station_id', 'parameter_code', 'recorded_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('station_readings');
    }
};
