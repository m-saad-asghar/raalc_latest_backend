<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: Arial, sans-serif;
            direction: rtl;
            text-align: right;
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
        <!-- Admin Email Content in Arabic -->
        <div class="email-section">
            <h1>{{ $bookingDetail['change_status'] ? 'تم تحديث حالة طلب الاستشارة القانونية' : 'تفاصيل طلب الاستشارة القانونية' }}</h1>
            <table>
                <tbody>
                    <tr>
                        <td>اسم العميل</td>
                        <td>{{ $bookingDetail['client_name'] }}</td>
                    </tr>
                    <tr>
                        <td>بريد العميل الإلكتروني</td>
                        <td>{{ $bookingDetail['client_email'] }}</td>
                    </tr>
                    <tr>
                        <td>رقم هاتف العميل</td>
                        <td>{{ $bookingDetail['client_phone'] ?? '' }}</td>
                    </tr>

                    @if(!empty($bookingDetail['consultant_name']))
                    <tr>
                        <td>إسم الإستشاري</td>
                        <td>{{ $bookingDetail['consultant_name'] }}</td>
                    </tr>
                    @endif

                    @if(!empty($bookingDetail['consultant_email']))
                    <tr>
                        <td>البريد الإلكتروني للمستشار</td>
                        <td>{{ $bookingDetail['consultant_email'] }}</td>
                    </tr>
                    @endif

                    <tr>
                        @if($bookingDetail['change_status']) 
                            <td>التاريخ</td>
                        @else
                            <td>التاريخ المطلوب</td>
                        @endif

                        <td>{{ $bookingDetail['meeting_date'] }}</td>
                    </tr>
                    <tr>
                        @if($bookingDetail['change_status']) 
                            <td>الوقت</td>
                        @else
                            <td>الوقت المطلوب</td>
                        @endif
                        <td>{{ $bookingDetail['time_slot'] }}</td>
                    </tr>
                    @if(!empty($bookingDetail['meeting_type']))
                    <tr>
                        <td>نوع الاجتماع</td>
                        <td>{{ $bookingDetail['meeting_type'] }}</td>
                    </tr>
                    @endif
                    @if(!empty($bookingDetail['meeting_link']))
                    <tr>
                        <td>رابط الاجتماع</td>
                        <td>{{ $bookingDetail['meeting_link'] }}</td>
                    </tr>
                    @endif
                    @if(!empty($bookingDetail['meeting_location']))
                    <tr>
                        <td>مكان الاجتماع</td>
                        <td>
                            @if(!empty($bookingDetail['meeting_location']))
                                {!! str_replace(['</br>', '<br/>', '<br />'], '<br>', $bookingDetail['meeting_location']) !!}
                            @endif
                        </td>
                    </tr>
                    @endif
                    <!-- <tr>
                        <td>عدد الحضور</td>
                        <td>{{ $bookingDetail['number_of_attendees'] }}</td>
                    </tr> -->
                    <tr>
                        <td>غرض الاجتماع</td>
                        <td>{{ $bookingDetail['meeting_purpose'] }}</td>
                    </tr>
                    <!-- @if(!empty($bookingDetail['description']))
                    <tr>
                        <td>حالة الاجتماع والتفاصيل</td>
                        <td>{{ $bookingDetail['description'] }}</td>
                    </tr>
                    @endif -->
                    <!-- <tr>
                        <td>حالة طلب</td>
                        <td>{{ $bookingDetail['booking_status'] }}</td>
                    </tr> -->
                </tbody>
            </table>
            <br>
            <p>مع التحية،<br>RAALC Law Firm</p>
            <div>
                <img src="https://www.raalc.ae/_next/static/media/main-logo.b40297c4.png" alt="RAALC Law Firm Logo" style="height:100px;width:auto;max-width:300px;">
            </div>
        </div>
    @else
        <!-- User Email Content in Arabic -->
        <div class="email-section">
            <!-- <h1>{{ $bookingDetail['change_status'] ? 'لقد تم تحديث حالة طلب الاستشارة القانونية الخاص بك!' : 'شكراً لتقديم طلبك!' }}</h1> -->
            <p>السيد/ة {{ $bookingDetail['client_name'] }}،</p>
            <!-- <p>{{ $bookingDetail['change_status'] ? 'لقد تم تحديث حالة طلب الاستشارة القانونية الخاص بك. يرجى الاطلاع على التفاصيل أدناه:' : 'شكراً لتقديم طلب للحصول على استشارة قانونية من مكتب RAALC للمحاماة. سيتواصل فريقنا معك خلال وقت قصير لتأكيد موعد الاجتماع.' }}</p> -->
            
            <p>تحياتنا من مكتب المحاماة RAALC.</p>

            <p>{{ $bookingDetail['change_status'] ? 
                'يسرنا إبلاغكم بتأكيد طلبكم لعقد اجتماع استشاري. تجدون أدناه تفاصيل الاجتماع للاطلاع عليها:' : 
                'نؤكد استلام طلبكم لعقد اجتماع استشاري. تجدون أدناه التفاصيل المرفقة للاطلاع عليها:' }}
            </p>
            
            <table>
                <tbody>

                    @if(!empty($bookingDetail['client_name']))
                    <tr>
                        <td>اسم العميل</td>
                        <td>{{ $bookingDetail['client_name'] }}</td>
                    </tr>
                    @endif

                    @if(!empty($bookingDetail['client_email']))
                    <tr>
                        <td>بريد العميل الإلكتروني</td>
                        <td>{{ $bookingDetail['client_email'] }}</td>
                    </tr>
                    @endif

                    @if(!empty($bookingDetail['client_phone']))
                    <tr>
                        <td>رقم هاتف العميل</td>
                        <td>{{ $bookingDetail['client_phone'] ?? '' }}</td>
                    </tr>
                    @endif

                    @if(!empty($bookingDetail['consultant_name']))
                    <tr>
                        <td>إسم الإستشاري</td>
                        <td>{{ $bookingDetail['consultant_name'] }}</td>
                    </tr>
                    @endif

                    @if(!empty($bookingDetail['consultant_email']))
                    <tr>
                        <td>البريد الإلكتروني للمستشار</td>
                        <td>{{ $bookingDetail['consultant_email'] }}</td>
                    </tr>
                    @endif

                    <!-- @if(!empty($bookingDetail['consultant_designation']))
                    <tr>
                        <td>تسمية المستشار</td>
                        <td>{{ $bookingDetail['consultant_designation'] }}</td>
                    </tr>
                    @endif -->
                    <tr>
                        @if($bookingDetail['change_status']) 
                            <td>التاريخ</td>
                        @else
                            <td>التاريخ المطلوب</td>
                        @endif

                        <td>{{ $bookingDetail['meeting_date'] }}</td>
                    </tr>
                    <tr>
                        @if($bookingDetail['change_status']) 
                            <td>الوقت</td>
                        @else
                            <td>الوقت المطلوب</td>
                        @endif

                        <td>{{ $bookingDetail['time_slot'] }}</td>
                    </tr>
                    @if(!empty($bookingDetail['meeting_type']))
                    <tr>
                        <td>نوع الاجتماع</td>
                        <td>{{ $bookingDetail['meeting_type'] }}</td>
                    </tr>
                    @endif
                    @if(!empty($bookingDetail['meeting_link']))
                    <tr>
                        <td>رابط الاجتماع</td>
                        <td>{{ $bookingDetail['meeting_link'] }}</td>
                    </tr>
                    @endif
                    @if(!empty($bookingDetail['meeting_location']))
                    <tr>
                        <td>مكان الاجتماع</td>
                        <td>
                            @if(!empty($bookingDetail['meeting_location']))
                                {!! str_replace(['</br>', '<br/>', '<br />'], '<br>', $bookingDetail['meeting_location']) !!}
                            @endif
                        </td>
                    </tr>
                    @endif
                    <!-- <tr>
                        <td>عدد الحضور</td>
                        <td>{{ $bookingDetail['number_of_attendees'] }}</td>
                    </tr> -->
                    <tr>
                        <td>غرض الاجتماع</td>
                        <td>{{ $bookingDetail['meeting_purpose'] }}</td>
                    </tr>
                    <!-- @if(!empty($bookingDetail['description']))
                    <tr>
                        <td>حالة الاجتماع والتفاصيل</td>
                        <td>{{ $bookingDetail['description'] }}</td>
                    </tr>
                    @endif -->
                    <!-- @if($bookingDetail['change_status'])
                    <tr>
                        <td>حالة طلب</td>
                        <td>{{ $bookingDetail['booking_status'] }}</td>
                    </tr>
                    @endif -->
                </tbody>
            </table>
            <br>

            @if($bookingDetail['change_status'])
            
                <!-- @if(!empty($bookingDetail['meeting_link']))
                    <p>يرجى الانضمام إلى الاجتماع باستخدام الرابط أعلاه في الموعد المحدد.</p>
                @endif

                @if(!empty($bookingDetail['meeting_location']))
                    <p>نتطلع إلى الترحيب بكم في مكتبنا. إذا كنتم بحاجة إلى أي مساعدة، فلا تترددوا في الاتصال بنا.</p>
                @endif -->

            <p>Wنتطلع إلى مساعدتكم. في حال رغبتكم في إعادة جدولة الموعد أو احتجتم إلى أي مساعدة إضافية، فلا تترددوا في الاتصال بالمستشار مباشرةً.</p>
            @else

                <p>طلبك قيد المراجعة حاليًا، وستتلقى رسالة بريد إلكتروني للمتابعة قريبًا لتأكيد التاريخ والوقت المحددين لاستشارتك.</p>

            @endif

            <!-- <p>يرجى الاحتفاظ بهذه المعلومات لسجلاتك.</p> -->

            <p>مع التحية،<br> RAALC Law Firm</p>
            <div>
                <img src="https://www.raalc.ae/_next/static/media/main-logo.b40297c4.png" alt="RAALC Law Firm Logo" style="height:100px;width:auto;max-width:300px;">
            </div>
        </div>
    @endif
</body>
</html>

