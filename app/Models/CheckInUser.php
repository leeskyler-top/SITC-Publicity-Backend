<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CheckInUser extends Model
{
    use HasFactory;

    public function checkIn()
    {
        return $this->belongsTo(CheckIn::class);
    }

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format("Y-m-d H:i:s");
    }
    protected $guarded = [];
    protected $hidden = ['check_in_id', 'user_id'];

}
