<?php
declare(strict_types=1);

namespace App\Validators;

use App\Helpers\Response;

class Validator {
    private array $errors = [];

    public function __construct(private array $data) {}

    public static function make(array $data): self {
        return new self($data);
    }

    public function required(array $fields): self {
        foreach ($fields as $field) {
            if (!isset($this->data[$field]) || trim((string)$this->data[$field]) === '') {
                $this->errors[$field][] = "The {$field} field is required.";
            }
        }
        return $this;
    }

    public function email(string $field): self {
        if (isset($this->data[$field]) && !filter_var($this->data[$field], FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field][] = "The {$field} must be a valid email address.";
        }
        return $this;
    }

    public function phone(string $field): self {
        if (isset($this->data[$field])) {
            $val = trim((string)$this->data[$field]);
            if (!preg_match('/^\+?[0-9\s\-]{6,20}$/', $val)) {
                $this->errors[$field][] = "The {$field} must be a valid phone number.";
            }
        }
        return $this;
    }

    public function in(string $field, array $allowed): self {
        if (isset($this->data[$field]) && !in_array($this->data[$field], $allowed, true)) {
            $this->errors[$field][] = "The {$field} must be one of: " . implode(', ', $allowed);
        }
        return $this;
    }

    public function minLength(string $field, int $min): self {
        if (isset($this->data[$field]) && strlen((string)$this->data[$field]) < $min) {
            $this->errors[$field][] = "The {$field} must be at least {$min} characters.";
        }
        return $this;
    }

    public function numeric(string $field): self {
        if (isset($this->data[$field]) && !is_numeric($this->data[$field])) {
            $this->errors[$field][] = "The {$field} must be a numeric value.";
        }
        return $this;
    }

    public function fails(): bool {
        return !empty($this->errors);
    }

    public function getErrors(): array {
        return $this->errors;
    }

    public function validateOrAbort(): void {
        if ($this->fails()) {
            Response::error('VALIDATION_ERROR', 'Input validation failed.', 422, $this->errors);
        }
    }
}
