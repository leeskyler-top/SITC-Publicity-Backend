<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivityResource extends JsonResource
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
            'admin_uid' => $this->loadMissing('admin')->admin->uid,
            'admin_name' => $this->loadMissing('admin')->admin->name,
            'title' => $this->title,
            'type' => $this->type,
            'place' => $this->place,
            'note' => $this->note,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'users' => $this->loadMissing('users')->users->makeHidden(['pivot', 'token', 'is_admin', 'created_at']),
            'status' => $this->status,
            'is_enrolling' => $this->is_enrolling,
        ];
    }
}
