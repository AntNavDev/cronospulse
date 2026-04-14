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
        Schema::create('earthquakes', function (Blueprint $table) {
            $table->id();
            $table->string('usgs_id')->unique();                      // USGS ComCat event ID, e.g. "us6000abc1"
            $table->decimal('magnitude', 4, 2);
            $table->string('magnitude_type', 10)->nullable();         // ml, mw, mb, md, etc.
            $table->decimal('depth_km', 8, 3);
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->string('place');                                   // e.g. "10km NW of Ridgecrest, CA"
            $table->string('status', 20)->default('automatic');       // automatic | reviewed
            $table->string('alert', 10)->nullable();                  // green | yellow | orange | red (PAGER)
            $table->unsignedInteger('felt')->nullable();              // Did You Feel It? report count
            $table->decimal('cdi', 3, 1)->nullable();                 // Community decimal intensity
            $table->decimal('mmi', 3, 1)->nullable();                 // Instrumental intensity (ShakeMap MMI)
            $table->unsignedSmallInteger('significance')->nullable();  // 0–1000 significance score
            $table->string('url')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index('occurred_at');
            $table->index('magnitude');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('earthquakes');
    }
};
