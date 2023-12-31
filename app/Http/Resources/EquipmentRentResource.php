<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EquipmentRentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $arr = [
            'id' => $this->id,
            'equipment_fixed_assets_num' => $this->loadMissing('equipment')->equipment->fixed_assets_num,
            'equipment_name' => $this->loadMissing('equipment')->equipment->name,
            'equipment_model' => $this->loadMissing('equipment')->equipment->model,
            'user_uid' => $this->loadMissing('user')->user->uid,
            'user_name' => $this->loadMissing('user')->user->name,
            'audit_time' => $this->audit_time,
            'apply_time' => $this->apply_time,
            'back_time' => $this->back_time,
            'report_time' => $this->report_time,
            'assigned_url' => $this->assigned_url,
            'returned_url' => $this->returned_url,
            'damaged_url' => $this->damaged_url,
            'status' => $this->status,
            'created_at' => Carbon::parse($this->created_at)->format("Y-m-d H:i:s"),
        ];
        if (!$this->audit) {
            $arr['audit_uid'] = null;
            $arr['audit_name'] = null;
        } else {
            $arr['audit_uid'] = $this->loadMissing('audit')->audit->uid;
            $arr['audit_name'] = $this->loadMissing('audit')->audit->name;
        }
        return $arr;
    }
}
