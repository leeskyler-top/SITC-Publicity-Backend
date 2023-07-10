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

        $arr = [
            'id' => $this->id,
            'equipment_rent_id' => $this->equipmentRent->id,
            'user_uid' => $this->user->uid,
            'user_name' => $this->user->name,
            'apply_time' => $this->apply_time,
            'reason' => $this->reason,
            'status' => $this->status,
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
