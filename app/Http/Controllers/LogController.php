<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Carbon\Carbon;

class LogController extends Controller
{
    public function saveLog(Request $request)
    {
        try {

            if (empty($adNumber) && $request->has('page_url')) {
                $parsedUrl = parse_url($request->page_url);
            
                if (isset($parsedUrl['query'])) {
                    parse_str($parsedUrl['query'], $queryParams);
            
                    if (isset($queryParams['gad_campaignid'])) {
                        $adNumber = $queryParams['gad_campaignid'];
                    }
                }
            }

            if (empty($compaignSource) && $request->has('page_url')) {
                $parsedUrl = parse_url($request->page_url);
            
                if (isset($parsedUrl['query'])) {
                    parse_str($parsedUrl['query'], $queryParams);
            
                    if (isset($queryParams['utm_source'])) {
                        $compaignSource = $queryParams['utm_source'];
                    }
                }
            }

            DB::table('logs')->insert([
                'page_url'   => $request->page_url,
                'origin'     => $request->origin,
                'ad_number'  => $adNumber ?? '',
                'compaign_source'  => $compaignSource ?? '',
                // 'ad_number' => $request->ad_number ?? '',
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

    public function saveLogs(Request $request)
    {
        try {
            DB::table('logs')->insert([
                'page_url'   => $request->page_url,
                'origin'     => $request->origin,
                'ad_number' => $request->ad_number ?? '',
                'source'     => $request->source,
                'type'     => $request->type,
                'message'    => $request->message,
                'ip_address' => $request->ip(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
    
            return redirect()->away('https://wa.me/97145693370?text=' . urlencode('Need legal support? Chat with RAALC on WhatsApp'));
    
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
        $currentPage = $request->get('page', 1);
        $offset = ($currentPage - 1) * $perPage;
    
        // Retrieve all filters
        $origin = $request->get('origin');
        $ad_number = $request->get('ad_number');
        $type = $request->get('type');
        $source = $request->get('source');
        $ipAddress = $request->get('ip_address');
        $pageUrl = $request->get('page_url');
        $dateRange = $request->get('date_range');
    
        // Build the query
        $query = DB::table('logs');
    
        if ($origin) {
            $query->where('origin', $origin);
        }
    
        if ($type) {
            $query->where('type', $type);
        }
    
        if ($source) {
            $query->where('source', $source);
        }
    
        if ($ipAddress) {
            $query->where('ip_address', 'LIKE', "%$ipAddress%");
        }
    
        if ($pageUrl) {
            $query->where('page_url', 'LIKE', "%$pageUrl%");
        }

        if ($ad_number) {
            $query->where('ad_number', 'LIKE', "%$ad_number%");
        }

        if (is_array($dateRange) && count($dateRange) === 2) {
            $query->whereBetween('created_at', [
                Carbon::parse($dateRange[0])->startOfDay(),
                Carbon::parse($dateRange[1])->endOfDay(),  
            ]);
        }
    
        $total = $query->count();
    
        $data = $query->select('id', 'page_url', 'ip_address', 'created_at', 'origin', 'type', 'source', 'ad_number')
                      ->orderBy('created_at', 'desc')
                      ->offset($offset)
                      ->limit($perPage)
                      ->get();
    
        return response()->json([
            'current_page' => (int) $currentPage,
            'per_page' => (int) $perPage,
            'total' => $total,
            'data' => $data,
        ]);
    }

    public function getLogsLatestRecord(Request $request)
    {
        $latestLog = DB::table('logs')
        ->orderBy('created_at', 'desc')
        ->first();

    return response()->json([
        'data' => $latestLog,
    ]);
    }
}
