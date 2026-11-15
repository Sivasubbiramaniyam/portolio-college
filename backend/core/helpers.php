<?php

declare(strict_types=1);

use App\Core\Config;

if (!function_exists('e')) {
    function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('app_url')) {
    function app_url(string $path = '/'): string
    {
        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }

        $base = rtrim((string) Config::get('base_path', ''), '/');
        $path = '/' . ltrim($path, '/');

        return $base . ($path === '/' ? '/' : $path);
    }
}

if (!function_exists('asset_url')) {
    function asset_url(string $path): string
    {
        $url = app_url($path);

        // Append the file's modification time to local CSS/JS URLs so a
        // browser (or proxy) never serves a stale copy after an update.
        if (preg_match('/\.(css|js)$/i', $path) === 1 && preg_match('#^https?://#i', $path) !== 1) {
            $file = dirname(__DIR__, 2) . '/' . ltrim($path, '/');
            $mtime = @filemtime($file);
            if ($mtime !== false) {
                $url .= (str_contains($url, '?') ? '&' : '?') . 'v=' . $mtime;
            }
        }

        return $url;
    }
}

if (!function_exists('redirect_to')) {
    function redirect_to(string $path, int $status = 302): never
    {
        if (!headers_sent()) {
            header('Location: ' . app_url($path), true, $status);
        }
        exit;
    }
}

if (!function_exists('is_truthy')) {
    function is_truthy(mixed $value): bool
    {
        return (bool) preg_match('/^(true|yes|on|1|checked)$/i', trim((string) $value));
    }
}

if (!function_exists('safe_asset_url')) {
    /**
     * Only allow same-origin uploaded media, local static assets, http(s) URLs
     * and image data URLs. Everything else falls back safely.
     */
    function safe_asset_url(mixed $value, string $fallback = ''): string
    {
        $url = trim((string) $value);
        if ($url === '') {
            return $fallback;
        }

        if (str_starts_with($url, '/media.php?id=') || str_starts_with($url, '/frontend/') || str_starts_with($url, '/assets/')) {
            return app_url($url);
        }

        if (preg_match('#^https?://#i', $url)) {
            return $url;
        }

        if (preg_match('#^data:image/(jpeg|png|webp|gif);base64,#i', $url)) {
            return $url;
        }

        return $fallback;
    }
}

if (!function_exists('photo_url')) {
    /** @param array<string, mixed> $profile */
    function photo_url(array $profile): string
    {
        $default = asset_url((string) Config::get('institution.defaultProfileUrl', '/frontend/assets/default-profile.svg'));

        return safe_asset_url($profile['photo'] ?? '', $default);
    }
}

if (!function_exists('banner_url')) {
    /** @param array<string, mixed> $profile */
    function banner_url(array $profile): string
    {
        return safe_asset_url($profile['banner'] ?? '', '');
    }
}

if (!function_exists('safe_external_url')) {
    function safe_external_url(mixed $value): string
    {
        $url = trim((string) $value);
        if ($url === '') {
            return '';
        }
        if (preg_match('#^https?://#i', $url)) {
            return $url;
        }
        if (preg_match('/^10\.\d{4,9}\//', $url)) {
            return 'https://doi.org/' . $url;
        }

        return '';
    }
}

if (!function_exists('attachment_url')) {
    /**
     * Links stored on collection entries may be uploaded media URLs or external
     * http(s) links. Anything else is rejected rather than rendered.
     */
    function attachment_url(mixed $value): string
    {
        $url = trim((string) $value);
        if ($url === '') {
            return '';
        }
        if (str_starts_with($url, '/media.php?id=') || str_starts_with($url, '/frontend/')) {
            return app_url($url);
        }
        if (preg_match('#^https?://#i', $url)) {
            return $url;
        }

        return '';
    }
}

if (!function_exists('format_date')) {
    function format_date(mixed $value): string
    {
        $value = trim((string) $value);
        if ($value === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return $value;
        }

        try {
            return (new DateTimeImmutable($value))->format('j M Y');
        } catch (Throwable) {
            return $value;
        }
    }
}

if (!function_exists('format_month')) {
    function format_month(mixed $value, string $fallback = 'Present'): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return $fallback;
        }
        if (preg_match('/^(\d{4})-(\d{2})$/', $value, $m)) {
            try {
                return (new DateTimeImmutable($m[1] . '-' . $m[2] . '-01'))->format('M Y');
            } catch (Throwable) {
                return $value;
            }
        }

        return format_date($value);
    }
}

if (!function_exists('date_span')) {
    function date_span(mixed $from, mixed $to, string $present = 'Present'): string
    {
        $start = trim((string) $from);
        $end = trim((string) $to);
        if ($start === '' && $end === '') {
            return '';
        }
        if ($start === '') {
            return format_month($end);
        }
        if ($end === '') {
            return format_month($start) . ' – ' . $present;
        }

        return format_month($start) . ' – ' . format_month($end);
    }
}

if (!function_exists('format_amount')) {
    function format_amount(mixed $value): string
    {
        $text = trim((string) $value);
        if ($text === '' || !is_numeric($text) || (float) $text <= 0) {
            return '';
        }

        $amount = (float) $text;
        if ($amount >= 10000000) {
            $formatted = rtrim(rtrim(number_format($amount / 10000000, 2, '.', ''), '0'), '.');
            return '₹' . $formatted . ' Cr';
        }
        if ($amount >= 100000) {
            $formatted = rtrim(rtrim(number_format($amount / 100000, 2, '.', ''), '0'), '.');
            return '₹' . $formatted . ' L';
        }

        return '₹' . number_format($amount, 0, '.', ',');
    }
}

if (!function_exists('split_tags')) {
    /** @return array<int, string> */
    function split_tags(mixed $value): array
    {
        $parts = array_map('trim', explode(',', (string) $value));

        return array_values(array_filter($parts, static fn (string $part): bool => $part !== ''));
    }
}

if (!function_exists('specialization_icon')) {
    /**
     * Map a free-text area of specialization to a Bootstrap Icons name.
     * Falls back to a generic sparkle when nothing matches.
     */
    function specialization_icon(string $label): string
    {
        $key = strtolower(trim($label));
        if ($key === '') {
            return 'stars';
        }

        $map = [
            // Computing and data
            'artificial intelligence' => 'cpu',
            'machine learning' => 'diagram-3',
            'deep learning' => 'diagram-3',
            'data science' => 'graph-up-arrow',
            'data mining' => 'database',
            'big data' => 'database',
            'natural language processing' => 'chat-square-text',
            'computer vision' => 'eye',
            'image processing' => 'image',
            'cloud computing' => 'cloud',
            'cloud' => 'cloud',
            'cyber security' => 'shield-lock',
            'cybersecurity' => 'shield-lock',
            'information security' => 'shield-lock',
            'security' => 'shield-lock',
            'blockchain' => 'boxes',
            'internet of things' => 'router',
            'iot' => 'router',
            'networking' => 'diagram-3',
            'software engineering' => 'code-slash',
            'algorithms' => 'bezier2',
            'human-computer interaction' => 'hand-index-thumb',
            'human computer interaction' => 'hand-index-thumb',
            'learning analytics' => 'graph-up',
            'analytics' => 'graph-up',
            // Electronics and electrical
            'vlsi design' => 'cpu',
            'vlsi' => 'cpu',
            'embedded systems' => 'motherboard',
            'embedded' => 'motherboard',
            'signal processing' => 'soundwave',
            'power electronics' => 'lightning-charge',
            'power systems' => 'lightning-charge-fill',
            'smart grid' => 'diagram-3',
            'renewable energy' => 'lightning-charge',
            'energy' => 'lightning-charge',
            'control systems' => 'sliders',
            'instrumentation' => 'speedometer2',
            'communication' => 'broadcast',
            'robotics' => 'robot',
            // Mechanical and civil
            'computational fluid dynamics' => 'wind',
            'fluid dynamics' => 'wind',
            'thermal engineering' => 'thermometer-half',
            'thermal' => 'thermometer-half',
            'heat transfer' => 'fire',
            'manufacturing' => 'gear',
            'structural engineering' => 'building',
            'structural' => 'building',
            'earthquake resistant design' => 'activity',
            'earthquake' => 'activity',
            'seismic' => 'activity',
            'concrete technology' => 'bricks',
            'concrete' => 'bricks',
            'surveying' => 'geo-alt',
            'transportation' => 'truck',
            'environmental' => 'globe2',
            // Sciences and mathematics
            'applied mathematics' => 'calculator',
            'numerical analysis' => 'calculator',
            'numerical' => 'calculator',
            'mathematics' => 'calculator',
            'statistics' => 'bar-chart',
            'operations research' => 'sliders',
            'optimization' => 'graph-up',
            'physics' => 'magnet',
            'chemistry' => 'droplet',
            'biotechnology' => 'activity',
            'microbiology' => 'virus',
            // Management and humanities
            'organisational behaviour' => 'people',
            'organizational behaviour' => 'people',
            'human resource management' => 'person-badge',
            'human resource' => 'person-badge',
            'entrepreneurship' => 'lightbulb',
            'marketing' => 'megaphone',
            'finance' => 'cash-coin',
            'accounting' => 'calculator',
            'economics' => 'graph-up-arrow',
            'business' => 'briefcase',
            'management' => 'briefcase',
            'education' => 'mortarboard',
            'english' => 'book',
            'psychology' => 'person-badge',
            // Generic
            'design' => 'vector-pen',
            'research' => 'search',
        ];

        // Longest phrases first so specific matches win over short ones.
        $keys = array_keys($map);
        usort($keys, static fn (string $a, string $b): int => strlen($b) <=> strlen($a));
        foreach ($keys as $needle) {
            if (str_contains($key, $needle)) {
                return $map[$needle];
            }
        }

        return 'stars';
    }
}

if (!function_exists('status_class')) {
    function status_class(mixed $value): string
    {
        $slug = preg_replace('/[^a-z0-9]+/', '-', strtolower(trim((string) $value)));

        return trim((string) $slug, '-');
    }
}

if (!function_exists('initials_of')) {
    function initials_of(string $name): string
    {
        $name = preg_replace('/^(Dr|Prof|Mr|Ms|Mrs)\.?\s+/i', '', trim($name)) ?? '';
        $parts = array_values(array_filter(preg_split('/\s+/', $name) ?: []));
        if ($parts === []) {
            return 'FM';
        }
        if (count($parts) === 1) {
            return strtoupper(substr($parts[0], 0, 2));
        }

        return strtoupper(substr($parts[0], 0, 1) . substr($parts[count($parts) - 1], 0, 1));
    }
}

if (!function_exists('avatar_gradient')) {
    /** @return array{0: string, 1: string} */
    function avatar_gradient(string $name): array
    {
        $palettes = [
            ['#7B5EF0', '#B69CFF'],
            ['#2F6FE4', '#7DB4FF'],
            ['#219E80', '#7AD9C2'],
            ['#B8790D', '#F1C56D'],
            ['#D94B63', '#F49AAC'],
            ['#5A3BD4', '#9E86F5'],
        ];
        $hash = 0;
        foreach (str_split($name !== '' ? $name : 'Faculty') as $char) {
            $hash = ($hash * 31 + ord($char)) % 2147483647;
        }

        return $palettes[$hash % count($palettes)];
    }
}

if (!function_exists('format_value')) {
    function format_value(mixed $value, string $fallback = '—'): string
    {
        $text = trim((string) $value);

        return $text !== '' ? $text : $fallback;
    }
}

if (!function_exists('allowed_option')) {
    function allowed_option(string $value, array $options): bool
    {
        return in_array($value, $options, true);
    }
}
