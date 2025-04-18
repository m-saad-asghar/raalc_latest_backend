<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class LogController extends Controller
{
    public function saveLog(Request $request)
    {
        try {
            DB::table('logs')->insert([
                'page_url'   => $request->page_url,
                'origin'     => $request->origin,
                'source'     => $request->source,
                'type'     => $request->type,
                'message'    => $request->message,
                'ip_address' => $request->ip(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
    
            return response()->json([
                'status'     => true,
                'message'    => 'Log saved successfully',
            ], Response::HTTP_OK);
    
        } catch (\Exception $e) {
            Log::error('Log saving failed', ['error' => $e->getMessage()]);
    
            return response()->json([
                'status'     => false,
                'message'    => 'Failed to save log',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getLogs(Request $request)
    {
        $perPage = $request->get('per_page', 15);
        $origin = $request->get('origin');

        $query = DB::table('logs');

        if ($origin) {
            $query->where('origin', 'LIKE', "%$origin%");
        }

        $currentPage = $request->get('page', 1);
        $offset = ($currentPage - 1) * $perPage;

        $total = $query->count();

        $data = $query->select('id', 'page_url', 'ip_address', 'created_at', 'origin', 'type', 'source')
                      ->orderBy('created_at', 'desc')
                      ->offset($offset)
                      ->limit($perPage)
                      ->get();

        return response()->json([
            'current_page' => (int)$currentPage,
            'per_page' => (int)$perPage,
            'total' => $total,
            'data' => $data
        ]);
    }
}
