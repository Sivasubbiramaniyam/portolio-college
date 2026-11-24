<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Canonical profile shape, aliases and value normalisation.
 *
 * Keeping this in one place means the JSON provider, the future MySQL provider
 * and the API all speak exactly the same language.
 */
final class ProfileShape
{
    /** @var array<string, array<int, string>> */
    public const COLLECTIONS = [
        'qualifications' => ['degree', 'institution', 'university', 'year', 'classObtained', 'specialization'],
        'experience' => ['institution', 'designation', 'from', 'to', 'total'],
        'publications' => ['title', 'type', 'venue', 'authors', 'year', 'volume', 'issue', 'pages', 'publisher', 'indexing', 'doi', 'url'],
        'projects' => ['title', 'agency', 'category', 'pi', 'coPi', 'role', 'duration', 'start', 'end', 'amount', 'status', 'reference', 'description', 'sanctionLetter'],
        'conferencesAttended' => ['name', 'type', 'category', 'organizer', 'location', 'start', 'end', 'participation', 'paperTitle', 'certificate'],
        'conferencesConducted' => ['name', 'type', 'category', 'organizer', 'role', 'location', 'start', 'end', 'participants', 'website'],
        'trainingPrograms' => ['title', 'type', 'organizer', 'level', 'start', 'end', 'role', 'participants', 'certificate'],
        'patents' => ['title', 'number', 'type', 'inventors', 'authority', 'country', 'grantDate', 'status', 'url', 'certificate'],
        'researchAreas' => ['area', 'subject', 'specialization', 'keywords'],
        'achievements' => ['title', 'category', 'organization', 'year', 'certificateNumber', 'certificateUrl', 'description'],
        'certifications' => ['title', 'category', 'organization', 'year', 'certificateNumber', 'certificateUrl', 'description'],
        'responsibilities' => ['title', 'category', 'institution', 'position', 'level', 'from', 'to', 'current'],
    ];

    /** @var array<string, string> */
    public const COLLECTION_ALIASES = [
        'qualification' => 'qualifications',
        'publication' => 'publications',
        'project' => 'projects',
        'conferenceAttended' => 'conferencesAttended',
        'conferenceConducted' => 'conferencesConducted',
        'trainingProgram' => 'trainingPrograms',
        'patent' => 'patents',
        'researchArea' => 'researchAreas',
        'achievement' => 'achievements',
        'certification' => 'certifications',
        'responsibility' => 'responsibilities',
    ];

    /** @var array<string, mixed> */
    private const ROOT_DEFAULTS = [
        'id' => '',
        'name' => '',
        'facultyId' => '',
        'department' => '',
        'designation' => '',
        'email' => '',
        'contact' => '',
        'specialization' => '',
        'photo' => '',
        'banner' => '',
        'researchGuideNumber' => '',
        'researchGuideUniversity' => '',
        'researchGuideDate' => '',
        'researchGuideStatus' => '',
        'phdCompleted' => 0,
        'otherCompleted' => 0,
        'phdGuiding' => 0,
        'otherGuiding' => 0,
        'googleScholarId' => '',
        'googleScholarUrl' => '',
        'orcidId' => '',
        'orcidUrl' => '',
        'scopusId' => '',
        'scopusUrl' => '',
        'isDean' => false,
        'displayOrder' => 0,
        'createdAt' => '',
        'updatedAt' => '',
    ];

    /** @return array<string, mixed> */
    public static function emptyProfile(): array
    {
        $profile = self::ROOT_DEFAULTS;
        foreach (array_keys(self::COLLECTIONS) as $collection) {
            $profile[$collection] = [];
        }

        return $profile;
    }

    /** @return array<int, string> */
    public static function rootFields(): array
    {
        return array_keys(self::ROOT_DEFAULTS);
    }

    /**
     * Normalise a profile to the canonical API shape. Missing collections become
     * empty arrays, aliases are folded to canonical keys and every scalar is
     * trimmed.
     *
     * @param array<string, mixed> $raw
     * @return array<string, mixed>
     */
    public static function normalize(array $raw): array
    {
        $profile = self::ROOT_DEFAULTS;

        foreach (self::ROOT_DEFAULTS as $key => $default) {
            $value = array_key_exists($key, $raw) ? $raw[$key] : $default;
            if (is_int($default)) {
                $profile[$key] = self::toInt($value);
            } elseif (is_bool($default)) {
                $profile[$key] = self::toBool($value);
            } else {
                $profile[$key] = self::toText($value);
            }
        }

        $profile['email'] = strtolower($profile['email']);
        $profile['researchGuideStatus'] = self::normalizeGuideStatus($profile['researchGuideStatus']);

        foreach (self::COLLECTION_ALIASES as $alias => $canonical) {
            if (!array_key_exists($canonical, $raw) && array_key_exists($alias, $raw)) {
                $raw[$canonical] = $raw[$alias];
            }
        }

        foreach (self::COLLECTIONS as $collection => $fields) {
            $source = $raw[$collection] ?? [];
            $profile[$collection] = self::normalizeCollection($collection, is_array($source) ? $source : [], $profile['id']);
        }

        return $profile;
    }

    /**
     * @param array<int, mixed> $entries
     * @return array<int, array<string, mixed>>
     */
    public static function normalizeCollection(string $collection, array $entries, string $profileId): array
    {
        if (!isset(self::COLLECTIONS[$collection])) {
            return [];
        }

        $fields = self::COLLECTIONS[$collection];
        $normalised = [];
        $index = 0;

        foreach (array_values($entries) as $rawEntry) {
            if (!is_array($rawEntry)) {
                continue;
            }

            $entry = [];
            foreach ($fields as $field) {
                $entry[$field] = self::normalizeEntryValue($collection, $field, $rawEntry[$field] ?? '');
            }

            $entry = self::applyEntryRules($collection, $entry, $rawEntry);
            if (!self::entryHasContent($collection, $entry)) {
                continue;
            }

            $id = self::toText($rawEntry['id'] ?? '');
            if ($id === '') {
                $id = self::generatedChildId($profileId, $collection, $index);
            }
            $sortOrder = array_key_exists('sortOrder', $rawEntry) ? self::toInt($rawEntry['sortOrder']) : $index;

            $normalised[] = array_merge(
                ['id' => $id, 'sortOrder' => $sortOrder],
                $entry
            );
            $index++;
        }

        // Keep the array order authoritative and make sortOrder contiguous.
        foreach ($normalised as $position => &$item) {
            $item['sortOrder'] = $position;
        }
        unset($item);

        return $normalised;
    }

    private static function normalizeEntryValue(string $collection, string $field, mixed $value): mixed
    {
        if ($collection === 'responsibilities' && $field === 'current') {
            return self::toBool($value);
        }

        if (in_array($field, ['year', 'participants'], true)) {
            return self::toInt($value);
        }

        if ($field === 'amount') {
            $text = self::toText($value);
            if ($text === '') {
                return '';
            }
            return preg_match('/^\d+(?:\.\d+)?$/', $text) ? $text : $text;
        }

        return self::toText($value);
    }

    /**
     * @param array<string, mixed> $entry
     * @param array<string, mixed> $rawEntry
     * @return array<string, mixed>
     */
    private static function applyEntryRules(string $collection, array $entry, array $rawEntry): array
    {
        if ($collection === 'publications') {
            if ($entry['type'] === '') {
                $haystack = $entry['venue'] . ' ' . $entry['title'];
                $entry['type'] = preg_match('/conference|proceedings|workshop|symposium/i', $haystack)
                    ? 'Conference Paper'
                    : 'Journal Article';
            }
            if ($entry['type'] === 'Patent') {
                $entry['type'] = 'Other';
            }

            $legacyLink = self::toText($rawEntry['link'] ?? '');
            if ($legacyLink !== '' && $entry['doi'] === '' && $entry['url'] === '') {
                if (preg_match('/^10\.\d{4,9}\//', $legacyLink)) {
                    $entry['doi'] = $legacyLink;
                } else {
                    $entry['url'] = $legacyLink;
                }
            }

            if ($entry['doi'] !== '' && !preg_match('#^https?://#i', $entry['doi'])) {
                if (preg_match('/^10\.\d{4,9}\//', $entry['doi'])) {
                    $entry['doi'] = 'https://doi.org/' . $entry['doi'];
                }
            }
        }

        if ($collection === 'projects') {
            if (preg_match('/^(approved|proposed)$/i', (string) $entry['status'])) {
                $entry['status'] = 'Ongoing';
            }
        }

        return $entry;
    }

    /** @param array<string, mixed> $entry */
    public static function entryHasContent(string $collection, array $entry): bool
    {
        $fields = self::COLLECTIONS[$collection] ?? [];
        foreach ($fields as $field) {
            $value = $entry[$field] ?? '';
            if (is_bool($value)) {
                if ($value) {
                    return true;
                }
            } elseif (is_int($value)) {
                if ($value > 0) {
                    return true;
                }
            } elseif (self::toText($value) !== '') {
                return true;
            }
        }

        return false;
    }

    public static function toText(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : '';
        }
        if ($value === null) {
            return '';
        }
        if (is_array($value) || is_object($value)) {
            return '';
        }

        return trim((string) $value);
    }

    public static function toInt(mixed $value): int
    {
        if (is_bool($value)) {
            return $value ? 1 : 0;
        }
        if (is_int($value)) {
            return $value;
        }
        if (is_float($value)) {
            return (int) $value;
        }
        if (is_string($value) && is_numeric(trim($value))) {
            return (int) trim($value);
        }

        return 0;
    }

    public static function toBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        if (is_int($value) || is_float($value)) {
            return (int) $value === 1;
        }

        return (bool) preg_match('/^(true|yes|on|1|checked)$/i', self::toText($value));
    }

    public static function isFilled(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        if (is_int($value) || is_float($value)) {
            return $value > 0;
        }

        return self::toText($value) !== '';
    }

    public static function normalizeGuideStatus(string $status): string
    {
        $map = [
            'Recognised' => 'Recognized',
            'recognised' => 'Recognized',
            'recognized' => 'Recognized',
            'provisional' => 'Provisional',
            'expired' => 'Expired',
            'not applicable' => 'Not Applicable',
        ];

        $key = strtolower(trim($status));
        return $map[$key] ?? trim($status);
    }

    private static function generatedChildId(string $profileId, string $collection, int $index): string
    {
        $profileId = $profileId !== '' ? $profileId : 'faculty';
        $prefixes = [
            'qualifications' => 'qual',
            'experience' => 'exp',
            'publications' => 'pub',
            'projects' => 'prj',
            'conferencesAttended' => 'att',
            'conferencesConducted' => 'cond',
            'trainingPrograms' => 'trn',
            'patents' => 'pat',
            'researchAreas' => 'area',
            'achievements' => 'ach',
            'certifications' => 'cert',
            'responsibilities' => 'resp',
        ];
        $prefix = $prefixes[$collection] ?? 'item';

        return $profileId . '-' . $prefix . '-' . ($index + 1);
    }
}
