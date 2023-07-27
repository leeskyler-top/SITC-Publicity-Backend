<?php

namespace App\Http\Controllers;

use App\Http\Resources\ActivityApplicationResource;
use App\Http\Resources\ActivityResource;
use App\Models\Activity;
use App\Models\ActivityAudit;
use App\Models\ActivityUser;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ActivityController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $activities = Activity::all();
        return $this->jsonRes(200, '列出所有活动成功', ActivityResource::collection($activities));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->only([
            'title',
            'type',
            'place',
            'note',
            'start_time',
            'end_time',
            'user_id'
        ]);
        $validator = Validator::make($data, [
            'title' => 'required',
            'type' => 'required|in:self-enrollment,assignment,ase',
            'place' => 'required',
            'note' => 'required',
            'start_time' => 'required|date_format:Y-m-d H:i:s|after:now',
            'end_time' => 'required|date_format:Y-m-d H:i:s|after:start_time',
            'user_id' => [
                Rule::requiredIf(function () use ($data) {
                    if (isset($data['type'])) {
                        return $data['type'] === 'assignment';
                    }
                }),
                'array'
            ],
            'user_id.*' => [
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->where('deleted_at', null);
                }),
                'integer'
            ],
        ], [
            'title' => '标题必填',
            'type.required' => '类型必填',
            'type.in' => '类型必须是self-enrollment,assignment,ase中的一个',
            'place' => '地点必填',
            'note' => '需求必填',
            'start_time' => '开始时间必填，并且必须合法',
            'end_time' => '结束时间必填，并且不得早于开始时间',
            'user_id' => '用户必须是存在的',
        ]);
        if ($validator->fails()) {
            return $this->jsonRes(422, $validator->errors()->first());
        }
        $data['admin_id'] = Auth::id();
        if (isset($data['user_id'])) {
            $users = $data['user_id'];
            unset($data['user_id']);
        }
        $activity = Activity::create($data)->refresh();
        if ($data['type'] !== 'self-enrollment' && isset($users)) {
            $activity->users()->sync($users);
        }
        return $this->jsonRes(200, '活动创建成功', new ActivityResource($activity));

    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        if (!is_numeric($id)) {
            return $this->jsonRes(404, '活动未找到');
        }
        $activity = Activity::find($id);
        if (!$activity) {
            return $this->jsonRes(404, '活动未找到');
        }
        return $this->jsonRes(200, '活动查找成功', new ActivityResource($activity));

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        if (!is_numeric($id)) {
            return $this->jsonRes(404, '活动未找到');
        }
        $activity = Activity::find($id);
        if (!$activity) {
            return $this->jsonRes(404, '活动未找到');
        }
        $data = $request->only([
            'title',
            'place',
            'note',
            'start_time',
            'end_time',
            'user_id'
        ]);
        $data = array_filter($data, function ($value) {
            return !empty($value) || $value === 0;
        });
        $validator = Validator::make($data, [
            'title' => 'string',
            'place' => 'string',
            'note' => 'string',
            'start_time' => 'date_format:Y-m-d H:i:s',
            'end_time' => 'date_format:Y-m-d H:i:s|after:start_time',
            'user_id' => 'array',
            'user_id.*' => [
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->where('deleted_at', null);
                }),
            ],
        ], [
            'title' => '标题必填',
            'type.required' => '类型必填',
            'type.in' => '类型必须是self-enrollment,assignment,ase中的一个',
            'place' => '地点必填',
            'note' => '需求必填',
            'start_time' => '开始时间必填，并且必须合法',
            'end_time' => '结束时间必填，并且不得早于开始时间',
            'user_id' => '用户必须是存在的',
        ]);
        if ($validator->fails()) {
            return $this->jsonRes(422, $validator->errors()->first());
        }
        if (isset($data['user_id'])) {
            $activity->users()->syncWithoutDetaching($data['user_id']);
            unset($data['user_id']);
        }
        $activity->fill($data)->save();
        return $this->jsonRes(200, '活动修改成功', new ActivityResource($activity));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        if (!is_numeric($id)) {
            return $this->jsonRes(404, '活动未找到');
        }
        $activity = Activity::find($id);
        if (!$activity) {
            return $this->jsonRes(404, '活动未找到');
        }
        $activity->users()->detach();
        $activity->delete();
        return $this->jsonRes(200, "活动已删除");
    }

    public function removeUser($activity_id, $user_id)
    {
        if (!is_numeric($activity_id) || !is_numeric($user_id)) {
            return $this->jsonRes(404);
        }
        $activity = Activity::find($activity_id);
        if (!$activity) {
            return $this->jsonRes(404, '活动未找到');
        }
        $user_validator = $activity->users()->where('user_id', $user_id)->exists();
        if (!$user_validator) {
            return $this->jsonRes(404, '用户不存在');
        }
        $activity->users()->detach($user_id);
        return $this->jsonRes(200, "用户已移出");
    }

    public function searchUserNotInActivity(Request $request, $activity_id)
    {
        if (!is_numeric($activity_id)) {
            return $this->jsonRes(404);
        }
        $activity = Activity::find($activity_id);
        if (!$activity) {
            return $this->jsonRes(404, '活动未找到');
        }
        $data = $request->only(['info']);
        $activity_current_users = $activity->users()->pluck('user_id')->toArray(); // 获取已参与活动的用户ID数组
        if (!isset($data['info']) || !$data['info'] || $data['info'] === '*') {
            $users = User::whereNotIn('id', $activity_current_users)->get();
            return $this->jsonRes(200, "用户搜索成功", $users);
        }
        $users = User::where(function ($query) use ($data, $activity_current_users) {
            $query->where('name', 'LIKE', '%' . $data['info'] . '%')
                ->orWhere('uid', 'LIKE', '%' . $data['info'] . '%');
        })->whereNotIn('id', $activity_current_users)
            ->get();
        return $this->jsonRes(200, "用户搜索成功", $users);
    }

    public function listActivityByType($type)
    {
        $types = ['assignment', 'recruiting', 'applying', 'rejected', 'ended'];
        if (!in_array($type, $types)) {
            return $this->jsonRes(404, '查询的类型不存在');
        }
        $user = Auth::user();
        if ($type === 'assignment') {
            $activities = $user->activities()->orderBy('start_time', 'desc')->where('end_time', '>', now())->get();
            return $this->jsonRes(200, "活动获取成功", $activities);
        } else if ($type === 'recruiting') {
            $activities = Activity::where('is_enrolling', '1')->where(function ($query) {
                $query->where('type', 'ase')
                    ->orWhere('type', 'self-enrollment');
            })->whereDoesntHave('users', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })->where('start_time', '>', now())->get();
            return $this->jsonRes(200, "活动获取成功", $activities);
        }
        // 还不知道对不对
        else if ($type === 'applying' || $type === 'rejected') {
            $status = ($type === 'applying') ? 'applying' : 'rejected';
            $activities = $user->activityApplications()
                ->where('status', $status)
                ->whereHas('activity', function ($query) {
                    $query->where('start_time', '>', now());
                })
                ->get();
            return $this->jsonRes(200, "活动获取成功", $activities);
        } else if ($type === 'ended') {
            $activities = Activity::where('start_time', '<', now())->get();
            return $this->jsonRes(200, "活动获取成功", $activities);
        }
    }

    public function listEnrollments($type)
    {
        $types = ['agreed', 'rejected', 'applying', 'all'];
        if (!in_array($type, $types)) {
            return $this->jsonRes(404, "状态不存在");
        }
        if ($type === 'all') {
            $applications = ActivityAudit::orderBy('created_at', 'desc')->get();
            return $this->jsonRes(200, "列出所有申请成功", ActivityApplicationResource::collection($applications));
        } else {
            $applications = ActivityAudit::where('status', $type)->whereHas('activity', function ($query) {
                $query->where('start_time', '>', now());
            })->get();
            return $this->jsonRes(200, "列出所有申请" . '(' . $type . ')' . '成功', ActivityApplicationResource::collection($applications));
        }
    }

    public function agreeEnrollmnent($enrollment_id)
    {
        if (!is_numeric($enrollment_id)) {
            return $this->jsonRes(404, '申请未找到');
        }
        $application = ActivityAudit::find($enrollment_id);
        if (!$application) {
            return $this->jsonRes(404, '申请未找到');
        }
        $activityStartTime = $application->activity->start_time;
        $startTimeCarbon = Carbon::parse($activityStartTime);
        $application_validator = $startTimeCarbon->isPast();
        if ($application_validator) {
            return $this->jsonRes(400, "申请已过期");
        }
        if ($application->status !== 'applying') {
            return $this->jsonRes(400, "申请状态错误");
        }
        $application->status = "agreed";
        $application->save();
        ActivityUser::create([
            'user_id' => $application->user_id,
            'activity_id' => $application->activity_id,
        ]);
        return $this->jsonRes(200, "已同意此申请");
    }

    public function rejectEnrollmnent($enrollment_id)
    {
        if (!is_numeric($enrollment_id)) {
            return $this->jsonRes(404, '申请未找到');
        }
        $application = ActivityAudit::find($enrollment_id);
        if (!$application) {
            return $this->jsonRes(404, '申请未找到');
        }
        $activityStartTime = $application->activity->start_time;
        $startTimeCarbon = Carbon::parse($activityStartTime);
        $application_validator = $startTimeCarbon->isPast();
        if ($application_validator) {
            return $this->jsonRes(400, "申请已过期");
        }
        if ($application->status !== 'applying') {
            return $this->jsonRes(400, "申请状态错误");
        }
        $application->status = "rejected";
        $application->save();
        return $this->jsonRes(200, "已拒绝此申请");
    }

    public function enrollActivity($activity_id)
    {
        if (!is_numeric($activity_id)) {
            return $this->jsonRes(404, "活动未找到");
        }
        $user = Auth::user();
        $activity = Activity::find($activity_id);
        if (!$activity) {
            return $this->jsonRes(404, "活动未找到");
        }
        if ($activity->status !== 'waiting' || $activity->is_enrolling !== '1' || !($activity->type === 'ase' || $activity->type === 'assigment')) {
            return $this->jsonRes(404, "活动不允许报名或活动已开始");
        }
        if ($activity->activityAudits()->where('user_id', $user->id)->exists()) {
            return $this->jsonRes(400, "您已报名此活动");
        }
        $activity->activityAudits()->create([
            'user_id' => $user->id
        ]);
        return $this->jsonRes(200, "活动报名成功");
    }
}
