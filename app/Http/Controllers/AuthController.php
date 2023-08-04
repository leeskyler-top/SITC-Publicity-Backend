<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Mews\Captcha\Facades\Captcha;

class AuthController extends Controller
{
    public function generateCaptcha()
    {
        $captcha = Captcha::create('default', true); // Generate captcha and return its JSON representation
        return response()->json($captcha);
    }
    function login(Request $request)
    {
        $data = $request->only(['email', 'password', 'captcha', 'captcha_key']);
        $validator = Validator::make($data, [
            'email' => 'required|email:filter',
            'password' => 'required',
            'captcha' => 'required|captcha_api:' . $data['captcha_key'],
            'captcha_key' => 'required',
        ], [
            'email' => '邮箱格式必须正确',
            'password' => '密码必填',
            'captcha' => '验证码不正确',
            'captcha_key' => '验证码必填'
        ]);
        if ($validator->fails()) {
            return $this->jsonRes(422 , $validator->errors()->first());
        }
        unset($data['captcha_key']);
        unset($data['captcha']);
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
