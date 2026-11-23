<?php

namespace App\Providers;

use App\Models\Event;
use App\Models\User;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Schema::defaultStringLength(191);
        Paginator::defaultView('partials.pagination');

        // Création d'événement : réservée aux joueurs ayant atteint le grade "Parieur" (ou aux admins)
        Gate::define('create-event', fn (User $user) => $user->isAdmin() || $user->grade()->canCreateEvents());

        Gate::define('view-unpublished-event', fn (?User $user, Event $event) => $user !== null
            && ($user->isAdmin() || $event->user_id === $user->id));
    }
}
