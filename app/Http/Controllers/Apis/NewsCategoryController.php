<?php

namespace App\Http\Controllers\Apis;

use App\Http\Controllers\Controller;
use App\Models\News;
use App\Models\NewsTranslation;
use App\Models\NewsCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Facades\JWTAuth;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Str;

class NewsCategoryController extends Controller
{
    protected $user;
    protected $newscategory;

    public function __construct()
    {
        $this->newscategory = new NewsCategory();
        // List of routes where JWT authentication should not be applied
        $excludedRoutes = [
            'list',
            'fetch'
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

    // get news categories
    public function getNewsCategories(Request $request)
    {
        try {
            $lang = $request->query('lang', 'en'); // Default to 'en' if not provided

            // Single query to get all active categories
            $newscategories = NewsCategory::where('status', 1)->get();

            // Process data in memory to avoid N+1 queries
            $allData = $newscategories->map(function($category) use($lang) {
                // Decode JSON once per category
                $categoryValues = json_decode($category->news_category_values, true);
                
                if (empty($categoryValues) || !is_array($categoryValues)) {
                    return null;
                }

                $categoryTitle = null;
                
                // Try to get the requested language first
                if (isset($categoryValues[$lang]['category_title']) && !empty($categoryValues[$lang]['category_title'])) {
                    $categoryTitle = $categoryValues[$lang]['category_title'];
                } 
                // Fallback to English if requested language not available
                else if (isset($categoryValues['en']['category_title']) && !empty($categoryValues['en']['category_title'])) {
                    $categoryTitle = $categoryValues['en']['category_title'];
                }

                // Return null if no valid title found
                if (empty($categoryTitle)) {
                    return null;
                }

                return [
                    'id' => $category->id,
                    'category_title' => $categoryTitle
                ];
            })->filter(); // Remove null values

            return response()->json([
                'status' => 'true',
                'data' => $allData->values()
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'false', 'message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // get one category by id
    public function getCategoryById(Request $request, $id)
    {
        try {
            $lang = $request->query('lang', 'en'); // Default to 'en' if not provided
            
            // Single query to get the category
            $category = NewsCategory::where('id', $id)
                ->where('status', 1)
                ->first();

            if (!$category) {
                return response()->json(['status' => 'false', 'message' => 'News category not found'], Response::HTTP_NOT_FOUND);
            }

            // Decode JSON once and process in memory
            $categoryValues = json_decode($category->news_category_values, true);
            
            if (empty($categoryValues) || !is_array($categoryValues)) {
                return response()->json(['status' => 'false', 'message' => 'News category data not found'], Response::HTTP_NOT_FOUND);
            }

            $categoryTitle = null;
            
            // Try to get the requested language first
            if (isset($categoryValues[$lang]['category_title']) && !empty($categoryValues[$lang]['category_title'])) {
                $categoryTitle = $categoryValues[$lang]['category_title'];
            } 
            // Fallback to English if requested language not available
            else if (isset($categoryValues['en']['category_title']) && !empty($categoryValues['en']['category_title'])) {
                $categoryTitle = $categoryValues['en']['category_title'];
            }

            if (empty($categoryTitle)) {
                return response()->json(['status' => 'false', 'message' => 'News category translation not found'], Response::HTTP_NOT_FOUND);
            }

            return response()->json([
                'status' => 'true',
                'data' => [
                    'category_title' => $categoryTitle
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'false', 'message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}

