<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

use Illuminate\Http\Request;

class DeleteController extends Controller
{
     public function deletedTeams(Request $request) {
        $perPage = $request->get('per_page', 15);
    $currentPage = $request->get('page', 1);
    $offset = ($currentPage - 1) * $perPage;
  $team = DB::table('teams')
    ->where('teams.active', 0)
    ->leftJoin('team_translations', 'team_translations.team_id', '=', 'teams.id')
    ->select(
        'teams.id',
        DB::raw('CONCAT("https://api.raalc.ae/storage/", MAX(teams.lowyer_image)) as lowyer_image'),
        DB::raw('MAX(teams.updated_at) as updated_at'),
        DB::raw('MAX(teams.active) as active'),
        DB::raw('JSON_UNQUOTE(JSON_EXTRACT(MAX(team_translations.fields_value), "$.name")) as name'),
        DB::raw('JSON_UNQUOTE(JSON_EXTRACT(MAX(team_translations.fields_value), "$.designation")) as designation'),
        DB::raw('JSON_UNQUOTE(JSON_EXTRACT(MAX(team_translations.fields_value), "$.lawyer_email")) as lawyer_email')
    )
    ->groupBy('teams.id')
     ->offset($offset)
     ->limit($perPage)
    ->paginate();



    if($team) {
        return response()->json([
            'status'  => 1,
            'team' => $team,
            'current_page' => (int) $currentPage,
            'per_page' => (int) $perPage,
        ], Response::HTTP_OK);
    }else {
        return response()->json([
            'status' => 0,
        ], Response::HTTP_OK);
    }
}
public function recoverTeams(Request $request) {

    $perPage = $request->get('per_page', 15);
    $currentPage = $request->get('page', 1);
    $offset = ($currentPage - 1) * $perPage;
    
    $id = $request->id;

    $teamExists = DB::table('teams')
        ->where('id', $id)
        ->first();

    if ($teamExists) {
        DB::table('teams')
            ->where('id', $id)
            ->update(['active' => 1]);

        $teams = DB::table('teams')
            ->where('teams.active', 0)
            ->leftJoin('team_translations', 'team_translations.team_id', '=', 'teams.id')
            ->select(
                'teams.id',
                DB::raw('CONCAT("https://api.raalc.ae/storage/", MAX(teams.lowyer_image)) as lowyer_image'),
                DB::raw('MAX(teams.updated_at) as updated_at'),
                DB::raw('MAX(teams.active) as active'),
                DB::raw('JSON_UNQUOTE(JSON_EXTRACT(MAX(team_translations.fields_value), "$.name")) as name'),
                DB::raw('JSON_UNQUOTE(JSON_EXTRACT(MAX(team_translations.fields_value), "$.designation")) as designation'),
                DB::raw('JSON_UNQUOTE(JSON_EXTRACT(MAX(team_translations.fields_value), "$.lawyer_email")) as lawyer_email')
            )
            ->groupBy('teams.id')
             ->offset($offset)
             ->limit($perPage)
            ->get();

        return response()->json([
            'status' => 1,
            'team'   => $teams,
            'current_page' => (int) $currentPage,
            'per_page' => (int) $perPage,
        ], Response::HTTP_OK);
    } else {
        return response()->json([
            'status' => 0,
            'message' => 'Team not found.'
        ], Response::HTTP_OK);
    }
}
}
