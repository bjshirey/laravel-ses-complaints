<?php


namespace Oza75\LaravelSesComplaints\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    // 2023-10-03: I had to disable this b/c it's not currently supported with the
    // version of Illuminate libraries we use.
    // use HasFactory;

    protected $table = 'ses_notifications';

    protected $guarded = [];

    protected $casts = [
        'options' => 'array'
    ];
}
