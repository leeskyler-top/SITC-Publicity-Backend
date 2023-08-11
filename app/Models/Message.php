<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Message extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function sendMsg($title, $msg, $type, $user_id = null)
    {
        $status = 'unread';
        if ($type !== 'private') {
            $status = 'unnecessary';
        }
        Message::create([
            'user_id' => $user_id,
            'msg_user_id' => Auth::id(),
            'title' => $title,
            'msg' => $msg,
            'type' => $type,
            'status' => $status
        ]);
    }
}
