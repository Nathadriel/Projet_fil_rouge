<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BetResource extends JsonResource
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
            'choice' => $this->choice,
            'amount' => $this->amount,
            'odds' => (float) $this->odds,
            'status' => $this->status,
            'potential_gain' => $this->potentialGain(),
            'payout' => $this->payout,
            'event' => [
                'id' => $this->event_id,
                'title' => $this->whenLoaded('event', fn () => $this->event->title),
                'link' => route('api.events.show', ['event' => $this->event_id]),
            ],
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
