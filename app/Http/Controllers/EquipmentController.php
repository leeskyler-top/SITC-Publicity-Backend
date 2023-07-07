<?php

namespace App\Http\Controllers;

use App\Http\Resources\EquipmentDelayApplicationResource;
use App\Http\Resources\EquipmentRentResource;
use App\Models\Equipment;
use App\Models\EquipmentDelayApplication;
use App\Models\EquipmentRent;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class EquipmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $equipments = Equipment::all();
        return $this->jsonRes(200, "设备获取成功", $equipments);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->only([
            'fixed_assets_num',
            'name',
            'model',
            'status',
            'create_time'
        ]);

        $validator = Validator::make($data, [
            'fixed_assets_num' => 'required',
            'name' => 'required',
            'model' => 'required',
            'status' => 'required|in:damaged,unassigned,scrapped',
            'create_time' => 'required|date_format:Y-m-d H:i:s|before:tomorrow'
        ], [
            'fixed_assets_num' => '固定资产编号必填',
            'name' => '设备名称必填',
            'model' => '模型必填',
            'status' => '状态必须为空闲或受损',
            'create_time' => '入库时间必须为合法时间'
        ]);

        if ($validator->fails()) {
            return $this->jsonRes(422, $validator->errors()->first());
        }

        $equipment = Equipment::create($data);
        $equipment->refresh();
        return $this->jsonRes(200, "设备创建成功", $equipment);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        if (!is_numeric($id)) {
            return $this->jsonRes(404, '未找到设备');
        }
        $equipment = Equipment::find($id);
        if (!$equipment) {
            return $this->jsonRes(404, '设备未找到');
        }
        $equipment->assigned_rent = new EquipmentRentResource($equipment->equipmentRents()->where(function ($query) {
            $query->where('status', 'assigned')
                ->orWhere('status', 'delay-applying')
                ->orWhere('status', 'delayed');
        })->first());
        return $this->jsonRes(200, '设备查找成功', $equipment);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        if (!is_numeric($id)) {
            return $this->jsonRes(404, '未找到设备');
        }
        $equipment = Equipment::find($id);
        if (!$equipment) {
            return $this->jsonRes(404, '设备未找到');
        }

        $data = $request->only([
            'fixed_assets_num',
            'name',
            'model',
            'status',
            'create_time',
            'user_id',
            'apply_time'
        ]);
        $data = array_filter($data, function ($value) {
            return !empty($value) || $value === 0;
        });
        $validator = Validator::make($data, [
            'fixed_assets_num' => 'nullable|string',
            'name' => 'nullable|string',
            'model' => 'nullable|string',
            'status' => 'nullable|in:missed,damaged,assigned,unassigned,scrapped',
            'create_time' => 'nullable|date_format:Y-m-d H:i:s|before:tomorrow',
            'user_id' => [
                Rule::requiredIf(function () use ($data) {
                    if (isset($data['status'])) {
                        return $data['status'] === 'assigned';
                    }
                }),
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->where('deleted_at', null);
                }),
            ],
            'apply_time' => [
                Rule::requiredIf(function () use ($data) {
                    if (isset($data['status'])) {
                        return $data['status'] === 'assigned';
                    }
                }),
                'date_format:Y-m-d H:i:s',
                'after:yesterday'
            ]
        ], [
            'fixed_assets_num' => '固定资产编号必须为字符串',
            'name' => '设备名称必须为字符串',
            'model' => '模型必须为字符串',
            'status' => '状态必须为空闲或已分配或受损',
            'user_id' => '当状态为已分配，必须分配用户',
            'apply_time' => '当状态为已分配，必须为用户填写承诺归还时间，并且时间合法',
            'create_time' => '入库时间必须为合法时间'
        ]);

        if ($validator->fails()) {
            return $this->jsonRes(422, $validator->errors()->first());
        }
        $statuses = ['assigned', 'delay-applying', 'delayed'];
        $equipment_application_exists = Equipment::find($id)->equipmentRents()->where('equipment_id', $equipment->id)
            ->whereIn('status', $statuses)
            ->exists();
        if (isset($data['status']) && $data['status'] === 'assigned' && $equipment_application_exists) {
            return $this->jsonRes(400, "设备使用中，不可分配。");
        }
        if (isset($data['status']) && $data['status'] === 'assigned' && !$equipment_application_exists) {
            EquipmentRent::create([
                'equipment_id' => $equipment->id,
                'user_id' => $data['user_id'],
                'audit_id' => Auth::id(),
                'audit_time' => Carbon::now()->format("Y-m-d H:i:s"),
                'apply_time' => $data['apply_time'],
                'status' => 'assigned'
            ]);
        }
        if (isset($data['status']) && $data['status'] !== 'assigned' && $equipment_application_exists) {
            $equipment_applications = Equipment::find($id)->equipmentRents()->where('equipment_id', $equipment->id)
                ->whereIn('status', $statuses)
                ->get();
            if ($data['status'] === 'unassigned') {
                $status = 'returned';
            } else {
                $status = $data['status'];
            }
            foreach ($equipment_applications as $equipment_apply) {
                $equipment_apply->status = $status;
                $equipment_apply->save();
            }
        }
        unset($data['user_id']);
        unset($data['apply_time']);

        $equipment->fill($data)->save();
        return $this->jsonRes(200, "修改成功", $equipment);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        if (!is_numeric($id)) {
            return $this->jsonRes(404, '未找到设备');
        }
        $equipment = Equipment::find($id);
        if (!$equipment) {
            return $this->jsonRes(404, '设备未找到');
        } else if ($equipment->status === 'assigned') {
            return $this->jsonRes(400, '设备正在使用中');
        } else {
            $equipment->delete();
            return $this->jsonRes(200, "设备删除成功");
        }
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
            'fixed_assets_num' => 'required|string',
            'name' => 'required|string',
            'model' => 'required|string',
            'status' => 'required|in:damaged,unassigned,scrapped',
            'create_time' => 'required|date_format:Y-m-d H:i|before:tomorrow'
        ];

        DB::beginTransaction();

        try {
            foreach ($file as $key => $element) {
                if ($key === 0) { // Skip the header row
                    continue;
                }
                if (count($element) !== 5) {
                    throw new \Exception("Invalid CSV format. Please check the file contents.");
                }
                $validator = Validator::make([
                    'fixed_assets_num' => $element[0],
                    'name' => $element[1],
                    'model' => $element[2],
                    'status' => $element[3],
                    'create_time' => $element[4]
                ], $validationRules, [
                    'fixed_assets_num' => '固定资产编号必须为字符串',
                    'name' => '设备名称必须为字符串',
                    'model' => '模型必须为字符串',
                    'status' => '状态必须为空闲或受损或已报废',
                    'create_time' => '入库时间必须为合法时间'
                ]);
                if ($validator->fails()) {
                    DB::rollBack();
                    return $this->jsonRes(400, $validator->errors());
                }
                $equipment = Equipment::create([
                    'fixed_assets_num' => $element[0],
                    'name' => $element[1],
                    'model' => $element[2],
                    'status' => $element[3],
                    'create_time' => $element[4]
                ]);
                $equipment->refresh();
                $equipments[] = $equipment;
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['msg' => '在执行批量操作时发生严重错误，已回滚操作！'], 500);
        }

        return $this->jsonRes(200, '设备批量添加完成', $equipments);
    }

    /*
     * 用户功能
     */

    // 我的设备
    public function showMyEquipment($status)
    {
        $user = Auth::user();
        $valid_status = ['applying', 'using', 'returned', 'rejected', 'assigned', 'delay-applying', 'delayed', 'damaged', 'missed'];
        if (!in_array($status, $valid_status)) {
            return $this->jsonRes(404, '查询的状态不存在');
        }
        if ($status === 'using') {
            $equipments = $user->equipmentRents()->where(function ($query) {
                $query->where('status', 'assigned')
                    ->orWhere('status', 'delay-applying')
                    ->orWhere('status', 'delayed');
            })->get();
            return $this->jsonRes(200, '获取我的设备列表成功' . '(' . $status . ')', EquipmentRentResource::collection($equipments));
        }
        $equipments = $user->equipmentRents()->where('status', $status)->get();
        return $this->jsonRes(200, '获取我的设备列表成功' . '(' . $status . ')', EquipmentRentResource::collection($equipments));
    }

    // 列出空闲状态的设备
    public function indexUnassignedEquipments()
    {
        $equipments = Equipment::where('status', 'unassigned')->get();
        return $this->jsonRes(200, "列出所有空闲设备成功", $equipments);
    }

    // 设备申请
    public function equipmentApply(Request $request)
    {
        $data = $request->only([
            'equipment_id',
            'apply_time',
            'assigned_url'
        ]);
        $validator = Validator::make($data, [
            'equipment_id' => [
                'required',
                Rule::exists("equipment", 'id')->where("status", 'unassigned')],
            'apply_time' => 'required|date_format:Y-m-d H:i:s|after:now',
            'assigned_url' => 'required|array',
            'assigned_url.*' => 'required|image'
        ], [
            'equipment_id.required' => '设备ID必填',
            'equipment_id.exists' => '设备不存在或设备状态不符合要求',
            'apply_time.required' => '申请归还时间必填',
            'apply_time.after' => '不得早于当前时间',
            'apply_time.date_format' => '日期格式不正确，必须为Y-m-d H:i:s',
            'assigned_url' => '必须为数组',
            'assigned_url.*' => '必须是图片'
        ]);
        if ($validator->fails()) {
            return $this->jsonRes(422, $validator->errors()->first());
        }

        $apply_validator = Auth::user()->equipmentRents()->where(function ($query) use ($data) {
            $query->where(['status' => 'applying', 'equipment_id' => $data['equipment_id']]);
        })->exists();
        if ($apply_validator) {
            return $this->jsonRes(400,'您已申请此设备');
        }

        foreach ($data['assigned_url'] as $image) {
            $path = Storage::put('images/assigned', $image);
            $assignedUrls[] = $path;
        }

        $data['assigned_url'] = json_encode($assignedUrls);
        $data['status'] = 'applying';
        $data['user_id'] = Auth::id();
        $equipment = EquipmentRent::create($data);
        $equipment->refresh();
        return $this->jsonRes(200, "设备申请成功", $equipment);
    }

    // 设备归还
    public function back(Request $request, $equipment_id)
    {
        if (!is_numeric($equipment_id)) {
            return $this->jsonRes(404, '试图查找的设备未找到');
        }
        $equipment = Equipment::find($equipment_id);
        $user = Auth::user();
        if (!$equipment) {
            return $this->jsonRes(404, '试图查找的设备未找到');
        }
        $statuses = ['assigned', 'delay-applying', 'delayed'];
        $application_validator = $user->equipmentRents()->where('equipment_id', $equipment->id)
            ->whereIn('status', $statuses)
            ->exists();
        if (!$equipment || !$application_validator) {
            return $this->jsonRes(404, '试图查找的设备未找到');
        }
        $data = $request->only([
            'returned_url'
        ]);

        $validator = Validator::make($data, [
            'returned_url' => 'required|array',
            'returned_url.*' => 'required|image'
        ], [
            'returned_url' => '必须为数组',
            'returned_url.*' => '必须是图片'
        ]);

        if ($validator->fails()) {
            return $this->jsonRes(422, $validator->errors()->first());
        }

        foreach ($data['returned_url'] as $image) {
            $path = Storage::put('images/returned', $image);
            $returnedUrls[] = $path;
        }
        // 理论上，一个设备只会有一个人在使用，为防止多人使用的Bug出现，使用get使每个出现的错误条目都变为相同状态。
        $applications = $user->equipmentRents()->where('equipment_id', $equipment->id)
            ->whereIn('status', $statuses)
            ->get();
        $data['returned_url'] = json_encode($returnedUrls);
        $equipment->status = 'unassigned';
        $equipment->save();
        foreach ($applications as $application) {
            $application->status = 'returned';
            $application->returned_url = $data['returned_url'];
            $application->save();
        }
        return $this->jsonRes(200, '设备归还成功');
    }

    // 延期申报
    public function delayApply(Request $request, $equipment_rent_application_id)
    {
        if (!is_numeric($equipment_rent_application_id)) {
            return $this->jsonRes(404, '未找到此出借信息');
        }
        $user = Auth::user();
        $statuses = ['assigned', 'delayed'];
        $application= EquipmentRent::find($equipment_rent_application_id);
        if (!$application) {
            return $this->jsonRes(404, '试图查找的出借信息未找到');
        }
        $application_validator = EquipmentRent::where('id', $application->id)->whereIn('status', $statuses)->exists();
        if (!$application_validator) {
            return $this->jsonRes(404, '不是符合申请条件的出借信息');
        }
        $data = $request->only([
            'reason',
            'apply_time'
        ]);
        $validator = Validator::make($data, [
            'reason' => 'required',
            'apply_time' => 'required|date_format:Y-m-d H:i:s|after:now'
        ], [
            'reason' => '原因必填',
            'apply_time' => '申请日期必填，并且时间必须合法',
        ]);
        if ($validator->fails()) {
            return $this->jsonRes(422, $validator->errors()->first());
        }
        $data['equipment_rent_id'] = $application->id;
        $data['user_id'] = $user->id;
        $eda = EquipmentDelayApplication::create($data);
        $eda->refresh();
        $eda->equipmentRent->status = 'delay-applying';
        $eda->equipmentRent->save();
        return $this->jsonRes(200, "设备延期申请提交成功");
    }

    // 设备异常报告
    public function reportEquipment(Request $request, $equipment_rent_application_id)
    {
        if (!is_numeric($equipment_rent_application_id)) {
            return $this->jsonRes(404, '没有此出借ID');
        }
        $user = Auth::user();
        $equipment_rent = $user->equipmentRents()->find($equipment_rent_application_id);
        if (!$equipment_rent) {
            return $this->jsonRes(404, '没有此出借ID');
        }
        $equipment = $equipment_rent->equipment;
        if ($equipment->status !== 'assigned') {
            return $this->jsonRes(400, '出借状态错误');
        }
        $data = $request->only([
            'type',
            'damaged_url'
        ]);

        $data = array_filter($data, function ($value) {
            return !empty($value) || $value === 0;
        });

        $validator = Validator::make($data, [
            'type' => 'required|in:damaged,missed',
            'damaged_url' => [
                Rule::requiredIf(function () use ($data) {
                    return $data['type'] === 'damaged';
                }),
                'array'
            ],
            'damaged_url.*' => 'required|image',
        ], [
            'type.required' => '类型必须为damaged或missed',
            'type.in' => '类型必须为damaged或missed',
            'damaged_url.required' => '当类型为damaged时，此项目必填',
            'damaged_url.array' => 'damaged_url必须为数组',
            'damaged_url.*.required' => '至少有一个内容',
            'damaged_url.*.image' => '内容必须为图片',
        ]);
        if ($validator->fails()) {
            return $this->jsonRes(422, $validator->errors()->first());
        }
        $data['report_time'] = Carbon::now()->format('Y-m-d H:i:s');
        if ($data['type'] === 'damaged') {
            foreach ($data['damaged_url'] as $image) {
                $path = Storage::put('images/assigned', $image);
                $damagedUrls[] = $path;
            }
            $data['damaged_url'] = json_encode($damagedUrls);
        }
        if ($data['type'] === 'missed' && isset($data['damaged_url'])) {
            unset($data['damaged_url']);
        }

        $equipment_rent->fill($data)->save();
        $equipment->status = $data['type'];
        $equipment->save();
        return $this->jsonRes(200, '设备异常已报告成功', new EquipmentRentResource($equipment_rent));
    }

    /*
     * 管理员功能
     */

    // 列出审批列表
    public function indexApplicationList($status)
    {
        $valid_status = ['applying', 'delay-applying', 'rejected', 'assigned'];
        if (!in_array($status, $valid_status)) {
            return $this->jsonRes(404, '查询的状态不存在');
        }
        $equipment_enrollments = EquipmentRent::where('status', $status)->get();
        return $this->jsonRes(200, "审核列表获取成功", EquipmentRentResource::collection($equipment_enrollments));
    }

    // 同意设备申请
    public function agreeApplication(Request $request, $equipment_application_id)
    {
        if (!is_numeric($equipment_application_id)) {
            return $this->jsonRes(404, '设备申请未找到');
        }

        $application = EquipmentRent::find($equipment_application_id);

        if (!$application || $application->status !== 'applying') {
            return $this->jsonRes(404, '设备申请未找到');
        }

        $application->status = 'assigned';
        $application->audit_id = Auth::id();
        $application->audit_time = Carbon::now()->format("Y-m-d H:i:s");
        $application->save();

        // 更新设备状态为"assigned"
        $equipment = $application->equipment;
        if ($equipment) {
            $equipment->status = 'assigned';
            $equipment->save();
        }

        // 拒绝其他申请
        $otherApplications = $equipment->equipmentRents()->where('status', 'applying')->where('id', '!=', $equipment_application_id)->get();
        foreach ($otherApplications as $otherApplication) {
            $otherApplication->status = 'reject';
            $application->audit_id = Auth::id();
            $application->audit_time = Carbon::now()->format("Y-m-d H:i:s");
            $otherApplication->save();
        }

        return $this->jsonRes(200, '此申请已通过');
    }

    // 拒绝设备申请
    public function rejectApplication(Request $request, $equipment_application_id)
    {
        if (!is_numeric($equipment_application_id)) {
            return $this->jsonRes(404, '设备申请未找到');
        }

        $application = EquipmentRent::find($equipment_application_id);

        if (!$application || $application->status !== 'applying') {
            return $this->jsonRes(404, '设备申请未找到');
        }

        $application->status = 'reject';
        $application->audit_id = Auth::id();
        $application->audit_time = Carbon::now()->format("Y-m-d H:i:s");
        $application->save();

        return $this->jsonRes(200, '此申请已拒绝');
    }

    // 列出待延期申报
    public function indexDelayApplication()
    {
        $eda = EquipmentDelayApplication::where(['status' => 'delay-applying'])->get();
        return $this->jsonRes(200, '列出所有待延期申请成功', EquipmentDelayApplicationResource::collection($eda));
    }

    // 列出此设备申请的所有延期申报
    public function indexAllDelayApplicationByERID($equipment_rent_application_id)
    {
        if (!is_numeric($equipment_rent_application_id)) {
            return $this->jsonRes(404, '没有此出借ID');
        }
        $equipment_rent = EquipmentRent::find($equipment_rent_application_id);
        if (!$equipment_rent) {
            return $this->jsonRes(404, '没有此出借ID');
        }
        $eda = $equipment_rent->equipmentDelayApplications;
        return $this->jsonRes(200, '获取此设备申请ID的延期申请成功', EquipmentDelayApplicationResource::collection($eda));
    }

    // 同意延期
    public function agreeEquipmentDelayApplication($equipment_delay_id)
    {
        if (!is_numeric($equipment_delay_id)) {
            return $this->jsonRes(404, "设备延期申请未找到");
        }
        $eda = EquipmentDelayApplication::find($equipment_delay_id);
        if (!$eda || $eda->status !== 'delay-applying') {
            return $this->jsonRes(404, "设备延期申请未找到");
        }
        $eda->audit_id = Auth::id();
        $eda->status = 'delayed';
        $eda->save();

        $eda->equipmentRent->status = 'delayed';
        $eda->equipmentRent->apply_time = $eda->apply_time;
        $eda->equipmentRent->save();

        return $this->jsonRes(200, '设备借用申请已延期');
    }

    // 拒绝延期
    public function rejectEquipmentDelayApplication($equipment_delay_id)
    {
        if (!is_numeric($equipment_delay_id)) {
            return $this->jsonRes(404, "设备延期申请未找到");
        }
        $eda = EquipmentDelayApplication::find($equipment_delay_id);
        if (!$eda || $eda->status !== 'delay-applying') {
            return $this->jsonRes(404, "设备延期申请未找到");
        }
        $eda->audit_id = Auth::id();
        $eda->status = 'rejected';
        $eda->save();

        return $this->jsonRes(200, '已拒绝延期申请');
    }

    // 列出主动上报的设备异常
    public function indexReports($status)
    {
        $valid_status = ['damaged', 'missed'];
        if (!in_array($status, $valid_status)) {
            return $this->jsonRes(404, '状态不存在');
        }
        $equipment_rents = EquipmentRent::where('status', $status)->get();
        return $this->jsonRes(200, '获取上报的设备异常成功', EquipmentRentResource::collection($equipment_rents));
    }

    // 设备出借历史
    public function indexRentHistory()
    {
        $equipment_rent = EquipmentRent::all();
        return $this->jsonRes(200, '获取设备出借历史成功', EquipmentRentResource::collection($equipment_rent));
    }
}
