<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CheckIn extends Model
{
    use HasFactory, SoftDeletes;

    protected $hidden = [
        'deleted_at'
    ];
    public function activity()
    {
        return $this->belongsTo(Activity::class);
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

    protected $guarded = [];

}
