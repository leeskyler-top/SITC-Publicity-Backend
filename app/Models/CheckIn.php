<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CheckIn extends Model
{
    use HasFactory;

    protected $hidden = [
        'deleted_at'
    ];
    public function activity()
    {
        return $this->belongsTo(Activity::class);
    }

    public function checkInUsers()
    {
        return $this->belongsToMany(User::class, 'check_in_users', 'check_in_id', 'user_id')->withPivot('status');
    }

    // 不知道
    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format("Y-m-d H:i:s");
    }

    public function getStatusAttribute()
    {
        $now = now();
        if ($this->start_time > $now) {
            return 'waiting';
        } elseif ($this->end_time <= $now) {
            return 'ended';
        } else {
            return 'started';
        }
    }

    protected $guarded = [];

}
