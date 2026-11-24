<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventResource extends JsonResource
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
            'title' => $this->title,
            'description' => $this->description,
            'category' => $this->category,
            'status' => $this->status,
            'odds_yes' => (float) $this->odds_yes,
            'odds_no' => (float) $this->odds_no,
            'result' => $this->result,
            'closes_at' => $this->closes_at->toIso8601String(),
            'bets_count' => $this->whenCounted('bets'),

            '_links' => [
                'self' => route('api.events.show', ['event' => $this->id]),
                'bet' => route('api.bets.store', ['event' => $this->id]),
                'all' => route('api.events.index'),
            ],
        ];
    }
}
