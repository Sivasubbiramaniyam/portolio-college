<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\ProfileShape;

/**
 * Exact 15-section completion rules from the data contract. Every section has
 * equal weight; percent is round(filled / 15 * 100).
 */
final class ProfileCompletionService
{
    /** @var array<int, array{key: string, label: string}> */
    private const SECTIONS = [
        ['key' => 'personal', 'label' => 'Personal Information'],
        ['key' => 'specializations', 'label' => 'Areas of Specialization'],
        ['key' => 'qualification', 'label' => 'Qualification'],
        ['key' => 'experience', 'label' => 'Professional Experience'],
        ['key' => 'publications', 'label' => 'Publications'],
        ['key' => 'conferencesAttended', 'label' => 'Conferences Attended'],
        ['key' => 'conferencesConducted', 'label' => 'Conferences Conducted'],
        ['key' => 'trainingPrograms', 'label' => 'Training Programs Conducted'],
        ['key' => 'patents', 'label' => 'Patents'],
        ['key' => 'projects', 'label' => 'Funded Projects'],
        ['key' => 'researchAreas', 'label' => 'Research'],
        ['key' => 'achievements', 'label' => 'Achievements'],
        ['key' => 'certifications', 'label' => 'Certifications'],
        ['key' => 'responsibilities', 'label' => 'Additional Responsibilities'],
        ['key' => 'researchIds', 'label' => 'Research Profile IDs'],
    ];

    /**
     * @param array<string, mixed> $profile
     * @return array{percent: int, filled: int, total: int, badge: array{label: string, tone: string}, sections: array<int, array{key: string, label: string, complete: bool}>}
     */
    public function calculate(array $profile): array
    {
        $profile = ProfileShape::normalize($profile);
        $sections = [];
        $filled = 0;

        foreach (self::SECTIONS as $section) {
            $complete = $this->isSectionComplete($profile, $section['key']);
            if ($complete) {
                $filled++;
            }
            $sections[] = [
                'key' => $section['key'],
                'label' => $section['label'],
                'complete' => $complete,
            ];
        }

        $total = count(self::SECTIONS);
        $percent = (int) round(($filled / $total) * 100);

        return [
            'percent' => $percent,
            'filled' => $filled,
            'total' => $total,
            'badge' => $this->badge($percent),
            'sections' => $sections,
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $profiles
     * @return array{total: int, complete: int, incomplete: int, rate: int, departments: array<int, array{department: string, count: int}>}
     */
    public function directoryStats(array $profiles): array
    {
        $eligible = array_values(array_filter($profiles, static fn (array $profile): bool => ($profile['isDean'] ?? false) !== true));

        $total = count($eligible);
        $complete = 0;
        $departmentCounts = [];

        foreach ($eligible as $profile) {
            $completion = $this->calculate($profile);
            if ($completion['percent'] >= 100) {
                $complete++;
            }
            $department = trim((string) ($profile['department'] ?? ''));
            if ($department === '') {
                $department = 'Not assigned';
            }
            $departmentCounts[$department] = ($departmentCounts[$department] ?? 0) + 1;
        }

        $departments = [];
        foreach ($departmentCounts as $department => $count) {
            $departments[] = ['department' => $department, 'count' => $count];
        }
        usort($departments, static function (array $a, array $b): int {
            $byCount = $b['count'] <=> $a['count'];

            return $byCount !== 0 ? $byCount : strcasecmp($a['department'], $b['department']);
        });

        return [
            'total' => $total,
            'complete' => $complete,
            'incomplete' => max(0, $total - $complete),
            'rate' => $total > 0 ? (int) round(($complete / $total) * 100) : 0,
            'departments' => $departments,
        ];
    }

    /** @return array{label: string, tone: string} */
    public function badge(int $percent): array
    {
        if ($percent >= 100) {
            return ['label' => 'Profile complete', 'tone' => 'complete'];
        }
        if ($percent >= 70) {
            return ['label' => 'Looking strong', 'tone' => 'strong'];
        }
        if ($percent >= 40) {
            return ['label' => 'In progress', 'tone' => 'progress'];
        }

        return ['label' => 'Getting started', 'tone' => 'start'];
    }

    /** @param array<string, mixed> $profile */
    private function isSectionComplete(array $profile, string $key): bool
    {
        if ($key === 'personal') {
            foreach (['name', 'facultyId', 'department', 'designation', 'email', 'contact'] as $field) {
                if (trim((string) ($profile[$field] ?? '')) === '') {
                    return false;
                }
            }
            return true;
        }

        if ($key === 'specializations') {
            return trim((string) ($profile['specialization'] ?? '')) !== '';
        }

        if ($key === 'researchIds') {
            foreach (['googleScholarId', 'googleScholarUrl', 'orcidId', 'orcidUrl', 'scopusId', 'scopusUrl'] as $field) {
                if (trim((string) ($profile[$field] ?? '')) !== '') {
                    return true;
                }
            }
            return false;
        }

        if ($key === 'researchAreas') {
            $hasGuide = false;
            foreach (['researchGuideNumber', 'researchGuideUniversity', 'researchGuideDate', 'researchGuideStatus'] as $field) {
                if (trim((string) ($profile[$field] ?? '')) !== '') {
                    $hasGuide = true;
                    break;
                }
            }
            $hasCounts = false;
            foreach (['phdCompleted', 'otherCompleted', 'phdGuiding', 'otherGuiding'] as $field) {
                if ((int) ($profile[$field] ?? 0) > 0) {
                    $hasCounts = true;
                    break;
                }
            }

            return $this->hasEntries($profile, 'researchAreas') || $hasGuide || $hasCounts;
        }

        $collection = $key === 'qualification' ? 'qualifications' : $key;

        return $this->hasEntries($profile, $collection);
    }

    /** @param array<string, mixed> $profile */
    private function hasEntries(array $profile, string $collection): bool
    {
        $entries = $profile[$collection] ?? [];
        if (!is_array($entries)) {
            return false;
        }
        foreach ($entries as $entry) {
            if (is_array($entry) && ProfileShape::entryHasContent($collection, $entry)) {
                return true;
            }
        }

        return false;
    }
}
