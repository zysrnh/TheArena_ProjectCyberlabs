<?php

namespace App\Http\Controllers;

use App\Models\LiveMatch;
use App\Models\Game;
use App\Models\News;
use App\Models\Sponsor;
use App\Models\Partner;
use App\Models\Review;
use App\Models\Facility;
use App\Models\EventNotif;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        try {
            $newsForHome = News::published()
                ->latest()
                ->take(3)
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'title' => $item->title,
                        'excerpt' => $item->excerpt,
                        'image' => $item->image ? asset('storage/' . $item->image) : 'https://images.unsplash.com/photo-1546519638-68e109498ffc?w=800',
                        'category' => $item->category,
                        'date' => $item->formatted_date,
                    ];
                });

            $liveMatches = LiveMatch::where('is_active', true)
                ->orderBy('match_date', 'desc')
                ->orderBy('time', 'desc')
                ->take(6)
                ->get()
                ->map(function ($match) {
                    return [
                        'id' => $match->id,
                        'status' => $match->status,
                        'time' => $match->time,
                        'title' => $match->title,
                        'img' => $match->thumbnail
                            ? asset('storage/' . $match->thumbnail)
                            : asset('images/comingsoon.png'),
                        'venue' => $match->venue,
                        'category' => $match->category,
                        'court' => $match->court,
                        'stream_url' => $match->stream_url,
                    ];
                });

            $filter = $request->get('filter', 'all');
           $query = Game::with(['team1', 'team2', 'category']);


            if ($filter === 'live') {
                $query->where('status', 'live');
            } elseif ($filter === 'upcoming') {
                $query->whereIn('status', ['upcoming', 'scheduled']);
            } elseif ($filter === 'all') {
                $query->whereIn('status', ['live', 'upcoming', 'scheduled', 'finished', 'completed']);
            }

            $homeMatches = $query
                ->orderByRaw("FIELD(status, 'live', 'upcoming', 'scheduled', 'finished', 'completed')")
                ->orderBy('date', 'desc')
                ->orderBy('time', 'desc')
                ->take(4)
                ->get()
                ->map(function ($game) {
                    $type = 'upcoming';
                    if ($game->status === 'live') {
                        $type = 'live';
                    } elseif ($game->status === 'finished' || $game->status === 'completed') {
                        $type = 'finished';
                    }

                    $team1Logo = $this->normalizeLogoPath($game->team1->logo ?? null, $game->team1->name ?? null);
                    $team2Logo = $this->normalizeLogoPath($game->team2->logo ?? null, $game->team2->name ?? null);

                    return [
                        'id' => $game->id,
                        'team1' => [
    'name'     => $game->team1->name ?? 'Team 1',
    'division' => $game->team1_division,
    'logo'     => $team1Logo,
    'category' => $game->category ? [
        'name'      => $game->category->category_name,
        'age_group' => $game->category->age_group
    ] : null
],
'team2' => [
    'name'     => $game->team2->name ?? 'Team 2',
    'division' => $game->team2_division,
    'logo'     => $team2Logo,
    'category' => $game->category ? [
        'name'      => $game->category->category_name,
        'age_group' => $game->category->age_group
    ] : null
],
                        'type' => $type,
                        'league' => $game->league ?? 'League',
                        'day' => $game->date->locale('id')->isoFormat('dddd'),
                        'date' => $game->date->locale('id')->isoFormat('D MMMM YYYY'),
                        'time' => $game->formatted_time ?? $game->time,
                        'score' => $game->score,
                    ];
                });

            $sponsors = Sponsor::active()->ordered()->get()->map(function ($sponsor) {
                return [
                    'id' => $sponsor->id,
                    'name' => $sponsor->name,
                    'image' => asset('storage/' . $sponsor->image),
                ];
            });

            $partners = Partner::active()->ordered()->get()->map(function ($partner) {
                return [
                    'id' => $partner->id,
                    'name' => $partner->name,
                    'image' => asset('storage/' . $partner->image),
                ];
            });

            $reviews = Review::with('client:id,name,profile_image')
                ->approved()
                ->latest()
                ->take(6)
                ->get()
                ->map(function ($review) {
                    return [
                        'id' => $review->id,
                        'client_name' => $review->client->name,
                        'client_profile_image' => $review->client->profile_image,
                        'rating' => $review->rating,
                        'rating_facilities' => $review->rating_facilities,
                        'rating_hospitality' => $review->rating_hospitality,
                        'rating_cleanliness' => $review->rating_cleanliness,
                        'comment' => $review->comment,
                        'created_at' => $review->created_at->diffForHumans(),
                    ];
                });

            $facilities = Facility::active()
                ->ordered()
                ->get()
                ->map(function ($facility) {
                    $imageUrl = 'https://images.unsplash.com/photo-1555939594-58d7cb561ad1?w=800';

                    if ($facility->image_url) {
                        if (
                            str_starts_with($facility->image_url, 'http://') ||
                            str_starts_with($facility->image_url, 'https://')
                        ) {
                            $imageUrl = $facility->image_url;
                        } else {
                            $imageUrl = asset('storage/' . $facility->image_url);
                        }
                    }

                    return [
                        'id' => $facility->id,
                        'name' => $facility->name,
                        'description' => $facility->description,
                        'image' => $imageUrl,
                    ];
                });

         // Di HomeController index(), ganti bagian EVENT NOTIF dengan ini:

// ✅ GET ACTIVE EVENT NOTIF (POPUP) - SIMPLIFIED
$activeEventNotif = EventNotif::active()->first();

$eventNotifData = null;
if ($activeEventNotif) {
    $eventNotifData = [
        'id' => $activeEventNotif->id,
        'title' => $activeEventNotif->title,
        'description' => $activeEventNotif->description,
        'tagline' => $activeEventNotif->tagline, // ✅ TAMBAH TAGLINE
        'image_url' => $activeEventNotif->image_url,
        'formatted_date' => $activeEventNotif->formatted_date,
        'formatted_time' => $activeEventNotif->formatted_time,
        'location' => $activeEventNotif->location,
        
        // ✅ SHOW PRICING FLAG
        'show_pricing' => $activeEventNotif->show_pricing,

        // ✅ PRICING OPTIONS (SIMPLIFIED)
        'monthly_original_price' => $activeEventNotif->monthly_original_price,
        'formatted_monthly_original_price' => $activeEventNotif->formatted_monthly_original_price,
        'monthly_price' => $activeEventNotif->monthly_price,
        'formatted_monthly_price' => $activeEventNotif->formatted_monthly_price,
        'monthly_discount_percent' => $activeEventNotif->monthly_discount_percent,
        'weekly_price' => $activeEventNotif->weekly_price,
        'formatted_weekly_price' => $activeEventNotif->formatted_weekly_price,

        // ✅ BENEFITS (INCLUDING)
        'benefits_list' => $activeEventNotif->benefits_list, // Langsung ambil JSON

        // ✅ WHATSAPP
        'whatsapp_number' => $activeEventNotif->whatsapp_number,
        'whatsapp_message' => $activeEventNotif->whatsapp_message,
        'whatsapp_url' => $activeEventNotif->whatsapp_url,
    ];

    // ✅ DEBUGGING - CEK DATA HARGA
    \Log::info('Active Event Notif Data:', [
        'title' => $eventNotifData['title'],
        'show_pricing' => $eventNotifData['show_pricing'],
        'monthly_price' => $eventNotifData['monthly_price'],
        'formatted_monthly_price' => $eventNotifData['formatted_monthly_price'],
        'weekly_price' => $eventNotifData['weekly_price'],
        'formatted_weekly_price' => $eventNotifData['formatted_weekly_price'],
    ]);
}            return Inertia::render('HomePage/HomePage', [
                'auth' => [
                    'client' => Auth::guard('client')->user()
                ],
                'liveMatches' => $liveMatches,
                'homeMatches' => $homeMatches,
                'currentFilter' => $filter,
                'newsForHome' => $newsForHome,
                'sponsors' => $sponsors,
                'partners' => $partners,
                'reviews' => $reviews,
                'facilities' => $facilities,
                'activeEventNotif' => $eventNotifData, // ✅ PASS EVENT NOTIF DATA
            ]);
        } catch (\Exception $e) {
            Log::error('HomePage Error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return Inertia::render('HomePage/HomePage', [
                'auth' => [
                    'client' => Auth::guard('client')->user()
                ],
                'liveMatches' => [],
                'homeMatches' => [],
                'currentFilter' => 'all',
                'newsForHome' => [],
                'sponsors' => [],
                'partners' => [],
                'reviews' => [],
                'facilities' => [],
                'activeEventNotif' => null,
            ]);
        }
    }

    private function normalizeLogoPath($logoPath, $teamName = null)
    {
        if (empty($logoPath)) {
            return '/images/default-team-logo.png';
        }

        if (str_starts_with($logoPath, 'http://') || str_starts_with($logoPath, 'https://')) {
            return $logoPath;
        }

        if (str_starts_with($logoPath, '/storage/')) {
            return $logoPath;
        }

        if (str_starts_with($logoPath, 'storage/')) {
            return '/' . $logoPath;
        }

        if (!str_contains($logoPath, '/')) {
            return '/storage/teams/logos/' . $logoPath;
        }

        return '/storage/' . ltrim($logoPath, '/');
    }
}