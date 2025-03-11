<?php

namespace App\Http\Controllers\Apis;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Booking;
use App\Models\BookingNotification;
use App\Models\TimeSlot;
use App\Models\Team;
use App\Models\TeamTranslation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use App\Mail\BookMeetingMail;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use App\Events\MessageSent;
use Illuminate\Support\Facades\Broadcast;

class BookingController extends Controller
{
    public function __construct() { 
        // List of routes where JWT authentication should not be applied
        $excludedRoutes = [
            'register',
            'list',
            'listSearch'
        ];
    
        // Get current route name
        $currentRoute = request()->route()->getName();
    
        // Check if the current route is excluded
        if (!in_array($currentRoute, $excludedRoutes)) {
            // Handle JWT token validation and user authentication
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
            
            if (!$this->user  || !$this->user->isSuperAdmin()) {
                return response()->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
            }
        }
    }
    
    /**
     * Store a newly created resource in storage.
     */
     public function meetingStore($lang, Request $request)
    {   
        DB::beginTransaction(); // Start transaction

        try {
            
            // Define validation rules
            $rules = [
                'client_id'  => 'nullable|numeric',
                'client_name'  => 'required|string',
                'client_email' => 'required|string',
                'client_phone' => 'nullable|string',
                'meeting_date' => 'required|date',
                'time_slot' => 'required|numeric',
                'number_of_attendees' => 'required|numeric',
            ];
        
            // Create a validator instance with the request data and rules
            $validator = Validator::make($request->all(), $rules);
        
            // Check if validation fails
            if ($validator->fails()) {
                // Collect all error messages into a single string with line breaks
                $errorMessages = implode("\n", $validator->errors()->all());
                return response()->json([
                    'status' => 'false',
                    'message' => $errorMessages
                ], Response::HTTP_UNPROCESSABLE_ENTITY); // HTTP 422
            }
            $client_id = $request->input('client_id');
            $recipientEmail = $request->input('client_email');
            // Insert into booking table
            Booking::create([
                    'client_id' => $client_id,
                    'client_name' => $request->input('client_name'),
                    'client_email' => $request->input('client_email'),
                    'client_phone' => $request->input('client_phone', null),
                    'consultant_id' => $request->input('consultant_id'),
                    'meeting_date' => $request->input('meeting_date'),
                    'time_slot' => $request->input('time_slot'),
                    'number_of_attendees' => $request->input('number_of_attendees'),
                    'booking_status' => 'booked',
                    'meeting_purpose' => $request->input('meeting_purpose'),
                    'language' => $lang,
                ]);
            
            DB::commit(); // Commit transaction
            
            $team_id = $request->input('consultant_id');
            
            // Retrieve the team memeber
            $team = Team::find($team_id);
            
            $consultant_image = null;
            if(!empty($team->lowyer_image) && $team->lowyer_image != null){
                $consultant_image = $this->getImageUrl($team->lowyer_image);
            }
           
            // Retrieve the team translation
            $translation = TeamTranslation::where('team_id', $team_id)
                ->where('lang', $lang)
                ->first();
            if (empty($translation)) {
                // For Defualt Language Data Fetch
                $defaultData = TeamTranslation::where('team_id', $team_id)
                ->where('lang', 'en')
                ->first();
                    
                if (!empty($defaultData)) {
                    $translation = $defaultData;
                }    
            }   
            
            $consultant_name = $consultant_designation = "";
            // Check if translation is found
            if ($translation) {
                // Decode the JSON data
                $fields_value = json_decode($translation->fields_value, true);
                $consultant_name = $fields_value['name'] ?? "";
                $consultant_designation = $fields_value['designation'] ?? "";
            }
            
             // Convert the date format
            $formattedDate = Carbon::createFromFormat('Y-m-d', $request->input('meeting_date'))->format('d M Y');
            
            $slot_id = $request->input('time_slot');
            $slot_data = TimeSlot::where('id', $slot_id)->first();
            $time_slot = $slot_data->from_time.' To '.$slot_data->to_time;
            
            // Combine order details, tracking data, and order items
            $bookingDetail = [
                    'client_id' => $client_id,
                    'client_name' => $request->input('client_name'),
                    'client_email' => $request->input('client_email'),
                    'client_phone' => $request->input('client_phone', null),
                    'consultant_name' => $consultant_name,
                    'consultant_designation' => $consultant_designation,
                    'consultant_image' => $consultant_image,
                    'meeting_date' => $formattedDate,
                    'time_slot' => $time_slot,
                    'description' => "",
                    'number_of_attendees' => $request->input('number_of_attendees'),
                    'meeting_purpose' => $request->input('meeting_purpose'),
                    'booking_status' => 'booked',
                    'change_status' => false
                ];
            
            // Define messages for different languages
            $messages = [
                'en' => 'Booked a meeting successfully.',
                'ar' => 'تم حجز الاجتماع بنجاح.',
                'ru' => 'Встреча успешно забронирована.',
                'ch' => '会议成功预订。',
            ];
            
            // Get the appropriate message
            $message = $messages[$lang];
            
            $send_email = $notification = true;
            if(!empty($client_id) && $client_id != null){
                $clientData = User::where('id', $client_id)->first();
                if ($clientData->email_notification == 0) {
                    $send_email = false;
                }
                
                if ($clientData->push_notification == 0) {
                    $notification = false;
                }
            }
            
            // Broadcast the message event
            event(new MessageSent($bookingDetail));    
            
            if($send_email){    
                $template = 'emails.booking_templates.'.$lang.'_bookMeeting'; // Example for Arabic template
                // Send email to client with booking details
                Mail::to($recipientEmail)->send(new BookMeetingMail($bookingDetail, $template, $lang, false));
                
                // Send email to Admin with booking details
                $isAdmin = config('mail.admin_address');
                Mail::to($isAdmin)->send(new BookMeetingMail($bookingDetail, $template, $lang, true));
            }
            
            if(!empty($team->lawyer_email) && $team->lawyer_email != null){
                $lawyer_email = $team->lawyer_email;    
                // Send email to the consultant/Lawyer
                Mail::to($lawyer_email)->send(new BookMeetingMail($bookingDetail, $template, $lang, true));
            }
            
            return response()->json(['status' => 'true', 'message' => $message,'data' => $bookingDetail], 200);

        } catch (\Exception $e) {
            DB::rollBack(); // Rollback transaction on error
            return response()->json(['status' => 'false', 'message' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Display the specified resource.
     */
    public function meetingList($lang, Request $request, $per_page=12)
    {   
        try {
            // Define validation rules
            $rules = [
                'from_month'  => 'nullable|string',
                'to_month'  => 'nullable|string',
                'booking_status'  => 'nullable|string'
            ];
        
            // Create a validator instance with the request data and rules
            $validator = Validator::make($request->all(), $rules);
        
            // Check if validation fails
            if ($validator->fails()) {
                // Collect all error messages into a single string with line breaks
                $errorMessages = implode("\n", $validator->errors()->all());
                return response()->json([
                    'status' => 'false',
                    'message' => $errorMessages
                ], Response::HTTP_UNPROCESSABLE_ENTITY); // HTTP 422
            }
            $status = $request->input('booking_status');
            $from_month = $request->input('from_month');
            $to_month = $request->input('to_month');
            $client_id = "";
            if($request->has('client_id')){
                $client_id = $request->input('client_id');
            }
            
            // Fetch meeting list
            $bookings = Booking::when(!empty($status), function ($query) use ($status) {
                                return $query->where('booking_status', $status);
                            })
                            ->when(!empty($from_month) && !empty($to_month), function ($query) use ($from_month, $to_month) {
                                return $query->whereRaw("DATE_FORMAT(meeting_date, '%Y-%m-%d') BETWEEN ? AND ?", [$from_month, $to_month]);
                            })
                            ->when(!empty($client_id), function ($query) use ($client_id) {
                                return $query->where('client_id', $client_id);
                            })
                            ->where('language', $lang)
                            ->orderBy('updated_at','DESC')
                            ->paginate($per_page);
    
            if ($bookings->isEmpty()) {
                return response()->json(['status' => 'false', 'message' => 'Booking not found'], 400);
            }
            
            // Retrieve each booking data
            $bookingList = $bookings->map(function ($list) use ($lang) {
                $id = $list->id;
                $team_id = $list->consultant_id;
                $client_id = (int)  $list->client_id;
                $client_name = $list->client_name;
                $client_email = $list->client_email;
		$client_phone = $list->client_phone;
                $number_of_attendees = $list->number_of_attendees;
                $meeting_date = $list->meeting_date;
                $time_slot_id = $list->time_slot;
                $booking_status = $list->booking_status;
                $meeting_purpose = $list->meeting_purpose;
                $description = $list->description;
                
                // Retrieve the team memeber
                $team = Team::find($team_id);
                
                $consultant_image = $consultant_email = null;
                if(!empty($team->lowyer_image) && $team->lowyer_image != null){
                    $consultant_image = $this->getImageUrl($team->lowyer_image);
                }
                
                if(!empty($team->lawyer_email) && $team->lawyer_email != null){
                    $consultant_email = $team->lawyer_email;
                }
                
                // Retrieve the team translation
                $translation = TeamTranslation::where('team_id', $team_id)
                    ->where('lang', $lang)
                    ->first();
                if (empty($translation)) {
                    // For Defualt Language Data Fetch
                    $defaultData = TeamTranslation::where('team_id', $team_id)
                    ->where('lang', 'en')
                    ->first();
                        
                    if (!empty($defaultData)) {
                        $translation = $defaultData;
                    }    
                }   
                
                $consultant_name = $consultant_designation = "";
                // Check if translation is found
                if ($translation) {
                    // Decode the JSON data
                    $fields_value = json_decode($translation->fields_value, true);
                    $consultant_name = $fields_value['name'] ?? "";
                    $consultant_designation = $fields_value['designation'] ?? "";
                }
                
                // Retrieve the notification details
                $notification_record = BookingNotification::where('booking_id', $id)->get();
                $notification_details = [];
                foreach ($notification_record as $detail) {
                    $notification_details[] = [
                        'status' => $detail->booking_status,
                        'notification_description' => $detail->description,
                        'status_changeDateTime' => Carbon::createFromFormat('Y-m-d H:i:s', $detail->created_at)->format('d M Y H:ia')
                    ];
                }
                
                 // Convert the date format
                $formattedDate = Carbon::createFromFormat('Y-m-d', $meeting_date)->format('d M Y');
                
                $slot_id = $time_slot_id;
                $slot_data = TimeSlot::where('id', $slot_id)->first();
                $time_slot = $slot_data->from_time.' To '.$slot_data->to_time;
                
                return [
                        'id' => $id,
                        'client_id' => $client_id,
                        'client_name' => $client_name,
                        'client_email' => $client_email,
			'client_phone' => $client_phone,
                        'consultant_id' => (int) $team_id,
                        'consultant_name' => $consultant_name,
                        'consultant_email' => $consultant_email,
                        'consultant_designation' => $consultant_designation,
                        'consultant_image' => $consultant_image,
                        'meeting_date' => $formattedDate,
                        'time_slot' => $time_slot,
                        'number_of_attendees' => $number_of_attendees,
                        'description' => $description ?? "",
                        'booking_status' => $booking_status,
                        'meeting_purpose' => $meeting_purpose ?? "",
                        'notification_detail' => $notification_details
                    ];
                
            });
    
            return response()->json([
                'status' => 'true',
                'message' => 'Booking record found.',
                'data' => $bookingList,
                'pagination' => [
                        'current_page' => $bookings->currentPage(),
                        'last_page' => $bookings->lastPage(),
                        'per_page' => $bookings->perPage(),
                        'total' => $bookings->total(),
                    ]
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            DB::rollBack(); // Rollback transaction on error
            return response()->json(['status' => 'false', 'message' => $e->getMessage()], 500);
        }
    }
    
    public function meetingStatus($id, $lang, Request $request)
    {
        // Define validation rules
        $rules = [
            'booking_status' => 'required|string',
            'description' => 'nullable|string',
            'meeting_date' => 'nullable|date',
            'time_slot' => 'nullable|numeric',
            'consultant_id' => 'nullable|numeric'
        ];

        // Create a validator instance with the request data and rules
        $validator = Validator::make($request->all(), $rules);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json([
                'status' => 'false',
                'message' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            DB::beginTransaction(); // Start transaction
        
            // Retrieve booking
            $booking = Booking::where('id', $id)
                              ->where('language', $lang)
                              ->first();
            if (!$booking) {
                return response()->json(['status' => 'false', 'message' => 'Booking not found'], Response::HTTP_NOT_FOUND);
            }
        
            // Update booking details
            if ($request->has('booking_status')) {
                $booking->booking_status = $request->input('booking_status');
            }
            
            if ($request->has('description')) {
                $booking->description = $request->input('description');
            }
            
            if ($request->has('meeting_date') && $request->has('time_slot')) {
                $booking->meeting_date = $request->input('meeting_date');
                $booking->time_slot = $request->input('time_slot');
            }
            
            
            if ($request->has('consultant_id')) {
                $booking->consultant_id = $request->input('consultant_id');
            }
            
            $booking->save();
        
            $client_id = (int)  $booking->client_id;
            $recipientEmail = $booking->client_email;
            $team_id = $booking->consultant_id;
            $meeting_date = $booking->meeting_date;
            $slot_id = $booking->time_slot;
            $status = $request->input('booking_status');
            
            // Retrieve the team memeber
            $team = Team::find($team_id);
            
            $consultant_image = $consultant_email= "";
            if(!empty($team->lowyer_image) && $team->lowyer_image != null){
                $consultant_image = $this->getImageUrl($team->lowyer_image);
            }
            
            if(!empty($team->lawyer_email) && $team->lawyer_email != null){
                $consultant_email = $team->lawyer_email;
            }
            
            // Retrieve the team translation
            $translation = TeamTranslation::where('team_id', $team_id)
                                          ->where('lang', $lang)
                                          ->first();
            if (empty($translation)) {
                // For Default Language Data Fetch
                $defaultData = TeamTranslation::where('team_id', $team_id)
                                              ->where('lang', 'en')
                                              ->first();
                if (!empty($defaultData)) {
                    $translation = $defaultData;
                }    
            }   
            
            $consultant_name = $consultant_designation = "";
            // Check if translation is found
            if ($translation) {
                // Decode the JSON data
                $fields_value = json_decode($translation->fields_value, true);
                $consultant_name = $fields_value['name'] ?? "";
                $consultant_designation = $fields_value['designation'] ?? "";
            }
            
            // Convert the date format
            $formattedDate = Carbon::createFromFormat('Y-m-d', $meeting_date)->format('d M Y');
            
            $slot_data = TimeSlot::where('id', $slot_id)->first();
            $time_slot = $slot_data ? ($slot_data->from_time . ' To ' . $slot_data->to_time) : 'N/A';
            
            // Combine booking details
            $bookingDetail = [
                'client_id' => $client_id,
                'client_name' => $booking->client_name,
                'client_email' => $booking->client_email,
                'client_phone' => $booking->client_phone ?? "N/A",
                'consultant_name' => $consultant_name,
                'consultant_email' => $consultant_email,
                'consultant_designation' => $consultant_designation,
                'consultant_image' => $consultant_image,
                'meeting_date' => $formattedDate,
                'time_slot' => $time_slot,
                'description' => $booking->description,
                'number_of_attendees' => $booking->number_of_attendees,
                'meeting_purpose' => $booking->meeting_purpose,
                'booking_status' => $status,
                'change_status' => true
            ];
            
            $send_email = $notification = true;
            if(!empty($client_id) && $client_id != null){
                $clientData = User::where('id', $client_id)->first();
                if ($clientData->email_notification == 0) {
                    $send_email = false;
                }
                
                if ($clientData->push_notification == 0) {
                    $notification = false;
                }
            }
            
            if($notification){
                 $unread_notification_count = BookingNotification::countUnreadNotifications($client_id);
                 $bookingDetail['unread_count'] = (int) $unread_notification_count;
                // Insert into BookingNotification table
                BookingNotification::create([
                        'booking_id' => $id,
                        'description' => $booking->description,
                        'booking_status' => $status
                    ]);
                    
                // Broadcast the message event
                event(new MessageSent($bookingDetail));    
            }
            
            if ($send_email) {    
                $template = 'emails.booking_templates.' . $lang . '_bookMeeting'; // Example for language template
                // Send email to client with booking details
                Mail::to($recipientEmail)->send(new BookMeetingMail($bookingDetail, $template, $lang, false));
                
                // Send email to Admin with booking details
                $isAdmin = config('mail.admin_address');
                Mail::to($isAdmin)->send(new BookMeetingMail($bookingDetail, $template, $lang, true));
            }
            
            
            if(!empty($team->lawyer_email) && $team->lawyer_email != null){
                $lawyer_email = $team->lawyer_email;    
                // Send email to the consultant/Lawyer
                Mail::to($lawyer_email)->send(new BookMeetingMail($bookingDetail, $template, $lang, true));
            }
        
            DB::commit(); // Commit transaction
            
            return response()->json(['status' => 'true', 'message' => 'Booking status updated successfully'], Response::HTTP_OK);
        } catch (\Exception $e) {
            DB::rollBack(); // Rollback transaction on error
            return response()->json(['status' => 'false', 'message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

    }
    
    
    public function meetingListSearch($lang, Request $request, $per_page=12)
    {   
        try {
            
            // Define validation rules
            $rules = [
                'search_value'  => 'required|string',
                'booking_status'  => 'nullable|string',
                'from_month'  => 'nullable|string',
                'to_month'  => 'nullable|string'
            ];
        
            // Create a validator instance with the request data and rules
            $validator = Validator::make($request->all(), $rules);
        
            // Check if validation fails
            if ($validator->fails()) {
                // Collect all error messages into a single string with line breaks
                $errorMessages = implode("\n", $validator->errors()->all());
                return response()->json([
                    'status' => 'false',
                    'message' => $errorMessages
                ], Response::HTTP_UNPROCESSABLE_ENTITY); // HTTP 422
            }
            
            // Retrieve the search value from the request
            $search_value = $request->input('search_value');
            $from_month = $request->input('from_month');
            $to_month = $request->input('to_month');
            $status = "";
            
            if($request->has('booking_status')){
                $status = $request->input('booking_status');
            }
            
            // Perform the search query
            $bookings = Booking::where('language', $lang)
                            ->where(function ($query) use ($search_value) {
                                $query->where('client_name', 'LIKE', "%{$search_value}%")
                                      ->orWhere('client_email', 'LIKE', "%{$search_value}%");
                            })
                            ->when(!empty($from_month) && !empty($to_month), function ($query) use ($from_month, $to_month) {
                                return $query->whereRaw("DATE_FORMAT(meeting_date, '%Y-%m-%d') BETWEEN ? AND ?", [$from_month, $to_month]);
                            })
                            ->when(!empty($status), function ($query) use ($status) {
                                $query->where('booking_status', $status);
                            })
                            ->orderBy('updated_at','DESC')
                            ->paginate($per_page);
                          
            // Check if any results are found
            if ($bookings->isEmpty()) {
                return response()->json(['status' => 'false', 'message' => 'No bookings found'], Response::HTTP_NOT_FOUND);
            }
            
            // Retrieve each booking data
            $bookingList = $bookings->map(function ($list) use ($lang) {
                $id = $list->id;
                $team_id = $list->consultant_id;
                $client_id = (int) $list->client_id;
                $client_name = $list->client_name;
                $client_email = $list->client_email;
		$client_phone = $list->client_phone;
                $number_of_attendees = $list->number_of_attendees;
                $meeting_date = $list->meeting_date;
                $time_slot_id = $list->time_slot;
                $booking_status = $list->booking_status;
                $description = $list->description;
                
                // Retrieve the team memeber
                $team = Team::find($team_id);
                
                $consultant_image = $consultant_email = null;
                if(!empty($team->lowyer_image) && $team->lowyer_image != null){
                    $consultant_image = $this->getImageUrl($team->lowyer_image);
                }
                
                if(!empty($team->lawyer_email) && $team->lawyer_email != null){
                    $consultant_email = $team->lawyer_email;
                }
                
                // Retrieve the team translation
                $translation = TeamTranslation::where('team_id', $team_id)
                    ->where('lang', $lang)
                    ->first();
                if (empty($translation)) {
                    // For Defualt Language Data Fetch
                    $defaultData = TeamTranslation::where('team_id', $team_id)
                    ->where('lang', 'en')
                    ->first();
                        
                    if (!empty($defaultData)) {
                        $translation = $defaultData;
                    }    
                }   
                
                $consultant_name = $consultant_designation = "";
                // Check if translation is found
                if ($translation) {
                    // Decode the JSON data
                    $fields_value = json_decode($translation->fields_value, true);
                    $consultant_name = $fields_value['name'] ?? "";
                    $consultant_designation = $fields_value['designation'] ?? "";
                }
                
                // Retrieve the notification details
                $notification_record = BookingNotification::where('booking_id', $id)->get();
                $notification_details = [];
                foreach ($notification_record as $detail) {
                    $notification_details[] = [
                        'status' => $detail->booking_status,
                        'notification_description' => $detail->description,
                        'status_changeDateTime' => Carbon::createFromFormat('Y-m-d H:i:s', $detail->created_at)->format('d M Y H:ia')
                    ];
                }
                
                 // Convert the date format
                $formattedDate = Carbon::createFromFormat('Y-m-d', $meeting_date)->format('d M Y');
                
                $slot_id = $time_slot_id;
                $slot_data = TimeSlot::where('id', $slot_id)->first();
                $time_slot = $slot_data->from_time.' To '.$slot_data->to_time;
                
                return [
                        'id' => $id,
                        'client_id' => $client_id,
                        'client_name' => $client_name,
                        'client_email' => $client_email,
			'client_phone' => $client_phone,
                        'consultant_id' => (int) $team_id,
                        'consultant_name' => $consultant_name,
                        'consultant_email' =>$consultant_email,
                        'consultant_designation' => $consultant_designation,
                        'consultant_image' => $consultant_image,
                        'meeting_date' => $formattedDate,
                        'time_slot' => $time_slot,
                        'number_of_attendees' => $number_of_attendees,
                        'description' => $description ?? "",
                        'booking_status' => $booking_status,
                        'notification_detail' => $notification_details
                    ];
                
            });
    
            return response()->json([
                'status' => 'true',
                'message' => 'Booking record found.',
                'data' => $bookingList,
                'pagination' => [
                        'current_page' => $bookings->currentPage(),
                        'last_page' => $bookings->lastPage(),
                        'per_page' => $bookings->perPage(),
                        'total' => $bookings->total(),
                    ]
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            DB::rollBack(); // Rollback transaction on error
            return response()->json(['status' => 'false', 'message' => $e->getMessage()], 500);
        }
    }
    
    // dashboard booking route
    public function getLatestBookings($lang)
    {
        try {

            $dataArray = array();

            $bookings = Booking::orderBy('updated_at', 'DESC')->get();

            if ($bookings->isEmpty()) {
                return response()->json(['status' => 'false', 'message' => 'Booking not found'], 400);
            }

            // Retrieve each booking data
            $bookingList = $bookings->map(function ($list) use ($lang) {
                $id = $list->id;
                $team_id = $list->consultant_id;
                $client_id = (int)  $list->client_id;
                $client_name = $list->client_name;
                $client_email = $list->client_email;
		$client_phone = $list->client_phone;
                $number_of_attendees = $list->number_of_attendees;
                $meeting_date = $list->meeting_date;
                $time_slot_id = $list->time_slot;
                $booking_status = $list->booking_status;
                $meeting_purpose = $list->meeting_purpose;
                $description = $list->description;

                // Retrieve the team memeber
                $team = Team::find($team_id);

                $consultant_image = $consultant_email = null;
                if (!empty($team->lowyer_image) && $team->lowyer_image != null) {
                    $consultant_image = $this->getImageUrl($team->lowyer_image);
                }
                
                if(!empty($team->lawyer_email) && $team->lawyer_email != null){
                    $consultant_email = $team->lawyer_email;
                }

                // Retrieve the team translation
                $translation = TeamTranslation::where('team_id', $team_id)
                    ->where('lang', $lang)
                    ->first();
                if (empty($translation)) {
                    // For Defualt Language Data Fetch
                    $defaultData = TeamTranslation::where('team_id', $team_id)
                        ->where('lang', 'en')
                        ->first();

                    if (!empty($defaultData)) {
                        $translation = $defaultData;
                    }
                }

                $consultant_name = $consultant_designation = "";
                // Check if translation is found
                if ($translation) {
                    // Decode the JSON data
                    $fields_value = json_decode($translation->fields_value, true);
                    $consultant_name = $fields_value['name'] ?? "";
                    $consultant_designation = $fields_value['designation'] ?? "";
                }
                
                // Retrieve the notification details
                $notification_record = BookingNotification::where('booking_id', $id)->get();
                $notification_details = [];
                foreach ($notification_record as $detail) {
                    $notification_details[] = [
                        'status' => $detail->booking_status,
                        'notification_description' => $detail->description,
                        'status_changeDateTime' => Carbon::createFromFormat('Y-m-d H:i:s', $detail->created_at)->format('d M Y H:ia')
                    ];
                }
                
                // Convert the date format
                $formattedDate = Carbon::createFromFormat('Y-m-d', $meeting_date)->format('d M Y');

                $slot_id = $time_slot_id;
                $slot_data = TimeSlot::where('id', $slot_id)->first();
                $time_slot = $slot_data->from_time . ' To ' . $slot_data->to_time;

                return [
                    'id' => $id,
                    'client_id' => $client_id,
                    'client_name' => $client_name,
                    'client_email' => $client_email,
		    'client_phone' => $client_phone,
                    'consultant_name' => $consultant_name,
                    'consultant_email' => $consultant_email,
                    'consultant_designation' => $consultant_designation,
                    'consultant_image' => $consultant_image,
                    'meeting_date' => $formattedDate,
                    'time_slot' => $time_slot,
                    'number_of_attendees' => $number_of_attendees,
                    'description' => $description ?? "",
                    'booking_status' => $booking_status,
                    'meeting_purpose' => $meeting_purpose ?? "",
                    'notification_detail' => $notification_details
                ];
            });

            $dataArray['bookings'] = $bookingList;

            $departmentController = new DepartmentController();
            $department = $departmentController->getDepartments($lang);

            $teamController = new TeamController();
            $team = $teamController->getTeams($lang);

            if ($team->original['data']) {
                $dataArray['teams'] = $team->original['data'];
            } else {
                $dataArray['teams'] = [];
            }

            if ($department->original["data"]) {
                // $departments->original["data"] = array_slice($departments->original["data"],0,6);
                $dataArray['departments'] = $department->original["data"];
            }else{
                $dataArray['departments'] = [];
            }

            return response()->json([
                'status' => true,
                'data' => $dataArray
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'false',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    
     public function consultantsList($lang){
        try {
            
            // Retrieve all teams
            $teams = Team::orderBy('order_number', 'ASC')->get();
            
            // Check if any teams are found
            if ($teams->isEmpty()) {
                return response()->json(['status' => 'false', 'message' => 'Team member not found'], Response::HTTP_NOT_FOUND);
            }

            // Retrieve translations for each department
            $teamssWithTranslations = $teams->map(function ($team) use ($lang) {
                $id = $team->id;
                $translation = TeamTranslation::where('team_id', $id)
                    ->where('lang', $lang)
                    ->first();

                if (empty($translation)) {
                    // For Defualt Language Data Fetch
                    $translation = TeamTranslation::where('team_id', $id)
                        ->where('lang', 'en')
                        ->first();
                }

                $name = $designation =  "N/A";
                // Check if translation is found
                if ($translation) {
                    // Decode the JSON data
                    $fields_value = json_decode($translation->fields_value, true);
                    $name = $fields_value['name'] ?? "N/A";
                    $designation = $fields_value['designation'] ?? "N/A";
                }

                return [
                    'id' => $id,
                    'name' => $name,
                    'designation' =>  $designation
                ];
            });
            
            return response()->json([
                'status' => 'true',
                'data' => $teamssWithTranslations
            ], Response::HTTP_OK);
        } catch (\Exception $ex) {
            return response()->json(['status' => 'false', 'message' => $ex->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getImageUrl($image_path)
    {
        $image_path = Storage::url($image_path);
        $image_url = asset($image_path);
        return $image_url;
    }
}
