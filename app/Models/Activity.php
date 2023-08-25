<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    use HasFactory;
    public function users()
    {
        return $this->belongsToMany(User::class, 'activity_users');
    }

    // 笑死 谁知道这样用对不对，外键别名。。。
    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id', 'id');
    }

    public function activityAudits()
    {
        return $this->hasMany(ActivityAudit::class);
    }

    public function checkIns()
    {
        return $this->hasMany(CheckIn::class);
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
