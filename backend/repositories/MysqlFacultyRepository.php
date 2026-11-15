<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\ApiException;
use App\Core\Database;
use App\Core\ProfileShape;
use PDOException;

/**
 * MySQL-backed faculty repository.
 *
 * Implements the same contract as JsonFacultyRepository: profiles are returned
 * in the canonical ProfileShape, ordered by display_order then name, with
 * collections loaded from their child tables. Every query is parameterised.
 *
 * The root table stores scalar fields; each collection has its own child table
 * keyed by (profile_id, sort_order). Writes run in a transaction: the root row
 * is written, then the profile's child rows are replaced atomically.
 */
final class MysqlFacultyRepository implements FacultyRepositoryInterface
{
    use TranslatesDriverErrors;

    /** API root field => database column. */
    private const ROOT_FIELDS = [
        'id' => 'id',
        'name' => 'name',
        'facultyId' => 'faculty_id',
        'department' => 'department',
        'designation' => 'designation',
        'email' => 'email',
        'contact' => 'contact',
        'specialization' => 'specialization',
        'photo' => 'photo_url',
        'banner' => 'banner_url',
        'researchGuideNumber' => 'research_guide_number',
        'researchGuideUniversity' => 'research_guide_university',
        'researchGuideDate' => 'research_guide_date',
        'researchGuideStatus' => 'research_guide_status',
        'phdCompleted' => 'phd_completed',
        'mphilCompleted' => 'mphil_completed',
        'otherCompleted' => 'other_completed',
        'phdGuiding' => 'phd_guiding',
        'mphilGuiding' => 'mphil_guiding',
        'otherGuiding' => 'other_guiding',
        'googleScholarId' => 'google_scholar_id',
        'googleScholarUrl' => 'google_scholar_url',
        'orcidId' => 'orcid_id',
        'orcidUrl' => 'orcid_url',
        'scopusId' => 'scopus_id',
        'scopusUrl' => 'scopus_url',
        'isDean' => 'is_dean',
        'displayOrder' => 'display_order',
        'createdAt' => 'created_at',
        'updatedAt' => 'updated_at',
    ];

    /** Root fields stored as integers. */
    private const ROOT_INTS = [
        'phdCompleted', 'mphilCompleted', 'otherCompleted',
        'phdGuiding', 'mphilGuiding', 'otherGuiding', 'displayOrder',
    ];

    /** Root fields stored as booleans. */
    private const ROOT_BOOLS = ['isDean'];

    /** Root date fields (empty string is stored as NULL). */
    private const ROOT_DATES = ['researchGuideDate'];

    /** Root columns with a UNIQUE index: empty string is stored as NULL. */
    private const ROOT_UNIQUE = ['facultyId', 'email'];

    /**
     * Collection => [table, field map (api => column), ints, bools, dates].
     *
     * @var array<string, array{table: string, fields: array<string, string>, ints?: array<int, string>, bools?: array<int, string>, dates?: array<int, string>}>
     */
    private const COLLECTIONS = [
        'qualifications' => [
            'table' => 'faculty_qualifications',
            'fields' => [
                'degree' => 'degree',
                'institution' => 'institution',
                'university' => 'university',
                'year' => 'year',
                'classObtained' => 'class_obtained',
                'specialization' => 'specialization',
            ],
            'ints' => ['year'],
        ],
        'experience' => [
            'table' => 'faculty_experiences',
            'fields' => [
                'institution' => 'institution',
                'designation' => 'designation',
                'from' => 'from_month',
                'to' => 'to_month',
                'total' => 'total_experience',
            ],
        ],
        'publications' => [
            'table' => 'faculty_publications',
            'fields' => [
                'title' => 'title',
                'type' => 'type',
                'venue' => 'venue',
                'authors' => 'authors',
                'year' => 'year',
                'volume' => 'volume',
                'issue' => 'issue',
                'pages' => 'pages',
                'publisher' => 'publisher',
                'indexing' => 'indexing',
                'doi' => 'doi',
                'url' => 'url',
            ],
            'ints' => ['year'],
        ],
        'projects' => [
            'table' => 'faculty_projects',
            'fields' => [
                'title' => 'title',
                'agency' => 'agency',
                'category' => 'category',
                'pi' => 'principal_investigator',
                'coPi' => 'co_principal_investigator',
                'role' => 'faculty_role',
                'duration' => 'duration',
                'start' => 'start_month',
                'end' => 'end_month',
                'amount' => 'amount',
                'status' => 'status',
                'reference' => 'reference',
                'description' => 'description',
                'sanctionLetter' => 'sanction_letter_url',
            ],
        ],
        'conferencesAttended' => [
            'table' => 'faculty_conferences_attended',
            'fields' => [
                'name' => 'name',
                'type' => 'type',
                'category' => 'category',
                'organizer' => 'organizer',
                'location' => 'location',
                'start' => 'start_date',
                'end' => 'end_date',
                'participation' => 'participation',
                'paperTitle' => 'paper_title',
                'certificate' => 'certificate_url',
            ],
            'dates' => ['start', 'end'],
        ],
        'conferencesConducted' => [
            'table' => 'faculty_conferences_conducted',
            'fields' => [
                'name' => 'name',
                'type' => 'type',
                'category' => 'category',
                'organizer' => 'organizer',
                'role' => 'faculty_role',
                'location' => 'location',
                'start' => 'start_date',
                'end' => 'end_date',
                'participants' => 'participants',
                'website' => 'website',
            ],
            'ints' => ['participants'],
            'dates' => ['start', 'end'],
        ],
        'trainingPrograms' => [
            'table' => 'faculty_training_programs',
            'fields' => [
                'title' => 'title',
                'type' => 'type',
                'organizer' => 'organizer',
                'level' => 'level',
                'start' => 'start_date',
                'end' => 'end_date',
                'role' => 'faculty_role',
                'participants' => 'participants',
                'certificate' => 'certificate_url',
            ],
            'ints' => ['participants'],
            'dates' => ['start', 'end'],
        ],
        'patents' => [
            'table' => 'faculty_patents',
            'fields' => [
                'title' => 'title',
                'number' => 'patent_number',
                'type' => 'patent_type',
                'inventors' => 'inventors',
                'authority' => 'authority',
                'country' => 'country',
                'grantDate' => 'grant_date',
                'status' => 'status',
                'url' => 'url',
                'certificate' => 'certificate_url',
            ],
            'dates' => ['grantDate'],
        ],
        'researchAreas' => [
            'table' => 'faculty_research_areas',
            'fields' => [
                'area' => 'area',
                'subject' => 'subject',
                'specialization' => 'specialization',
                'keywords' => 'keywords',
            ],
        ],
        'achievements' => [
            'table' => 'faculty_achievements',
            'fields' => [
                'title' => 'title',
                'category' => 'category',
                'organization' => 'organization',
                'year' => 'year',
                'certificateNumber' => 'certificate_number',
                'certificateUrl' => 'certificate_url',
                'description' => 'description',
                'certificate' => 'certificate_file_url',
            ],
        ],
        'certifications' => [
            'table' => 'faculty_certifications',
            'fields' => [
                'title' => 'title',
                'category' => 'category',
                'organization' => 'organization',
                'year' => 'year',
                'certificateNumber' => 'certificate_number',
                'certificateUrl' => 'certificate_url',
                'description' => 'description',
                'certificate' => 'certificate_file_url',
            ],
        ],
        'responsibilities' => [
            'table' => 'faculty_responsibilities',
            'fields' => [
                'title' => 'title',
                'category' => 'category',
                'institution' => 'institution',
                'position' => 'position',
                'level' => 'level',
                'from' => 'from_month',
                'to' => 'to_month',
                'current' => 'is_current',
            ],
            'bools' => ['current'],
        ],
    ];

    public function __construct(private readonly Database $db)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function all(): array
    {
        $rows = $this->db->select('SELECT * FROM faculty_profiles ORDER BY display_order ASC, name ASC');
        if ($rows === []) {
            return [];
        }

        $ids = array_map(static fn (array $row): string => (string) $row['id'], $rows);
        $grouped = $this->loadChildren($ids);

        $profiles = [];
        foreach ($rows as $row) {
            $profiles[] = $this->hydrate($row, $grouped);
        }

        usort($profiles, static function (array $a, array $b): int {
            $order = ((int) ($a['displayOrder'] ?? 0)) <=> ((int) ($b['displayOrder'] ?? 0));

            return $order !== 0 ? $order : strcasecmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? ''));
        });

        return $profiles;
    }

    /** @return array<string, mixed>|null */
    public function find(string $id): ?array
    {
        $row = $this->db->selectOne('SELECT * FROM faculty_profiles WHERE id = ? LIMIT 1', [$id]);
        if ($row === null) {
            return null;
        }

        return $this->hydrate($row, $this->loadChildren([(string) $row['id']]));
    }

    /** @return array<string, mixed>|null */
    public function findDean(): ?array
    {
        $row = $this->db->selectOne('SELECT * FROM faculty_profiles WHERE is_dean = 1 LIMIT 1');
        if ($row === null) {
            return null;
        }

        return $this->hydrate($row, $this->loadChildren([(string) $row['id']]));
    }

    /** @param array<string, mixed> $profile */
    public function create(array $profile): array
    {
        $profile = ProfileShape::normalize($profile);
        $id = (string) $profile['id'];

        if ($this->existsById($id)) {
            throw ApiException::conflict('A profile with this identifier already exists.');
        }

        try {
            $this->db->transaction(function () use ($id, $profile): void {
                $this->insertRoot($profile);
                $this->insertChildren($id, $profile);
            });
        } catch (PDOException $exception) {
            if (self::isDuplicateKey($exception)) {
                throw ApiException::conflict('A profile with this identifier already exists.');
            }
            throw $exception;
        }

        return $this->find($id) ?? $profile;
    }

    /** @param array<string, mixed> $profile */
    public function update(string $id, array $profile): array
    {
        $profile = ProfileShape::normalize($profile);
        $profile['id'] = $id;

        try {
            $this->db->transaction(function () use ($id, $profile): void {
                if (!$this->existsById($id)) {
                    throw ApiException::notFound('The faculty profile was not found.');
                }

                $values = $this->rootValues($profile);
                unset($values['id']);

                $assignments = implode(', ', array_map(
                    static fn (string $column): string => $column . ' = ?',
                    array_keys($values)
                ));

                $params = array_values($values);
                $params[] = $id;
                $this->db->execute('UPDATE faculty_profiles SET ' . $assignments . ' WHERE id = ?', $params);

                $this->deleteChildren($id);
                $this->insertChildren($id, $profile);
            });
        } catch (PDOException $exception) {
            if (self::isDuplicateKey($exception)) {
                throw ApiException::conflict('Another profile already uses one of these unique values.');
            }
            throw $exception;
        }

        return $this->find($id) ?? $profile;
    }

    public function delete(string $id): bool
    {
        // Child rows and owned assets are removed by ON DELETE CASCADE.
        return $this->db->execute('DELETE FROM faculty_profiles WHERE id = ?', [$id]) > 0;
    }

    public function existsFacultyId(string $facultyId, ?string $exceptId = null): bool
    {
        $facultyId = trim($facultyId);
        if ($facultyId === '') {
            return false;
        }

        $sql = 'SELECT 1 FROM faculty_profiles WHERE faculty_id = ?';
        $params = [$facultyId];
        if ($exceptId !== null) {
            $sql .= ' AND id <> ?';
            $params[] = $exceptId;
        }

        return $this->db->scalar($sql . ' LIMIT 1', $params) !== null;
    }

    public function existsEmail(string $email, ?string $exceptId = null): bool
    {
        $email = trim($email);
        if ($email === '') {
            return false;
        }

        $sql = 'SELECT 1 FROM faculty_profiles WHERE email = ?';
        $params = [$email];
        if ($exceptId !== null) {
            $sql .= ' AND id <> ?';
            $params[] = $exceptId;
        }

        return $this->db->scalar($sql . ' LIMIT 1', $params) !== null;
    }

    private function existsById(string $id): bool
    {
        return $this->db->scalar('SELECT 1 FROM faculty_profiles WHERE id = ? LIMIT 1', [$id]) !== null;
    }

    /**
     * @param array<int, string> $profileIds
     * @return array<string, array<string, array<int, array<string, mixed>>>>
     */
    private function loadChildren(array $profileIds): array
    {
        $grouped = [];
        if ($profileIds === []) {
            return $grouped;
        }

        $placeholders = Database::placeholders($profileIds);
        $params = array_values($profileIds);

        foreach (self::COLLECTIONS as $collection => $meta) {
            $sql = 'SELECT * FROM ' . $meta['table']
                . ' WHERE profile_id IN (' . $placeholders . ')'
                . ' ORDER BY profile_id, sort_order';
            foreach ($this->db->select($sql, $params) as $row) {
                $profileId = (string) $row['profile_id'];
                $grouped[$profileId][$collection][] = $this->hydrateChildEntry($meta, $row);
            }
        }

        return $grouped;
    }

    /**
     * @param array<string, mixed> $row
     * @param array<string, array<string, array<int, array<string, mixed>>>> $grouped
     * @return array<string, mixed>
     */
    private function hydrate(array $row, array $grouped): array
    {
        $id = (string) $row['id'];
        $profile = $this->hydrateRoot($row);
        foreach (array_keys(self::COLLECTIONS) as $collection) {
            $profile[$collection] = $grouped[$id][$collection] ?? [];
        }

        return ProfileShape::normalize($profile);
    }

    /** @param array<string, mixed> $row
     *  @return array<string, mixed>
     */
    private function hydrateRoot(array $row): array
    {
        $profile = [];
        foreach (self::ROOT_FIELDS as $api => $column) {
            $value = $row[$column] ?? null;
            if (in_array($api, self::ROOT_INTS, true)) {
                $profile[$api] = (int) $value;
            } elseif (in_array($api, self::ROOT_BOOLS, true)) {
                $profile[$api] = ((int) $value) === 1;
            } else {
                $profile[$api] = $value === null ? '' : (string) $value;
            }
        }

        return $profile;
    }

    /**
     * @param array{table: string, fields: array<string, string>, ints?: array<int, string>, bools?: array<int, string>, dates?: array<int, string>} $meta
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function hydrateChildEntry(array $meta, array $row): array
    {
        $entry = [
            'id' => (string) $row['id'],
            'sortOrder' => (int) $row['sort_order'],
        ];

        foreach ($meta['fields'] as $api => $column) {
            $value = $row[$column] ?? null;
            if (in_array($api, $meta['ints'] ?? [], true)) {
                $entry[$api] = (int) $value;
            } elseif (in_array($api, $meta['bools'] ?? [], true)) {
                $entry[$api] = ((int) $value) === 1;
            } else {
                $entry[$api] = $value === null ? '' : (string) $value;
            }
        }

        return $entry;
    }

    /** @param array<string, mixed> $profile */
    private function insertRoot(array $profile): void
    {
        $values = $this->rootValues($profile);
        $sql = 'INSERT INTO faculty_profiles (' . implode(', ', array_keys($values)) . ')'
            . ' VALUES (' . Database::placeholders($values) . ')';

        $this->db->execute($sql, array_values($values));
    }

    /** @param array<string, mixed> $profile */
    private function insertChildren(string $profileId, array $profile): void
    {
        foreach (self::COLLECTIONS as $collection => $meta) {
            $entries = is_array($profile[$collection] ?? null) ? $profile[$collection] : [];
            $position = 0;

            foreach ($entries as $entry) {
                if (!is_array($entry)) {
                    continue;
                }

                $columns = ['id', 'profile_id', 'sort_order'];
                $values = [(string) ($entry['id'] ?? ''), $profileId, $position];

                foreach ($meta['fields'] as $api => $column) {
                    $columns[] = $column;
                    $values[] = $this->childValue($meta, $api, $entry[$api] ?? '');
                }

                $sql = 'INSERT INTO ' . $meta['table']
                    . ' (' . implode(', ', $columns) . ')'
                    . ' VALUES (' . Database::placeholders($values) . ')';
                $this->db->execute($sql, $values);
                $position++;
            }
        }
    }

    private function deleteChildren(string $profileId): void
    {
        foreach (self::COLLECTIONS as $meta) {
            $this->db->execute('DELETE FROM ' . $meta['table'] . ' WHERE profile_id = ?', [$profileId]);
        }
    }

    /**
     * @param array{table: string, fields: array<string, string>, ints?: array<int, string>, bools?: array<int, string>, dates?: array<int, string>} $meta
     */
    private function childValue(array $meta, string $field, mixed $value): mixed
    {
        if (in_array($field, $meta['ints'] ?? [], true)) {
            return ProfileShape::toInt($value);
        }
        if (in_array($field, $meta['bools'] ?? [], true)) {
            return ProfileShape::toBool($value) ? 1 : 0;
        }

        $text = ProfileShape::toText($value);
        if (in_array($field, $meta['dates'] ?? [], true)) {
            return $text === '' ? null : $text;
        }

        return $text;
    }

    /** @param array<string, mixed> $profile
     *  @return array<string, mixed>
     */
    private function rootValues(array $profile): array
    {
        $values = [];
        foreach (self::ROOT_FIELDS as $api => $column) {
            $value = $profile[$api] ?? null;

            if (in_array($api, self::ROOT_INTS, true)) {
                $values[$column] = ProfileShape::toInt($value);
                continue;
            }
            if (in_array($api, self::ROOT_BOOLS, true)) {
                $values[$column] = ProfileShape::toBool($value) ? 1 : 0;
                continue;
            }

            $text = ProfileShape::toText($value);
            if ($text === '' && (in_array($api, self::ROOT_UNIQUE, true) || in_array($api, self::ROOT_DATES, true))) {
                $values[$column] = null;
                continue;
            }

            $values[$column] = $text;
        }

        return $values;
    }
}
