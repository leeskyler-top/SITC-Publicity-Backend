<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = User::all();
        $user->each(function ($item) {
            $item->makeHidden(['token']);
        });
        return $this->jsonRes(200, null, $user);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->only(['is_admin', 'uid', 'name', 'email', 'password', 'department', 'classname', 'note']);
        $data = array_filter($data, function ($value) {
            return !empty($value) || $value == 0;
        });
        $validator = Validator::make($data, [
            'is_admin' => 'required|in:"0","1"',
            'uid' => 'required|integer|regex:/^\d{8}$/',
            'name' => 'required',
            'email' => [
                'required',
                'email:filter',
                Rule::unique('users', 'email')->where('deleted_at')
            ],
            'password' => 'required',
            'department' => 'required',
            'classname' => 'required',
            'note' => 'nullable|string'
        ], [
            'is_admin' => '值必须为0或1，1代表管理员， 0代表普通用户',
            'uid' => '学籍号工号必须为8位的纯数字',
            'name' => '姓名必填',
            'email.unique' => '邮箱已被使用',
            'email.*' => '邮箱格式必须正确',
            'password' => '密码必填',
            'department' => '系部必填',
            'classname' => '班级必填',
            'note' => '必须为字符串'
        ]);
        if ($validator->fails()) {
            return $this->jsonRes(422, $validator->errors()->first());
        }
        $user = User::create($data);
        $user->refresh();
        return $this->jsonRes(200, '用户添加成功', $user);
    }

    /**
     * Display the specified resource.
     */
    public function show()
    {
        $user = Auth::user();
        return $this->jsonRes(200, '获取个人账户信息成功', $user->makeHidden(['token']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        if (!is_numeric($id)) {
            return $this->jsonRes(404, '用户未找到');
        }
        $user = User::find($id);
        if (!$user) {
            return $this->jsonRes(404, '用户未找到');
        }
        $data = $request->only(['is_admin', 'uid', 'name', 'department', 'classname', 'note']);
        $data = array_filter($data, function ($value) {
            return !empty($value) || $value == 0;
        });
        $validator = Validator::make($data, [
            'is_admin' => 'nullable|in:"0","1"',
            'uid' => 'nullable|integer|regex:/^\d{8}$/',
            'name' => 'nullable|string',
            'department' => 'nullable|string',
            'classname' => 'nullable|string',
            'note' => 'nullable|string'
        ], [
            'is_admin' => '值必须为0或1，1代表管理员， 0代表普通用户',
            'uid' => '学籍号工号必须为8位的纯数字',
            'name' => '姓名必填',
            'department' => '系部必填',
            'classname' => '班级必填',
            'note' => '必须为字符串'
        ]);
        if ($validator->fails()) {
            return $this->jsonRes(422, $validator->errors()->first());
        }
        $user->fill($data)->refresh();
        return $this->jsonRes(200, '变更成功');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        if (!is_numeric($id)) {
            return $this->jsonRes(404, '用户未找到');
        }
        $user = User::find($id);
        if (!$user) {
            return $this->jsonRes(404, '用户未找到');
        } else if ($user->id === Auth::id()) {
            return $this->jsonRes(403, '拒绝访问，不可以删除用户自己');
        } else {
            $user->delete();
            return $this->jsonRes(200, "用户删除成功");
        }
    }

    function changePwd($id)
    {
        if (!is_numeric($id)) {
            return $this->jsonRes(404, '用户未找到');
        }
        $user = User::find($id);
        if (!$user) {
            return $this->jsonRes(404, '用户未找到');
        } else if ($user->id === Auth::id()) {
            return $this->jsonRes(403, '拒绝访问，不可以重置用户自己的密码');
        } else {
            $pwd = User::genPwd();
            $user->password = $pwd;
            $user->save();
            return $this->jsonRes(200, "密码重置成功", ['password' => $pwd]);
        }
    }
}
