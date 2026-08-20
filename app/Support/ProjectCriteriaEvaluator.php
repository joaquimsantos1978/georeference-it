<?php

namespace App\Support;

use App\Models\Project;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
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

            // $field is safe to interpolate directly — validated above against a fixed
            // allowlist, never taken from raw user input otherwise.
            $column = "occurrences.{$field}";

            $isNumericField = in_array($field, Project::NUMERIC_CRITERIA_FIELDS, true);

            // Per-field, not just per-type — Project::operatorsForField() additionally gates
            // contains/not_contains to FULLTEXT_CRITERIA_FIELDS (a plain B-tree can't serve a
            // leading-wildcard LIKE at all, see below), on top of the numeric/text split
            // (gt/lt/gte/lte on a text field would either error at the SQL level or silently
            // do something nobody intended).
            if (!in_array($operator, Project::operatorsForField($field), true)) {
                throw new InvalidArgumentException("Operator '{$operator}' is not valid for field '{$field}'");
            }

            if ($isNumericField) {
                $sqlOperator = match ($operator) {
                    'equals'     => '=',
                    'not_equals' => '!=',
                    'gt'         => '>',
                    'lt'         => '<',
                    'gte'        => '>=',
                    'lte'        => '<=',
                };
                $clauses[]  = "{$column} {$sqlOperator} ?";
                $bindings[] = (int) $value;
                continue;
            }

            // A plain B-tree index can't be used for a leading-wildcard LIKE '%value%' at
            // all — MySQL scans every row regardless. Fields in FULLTEXT_CRITERIA_FIELDS are
            // *meant* to carry a FULLTEXT index, so route contains/not_contains through
            // MATCH()/AGAINST() for those: word-based matching, not arbitrary substring (a
            // search for "arriss" won't match inside "Carrisso" the way LIKE would), but
            // that's the tradeoff that makes "contains" on a free-text field fast instead of
            // a full-table scan over 300M+ rows.
            if (in_array($operator, ['contains', 'not_contains'], true)) {
                // Checked against the live schema, not just the static allowlist — a field
                // can spend real time in FULLTEXT_CRITERIA_FIELDS before its index build
                // actually finishes (they're built one at a time, InnoDB allows only one
                // FULLTEXT per ALTER TABLE). Deliberately no LIKE fallback here: that used to
                // silently turn "should be fast" into a quiet 300M-row scan the moment
                // someone picked 'contains' in the UI before the index landed — exactly the
                // failure mode this whole per-field operator gate exists to prevent. An error
                // here is loud and temporary; a silent scan was neither.
                if (!self::hasFulltextIndex($field)) {
                    throw new InvalidArgumentException("FULLTEXT index for field '{$field}' is not ready yet");
                }
                $match      = "MATCH({$column}) AGAINST(? IN BOOLEAN MODE)";
                $clauses[]  = $operator === 'contains' ? $match : "NOT {$match}";
                $bindings[] = self::fulltextBooleanQuery((string) $value);
                continue;
            }

            // No LOWER() here — the connection's default collation (utf8mb4_unicode_ci,
            // see config/database.php) is already case-insensitive, so wrapping either
            // side in LOWER() bought nothing semantically but turned `column = ?` into a
            // non-sargable functional expression, making the plain index on e.g.
            // country_code unusable and forcing a full scan of occurrences (273M+ rows) —
            // this is exactly what produced a 140+ minute runaway SELECT in production
            // for a plain "country_code equals AO" project, which in turn blocked a
            // concurrent pt-online-schema-change from creating its triggers.
            match ($operator) {
                'equals' => [
                    $clauses[]  = "{$column} = ?",
                    $bindings[] = (string) $value,
                ],
                'not_equals' => [
                    $clauses[]  = "{$column} != ?",
                    $bindings[] = (string) $value,
                ],
                'starts_with' => [
                    $clauses[]  = "{$column} LIKE ?",
                    $bindings[] = $value . '%',
                ],
                'not_starts_with' => [
                    $clauses[]  = "{$column} NOT LIKE ?",
                    $bindings[] = $value . '%',
                ],
            };
        }

        return [implode(' AND ', $clauses), $bindings];
    }

    // AND semantics across words (require every one, same spirit as
    // GeorefController::focusSearchTerms()) rather than BOOLEAN MODE's OR default — a
    // multi-word "contains" value should narrow results, not broaden them. Trailing '*'
    // allows a word to match as a prefix too (partial-word typing while it's still short).
    private static function fulltextBooleanQuery(string $value): string
    {
        $words = preg_split('/\s+/', trim($value), -1, PREG_SPLIT_NO_EMPTY);
        return implode(' ', array_map(fn($w) => '+' . $w . '*', $words));
    }

    // Cached for an hour, not forever — self-corrects on its own shortly after a FULLTEXT
    // index is added in production, with no cache-bust step to remember as part of that
    // deploy. A live schema check per call would also be cheap (single indexed
    // information_schema lookup), but this avoids paying it on every request.
    private static function hasFulltextIndex(string $column): bool
    {
        return Cache::remember("occurrences_fulltext_index:{$column}", 3600, function () use ($column) {
            return DB::selectOne("
                SELECT 1 FROM information_schema.statistics
                WHERE table_schema = DATABASE()
                  AND table_name = 'occurrences'
                  AND column_name = ?
                  AND index_type = 'FULLTEXT'
                LIMIT 1
            ", [$column]) !== null;
        });
    }

    private const DROPDOWN_CACHE_TTL   = 86400; // 24h — values only change via the monthly GBIF refresh.
    private const DROPDOWN_MAX_OPTIONS = 500;

    // The actual values a DROPDOWN_CRITERIA_FIELDS column holds right now, not a maintained
    // enum list that could drift from what GBIF data actually contains. $field is only ever
    // called with a member of Project::DROPDOWN_CRITERIA_FIELDS (checked below), so it's safe
    // to use as a column identifier via the query builder's whereNotNull/orderBy/pluck.
    public static function dropdownOptions(string $field): array
    {
        if (!in_array($field, Project::DROPDOWN_CRITERIA_FIELDS, true)) {
            return [];
        }

        return Cache::remember("occurrences_distinct_values:{$field}", self::DROPDOWN_CACHE_TTL, function () use ($field) {
            return DB::table('occurrences')
                ->whereNotNull($field)
                ->where($field, '!=', '')
                ->distinct()
                ->orderBy($field)
                ->limit(self::DROPDOWN_MAX_OPTIONS)
                ->pluck($field)
                ->all();
        });
    }
}
