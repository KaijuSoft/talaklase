<?php

declare(strict_types=1);

namespace Tala\Engine;

/**
 * Provides pure row-level conflict comparison and resolution decisions.
 *
 * This component does not access databases, configuration, or plans.
 */
final class ConflictResolver
{
    /**
     * Resolves one conflict without applying an automatic policy.
     *
     * @param array<string, mixed> $conflict
     * @return array<string, mixed>
     */
    public function resolve(array $conflict): array
    {
        $conflict['strategy'] = 'manual_review';
        $conflict['status'] = 'conflict';

        return $conflict;
    }

    /**
     * Resolves multiple conflicts using the default manual-review strategy.
     *
     * @param array<int, array<string, mixed>> $conflicts
     * @return array<int, array<string, mixed>>
     */
    public function resolveAll(array $conflicts): array
    {
        return array_map(
            fn (array $conflict): array => $this->resolve($conflict),
            $conflicts
        );
    }

    /**
     * Returns source fields whose values differ from the destination.
     * Missing destination fields are ignored to preserve merge behavior.
     *
     * @param array<string, mixed> $source
     * @param array<string, mixed> $destination
     * @return array<int, string>
     */
    public function detectChangedFields(array $source, array $destination): array
    {
        $changed = [];

        foreach ($source as $field => $value) {
            if (!array_key_exists($field, $destination)) {
                continue;
            }

            if ((string) $destination[$field] !== (string) $value) {
                $changed[] = (string) $field;
            }
        }

        return $changed;
    }

    /**
     * Determines whether two rows differ.
     *
     * @param array<string, mixed> $source
     * @param array<string, mixed> $destination
     */
    public function compareRows(array $source, array $destination): bool
    {
        return $this->detectChangedFields($source, $destination) !== [];
    }

    /**
     * Builds a normalized conflict object for later resolution policies.
     *
     * @param array<string, mixed> $businessKey
     * @param array<string, mixed> $source
     * @param array<string, mixed> $destination
     * @return array<string, mixed>
     */
    public function buildConflict(
        string $table,
        array $businessKey,
        array $source,
        array $destination
    ): array {
        return [
            'table' => $table,
            'business_key' => $businessKey,
            'source' => $source,
            'destination' => $destination,
            'changed_fields' => $this->detectChangedFields($source, $destination),
            'strategy' => 'manual_review',
            'status' => 'conflict',
        ];
    }
}
