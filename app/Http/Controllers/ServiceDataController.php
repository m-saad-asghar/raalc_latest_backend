<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use Validator;
use App\Mail\ServiceDataEmail;
use Illuminate\Support\Facades\Mail;

class ServiceDataController extends Controller
{
    /**
     * Save the service data into the database.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
   public function saveServicesData(Request $request)
{
    try {
        // Get the form data
        $formData = $request->all();

        // Send email with the form data
        $a = Mail::to('inquiry@raalc.ae')->send(new ServiceDataEmail($formData));

        return response()->json([
            'success' => 1,
            'message' => 'Thank you for connecting, we will get back to you shortly.',
        ], 200);
    } catch (\Exception $e) {
        // Log the error and return a failure message
        \Log::error("Error sending email: " . $e->getMessage());
        return response()->json([
            'success' => 0,
            'message' => 'Something went wrong, try again in some time.',
        ], 500);
    }
}

}

