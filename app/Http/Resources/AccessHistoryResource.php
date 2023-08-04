<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccessHistoryResource extends JsonResource
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
            'user_id' => $this->user_id,
            'user' => [
                'email' => $this->user->email,
                'uid' => $this->user->uid,
                'name' => $this->user->name,
            ],
            'request_url' => $this->request_url,
            'created_at' => $this->created_at,
        ];
    }
}
