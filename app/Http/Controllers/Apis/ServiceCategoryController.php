<?php

namespace App\Http\Controllers\Apis;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\ServiceTranslation;
use App\Models\ServiceCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Facades\JWTAuth;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Str;

class ServiceCategoryController extends Controller
{
    protected $user;
    protected $servicecategory;

    public function __construct()
    {
        $this->servicecategory = new ServiceCategory();
        // List of routes where JWT authentication should not be applied
        $excludedRoutes = [
            'list',
            'fetch',
            'fetchServices'
        ];
    
        // Get current route name
        $currentRoute = request()->route()->getName();
    
        // Check if the current route is excluded
        if (!in_array($currentRoute, $excludedRoutes)) {
            try {
                // Get the currently authenticated user
                $this->user = JWTAuth::parseToken()->authenticate();
            } catch (TokenExpiredException $e) {
                return response()->json(['error' => 'Token error: Token has expired'], Response::HTTP_UNAUTHORIZED); // HTTP 401
            } catch (TokenInvalidException $e) {
                return response()->json(['error' => 'Token error: Token is invalid'], Response::HTTP_UNAUTHORIZED); // HTTP 401
            } catch (JWTException $e) {
                return response()->json(['error' => 'Token error: Could not decode token: ' . $e->getMessage()], Response::HTTP_UNAUTHORIZED); // HTTP 401
            } catch (\Exception $e) {
                return response()->json(['error' => 'Token error: ' . $e->getMessage()], Response::HTTP_UNAUTHORIZED); // HTTP 401
            }
    
            if (!$this->user  || !$this->user->isSuperAdmin()) {
                return response()->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
            }
        }
    }


    // create service category
    public function createServiceCategory(Request $request, $lang)
    {
        try {

            $validator = Validator::make($request->all(), [
                'category_title' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 403,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
            $servicecategory = $this->servicecategory::where('service_category_values->' . $lang . '->category_title', $request->category_title)->first();

            // return $servicecategory;
            if (!$servicecategory) {
                $values[$lang] = [
                    'category_title' => $request->category_title,
                ];

                $servicecategory = new ServiceCategory();

                foreach (['en', 'ar', 'ru', 'ch'] as $lng) {
                    if (!isset($values[$lng])) {
                        $values[$lng] = ['category_title' => null];
                    }
                }

                $servicecategory->service_category_values = json_encode($values, JSON_UNESCAPED_UNICODE);
                $servicecategory->save();
            }

            return response()->json([
                'status' => 'true',
                'data' => 'Service category create success'
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json(['status' => 'false', 'message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // update service category
    public function updateServiceCategory(Request $request, $lang, $id)
    {
        try {

            $validator = Validator::make($request->all(), [
                'category_title' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 403,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
            // $servicecategory = $this->servicecategory::where('service_category_values->'.$lang.'->category_title',$request->category_title)->first();
            $servicecategory = $this->servicecategory::where('id', $id)->first();

            // return $servicecategory;
            if ($servicecategory) {
                $values[$lang] = [
                    'category_title' => $request->category_title,
                ];

                foreach (['en', 'ar', 'ru', 'ch'] as $lng) {
                    if (!isset($values[$lng])) {
                        $values[$lng] = ['category_title' => null];
                    }
                }

                $jsonDecode = json_decode($servicecategory->service_category_values, true);

                foreach ($jsonDecode as $key => $val) {
                    if ($key == $lang) {
                        $jsonDecode[$key]['category_title'] = $request->category_title;
                    }
                }

                $servicecategory->service_category_values = json_encode($jsonDecode, JSON_UNESCAPED_UNICODE);

                $servicecategory->save();
            }

            return response()->json([
                'status' => 'true',
                'data' => 'Service category update success'
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json(['status' => 'false', 'message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // get service categories
    public function getServiceCategories($lang)
    {
        try {

            $servicecategories = ServiceCategory::all();

            $allData = $servicecategories->map(function($service) use($lang) {
                
                $dataArray = array();

                $translation = ServiceCategory::whereNotNull('service_category_values->' . $lang . '->category_title')
                                    ->where('id', $service->id)
                                    ->first();

                // If no translation found in the requested language, fall back to English
                if (empty($translation)) {
                    $translation = ServiceCategory::whereNotNull('service_category_values->' . 'en' . '->category_title')
                                    ->where('id', $service->id)
                                    ->first();
                }
            
                // Decode the field values for the selected translation
                $field_value = json_decode($translation->service_category_values, true);
                // return $field_value;
            
                foreach($field_value as $key => $data){
                    // $key =
                    if($key == $lang && $data['category_title'] != null){
                        $dataArray = [
                            'id' => $service->id,
                            'category_title' => $data['category_title']
                        ];
                    }else if($key == 'en' && $data['category_title'] != null){
                        $dataArray = [
                            'id' => $service->id,
                            'category_title' => $data['category_title']
                        ];
                    }
                }

                return $dataArray;
            });

            
            return response()->json([
                'status' => 'true',
                'data' => $allData
            ]);
        } catch (\Exception $e) {
            return $e;
            return response()->json(['status' => 'false', 'message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    // services relates to category
    public function fetchServicesRelatesCategory($lang)
    {
        try {

            $dataArray  = array();
            
            
            // // rvicecategories = $this->servicecategory::whereNotNull('service_category_values->' . $lang . '->category_title')->get();
            // // (count($servicecategories) == 0) {
            // //  return response()->json(['status' => 'false', 'message' => 'Content not found'], Response::HTTP_NOT_FOUND);
            // // 

            // // taJoin = DB::table('service_categories')->whereNotNull('service_category_values->' . $lang . '->category_title')
            // //  ->leftJoin('services', 'services.service_category_id', '=', 'service_categories.id')
            // //  ->leftJoin('service_translations', 'service_translations.service_id', '=', 'services.id')
            // //  ->select('service_translations.id AS trans_id', 'services.*', 'service_translations.*', 'service_categories.*')
            // //  ->get();

            // // each ($dataJoin as $value) {
            // //  $jsonDecode = json_decode($value->service_category_values, true);
            // //  $translateDecode = json_decode($value->translated_value, true);
            // //  foreach ($jsonDecode as $index => $data) {

            // //      if(!empty($data['category_title'])){
            // //          if(!isset($dataArray[$data['category_title']]) && $value->trans_id == null){
            // //              $dataArray[$data['category_title']] = [];
            // //          }

            // //          if ($index === $lang && $value->trans_id !== null) {
            // //              $dataArray[$data['category_title']][] = [
            // //                  'id' => $value->trans_id,
            // //                  'service_title' => $translateDecode['sec_one_heading_one']
            // //              ];
            // //          }
            // //      }     
                    
            // //  }
            // // 
            
            
            
            $services = Service::orderByRaw('ISNULL(service_category_id), service_category_id ASC')
                            ->orderBy('id', 'ASC')
                            ->get();
            
             // Check if any teams are found
            if ($services->isEmpty()) {
                return response()->json(['status' => 'false', 'message' => 'Service not found'], Response::HTTP_NOT_FOUND);
            }
    
            // Retrieve translations for each department
            $fetchServicesMenu = $services->reduce(function ($dataArray, $service) use ($lang) {
                $id = $service->id;
                $service_id = $service->id;
                $translation = ServiceTranslation::where('service_id', $id)
                    ->where('language', $lang)
                    ->first();
            
                $translatedData = [];
                if (!empty($translation)) {
                    // Decode the JSON translation data
                    $translatedData = json_decode($translation->translated_value, true);
                } else {
                    // For Default Language Data Fetch
                    $defaultData = ServiceTranslation::where('service_id', $id)
                        ->where('language', 'en')
                        ->first();
            
                    if (!empty($defaultData)) {
                        // Decode the JSON translation data
                        $translatedData = json_decode($defaultData->translated_value, true);
                    }
                }
            
                $title = "";
                $relatedServices = [];  // Initialize or reset $relatedServices for each service
            
                if (!empty($service->service_category_id) && $service->service_category_id != null) {
                    $service_category_id = $service->service_category_id;
                    
                    $categoryData = $this->servicecategory::whereNotNull('service_category_values->' . $lang . '->category_title')
                        ->where('id', $service_category_id)
                        ->selectRaw('JSON_UNQUOTE(JSON_EXTRACT(service_category_values, \'$."' . $lang . '".category_title\')) as category_title')
                        ->first();
                    
                    $title = $categoryData->category_title;
                    $id = (int) $service_category_id;
                    
                    $dataArray[$title]['íd'] = $id;
                    $dataArray[$title]['data'][] = [
                        'id' => $service->id,
                        'service_title' => $translatedData['sec_one_heading_one'] ?? $title,
                    ];
                    
                } else {
                    $title = $translatedData['sec_one_heading_one'];
                    $dataArray[$title]['íd'] = $id;
                    $dataArray[$title]['data'] = [];
                }
            
                return $dataArray;
            }, []);


            return response()->json([
                'status' => 'true',
                'data' => $fetchServicesMenu
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'false', 'message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    //combine content function
    public function combineServicesRelatesCategory($lang)
    {
        try {

            $dataArray  = array();

            $services = Service::orderByRaw('ISNULL(service_category_id), service_category_id ASC')
                             ->where('active', 1)
                            ->orderBy('id', 'ASC')
                            ->get();
            
             // Check if any teams are found
            if ($services->isEmpty()) {
                return response()->json(['status' => 'false', 'message' => 'Service not found'], Response::HTTP_NOT_FOUND);
            }

            /* -------------------------------------------------------------
             * N+1 fix: bulk pre-load all ServiceTranslation rows for the
             * services on this page in a single query (current language and
             * 'en' fallback), keyed by [service_id][language] for O(1)
             * lookups inside the reduce() loop below.
             * ------------------------------------------------------------- */
            $serviceIds = $services->pluck('id')->all();

            $translationsByService = ServiceTranslation::whereIn('service_id', $serviceIds)
                ->whereIn('language', array_unique([$lang, 'en']))
                ->get(['service_id', 'language', 'translated_value'])
                ->groupBy('service_id')
                ->map(function ($rows) {
                    return $rows->keyBy('language');
                });

            /* -------------------------------------------------------------
             * N+1 fix: bulk pre-load every referenced ServiceCategory row in
             * a single query (with the current language and 'en' fallback
             * extracted as JSON columns), keyed by category id.
             * ------------------------------------------------------------- */
            $categoryIds = $services->pluck('service_category_id')->filter()->unique()->values()->all();

            $categoriesById = collect();
            if (!empty($categoryIds)) {
                $categoriesById = $this->servicecategory::whereIn('id', $categoryIds)
                    ->selectRaw(
                        'id, '
                        . 'JSON_UNQUOTE(JSON_EXTRACT(service_category_values, \'$."' . $lang . '".category_title\')) as category_title_lang, '
                        . 'JSON_UNQUOTE(JSON_EXTRACT(service_category_values, \'$."en".category_title\')) as category_title_en'
                    )
                    ->get()
                    ->keyBy('id');
            }

            // Retrieve translations for each department
            $fetchServicesMenu = $services->reduce(function ($dataArray, $service) use ($lang, $translationsByService, $categoriesById) {

                $id = $service->id;
                $service_id = $service->id;

                // Resolve translation from the pre-loaded map (lang -> en fallback).
                $translation = null;
                if (isset($translationsByService[$id])) {
                    $translation = $translationsByService[$id][$lang]
                        ?? $translationsByService[$id]['en']
                        ?? null;
                }

                $translatedData = [];
                if (!empty($translation)) {
                    $translatedData = json_decode($translation->translated_value, true);
                }

                $title = "";
                $relatedServices = [];  // Initialize or reset $relatedServices for each service
            
                if (!empty($service->service_category_id) && $service->service_category_id != null) {
                    $service_category_id = $service->service_category_id;

                    // Resolve category title from pre-loaded map (lang -> en fallback).
                    $categoryRow = $categoriesById->get($service_category_id);
                    $categoryTitle = null;
                    if ($categoryRow) {
                        $categoryTitle = !empty($categoryRow->category_title_lang)
                            ? $categoryRow->category_title_lang
                            : (!empty($categoryRow->category_title_en) ? $categoryRow->category_title_en : null);
                    }

                    // Preserve original behaviour: if the category title is
                    // missing for both languages, fail loudly the same way
                    // the legacy code did (calling ->category_title on null).
                    $title = $categoryTitle;
                    $id = (int) $service_category_id;

                    $dataArray[$title]['íd'] = $id;
                    $dataArray[$title]['data'][] = [
                        'id' => $service->id,
			'slug' => $service->slug,
                        'service_title' => $translatedData['sec_one_heading_one'] ?? $title,
                    ];
                    
                } else {
                    $title = $translatedData['sec_one_heading_one'];
                    $dataArray[$title] = [
                        'id' => $service->id,
			'slug' => $service->slug,
                        'data' => []
                    ];
                }
            
                return $dataArray;
            }, []);


            return response()->json([
                'status' => 'true',
                'data' => $fetchServicesMenu
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'false', 'message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // get one category by id
    public function getCategoryById($lang,$id)
    {
        try {
            $dataArray = array();
            $servicecategories = $this->servicecategory::where('id', $id)
            ->whereNotNull('service_category_values->' . $lang . '->category_title')
            ->first();

            $jsonDecode =  json_decode($servicecategories->service_category_values, true);

            foreach($jsonDecode as $key => $data){
                if($key === $lang)
                    $dataArray['category_title'] = $data['category_title'];
                    
            }

            return response()->json([
                'status' => 'true',
                'data' => $dataArray
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'false', 'message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /* service category tab data delete part DELETE/{id} */
    public function deleteServiceCategory($id)
    {
        try {

            $servicecategory = $this->servicecategory::find($id);

            if (!$servicecategory) {
                return response()->json(['status' => 'false', 'message' => 'Service category data not found'], Response::HTTP_NOT_FOUND);
            }

            $servicecategory->delete();
            return response()->json(['status' => 'true', 'message' => 'Service category deleted successfully'], Response::HTTP_OK);
        } catch (\Exception $ex) {
            return response()->json([
                'status' => 'false',
                'message' => 'An error occurred: ' . $ex->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
