<?php

declare(strict_types=1);

namespace Tala\Engine;

final class MergeValidator
{
    /**
     * Validates a dry-run merge plan without touching any database.
     *
     * @param array<string, mixed> $plan
     * @return array{valid: bool, errors: array<int, string>, warnings: array<int, string>}
     */
    public function validate(array $plan): array
    {
        $errors = [];
        $warnings = [];
        $seen = [];

        foreach ($plan['operations'] ?? [] as $operation) {
            if (!is_array($operation)) {
                $errors[] = 'Malformed operation array.';
                continue;
            }

            $this->validateOperation($operation, $errors, $warnings, $seen);
        }

        return [
            'valid' => $errors === [],
            'errors' => array_values(array_unique($errors)),
            'warnings' => array_values(array_unique($warnings)),
        ];
    }

    /**
     * @param array<string, mixed> $operation
     * @param array<int, string> $errors
     * @param array<int, string> $warnings
     * @param array<string, bool> $seen
     */
    public function validateOperation(array $operation, array &$errors, array &$warnings, array &$seen): void
    {
        $action = (string) ($operation['action'] ?? '');
        $this->validateAction($action, $errors);

        $table = (string) ($operation['table'] ?? '');
        if ($action === '' || $table === '') {
            $errors[] = 'Malformed operation array.';
            return;
        }

        $signature = $this->duplicateSignature($operation);
        if ($signature !== null) {
            if (isset($seen[$signature])) {
                $errors[] = $this->duplicateMessage($operation);
                return;
            }

            $seen[$signature] = true;
        }

        match ($action) {
            'create_table' => $this->validateCreateTable($operation, $errors),
            'add_column' => $this->validateAddColumn($operation, $errors),
            'modify_column' => $this->validateModifyColumn($operation, $errors, $warnings),
            'create_index' => $this->validateCreateIndex($operation, $errors),
            default => null,
        };
    }

    /**
     * @param array<int, string> $errors
     */
    private function validateAction(string $action, array &$errors): void
    {
        if ($action === '') {
            $errors[] = 'Missing action type.';
            return;
        }

        if (!in_array($action, ['create_table', 'add_column', 'modify_column', 'create_index'], true)) {
            $errors[] = sprintf('Unknown action type: %s', $action);
        }
    }

    /**
     * @param array<string, mixed> $operation
     * @param array<int, string> $errors
     */
    private function validateCreateTable(array $operation, array &$errors): void
    {
        if (($operation['table'] ?? '') === '') {
            $errors[] = 'Malformed create_table operation.';
        }
    }

    /**
     * @param array<string, mixed> $operation
     * @param array<int, string> $errors
     */
    private function validateAddColumn(array $operation, array &$errors): void
    {
        if (($operation['column'] ?? '') === '') {
            $errors[] = 'Malformed add_column operation.';
        }
    }

    /**
     * @param array<string, mixed> $operation
     * @param array<int, string> $errors
     * @param array<int, string> $warnings
     */
    private function validateModifyColumn(array $operation, array &$errors, array &$warnings): void
    {
        if (($operation['column'] ?? '') === '') {
            $errors[] = 'Malformed modify_column operation.';
            return;
        }

        $sourceType = $operation['source_type'] ?? null;
        $destinationType = $operation['destination_type'] ?? null;

        if (!is_string($sourceType) || trim($sourceType) === '') {
            $errors[] = sprintf(
                'Missing source_type metadata for modify_column operation on %s.%s',
                (string) ($operation['table'] ?? ''),
                (string) ($operation['column'] ?? '')
            );
        }

        if (!is_string($destinationType) || trim($destinationType) === '') {
            $errors[] = sprintf(
                'Missing destination_type metadata for modify_column operation on %s.%s',
                (string) ($operation['table'] ?? ''),
                (string) ($operation['column'] ?? '')
            );
        }

        if (
            is_string($sourceType) &&
            is_string($destinationType) &&
            strcasecmp(trim($sourceType), trim($destinationType)) === 0
        ) {
            $warnings[] = sprintf(
                'Redundant modify_column operation on %s.%s',
                (string) ($operation['table'] ?? ''),
                (string) ($operation['column'] ?? '')
            );
        }
    }

    /**
     * @param array<string, mixed> $operation
     * @param array<int, string> $errors
     */
    private function validateCreateIndex(array $operation, array &$errors): void
    {
        if (($operation['index'] ?? '') === '') {
            $errors[] = 'Malformed create_index operation.';
        }
    }

    /**
     * @param array<string, mixed> $operation
     */
    private function duplicateSignature(array $operation): ?string
    {
        $action = (string) ($operation['action'] ?? '');
        $table = (string) ($operation['table'] ?? '');

        return match ($action) {
            'create_table' => sprintf('%s|%s', $action, $table),
            'add_column', 'modify_column' => sprintf(
                '%s|%s|%s',
                $action,
                $table,
                (string) ($operation['column'] ?? '')
            ),
            'create_index' => sprintf(
                '%s|%s|%s',
                $action,
                $table,
                (string) ($operation['index'] ?? '')
            ),
            default => null,
        };
    }

    /**
     * @param array<string, mixed> $operation
     */
    private function duplicateMessage(array $operation): string
    {
        $action = (string) ($operation['action'] ?? '');
        $table = (string) ($operation['table'] ?? '');

        return match ($action) {
            'create_table' => sprintf('Duplicate create_table operation for %s', $table),
            'add_column' => sprintf(
                'Duplicate add_column operation for %s.%s',
                $table,
                (string) ($operation['column'] ?? '')
            ),
            'modify_column' => sprintf(
                'Multiple modify_column operations on %s',
                (string) ($operation['column'] ?? '')
            ),
            'create_index' => sprintf(
                'Duplicate create_index operation for %s.%s',
                $table,
                (string) ($operation['index'] ?? '')
            ),
            default => 'Duplicate operation detected.',
        };
    }
}
