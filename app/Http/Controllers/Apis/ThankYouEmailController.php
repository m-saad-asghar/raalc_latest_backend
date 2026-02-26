<?php

namespace App\Http\Controllers\Apis;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\ThankYouMail;

class ThankYouEmailController extends Controller
{
    public function sendThankYouEmail(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email',
        ]);

        $name = $request->input('name');
        $email = $request->input('email');

        // Send the thank you email
        Mail::to($email)->send(new ThankYouMail($name));

        return response()->json([
            'message' => 'Thank you email sent successfully.'
        ], 200);
    }
}
