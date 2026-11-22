<?php

declare(strict_types=1);

/**
 * Read-only profile rendering shared by the public viewer, staff workspace,
 * admin dashboard and print view. All values are escaped on output.
 */

if (!function_exists('render_avatar')) {
    /** @param array<string, mixed> $profile */
    function render_avatar(array $profile, string $size = 'avatar--xl'): string
    {
        $photo = safe_asset_url($profile['photo'] ?? '', '');
        if ($photo !== '') {
            return '<span class="avatar ' . e($size) . '"><img src="' . e($photo) . '" alt="" loading="lazy"></span>';
        }

        [$from, $to] = avatar_gradient((string) ($profile['name'] ?? 'Faculty Member'));

        return '<span class="avatar ' . e($size) . '" style="--av-a: ' . e($from) . '; --av-b: ' . e($to) . '" aria-hidden="true">'
            . e(initials_of((string) ($profile['name'] ?? 'Faculty Member'))) . '</span>';
    }
}

if (!function_exists('research_profiles')) {
    /**
     * Research profile services a faculty member has actually filled in, in
     * display order. Anything without a usable link is dropped, so the hero
     * card only shows icons that lead somewhere. A service that has only an
     * identifier still gets a link, built from its canonical profile URL.
     *
     * Each entry carries the official brand mark: a 24x24 SVG path and the
     * brand colour used to tint it.
     *
     * @param array<string, mixed> $profile
     * @return array<int, array{label: string, url: string, brand: string, path: string}>
     */
    function research_profiles(array $profile): array
    {
        $services = [
            [
                'label' => 'Google Scholar',
                'id' => 'googleScholarId',
                'url' => 'googleScholarUrl',
                'pattern' => 'https://scholar.google.com/citations?user=%s',
                'brand' => '#4285F4',
                'path' => 'M5.242 13.769L0 9.5 12 0l12 9.5-5.242 4.269C17.548 11.249 14.978 9.5 12 9.5c-2.977 0-5.548 1.748-6.758 4.269zM12 10a7 7 0 1 0 0 14 7 7 0 0 0 0-14z',
            ],
            [
                'label' => 'ORCID',
                'id' => 'orcidId',
                'url' => 'orcidUrl',
                'pattern' => 'https://orcid.org/%s',
                'brand' => '#A6CE39',
                'path' => 'M12 0C5.372 0 0 5.372 0 12s5.372 12 12 12 12-5.372 12-12S18.628 0 12 0zM7.369 4.378c.525 0 .947.431.947.947s-.422.947-.947.947a.95.95 0 0 1-.947-.947c0-.525.422-.947.947-.947zm-.722 3.038h1.444v10.041H6.647V7.416zm3.562 0h3.9c3.712 0 5.344 2.653 5.344 5.025 0 2.578-2.016 5.025-5.325 5.025h-3.919V7.416zm1.444 1.303v7.444h2.297c3.272 0 4.022-2.484 4.022-3.722 0-2.016-1.284-3.722-4.097-3.722h-2.222z',
            ],
            [
                'label' => 'Scopus',
                'id' => 'scopusId',
                'url' => 'scopusUrl',
                'pattern' => 'https://www.scopus.com/authid/detail.uri?authorId=%s',
                'brand' => '#E9711C',
                'path' => 'M24 19.059l-.14-1.777c-1.426.772-2.945 1.076-4.465 1.076-3.319 0-5.96-2.782-5.96-6.475 0-3.903 2.595-6.31 5.633-6.31 1.917 0 3.39.303 4.792 1.075L24 4.895c-1.286-.608-2.337-.889-4.698-.889-4.534 0-7.97 3.53-7.97 8.017 0 5.12 4.09 7.924 7.9 7.924 1.916 0 3.506-.257 4.768-.888zm-14.954-3.46c0-2.22-1.964-3.225-3.857-4.347C3.716 10.364 2.15 9.756 2.15 8.12c0-1.215.889-2.548 2.642-2.548 1.519 0 2.57.234 3.903 1.029l.117-1.847c-1.239-.514-2.127-.748-4.137-.748C1.8 4.006.047 5.876.047 8.26c0 2.384 2.103 3.413 4.02 4.581 1.426.865 2.922 1.45 2.922 2.992 0 1.496-1.333 2.571-2.922 2.571-1.566 0-2.594-.35-3.786-1.075L0 19.176c1.215.56 2.454.818 4.16.818 2.385 0 4.885-1.473 4.885-4.395z',
            ],
        ];

        $links = [];
        foreach ($services as $service) {
            $url = safe_external_url($profile[$service['url']] ?? '');
            if ($url === '') {
                $id = trim((string) ($profile[$service['id']] ?? ''));
                if ($id !== '') {
                    $url = safe_external_url(sprintf($service['pattern'], rawurlencode($id)));
                }
            }
            if ($url === '') {
                continue;
            }
            $links[] = [
                'label' => $service['label'],
                'url' => $url,
                'brand' => $service['brand'],
                'path' => $service['path'],
            ];
        }

        return $links;
    }
}

if (!function_exists('render_research_links')) {
    /** @param array<string, mixed> $profile */
    function render_research_links(array $profile): string
    {
        $links = research_profiles($profile);
        if ($links === []) {
            return '';
        }

        $html = '<div class="profile-hero__links" aria-label="Research profiles">';
        foreach ($links as $link) {
            $html .= '<a class="research-link" href="' . e($link['url']) . '" target="_blank" rel="noopener noreferrer"'
                . ' style="--brand: ' . e($link['brand']) . '"'
                . ' title="' . e($link['label']) . '" aria-label="' . e($link['label']) . ' profile">'
                . '<svg class="research-link__logo" viewBox="0 0 24 24" aria-hidden="true" focusable="false">'
                . '<path fill="currentColor" d="' . e($link['path']) . '"></path></svg></a>';
        }
        $html .= '</div>';

        return $html;
    }
}

if (!function_exists('render_profile_hero')) {
    /**
     * @param array<string, mixed> $profile
     * @param array<string, mixed> $completion
     */
    function render_profile_hero(array $profile, array $completion, string $actions = '', bool $showCompletion = true, bool $showCompletionRing = false): string
    {
        $name = (string) ($profile['name'] ?? 'Faculty member');
        $department = (string) ($profile['department'] ?? '');
        $percent = (int) ($completion['percent'] ?? 0);
        $badge = (array) ($completion['badge'] ?? []);
        $badgeClass = profile_badge_class((string) ($badge['tone'] ?? 'start'));

        $html = '<article class="profile-hero" id="profile-overview">';
        $html .= '<div class="profile-hero__banner" aria-hidden="true"></div>';
        $html .= '<div class="profile-hero__body">';
        $html .= '<div class="profile-hero__avatar">' . render_avatar($profile, 'avatar--xl') . '</div>';
        $html .= '<div class="profile-hero__main">';
        $html .= '<div class="badge-row">';
        if (($profile['isDean'] ?? false) === true) {
            $html .= '<span class="badge"><i class="bi bi-award-fill" aria-hidden="true"></i> Dean</span>';
        }
        if ($showCompletion) {
            $html .= '<span class="' . e($badgeClass) . '"><i class="bi bi-check2-circle" aria-hidden="true"></i> ' . e((string) ($badge['label'] ?? 'Profile')) . ' · ' . $percent . '%</span>';
        }
        $html .= '</div>';
        $html .= '<div class="profile-hero__name-row">';
        $html .= '<h1>' . e($name) . '</h1>';
        $html .= '</div>';
        $html .= '<p class="profile-hero__sub">' . e(format_value($profile['designation'] ?? '', 'Faculty member'))
            . ($department !== '' ? ' <span>· ' . e($department) . '</span>' : '') . '</p>';

        $html .= '<div class="profile-hero__contacts">';
        $email = trim((string) ($profile['email'] ?? ''));
        if ($email !== '') {
            $html .= '<a href="mailto:' . e($email) . '"><i class="bi bi-envelope" aria-hidden="true"></i>' . e($email) . '</a>';
        }
        $contact = trim((string) ($profile['contact'] ?? ''));
        if ($contact !== '') {
            $digits = preg_replace('/[^\d+]/', '', $contact) ?? '';
            $html .= '<a href="tel:' . e($digits) . '"><i class="bi bi-telephone" aria-hidden="true"></i>' . e($contact) . '</a>';
        }
        if ($department !== '') {
            $html .= '<span><i class="bi bi-building" aria-hidden="true"></i>' . e($department) . '</span>';
        }
        $html .= '</div>';

        $html .= render_research_links($profile);

        $html .= '</div>';
        if ($actions !== '' || $showCompletionRing) {
            $actionsClass = 'profile-hero__actions' . ($showCompletionRing ? ' profile-hero__actions--with-ring' : '');
            $html .= '<div class="' . $actionsClass . '">';
            if ($showCompletionRing) {
                $ringOffset = number_format(2 * M_PI * 18 * (1 - $percent / 100), 2, '.', '');
                $html .= '<div class="profile-hero__ring" role="img" aria-label="Profile ' . $percent . '% complete">';
                $html .= '<svg viewBox="0 0 44 44" aria-hidden="true">';
                $html .= '<circle class="profile-hero__ring-track" cx="22" cy="22" r="18"></circle>';
                $html .= '<circle class="profile-hero__ring-fill" cx="22" cy="22" r="18" stroke-dasharray="113.1" stroke-dashoffset="' . e($ringOffset) . '"></circle>';
                $html .= '</svg>';
                $html .= '<span>' . $percent . '%</span>';
                $html .= '</div>';
            }
            $html .= $actions . '</div>';
        }
        $html .= '</div></article>';

        return $html;
    }
}

if (!function_exists('profile_section_open')) {
    function profile_section_open(string $id, string $icon, string $title, string $subtitle, int $count = 0): string
    {
        unset($count); // Item counts are intentionally not shown on the cards.

        $html = '<section class="card profile-section" id="' . e($id) . '">';
        $html .= '<header class="profile-section__head">';
        $html .= '<span class="icon-badge"><i class="bi bi-' . e($icon) . '" aria-hidden="true"></i></span>';
        $html .= '<div class="profile-section__title"><h2>' . e($title) . '</h2><p>' . e($subtitle) . '</p></div>';
        $html .= '</header>';

        return $html;
    }
}

if (!function_exists('render_empty_section')) {
    function render_empty_section(string $message, string $icon = 'inbox'): string
    {
        return '<div class="section-empty"><i class="bi bi-' . e($icon) . '" aria-hidden="true"></i><p>' . e($message) . '</p></div>';
    }
}

if (!function_exists('profile_info_item')) {
    function profile_info_item(string $label, string $valueHtml, bool $full = false): string
    {
        return '<div class="info-item' . ($full ? ' info-item--full' : '') . '">'
            . '<span class="info-item__label">' . e($label) . '</span>'
            . '<p class="info-item__value">' . $valueHtml . '</p></div>';
    }
}

if (!function_exists('profile_section_index')) {
    /**
     * Canonical outline of the profile sections, in display order. Kept in sync
     * with render_profile_sections() so the sidebar can link to each section.
     *
     * @return array<int, array{id: string, label: string, icon: string}>
     */
    function profile_section_index(): array
    {
        return [
            ['id' => 'personal', 'label' => 'Personal information', 'icon' => 'person-vcard'],
            ['id' => 'specializations', 'label' => 'Areas of specialization', 'icon' => 'stars'],
            ['id' => 'qualification', 'label' => 'Qualification', 'icon' => 'mortarboard'],
            ['id' => 'experience', 'label' => 'Professional experience', 'icon' => 'briefcase'],
            ['id' => 'publications', 'label' => 'Publications', 'icon' => 'journal-richtext'],
            ['id' => 'conferences-attended', 'label' => 'Conferences attended', 'icon' => 'easel2'],
            ['id' => 'conferences-conducted', 'label' => 'Conferences conducted', 'icon' => 'mic'],
            ['id' => 'training-programs-conducted', 'label' => 'Training programs', 'icon' => 'clipboard2-check'],
            ['id' => 'patents', 'label' => 'Patents', 'icon' => 'award'],
            ['id' => 'projects', 'label' => 'Funded projects', 'icon' => 'cash-stack'],
            ['id' => 'research', 'label' => 'Research', 'icon' => 'lightbulb'],
            ['id' => 'achievements', 'label' => 'Achievements', 'icon' => 'trophy'],
            ['id' => 'certifications', 'label' => 'Certifications', 'icon' => 'patch-check'],
            ['id' => 'responsibilities', 'label' => 'Responsibilities', 'icon' => 'diagram-3'],
        ];
    }
}

if (!function_exists('render_profile_sections')) {
    /** @param array<string, mixed> $profile */
    function render_profile_sections(array $profile): string
    {
        $html = '';

        // 1. Personal information
        $html .= profile_section_open('personal', 'person-vcard', 'Personal information', 'Directory identity and contact details');
        $html .= '<div class="info-grid">';
        $html .= profile_info_item('Full name', e((string) ($profile['name'] ?? '')));
        $html .= profile_info_item('Employee / faculty ID', e(format_value($profile['facultyId'] ?? '')));
        $html .= profile_info_item('Department', e(format_value($profile['department'] ?? '')));
        $html .= profile_info_item('Designation', e(format_value($profile['designation'] ?? '')));
        $email = trim((string) ($profile['email'] ?? ''));
        $html .= profile_info_item('Email', $email !== '' ? '<a href="mailto:' . e($email) . '">' . e($email) . '</a>' : '—');
        $html .= profile_info_item('Contact', e(format_value($profile['contact'] ?? '')));
        $html .= '</div></section>';

        // 2. Areas of specialization
        $tags = split_tags($profile['specialization'] ?? '');
        $html .= profile_section_open('specializations', 'stars', 'Areas of specialization', 'Research and teaching expertise', count($tags));
        if ($tags === []) {
            $html .= render_empty_section('No areas of specialization have been added yet.', 'stars');
        } else {
            $html .= '<div class="specialization-grid">';
            foreach ($tags as $tag) {
                $html .= '<div class="specialization-item">';
                $html .= '<span class="specialization-item__icon"><i class="bi bi-' . e(specialization_icon($tag)) . '" aria-hidden="true"></i></span>';
                $html .= '<span class="specialization-item__label">' . e($tag) . '</span>';
                $html .= '</div>';
            }
            $html .= '</div>';
        }
        $html .= '</section>';

        // 3. Qualification
        $items = is_array($profile['qualifications'] ?? null) ? $profile['qualifications'] : [];
        $html .= profile_section_open('qualification', 'mortarboard', 'Qualification', 'Academic degrees and institutions', count($items));
        if ($items === []) {
            $html .= render_empty_section('No qualifications have been added yet.', 'mortarboard');
        } else {
            $html .= '<div class="timeline">';
            foreach ($items as $item) {
                $year = trim((string) ($item['year'] ?? ''));
                $html .= '<div class="timeline__item"><span class="timeline__dot" aria-hidden="true"></span>';
                $html .= '<div class="timeline__top"><h3>' . e((string) ($item['degree'] ?? 'Degree')) . '</h3>';
                if ($year !== '') {
                    $html .= '<span class="timeline__when">' . e($year) . '</span>';
                }
                $html .= '</div>';
                $html .= '<p class="timeline__sub">' . e(format_value($item['institution'] ?? '')) . '</p>';
                $meta = array_filter([
                    trim((string) ($item['university'] ?? '')),
                    trim((string) ($item['classObtained'] ?? '')),
                    trim((string) ($item['specialization'] ?? '')),
                ]);
                if ($meta !== []) {
                    $html .= '<p class="timeline__meta">' . e(implode(' · ', $meta)) . '</p>';
                }
                $html .= '</div>';
            }
            $html .= '</div>';
        }
        $html .= '</section>';

        // 4. Professional experience
        $items = is_array($profile['experience'] ?? null) ? $profile['experience'] : [];
        $html .= profile_section_open('experience', 'briefcase', 'Professional experience', 'Teaching and research positions', count($items));
        if ($items === []) {
            $html .= render_empty_section('No professional experience has been added yet.', 'briefcase');
        } else {
            $html .= '<div class="timeline">';
            foreach ($items as $item) {
                $html .= '<div class="timeline__item"><span class="timeline__dot" aria-hidden="true"></span>';
                $html .= '<div class="timeline__top"><h3>' . e(format_value($item['designation'] ?? '', 'Position')) . '</h3>';
                $span = date_span($item['from'] ?? '', $item['to'] ?? '', 'Present');
                if ($span !== '') {
                    $html .= '<span class="timeline__when">' . e($span) . '</span>';
                }
                $html .= '</div>';
                $html .= '<p class="timeline__sub">' . e(format_value($item['institution'] ?? '')) . '</p>';
                if (trim((string) ($item['total'] ?? '')) !== '') {
                    $html .= '<p class="timeline__meta">Total experience: ' . e((string) $item['total']) . '</p>';
                }
                $html .= '</div>';
            }
            $html .= '</div>';
        }
        $html .= '</section>';

        // 5. Publications
        $items = is_array($profile['publications'] ?? null) ? $profile['publications'] : [];
        $html .= profile_section_open('publications', 'journal-richtext', 'Publications', 'Journal articles, conference papers and books', count($items));
        if ($items === []) {
            $html .= render_empty_section('No publications have been added yet.', 'journal-richtext');
        } else {
            $html .= '<div class="publication-list">';
            foreach ($items as $item) {
                $year = trim((string) ($item['year'] ?? ''));
                $html .= '<article class="publication-item">';
                $html .= '<span class="pub-year">' . e($year !== '' ? $year : '—') . '</span>';
                $html .= '<div class="pub-body">';
                $html .= '<div class="badge-row"><span class="badge badge--muted">' . e(format_value($item['type'] ?? '', 'Publication')) . '</span>';
                if (trim((string) ($item['indexing'] ?? '')) !== '') {
                    $html .= '<span class="badge">' . e((string) $item['indexing']) . '</span>';
                }
                $html .= '</div>';
                $html .= '<h3>' . e(format_value($item['title'] ?? '', 'Untitled publication')) . '</h3>';
                $html .= '<p class="pub-venue">' . e(format_value($item['venue'] ?? '')) . '</p>';
                if (trim((string) ($item['authors'] ?? '')) !== '') {
                    $html .= '<p class="pub-authors">' . e((string) $item['authors']) . '</p>';
                }
                $details = array_filter([
                    trim((string) ($item['volume'] ?? '')) !== '' ? 'Vol. ' . trim((string) $item['volume']) : '',
                    trim((string) ($item['issue'] ?? '')) !== '' ? 'Issue ' . trim((string) $item['issue']) : '',
                    trim((string) ($item['pages'] ?? '')) !== '' ? 'pp. ' . trim((string) $item['pages']) : '',
                    trim((string) ($item['publisher'] ?? '')),
                ]);
                if ($details !== []) {
                    $html .= '<p class="timeline__meta">' . e(implode(' · ', $details)) . '</p>';
                }
                $links = '';
                $doiUrl = safe_external_url($item['doi'] ?? '');
                if ($doiUrl !== '') {
                    $links .= '<a class="pub-link" href="' . e($doiUrl) . '" target="_blank" rel="noopener noreferrer"><i class="bi bi-link-45deg" aria-hidden="true"></i>DOI</a>';
                }
                $url = safe_external_url($item['url'] ?? '');
                if ($url !== '') {
                    $links .= '<a class="pub-link" href="' . e($url) . '" target="_blank" rel="noopener noreferrer"><i class="bi bi-box-arrow-up-right" aria-hidden="true"></i>View publication</a>';
                }
                if ($links !== '') {
                    $html .= '<div class="pub-links">' . $links . '</div>';
                }
                $html .= '</div></article>';
            }
            $html .= '</div>';
        }
        $html .= '</section>';

        // 6. Conferences attended
        $items = is_array($profile['conferencesAttended'] ?? null) ? $profile['conferencesAttended'] : [];
        $html .= profile_section_open('conferences-attended', 'easel2', 'Conferences attended', 'Conferences, workshops, symposia and seminars', count($items));
        if ($items === []) {
            $html .= render_empty_section('No conferences have been recorded yet.', 'easel2');
        } else {
            $html .= '<div class="timeline">';
            foreach ($items as $item) {
                $html .= '<div class="timeline__item"><span class="timeline__dot" aria-hidden="true"></span>';
                $html .= '<div class="timeline__top"><h3>' . e(format_value($item['name'] ?? '', 'Conference')) . '</h3>';
                $span = date_span($item['start'] ?? '', $item['end'] ?? '', '');
                if ($span !== '') {
                    $html .= '<span class="timeline__when">' . e(trim($span, ' –')) . '</span>';
                }
                $html .= '</div>';
                $sub = array_filter([
                    trim((string) ($item['type'] ?? '')),
                    trim((string) ($item['category'] ?? '')),
                    trim((string) ($item['participation'] ?? '')),
                ]);
                if ($sub !== []) {
                    $html .= '<p class="timeline__sub">' . e(implode(' · ', $sub)) . '</p>';
                }
                $meta = array_filter([
                    trim((string) ($item['organizer'] ?? '')),
                    trim((string) ($item['location'] ?? '')),
                ]);
                if ($meta !== []) {
                    $html .= '<p class="timeline__meta">' . e(implode(' · ', $meta)) . '</p>';
                }
                if (trim((string) ($item['paperTitle'] ?? '')) !== '') {
                    $html .= '<p class="timeline__desc">Paper: ' . e((string) $item['paperTitle']) . '</p>';
                }
                $certificate = attachment_url($item['certificate'] ?? '');
                if ($certificate !== '') {
                    $html .= '<p class="timeline__meta"><a class="pub-link" href="' . e($certificate) . '" target="_blank" rel="noopener noreferrer"><i class="bi bi-paperclip" aria-hidden="true"></i>Certificate</a></p>';
                }
                $html .= '</div>';
            }
            $html .= '</div>';
        }
        $html .= '</section>';

        // 7. Conferences conducted
        $items = is_array($profile['conferencesConducted'] ?? null) ? $profile['conferencesConducted'] : [];
        $html .= profile_section_open('conferences-conducted', 'mic', 'Conferences conducted', 'Events organised and delivered', count($items));
        if ($items === []) {
            $html .= render_empty_section('No conducted conferences have been recorded yet.', 'mic');
        } else {
            $html .= '<div class="timeline">';
            foreach ($items as $item) {
                $html .= '<div class="timeline__item"><span class="timeline__dot" aria-hidden="true"></span>';
                $html .= '<div class="timeline__top"><h3>' . e(format_value($item['name'] ?? '', 'Conference')) . '</h3>';
                $span = date_span($item['start'] ?? '', $item['end'] ?? '', '');
                if ($span !== '') {
                    $html .= '<span class="timeline__when">' . e(trim($span, ' –')) . '</span>';
                }
                $html .= '</div>';
                $sub = array_filter([
                    trim((string) ($item['type'] ?? '')),
                    trim((string) ($item['category'] ?? '')),
                    trim((string) ($item['role'] ?? '')),
                ]);
                if ($sub !== []) {
                    $html .= '<p class="timeline__sub">' . e(implode(' · ', $sub)) . '</p>';
                }
                $meta = array_filter([
                    trim((string) ($item['organizer'] ?? '')),
                    trim((string) ($item['location'] ?? '')),
                    (int) ($item['participants'] ?? 0) > 0 ? ((int) $item['participants']) . ' participants' : '',
                ]);
                if ($meta !== []) {
                    $html .= '<p class="timeline__meta">' . e(implode(' · ', $meta)) . '</p>';
                }
                $website = safe_external_url($item['website'] ?? '');
                if ($website !== '') {
                    $html .= '<p class="timeline__meta"><a class="pub-link" href="' . e($website) . '" target="_blank" rel="noopener noreferrer"><i class="bi bi-globe" aria-hidden="true"></i>Event website</a></p>';
                }
                $html .= '</div>';
            }
            $html .= '</div>';
        }
        $html .= '</section>';

        // 8. Training programs
        $items = is_array($profile['trainingPrograms'] ?? null) ? $profile['trainingPrograms'] : [];
        $html .= profile_section_open('training-programs-conducted', 'clipboard2-check', 'Training programs conducted', 'FDPs, workshops, STTPs and training programmes', count($items));
        if ($items === []) {
            $html .= render_empty_section('No training programmes have been recorded yet.', 'clipboard2-check');
        } else {
            $html .= '<div class="timeline">';
            foreach ($items as $item) {
                $html .= '<div class="timeline__item"><span class="timeline__dot" aria-hidden="true"></span>';
                $html .= '<div class="timeline__top"><h3>' . e(format_value($item['title'] ?? '', 'Training programme')) . '</h3>';
                $span = date_span($item['start'] ?? '', $item['end'] ?? '', '');
                if ($span !== '') {
                    $html .= '<span class="timeline__when">' . e(trim($span, ' –')) . '</span>';
                }
                $html .= '</div>';
                $sub = array_filter([
                    trim((string) ($item['type'] ?? '')),
                    trim((string) ($item['level'] ?? '')),
                    trim((string) ($item['role'] ?? '')),
                ]);
                if ($sub !== []) {
                    $html .= '<p class="timeline__sub">' . e(implode(' · ', $sub)) . '</p>';
                }
                $meta = array_filter([
                    trim((string) ($item['organizer'] ?? '')),
                    (int) ($item['participants'] ?? 0) > 0 ? ((int) $item['participants']) . ' participants' : '',
                ]);
                if ($meta !== []) {
                    $html .= '<p class="timeline__meta">' . e(implode(' · ', $meta)) . '</p>';
                }
                $certificate = attachment_url($item['certificate'] ?? '');
                if ($certificate !== '') {
                    $html .= '<p class="timeline__meta"><a class="pub-link" href="' . e($certificate) . '" target="_blank" rel="noopener noreferrer"><i class="bi bi-paperclip" aria-hidden="true"></i>Certificate</a></p>';
                }
                $html .= '</div>';
            }
            $html .= '</div>';
        }
        $html .= '</section>';

        // 9. Patents
        $items = is_array($profile['patents'] ?? null) ? $profile['patents'] : [];
        $html .= profile_section_open('patents', 'award', 'Patents', 'Filed, granted and awarded patents', count($items));
        if ($items === []) {
            $html .= render_empty_section('No patents have been recorded yet.', 'award');
        } else {
            $html .= '<div class="card-grid">';
            foreach ($items as $item) {
                $html .= '<article class="achievement-card">';
                $html .= '<span class="achievement-card__icon"><i class="bi bi-award" aria-hidden="true"></i></span>';
                $html .= '<div class="badge-row"><span class="status-pill status-pill--' . e(status_class($item['status'] ?? 'granted')) . '">' . e(format_value($item['status'] ?? '', 'Patent')) . '</span>';
                if (trim((string) ($item['country'] ?? '')) !== '') {
                    $html .= '<span class="badge badge--muted">' . e((string) $item['country']) . '</span>';
                }
                $html .= '</div>';
                $html .= '<h3>' . e(format_value($item['title'] ?? '', 'Patent')) . '</h3>';
                $html .= '<p class="achievement-card__org">' . e(format_value($item['number'] ?? '', 'Number pending')) . '</p>';
                if (trim((string) ($item['inventors'] ?? '')) !== '') {
                    $html .= '<p>' . e((string) $item['inventors']) . '</p>';
                }
                $meta = array_filter([
                    trim((string) ($item['authority'] ?? '')),
                    trim((string) ($item['grantDate'] ?? '')) !== '' ? format_date($item['grantDate']) : '',
                ]);
                if ($meta !== []) {
                    $html .= '<p class="timeline__meta">' . e(implode(' · ', $meta)) . '</p>';
                }
                $url = safe_external_url($item['url'] ?? '');
                if ($url !== '') {
                    $html .= '<p class="timeline__meta"><a class="pub-link" href="' . e($url) . '" target="_blank" rel="noopener noreferrer"><i class="bi bi-box-arrow-up-right" aria-hidden="true"></i>Patent record</a></p>';
                }
                $html .= '</article>';
            }
            $html .= '</div>';
        }
        $html .= '</section>';

        // 10. Funded projects
        $items = is_array($profile['projects'] ?? null) ? $profile['projects'] : [];
        $html .= profile_section_open('projects', 'cash-stack', 'Funded projects', 'Sponsored research, consultancy and grants', count($items));
        if ($items === []) {
            $html .= render_empty_section('No funded projects have been recorded yet.', 'cash-stack');
        } else {
            $html .= '<div class="project-grid">';
            foreach ($items as $item) {
                $html .= '<article class="project-card">';
                $html .= '<div class="project-card__top"><h3>' . e(format_value($item['title'] ?? '', 'Project')) . '</h3>';
                if (trim((string) ($item['status'] ?? '')) !== '') {
                    $html .= '<span class="status-pill status-pill--' . e(status_class($item['status'])) . '">' . e((string) $item['status']) . '</span>';
                }
                $html .= '</div>';
                $html .= '<p class="project-agency">' . e(format_value($item['agency'] ?? '')) . '</p>';
                $meta = array_filter([
                    trim((string) ($item['category'] ?? '')),
                    trim((string) ($item['role'] ?? '')),
                    date_span($item['start'] ?? '', $item['end'] ?? '', ''),
                ]);
                if ($meta !== []) {
                    $html .= '<div class="project-meta">';
                    foreach ($meta as $piece) {
                        $html .= '<span>' . e($piece) . '</span>';
                    }
                    $html .= '</div>';
                }
                if (trim((string) ($item['pi'] ?? '')) !== '') {
                    $html .= '<p class="project-desc">PI: ' . e((string) $item['pi']) . (trim((string) ($item['coPi'] ?? '')) !== '' ? ' · Co-PI: ' . e((string) $item['coPi']) : '') . '</p>';
                }
                $amount = format_amount($item['amount'] ?? '');
                if ($amount !== '') {
                    $html .= '<div class="project-amount">' . e($amount) . '</div>';
                }
                if (trim((string) ($item['description'] ?? '')) !== '') {
                    $html .= '<p class="project-desc">' . e((string) $item['description']) . '</p>';
                }
                if (trim((string) ($item['reference'] ?? '')) !== '') {
                    $html .= '<p class="project-ref">Ref: ' . e((string) $item['reference']) . '</p>';
                }
                $letter = attachment_url($item['sanctionLetter'] ?? '');
                if ($letter !== '') {
                    $html .= '<p class="timeline__meta"><a class="pub-link" href="' . e($letter) . '" target="_blank" rel="noopener noreferrer"><i class="bi bi-paperclip" aria-hidden="true"></i>Sanction letter</a></p>';
                }
                $html .= '</article>';
            }
            $html .= '</div>';
        }
        $html .= '</section>';

        // 11. Research
        $researchAreas = is_array($profile['researchAreas'] ?? null) ? $profile['researchAreas'] : [];
        $hasGuide = trim((string) ($profile['researchGuideNumber'] ?? '')) !== ''
            || trim((string) ($profile['researchGuideUniversity'] ?? '')) !== ''
            || trim((string) ($profile['researchGuideStatus'] ?? '')) !== '';
        $counts = [
            'Ph.D. completed' => (int) ($profile['phdCompleted'] ?? 0),
            'M.Phil. completed' => (int) ($profile['mphilCompleted'] ?? 0),
            'Other completed' => (int) ($profile['otherCompleted'] ?? 0),
            'Ph.D. guiding' => (int) ($profile['phdGuiding'] ?? 0),
            'M.Phil. guiding' => (int) ($profile['mphilGuiding'] ?? 0),
            'Other guiding' => (int) ($profile['otherGuiding'] ?? 0),
        ];
        $totalCounts = array_sum($counts);
        $html .= profile_section_open('research', 'lightbulb', 'Research', 'Recognition, scholars and research areas', count($researchAreas));
        if ($hasGuide || $totalCounts > 0) {
            if ($hasGuide) {
                $html .= '<div class="info-grid">';
                $html .= profile_info_item('Guide recognition number', e(format_value($profile['researchGuideNumber'] ?? '')));
                $html .= profile_info_item('Recognising university', e(format_value($profile['researchGuideUniversity'] ?? '')));
                $html .= profile_info_item('Recognition date', e(format_value(format_date($profile['researchGuideDate'] ?? ''))));
                $html .= profile_info_item('Recognition status', e(format_value($profile['researchGuideStatus'] ?? '')));
                $html .= '</div>';
            }
            if ($totalCounts > 0) {
                $html .= '<div class="mini-stats">';
                foreach ($counts as $label => $count) {
                    if ($count > 0) {
                        $html .= '<div class="mini-stat"><strong>' . $count . '</strong><span>' . e($label) . '</span></div>';
                    }
                }
                $html .= '</div>';
            }
        }
        if ($researchAreas !== []) {
            $html .= '<div class="research-id-grid">';
            foreach ($researchAreas as $area) {
                $html .= '<article class="research-id-card">';
                $html .= '<h3>' . e(format_value($area['area'] ?? '', 'Research area')) . '</h3>';
                $meta = array_filter([
                    trim((string) ($area['subject'] ?? '')),
                    trim((string) ($area['specialization'] ?? '')),
                ]);
                if ($meta !== []) {
                    $html .= '<p class="timeline__meta">' . e(implode(' · ', $meta)) . '</p>';
                }
                if (trim((string) ($area['keywords'] ?? '')) !== '') {
                    $html .= '<div class="tag-cloud">';
                    foreach (split_tags($area['keywords']) as $keyword) {
                        $html .= '<span class="tag tag--keyword">' . e($keyword) . '</span>';
                    }
                    $html .= '</div>';
                }
                $html .= '</article>';
            }
            $html .= '</div>';
        } elseif (!$hasGuide && $totalCounts === 0) {
            $html .= render_empty_section('No research details have been added yet.', 'lightbulb');
        }
        $html .= '</section>';

        // 12. Achievements
        $items = is_array($profile['achievements'] ?? null) ? $profile['achievements'] : [];
        $html .= profile_section_open('achievements', 'trophy', 'Achievements', 'Awards, honours and notable achievements', count($items));
        if ($items === []) {
            $html .= render_empty_section('No achievements have been recorded yet.', 'trophy');
        } else {
            $html .= '<div class="card-grid">';
            foreach ($items as $item) {
                $html .= '<article class="achievement-card">';
                $html .= '<span class="achievement-card__icon"><i class="bi bi-trophy" aria-hidden="true"></i></span>';
                $html .= '<h3>' . e(format_value($item['title'] ?? '', 'Achievement')) . '</h3>';
                $html .= '<p class="achievement-card__org">' . e(format_value($item['organization'] ?? '')) . '</p>';
                $meta = array_filter([
                    trim((string) ($item['year'] ?? '')),
                    trim((string) ($item['category'] ?? '')),
                ]);
                if ($meta !== []) {
                    $html .= '<span class="achievement-card__year">' . e(implode(' · ', $meta)) . '</span>';
                }
                if (trim((string) ($item['description'] ?? '')) !== '') {
                    $html .= '<p>' . e((string) $item['description']) . '</p>';
                }
                $link = attachment_url($item['certificate'] ?? '') ?: safe_external_url($item['certificateUrl'] ?? '');
                if ($link !== '') {
                    $html .= '<p class="timeline__meta"><a class="pub-link" href="' . e($link) . '" target="_blank" rel="noopener noreferrer"><i class="bi bi-paperclip" aria-hidden="true"></i>Certificate</a></p>';
                }
                $html .= '</article>';
            }
            $html .= '</div>';
        }
        $html .= '</section>';

        // 13. Certifications
        $items = is_array($profile['certifications'] ?? null) ? $profile['certifications'] : [];
        $html .= profile_section_open('certifications', 'patch-check', 'Certifications', 'Professional certifications and completed training', count($items));
        if ($items === []) {
            $html .= render_empty_section('No certifications have been recorded yet.', 'patch-check');
        } else {
            $html .= '<div class="card-grid">';
            foreach ($items as $item) {
                $html .= '<article class="achievement-card">';
                $html .= '<span class="achievement-card__icon achievement-card__icon--cert"><i class="bi bi-patch-check" aria-hidden="true"></i></span>';
                $html .= '<h3>' . e(format_value($item['title'] ?? '', 'Certification')) . '</h3>';
                $html .= '<p class="achievement-card__org">' . e(format_value($item['organization'] ?? '')) . '</p>';
                $meta = array_filter([
                    trim((string) ($item['year'] ?? '')),
                    trim((string) ($item['category'] ?? '')),
                ]);
                if ($meta !== []) {
                    $html .= '<span class="achievement-card__year">' . e(implode(' · ', $meta)) . '</span>';
                }
                if (trim((string) ($item['description'] ?? '')) !== '') {
                    $html .= '<p>' . e((string) $item['description']) . '</p>';
                }
                $link = attachment_url($item['certificate'] ?? '') ?: safe_external_url($item['certificateUrl'] ?? '');
                if ($link !== '') {
                    $html .= '<p class="timeline__meta"><a class="pub-link" href="' . e($link) . '" target="_blank" rel="noopener noreferrer"><i class="bi bi-paperclip" aria-hidden="true"></i>Certificate</a></p>';
                }
                $html .= '</article>';
            }
            $html .= '</div>';
        }
        $html .= '</section>';

        // 14. Additional responsibilities
        $items = is_array($profile['responsibilities'] ?? null) ? $profile['responsibilities'] : [];
        $html .= profile_section_open('responsibilities', 'diagram-3', 'Additional responsibilities', 'Academic, administrative and institutional roles', count($items));
        if ($items === []) {
            $html .= render_empty_section('No additional responsibilities have been recorded yet.', 'diagram-3');
        } else {
            $html .= '<div class="timeline">';
            foreach ($items as $item) {
                $html .= '<div class="timeline__item"><span class="timeline__dot" aria-hidden="true"></span>';
                $html .= '<div class="timeline__top"><h3>' . e(format_value($item['title'] ?? '', 'Responsibility')) . '</h3>';
                $span = !empty($item['current']) ? 'Current' : date_span($item['from'] ?? '', $item['to'] ?? '', '');
                if ($span !== '') {
                    $html .= '<span class="timeline__when">' . e($span) . '</span>';
                }
                $html .= '</div>';
                $sub = array_filter([
                    trim((string) ($item['position'] ?? '')),
                    trim((string) ($item['institution'] ?? '')),
                ]);
                if ($sub !== []) {
                    $html .= '<p class="timeline__sub">' . e(implode(' · ', $sub)) . '</p>';
                }
                $meta = array_filter([
                    trim((string) ($item['category'] ?? '')),
                    trim((string) ($item['level'] ?? '')),
                ]);
                if ($meta !== []) {
                    $html .= '<p class="timeline__meta">' . e(implode(' · ', $meta)) . '</p>';
                }
                $html .= '</div>';
            }
            $html .= '</div>';
        }
        $html .= '</section>';

        return $html;
    }
}
