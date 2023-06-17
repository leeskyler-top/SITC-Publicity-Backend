<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Activity extends Model
{
    use HasFactory, SoftDeletes;

    protected $hidden = [
        'deleted_at'
    ];
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

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format("Y-m-d H:i:s");
    }

    protected $guarded = [];

}
