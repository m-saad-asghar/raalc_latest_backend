<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: Arial, sans-serif;
            direction: ltr;
            text-align: left;
            margin: 0;
            padding: 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
        }
        th {
            background-color: #f4f4f4;
        }
        .email-section {
            margin-bottom: 40px;
        }
    </style>
</head>
<body>
    @if($isAdmin)
        <!-- Admin Email Content -->
        <div class="email-section">
            <h1>{{ $bookingDetail['change_status'] ? 'Legal Consultation Request Status Updated' : 'Legal Consultation Request Details' }}</h1>
            <table>
                <tbody>
                    <tr>
                        <td>Client Name</td>
                        <td>{{ $bookingDetail['client_name'] }}</td>
                    </tr>
                    <tr>
                        <td>Client Email</td>
                        <td>{{ $bookingDetail['client_email'] }}</td>
                    </tr>
                    <tr>
                        <td>Client Phone</td>
                        <td>{{ $bookingDetail['client_phone'] ?? '' }}</td>
                    </tr>
                    @if(!empty($bookingDetail['consultant_name']))
                    <tr>
                        <td>Consultant Name</td>
                        <td>{{ $bookingDetail['consultant_name'] }}</td>
                    </tr>
                    @endif

                    @if(!empty($bookingDetail['consultant_email']))
                    <tr>
                        <td>Consultant Email</td>
                        <td>{{ $bookingDetail['consultant_email'] }}</td>
                    </tr>
                    @endif

                    <!-- @if(!empty($bookingDetail['consultant_designation']))
                    <tr>
                        <td>Consultant Designation</td>
                        <td>{{ $bookingDetail['consultant_designation'] }}</td>
                    </tr>
                    @endif -->
                    <tr>
                        @if($bookingDetail['change_status']) 
                            <td>Date</td>
                        @else
                            <td>Requested Date</td>
                        @endif
                        
                        <td>{{ $bookingDetail['meeting_date'] }}</td>
                    </tr>
                    <tr>
                        @if($bookingDetail['change_status']) 
                            <td>Time</td>
                        @else   
                        <td>Requested Time</td>
                        @endif

                        <td>{{ $bookingDetail['time_slot'] }}</td>
                    </tr>
                    @if(!empty($bookingDetail['meeting_type']))
                    <tr>
                        <td>Meeting Type</td>
                        <td>{{ $bookingDetail['meeting_type'] }}</td>
                    </tr>
                    @endif
                    @if(!empty($bookingDetail['meeting_link']))
                    <tr>
                        <td>Meeting Link</td>
                        <td>{{ $bookingDetail['meeting_link'] }}</td>
                    </tr>
                    @endif
                    @if(!empty($bookingDetail['meeting_location']))
                        <tr>
                            <td>Meeting Location</td>
                            <td>
                                @if(!empty($bookingDetail['meeting_location']))
                                    {!! str_replace(['</br>', '<br/>', '<br />'], '<br>', $bookingDetail['meeting_location']) !!}
                                @endif
                            </td>
                        </tr>
                    @endif
                    <!-- <tr>
                        <td>Number of Attendees</td>
                        <td>{{ $bookingDetail['number_of_attendees'] }}</td>
                    </tr> -->
                    <tr>
                        <td>Purpose of the meeting</td>
                        <td>{{ $bookingDetail['meeting_purpose'] }}</td>
                    </tr>
                    <!-- @if(!empty($bookingDetail['description']))
                    <tr>
                        <td>Meeting Status and Details</td>
                        <td>{{ $bookingDetail['description'] }}</td>
                    </tr>
                    @endif -->
                    <!-- <tr>
                        <td>Request Status</td>
                        <td>{{ $bookingDetail['booking_status'] }}</td>
                    </tr> -->
                </tbody>
            </table>
            <br>
            <p>Best regards,<br> RAALC Law Firm</p>
            <div>
                <img src="https://www.raalc.ae/_next/static/media/main-logo.b40297c4.png" alt="RAALC Law Firm Logo" style="height:100px;width:auto;max-width:300px;">
            </div>
        </div>
    @else
        <!-- User Email Content in Table Form -->
        <div class="email-section">
            <!-- <h1>{{ $bookingDetail['change_status'] ? 'Your Legal Consultation Request Status Has Been Updated!' : 'Thank You for Your Request!' }}</h1> -->
            <p>
                @if(isset($isConsultant) && $isConsultant && !empty($bookingDetail['consultant_name']))
                    Dear {{ $bookingDetail['consultant_name'] }},
                @else
                    Dear {{ $bookingDetail['client_name'] }},
                @endif
            </p>
            <!-- <p>{{ $bookingDetail['change_status'] ? 'The status of your legal advice request has been updated. Please see details below:' : 'Thank you for submitting a request for legal consultation from RAALC Law Firm. Our team will contact you shortly to confirm the meeting time.' }}</p> -->
            
            <p>Greetings from RAALC Law Firm.</p>

            <p>{{ $bookingDetail['change_status'] ? 
                'We are pleased to inform you that your consultation meeting request has been confirmed. Please find the meeting details below for your reference:' : 
                'We acknowledge receipt of your consultation meeting request. Please find the details submitted below for your reference:' }}
            </p>
            <table>
                <tbody>

                    @if(!empty($bookingDetail['client_name']))
                    <tr>
                        <td>Client Name</td>
                        <td>{{ $bookingDetail['client_name'] }}</td>
                    </tr>
                    @endif

                    @if(!empty($bookingDetail['client_email']))
                    <tr>
                        <td>Client Email</td>
                        <td>{{ $bookingDetail['client_email'] }}</td>
                    </tr>
                    @endif

                    @if(!empty($bookingDetail['client_phone']))
                    <tr>
                        <td>Client Phone</td>
                        <td>{{ $bookingDetail['client_phone'] ?? '' }}</td>
                    </tr>
                    @endif

                    @if(!empty($bookingDetail['consultant_name']))
                    <tr>
                        <td>Consultant Name</td>
                        <td>{{ $bookingDetail['consultant_name'] }}</td>
                    </tr>
                    @endif

                    @if(!empty($bookingDetail['consultant_email']))
                    <tr>
                        <td>Consultant Email</td>
                        <td>{{ $bookingDetail['consultant_email'] }}</td>
                    </tr>
                    @endif
                    <!-- @if(!empty($bookingDetail['consultant_designation']))
                    <tr>
                        <td>Consultant Designation</td>
                        <td>{{ $bookingDetail['consultant_designation'] }}</td>
                    </tr>
                    @endif -->
                    <tr>
                        @if($bookingDetail['change_status']) 
                            <td>Date</td>
                        @else
                            <td>Requested Date</td>
                        @endif

                        <td>{{ $bookingDetail['meeting_date'] }}</td>
                    </tr>
                    <tr>
                        @if($bookingDetail['change_status']) 
                            <td>Time</td>
                        @else   
                        <td>Requested Time</td>
                        @endif

                        <td>{{ $bookingDetail['time_slot'] }}</td>
                    </tr>
                    @if(!empty($bookingDetail['meeting_type']))
                    <tr>
                        <td>Meeting Type</td>
                        <td>{{ $bookingDetail['meeting_type'] }}</td>
                    </tr>
                    @endif
                    @if(!empty($bookingDetail['meeting_link']))
                    <tr>
                        <td>Meeting Link</td>
                        <td>{{ $bookingDetail['meeting_link'] }}</td>
                    </tr>
                    @endif
                    @if(!empty($bookingDetail['meeting_location']))
                    <tr>
                        <td>Meeting Location</td>
                        <td>
                            @if(!empty($bookingDetail['meeting_location']))
                                {!! str_replace(['</br>', '<br/>', '<br />'], '<br>', $bookingDetail['meeting_location']) !!}
                            @endif
                        </td>
                    </tr>
                    @endif
                    <!-- <tr>
                        <td>Number of Attendees</td>
                        <td>{{ $bookingDetail['number_of_attendees'] }}</td>
                    </tr> -->
                    <tr>
                        <td>Purpose of the meeting</td>
                        <td>{{ $bookingDetail['meeting_purpose'] }}</td>
                    </tr>
                    <!-- @if(!empty($bookingDetail['description']))
                    <tr>
                        <td>Meeting Status and Details</td>
                        <td>{{ $bookingDetail['description'] }}</td>
                    </tr>
                    @endif -->
                    <!-- @if($bookingDetail['change_status'])
                    <tr>
                        <td>Request Status</td>
                        <td>{{ $bookingDetail['booking_status'] }}</td>
                    </tr>
                    @endif -->
                </tbody>
            </table>
            <br>

            @if($bookingDetail['change_status'])

                <!-- @if(!empty($bookingDetail['meeting_link']))
                    <p>Kindly join the meeting using the link above at the scheduled time.</p>
                @endif

                @if(!empty($bookingDetail['meeting_location']))
                    <p>We look forward to welcoming you at our office. Should you require any assistance, please feel free to contact us.</p>
                @endif -->

                <p>We look forward to assisting you. Should you need to reschedule or require any further assistance, please do not hesitate to contact the consultant directly.</p>
            @else

                <p>Your request is currently under review, and you will receive a follow-up email shortly confirming the scheduled date and time of your consultation.</p>

            @endif
            

            <!-- <p>Please keep this information for your records.</p> -->
            <p>Best regards,<br>RAALC Law Firm</p>
            <div>
                <img src="https://www.raalc.ae/_next/static/media/main-logo.b40297c4.png" alt="RAALC Law Firm Logo" style="height:100px;width:auto;max-width:300px;">
            </div>
        </div>
    @endif
</body>
</html>
