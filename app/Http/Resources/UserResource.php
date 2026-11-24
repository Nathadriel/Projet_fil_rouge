<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // The email is only shown to the user himself
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->when($request->user()?->id === $this->id, $this->email),
            'betcoins' => $this->betcoins,
            'grade' => $this->grade(),
            'next_grade' => $this->nextGrade(),
            'can_create_events' => $this->canCreateEvents(),
        ];
    }
}
