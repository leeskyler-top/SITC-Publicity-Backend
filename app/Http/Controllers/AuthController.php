<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    function login(Request $request)
    {
        $data = $request->only(['email', 'password']);
        $validator = Validator::make($data, [
            'email' => 'required|email:filter',
            'password' => 'required'
        ], [
            'email' => '邮箱格式必须正确',
            'password' => '密码必填'
        ]);
        if ($validator->fails()) {
            return $this->jsonRes(422 , $validator->errors()->first());
        }
        if (Auth::once($data)) {
            $user = Auth::user();
            $user->token = md5(md5($data['email']).md5($data['password']));
            $user->save();
            return $this->jsonRes(200, "登录成功", $user);
        }
        return $this->jsonRes(401, '提供的凭证无效');
    }

    function logout()
    {
        $user = Auth::user();
        $user->token = null;
        $user->save();
        return $this->jsonRes(200, "登出成功");
    }
}
