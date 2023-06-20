<?php

namespace App\Http\Resources;

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
        return [
            'id' => $this->id,
            'equipment_fixed_assets_num' => $this->equipment->name,
            'equipment_name' => $this->equipment->name,
            'equipment_model' => $this->equipment->model,
            'user_uid' => $this->user->uid,
            'user_name' => $this->user->name,
            'audit_uid' => $this->audit->uid,
            'audit_name' => $this->audit->name,
            'audit_time' => $this->audit_time,
            'apply_time' => $this->apply_time,
            'back_time' => $this->back_time,
            'report_time' => $this->report_time,
            'assigned_url' => $this->assigned_url,
            'returned_url' => $this->returned_url,
            'damaged_url' => $this->damaged_url,
            'status' => $this->status,
        ];
    }
}
