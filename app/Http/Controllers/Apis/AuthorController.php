<?php

namespace App\Http\Controllers\Apis;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Author;
use App\Models\AuthorTranslation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Illuminate\Support\Str;

class AuthorController extends Controller
{
    public function __construct()
    {
        $excludedRoutes = [
            'authorsList',
            'authorFetch',
        ];

        $currentRoute = request()->route()->getName();

        if (!in_array($currentRoute, $excludedRoutes)) {
            try {
                $this->user = JWTAuth::parseToken()->authenticate();
            } catch (TokenExpiredException $e) {
                return response()->json(['status' => 'false', 'message' => 'Token has expired'], Response::HTTP_UNAUTHORIZED);
            } catch (TokenInvalidException $e) {
                return response()->json(['status' => 'false', 'message' => 'Token is invalid'], Response::HTTP_UNAUTHORIZED);
            } catch (JWTException $e) {
                return response()->json(['status' => 'false', 'message' => 'Token error: Could not decode token: ' . $e->getMessage()], Response::HTTP_UNAUTHORIZED);
            } catch (\Exception $e) {
                return response()->json(['status' => 'false', 'message' => 'Token error: ' . $e->getMessage()], Response::HTTP_UNAUTHORIZED);
            }

            if (!$this->user || !$this->user->isSuperAdmin()) {
                return response()->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
            }
        }
    }

    /**
     * List all authors
     */
    public function index($lang)
    {
        try {
            $authors = Author::where('active', 1)->orderBy('id', 'DESC')->get();

            if ($authors->isEmpty()) {
                return response()->json(['status' => 'false', 'message' => 'No authors found'], Response::HTTP_NOT_FOUND);
            }

            $authorsWithTranslations = $authors->map(function ($author) use ($lang) {
                $translation = AuthorTranslation::where('author_id', $author->id)
                    ->where('lang', $lang)
                    ->first();

                if (empty($translation)) {
                    $translation = AuthorTranslation::where('author_id', $author->id)
                        ->where('lang', 'en')
                        ->first();
                }

                $image = "";
                if (!empty($author->image) && $author->image != null) {
                    $image = $this->getImageUrl($author->image);
                }

                $name = $designation = $bio = "N/A";

                if ($translation) {
                    $fields_value = json_decode($translation->fields_value, true);
                    $name = $fields_value['name'] ?? "N/A";
                    $designation = $fields_value['designation'] ?? "N/A";
                    $bio = $fields_value['bio'] ?? "N/A";
                }

                return [
                    'id' => $author->id,
                    // 'slug' => $author->slug,
                    'image' => $image,
                    'active' => $author->active,
                    'name' => $name,
                    'designation' => $designation,
                    'bio' => $bio,
                ];
            });

            return response()->json([
                'status' => 'true',
                'data' => $authorsWithTranslations,
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'false',
                'message' => 'An error occurred: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Store a new author
     */
    public function store(Request $request)
    {
        $rules = [
            // 'slug' => 'required|string|unique:authors,slug',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp',
            'active' => 'nullable|boolean',
            'translations' => 'required|array',
            'translations.en' => 'required|array',
            'translations.en.fields_value' => 'required|array',
            'translations.en.fields_value.name' => 'required|string',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $errorMessages = implode("\n", $validator->errors()->all());
            return response()->json([
                'status' => 'false',
                'message' => $errorMessages
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        DB::beginTransaction();

        try {
            $author = new Author();
            // $author->slug = $request->input('slug');
            $author->active = $request->input('active', 1);

            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('author_images', 'public');
                $author->image = $imagePath;
            }

            $author->save();

            // Save translations
            $translations = $request->input('translations', []);
            foreach ($translations as $lang => $translationData) {
                $fieldsValue = $translationData['fields_value'] ?? [];
                $authorTranslation = new AuthorTranslation();
                $authorTranslation->author_id = $author->id;
                $authorTranslation->lang = $lang;
                $authorTranslation->fields_value = json_encode($fieldsValue);
                $authorTranslation->save();
            }

            DB::commit();

            return response()->json(['status' => 'true', 'message' => 'Author created successfully'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'false', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Show a single author
     */
    public function show($slug, $lang)
    {
        
        try {
            // $author = Author::where('slug', $slug)->first();

            // if (!$author) {
                $author = Author::where('id', $slug)->first();
                if (!$author) {
                    return response()->json(['status' => 'false', 'message' => 'Author not found'], Response::HTTP_NOT_FOUND);
                }
            // }

            $image = null;
            if (!empty($author->image) && $author->image != null) {
                $image = $this->getImageUrl($author->image);
            }

            // Fetch all translations (en + ar)
            $translations = AuthorTranslation::where('author_id', $author->id)->get();
            $translationsData = [];
            foreach ($translations as $translation) {
                $fieldsValue = json_decode($translation->fields_value, true);
                $translationsData[$translation->lang] = [
                    'lang' => $translation->lang,
                    'fields_value' => [
                        'name' => $fieldsValue['name'] ?? "",
                        'designation' => $fieldsValue['designation'] ?? "",
                        'bio' => $fieldsValue['bio'] ?? "",
                    ]
                ];
            }

            return response()->json([
                'status' => 'true',
                'data' => [
                    'id' => $author->id,
                    // 'slug' => $author->slug,
                    'image' => $image,
                    'active' => $author->active,
                    'translations' => $translationsData,
                ]
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'false',
                'message' => 'An error occurred: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update an existing author
     */
    public function update($id, Request $request)
    {
        $rules = [
            // 'slug' => 'nullable|string|unique:authors,slug,' . $id,
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp',
            'active' => 'nullable|boolean',
            'translations' => 'nullable|array',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'false',
                'message' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        DB::beginTransaction();

        try {
            $author = Author::find($id);
            if (!$author) {
                return response()->json(['status' => 'false', 'message' => 'Author not found'], Response::HTTP_NOT_FOUND);
            }

            // if ($request->has('slug')) {
            //     $author->slug = $request->input('slug');
            // }

            if ($request->has('active')) {
                $author->active = $request->input('active');
            }

            if ($request->hasFile('image')) {
                if ($author->image) {
                    Storage::disk('public')->delete($author->image);
                }
                $imagePath = $request->file('image')->store('author_images', 'public');
                $author->image = $imagePath;
            }

            $author->save();

            // Update translations
            $translations = $request->input('translations', []);
            foreach ($translations as $lang => $translationData) {
                $fieldsValue = $translationData['fields_value'] ?? [];
                AuthorTranslation::updateOrCreate(
                    ['author_id' => $id, 'lang' => $lang],
                    ['fields_value' => json_encode($fieldsValue)]
                );
            }

            DB::commit();

            return response()->json(['status' => 'true', 'message' => 'Author updated successfully'], Response::HTTP_OK);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'false', 'message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Soft delete an author (set active = 0)
     */
    public function destroy($id)
    {
        DB::beginTransaction();

        try {
            $author = Author::find($id);
            if (!$author) {
                return response()->json(['status' => 'false', 'message' => 'Author not found'], Response::HTTP_NOT_FOUND);
            }

            $author->active = 0;
            $author->save();

            DB::commit();

            return response()->json(['status' => 'true', 'message' => 'Author deleted successfully'], Response::HTTP_OK);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'false', 'message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get image URL helper
     */
    private function getImageUrl($image_path)
    {
        if (Storage::disk('public')->exists($image_path)) {
            return asset('storage/' . $image_path);
        }
        return asset('storage/' . $image_path);
    }
}
