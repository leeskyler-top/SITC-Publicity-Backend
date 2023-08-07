<?php

namespace App\Http\Controllers;

use App\Http\Resources\CheckInResource;
use App\Http\Resources\CheckInUsersResource;
use App\Models\CheckIn;
use App\Models\CheckInUser;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CheckInController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $checkIn = CheckIn::orderBy('start_time', 'desc')->get();
        return $this->jsonRes(200, '获取所有签到成功', CheckInResource::collection($checkIn));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->only([
            'activity_id',
            'user_id',
            'start_time',
            'end_time',
        ]);
        $validator = Validator::make($data, [
            'activity_id' => [
                'integer',
                'required',
                Rule::exists('activities', 'id')->where(function ($query) {
                    $query->where('deleted_at', null);
                }),
            ],
            'user_id' => 'required|array',
            'user_id.*' => [
                'integer',
                Rule::exists('activity_users', 'user_id')->where(function ($query) use ($data) {
                    $query->where('activity_id', $data['activity_id']);
                }),
            ],
            'start_time' => 'required|date_format:Y-m-d H:i:s',
            'end_time' => 'required|date_format:Y-m-d H:i:s|after:start_time',
        ], [
            'activity_id' => '活动ID必填且必须存在',
            'user_id' => '用户必填',
            'user_id.*' => '用户必须存在',
            'start_time' => '开始时间必填且必须合法',
            'end_time' => '结束时间必填且必须合法',
        ]);
        if ($validator->fails()) {
            return $this->jsonRes(422, $validator->errors()->first());
        }
        $users = $data['user_id'];
        unset($data['user_id']);
        $data['admin_id'] = Auth::id();
        $checkIn = CheckIn::create($data);
        foreach ($users as $user) {
            $checkIn->checkInUsers()->create([
                'user_id' => $user
            ]);
        }
        return $this->jsonRes(200, '签到创建成功', new CheckInResource($checkIn));
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        if (!is_numeric($id)) {
            return $this->jsonRes(404, '签到未找到');
        }
        $checkIn = CheckIn::find($id);
        if (!$checkIn) {
            return $this->jsonRes(404, '签到未找到');
        }
        return $this->jsonRes(200, "获取签到信息成功", new CheckInResource($checkIn));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        if (!is_numeric($id)) {
            return $this->jsonRes(404, '签到未找到');
        }
        $checkIn = CheckIn::find($id);
        if (!$checkIn) {
            return $this->jsonRes(404, '签到未找到');
        }
        $data = $request->only([
            'start_time',
            'end_time',
        ]);
        $validator = Validator::make($data, [
            'start_time' => 'required|date_format:Y-m-d H:i:s',
            'end_time' => 'required|date_format:Y-m-d H:i:s|after:start_time',
        ], [
            'start_time' => '开始时间必填且必须合法',
            'end_time' => '结束时间必填且必须合法',
        ]);
        if ($validator->fails()) {
            return $this->jsonRes(422, $validator->errors()->first());
        }
        $checkIn->fill($data)->save();
        return $this->jsonRes(200, '签到信息修改成功', new CheckInResource($checkIn));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        if (!is_numeric($id)) {
            return $this->jsonRes(404, '签到未找到');
        }
        $checkIn = CheckIn::find($id);
        if (!$checkIn) {
            return $this->jsonRes(404, '签到未找到');
        }
        $checkIn->checkInUsers()->delete();
        $checkIn->delete();
        return $this->jsonRes(200, "此签到已删除");
    }

    public function listMyCheckIns()
    {
        $checkIns = CheckInUser::where(['user_id' => Auth::id(), 'status' => 'unsigned'])->orderBy('start_time', 'asc')->get();
        return $this->jsonRes(200, '签到列表获取成功', CheckInUsersResource::collection($checkIns));
    }

    public function checkIn(Request $request, $id)
    {
        if (!is_numeric($id)) {
            return $this->jsonRes(404, '签到未找到');
        }
        $checkInUser = CheckInUser::find($id);
        if (!$checkInUser || $checkInUser->status !== 'unsigned') {
            return $this->jsonRes(404, '签到未找到');
        }
        $data = $request->only(['images']);
        $validator = Validator::make($data, [
            'image_url' => 'required|array',
            'image_url.*' => 'required|image'
        ], [
            'image_url' => '必须上传至少一张图片',
            'image_url.*' => '必须上传至少一张图片'
        ]);
        $checkInUser->status = 'signed';
        $checkInUser->image_url = $data['image_url'];
        $checkInUser->save();
        return $this->jsonRes(200, '签到成功');
    }

    public function revokeCheckIn($id)
    {
        if (!is_numeric($id)) {
            return $this->jsonRes(404, '签到未找到');
        }
        $checkInUser = CheckInUser::find($id);
        if (!$checkInUser || $checkInUser->status !== 'signed') {
            return $this->jsonRes(404, '签到未找到');
        }
        $checkInUser->status = 'invalid';
        $checkInUser->save();
        return $this->jsonRes(200, '签到已驳回');
    }

}
