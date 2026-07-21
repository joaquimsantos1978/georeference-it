<?php

namespace App\Support;

use App\Models\Project;
use InvalidArgumentException;

// Turns a project's stored criteria (AND-only field/operator/value conditions) into a raw
// SQL WHERE fragment + bindings, evaluated live against `occurrences` — no materialized
// membership table, see the thematic-projects plan. Fields/operators are checked against
// Project's allowlists here, the single enforcement point before anything reaches raw SQL.
class ProjectCriteriaEvaluator
{
    /**
     * @param array<int, array{field: string, operator: string, value: string}> $conditions
     * @return array{0: string, 1: array<int, string>} [$sql, $bindings] — $sql is '' when
     *         $conditions is empty (caller decides whether that means "no restriction").
     */
    public static function toSqlWhere(array $conditions): array
    {
        $clauses  = [];
        $bindings = [];

        foreach ($conditions as $condition) {
            $field    = $condition['field']    ?? null;
            $operator = $condition['operator'] ?? null;
            $value    = $condition['value']    ?? null;

            if (!in_array($field, Project::ALLOWED_CRITERIA_FIELDS, true)) {
                throw new InvalidArgumentException("Disallowed criteria field: {$field}");
            }
            if (!in_array($operator, Project::ALLOWED_OPERATORS, true)) {
                throw new InvalidArgumentException("Disallowed criteria operator: {$operator}");
            }

            // $field is safe to interpolate directly — validated above against a fixed
            // allowlist, never taken from raw user input otherwise.
            $column = "occurrences.{$field}";

            match ($operator) {
                'equals' => [
                    $clauses[]  = "LOWER({$column}) = LOWER(?)",
                    $bindings[] = (string) $value,
                ],
                'contains' => [
                    $clauses[]  = "LOWER({$column}) LIKE LOWER(?)",
                    $bindings[] = '%' . $value . '%',
                ],
                'starts_with' => [
                    $clauses[]  = "LOWER({$column}) LIKE LOWER(?)",
                    $bindings[] = $value . '%',
                ],
            };
        }

        return [implode(' AND ', $clauses), $bindings];
    }
}
