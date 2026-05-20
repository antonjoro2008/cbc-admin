<?php

namespace App\Support;

/**
 * Gravity CBC brand palette for Filament admin UI and analytics charts.
 */
final class GravityCbcColors
{
    public const GREEN = '#90C142';

    public const BLUE = '#3D90C7';

    public const RED = '#EC2735';

    /** Lighter blue for chart variety (AE competency level). */
    public const BLUE_LIGHT = '#6BA8D4';

    private const GREEN_RGB = '144, 193, 66';

    private const BLUE_RGB = '61, 144, 199';

    private const RED_RGB = '236, 39, 53';

    /**
     * @return array{primary: string, secondary: string, success: string, info: string, warning: string, danger: string}
     */
    public static function filamentPanelColors(): array
    {
        return [
            'primary' => self::GREEN,
            'secondary' => self::BLUE,
            'success' => self::GREEN,
            'info' => self::BLUE,
            'warning' => self::BLUE_LIGHT,
            'danger' => self::RED,
        ];
    }

    /**
     * CBE competency band colors: BE, AE, ME, EE.
     *
     * @return list<string>
     */
    public static function competencyBands(): array
    {
        return [self::RED, self::BLUE_LIGHT, self::BLUE, self::GREEN];
    }

    /**
     * Primary chart series colors (green, blue, red cycling).
     *
     * @return list<string>
     */
    public static function chartSeries(): array
    {
        return [self::GREEN, self::BLUE, self::RED, self::BLUE_LIGHT];
    }

    public static function rgbaGreen(float $alpha = 0.75): string
    {
        return 'rgba('.self::GREEN_RGB.", {$alpha})";
    }

    public static function rgbaBlue(float $alpha = 0.75): string
    {
        return 'rgba('.self::BLUE_RGB.", {$alpha})";
    }

    public static function rgbaRed(float $alpha = 0.75): string
    {
        return 'rgba('.self::RED_RGB.", {$alpha})";
    }
}
