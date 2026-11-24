<?php

namespace App\Http\Controllers;

use App\Models\Event;

class IndexController extends Controller
{
    public function index()
    {
        $events = Event::where('status', Event::STATUS_OPEN)
            ->where('closes_at', '>', now())
            ->withCount('bets')
            ->orderBy('closes_at')
            ->limit(6)
            ->get();

        return view('welcome', compact('events'));
    }
}
