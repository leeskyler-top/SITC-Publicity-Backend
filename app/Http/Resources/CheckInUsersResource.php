<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CheckInUsersResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'activity_id' => $this->activity_id,
            'activity_title' => $this->loadMissing('activity')->activity->title,
            'activity_place' => $this->loadMissing('activity')->activity->place,
            'user_uid' => $this->loadMissing('users')->checkInUsers->uid,
            'user_department' => $this->loadMissing('users')->checkInUsers->department,
            'user_name' => $this->loadMissing('users')->checkInUsers->name,
            'status' => $this->status
        ];
    }
}
