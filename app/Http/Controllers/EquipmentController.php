<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use App\Models\EquipmentRent;
use App\Models\User;
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
            'model'
        ]);

        $validator = Validator::make($data, [
            'fixed_assets_num' => 'required',
            'name' => 'required',
            'model' => 'required',
            'status' => 'required|in:damaged,unassigned',
            'create_time' => 'required|date_format:Y-m-d H:i:s'
        ], [
            'fixed_assets_num' => '固定资产编号必填',
            'name' => '设备名称必填',
            'model' => '模型必填',
            'status' => '状态必须为空闲或受损',
            'create_time' => '入库时间必填'
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
            'model'
        ]);

        $data = array_filter($data, function ($value) {
            return !empty($value) || $value == 0;
        });

        $validator = Validator::make($data, [
            'fixed_assets_num' => 'nullable|string',
            'name' => 'nullable|string',
            'model' => 'nullable|string',
            'status' => 'nullable|in:damaged,unassigned',
            'create_time' => 'nullable|date_format:Y-m-d H:i:s'
        ], [
            'fixed_assets_num' => '固定资产编号必须为字符串',
            'name' => '设备名称必须为字符串',
            'model' => '模型必须为字符串',
            'status' => '状态必须为空闲或受损',
            'create_time' => '入库时间必须为时间'
        ]);

        if ($validator->fails()) {
            return $this->jsonRes(422, $validator->errors()->first());
        }

        $equipment->fill($data)->refresh();
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
            return $this->jsonRes(400, '错误的请求：设备正在使用中');
        } else {
            $equipment->delete();
            return $this->jsonRes(200, null, "设备删除成功");
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
            'fixed_assets_num' => 'nullable|string',
            'name' => 'nullable|string',
            'model' => 'nullable|string',
            'status' => 'nullable|in:damaged,unassigned',
            'create_time' => 'nullable|date_format:Y-m-d H:i:s'
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
                ], $validationRules,  [
                    'fixed_assets_num' => '固定资产编号必须为字符串',
                    'name' => '设备名称必须为字符串',
                    'model' => '模型必须为字符串',
                    'status' => '状态必须为空闲或受损',
                    'create_time' => '入库时间必须为时间'
                ]);
                if ($validator->fails()) {
                    DB::rollBack();
                    return $this->jsonRes(400, $validator->errors()->first());
                }
                $equipment = Equipment::create([
                    'is_admin' => $element[0],
                    'uid' => $element[1],
                    'name' => $element[2],
                    'email' => $element[3],
                    'password' => $element[4],
                    'department' => $element[5],
                    'classname' => $element[6],
                    'note' => $element[7],
                ]);
                $equipment->refresh();
                $equipments[] = $equipment;
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['msg' => '在执行批量操作时发生严重错误，已回滚操作！'],500);
        }

        return $this->jsonRes(200, '设备批量添加完成', $equipments);
    }


    // 我的设备
    public function showMyEquipment($status)
    {
        $user = Auth::user();
        $valid_status = ['applying', 'returned', 'reject', 'assigned', 'damaged', 'missed'];
        if (!in_array($status, $valid_status)) {
            return $this->jsonRes(404,'查询的状态不存在');
        }
        $equipments =$user->equipmentRents()->where('status', $status)->get();
        return $this->jsonRes(200, '获取我的设备列表成功' . '('.$status.')', $equipments);
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
                Rule::exists("equipments", 'id')->where("status", 'unassigned')
            ],
            'apply_time' => 'required|date_format:Y-m-d H:i|after:now',
            'assigned_url' => 'required|array',
            'assigned_url.*' => 'required|image'
        ], [
            'equipment.required' => '设备ID必填',
            'equipment.exists' => '设备不存在或设备状态不符合要求',
            'apply_time.required' => '申请归还时间必填',
            'apply_time.after' => '不得早于当前时间',
            'assigned_url' => '必须为数组',
            'assigned_url.*' => '必须是图片'
        ]);
        if ($validator->fails()) {
            return $this->jsonRes(422, $validator->errors()->first());
        }

        foreach ($data['assigned_url'] as $image) {
            $path = Storage::putFile('images/assigned', $image);
            $assignedUrls[] = asset('storage/' . $path);
        }

        $data['assigned_url'] = $assignedUrls;
        $data['status'] = 'applying';
        $equipment = EquipmentRent::create($data);
        $equipment->refresh();
        return $this->jsonRes(200, "设备申请成功", $equipment);
    }
    // 列出审批列表
    public function indexAuditList($status)
    {
        $valid_status = ['applying', 'reject', 'damaged', 'missed'];
        if (!in_array($status, $valid_status)) {
            return $this->jsonRes(404,'查询的状态不存在');
        }
        $equipment_enrollments = EquipmentRent::where('status', $status)->get();
        return $this->jsonRes(200, "审核列表获取成功", $equipment_enrollments);
    }
    // 设备审批
    public function auditApplication($id)
    {
        if (!is_numeric($id)) {
            return $this->jsonRes(404, '设备申请未找到');
        }

        $application = EquipmentRent::find($id);

        if (!$application || $application->status !== 'applying') {
            return $this->jsonRes(404, '设备申请未找到');
        }

        $application->status = 'assigned';
        $application->save();

        // 更新设备状态为"assigned"
        $equipment = $application->equipment;
        if ($equipment) {
            $equipment->status = 'assigned';
            $equipment->save();
        }

        // 拒绝其他申请
        $otherApplications = $equipment->equipmentRents()->where('status', 'applying')->where('id', '!=', $id)->get();
        foreach ($otherApplications as $otherApplication) {
            $otherApplication->status = 'reject';
            $otherApplication->save();
        }

        return $this->jsonRes(200, '此申请已通过', $application);
    }

    // 设备归还
    // 延期申报
    // 设备异常报告
    // 设备出借历史
}
