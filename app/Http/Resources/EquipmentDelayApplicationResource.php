<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EquipmentDelayApplicationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'equipment_rent_id' => $this->equipment_rent,
            'user_uid' => $this->user->uid,
            'user_name' => $this->user->name,
            'audit_uid' => $this->audit->uid,
            'audit_name' => $this->audit->name,
            'apply_time' => $this->apply_time,
            'reason' => $this->reason,
            'status' => $this->status,
        ];
    }
}
