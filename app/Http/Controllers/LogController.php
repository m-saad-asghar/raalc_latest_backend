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
}
