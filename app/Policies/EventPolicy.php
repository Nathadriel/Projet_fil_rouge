<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;

class EventPolicy
{
    /**
     * Determine whether the user can view the model.
     */
    public function view(?User $user, Event $event): bool
    {
        // Open, resolved and cancelled events are public
        if (! in_array($event->status, [Event::STATUS_PENDING, Event::STATUS_REFUSED])) {
            return true;
        }

        // Pending and refused events are only visible by their creator
        return $user && $user->id === $event->user_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->canCreateEvents();
    }

    /**
     * Determine whether the user can validate, refuse, resolve or cancel events.
     */
    public function manage(User $user): bool
    {
        return false;
    }

    // "before" method for the admin role
    public function before(?User $user, string $ability): ?bool
    {
        if ($user && $user->hasRole('admin')) {
            return true;
        }

        return null;
    }
}
