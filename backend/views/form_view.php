<?php

declare(strict_types=1);

use App\Core\ProfileShape;

/**
 * Server-rendered profile editor shared by the faculty workspace and the
 * administrator dashboard.
 *
 * The form posts complete profiles (PUT semantics). Child collections are
 * rendered as repeatable blocks with a <template> used by portal.js. The
 * editor still works without JavaScript for the entries that already exist.
 */

if (!function_exists('form_input_key')) {
    function form_input_key(string $collection, string $index, string $field): string
    {
        return $collection . '[' . $index . '][' . $field . ']';
    }
}

if (!function_exists('map_validation_errors')) {
    /**
     * Convert validator keys such as "publications[0].title" to the bracket
     * names used by the form controls: "publications[0][title]".
     *
     * @param array<string, string> $errors
     * @return array<string, string>
     */
    function map_validation_errors(array $errors): array
    {
        $mapped = [];
        foreach ($errors as $key => $message) {
            $normalised = preg_replace('/^([A-Za-z][A-Za-z0-9_]*)\[(\d+)\]\.(.+)$/', '$1[$2][$3]', (string) $key);
            $mapped[$normalised !== null ? $normalised : (string) $key] = (string) $message;
        }

        return $mapped;
    }
}

if (!function_exists('render_collection_item')) {
    /**
     * @param array<string, mixed> $entry
     * @param array<string, mixed> $definition
     * @param array<string, string> $errors
     */
    function render_collection_item(string $collection, int $index, array $entry, array $definition, array $errors, bool $isTemplate = false): string
    {
        $indexText = $isTemplate ? '__INDEX__' : (string) $index;
        $label = (string) ($definition['title'] ?? ucfirst($collection));
        $titleField = (string) ($definition['titleField'] ?? '');
        $titleValue = $titleField !== '' ? form_field_value($entry[$titleField] ?? '') : '';
        $itemTitle = $titleValue !== '' ? $titleValue : $label . ' entry';

        $html = '<article class="collection-item" data-remove-message="Remove this entry? Its saved values are deleted when you save the profile.">';
        $html .= '<div class="collection-item__head">';
        $html .= '<span class="collection-item__title"><i class="bi bi-collection" aria-hidden="true"></i> <span data-item-title>' . e($itemTitle) . '</span> <span class="badge badge--muted" data-item-ordinal>' . e($label) . ' ' . ($isTemplate ? '__ORDINAL__' : (string) ($index + 1)) . '</span></span>';
        $html .= '<button class="collection-item__remove" type="button" data-remove-item><i class="bi bi-trash3" aria-hidden="true"></i> Remove</button>';
        $html .= '</div>';
        $html .= '<div class="form-grid">';

        $html .= '<input type="hidden" name="' . e(form_input_key($collection, $indexText, 'id')) . '" value="' . e((string) ($entry['id'] ?? '')) . '">';
        $html .= '<input type="hidden" name="' . e(form_input_key($collection, $indexText, 'sortOrder')) . '" value="' . e((string) ($entry['sortOrder'] ?? $index)) . '">';

        foreach (($definition['fields'] ?? []) as $field) {
            $fieldName = (string) ($field['key'] ?? '');
            if ($fieldName === '') {
                continue;
            }
            $inputName = form_input_key($collection, $indexText, $fieldName);
            $value = $entry[$fieldName] ?? (($field['type'] ?? 'text') === 'checkbox' ? false : '');
            $html .= render_field($inputName, $field, $value, $errors);
        }

        $html .= '</div></article>';

        return $html;
    }
}

if (!function_exists('render_root_group_fields')) {
    /**
     * Render the root-level fields of one field group inside an editor section.
     *
     * @param array<string, mixed> $group
     * @param array<string, mixed> $profile
     * @param array<string, string> $errors
     */
    function render_root_group_fields(array $group, array $profile, array $errors, bool $admin = false, bool $showHeading = false): string
    {
        $html = '';
        if ($showHeading) {
            $html .= '<h3 class="form-subheading">' . e((string) ($group['title'] ?? 'Details')) . '</h3>';
        }
        $html .= '<div class="form-grid">';
        foreach (($group['fields'] ?? []) as $field) {
            $key = (string) ($field['key'] ?? '');
            if ($key === '') {
                continue;
            }
            $html .= render_field($key, $field, $profile[$key] ?? '', $errors);
        }
        $html .= '</div>';

        if (($group['key'] ?? '') === 'identity' && $admin) {
            $html .= '<div class="field field--checkbox" style="margin-top:14px">';
            $html .= '<label class="check-field"><input type="checkbox" name="isDean" value="1"' . (($profile['isDean'] ?? false) === true ? ' checked' : '') . '> <span>Mark this profile as the Dean</span></label>';
            $html .= '<small class="field-hint">Only one profile can be the dean. The dean is excluded from directory statistics and cannot be deleted.</small>';
            $html .= '</div>';
        }

        return $html;
    }
}

if (!function_exists('render_upload_field')) {
    /**
     * @param array<string, mixed> $profile
     */
    function render_upload_field(
        string $name,
        string $label,
        string $help,
        string $currentUrl
    ): string {
        $rawValue = trim($currentUrl);
        $preview = safe_asset_url($rawValue, '');
        $id = 'upload-' . $name;
        $cropMode = 'photo';

        $html = '<div class="upload-field" data-upload-mode="' . e($cropMode) . '">';
        $html .= '<input type="hidden" name="' . e($name) . '" value="' . e($rawValue) . '">';
        $html .= '<span class="upload-field__preview">';
        if ($preview !== '') {
            $html .= '<img src="' . e($preview) . '" alt="" data-upload-preview="' . e($name) . '" data-original-src="' . e($preview) . '">';
        } else {
            $html .= '<i class="bi bi-person-bounding-box" aria-hidden="true" data-upload-empty="' . e($name) . '"></i>';
            $html .= '<img src="" alt="" hidden data-upload-preview="' . e($name) . '">';
        }
        $html .= '</span>';
        $html .= '<div class="upload-field__copy">';
        $html .= '<strong>' . e($label) . '</strong>';
        $html .= '<small>' . e($help) . '</small>';
        $html .= '<div class="upload-field__actions">';
        $html .= '<label class="btn btn-soft btn-sm" for="' . e($id) . '"><i class="bi bi-upload" aria-hidden="true"></i> Choose file</label>';
        $html .= '<input id="' . e($id) . '" class="sr-only" type="file" name="' . e($name) . '_file" accept="image/jpeg,image/png,image/webp" data-upload-file="' . e($name) . '" data-crop-mode="' . e($cropMode) . '" data-preview-image="[data-upload-preview=&quot;' . e($name) . '&quot;]" data-preview-empty="[data-upload-empty=&quot;' . e($name) . '&quot;]">';
        if ($preview !== '') {
            $html .= '<button type="button" class="btn btn-ghost btn-sm" data-adjust-upload="' . e($name) . '" data-adjust-src="' . e($preview) . '" data-crop-mode="' . e($cropMode) . '"><i class="bi bi-sliders" aria-hidden="true"></i> Adjust</button>';
            $html .= '<label class="check-field"><input type="checkbox" name="remove_' . e($name) . '" value="1" data-clear-upload="' . e($name) . '"> <span>Remove current ' . e(strtolower($label)) . '</span></label>';
        }
        $html .= '</div></div></div>';

        return $html;
    }
}

if (!function_exists('render_profile_form')) {
    /**
     * @param array<string, mixed> $profile
     * @param array<string, string> $errors
     * @param array<string, mixed> $config
     * @param array<string, mixed> $options
     */
    function render_profile_form(
        array $profile,
        array $errors,
        array $config,
        string $action,
        array $options = []
    ): string {
        $admin = (bool) ($options['admin'] ?? false);
        $cancelUrl = (string) ($options['cancelUrl'] ?? app_url('/'));
        $submitLabel = (string) ($options['submitLabel'] ?? 'Save profile');
        $previewUrl = (string) ($options['previewUrl'] ?? '');
        $csrfToken = (string) ($options['csrfToken'] ?? '');
        $errors = map_validation_errors($errors);
        $photo = (string) ($profile['photo'] ?? '');

        $html = '<form class="editor-shell" method="post" action="' . e($action) . '" enctype="multipart/form-data" data-guard-unsaved novalidate'
            . ' data-upload-url="' . e(app_url('/api/v1/uploads')) . '"'
            . ' data-profile-id="' . e((string) ($profile['id'] ?? '')) . '">';
        $html .= '<input type="hidden" name="_csrf" value="' . e($csrfToken) . '">';
        $html .= '<input type="hidden" name="displayOrder" value="' . e((string) ($profile['displayOrder'] ?? 0)) . '">';
        if (!$admin) {
            $html .= '<input type="hidden" name="isDean" value="' . (($profile['isDean'] ?? false) === true ? '1' : '') . '">';
        }

        $html .= '<div class="editor-intro">';
        $html .= '<div><h2>' . ($profile['id'] !== '' ? 'Edit profile' : 'Create profile') . '</h2>';
        $html .= '<p>Required fields are marked with an asterisk. Changes appear on the public profile as soon as you save.</p></div>';
        $html .= '<div class="editor-intro__actions">';
        $html .= '<a class="btn btn-ghost" href="' . e($cancelUrl) . '" data-cancel-guard="true"><i class="bi bi-arrow-left" aria-hidden="true"></i> Cancel</a>';
        if ($previewUrl !== '') {
            $html .= '<a class="btn btn-soft" href="' . e($previewUrl) . '" target="_blank" rel="noopener"><i class="bi bi-box-arrow-up-right" aria-hidden="true"></i> Preview</a>';
        }
        $html .= '</div></div>';

        if ($errors !== []) {
            $html .= render_error_summary($errors, 'The profile could not be saved. Please correct the highlighted fields.');
        }

        // Media is part of the first (Personal information) section so the
        // editor follows the same top-to-bottom order as the public profile.
        $mediaHtml = '<h3 class="form-subheading">Profile photo</h3>';
        $mediaHtml .= '<div class="form-grid">';
        $mediaHtml .= render_upload_field('photo', 'Profile photo', 'JPEG, PNG or WebP. Maximum 5 MB. Drag and zoom to set a square crop.', $photo);
        $mediaHtml .= '</div>';

        // Sections are ordered to match the public profile and its sidebar.
        $sections = $config['sections'] ?? [];
        if ($sections === []) {
            $sections = [];
            foreach (($config['rootGroups'] ?? []) as $groupKey => $group) {
                $sections[] = [
                    'id' => $groupKey,
                    'title' => $group['title'] ?? '',
                    'description' => $group['description'] ?? '',
                    'icon' => $group['icon'] ?? 'pencil-square',
                    'groups' => [$groupKey],
                ];
            }
            foreach (array_keys($config['collections'] ?? []) as $collectionKey) {
                $sections[] = [
                    'id' => $collectionKey,
                    'groups' => [],
                    'collections' => [$collectionKey],
                ];
            }
        }

        foreach ($sections as $section) {
            $sectionId = (string) ($section['id'] ?? '');
            $groups = array_values(array_filter((array) ($section['groups'] ?? []), 'is_string'));
            $collectionKeys = array_values(array_filter((array) ($section['collections'] ?? []), 'is_string'));

            $itemCount = 0;
            foreach ($collectionKeys as $collectionKey) {
                $candidate = $profile[$collectionKey] ?? null;
                $itemCount += is_array($candidate) ? count($candidate) : 0;
            }

            $html .= '<section class="card form-section" id="section-' . e($sectionId) . '">';
            $html .= '<header class="form-section__head"><span class="icon-badge"><i class="bi bi-' . e((string) ($section['icon'] ?? 'pencil-square')) . '" aria-hidden="true"></i></span><div><h2>' . e((string) ($section['title'] ?? 'Details')) . '</h2><p>' . e((string) ($section['description'] ?? '')) . '</p></div>';
            if ($itemCount > 0) {
                $html .= '<span class="profile-section__count">' . $itemCount . ' item' . ($itemCount === 1 ? '' : 's') . '</span>';
            }
            $html .= '</header>';

            if ($sectionId === 'personal') {
                $html .= $mediaHtml;
            }

            foreach ($groups as $groupKey) {
                $group = $config['rootGroups'][$groupKey] ?? null;
                if (!is_array($group)) {
                    continue;
                }
                $html .= render_root_group_fields($group, $profile, $errors, $admin, count($groups) > 1);
            }

            foreach ($collectionKeys as $collectionKey) {
                $definition = $config['collections'][$collectionKey] ?? null;
                if (!is_array($definition)) {
                    continue;
                }
                $items = is_array($profile[$collectionKey] ?? null) ? array_values($profile[$collectionKey]) : [];
                $title = (string) ($definition['title'] ?? ucfirst($collectionKey));
                $templateId = 'template-' . $collectionKey;

                $html .= '<div data-collection="' . e($collectionKey) . '" data-template="' . e($templateId) . '" data-item-label="' . e($title) . '">';
                $html .= '<div class="collection-list" data-collection-items>';
                foreach ($items as $index => $entry) {
                    $html .= render_collection_item($collectionKey, (int) $index, (array) $entry, $definition, $errors);
                }
                $html .= '</div>';
                $html .= '<div class="collection-empty" data-collection-empty' . ($items !== [] ? ' hidden' : '') . '><i class="bi bi-inbox" aria-hidden="true"></i> No ' . e(strtolower($title)) . ' have been added yet. Use the button below to add the first entry.</div>';
                $html .= '<div class="collection-add"><button class="btn btn-soft" type="button" data-add-collection><i class="bi bi-plus-lg" aria-hidden="true"></i> Add ' . e(strtolower($title)) . '</button></div>';
                $html .= '<template id="' . e($templateId) . '">' . render_collection_item($collectionKey, 0, [], $definition, $errors, true) . '</template>';
                $html .= '</div>';
            }

            $html .= '</section>';
        }

        $html .= '<div class="form-actions form-actions--sticky">';
        $html .= '<div class="form-actions__buttons">';
        $html .= '<a class="btn btn-ghost" href="' . e($cancelUrl) . '" data-cancel-guard="true">Cancel</a>';
        $html .= '<button class="btn btn-primary" type="submit"><i class="bi bi-check2-circle" aria-hidden="true"></i> ' . e($submitLabel) . '</button>';
        $html .= '</div></div>';

        $html .= '</form>';

        return $html;
    }
}

if (!function_exists('collect_profile_form_input')) {
    /**
     * Convert the posted editor form into the canonical profile input array.
     *
     * @param array<string, mixed> $source
     * @param array<string, mixed> $config
     * @param array<string, mixed> $existing
     * @return array<string, mixed>
     */
    function collect_profile_form_input(array $source, array $config, array $existing = [], bool $admin = false): array
    {
        $input = [];

        foreach (($config['rootGroups'] ?? []) as $group) {
            foreach (($group['fields'] ?? []) as $field) {
                $key = (string) ($field['key'] ?? '');
                if ($key === '') {
                    continue;
                }
                if (($field['type'] ?? 'text') === 'checkbox') {
                    $input[$key] = isset($source[$key]) ? '1' : '';
                } else {
                    $input[$key] = (string) ($source[$key] ?? '');
                }
            }
        }

        $input['photo'] = isset($source['remove_photo']) ? '' : (string) ($source['photo'] ?? ($existing['photo'] ?? ''));
        $input['banner'] = '';
        $input['displayOrder'] = (string) ($source['displayOrder'] ?? ($existing['displayOrder'] ?? '0'));

        if ($admin) {
            $input['isDean'] = isset($source['isDean']) ? '1' : '';
        } else {
            $input['isDean'] = ($existing['isDean'] ?? false) === true ? '1' : '';
        }

        foreach (array_keys(ProfileShape::COLLECTIONS) as $collectionKey) {
            $input[$collectionKey] = [];
            $posted = $source[$collectionKey] ?? null;
            if (!is_array($posted)) {
                continue;
            }

            $definition = (array) ($config['collections'][$collectionKey] ?? []);
            $fields = (array) ($definition['fields'] ?? []);
            foreach ($posted as $rawEntry) {
                if (!is_array($rawEntry)) {
                    continue;
                }
                $entry = [];
                foreach ($fields as $field) {
                    $fieldKey = (string) ($field['key'] ?? '');
                    if ($fieldKey === '') {
                        continue;
                    }
                    if (($field['type'] ?? 'text') === 'checkbox') {
                        $entry[$fieldKey] = isset($rawEntry[$fieldKey]) ? '1' : '';
                    } else {
                        $entry[$fieldKey] = (string) ($rawEntry[$fieldKey] ?? '');
                    }
                }
                if (trim((string) ($rawEntry['id'] ?? '')) !== '') {
                    $entry['id'] = (string) $rawEntry['id'];
                }
                if (isset($rawEntry['sortOrder']) && is_numeric($rawEntry['sortOrder'])) {
                    $entry['sortOrder'] = (int) $rawEntry['sortOrder'];
                }
                $input[$collectionKey][] = $entry;
            }
        }

        // Preserve the currently stored hidden values even when the browser
        // omits them because the form was partially submitted.
        if (!array_key_exists('photo', $source) && !isset($source['remove_photo'])) {
            $input['photo'] = (string) ($existing['photo'] ?? '');
        }

        return $input;
    }
}
