<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\PageVisit;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Cache;

class TrackPageVisit
{
    public function handle(Request $request, Closure $next): Response
    {
        // Hanya track GET requests dan bukan ajax/livewire/assets
        if ($request->isMethod('GET') 
            && !$request->ajax() 
            && !$request->header('X-Livewire')
            && !str_starts_with($request->path(), 'livewire')
            && !str_starts_with($request->path(), 'filament/assets')
        ) {
            $today = now()->format('Y-m-d');
            $sessionId = session()->getId();
            $cacheKey = "visit_tracked_{$today}_{$sessionId}";

            // Cek apakah session ini sudah di-track hari ini
            if (!Cache::has($cacheKey)) {
                // Update atau create daily visit record
                $dailyVisit = PageVisit::firstOrCreate(
                    ['visit_date' => $today],
                    ['total_visits' => 0]
                );

                // Increment total visits
                $dailyVisit->increment('total_visits');

                // Cache selama 24 jam (sampai besok)
                Cache::put($cacheKey, true, now()->endOfDay());
            }
        }

        return $next($request);
    }
}