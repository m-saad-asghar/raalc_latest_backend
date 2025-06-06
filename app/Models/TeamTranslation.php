<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeamTranslation extends Model
{
    use HasFactory;
    
    protected $fillable = ['team_id', 'lang', 'fields_value'];
}
