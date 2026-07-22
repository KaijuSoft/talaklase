<?php

declare(strict_types=1);

namespace Tala\Engine;

use PDO;

/**
 * Builds reusable SQL fragments for column definitions.
 *
 * Future column handlers can share this builder to avoid duplicating
 * SQL formatting and default-expression handling.
 */
final class SqlColumnDefinitionBuilder
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * @param array<string, mixed> $definition
     */
    public function buildColumnDefinition(array $definition, ?array $existingDefinition = null): string
    {
        $parts = [];

        $type = $this->buildTypeClause($definition);
        if ($type !== '') {
            $parts[] = $type;
        }

        $characterSet = $this->buildCharacterSetClause($definition, $existingDefinition);
        if ($characterSet !== '') {
            $parts[] = $characterSet;
        }

        $collation = $this->buildCollationClause($definition, $existingDefinition);
        if ($collation !== '') {
            $parts[] = $collation;
        }

        $nullable = $this->buildNullableClause($definition);
        if ($nullable !== '') {
            $parts[] = $nullable;
        }

        $default = $this->buildDefaultClause($definition);
        if ($default !== '') {
            $parts[] = $default;
        }

        $extra = $this->buildExtraClause($definition);
        if ($extra !== '') {
            $parts[] = $extra;
        }

        $comment = $this->buildCommentClause($definition);
        if ($comment !== '') {
            $parts[] = $comment;
        }

        return implode(' ', $parts);
    }

    /**
     * @param array<string, mixed> $definition
     */
    private function buildTypeClause(array $definition): string
    {
        return trim((string) ($definition['type'] ?? ''));
    }

    /**
     * @param array<string, mixed> $definition
     */
    private function buildNullableClause(array $definition): string
    {
        return ((bool) ($definition['nullable'] ?? true)) ? 'NULL' : 'NOT NULL';
    }

    /**
     * @param array<string, mixed> $definition
     */
    private function buildDefaultClause(array $definition): string
    {
        if (!array_key_exists('default', $definition)) {
            return '';
        }

        $default = $definition['default'];

        if ($default === null) {
            return '';
        }

        if (is_int($default) || is_float($default)) {
            return 'DEFAULT ' . $default;
        }

        if (is_bool($default)) {
            return 'DEFAULT ' . ($default ? '1' : '0');
        }

        $value = trim((string) $default);
        if ($value === '') {
            return '';
        }

        if ($this->isSqlExpression($value)) {
            return 'DEFAULT ' . $this->normalizeExpression($value);
        }

        return 'DEFAULT ' . $this->pdo->quote($value);
    }

    /**
     * @param array<string, mixed> $definition
     */
    private function buildExtraClause(array $definition): string
    {
        $extra = strtolower(trim((string) ($definition['extra'] ?? '')));

        if ($extra === '') {
            return '';
        }

        $parts = [];

        if (str_contains($extra, 'auto_increment')) {
            $parts[] = 'AUTO_INCREMENT';
        }

        return implode(' ', $parts);
    }

    /**
     * @param array<string, mixed> $definition
     */
    private function buildCharacterSetClause(array $definition, ?array $existingDefinition = null): string
    {
        $charset = trim((string) ($definition['charset'] ?? ''));

        if ($charset === '' || !$this->isTextualColumn($definition)) {
            return '';
        }

        if ($existingDefinition !== null) {
            $existingCharset = trim((string) ($existingDefinition['charset'] ?? ''));
            if ($existingCharset === $charset) {
                return '';
            }
        }

        return 'CHARACTER SET ' . $charset;
    }

    /**
     * @param array<string, mixed> $definition
     */
    private function buildCollationClause(array $definition, ?array $existingDefinition = null): string
    {
        $collation = trim((string) ($definition['collation'] ?? ''));

        if ($collation === '' || !$this->isTextualColumn($definition)) {
            return '';
        }

        if ($existingDefinition !== null) {
            $existingCollation = trim((string) ($existingDefinition['collation'] ?? ''));
            if ($existingCollation === $collation) {
                return '';
            }
        }

        return 'COLLATE ' . $collation;
    }

    /**
     * @param array<string, mixed> $definition
     */
    private function buildCommentClause(array $definition): string
    {
        $comment = $definition['comment'] ?? null;
        if ($comment === null) {
            return '';
        }

        $value = trim((string) $comment);
        if ($value === '') {
            return '';
        }

        return 'COMMENT ' . $this->pdo->quote($value);
    }

    private function isSqlExpression(string $value): bool
    {
        $normalized = strtoupper(trim($value));

        return in_array($normalized, ['CURRENT_TIMESTAMP', 'CURRENT_TIMESTAMP()', 'NULL'], true);
    }

    private function normalizeExpression(string $value): string
    {
        $normalized = strtoupper(trim($value));

        return $normalized === 'NULL' ? 'NULL' : $normalized;
    }

    /**
     * @param array<string, mixed> $definition
     */
    private function isTextualColumn(array $definition): bool
    {
        $type = strtolower(trim((string) ($definition['type'] ?? '')));

        return $type !== '' && preg_match('/\b(char|varchar|text|tinytext|mediumtext|longtext|enum|set)\b/', $type) === 1;
    }
}
