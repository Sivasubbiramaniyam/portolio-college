<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\ApiException;
use App\Core\Enums;
use App\Core\ProfileShape;
use App\Core\Validator;
use App\Repositories\FacultyRepositoryInterface;

/**
 * Faculty profile use-cases: listing, validation, authorisation and writes.
 */
final class FacultyService
{
    public function __construct(
        private FacultyRepositoryInterface $repository,
        private ProfileCompletionService $completion,
        private string $deanId = 'fac-dean'
    ) {
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{items: array<int, array<string, mixed>>, total: int, page: int, pageSize: int}
     */
    public function list(array $filters = []): array
    {
        $includeDean = $this->boolQuery($filters['includeDean'] ?? false);
        $query = strtolower(trim((string) ($filters['q'] ?? '')));
        $department = trim((string) ($filters['department'] ?? ''));
        $designation = trim((string) ($filters['designation'] ?? ''));

        $items = [];
        foreach ($this->repository->all() as $profile) {
            if (!$includeDean && ($profile['isDean'] ?? false) === true) {
                continue;
            }
            if ($department !== '' && (string) ($profile['department'] ?? '') !== $department) {
                continue;
            }
            if ($designation !== '' && (string) ($profile['designation'] ?? '') !== $designation) {
                continue;
            }
            if ($query !== '') {
                $haystack = strtolower(implode(' ', [
                    (string) ($profile['name'] ?? ''),
                    (string) ($profile['department'] ?? ''),
                    (string) ($profile['designation'] ?? ''),
                    (string) ($profile['specialization'] ?? ''),
                    (string) ($profile['facultyId'] ?? ''),
                    (string) ($profile['email'] ?? ''),
                ]));
                if (!str_contains($haystack, $query)) {
                    continue;
                }
            }
            $items[] = $profile;
        }

        $total = count($items);
        $page = max(1, (int) ($filters['page'] ?? 1));
        $pageSize = (int) ($filters['pageSize'] ?? 50);
        if ($pageSize < 1) {
            $pageSize = 50;
        }
        $pageSize = min(200, $pageSize);
        $offset = ($page - 1) * $pageSize;

        return [
            'items' => array_slice($items, $offset, $pageSize),
            'total' => $total,
            'page' => $page,
            'pageSize' => $pageSize,
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public function all(): array
    {
        return $this->repository->all();
    }

    /** @return array<string, mixed>|null */
    public function maybeFind(string $id): ?array
    {
        return $this->repository->find($id);
    }

    /** @return array<string, mixed> */
    public function require(string $id): array
    {
        $profile = $this->repository->find($id);
        if ($profile === null) {
            throw ApiException::notFound('The faculty profile was not found.');
        }

        return $profile;
    }

    /** @return array<string, mixed>|null */
    public function dean(): ?array
    {
        $dean = $this->repository->findDean();
        if ($dean !== null) {
            return $dean;
        }

        return $this->repository->find($this->deanId);
    }

    /** @return array<string, mixed> */
    public function completionFor(string $id): array
    {
        $profile = $this->require($id);
        $completion = $this->completion->calculate($profile);

        return array_merge(['profileId' => $profile['id']], $completion);
    }

    /**
     * @param array<string, mixed> $input
     * @param array<string, mixed> $actor
     * @return array<string, mixed>
     */
    public function create(array $input, array $actor): array
    {
        $this->assertAdmin($actor);

        $input = $this->expandAliases($input);
        $id = $this->resolveNewId(ProfileShape::toText($input['id'] ?? ''));
        $input['id'] = $id;

        $profile = ProfileShape::normalize($input);
        $profile['id'] = $id;
        $profile['createdAt'] = gmdate('c');
        $profile['updatedAt'] = gmdate('c');
        if (!$this->isAdmin($actor)) {
            $profile['isDean'] = false;
        }

        $this->validate($profile, false, $input, null);
        $this->validateDeanConflict($profile);
        $this->validateUniqueness($profile, null);

        return $this->repository->create($profile);
    }

    /**
     * @param array<string, mixed> $input
     * @param array<string, mixed> $actor
     * @return array<string, mixed>
     */
    public function update(string $id, array $input, bool $partial, array $actor): array
    {
        $existing = $this->require($id);
        $this->assertCanEdit($id, $actor);

        $input = $this->expandAliases($input);
        $input = $this->stripProtectedInput($input);

        if ($partial) {
            $merged = $existing;
            foreach ($input as $key => $value) {
                if (in_array($key, ProfileShape::rootFields(), true) || array_key_exists($key, ProfileShape::COLLECTIONS)) {
                    $merged[$key] = $value;
                }
            }
        } else {
            $merged = $input;
            $merged['id'] = $id;
        }

        $merged['id'] = $id;
        $merged['createdAt'] = $existing['createdAt'] ?? gmdate('c');
        $merged['updatedAt'] = gmdate('c');

        $profile = ProfileShape::normalize($merged);
        $profile['id'] = $id;
        $profile['createdAt'] = $existing['createdAt'] ?? $profile['createdAt'];
        $profile['updatedAt'] = gmdate('c');

        // Only administrators may change the dean flag.
        if (!$this->isAdmin($actor)) {
            $profile['isDean'] = (bool) ($existing['isDean'] ?? false);
        }

        $this->validate($profile, $partial, $input, $id);
        $this->validateDeanConflict($profile);
        $this->validateUniqueness($profile, $id);

        return $this->repository->update($id, $profile);
    }

    /** @param array<string, mixed> $actor */
    public function delete(string $id, array $actor): void
    {
        $this->assertAdmin($actor);
        $profile = $this->require($id);

        if ((string) $profile['id'] === $this->deanId || ($profile['isDean'] ?? false) === true) {
            throw ApiException::conflict('The dean profile is protected and cannot be deleted.');
        }

        $this->repository->delete($id);
    }

    /** @param array<string, mixed> $actor */
    public function assertCanEdit(string $id, array $actor): void
    {
        $role = (string) ($actor['role'] ?? '');
        if ($role === 'admin') {
            return;
        }
        if ($role === 'faculty' && (string) ($actor['facultyProfileId'] ?? '') === $id) {
            return;
        }

        throw ApiException::forbidden('You can only edit your own faculty profile.');
    }

    /** @param array<string, mixed> $actor */
    private function assertAdmin(array $actor): void
    {
        if (!$this->isAdmin($actor)) {
            throw ApiException::forbidden('Administrator access is required.');
        }
    }

    /** @param array<string, mixed> $actor */
    private function isAdmin(array $actor): bool
    {
        return (string) ($actor['role'] ?? '') === 'admin';
    }

    /**
     * Validate root fields and child collections. For PATCH only supplied fields
     * are validated; for PUT/POST the complete payload is authoritative.
     *
     * @param array<string, mixed> $profile
     * @param array<string, mixed> $input
     */
    private function validate(array $profile, bool $partial, array $input, ?string $existingId): void
    {
        $validator = new Validator();
        $root = static fn (string $field): bool => !$partial || array_key_exists($field, $input);
        $collection = static fn (string $key): bool => !$partial || array_key_exists($key, $input);
        $maxYear = (int) date('Y') + 1;

        if ($root('name')) {
            $validator->requiredString($profile, 'name', 'Full name', 120);
        }
        if ($root('facultyId')) {
            $validator->requiredString($profile, 'facultyId', 'Employee / faculty ID', 40);
        }
        if ($root('department')) {
            $validator->requiredEnum($profile, 'department', 'Department', Enums::DEPARTMENTS);
        }
        if ($root('designation')) {
            $validator->requiredEnum($profile, 'designation', 'Designation', Enums::DESIGNATIONS);
        }
        if ($root('email')) {
            $validator->requiredEmail($profile);
        }
        if ($root('contact')) {
            $validator->requiredPhone($profile);
        }
        if ($root('specialization')) {
            $validator->requiredString($profile, 'specialization', 'Areas of specialization', 500);
        }

        foreach (['photo'] as $mediaField) {
            if ($root($mediaField)) {
                $validator->optionalString($profile, $mediaField, ucfirst($mediaField), 8_000_000);
            }
        }

        if ($root('researchGuideNumber')) {
            $validator->optionalString($profile, 'researchGuideNumber', 'Research guide number', 120);
        }
        if ($root('researchGuideUniversity')) {
            $validator->optionalString($profile, 'researchGuideUniversity', 'Research guide university', 160);
        }
        if ($root('researchGuideDate')) {
            $validator->date($profile, 'researchGuideDate', 'Research guide date');
        }
        if ($root('researchGuideStatus')) {
            $validator->optionalEnum($profile, 'researchGuideStatus', 'Research guide status', Enums::OPTIONS['researchGuideStatus']);
        }

        foreach (['phdCompleted', 'mphilCompleted', 'otherCompleted', 'phdGuiding', 'mphilGuiding', 'otherGuiding'] as $countField) {
            if ($root($countField)) {
                $validator->integerRange($profile, $countField, ucfirst($countField), 0);
            }
        }

        foreach (['googleScholarId', 'orcidId', 'scopusId'] as $stringField) {
            if ($root($stringField)) {
                $validator->optionalString($profile, $stringField, $stringField, 160);
            }
        }
        foreach (['googleScholarUrl', 'orcidUrl', 'scopusUrl'] as $urlField) {
            if ($root($urlField)) {
                $validator->url($profile, $urlField, $urlField);
            }
        }

        if ($root('displayOrder')) {
            $validator->integerRange($profile, 'displayOrder', 'Display order', 0);
        }

        if (($profile['isDean'] ?? false) === true) {
            $dean = $this->repository->findDean();
            if ($dean !== null && (string) ($dean['id'] ?? '') !== (string) ($profile['id'] ?? '') && $existingId !== (string) ($dean['id'] ?? '')) {
                $validator->add('isDean', 'Another profile is already marked as the dean.');
            }
        }

        if ($collection('qualifications')) {
            foreach ($profile['qualifications'] as $i => $entry) {
                $p = 'qualifications[' . $i . '].';
                $validator->requiredString($entry, 'degree', 'Degree', 180, $p . 'degree');
                $validator->requiredString($entry, 'institution', 'Institution', 180, $p . 'institution');
                $validator->optionalString($entry, 'university', 'University', 180, $p . 'university');
                $validator->integerRange($entry, 'year', 'Year', 1950, $maxYear, true, $p . 'year');
                $validator->optionalEnum($entry, 'classObtained', 'Class obtained', Enums::OPTIONS['qualificationClass'], $p . 'classObtained');
                $validator->optionalString($entry, 'specialization', 'Specialisation', 180, $p . 'specialization');
            }
        }

        if ($collection('experience')) {
            foreach ($profile['experience'] as $i => $entry) {
                $p = 'experience[' . $i . '].';
                $validator->requiredString($entry, 'institution', 'Institution', 180, $p . 'institution');
                $validator->requiredString($entry, 'designation', 'Designation', 120, $p . 'designation');
                $validator->month($entry, 'from', 'Start date', true, $p . 'from');
                $validator->month($entry, 'to', 'End date', false, $p . 'to');
                $validator->optionalString($entry, 'total', 'Total experience', 120, $p . 'total');
                $validator->range($entry, 'from', 'to', 'End date', 'Y-m', rtrim($p, '.'));
            }
        }

        if ($collection('publications')) {
            foreach ($profile['publications'] as $i => $entry) {
                $p = 'publications[' . $i . '].';
                $validator->requiredString($entry, 'title', 'Publication title', 300, $p . 'title');
                $validator->requiredEnum($entry, 'type', 'Publication type', Enums::OPTIONS['publicationType'], $p . 'type');
                $validator->requiredString($entry, 'venue', 'Journal or conference', 250, $p . 'venue');
                $validator->optionalString($entry, 'authors', 'Authors', 500, $p . 'authors');
                $validator->integerRange($entry, 'year', 'Publication year', 1950, $maxYear, true, $p . 'year');
                $validator->optionalString($entry, 'volume', 'Volume', 40, $p . 'volume');
                $validator->optionalString($entry, 'issue', 'Issue', 40, $p . 'issue');
                $validator->optionalString($entry, 'pages', 'Pages', 60, $p . 'pages');
                $validator->optionalString($entry, 'publisher', 'Publisher', 160, $p . 'publisher');
                $validator->optionalString($entry, 'indexing', 'Indexing', 300, $p . 'indexing');
                $validator->doi($entry, 'doi', 'DOI', $p . 'doi');
                $validator->url($entry, 'url', 'Publication URL', false, $p . 'url');
            }
        }

        if ($collection('projects')) {
            foreach ($profile['projects'] as $i => $entry) {
                $p = 'projects[' . $i . '].';
                $validator->requiredString($entry, 'title', 'Project title', 300, $p . 'title');
                $validator->requiredString($entry, 'agency', 'Funding agency', 200, $p . 'agency');
                $validator->optionalEnum($entry, 'category', 'Project category', Enums::OPTIONS['projectCategory'], $p . 'category');
                $validator->optionalString($entry, 'pi', 'Principal investigator', 180, $p . 'pi');
                $validator->optionalString($entry, 'coPi', 'Co-principal investigator', 180, $p . 'coPi');
                $validator->optionalString($entry, 'role', 'Faculty role', 160, $p . 'role');
                $validator->optionalString($entry, 'duration', 'Duration', 120, $p . 'duration');
                $validator->month($entry, 'start', 'Start date', false, $p . 'start');
                $validator->month($entry, 'end', 'End date', false, $p . 'end');
                $validator->range($entry, 'start', 'end', 'End date', 'Y-m', rtrim($p, '.'));
                $validator->numericMin($entry, 'amount', 'Amount', 0, $p . 'amount');
                $validator->requiredEnum($entry, 'status', 'Project status', Enums::OPTIONS['projectStatus'], $p . 'status');
                $validator->optionalString($entry, 'reference', 'Reference number', 120, $p . 'reference');
                $validator->optionalString($entry, 'description', 'Description', 5000, $p . 'description');
                $validator->optionalString($entry, 'sanctionLetter', 'Sanction letter', 8_000_000, $p . 'sanctionLetter');
            }
        }

        if ($collection('conferencesAttended')) {
            foreach ($profile['conferencesAttended'] as $i => $entry) {
                $p = 'conferencesAttended[' . $i . '].';
                $validator->requiredString($entry, 'name', 'Conference name', 300, $p . 'name');
                $validator->optionalEnum($entry, 'type', 'Conference type', Enums::OPTIONS['conferenceType'], $p . 'type');
                $validator->optionalEnum($entry, 'category', 'Conference category', Enums::OPTIONS['conferenceCategory'], $p . 'category');
                $validator->optionalString($entry, 'organizer', 'Organizer', 200, $p . 'organizer');
                $validator->optionalString($entry, 'location', 'Location', 160, $p . 'location');
                $validator->date($entry, 'start', 'Start date', false, $p . 'start');
                $validator->date($entry, 'end', 'End date', false, $p . 'end');
                $validator->range($entry, 'start', 'end', 'End date', 'Y-m-d', rtrim($p, '.'));
                $validator->optionalEnum($entry, 'participation', 'Participation', Enums::OPTIONS['conferenceParticipation'], $p . 'participation');
                $validator->optionalString($entry, 'paperTitle', 'Paper title', 300, $p . 'paperTitle');
                $validator->optionalString($entry, 'certificate', 'Certificate', 8_000_000, $p . 'certificate');
            }
        }

        if ($collection('conferencesConducted')) {
            foreach ($profile['conferencesConducted'] as $i => $entry) {
                $p = 'conferencesConducted[' . $i . '].';
                $validator->requiredString($entry, 'name', 'Conference name', 300, $p . 'name');
                $validator->optionalEnum($entry, 'type', 'Conference type', Enums::OPTIONS['conferenceType'], $p . 'type');
                $validator->optionalEnum($entry, 'category', 'Conference category', Enums::OPTIONS['conferenceCategory'], $p . 'category');
                $validator->optionalString($entry, 'organizer', 'Organizer', 200, $p . 'organizer');
                $validator->optionalEnum($entry, 'role', 'Faculty role', Enums::OPTIONS['conferenceRole'], $p . 'role');
                $validator->optionalString($entry, 'location', 'Location', 160, $p . 'location');
                $validator->date($entry, 'start', 'Start date', false, $p . 'start');
                $validator->date($entry, 'end', 'End date', false, $p . 'end');
                $validator->range($entry, 'start', 'end', 'End date', 'Y-m-d', rtrim($p, '.'));
                $validator->integerRange($entry, 'participants', 'Participants', 0, null, false, $p . 'participants');
                $validator->url($entry, 'website', 'Website', false, $p . 'website');
            }
        }

        if ($collection('trainingPrograms')) {
            foreach ($profile['trainingPrograms'] as $i => $entry) {
                $p = 'trainingPrograms[' . $i . '].';
                $validator->requiredString($entry, 'title', 'Program title', 300, $p . 'title');
                $validator->optionalEnum($entry, 'type', 'Program type', Enums::OPTIONS['trainingType'], $p . 'type');
                $validator->optionalString($entry, 'organizer', 'Organizer', 200, $p . 'organizer');
                $validator->optionalEnum($entry, 'level', 'Level', Enums::OPTIONS['trainingLevel'], $p . 'level');
                $validator->date($entry, 'start', 'Start date', false, $p . 'start');
                $validator->date($entry, 'end', 'End date', false, $p . 'end');
                $validator->range($entry, 'start', 'end', 'End date', 'Y-m-d', rtrim($p, '.'));
                $validator->optionalEnum($entry, 'role', 'Role', Enums::OPTIONS['trainingRole'], $p . 'role');
                $validator->integerRange($entry, 'participants', 'Participants', 0, null, false, $p . 'participants');
                $validator->optionalString($entry, 'certificate', 'Certificate', 8_000_000, $p . 'certificate');
            }
        }

        if ($collection('patents')) {
            foreach ($profile['patents'] as $i => $entry) {
                $p = 'patents[' . $i . '].';
                $validator->requiredString($entry, 'title', 'Patent title', 300, $p . 'title');
                $validator->requiredString($entry, 'number', 'Patent number', 80, $p . 'number');
                $validator->optionalString($entry, 'type', 'Patent type', 120, $p . 'type');
                $validator->optionalString($entry, 'inventors', 'Inventors', 500, $p . 'inventors');
                $validator->optionalString($entry, 'authority', 'Authority', 200, $p . 'authority');
                $validator->optionalString($entry, 'country', 'Country', 80, $p . 'country');
                $validator->date($entry, 'grantDate', 'Grant date', true, $p . 'grantDate');
                $validator->requiredEnum($entry, 'status', 'Patent status', Enums::OPTIONS['patentStatus'], $p . 'status');
                $validator->url($entry, 'url', 'Patent URL', false, $p . 'url');
                $validator->optionalString($entry, 'certificate', 'Certificate', 8_000_000, $p . 'certificate');
            }
        }

        if ($collection('researchAreas')) {
            foreach ($profile['researchAreas'] as $i => $entry) {
                $p = 'researchAreas[' . $i . '].';
                $validator->requiredString($entry, 'area', 'Research area', 160, $p . 'area');
                $validator->optionalString($entry, 'subject', 'Subject', 160, $p . 'subject');
                $validator->optionalString($entry, 'specialization', 'Specialisation', 200, $p . 'specialization');
                $validator->optionalString($entry, 'keywords', 'Keywords', 500, $p . 'keywords');
            }
        }

        foreach (['achievements' => 'achievementCategory', 'certifications' => 'certificationCategory'] as $collectionKey => $categoryOption) {
            if (!$collection($collectionKey)) {
                continue;
            }
            foreach ($profile[$collectionKey] as $i => $entry) {
                $p = $collectionKey . '[' . $i . '].';
                $validator->requiredString($entry, 'title', 'Title', 300, $p . 'title');
                $validator->optionalEnum($entry, 'category', 'Category', Enums::OPTIONS[$categoryOption], $p . 'category');
                $validator->requiredString($entry, 'organization', 'Organisation', 200, $p . 'organization');
                $validator->optionalString($entry, 'year', 'Year', 80, $p . 'year');
                $validator->optionalString($entry, 'certificateNumber', 'Certificate number', 120, $p . 'certificateNumber');
                $validator->url($entry, 'certificateUrl', 'Certificate URL', false, $p . 'certificateUrl');
                $validator->optionalString($entry, 'description', 'Description', 5000, $p . 'description');
                $validator->optionalString($entry, 'certificate', 'Certificate', 8_000_000, $p . 'certificate');
            }
        }

        if ($collection('responsibilities')) {
            foreach ($profile['responsibilities'] as $i => $entry) {
                $p = 'responsibilities[' . $i . '].';
                $validator->requiredString($entry, 'title', 'Responsibility title', 300, $p . 'title');
                $validator->optionalEnum($entry, 'category', 'Category', Enums::OPTIONS['responsibilityCategory'], $p . 'category');
                $validator->optionalString($entry, 'institution', 'Institution', 200, $p . 'institution');
                $validator->optionalString($entry, 'position', 'Position', 160, $p . 'position');
                $validator->optionalEnum($entry, 'level', 'Level', Enums::OPTIONS['responsibilityLevel'], $p . 'level');
                $validator->month($entry, 'from', 'Start date', false, $p . 'from');
                $validator->month($entry, 'to', 'End date', false, $p . 'to');
                $validator->booleanish($entry, 'current', 'Current flag', $p . 'current');
                $validator->range($entry, 'from', 'to', 'End date', 'Y-m', rtrim($p, '.'));
            }
        }

        $validator->throwIfFails();
    }

    /** @param array<string, mixed> $profile */
    private function validateDeanConflict(array $profile): void
    {
        if (($profile['isDean'] ?? false) !== true) {
            return;
        }
        $dean = $this->repository->findDean();
        if ($dean !== null && (string) ($dean['id'] ?? '') !== (string) ($profile['id'] ?? '')) {
            throw ApiException::conflict('Another profile is already marked as the dean.', ['isDean' => 'Another profile is already marked as the dean.']);
        }
    }

    /** @param array<string, mixed> $profile */
    private function validateUniqueness(array $profile, ?string $existingId): void
    {
        $errors = [];
        if ((string) ($profile['facultyId'] ?? '') !== '' && $this->repository->existsFacultyId((string) $profile['facultyId'], $existingId)) {
            $errors['facultyId'] = 'A faculty member with this employee ID already exists.';
        }
        if ((string) ($profile['email'] ?? '') !== '' && $this->repository->existsEmail((string) $profile['email'], $existingId)) {
            $errors['email'] = 'A faculty member with this email address already exists.';
        }
        if ($errors !== []) {
            throw ApiException::validation($errors);
        }
    }

    /** @param array<string, mixed> $input */
    private function expandAliases(array $input): array
    {
        foreach (ProfileShape::COLLECTION_ALIASES as $alias => $canonical) {
            if (!array_key_exists($canonical, $input) && array_key_exists($alias, $input)) {
                $input[$canonical] = $input[$alias];
            }
        }

        return $input;
    }

    /** @param array<string, mixed> $input */
    private function stripProtectedInput(array $input): array
    {
        foreach (['createdAt', 'updatedAt'] as $field) {
            unset($input[$field]);
        }

        return $input;
    }

    private function resolveNewId(string $requested): string
    {
        if ($requested !== '' && preg_match('/^[A-Za-z0-9_-]{3,80}$/', $requested) && $this->repository->find($requested) === null) {
            return $requested;
        }

        do {
            $id = 'fac-' . bin2hex(random_bytes(5));
        } while ($this->repository->find($id) !== null);

        return $id;
    }

    private function boolQuery(mixed $value): bool
    {
        return in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on'], true);
    }
}
