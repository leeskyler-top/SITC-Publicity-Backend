<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

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

    // 我的设备
    public function showMyEquipment($type)
    {
        $user = Auth::user();
    }

    // 设备申请
    // 设备归还
    // 延期申报
    // 设备异常报告
    // 设备出借历史
}
