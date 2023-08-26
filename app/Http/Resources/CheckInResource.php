<?php

namespace App\Http\Resources;

use Carbon\Carbon;
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
            'title' =>  $this->title,
            'activity_title' => $this->loadMissing('activity')->activity->title,
            'activity_place' => $this->loadMissing('activity')->activity->place,
            'admin_uid' => $this->loadMissing('admin')->admin->uid,
            'admin_name' => $this->loadMissing('admin')->admin->name,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'status' => $this->status,
            'checkInUsers' => $this->checkInUsers->map(function ($item) {
                return [
                    'id' => $item->pivot->id,
                    'uid' => $item->uid,
                    'department' => $item->department,
                    'classname' => $item->classname,
                    'name' => $item->name,
                    'image_url' => $item->pivot->image_url,
                    'status' => $item->pivot->status,
                    'updated_at' => Carbon::parse($item->pivot->updated_at)->format("Y-m-d H:i:s")
                ];
            })
        ];
    }
}
