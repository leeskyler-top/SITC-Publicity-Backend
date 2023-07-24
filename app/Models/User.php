<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
//    protected $fillable = [
//        'name',
//        'email',
//        'password',
//    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'deleted_at'
//        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
//        'email_verified_at' => 'datetime',
//        'password' => 'hashed',
    ];

    protected $guarded = [];

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format("Y-m-d H:i:s");
    }

    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = Hash::make($value);
    }

    public static function genPwd()
    {
        do {
            $pwd = Str::random(8);
        } while (preg_match("/^[a-zA-Z]+$/", $pwd) || preg_match("/^\d+$/", $pwd));

        return $pwd;
    }

    public function equipmentRents()
    {
        return $this->hasMany(EquipmentRent::class, 'user_id');
    }

    public function activities()
    {
        return $this->belongsToMany(Activity::class, 'activity_users', 'user_id', 'activity_id');
    }

    public function activityApplications()
    {
        return $this->hasMany(ActivityAudit::class);
    }

    public function checkInUsers()
    {
        return $this->belongsToMany(User::class, 'check_in_users', 'user_id', 'check_in_id');
    }
}
