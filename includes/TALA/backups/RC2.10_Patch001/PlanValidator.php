<?php

declare(strict_types=1);

namespace Tala\Engine;

final class PlanValidator
{
    /**
     * Validate a merge plan.
     *
     * @param array<string,mixed> $plan
     * @return array<string,mixed>
     */
    public function validate(array $plan): array
    {
        $validated = [
            'status' => true,
            'operations' => [],
            'warnings' => [],
            'errors' => [],
        ];

        foreach ($plan['operations'] ?? [] as $operation) {

            if (!$this->validateOperation($operation, $validated)) {
                continue;
            }

            $validated['operations'][] = $operation;
        }

        if (!empty($validated['errors'])) {
            $validated['status'] = false;
        }

        return $validated;
    }

    /**
     * Validate a single operation.
     *
     * @param array<string,mixed> $operation
     * @param array<string,mixed> $validated
     */
    private function validateOperation(
        array $operation,
        array &$validated
    ): bool {

      return
    $this->validateOperationType($operation, $validated)
    && $this->validateTarget($operation, $validated)
    && $this->validateCategory($operation, $validated);
    }
	
	private function validateOperationType(
		array $operation,
		array &$validated
	): bool {

    if (empty($operation['operation'])) {

        $validated['errors'][] =
            'Operation type is missing.';

        return false;
    }

    return true;
}

	private function validateTarget(
		array $operation,
		array &$validated
	): bool {

		if (empty($operation['target'])) {

			$validated['errors'][] =
				'Operation target is missing.';

			return false;
		}

		return true;
}

	private function validateCategory(
		array $operation,
		array &$validated
	): bool {

		if (empty($operation['category'])) {

			$validated['errors'][] =
				'Operation category is missing.';

			return false;
		}

		return true;
	}
}