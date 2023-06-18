<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $users = User::all();
        $users->each(function ($item) {
            $item->makeHidden(['token']);
        });
        return $this->jsonRes(200, '用户获取成功', $users);
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
    public function show($id = 'my')
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
            'is_admin' => ['nullable', Rule::in(['0','1'])],
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
        $user->fill($data)->save();
        $user->refresh();
        return $this->jsonRes(200, '变更成功', $user->makeHidden(['token']));
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

    function resetPwd($id)
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

    function changePwd(Request $request)
    {
        $data = $request->only(['origin_password', 'new_password', 'repeat_password']);
        $validator = Validator::make($data, [
            'origin_password' => 'required|current_password',
            'new_password' => 'required',
            'repeat_password' => 'required|same:new_password',
        ], [
            'origin_password' => '提供的用户凭据是无效的',
            'new_password' => '必填',
            'repeat_password' => '与即将新设定的密码不符'
        ]);
        if ($validator->fails()) {
            return $this->jsonRes(422, $validator->errors()->first());
        }
        $user = Auth::user();
        $user->password = $data['new_password'];
        $user->save();
        return $this->jsonRes(200, '密码修改成功');
    }

    public function batchStore(Request $request)
    {
        $data = $request->only(['csv_file']);
        $validator = Validator::make($data, [
            'csv_file' => 'required|mimes:csv,txt'
        ]);
        if ($validator->fails()) {
            return $this->jsonRes(422, '必须是csv文件');
        }
        $file = $request->file('csv_file')->openFile('r');
        $file->setFlags(\SplFileObject::READ_CSV);

        $validationRules = [
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
        ];

        DB::beginTransaction();

        try {
            foreach ($file as $key => $element) {
                if ($key === 0) { // Skip the header row
                    continue;
                }
                if (count($element) !== 8) {
                    throw new \Exception("Invalid CSV format. Please check the file contents.");
                }
                $validator = Validator::make([
                    'is_admin' => $element[0],
                    'uid' => $element[1],
                    'name' => $element[2],
                    'email' => $element[3],
                    'password' => $element[4],
                    'department' => $element[5],
                    'classname' => $element[6],
                    'note' => $element[7],

                ], $validationRules,  [
                    'email.unique' => '邮箱已被使用',
                ]);
                if ($validator->fails()) {
                    DB::rollBack();
                    return $this->jsonRes(400, $validator->errors()->first());
                }
                $user = User::create([
                    'is_admin' => $element[0],
                    'uid' => $element[1],
                    'name' => $element[2],
                    'email' => $element[3],
                    'password' => $element[4],
                    'department' => $element[5],
                    'classname' => $element[6],
                    'note' => $element[7],
                ]);
                $user->refresh();
                $users[] = $user;
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['msg' => '在执行批量操作时发生严重错误，已回滚操作！'],500);
        }

        return $this->jsonRes(200, '用户批量添加完成', $users);
    }

    // 通过姓名模糊搜索用户
    // 通过姓名模糊搜索不在某个活动中的用户
}
