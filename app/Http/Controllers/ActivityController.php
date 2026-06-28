<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ActivityController extends Controller
{
    public function index(Request $request)
    {
        $filterUserId  = $request->integer('user') ?: null;
        $filterCountry = strtoupper(trim($request->get('country', ''))) ?: null;
        $authId        = (int) auth()->id();

        // Resolve filtered user (must have public_name or be the auth user)
        $filterUser = null;
        if ($filterUserId) {
            $filterUser = User::find($filterUserId);
            if ($filterUser && !$filterUser->public_name && $authId !== $filterUser->id) {
                $filterUser   = null;
                $filterUserId = null;
            }
        }

        $userVisibility = "IF(u.public_name = 1 OR u.id = {$authId}, u.name, NULL)";
        $userIdVisible  = "IF(u.public_name = 1 OR u.id = {$authId}, u.id, NULL)";
        $userAvatar     = "IF(u.public_name = 1 OR u.id = {$authId}, u.avatar, NULL)";

        $userFilter    = $filterUserId ? "AND gs.user_id = {$filterUserId}" : '';
        $countryFilter = $filterCountry ? "AND lg.country_code = '{$filterCountry}'" : '';

        $userFilterV    = $filterUserId ? "AND gv.user_id = {$filterUserId}" : '';

        // Georef suggestions — one row per (locality_group, user) event
        $georefs = "
            SELECT
                'georef'                             AS type,
                gs.locality_group_id,
                gs.user_id,
                MAX(gs.created_at)                   AS activity_at,
                COUNT(*)                             AS occ_count,
                ANY_VALUE(gs.decimal_latitude)       AS lat,
                ANY_VALUE(gs.decimal_longitude)      AS lng,
                ANY_VALUE(gs.coordinate_uncertainty_m) AS uncertainty_m,
                ANY_VALUE(gs.georeference_remarks)   AS remarks,
                ANY_VALUE(gs.status)                 AS status,
                NULL                                 AS vote,
                NULL                                 AS suggestion_owner_id,
                lg.verbatim_locality, lg.municipality, lg.county, lg.state_province, lg.country_code,
                {$userVisibility}                    AS user_name,
                {$userIdVisible}                     AS public_user_id,
                {$userAvatar}                        AS user_avatar
            FROM georef_suggestions gs
            JOIN locality_groups lg ON lg.id = gs.locality_group_id
            LEFT JOIN users u ON u.id = gs.user_id
            WHERE gs.locality_group_id IS NOT NULL
            {$userFilter}
            {$countryFilter}
            GROUP BY gs.locality_group_id, gs.user_id,
                     lg.verbatim_locality, lg.municipality, lg.county, lg.state_province, lg.country_code
        ";

        // Validations — one row per vote
        $validations = "
            SELECT
                CASE gv.vote WHEN 'agree' THEN 'validation_agree' WHEN 'disagree' THEN 'validation_disagree' ELSE 'validation_abstain' END AS type,
                gs.locality_group_id,
                gv.user_id,
                gv.created_at                        AS activity_at,
                1                                    AS occ_count,
                NULL AS lat, NULL AS lng, NULL AS uncertainty_m, NULL AS remarks,
                gs.status,
                gv.vote,
                gs.user_id                           AS suggestion_owner_id,
                lg.verbatim_locality, lg.municipality, lg.county, lg.state_province, lg.country_code,
                {$userVisibility}                    AS user_name,
                {$userIdVisible}                     AS public_user_id,
                {$userAvatar}                        AS user_avatar
            FROM georef_validations gv
            JOIN georef_suggestions gs ON gs.id = gv.suggestion_id
            JOIN locality_groups lg ON lg.id = gs.locality_group_id
            LEFT JOIN users u ON u.id = gv.user_id
            WHERE gs.locality_group_id IS NOT NULL
            {$userFilterV}
            {$countryFilter}
        ";

        $union = "({$georefs}) UNION ALL ({$validations})";

        $activities = DB::table(DB::raw("({$union}) AS activity"))
            ->orderByDesc('activity_at')
            ->paginate(40)
            ->withQueryString();

        // Public users for filter dropdown
        $publicUsers = User::where('public_name', true)
            ->withCount('suggestions')
            ->having('suggestions_count', '>', 0)
            ->orderByDesc('suggestions_count')
            ->limit(50)
            ->get(['id', 'name', 'avatar']);

        return view('activity', compact('activities', 'filterUser', 'filterCountry', 'publicUsers'));
    }
}
