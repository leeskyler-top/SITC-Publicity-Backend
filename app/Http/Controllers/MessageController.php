<?php

namespace App\Http\Controllers;

use App\Http\Resources\MessageResource;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{
    public function getMyMsg()
    {
        $user = Auth::user();
        if ($user->is_admin === '1') {
            $msg = Message::where(['type' => 'private', 'user_id' => $user->id])->orWhere('type', 'admin')->orWhere('type', 'all')->orderBy('created_at', 'desc')->get();
            return $this->jsonRes(200, '消息获取成功', MessageResource::collection($msg));
        } else {
            $msg = Message::where(['type' => 'private', 'user_id' => $user->id])->orWhere('type', 'all')->orderBy('created_at', 'desc')->get();
            return $this->jsonRes(200, '消息获取成功', MessageResource::collection($msg));
        }
    }

    public function readMsg($id)
    {
        if (!is_numeric($id)) {
            return $this->jsonRes(404, '消息不存在');
        }
        $msg = Message::find($id);
        if (!$msg || $msg->type !== 'private' || $msg->user_id !== Auth::id()) {
            return $this->jsonRes(400, '消息不符合条件或消息不存在');
        }
        $msg->status = 'read';
        $msg->save();
        return $this->jsonRes(200, '已读此消息');
    }

    public function readAllMsg()
    {
        $messages = Message::where('user_id', Auth::id())->get();
        foreach ($messages as $message) {
            $message->status = 'read';
            $message->save();
        }
        return $this->jsonRes(200, '已读全部消息');
    }
}
