<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\News;
use App\Models\MatchHighlight;
use App\Models\EventNotif;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class MatchController extends Controller
{
    /**
     * Display the match schedule & results page
     */
    public function index(Request $request)
    {
        // Set timezone dan locale untuk Jakarta
        Carbon::setLocale('id');
        $jakartaNow = Carbon::now('Asia/Jakarta');

        // Get filter parameters
        $selectedYear = $request->input('year', '');
        $league = $request->input('league', '');
        $series = $request->input('series', '');
        $region = $request->input('region', '');
        $search = $request->input('search', '');
        $selectedDate = $request->input('date');
        $weekOffset = (int) $request->input('week', 0);
        $selectedMonth = $request->input('month');

        // Get unique leagues dari database
        $leagues = Game::select('league')
            ->distinct()
            ->whereNotNull('league')
            ->where('league', '!=', '')
            ->orderBy('league', 'asc')
            ->pluck('league')
            ->toArray();

        // ✅ FIX: Tentukan base date dengan prioritas yang benar
        if ($selectedDate) {
            $baseDate = Carbon::parse($selectedDate, 'Asia/Jakarta');
            $startOfWeek = $baseDate->copy()->startOfWeek();
            $weekOffset = 0;
        } elseif ($selectedMonth) {
            $baseDate = Carbon::parse($selectedMonth, 'Asia/Jakarta')->startOfMonth();
            $startOfWeek = $baseDate->copy()->addWeeks($weekOffset);
        } else {
            $baseDate = $jakartaNow->copy();
            $startOfWeek = $baseDate->copy()->startOfWeek()->addWeeks($weekOffset);
        }

        // Generate 7 hari berdasarkan start of week
        $dates = [];
        for ($i = 0; $i < 7; $i++) {
            $currentDate = $startOfWeek->copy()->addDays($i);

            // ✅ CRITICAL FIX: Count matches dengan SEMUA filter yang SAMA dengan query matches
            // Ini yang bikin konsisten antara count di card vs data yang ditampilkan
            $matchCountQuery = Game::whereDate('date', $currentDate->format('Y-m-d'));

            // ✅ Apply year filter SAMA seperti di matches query
            if ($selectedYear !== '' && $selectedYear !== null) {
                $matchCountQuery->whereYear('date', $selectedYear);
            }

            if ($league !== '') {
                $matchCountQuery->where('league', $league);
            }

            if ($series !== '') {
                $matchCountQuery->where('series', $series);
            }

            if ($region !== '') {
                $matchCountQuery->where('region', $region);
            }

            if ($search) {
                $matchCountQuery->where(function ($query) use ($search) {
                    $query->whereHas('team1', function ($q) use ($search) {
                        $q->where('name', 'LIKE', "%{$search}%");
                    })
                        ->orWhereHas('team2', function ($q) use ($search) {
                            $q->where('name', 'LIKE', "%{$search}%");
                        });
                });
            }

            $matchCount = $matchCountQuery->count();

            $dates[] = [
                'day' => $currentDate->day,
                'name' => ucfirst($currentDate->isoFormat('dddd')),
                'month' => ucfirst($currentDate->isoFormat('MMMM Y')),
                'matches' => $matchCount,
                'full_date' => $currentDate->format('Y-m-d'),
                'is_today' => $currentDate->isSameDay($jakartaNow),
            ];
        }

        // ✅ FIX: Auto-select date logic - HANYA jika user TIDAK pilih date
        // IMPORTANT: Jangan auto-select kalau user explicitly pilih date dari request
        if (!$request->has('date') && !$selectedDate && !empty($dates)) {
            if ($weekOffset == 0 && !$selectedMonth) {
                $todayInDates = collect($dates)->firstWhere('is_today', true);
                $selectedDate = $todayInDates ? $todayInDates['full_date'] : $dates[0]['full_date'];
            } else {
                $firstDateWithMatch = collect($dates)->firstWhere(function ($date) {
                    return $date['matches'] > 0;
                });
                $selectedDate = $firstDateWithMatch ? $firstDateWithMatch['full_date'] : $dates[0]['full_date'];
            }
        }

        // Hitung info minggu untuk navigasi
        $currentWeekStart = $startOfWeek->copy();
        $currentWeekEnd = $currentWeekStart->copy()->addDays(6);
        $weekInfo = [
            'offset' => $weekOffset,
            'start' => $currentWeekStart->format('d M'),
            'end' => $currentWeekEnd->format('d M Y'),
            'is_current' => $weekOffset == 0 && !$selectedMonth && $currentWeekStart->isSameWeek($jakartaNow),
        ];

        // ✅ Query matches dengan filter yang SAMA
        $matchesQuery = Game::with(['team1', 'team2', 'category']);


        if ($selectedYear !== '' && $selectedYear !== null) {
            $matchesQuery->whereYear('date', $selectedYear);
        }

        if ($league !== '') {
            $matchesQuery->where('league', $league);
        }

        if ($series !== '') {
            $matchesQuery->where('series', $series);
        }

        if ($region !== '') {
            $matchesQuery->where('region', $region);
        }

        if ($selectedDate) {
            $matchesQuery->whereDate('date', $selectedDate);
        }

        if ($search) {
            $matchesQuery->where(function ($query) use ($search) {
                $query->whereHas('team1', function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%");
                })
                    ->orWhereHas('team2', function ($q) use ($search) {
                        $q->where('name', 'LIKE', "%{$search}%");
                    });
            });
        }

        $matches = $matchesQuery
            ->orderBy('date', 'asc')
            ->orderBy('time', 'asc')
            ->paginate(6)
            ->withQueryString()
            ->through(function ($match) {
                $team1Logo = $this->normalizeLogoPath($match->team1->logo, $match->team1->name);
                $team2Logo = $this->normalizeLogoPath($match->team2->logo, $match->team2->name);

                return [
                    'id' => $match->id,
                    'type' => $match->status,
                    'league' => $match->league,
                    'date' => $match->formatted_date,
                    'time' => $match->formatted_time,
                    'venue' => $match->venue,
                    'team1' => [
    'name' => $match->team1->name,
    'division' => $match->team1_division,
    'logo' => $team1Logo,
    'category' => $match->category ? [
        'name' => $match->category->category_name,
        'age_group' => $match->category->age_group
    ] : null
],
'team2' => [
    'name' => $match->team2->name,
    'division' => $match->team2_division,
    'logo' => $team2Logo,
    'category' => $match->category ? [
        'name' => $match->category->category_name,
        'age_group' => $match->category->age_group
    ] : null
],
                    'score' => $match->score
                ];
            });

        $activeEventNotif = EventNotif::active()->first();

        $eventNotifData = null;
        if ($activeEventNotif) {
            $eventNotifData = [
                'id' => $activeEventNotif->id,
                'title' => $activeEventNotif->title,
                'description' => $activeEventNotif->description,
                'image_url' => $activeEventNotif->image_url,
                'formatted_date' => $activeEventNotif->formatted_date,
                'formatted_time' => $activeEventNotif->formatted_time,
                'location' => $activeEventNotif->location,
                'monthly_original_price' => $activeEventNotif->monthly_original_price,
                'formatted_monthly_original_price' => $activeEventNotif->formatted_monthly_original_price,
                'monthly_price' => $activeEventNotif->monthly_price,
                'formatted_monthly_price' => $activeEventNotif->formatted_monthly_price,
                'monthly_discount_percent' => $activeEventNotif->monthly_discount_percent,
                'weekly_price' => $activeEventNotif->weekly_price,
                'formatted_weekly_price' => $activeEventNotif->formatted_weekly_price,
                'monthly_frequency' => $activeEventNotif->monthly_frequency,
                'monthly_loyalty_points' => $activeEventNotif->monthly_loyalty_points,
                'monthly_note' => $activeEventNotif->monthly_note,
                'weekly_loyalty_points' => $activeEventNotif->weekly_loyalty_points,
                'weekly_note' => $activeEventNotif->weekly_note,
                'benefits_list' => $activeEventNotif->benefits_array,
                'participant_count' => $activeEventNotif->participant_count,
                'level_tagline' => $activeEventNotif->level_tagline,
                'whatsapp_number' => $activeEventNotif->whatsapp_number,
                'whatsapp_message' => $activeEventNotif->whatsapp_message,
                'whatsapp_url' => $activeEventNotif->whatsapp_url,
            ];
        }

        return Inertia::render('MatchPage/MatchPage', [
            'auth' => [
                'client' => auth('client')->user()
            ],
            'filters' => [
                'year' => $selectedYear,
                'league' => $league,
                'series' => $series,
                'region' => $region,
                'search' => $search,
                'selectedDate' => $selectedDate,
                'week' => $weekOffset,
                'month' => $selectedMonth,
            ],
            'dates' => $dates,
            'matches' => $matches,
            'today' => $jakartaNow->format('Y-m-d'),
            'weekInfo' => $weekInfo,
            'leagues' => $leagues,
            'activeEventNotif' => $eventNotifData,
        ]);
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

    public function show($id)
    {
        Carbon::setLocale('id');

        $match = Game::with(['team1', 'team2', 'category', 'playerStats.player'])
            ->findOrFail($id);

        $quartersRaw = $match->quarters;

        $quarters = [
            'team1' => array_map('intval', $quartersRaw['team1'] ?? [0, 0, 0, 0]),
            'team2' => array_map('intval', $quartersRaw['team2'] ?? [0, 0, 0, 0]),
        ];

        $total1 = array_sum($quarters['team1']);
        $total2 = array_sum($quarters['team2']);
        $calculatedScore = ($total1 > 0 || $total2 > 0) ? "{$total1} - {$total2}" : null;

        $stats = [];
        if ($match->status === 'finished' && $match->stat_fg_team1) {
            $stats = [
                [
                    'category' => 'Field Goals',
                    'team1' => $match->stat_fg_team1 ?? '0/0 (0%)',
                    'team2' => $match->stat_fg_team2 ?? '0/0 (0%)',
                ],
                [
                    'category' => '2 Points',
                    'team1' => $match->stat_2pt_team1 ?? '0/0 (0%)',
                    'team2' => $match->stat_2pt_team2 ?? '0/0 (0%)',
                ],
                [
                    'category' => '3 Points',
                    'team1' => $match->stat_3pt_team1 ?? '0/0 (0%)',
                    'team2' => $match->stat_3pt_team2 ?? '0/0 (0%)',
                ],
                [
                    'category' => 'Free Throws',
                    'team1' => $match->stat_ft_team1 ?? '0/0 (0%)',
                    'team2' => $match->stat_ft_team2 ?? '0/0 (0%)',
                ],
                [
                    'category' => 'Rebounds',
                    'team1' => $match->stat_reb_team1 ?? '0/0',
                    'team2' => $match->stat_reb_team2 ?? '0/0',
                ],
                [
                    'category' => 'Assist',
                    'team1' => $match->stat_ast_team1 ?? '0',
                    'team2' => $match->stat_ast_team2 ?? '0',
                ],
                [
                    'category' => 'Steals',
                    'team1' => $match->stat_stl_team1 ?? '0',
                    'team2' => $match->stat_stl_team2 ?? '0',
                ],
                [
                    'category' => 'Blocks',
                    'team1' => $match->stat_blk_team1 ?? '0',
                    'team2' => $match->stat_blk_team2 ?? '0',
                ],
                [
                    'category' => 'Turnovers',
                    'team1' => $match->stat_to_team1 ?? '0',
                    'team2' => $match->stat_to_team2 ?? '0',
                ],
                [
                    'category' => 'Fouls',
                    'team1' => $match->stat_foul_team1 ?? '0',
                    'team2' => $match->stat_foul_team2 ?? '0',
                ],
                [
                    'category' => 'Points Off Turnover',
                    'team1' => $match->stat_pot_team1 ?? '0',
                    'team2' => $match->stat_pot_team2 ?? '0',
                ],
            ];
        }

        $matchHighlights = MatchHighlight::where('game_id', $id)
            ->where('status', 'active')
            ->orderBy('is_featured', 'desc')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($highlight) use ($match) {
                return [
                    'id' => $highlight->id,
                    'title' => $highlight->title,
                    'thumbnail' => $highlight->thumbnail_url,
                    'quarter' => $highlight->quarter,
                    'duration' => $highlight->duration,
                    'views' => $highlight->formatted_views,
                    'video_url' => $highlight->video_url,
                    'category' => $match->league ?? 'Basketball',
                    'venue' => $match->venue ?? 'GOR Arena',
                    'time' => $match->formatted_time ?? '00:00',
                    'date' => $match->formatted_date ?? now()->format('d M Y'),
                ];
            });

        $relatedNews = News::published()
            ->inRandomOrder()
            ->take(3)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'title' => $item->title,
                    'excerpt' => $item->excerpt,
                    'image' => $item->image
                        ? asset('storage/' . $item->image)
                        : 'https://images.unsplash.com/photo-1546519638-68e109498ffc?w=800',
                    'category' => $item->category,
                    'date' => $item->formatted_date,
                ];
            });

        $matchData = [
            'id' => $match->id,
            'league' => $match->league,
            'date' => $match->formatted_date,
            'time' => $match->formatted_time,
            'venue' => $match->venue,
            'status' => $match->status,
            'team1' => [
    'id' => $match->team1->id,
    'name' => $match->team1->name,
    'division' => $match->team1_division,
    'logo' => $this->normalizeLogoPath($match->team1->logo, $match->team1->name),
    'category' => $match->category ? [
        'name' => $match->category->category_name,
        'age_group' => $match->category->age_group
    ] : null
],
'team2' => [
    'id' => $match->team2->id,
    'name' => $match->team2->name,
    'division' => $match->team2_division,
    'logo' => $this->normalizeLogoPath($match->team2->logo, $match->team2->name),
    'category' => $match->category ? [
        'name' => $match->category->category_name,
        'age_group' => $match->category->age_group
    ] : null
],
            'score' => $calculatedScore ?? $match->score,
            'quarters' => $quarters,
            'stats' => $stats,
            'boxScoreTeam1' => $match->boxScoreTeam1()->values()->toArray(),
            'boxScoreTeam2' => $match->boxScoreTeam2()->values()->toArray(),
        ];

        return Inertia::render('MatchPage/MatchDetail', [
            'auth' => [
                'client' => auth('client')->user()
            ],
            'match' => $matchData,
            'matchHighlights' => $matchHighlights,
            'relatedNews' => $relatedNews,
        ]);
    }

    public function getMatchesByDate(Request $request)
    {
        $date = $request->input('date');
        $matches = Game::with(['team1', 'team2'])
            ->whereDate('date', $date)
            ->orderBy('time', 'asc')
            ->get()
            ->map(function ($match) {
                return [
                    'id' => $match->id,
                    'type' => $match->status,
                    'league' => $match->league,
                    'date' => $match->formatted_date,
                    'time' => $match->formatted_time,
                    'team1' => [
                        'name' => $match->team1->name,
                        'logo' => $this->normalizeLogoPath($match->team1->logo, $match->team1->name)
                    ],
                    'team2' => [
                        'name' => $match->team2->name,
                        'logo' => $this->normalizeLogoPath($match->team2->logo, $match->team2->name)
                    ],
                    'score' => $match->score
                ];
            });

        return response()->json([
            'success' => true,
            'matches' => $matches
        ]);
    }

    public function search(Request $request)
    {
        $query = $request->input('query');

        $results = Game::with(['team1', 'team2'])
            ->search($query)
            ->orderBy('date', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($match) {
                return [
                    'id' => $match->id,
                    'league' => $match->league,
                    'date' => $match->formatted_date,
                    'team1' => $match->team1->name,
                    'team2' => $match->team2->name,
                    'score' => $match->score
                ];
            });

        return response()->json([
            'success' => true,
            'results' => $results
        ]);
    }

    public function getStats($id)
    {
        $match = Game::findOrFail($id);

        return response()->json([
            'success' => true,
            'stats' => $match->stats
        ]);
    }
}
