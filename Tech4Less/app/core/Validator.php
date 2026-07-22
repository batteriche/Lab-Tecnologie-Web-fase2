<?php

/**
 * Validazione input centralizzata: ogni controller costruisce le regole
 * e raccoglie errori omogenei, invece di if sparsi.
 */
class Validator
{
    private array $errors = [];

    public function required(array $data, string $field, string $label): self
    {
        if (empty($data[$field]) && $data[$field] !== '0') {
            $this->errors[$field] = "Il campo {$label} è obbligatorio.";
        }
        return $this;
    }

    public function email(array $data, string $field): self
    {
        if (!empty($data[$field]) && !filter_var($data[$field], FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = 'Indirizzo email non valido.';
        }
        return $this;
    }

    public function minLength(array $data, string $field, int $min, string $label): self
    {
        $lunghezza = function_exists('mb_strlen') ? mb_strlen($data[$field] ?? '') : strlen($data[$field] ?? '');

        if (!empty($data[$field]) && $lunghezza < $min) {
            $this->errors[$field] = "{$label} deve contenere almeno {$min} caratteri.";
        }
        return $this;
    }

    public function numeric(array $data, string $field, string $label): self
    {
        if (!empty($data[$field]) && !is_numeric($data[$field])) {
            $this->errors[$field] = "{$label} deve essere un numero.";
        }
        return $this;
    }

    public function inArray(array $data, string $field, array $allowed, string $label): self
    {
        if (!empty($data[$field]) && !in_array($data[$field], $allowed, true)) {
            $this->errors[$field] = "{$label} non valido.";
        }
        return $this;
    }

    public function fails(): bool
    {
        return count($this->errors) > 0;
    }

    public function errors(): array
    {
        return $this->errors;
    }
}
