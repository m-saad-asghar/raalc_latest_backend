<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

use Illuminate\Http\Request;

class DeleteController extends Controller
{
     public function deletedEvents(Request $request) {
    $perPage = $request->get('per_page', 15);
    $currentPage = $request->get('page', 1);
    $offset = ($currentPage - 1) * $perPage;
    $filterName = $request->get('name');

    $query = DB::table('event')
        ->where('event.active', 0)
        ->leftJoin('event_translation', 'event_translation.event_id', '=', 'event.id')
        ->select(
            'event.id',
            DB::raw('CONCAT("https://api.raalc.ae/storage/", MAX(event.images)) as images'),
            DB::raw('MAX(event.updated_at) as updated_at'),
            DB::raw('MAX(event.active) as active'),
            DB::raw('JSON_UNQUOTE(JSON_EXTRACT(MAX(event_translation.field_values), "$.title")) as title'),
             DB::raw('JSON_UNQUOTE(JSON_EXTRACT(MAX(event_translation.field_values), "$.author_name")) as author_name'),
        )
        ->groupBy('event.id');

if (!empty($filterName)) {
    $query->havingRaw(
        'LOWER(JSON_UNQUOTE(JSON_EXTRACT(MAX(event_translation.field_values), "$.title"))) LIKE ?', 
        ['%' . strtolower($filterName) . '%']
    );
}



    $events = $query
        ->offset($offset)
        ->limit($perPage)
        ->get();

    if ($events) {
        return response()->json([
            'status'       => 1,
            'events'         => $events,
            'current_page' => (int) $currentPage,
            'per_page'     => (int) $perPage,
        ], Response::HTTP_OK);
    } else {
        return response()->json([
            'status' => 0,
        ], Response::HTTP_OK);
    }
}
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

public function deletedAuthors(Request $request) {
    $perPage     = (int) $request->get('per_page', 15);
    $currentPage = (int) $request->get('page', 1);
    $offset      = ($currentPage - 1) * $perPage;
    $filterName  = $request->get('name');
    $lang        = $request->get('lang', 'en');

    // Step 1: paginate the authors table only (uses index on `active`).
    $authorsQuery = DB::table('authors')
        ->where('active', 0)
        ->select('id', 'image', 'updated_at', 'active');

    // Optional name filter — apply via a sub-join only when needed.
    if (!empty($filterName)) {
        $authorsQuery->whereExists(function ($q) use ($filterName, $lang) {
            $q->select(DB::raw(1))
              ->from('author_translations as at')
              ->whereColumn('at.author_id', 'authors.id')
              ->where('at.lang', $lang)
              ->whereRaw(
                  'LOWER(JSON_UNQUOTE(JSON_EXTRACT(at.fields_value, "$.name"))) LIKE ?',
                  ['%' . strtolower($filterName) . '%']
              );
        });
    }

    $totalCount = (clone $authorsQuery)->count();

    $authors = $authorsQuery
        ->orderByDesc('id')
        ->offset($offset)
        ->limit($perPage)
        ->get();

    if ($authors->isEmpty()) {
        return response()->json([
            'status'       => 0,
            'authors'      => [],
            'total'        => 0,
            'current_page' => $currentPage,
            'per_page'     => $perPage,
        ], Response::HTTP_OK);
    }

    // Step 2: fetch translations for paginated ids in a single query.
    $authorIds = $authors->pluck('id')->all();

    $translations = DB::table('author_translations')
        ->whereIn('author_id', $authorIds)
        ->where('lang', $lang)
        ->select('author_id', 'fields_value')
        ->get()
        ->keyBy('author_id');

    $baseUrl = 'https://api.raalc.ae/storage/';

    $authors = $authors->map(function ($author) use ($translations, $baseUrl) {
        $name = null;
        $designation = null;

        if (isset($translations[$author->id])) {
            $fields = json_decode($translations[$author->id]->fields_value, true) ?: [];
            $name        = $fields['name'] ?? null;
            $designation = $fields['designation'] ?? null;
        }

        return [
            'id'          => $author->id,
            'image'       => $author->image ? $baseUrl . $author->image : null,
            'updated_at'  => $author->updated_at,
            'active'      => $author->active,
            'name'        => $name,
            'designation' => $designation,
        ];
    });

    return response()->json([
        'status'       => 1,
        'authors'      => $authors,
        'total'        => $totalCount,
        'current_page' => $currentPage,
        'per_page'     => $perPage,
    ], Response::HTTP_OK);
}

public function recoverAuthors(Request $request) {

    $id = $request->id;

    $authorExists = DB::table('authors')
        ->where('id', $id)
        ->first();

    if ($authorExists) {
        DB::table('authors')
            ->where('id', $id)
            ->update(['active' => 1]);

        return response()->json([
            'status' => 1,
        ], Response::HTTP_OK);
    } else {
        return response()->json([
            'status' => 0,
            'message' => 'Author not found.'
        ], Response::HTTP_OK);
    }
}

public function recoverEvents(Request $request) {
    
    $id = $request->id;

    $eventExists = DB::table('event')
        ->where('id', $id)
        ->first();

    if ($eventExists) {
        DB::table('event')
            ->where('id', $id)
            ->update(['active' => 1]);

        return response()->json([
            'status' => 1,
        ], Response::HTTP_OK);
    } else {
        return response()->json([
            'status' => 0,
            'message' => 'Event not found.'
        ], Response::HTTP_OK);
    }
}
}
