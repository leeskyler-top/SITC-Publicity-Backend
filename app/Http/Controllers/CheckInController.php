<?php

namespace App\Http\Controllers;

use App\Http\Resources\CheckInResource;
use App\Http\Resources\CheckInUsersResource;
use App\Models\Activity;
use App\Models\ActivityUser;
use App\Models\CheckIn;
use App\Models\CheckInUser;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
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

    public function listMyCheckIns($status)
    {
        $statuses = ['waiting', 'started', 'ended'];
        if (!in_array($status, $statuses)) {
            return $this->jsonRes(404, '查询的状态不存在');
        }
        $checkins = CheckInUser::where(['user_id' => Auth::id(), 'status' => 'unsigned'])->get()->filter(function ($item) use ($status) {
            return $item->checkIn->status === $status;
        });
        return $this->jsonRes(200, '签到列表获取成功' . '(' . $status . ')', CheckInUsersResource::collection($checkins));
    }

    public function listCheckInsByActivity($id)
    {
        if (!is_numeric($id)) {
            return $this->jsonRes(404);
        }
        $activity = Activity::find($id);
        if (!$activity) {
            return $this->jsonRes(404);
        }
        $checkIns = $activity->checkIns;
        return $this->jsonRes(200, '列出所有签到成功', CheckInResource::collection($checkIns));
    }

    public function checkIn(Request $request, $id)
    {
        if (!is_numeric($id)) {
            return $this->jsonRes(404, '签到未找到');
        }
        $checkInUser = CheckInUser::find($id);
        if (!$checkInUser || $checkInUser->status !== 'unsigned' || $checkInUser->checkIn->status !== 'started') {
            return $this->jsonRes(404, '签到未找到');
        }
        $data = $request->only(['image_url']);
        $validator = Validator::make($data, [
            'image_url' => 'required|array',
            'image_url.*' => 'required|image'
        ], [
            'image_url' => '必须上传至少一张图片',
            'image_url.*' => '必须上传至少一张图片'
        ]);
        if ($validator->fails()) {
            return $this->jsonRes(422, $validator->errors()->first());
        }
        $checkInUser->status = 'signed';
        foreach ($data['image_url'] as $image) {
            $path = Storage::put('images/assigned', $image);
            $imageUrls[] = $path;
        }

        $checkInUser->image_url = json_encode($imageUrls);
        $checkInUser->save();
        return $this->jsonRes(200, '签到成功');
    }

    public function revokeCheckIn($id)
    {
        if (!is_numeric($id)) {
            return $this->jsonRes(404, '签到未找到');
        }
        $checkInUser = CheckInUser::find($id);
        if (!$checkInUser || $checkInUser->status !== 'signed' || $checkInUser->checkIn->status !== 'started') {
            return $this->jsonRes(404, '签到未找到');
        }
        $checkInUser->status = 'unsigned';
        $checkInUser->image_url = null;
        $checkInUser->save();
        Message::sendMsg('您的签到被驳回', '我们很遗憾的通知您，管理员驳回了您在' . $checkInUser->checkIn->title . '的签到，详情请咨询活动负责人或管理员', 'private', $checkInUser->user_id);
        return $this->jsonRes(200, '签到已驳回');
    }

    public function addUser(Request $request, $id)
    {
        if (!is_numeric($id)) {
            return $this->jsonRes(404, '签到未找到');
        }
        $checkIn = CheckIn::find($id);
        if (!$checkIn || $checkIn->status === 'ended') {
            return $this->jsonRes(404, '签到未找到');
        }
        $data = $request->only(['user_id']);
        $validator = Validator::make($data, [
            'user_id' => 'required|array',
            'user_id.*' => [
                'required',
                'integer',
                Rule::exists('user_id', 'id')->where(function ($query) {
                    $query->where('deleted_at');
                }),
                Rule::exists('check_in_users', 'user_id')->whereDoesntExist('check_in_users', 'check_in_id')
            ]]);
        if ($validator->fails()) {
            return $this->jsonRes(422, $validator->errors()->first());
        }
        $checkIn->checkInUsers()->syncWithoutDetaching($data['user_id'], ['check_in_id' => $checkIn->id]);
        Message::sendMsg('管理员将您列入一个待签到列表', '现在通知您，管理员将您纳入了一个名为' . $checkIn->title . '的签到列表', 'private', null);
        return $this->jsonRes(200, '人员已添加');
    }

    public function removeUser($id)
    {
        if (!is_numeric($id)) {
            return $this->jsonRes(404, '签到未找到');
        }
        $checkInUser = CheckInUser::find($id);
        if ($checkInUser || $checkInUser->checkIn->status === 'ended') {
            return $this->jsonRes(404, '签到未找到');
        }
        $checkInUser->delete();
        Message::sendMsg('管理员将您列入移出了一个待签到列表', '现在通知您，管理员将您从一个名为' . $checkInUser->checkIn->title . '的签到列表中移除', 'private', null);
        return $this->jsonRes(200, '人员已移除');
    }

    public function searchUserNotInCheckIn(Request $request, $check_in_id)
    {
        try {
            $checkIn = CheckIn::findOrFail($check_in_id);
        } catch (ModelNotFoundException $exception) {
            return $this->jsonRes(404, '活动未找到');
        }

        $data = $request->only(['info']);
        $checkIn_current_users = $checkIn->checkInUsers()->pluck('user_id')->toArray();

        $usersQuery = ActivityUser::where('activity_id', $checkIn->activity->id)
            ->whereNotIn('user_id', $checkIn_current_users);

        if (!isset($data['info']) || $data['info'] === '*' || $data['info'] === '') {
            $users = $usersQuery->get();
        } else {
            $usersQuery->orWhere(function ($query) use ($data, $checkIn_current_users) {
                $query->where('name', 'LIKE', '%' . $data['info'] . '%')
                    ->orWhere('uid', 'LIKE', '%' . $data['info'] . '%');
            });

            $users = $usersQuery->get();
        }

        return $this->jsonRes(200, "用户搜索成功", $users);
    }
}
