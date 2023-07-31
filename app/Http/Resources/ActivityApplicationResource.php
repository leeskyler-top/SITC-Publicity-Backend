<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivityApplicationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request)
    {
        $arr = [
            'id' => $this->id,
            'user_uid' => $this->user->uid,
            'user_name' => $this->user->name,
            'activity' => $this->activity,
            'admin_uid' => $this->activity->loadMissing('admin')->admin->uid,
            'admin_name' => $this->activity->loadMissing('admin')->admin->name,
            'status' => $this->status,
            'created_at' => Carbon::parse($this->created_at)->format("Y-m-d H:i:s"),
            'updated_at' => Carbon::parse($this->updated_at)->format("Y-m-d H:i:s"),
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
