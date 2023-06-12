<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Equipment extends Model
{
    use HasFactory, SoftDeletes;

    public function equipmentRents()
    {
        return $this->hasMany(EquipmentRent::class, 'equipment_id');
    }
}
