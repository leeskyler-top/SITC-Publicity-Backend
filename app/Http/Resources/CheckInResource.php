<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CheckInResource extends JsonResource
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
            'activity_id' =>  $this->activity_id,
            'activity_title' => $this->loadMissing('activity')->activity->title,
            'activity_place' => $this->loadMissing('activity')->activity->place,
            'admin_uid' => $this->loadMissing('admin')->checkInUsers->uid,
            'admin_name' => $this->loadMissing('admin')->checkInUsers->name,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'status' => $this->status,
            'checkInUsers' => CheckInUsersResource::collection($this->checkInUsers)
        ];
    }
}
