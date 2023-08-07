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
            'user_uid' => $this->loadMissing('users')->users->uid,
            'user_department' => $this->loadMissing('users')->users->department,
            'user_name' => $this->loadMissing('users')->users->name,
            'status' => $this->status
        ];
    }
}
