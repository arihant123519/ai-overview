<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedianInfo extends Model
{
    use HasFactory;
    public $timestamps = true;
    protected $table='median_info';
    protected $fillable = [
        'domainmanagement_id',
        'client_property_id',
        'keyword_request_id',
        'median_name',
        'date_from',
        'date_to',
    ];
}
