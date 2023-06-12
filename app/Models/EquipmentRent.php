<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EquipmentRent extends Model
{
    use HasFactory;

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // 是不是这样用的一会儿再说，他妈的，头一回要做别名外键！
    public function audit()
    {
        return $this->belongsTo(User::class, 'audit_id');
    }

    public function equipment()
    {
        return $this->belongsTo(Equipment::class, 'equipment_id');
    }
}
