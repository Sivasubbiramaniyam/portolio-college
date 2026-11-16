<?php

declare(strict_types=1);

/**
 * View helpers shared by the server-rendered pages.
 */

if (!function_exists('form_field_id')) {
    function form_field_id(string $name): string
    {
        $id = preg_replace('/[^a-zA-Z0-9_-]+/', '_', $name) ?? $name;

        return 'field-' . trim($id, '_');
    }
}

if (!function_exists('form_field_value')) {
    function form_field_value(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? '1' : '';
        }
        if (is_array($value) || is_object($value)) {
            return '';
        }

        return trim((string) $value);
    }
}

if (!function_exists('render_field')) {
    /**
     * Render one labelled form control.
     *
     * @param array<string, mixed> $field
     * @param array<string, string> $errors
     */
    function render_field(string $name, array $field, mixed $value, array $errors = []): string
    {
        $type = (string) ($field['type'] ?? 'text');
        $id = form_field_id($name);
        $label = (string) ($field['label'] ?? $name);
        $required = (bool) ($field['required'] ?? false);
        $full = (bool) ($field['full'] ?? false);
        $error = $errors[$name] ?? '';
        $help = (string) ($field['help'] ?? '');
        $valueText = form_field_value($value);
        $classes = 'field' . ($full ? ' field--full' : '') . ($error !== '' ? ' is-invalid' : '');

        if ($type === 'checkbox') {
            $checked = is_bool($value) ? $value : is_truthy($valueText);
            $html = '<div class="' . $classes . ' field--checkbox">';
            $html .= '<label class="check-field" for="' . e($id) . '">';
            $html .= '<input type="checkbox" id="' . e($id) . '" name="' . e($name) . '" value="1"' . ($checked ? ' checked' : '') . '>';
            $html .= '<span>' . e($label) . '</span>';
            $html .= '</label>';
            if ($help !== '') {
                $html .= '<small class="field-hint">' . e($help) . '</small>';
            }
            if ($error !== '') {
                $html .= '<span class="field-error" id="' . e($id) . '-error">' . e($error) . '</span>';
            }
            $html .= '</div>';

            return $html;
        }

        $attributes = ' id="' . e($id) . '" name="' . e($name) . '"';
        if ($required) {
            $attributes .= ' required';
        }
        if (!empty($field['placeholder'])) {
            $attributes .= ' placeholder="' . e((string) $field['placeholder']) . '"';
        }
        if (!empty($field['maxlength'])) {
            $attributes .= ' maxlength="' . (int) $field['maxlength'] . '"';
        }
        if (array_key_exists('min', $field)) {
            $attributes .= ' min="' . e((string) $field['min']) . '"';
        }
        if (array_key_exists('max', $field)) {
            $attributes .= ' max="' . e((string) $field['max']) . '"';
        }
        if (!empty($field['step'])) {
            $attributes .= ' step="' . e((string) $field['step']) . '"';
        }
        if ($error !== '') {
            $attributes .= ' aria-invalid="true" aria-describedby="' . e($id) . '-error"';
        } elseif ($help !== '') {
            $attributes .= ' aria-describedby="' . e($id) . '-help"';
        }

        $html = '<div class="' . $classes . '">';
        $html .= '<label for="' . e($id) . '">' . e($label) . ($required ? ' <span class="req" aria-hidden="true">*</span>' : '') . '</label>';

        if ($type === 'select') {
            $options = is_array($field['options'] ?? null) ? $field['options'] : [];
            $html .= '<select' . $attributes . '>';
            $html .= '<option value="">Select ' . e(strtolower($label)) . '</option>';
            foreach ($options as $key => $optionLabel) {
                $optionValue = is_int($key) ? (string) $optionLabel : (string) $key;
                $optionText = is_int($key) ? (string) $optionLabel : (string) $optionLabel;
                $html .= '<option value="' . e($optionValue) . '"' . ($valueText === $optionValue ? ' selected' : '') . '>' . e($optionText) . '</option>';
            }
            $html .= '</select>';
        } elseif ($type === 'textarea') {
            $rows = (int) ($field['rows'] ?? 3);
            $html .= '<textarea' . $attributes . ' rows="' . $rows . '">' . e($valueText) . '</textarea>';
        } else {
            $inputType = in_array($type, ['text', 'email', 'tel', 'url', 'number', 'date', 'month', 'password'], true) ? $type : 'text';
            $html .= '<input type="' . $inputType . '"' . $attributes . ' value="' . e($valueText) . '">';
        }

        if ($help !== '') {
            $html .= '<small class="field-hint" id="' . e($id) . '-help">' . e($help) . '</small>';
        }
        if ($error !== '') {
            $html .= '<span class="field-error" id="' . e($id) . '-error">' . e($error) . '</span>';
        }
        $html .= '</div>';

        return $html;
    }
}

if (!function_exists('render_error_summary')) {
    /** @param array<string, string> $errors */
    function render_error_summary(array $errors, string $message = 'Please correct the highlighted fields.'): string
    {
        if ($errors === []) {
            return '';
        }

        $html = '<div class="portal-alert portal-alert--error" role="alert">';
        $html .= '<i class="bi bi-exclamation-octagon-fill" aria-hidden="true"></i>';
        $html .= '<div><strong>' . e($message) . '</strong><ul>';
        foreach ($errors as $field => $error) {
            $html .= '<li>' . e($error) . '</li>';
        }
        $html .= '</ul></div></div>';

        return $html;
    }
}

if (!function_exists('pull_flash')) {
    /** @return array<string, mixed> */
    function pull_flash(): array
    {
        $flash = \App\Core\Session::flashPull('flash', []);

        return is_array($flash) ? $flash : [];
    }
}

if (!function_exists('render_toast')) {
    function render_toast(string $message, string $tone = 'success'): string
    {
        if ($message === '') {
            return '';
        }

        $tone = in_array($tone, ['success', 'error', 'info'], true) ? $tone : 'success';
        $icon = match ($tone) {
            'error' => 'exclamation-octagon-fill',
            'info' => 'info-circle-fill',
            default => 'check2-circle',
        };
        $role = $tone === 'error' ? 'alert' : 'status';

        return '<div class="toast toast--' . $tone . ' show" role="' . $role . '" data-server-toast>'
            . '<i class="bi bi-' . $icon . '" aria-hidden="true"></i><span>' . e($message) . '</span></div>';
    }
}

if (!function_exists('render_flash')) {
    /** @param array<string, mixed> $flash */
    function render_flash(array $flash): string
    {
        $message = (string) ($flash['message'] ?? '');
        if ($message === '') {
            return '';
        }

        $type = (string) ($flash['type'] ?? 'success');
        $tone = $type === 'warning' ? 'info' : $type;

        return render_toast($message, $tone);
    }
}

if (!function_exists('active_nav')) {
    function active_nav(string $key, string $active): string
    {
        return $key === $active ? ' is-active' : '';
    }
}

if (!function_exists('department_short')) {
    function department_short(string $department): string
    {
        $map = [
            'Computer Science and Engineering' => 'CSE',
            'Information Technology' => 'IT',
            'Electronics and Communication Engineering' => 'ECE',
            'Electrical and Electronics Engineering' => 'EEE',
            'Mechanical Engineering' => 'MECH',
            'Civil Engineering' => 'CIVIL',
            'MBA' => 'MBA',
            'Science and Humanities' => 'S&H',
        ];
        if (isset($map[$department])) {
            return $map[$department];
        }

        $words = preg_split('/\s+/', trim($department)) ?: [];
        $initials = '';
        foreach ($words as $word) {
            if ($word !== '') {
                $initials .= strtoupper(substr($word, 0, 1));
            }
        }

        return $initials !== '' ? substr($initials, 0, 5) : 'UCEN';
    }
}

if (!function_exists('profile_badge_class')) {
    function profile_badge_class(string $tone): string
    {
        return match ($tone) {
            'complete' => 'badge badge--green',
            'strong' => 'badge',
            default => 'badge badge--muted',
        };
    }
}
