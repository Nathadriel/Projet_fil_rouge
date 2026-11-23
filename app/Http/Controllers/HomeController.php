<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $events = Event::open()
            ->where('closes_at', '>', now())
            ->withCount('paris')
            ->orderBy('closes_at')
            ->limit(6)
            ->get();

        return view('home', compact('events'));
    }
}
