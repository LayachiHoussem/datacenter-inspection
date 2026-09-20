<?php
/**
 * Datacenter Inspection System - Validation Helper
 */

if (!function_exists('validate')) {
    function validate(array $data, array $rules): array {
        $errors = [];
        foreach ($rules as $field => $ruleString) {
            $ruleList = explode('|', $ruleString);
            $value = trim($data[$field] ?? '');

            foreach ($ruleList as $rule) {
                if ($rule === 'required' && empty($value) && $value !== '0') {
                    $errors[$field][] = ucfirst($field) . ' is required.';
                }
                if ($rule === 'email' && !empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $errors[$field][] = ucfirst($field) . ' must be a valid email address.';
                }
                if (str_starts_with($rule, 'min:')) {
                    $min = (int) str_replace('min:', '', $rule);
                    if (!empty($value) && strlen($value) < $min) {
                        $errors[$field][] = ucfirst($field) . " must be at least {$min} characters.";
                    }
                }
                if (str_starts_with($rule, 'max:')) {
                    $max = (int) str_replace('max:', '', $rule);
                    if (!empty($value) && strlen($value) > $max) {
                        $errors[$field][] = ucfirst($field) . " cannot exceed {$max} characters.";
                    }
                }
            }
        }
        return $errors;
    }
}
