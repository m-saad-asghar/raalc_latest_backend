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
                'compaign_source' => $request->compaign_source ?? '',
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
    $origin = $request->get('origin');
    $ad_number = $request->get('ad_number');
    $compaign_source = $request->get('compaign_source');
    $type = $request->get('type');
    $source = $request->get('source');
    $ipAddress = $request->get('ip_address');
    $pageUrl = $request->get('page_url');
    $dateRange = $request->get('date_range');
    $leadType = $request->get('lead_type');

    // Shared base query with all filters
    $baseQuery = function ($query) use ($origin, $type, $source, $ipAddress, $pageUrl, $ad_number, $compaign_source, $dateRange, $leadType) {
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
        if ($compaign_source) {
            $query->where('compaign_source', 'LIKE', "%$compaign_source%");
        }
     if ($leadType) {
    if ($leadType == 'organic') {
        $query->where(function ($query) {
    $query->where('page_url', 'NOT LIKE', '%utm_campaign%')
          ->where('page_url', 'NOT LIKE', '%gad_campaignid%')
          ->where('page_url', 'NOT LIKE', '%gad_source%');
});
    } elseif ($leadType == 'non_organic') {
   $query->where(function ($query) {
    $query->where('page_url', 'LIKE', '%utm_campaign%')
          ->orWhere('page_url', 'LIKE', '%gad_campaignid%');
});
}
}
        if (is_array($dateRange) && count($dateRange) === 2) {
            $query->whereBetween('created_at', [
                Carbon::parse($dateRange[0])->startOfDay(),
                Carbon::parse($dateRange[1])->endOfDay(),
            ]);
        }
    };

    // Data query
    $query = DB::table('logs');
    $baseQuery($query);

    $total = (clone $query)->count();

    $data = (clone $query)
        ->select('id', 'page_url', 'ip_address', 'created_at', 'origin', 'type', 'source', 'ad_number', 'compaign_source')
        ->orderBy('created_at', 'desc')
        ->offset($offset)
        ->limit($perPage)
        ->get();

    // Source summary
    $sourceCounts = DB::table('logs')
        ->select('compaign_source', DB::raw('count(*) as total'))
        ->whereIn('compaign_source', [
            'Google_Ads',
            'gbp',
            'chatgpt.com',
            'clutch.co',
            'Facebook',
            'Instagram'
        ])
        ->where(function ($query) use ($baseQuery) {
            $baseQuery($query);
        })
        ->groupBy('compaign_source');

  $organicCount = DB::table('logs')
    ->select(DB::raw("'organic' as compaign_source"), DB::raw('count(*) as total'))
    ->where(function ($query) {
        $query->where(function ($query) {
    $query->where('page_url', 'NOT LIKE', '%utm_campaign%')
          ->where('page_url', 'NOT LIKE', '%gad_campaignid%')
          ->where('page_url', 'NOT LIKE', '%gad_source%');
});
        // $query->whereNull('page_url')
        //       ->orWhere('page_url', 'NOT LIKE', '%utm_campaign%');
    })
    // ->where(function ($query) {
    //     $query->whereNull('compaign_source')
    //           ->orWhere('compaign_source', '');
    // })
    ->where(function ($query) use ($baseQuery) {
        $baseQuery($query);
    });


    $combined = $sourceCounts
        ->unionAll($organicCount)
        ->get()
        ->mapWithKeys(function ($item) {
            $key = $item->compaign_source ?? 'organic';
            return [$key => $item->total];
        });

    return response()->json([
        'current_page' => (int) $currentPage,
        'per_page' => (int) $perPage,
        'total' => $total,
        'data' => $data,
        'source_summary' => $combined,
    ]);
}


//     public function getLogs(Request $request)
//     {
//         $perPage = $request->get('per_page', 15);
// $currentPage = $request->get('page', 1);
// $offset = ($currentPage - 1) * $perPage;
// $origin = $request->get('origin');
// $ad_number = $request->get('ad_number');
// $compaign_source = $request->get('compaign_source');
// $type = $request->get('type');
// $source = $request->get('source');
// $ipAddress = $request->get('ip_address');
// $pageUrl = $request->get('page_url');
// $dateRange = $request->get('date_range');

// $query = DB::table('logs');

// if ($origin) {
//     $query->where('origin', $origin);
// }
// if ($type) {
//     $query->where('type', $type);
// }
// if ($source) {
//     $query->where('source', $source);
// }
// if ($ipAddress) {
//     $query->where('ip_address', 'LIKE', "%$ipAddress%");
// }
// if ($pageUrl) {
//     $query->where('page_url', 'LIKE', "%$pageUrl%");
// }
// if ($ad_number) {
//     $query->where('ad_number', 'LIKE', "%$ad_number%");
// }
// if ($compaign_source) {
//     $query->where('compaign_source', 'LIKE', "%$compaign_source%");
// }
// if (is_array($dateRange) && count($dateRange) === 2) {
//     $query->whereBetween('created_at', [
//         Carbon::parse($dateRange[0])->startOfDay(),
//         Carbon::parse($dateRange[1])->endOfDay(),
//     ]);
// }

// $total = (clone $query)->count();

// $data = (clone $query)
//     ->select('id', 'page_url', 'ip_address', 'created_at', 'origin', 'type', 'source', 'ad_number', 'compaign_source')
//     ->orderBy('created_at', 'desc')
//     ->offset($offset)
//     ->limit($perPage)
//     ->get();

// $sourceCounts = DB::table('logs')
//     ->select('compaign_source', DB::raw('count(*) as total'))
//     ->whereIn('compaign_source', [
//         'Google_Ads',
//         'gbp',
//         'chatgpt.com',
//         'clutch.co',
//         'Facebook',
//         'Instagram'
//     ])
//     ->when(is_array($dateRange) && count($dateRange) === 2, function ($query) use ($dateRange) {
//         $query->whereBetween('created_at', [
//             Carbon::parse($dateRange[0])->startOfDay(),
//             Carbon::parse($dateRange[1])->endOfDay()
//         ]);
//     })
//     ->groupBy('compaign_source');


// $organicCount = DB::table('logs')
//     ->select(DB::raw("'organic' as compaign_source"), DB::raw('count(*) as total'))
//     ->where(function($query) {
//         $query->whereNull('compaign_source')
//               ->orWhere('compaign_source', '');
//     })
//     ->when(is_array($dateRange) && count($dateRange) === 2, function ($query) use ($dateRange) {
//         $query->whereBetween('created_at', [
//             Carbon::parse($dateRange[0])->startOfDay(),
//             Carbon::parse($dateRange[1])->endOfDay()
//         ]);
//     });


// $combined = $sourceCounts
//     ->unionAll($organicCount)
//     ->get()
//     ->mapWithKeys(function ($item) {
//         $key = $item->compaign_source ?? 'organic';
//         return [$key => $item->total];
//     });

// return response()->json([
//     'current_page' => (int) $currentPage,
//     'per_page' => (int) $perPage,
//     'total' => $total,
//     'data' => $data,
//     'source_summary' => $combined,
// ]);
//     }

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
