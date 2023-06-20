<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EquipmentDelayApplication extends Model
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

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format("Y-m-d H:i:s");
    }
    public function equipmentRent()
    {
        return $this->belongsTo(EquipmentRent::class);
    }

    protected $guarded = [];

    protected $hidden = ['equipment_rent_id', 'user_id', 'audit_id'];

}
