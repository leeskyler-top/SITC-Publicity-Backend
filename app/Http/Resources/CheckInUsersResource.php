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
            'id' => $this->id,
            'title' => $this->checkIn->title,
            'activity_title' => $this->checkIn->activity->title,
            'activity_place' => $this->checkIn->activity->place,
            'start_time' => $this->checkIn->start_time,
            'end_time' => $this->checkIn->end_time,
            'checkin_status' => $this->checkIn->status,
            'status' => $this->status
        ];
    }
}
