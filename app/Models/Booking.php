<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Booking extends Model
{
    use HasFactory, SoftDeletes;
    
    protected $table = 'bookings';

    protected $fillable = [
        "client_id",
        "client_name",
        "client_email",
        "client_phone",
        "meeting_date",
        "time_slot",
        "beverage",
        "number_of_attendees",
        "meeting_type",
        "meeting_shift",
        "meeting_place",
        "booking_status",
        "consultant_id",
        "meeting_purpose",
        "language",
        "description",
        "consultant_email",
        "meeting_link",
        "meeting_location",
    ];

    protected $dates = ['deleted_at'];
    
    public function notifications()
    {
        return $this->hasMany(BookingNotification::class, 'booking_id');
    }
}
