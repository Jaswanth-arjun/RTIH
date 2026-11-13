<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Small server-side validator for form input.
 *
 * Supported rules: required, nullable, min:n, max:n, email, in:a,b,c,
 * date, confirmed, regex:/pattern/.
 */
final class Validator
{
    /** @var array<string, string> */
    private array $errors = [];

    /** @var array<string, string> */
    private array $values = [];

    /**
     * @param array<string, mixed> $data
     * @param array<string, string> $rules
     * @param array<string, string> $labels
     */
    private function __construct(array $data, array $rules, array $labels)
    {
        foreach ($rules as $field => $ruleString) {
            $ruleset = explode('|', $ruleString);
            $raw = $data[$field] ?? '';
            $value = is_scalar($raw) ? trim((string) $raw) : '';
            $this->values[$field] = $value;
            $label = $labels[$field] ?? ucfirst(str_replace('_', ' ', $field));

            $isRequired = in_array('required', $ruleset, true);
            $isNullable = in_array('nullable', $ruleset, true);

            if ($value === '') {
                if ($isRequired) {
                    $this->errors[$field] = $label . ' is required.';
                } elseif ($isNullable) {
                    continue;
                }
            }

            if ($value === '') {
                continue;
            }

            foreach ($ruleset as $rule) {
                if ($rule === 'required' || $rule === 'nullable') {
                    continue;
                }

                [$name, $parameter] = array_pad(explode(':', $rule, 2), 2, '');

                switch ($name) {
                    case 'min':
                        if (mb_strlen($value) < (int) $parameter) {
                            $this->errors[$field] = sprintf('%s must be at least %d characters.', $label, (int) $parameter);
                        }
                        break;

                    case 'max':
                        if (mb_strlen($value) > (int) $parameter) {
                            $this->errors[$field] = sprintf('%s must be at most %d characters.', $label, (int) $parameter);
                        }
                        break;

                    case 'email':
                        if (filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
                            $this->errors[$field] = $label . ' must be a valid email address.';
                        }
                        break;

                    case 'in':
                        $allowed = explode(',', $parameter);
                        if (!in_array($value, $allowed, true)) {
                            $this->errors[$field] = $label . ' contains an invalid value.';
                        }
                        break;

                    case 'date':
                        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
                        if ($date === false || $date->format('Y-m-d') !== $value) {
                            $this->errors[$field] = $label . ' must be a valid date in YYYY-MM-DD format.';
                        }
                        break;

                    case 'confirmed':
                        $confirmation = $data[$field . '_confirmation'] ?? '';
                        if (!is_scalar($confirmation) || (string) $confirmation !== $value) {
                            $this->errors[$field] = $label . ' confirmation does not match.';
                        }
                        break;

                    case 'regex':
                        if (@preg_match($parameter, $value) !== 1) {
                            $this->errors[$field] = $label . ' has an invalid format.';
                        }
                        break;
                }
            }
        }
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, string> $rules
     * @param array<string, string> $labels
     */
    public static function make(array $data, array $rules, array $labels = []): self
    {
        return new self($data, $rules, $labels);
    }

    public function fails(): bool
    {
        return $this->errors !== [];
    }

    /**
     * @return array<string, string>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    public function value(string $field): string
    {
        return $this->values[$field] ?? '';
    }

    /**
     * @return array<string, string>
     */
    public function values(): array
    {
        return $this->values;
    }
}
