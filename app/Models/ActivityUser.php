<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityUser extends Model
{
    use HasFactory;

    protected $hidden = ['user_id', 'audit_id'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function activity()
    {
        return $this->belongsTo(Activity::class, 'activity_id');
    }

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format("Y-m-d H:i:s");
    }
    protected $guarded = [];
    public $timestamps = false;

}
