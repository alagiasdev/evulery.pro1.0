<?php

namespace App\Core;

class Validator
{
    private array $errors = [];
    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public static function make(array $data): self
    {
        return new self($data);
    }

    public function required(string $field, string $label = ''): self
    {
        $label = $label ?: $field;
        if (!isset($this->data[$field]) || trim($this->data[$field]) === '') {
            $this->errors[$field] = "Il campo {$label} è obbligatorio.";
        }
        return $this;
    }

    public function email(string $field, string $label = ''): self
    {
        $label = $label ?: $field;
        if (isset($this->data[$field]) && !filter_var($this->data[$field], FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = "Il campo {$label} deve essere un'email valida.";
        }
        return $this;
    }

    public function minLength(string $field, int $min, string $label = ''): self
    {
        $label = $label ?: $field;
        if (isset($this->data[$field]) && mb_strlen($this->data[$field]) < $min) {
            $this->errors[$field] = "Il campo {$label} deve avere almeno {$min} caratteri.";
        }
        return $this;
    }

    public function maxLength(string $field, int $max, string $label = ''): self
    {
        $label = $label ?: $field;
        if (isset($this->data[$field]) && mb_strlen($this->data[$field]) > $max) {
            $this->errors[$field] = "Il campo {$label} non può superare {$max} caratteri.";
        }
        return $this;
    }

    public function numeric(string $field, string $label = ''): self
    {
        $label = $label ?: $field;
        if (isset($this->data[$field]) && !is_numeric($this->data[$field])) {
            $this->errors[$field] = "Il campo {$label} deve essere un numero.";
        }
        return $this;
    }

    public function integer(string $field, string $label = ''): self
    {
        $label = $label ?: $field;
        if (isset($this->data[$field]) && !ctype_digit((string)$this->data[$field])) {
            $this->errors[$field] = "Il campo {$label} deve essere un numero intero.";
        }
        return $this;
    }

    public function phone(string $field, string $label = ''): self
    {
        $label = $label ?: $field;
        if (isset($this->data[$field]) && !preg_match('/^\+?[0-9\s\-]{7,20}$/', $this->data[$field])) {
            $this->errors[$field] = "Il campo {$label} deve essere un numero di telefono valido.";
        }
        return $this;
    }

    public function date(string $field, string $label = ''): self
    {
        $label = $label ?: $field;
        if (isset($this->data[$field])) {
            $d = \DateTime::createFromFormat('Y-m-d', $this->data[$field]);
            if (!$d || $d->format('Y-m-d') !== $this->data[$field]) {
                $this->errors[$field] = "Il campo {$label} deve essere una data valida (YYYY-MM-DD).";
            }
        }
        return $this;
    }

    public function time(string $field, string $label = ''): self
    {
        $label = $label ?: $field;
        if (isset($this->data[$field]) && !preg_match('/^([01]\d|2[0-3]):([0-5]\d)$/', $this->data[$field])) {
            $this->errors[$field] = "Il campo {$label} deve essere un orario valido (HH:MM).";
        }
        return $this;
    }

    public function passwordStrength(string $field, string $label = ''): self
    {
        $label = $label ?: $field;
        if (isset($this->data[$field])) {
            $val = $this->data[$field];
            if (!preg_match('/[A-Z]/', $val)) {
                $this->errors[$field] = "Il campo {$label} deve contenere almeno una lettera maiuscola.";
            } elseif (!preg_match('/[0-9]/', $val)) {
                $this->errors[$field] = "Il campo {$label} deve contenere almeno un numero.";
            }
        }
        return $this;
    }

    public function between(string $field, int $min, int $max, string $label = ''): self
    {
        $label = $label ?: $field;
        if (isset($this->data[$field]) && is_numeric($this->data[$field])) {
            $value = (int)$this->data[$field];
            if ($value < $min || $value > $max) {
                $this->errors[$field] = "Il campo {$label} deve essere tra {$min} e {$max}.";
            }
        }
        return $this;
    }

    public function fails(): bool
    {
        return !empty($this->errors);
    }

    public function passes(): bool
    {
        return empty($this->errors);
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function firstError(): ?string
    {
        return reset($this->errors) ?: null;
    }
}
