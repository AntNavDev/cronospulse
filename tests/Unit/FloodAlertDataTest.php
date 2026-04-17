<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Data\FloodAlertData;
use PHPUnit\Framework\TestCase;

class FloodAlertDataTest extends TestCase
{
    /**
     * FLOOD_EVENT_TYPES contains the core flood-related NWS product names.
     */
    public function test_flood_event_types_contains_core_products(): void
    {
        $types = FloodAlertData::FLOOD_EVENT_TYPES;

        $this->assertContains('Flash Flood Warning', $types);
        $this->assertContains('Flood Watch', $types);
        $this->assertContains('Coastal Flood Warning', $types);
    }

    /**
     * severityClass() returns the correct Tailwind class for each CAP severity.
     */
    public function test_severity_class_maps_correctly(): void
    {
        $this->assertSame('text-danger', $this->makeAlert('Extreme')->severityClass());
        $this->assertSame('text-danger', $this->makeAlert('Severe')->severityClass());
        $this->assertSame('text-warning', $this->makeAlert('Moderate')->severityClass());
        $this->assertSame('text-info', $this->makeAlert('Minor')->severityClass());
        $this->assertSame('text-muted', $this->makeAlert('Unknown')->severityClass());
    }

    /**
     * severityBadgeStyle() returns an array with bg, text, and border keys.
     */
    public function test_severity_badge_style_has_required_keys(): void
    {
        $badge = $this->makeAlert('Severe')->severityBadgeStyle();

        $this->assertArrayHasKey('bg', $badge);
        $this->assertArrayHasKey('text', $badge);
        $this->assertArrayHasKey('border', $badge);
    }

    /**
     * hasGeometry() returns true only when geometry is not null.
     */
    public function test_has_geometry_returns_correct_value(): void
    {
        $withGeometry    = $this->makeAlert('Severe', geometry: ['type' => 'Polygon', 'coordinates' => []]);
        $withoutGeometry = $this->makeAlert('Severe', geometry: null);

        $this->assertTrue($withGeometry->hasGeometry());
        $this->assertFalse($withoutGeometry->hasGeometry());
    }

    /**
     * formattedEffective() returns a human-readable string in the given timezone.
     */
    public function test_formatted_effective_uses_timezone(): void
    {
        $alert = $this->makeAlert('Moderate', effective: '2025-04-14T10:00:00+00:00');

        $formatted = $alert->formattedEffective('America/New_York');

        // 10:00 UTC = 06:00 EDT (UTC-4)
        $this->assertStringContainsString('6:00am', $formatted);
    }

    /**
     * formattedEffective() returns null when effective is null.
     */
    public function test_formatted_effective_returns_null_for_null(): void
    {
        $alert = $this->makeAlert('Minor', effective: null);

        $this->assertNull($alert->formattedEffective('UTC'));
    }

    /**
     * isExpired() returns true when the expires datetime is in the past.
     */
    public function test_is_expired_returns_true_for_past_datetime(): void
    {
        $alert = $this->makeAlert('Minor', expires: '2000-01-01T00:00:00+00:00');

        $this->assertTrue($alert->isExpired());
    }

    /**
     * isExpired() returns false when the expires datetime is in the future.
     */
    public function test_is_expired_returns_false_for_future_datetime(): void
    {
        $alert = $this->makeAlert('Minor', expires: '2099-01-01T00:00:00+00:00');

        $this->assertFalse($alert->isExpired());
    }

    /**
     * toArray() includes all expected keys.
     */
    public function test_to_array_includes_expected_keys(): void
    {
        $array = $this->makeAlert('Severe')->toArray();

        foreach (['id', 'event', 'severity', 'urgency', 'certainty', 'headline',
                  'area_desc', 'description', 'instruction', 'effective', 'expires',
                  'has_geometry', 'geometry', 'severity_class', 'severity_badge',
                  'formatted_effective', 'formatted_expires', 'is_expired'] as $key) {
            $this->assertArrayHasKey($key, $array, "Missing key: {$key}");
        }
    }

    /**
     * Build a minimal FloodAlertData for testing.
     */
    private function makeAlert(
        string $severity,
        ?array $geometry = null,
        ?string $effective = null,
        ?string $expires = null,
    ): FloodAlertData {
        return new FloodAlertData(
            id: 'urn:oid:2.49.0.1.840.0.TEST',
            event: 'Flash Flood Warning',
            severity: $severity,
            urgency: 'Immediate',
            certainty: 'Observed',
            headline: 'Flash Flood Warning issued for test county.',
            areaDesc: 'Test County, VA',
            description: 'Heavy rainfall causing rapid rises on area streams.',
            instruction: 'Move to higher ground immediately.',
            effective: $effective,
            expires: $expires,
            geometry: $geometry,
        );
    }
}
