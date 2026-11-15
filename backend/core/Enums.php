<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Canonical departments, designations, roles and enum option lists.
 */
final class Enums
{
    public const DEPARTMENTS = [
        'Computer Science and Engineering',
        'Information Technology',
        'Electronics and Communication Engineering',
        'Electrical and Electronics Engineering',
        'Mechanical Engineering',
        'Civil Engineering',
        'MBA',
        'Science and Humanities',
    ];

    public const DESIGNATIONS = [
        'Professor & Head',
        'Professor',
        'Dean & Professor',
        'Associate Professor',
        'Asst Professor (Sr.Gr)',
        'Asst Professor (Sr.Gr) & HOD',
        'Assistant Professor & HOD',
        'Assistant Professor',
        'Dean',
        'Visiting Faculty',
    ];

    public const ROLES = [
        'admin',
        'faculty',
        'viewer',
    ];

    /** @var array<string, array<int, string>> */
    public const OPTIONS = [
        'qualificationClass' => ['Distinction', '1st Class', '2nd Class', '3rd Class', 'Pass'],
        'projectCategory' => ['Government Funded', 'University Funded', 'Industry Funded', 'Research Grant', 'Other'],
        'projectStatus' => ['Ongoing', 'Completed'],
        'publicationType' => ['Journal Article', 'Conference Paper', 'Book', 'Book Chapter', 'Review Article', 'Other'],
        'achievementCategory' => ['Academic Achievement', 'Teaching Achievement', 'Research Achievement', 'Award', 'Professional Certification', 'Training', 'Other'],
        'certificationCategory' => ['Professional Certification', 'Training', 'Academic Achievement', 'Teaching Achievement', 'Research Achievement', 'Award', 'Other'],
        'conferenceType' => ['Technical Conference', 'Research Conference', 'Workshop', 'Symposium', 'Seminar'],
        'conferenceCategory' => ['National', 'International'],
        'conferenceParticipation' => ['Participant', 'Presenter', 'Paper Presenter', 'Resource Person', 'Invited Speaker'],
        'conferenceRole' => ['Convener', 'Co-Convener', 'Coordinator', 'Organizing Secretary', 'Organizing Committee Member', 'Chairperson'],
        'trainingType' => ['FDP', 'Workshop', 'Seminar', 'Training', 'STTP', 'Refresher Course', 'Orientation Programme', 'Other'],
        'trainingLevel' => ['National', 'International', 'State', 'Institutional'],
        'trainingRole' => ['Coordinator', 'Resource Person', 'Trainer', 'Organizer'],
        'patentStatus' => ['Granted', 'Awarded'],
        'responsibilityCategory' => ['Academic Responsibility', 'Administrative Responsibility', 'Department Responsibility', 'Institutional Responsibility', 'Professional Responsibility', 'Other'],
        'responsibilityLevel' => ['Department Level', 'College Level', 'University Level', 'National Level', 'International Level'],
        'researchGuideStatus' => ['Recognized', 'Provisional', 'Expired', 'Not Applicable'],
    ];

    /** @return array<int, array{key: string, label: string, short: string}> */
    public static function roles(): array
    {
        return [
            ['key' => 'admin', 'label' => 'Admin / In-charge', 'short' => 'Admin'],
            ['key' => 'faculty', 'label' => 'Faculty Member', 'short' => 'Faculty'],
            ['key' => 'viewer', 'label' => 'General Viewer', 'short' => 'Viewer'],
        ];
    }

    public static function roleLabel(?string $role): string
    {
        foreach (self::roles() as $item) {
            if ($item['key'] === $role) {
                return $item['label'];
            }
        }

        return 'General Viewer';
    }

    /** @return array<string, array<int, string>> */
    public static function allOptions(): array
    {
        return self::OPTIONS;
    }
}
