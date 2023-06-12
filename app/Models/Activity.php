<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Activity extends Model
{
    use HasFactory, SoftDeletes;

    public function users()
    {
        return $this->belongsToMany(User::class, 'activity_users');
    }

    // 笑死 谁知道这样用对不对，外键别名。。。
    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
