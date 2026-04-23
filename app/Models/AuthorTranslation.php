<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuthorTranslation extends Model
{
    use HasFactory;

    protected $fillable = ['author_id', 'lang', 'fields_value'];
}
