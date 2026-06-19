<?php

namespace App\Support;

use App\Models\Company;

/**
 * Builds a per-company CSS-variable override block (visual identity / branding).
 * Injected into the <head>; overrides the design-system variables without
 * touching the base stylesheet or any system logic. Multi-tenant SaaS ready.
 */
class Theme
{
    public const DEFAULT_PRIMARY = '#4f46e5';
    public const DEFAULT_BG = '#f6f7fb';
    public const DEFAULT_SIDEBAR = '#0f172a';

    /** Ready-made palettes for the settings page. */
    public const PRESETS = [
        ['name' => 'البنفسجي (افتراضي)', 'primary' => '#4f46e5', 'sidebar' => '#0f172a', 'bg' => '#f6f7fb'],
        ['name' => 'الأزرق المؤسسي', 'primary' => '#2563eb', 'sidebar' => '#0b2447', 'bg' => '#f4f7fb'],
        ['name' => 'الأخضر الزمردي', 'primary' => '#0d9488', 'sidebar' => '#0f2e2a', 'bg' => '#f3faf8'],
        ['name' => 'الكهرماني الدافئ', 'primary' => '#d97706', 'sidebar' => '#1c1917', 'bg' => '#faf7f2'],
        ['name' => 'الأحمر الجريء', 'primary' => '#dc2626', 'sidebar' => '#1a1212', 'bg' => '#fbf5f5'],
        ['name' => 'النيلي الداكن', 'primary' => '#4338ca', 'sidebar' => '#111827', 'bg' => '#f5f6fb'],
    ];

    /** Inline CSS for the company's theme (empty-safe — falls back to defaults). */
    public static function cssFor(?Company $company): string
    {
        $primary = self::clean($company?->primary_color) ?? self::DEFAULT_PRIMARY;
        $bg = self::clean($company?->bg_color) ?? self::DEFAULT_BG;
        $sidebar = self::clean($company?->sidebar_color);

        $vars = [
            '--primary' => $primary,
            '--primary-600' => self::darken($primary, 8),
            '--primary-700' => self::darken($primary, 16),
            '--primary-soft' => self::tint($primary, 90),
            '--bg' => $bg,
        ];
        if ($sidebar) {
            // Subtle vertical gradient derived from the chosen colour.
            $vars['--sidebar-bg'] = 'linear-gradient(180deg, ' . self::lighten($sidebar, 6) . ' 0%, ' . $sidebar . ' 100%)';
        }

        $css = ':root{';
        foreach ($vars as $k => $v) {
            $css .= "{$k}:{$v};";
        }

        return $css . '}';
    }

    /** Validate a #RRGGBB hex; null if invalid/empty. */
    public static function clean(?string $hex): ?string
    {
        $hex = trim((string) $hex);

        return preg_match('/^#[0-9a-fA-F]{6}$/', $hex) ? strtolower($hex) : null;
    }

    public static function darken(string $hex, int $pct): string
    {
        return self::adjust($hex, -$pct);
    }

    public static function lighten(string $hex, int $pct): string
    {
        return self::adjust($hex, $pct);
    }

    /** Blend the colour toward white by $pct% (a soft tint). */
    public static function tint(string $hex, int $pct): string
    {
        [$r, $g, $b] = self::rgb($hex);
        $f = $pct / 100;
        $r = (int) round($r + (255 - $r) * $f);
        $g = (int) round($g + (255 - $g) * $f);
        $b = (int) round($b + (255 - $b) * $f);

        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }

    protected static function adjust(string $hex, int $pct): string
    {
        [$r, $g, $b] = self::rgb($hex);
        $f = 1 + ($pct / 100);
        $clamp = fn ($v) => max(0, min(255, (int) round($v * $f)));

        return sprintf('#%02x%02x%02x', $clamp($r), $clamp($g), $clamp($b));
    }

    protected static function rgb(string $hex): array
    {
        $hex = ltrim($hex, '#');

        return [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
    }
}
