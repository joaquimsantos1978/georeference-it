<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Project extends Model
{
    // Curated allowlist, not "any occurrences column" — keeps criteria to descriptive
    // fields a user would actually search by, and is the single enforcement point
    // ProjectCriteriaEvaluator checks before any value reaches raw SQL.
    public const ALLOWED_CRITERIA_FIELDS = [
        'recorded_by', 'family', 'kingdom', 'phylum', 'class', 'order', 'genus',
        'specific_epithet', 'taxon_rank', 'scientific_name', 'type_status',
        'catalog_number', 'institution_code', 'collection_code', 'basis_of_record',
        'country_code', 'country', 'state_province', 'county', 'municipality',
        'island', 'island_group', 'water_body', 'continent', 'higher_geography',
        'dataset_key', 'verbatim_locality', 'location_remarks',
        'year', 'month', 'day',
    ];

    // year/month/day are stored as integers — 'contains'/'starts_with' route through LIKE,
    // which can't use a numeric column's B-tree index at all (not "isn't indexed yet", but
    // structurally can't, regardless of indexing), and were the mechanism behind a
    // 'year starts_with "17"' project ("Before 1800") producing a 1+ hour unindexed scan in
    // production. Comparison operators below are the actually-correct tool for these fields.
    public const NUMERIC_CRITERIA_FIELDS = ['year', 'month', 'day'];

    public const TEXT_OPERATORS = ['equals', 'contains', 'starts_with'];

    public const NUMERIC_OPERATORS = ['equals', 'gt', 'lt', 'gte', 'lte'];

    public const ALLOWED_OPERATORS = ['equals', 'contains', 'starts_with', 'gt', 'lt', 'gte', 'lte'];

    // Fields carrying a FULLTEXT index in addition to a plain one — ProjectCriteriaEvaluator
    // uses MATCH()/AGAINST() instead of LIKE '%value%' for these when the operator is
    // 'contains', since a plain B-tree index can't be used for a leading-wildcard LIKE at
    // all. Word-based matching, not arbitrary substring — the tradeoff that makes "contains"
    // on a free-text field fast instead of a full-table scan over 280M+ rows.
    public const FULLTEXT_CRITERIA_FIELDS = ['recorded_by', 'scientific_name', 'catalog_number'];

    protected $fillable = [
        'user_id', 'title', 'description', 'tags', 'image', 'visibility', 'mode',
        'criteria', 'submitted_keys', 'invalid_keys',
    ];

    protected $casts = [
        'criteria'       => 'array',
        'submitted_keys' => 'array',
        'invalid_keys'   => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isOwnedBy(?User $user): bool
    {
        return $user && $user->id === $this->user_id;
    }

    public function isVisibleTo(?User $user): bool
    {
        return $this->visibility === 'public' || $this->isOwnedBy($user);
    }

    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        return $query->where(function ($q) use ($user) {
            $q->where('visibility', 'public');
            if ($user) {
                $q->orWhere('user_id', $user->id);
            }
        });
    }
}
