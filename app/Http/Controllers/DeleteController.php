<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

use Illuminate\Http\Request;

class DeleteController extends Controller
{
     public function deletedServices(Request $request) {
    $perPage = $request->get('per_page', 15);
    $currentPage = $request->get('page', 1);
    $offset = ($currentPage - 1) * $perPage;
    $filterName = $request->get('name');

    $query = DB::table('services')
        ->where('services.active', 0)
        ->leftJoin('service_translations', 'service_translations.service_id', '=', 'services.id')
        ->select(
            'services.id',
            DB::raw('CONCAT("https://api.raalc.ae/storage/", MAX(services.sec_one_image)) as images'),
            DB::raw('MAX(services.updated_at) as updated_at'),
            DB::raw('MAX(services.active) as active'),
            DB::raw('JSON_UNQUOTE(JSON_EXTRACT(MAX(service_translations.translated_value), "$.sec_one_heading_one")) as heading'),
        )
        ->groupBy('services.id');

if (!empty($filterName)) {
    $query->havingRaw(
        'LOWER(JSON_UNQUOTE(JSON_EXTRACT(MAX(service_translations.translated_value), "$.sec_one_heading_one"))) LIKE ?', 
        ['%' . strtolower($filterName) . '%']
    );
}



    $services = $query
        ->offset($offset)
        ->limit($perPage)
        ->get();

    if ($services) {
        return response()->json([
            'status'       => 1,
            'services'         => $services,
            'current_page' => (int) $currentPage,
            'per_page'     => (int) $perPage,
        ], Response::HTTP_OK);
    } else {
        return response()->json([
            'status' => 0,
        ], Response::HTTP_OK);
    }
}
   public function deletedNews(Request $request) {
    $perPage = $request->get('per_page', 15);
    $currentPage = $request->get('page', 1);
    $offset = ($currentPage - 1) * $perPage;
    $filterName = $request->get('name');

    $query = DB::table('news')
        ->where('news.active', 0)
        ->leftJoin('news_translation', 'news_translation.news_id', '=', 'news.id')
        ->select(
            'news.id',
            DB::raw('CONCAT("https://api.raalc.ae/storage/", MAX(news.images)) as images'),
            DB::raw('MAX(news.updated_at) as updated_at'),
            DB::raw('MAX(news.active) as active'),
            DB::raw('JSON_UNQUOTE(JSON_EXTRACT(MAX(news_translation.field_values), "$.name")) as name'),
        )
        ->groupBy('news.id');

if (!empty($filterName)) {
    $query->havingRaw(
        'LOWER(JSON_UNQUOTE(JSON_EXTRACT(MAX(news_translation.field_values), "$.name"))) LIKE ?', 
        ['%' . strtolower($filterName) . '%']
    );
}



    $news = $query
        ->offset($offset)
        ->limit($perPage)
        ->get();

    if ($news) {
        return response()->json([
            'status'       => 1,
            'news'         => $news,
            'current_page' => (int) $currentPage,
            'per_page'     => (int) $perPage,
        ], Response::HTTP_OK);
    } else {
        return response()->json([
            'status' => 0,
        ], Response::HTTP_OK);
    }
}
public function deletedTeams(Request $request) {
    $perPage = $request->get('per_page', 15);
    $currentPage = $request->get('page', 1);
    $offset = ($currentPage - 1) * $perPage;
    $filterName = $request->get('name');

    $query = DB::table('teams')
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
        ->groupBy('teams.id');

if (!empty($filterName)) {
    $query->havingRaw(
        'LOWER(JSON_UNQUOTE(JSON_EXTRACT(MAX(team_translations.fields_value), "$.name"))) LIKE ?', 
        ['%' . strtolower($filterName) . '%']
    );
}



    $team = $query
        ->offset($offset)
        ->limit($perPage)
        ->get();

    if ($team) {
        return response()->json([
            'status'       => 1,
            'team'         => $team,
            'current_page' => (int) $currentPage,
            'per_page'     => (int) $perPage,
        ], Response::HTTP_OK);
    } else {
        return response()->json([
            'status' => 0,
        ], Response::HTTP_OK);
    }
}
public function recoverTeams(Request $request) {
    
    $id = $request->id;

    $teamExists = DB::table('teams')
        ->where('id', $id)
        ->first();

    if ($teamExists) {
        DB::table('teams')
            ->where('id', $id)
            ->update(['active' => 1]);

        return response()->json([
            'status' => 1,
        ], Response::HTTP_OK);
    } else {
        return response()->json([
            'status' => 0,
            'message' => 'Team not found.'
        ], Response::HTTP_OK);
    }
}

public function recoverNews(Request $request) {
    
    $id = $request->id;

    $newsExists = DB::table('news')
        ->where('id', $id)
        ->first();

    if ($newsExists) {
        DB::table('news')
            ->where('id', $id)
            ->update(['active' => 1]);

        return response()->json([
            'status' => 1,
        ], Response::HTTP_OK);
    } else {
        return response()->json([
            'status' => 0,
            'message' => 'News not found.'
        ], Response::HTTP_OK);
    }
}

public function recoverServices(Request $request) {
    
    $id = $request->id;

    $serviceExists = DB::table('services')
        ->where('id', $id)
        ->first();

    if ($serviceExists) {
        DB::table('services')
            ->where('id', $id)
            ->update(['active' => 1]);

        return response()->json([
            'status' => 1,
        ], Response::HTTP_OK);
    } else {
        return response()->json([
            'status' => 0,
            'message' => 'Services not found.'
        ], Response::HTTP_OK);
    }
}
}
