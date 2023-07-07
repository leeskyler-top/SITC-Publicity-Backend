<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EquipmentRent extends Model
{
    use HasFactory;

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id','id');
    }

    public function audit()
    {
        return $this->belongsTo(User::class, 'audit_id','id');
    }

    public function equipment()
    {
        return $this->belongsTo(Equipment::class, 'equipment_id');
    }

    public function equipmentDelayApplications()
    {
        return $this->hasMany(EquipmentDelayApplication::class);
    }

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format("Y-m-d H:i:s");
    }
    protected $guarded = [];
    protected $hidden = ['equipment_rent_id', 'equipment_id', 'user_id', 'audit_id'];
}
