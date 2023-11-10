<?php

namespace Oza75\LaravelSesComplaints\Tests\TestSupport\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class TestUser extends Model
{
    use Notifiable;

    protected $table = 'test_users';
}
