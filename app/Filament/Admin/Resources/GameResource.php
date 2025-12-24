<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\GameResource\Pages;
use App\Models\Game;
use App\Models\Player;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use App\Models\PlayerStat;
use App\Models\TeamCategory;

class GameResource extends Resource
{
    protected static ?string $model = Game::class;

    protected static ?string $navigationIcon = 'heroicon-o-trophy';

    protected static ?string $navigationLabel = 'Matches';
    protected static ?string $navigationGroup = 'Content Management';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // ============ SECTION 1: MATCH INFO ============
                Forms\Components\Section::make('Match Information')
                    ->schema([
                        Forms\Components\TextInput::make('league')
                            ->label('League/Competition')
                            ->required()
                            ->default('Arena Seasons 2025')
                            ->maxLength(255)
                            ->disabled(fn($operation) => $operation === 'edit')
                            ->dehydrated(),

                        Forms\Components\DatePicker::make('date')
                            ->label('Match Date')
                            ->required()
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->default(now('Asia/Jakarta'))
                            ->disabled(fn($operation) => $operation === 'edit')
                            ->dehydrated()
                            ->live()
                            ->afterStateUpdated(function ($state, $set) {
                                if ($state) {
                                    try {
                                        $year = date('Y', strtotime($state));
                                        $set('year', $year);
                                    } catch (\Exception $e) {
                                        $set('year', date('Y'));
                                    }
                                }
                            }),

                        Forms\Components\TimePicker::make('time')
                            ->label('Match Time (WIB)')
                            ->required()
                            ->seconds(false)
                            ->default('19:00')
                            ->disabled(fn($operation) => $operation === 'edit')
                            ->dehydrated(),

                        Forms\Components\TextInput::make('venue')
                            ->label('Venue')
                            ->maxLength(255)
                            ->placeholder('e.g., GOR Pajajaran')
                            ->disabled(fn($operation) => $operation === 'edit')
                            ->dehydrated(),

                        Forms\Components\TextInput::make('year')
                            ->label('Year (Auto-filled)')
                            ->numeric()
                            ->default(date('Y'))
                            ->maxLength(4)
                            ->disabled()
                            ->dehydrated(),

                        Forms\Components\Select::make('series')
                            ->label('Series')
                            ->options([
                                'Regular Season' => 'Regular Season',
                                'Playoff' => 'Playoff',
                                'Finals' => 'Finals',
                            ])
                            ->required()
                            ->default('Regular Season')
                            ->disabled(fn($operation) => $operation === 'edit')
                            ->dehydrated(),

                        Forms\Components\Select::make('region')
                            ->label('Region')
                            ->options([
                                'Jakarta' => 'Jakarta',
                                'Bandung' => 'Bandung',
                                'Surabaya' => 'Surabaya',
                                'Semarang' => 'Semarang',
                                'Medan' => 'Medan',
                                'Bali' => 'Bali',
                            ])
                            ->searchable()
                            ->nullable()
                            ->disabled(fn($operation) => $operation === 'edit')
                            ->dehydrated(),
                    ])
                    ->columns(2)
                    ->collapsible(),

               // ============ SECTION 2: TEAMS & CATEGORIES ============
Forms\Components\Section::make('Teams & Categories')
    ->schema([
        // Team 1 Selection
        Forms\Components\Select::make('team1_id')
            ->label('Home Team')
            ->relationship('team1', 'name')
            ->searchable()
            ->preload()
            ->required()
            ->disabled(fn($operation) => $operation === 'edit')
            ->dehydrated()
            ->live()
            ->afterStateUpdated(function ($state, $set) {
                // Reset category when team changes
                $set('team1_category_id', null);
            })
            ->disableOptionWhen(function ($value, $get) {
                return $value === $get('team2_id');
            }),

        // Team 1 Category Selection
        Forms\Components\Select::make('team1_category_id')
            ->label('Home Team Category')
            ->options(function ($get) {
                $teamId = $get('team1_id');
                if (!$teamId) return [];
                
                return \App\Models\TeamCategory::where('team_id', $teamId)
                    ->where('is_active', true)
                    ->get()
                    ->mapWithKeys(function ($category) {
                        return [
                            $category->id => "{$category->category_name} ({$category->age_group})"
                        ];
                    });
            })
            ->searchable()
            ->nullable()
            ->disabled(fn($operation) => $operation === 'edit')
            ->dehydrated()
            ->helperText('Optional - Select if match is for specific age category')
            ->hidden(fn($get) => !$get('team1_id')),

        // Team 2 Selection
        Forms\Components\Select::make('team2_id')
            ->label('Away Team')
            ->relationship('team2', 'name')
            ->searchable()
            ->preload()
            ->required()
            ->disabled(fn($operation) => $operation === 'edit')
            ->dehydrated()
            ->live()
            ->afterStateUpdated(function ($state, $set) {
                // Reset category when team changes
                $set('team2_category_id', null);
            })
            ->disableOptionWhen(function ($value, $get) {
                return $value === $get('team1_id');
            }),

        // Team 2 Category Selection
        Forms\Components\Select::make('team2_category_id')
            ->label('Away Team Category')
            ->options(function ($get) {
                $teamId = $get('team2_id');
                if (!$teamId) return [];
                
                return \App\Models\TeamCategory::where('team_id', $teamId)
                    ->where('is_active', true)
                    ->get()
                    ->mapWithKeys(function ($category) {
                        return [
                            $category->id => "{$category->category_name} ({$category->age_group})"
                        ];
                    });
            })
            ->searchable()
            ->nullable()
            ->disabled(fn($operation) => $operation === 'edit')
            ->dehydrated()
            ->helperText('Optional - Select if match is for specific age category')
            ->hidden(fn($get) => !$get('team2_id')),
    ])
    ->columns(2)
    ->collapsible(),

                // ============ SECTION 3: STATUS ============
                Forms\Components\Section::make('Match Status')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Match Status')
                            ->options([
                                'upcoming' => 'Upcoming',
                                'live' => 'Live',
                                'finished' => 'Finished',
                            ])
                            ->required()
                            ->default('upcoming')
                            ->live()
                            ->helperText('⚠️ Ubah ke "Finished" untuk mengaktifkan form input score & statistik'),
                    ])
                    ->columns(1),

                // ============ SECTION 4: QUARTER SCORES (FORMAT ARRAY) ============
                Forms\Components\Section::make('Quarter Scores')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                // Team 1 Quarters
                                Forms\Components\Group::make()
                                    ->schema([
                                        Forms\Components\Placeholder::make('team1_header')
                                            ->label('')
                                            ->content(
                                                fn($livewire): string =>
                                                '<strong>' . ($livewire->record?->team1?->name ?? 'Team 1') . '</strong>'
                                            ),

                                        Forms\Components\Grid::make(4)
                                            ->schema([
                                                Forms\Components\TextInput::make('quarters.team1.0')
                                                    ->label('Q1')
                                                    ->numeric()
                                                    ->default(0)
                                                    ->required()
                                                    ->live(onBlur: true)
                                                    ->afterStateUpdated(
                                                        fn($set, $get) =>
                                                        self::calculateFinalScore($set, $get)  // ✅ Ganti ini
                                                    ),

                                                Forms\Components\TextInput::make('quarters.team1.1')
                                                    ->label('Q2')
                                                    ->numeric()
                                                    ->default(0)
                                                    ->required()
                                                    ->live(onBlur: true)
                                                    ->afterStateUpdated(
                                                        fn($set, $get) =>
                                                        self::calculateFinalScore($set, $get)  // ✅ Ganti ini
                                                    ),

                                                Forms\Components\TextInput::make('quarters.team1.2')
                                                    ->label('Q3')
                                                    ->numeric()
                                                    ->default(0)
                                                    ->required()
                                                    ->live(onBlur: true)
                                                    ->afterStateUpdated(
                                                        fn($set, $get) =>
                                                        self::calculateFinalScore($set, $get)  // ✅ Ganti ini
                                                    ),

                                                Forms\Components\TextInput::make('quarters.team1.3')
                                                    ->label('Q4')
                                                    ->numeric()
                                                    ->default(0)
                                                    ->required()
                                                    ->live(onBlur: true)
                                                    ->afterStateUpdated(
                                                        fn($set, $get) =>
                                                        self::calculateFinalScore($set, $get)  // ✅ Ganti ini
                                                    ),
                                            ]),
                                    ]),

                                // Team 2 Quarters
                                Forms\Components\Group::make()
                                    ->schema([
                                        Forms\Components\Placeholder::make('team2_header')
                                            ->label('')
                                            ->content(
                                                fn($livewire): string =>
                                                '<strong>' . ($livewire->record?->team2?->name ?? 'Team 2') . '</strong>'
                                            ),

                                        Forms\Components\Grid::make(4)
                                            ->schema([
                                                Forms\Components\TextInput::make('quarters.team2.0')
                                                    ->label('Q1')
                                                    ->numeric()
                                                    ->default(0)
                                                    ->required()
                                                    ->live(onBlur: true)
                                                    ->afterStateUpdated(
                                                        fn($set, $get) =>
                                                        self::calculateFinalScore($set, $get)  // ✅ Ganti ini
                                                    ),

                                                Forms\Components\TextInput::make('quarters.team2.1')
                                                    ->label('Q2')
                                                    ->numeric()
                                                    ->default(0)
                                                    ->required()
                                                    ->live(onBlur: true)
                                                    ->afterStateUpdated(
                                                        fn($set, $get) =>
                                                        self::calculateFinalScore($set, $get)  // ✅ Ganti ini
                                                    ),

                                                Forms\Components\TextInput::make('quarters.team2.2')
                                                    ->label('Q3')
                                                    ->numeric()
                                                    ->default(0)
                                                    ->required()
                                                    ->live(onBlur: true)
                                                    ->afterStateUpdated(
                                                        fn($set, $get) =>
                                                        self::calculateFinalScore($set, $get)  // ✅ Ganti ini
                                                    ),

                                                Forms\Components\TextInput::make('quarters.team2.3')
                                                    ->label('Q4')
                                                    ->numeric()
                                                    ->default(0)
                                                    ->required()
                                                    ->live(onBlur: true)
                                                    ->afterStateUpdated(
                                                        fn($set, $get) =>
                                                        self::calculateFinalScore($set, $get)  // ✅ Ganti ini
                                                    ),
                                            ]),
                                    ]),
                            ]),

                        // Display calculated score
                        Forms\Components\Placeholder::make('score_preview')
                            ->label('Final Score')
                            ->content(function ($get, $livewire) {
                                $quarters = $get('quarters') ?? [];
                                $team1Quarters = $quarters['team1'] ?? [0, 0, 0, 0];
                                $team2Quarters = $quarters['team2'] ?? [0, 0, 0, 0];

                                $total1 = array_sum(array_map('intval', $team1Quarters));
                                $total2 = array_sum(array_map('intval', $team2Quarters));

                                if ($total1 == 0 && $total2 == 0) {
                                    return new \Illuminate\Support\HtmlString(
                                        '<div style="text-align: center; padding: 10px; color: #9ca3af;">
                            Isi Quarter Scores untuk melihat score
                        </div>'
                                    );
                                }

                                $team1Name = $livewire->record?->team1?->name ?? 'Team 1';
                                $team2Name = $livewire->record?->team2?->name ?? 'Team 2';

                                return new \Illuminate\Support\HtmlString(
                                    "<div style='text-align: center; padding: 15px; background: #1e40af; color: white; border-radius: 8px; font-size: 24px; font-weight: bold;'>
                        {$team1Name}: {$total1} - {$team2Name}: {$total2}
                    </div>"
                                );
                            }),

                        Forms\Components\Hidden::make('score')
                            ->live() // ✅ Tambahkan ini agar reactive
                            ->afterStateHydrated(function ($component, $state, $get) {
                                // Ketika form dibuka, hitung ulang dari quarters
                                $quarters = $get('quarters') ?? [];
                                $team1Quarters = array_map('intval', $quarters['team1'] ?? [0, 0, 0, 0]);
                                $team2Quarters = array_map('intval', $quarters['team2'] ?? [0, 0, 0, 0]);

                                $total1 = array_sum($team1Quarters);
                                $total2 = array_sum($team2Quarters);

                                if ($total1 > 0 || $total2 > 0) {
                                    $component->state("{$total1} - {$total2}");
                                }
                            })
                            ->dehydrateStateUsing(function ($state, $get) {
                                // ✅ PENTING: Kalkulasi ulang saat SAVE
                                $quarters = $get('quarters') ?? [];
                                $team1Quarters = array_map('intval', $quarters['team1'] ?? [0, 0, 0, 0]);
                                $team2Quarters = array_map('intval', $quarters['team2'] ?? [0, 0, 0, 0]);

                                $total1 = array_sum($team1Quarters);
                                $total2 = array_sum($team2Quarters);

                                if ($total1 == 0 && $total2 == 0) {
                                    return null;
                                }

                                return "{$total1} - {$total2}";
                            })
                            ->dehydrated(true),
                    ])

                    ->visible(fn($get) => in_array($get('status'), ['live', 'finished']))
                    ->columns(1),
                // ============ SECTION 5: TEAM STATISTICS (STATIC TABLE) ============
                Forms\Components\Section::make('Team Statistics')
                    ->description('Input statistik tim seperti box score')
                    ->schema([
                        // Header Row
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Placeholder::make('header_stat')
                                    ->label('')
                                    ->content(fn(): string => '<strong>STATISTIK</strong>'),

                                Forms\Components\Placeholder::make('header_team1')
                                    ->label('')
                                    ->content(
                                        fn($livewire): \Illuminate\Support\HtmlString =>
                                        new \Illuminate\Support\HtmlString('<strong>' . ($livewire->record?->team1?->name ?? 'TEAM 1') . '</strong>')
                                    ),

                                Forms\Components\Placeholder::make('header_team2')
                                    ->label('')
                                    ->content(
                                        fn($livewire): \Illuminate\Support\HtmlString =>
                                        new \Illuminate\Support\HtmlString('<strong>' . ($livewire->record?->team2?->name ?? 'TEAM 2') . '</strong>')
                                    ),
                            ]),

                        // Field Goals
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Placeholder::make('fg_label')
                                    ->label('')
                                    ->content('Field Goals'),
                                Forms\Components\TextInput::make('stat_fg_team1')
                                    ->label('')
                                    ->placeholder('40/80 (50%)')
                                    ->default('0/0 (0%)'),
                                Forms\Components\TextInput::make('stat_fg_team2')
                                    ->label('')
                                    ->placeholder('38/82 (46%)')
                                    ->default('0/0 (0%)'),
                            ]),

                        // 2 Points
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Placeholder::make('2pt_label')
                                    ->label('')
                                    ->content('2 Points'),
                                Forms\Components\TextInput::make('stat_2pt_team1')
                                    ->label('')
                                    ->placeholder('30/50 (60%)')
                                    ->default('0/0 (0%)'),
                                Forms\Components\TextInput::make('stat_2pt_team2')
                                    ->label('')
                                    ->placeholder('28/52 (54%)')
                                    ->default('0/0 (0%)'),
                            ]),

                        // 3 Points
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Placeholder::make('3pt_label')
                                    ->label('')
                                    ->content('3 Points'),
                                Forms\Components\TextInput::make('stat_3pt_team1')
                                    ->label('')
                                    ->placeholder('10/30 (33%)')
                                    ->default('0/0 (0%)'),
                                Forms\Components\TextInput::make('stat_3pt_team2')
                                    ->label('')
                                    ->placeholder('10/30 (33%)')
                                    ->default('0/0 (0%)'),
                            ]),

                        // Free Throws
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Placeholder::make('ft_label')
                                    ->label('')
                                    ->content('Free Throws'),
                                Forms\Components\TextInput::make('stat_ft_team1')
                                    ->label('')
                                    ->placeholder('15/20 (75%)')
                                    ->default('0/0 (0%)'),
                                Forms\Components\TextInput::make('stat_ft_team2')
                                    ->label('')
                                    ->placeholder('12/18 (67%)')
                                    ->default('0/0 (0%)'),
                            ]),

                        // Rebounds
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Placeholder::make('reb_label')
                                    ->label('')
                                    ->content('Rebounds (O/D)'),
                                Forms\Components\TextInput::make('stat_reb_team1')
                                    ->label('')
                                    ->placeholder('10/30')
                                    ->default('0/0'),
                                Forms\Components\TextInput::make('stat_reb_team2')
                                    ->label('')
                                    ->placeholder('8/28')
                                    ->default('0/0'),
                            ]),

                        // Assist
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Placeholder::make('ast_label')
                                    ->label('')
                                    ->content('Assist'),
                                Forms\Components\TextInput::make('stat_ast_team1')
                                    ->label('')
                                    ->placeholder('20')
                                    ->default('0'),
                                Forms\Components\TextInput::make('stat_ast_team2')
                                    ->label('')
                                    ->placeholder('18')
                                    ->default('0'),
                            ]),

                        // Steals
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Placeholder::make('stl_label')
                                    ->label('')
                                    ->content('Steals'),
                                Forms\Components\TextInput::make('stat_stl_team1')
                                    ->label('')
                                    ->placeholder('8')
                                    ->default('0'),
                                Forms\Components\TextInput::make('stat_stl_team2')
                                    ->label('')
                                    ->placeholder('6')
                                    ->default('0'),
                            ]),

                        // Blocks
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Placeholder::make('blk_label')
                                    ->label('')
                                    ->content('Blocks'),
                                Forms\Components\TextInput::make('stat_blk_team1')
                                    ->label('')
                                    ->placeholder('5')
                                    ->default('0'),
                                Forms\Components\TextInput::make('stat_blk_team2')
                                    ->label('')
                                    ->placeholder('4')
                                    ->default('0'),
                            ]),

                        // Turnovers
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Placeholder::make('to_label')
                                    ->label('')
                                    ->content('Turnovers'),
                                Forms\Components\TextInput::make('stat_to_team1')
                                    ->label('')
                                    ->placeholder('12')
                                    ->default('0'),
                                Forms\Components\TextInput::make('stat_to_team2')
                                    ->label('')
                                    ->placeholder('15')
                                    ->default('0'),
                            ]),

                        // Fouls
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Placeholder::make('foul_label')
                                    ->label('')
                                    ->content('Fouls'),
                                Forms\Components\TextInput::make('stat_foul_team1')
                                    ->label('')
                                    ->placeholder('18')
                                    ->default('0'),
                                Forms\Components\TextInput::make('stat_foul_team2')
                                    ->label('')
                                    ->placeholder('20')
                                    ->default('0'),
                            ]),

                        // Points Off Turnover
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Placeholder::make('pot_label')
                                    ->label('')
                                    ->content('Points Off Turnover'),
                                Forms\Components\TextInput::make('stat_pot_team1')
                                    ->label('')
                                    ->placeholder('15')
                                    ->default('0'),
                                Forms\Components\TextInput::make('stat_pot_team2')
                                    ->label('')
                                    ->placeholder('12')
                                    ->default('0'),
                            ]),
                    ])
                    ->visible(fn($get) => $get('status') === 'finished')
                    ->collapsed(),

                // ============ SECTION 6: BOX SCORE TEAM 1 (AUTO-LOAD PLAYERS) ============
                Forms\Components\Section::make('Box Score - Team 1')
                    ->description(
                        fn($livewire): string =>
                        'Input statistik pemain ' . ($livewire->record?->team1?->name ?? 'Team 1')
                    )
                    ->schema([
                        Forms\Components\Repeater::make('box_score_team1')
                            ->label('Player Statistics')
                            ->schema([
                                Forms\Components\Select::make('player_id')
                                    ->label('Player')
                                    ->options(function ($livewire) {
                                        if (!$livewire->record) return [];
                                        return Player::where('team_id', $livewire->record->team1_id)
                                            ->where('is_active', true)
                                            ->orderBy('jersey_no')
                                            ->get()
                                            ->mapWithKeys(function ($player) {
                                                return [$player->id => "#{$player->jersey_no} {$player->name} ({$player->position})"];
                                            });
                                    })
                                    ->searchable()
                                    ->required()
                                    ->disableOptionWhen(function ($value, $get, $livewire) {
                                        // Prevent selecting same player twice
                                        $allPlayers = $get('../../box_score_team1');
                                        if (!is_array($allPlayers)) return false;

                                        $selectedIds = array_filter(array_column($allPlayers, 'player_id'));
                                        return in_array($value, $selectedIds) && $value != $get('player_id');
                                    }),

                                Forms\Components\TextInput::make('minutes')
                                    ->label('Min')
                                    ->numeric()
                                    ->default(0)
                                    ->required(),

                                Forms\Components\TextInput::make('points')
                                    ->label('Pts')
                                    ->numeric()
                                    ->default(0)
                                    ->required(),

                                Forms\Components\TextInput::make('assists')
                                    ->label('Ast')
                                    ->numeric()
                                    ->default(0)
                                    ->required(),

                                Forms\Components\TextInput::make('rebounds')
                                    ->label('Reb')
                                    ->numeric()
                                    ->default(0)
                                    ->required(),

                                Forms\Components\Toggle::make('is_mvp')
                                    ->label('MVP')
                                    ->default(false),
                            ])
                            ->columns(6)
                            ->defaultItems(function ($livewire) {
                                if (!$livewire->record || !$livewire->record->team1_id) return 0;
                                return Player::where('team_id', $livewire->record->team1_id)
                                    ->where('is_active', true)
                                    ->count();
                            })
                            ->default(function ($livewire) {
                                if (!$livewire->record || !$livewire->record->team1_id) return [];

                                return Player::where('team_id', $livewire->record->team1_id)
                                    ->where('is_active', true)
                                    ->orderBy('jersey_no')
                                    ->get()
                                    ->map(function ($player) {
                                        return [
                                            'player_id' => $player->id,
                                            'minutes' => 0,
                                            'points' => 0,
                                            'assists' => 0,
                                            'rebounds' => 0,
                                            'is_mvp' => false,
                                        ];
                                    })
                                    ->toArray();
                            })
                            ->reorderable()
                            ->collapsible()
                            ->itemLabel(
                                fn(array $state): ?string =>
                                $state['player_id']
                                    ? Player::find($state['player_id'])?->name
                                    : 'New Player'
                            ),
                    ])
                    ->visible(fn($get) => $get('status') === 'finished')
                    ->collapsed(),

                // ============ SECTION 7: BOX SCORE TEAM 2 (AUTO-LOAD PLAYERS) ============
                Forms\Components\Section::make('Box Score - Team 2')
                    ->description(
                        fn($livewire): string =>
                        'Input statistik pemain ' . ($livewire->record?->team2?->name ?? 'Team 2')
                    )
                    ->schema([
                        Forms\Components\Repeater::make('box_score_team2')
                            ->label('Player Statistics')
                            ->schema([
                                Forms\Components\Select::make('player_id')
                                    ->label('Player')
                                    ->options(function ($livewire) {
                                        if (!$livewire->record) return [];
                                        return Player::where('team_id', $livewire->record->team2_id)
                                            ->where('is_active', true)
                                            ->orderBy('jersey_no')
                                            ->get()
                                            ->mapWithKeys(function ($player) {
                                                return [$player->id => "#{$player->jersey_no} {$player->name} ({$player->position})"];
                                            });
                                    })
                                    ->searchable()
                                    ->required()
                                    ->disableOptionWhen(function ($value, $get, $livewire) {
                                        // Prevent selecting same player twice
                                        $allPlayers = $get('../../box_score_team2');
                                        if (!is_array($allPlayers)) return false;

                                        $selectedIds = array_filter(array_column($allPlayers, 'player_id'));
                                        return in_array($value, $selectedIds) && $value != $get('player_id');
                                    }),

                                Forms\Components\TextInput::make('minutes')
                                    ->label('Min')
                                    ->numeric()
                                    ->default(0)
                                    ->required(),

                                Forms\Components\TextInput::make('points')
                                    ->label('Pts')
                                    ->numeric()
                                    ->default(0)
                                    ->required(),

                                Forms\Components\TextInput::make('assists')
                                    ->label('Ast')
                                    ->numeric()
                                    ->default(0)
                                    ->required(),

                                Forms\Components\TextInput::make('rebounds')
                                    ->label('Reb')
                                    ->numeric()
                                    ->default(0)
                                    ->required(),

                                Forms\Components\Toggle::make('is_mvp')
                                    ->label('MVP')
                                    ->default(false),
                            ])
                            ->columns(6)
                            ->defaultItems(function ($livewire) {
                                if (!$livewire->record || !$livewire->record->team2_id) return 0;
                                return Player::where('team_id', $livewire->record->team2_id)
                                    ->where('is_active', true)
                                    ->count();
                            })
                            ->default(function ($livewire) {
                                if (!$livewire->record || !$livewire->record->team2_id) return [];

                                return Player::where('team_id', $livewire->record->team2_id)
                                    ->where('is_active', true)
                                    ->orderBy('jersey_no')
                                    ->get()
                                    ->map(function ($player) {
                                        return [
                                            'player_id' => $player->id,
                                            'minutes' => 0,
                                            'points' => 0,
                                            'assists' => 0,
                                            'rebounds' => 0,
                                            'is_mvp' => false,
                                        ];
                                    })
                                    ->toArray();
                            })
                            ->reorderable()
                            ->collapsible()
                            ->itemLabel(
                                fn(array $state): ?string =>
                                $state['player_id']
                                    ? Player::find($state['player_id'])?->name
                                    : 'New Player'
                            ),
                    ])
                    ->visible(fn($get) => $get('status') === 'finished')
                    ->collapsed(),
            ]);
    }

    // Helper function to calculate final score
    // Helper function to calculate final score from array quarters
    protected static function calculateFinalScore($set, $get)
    {
        $quarters = $get('quarters') ?? [];

        // Ambil quarters dari nested array
        $team1Quarters = $quarters['team1'] ?? [0, 0, 0, 0];
        $team2Quarters = $quarters['team2'] ?? [0, 0, 0, 0];

        // Convert ke integer dan sum
        $total1 = array_sum(array_map('intval', $team1Quarters));
        $total2 = array_sum(array_map('intval', $team2Quarters));

        // Set score hanya jika ada value
        if ($total1 > 0 || $total2 > 0) {
            $set('score', "{$total1} - {$total2}");
        } else {
            $set('score', null);
        }
    }

    // Helper function to get score from quarters
    // Helper function to get score from quarters (untuk default value)
    protected static function getScoreFromQuarters($get)
    {
        $quarters = $get('quarters') ?? [];

        $team1Quarters = array_map('intval', $quarters['team1'] ?? [0, 0, 0, 0]);
        $team2Quarters = array_map('intval', $quarters['team2'] ?? [0, 0, 0, 0]);

        $total1 = array_sum($team1Quarters); // Sekarang benar
        $total2 = array_sum($team2Quarters);
        if ($total1 == 0 && $total2 == 0) {
            return null;
        }

        return "{$total1} - {$total2}";
    }
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->date('d M Y')
                    ->sortable()
                    ->searchable()
                    ->description(fn(Game $record): string => $record->time ? $record->time->format('H:i') . ' WIB' : ''),

                Tables\Columns\TextColumn::make('team1.name')
    ->label('Home Team')
    ->searchable()
    ->sortable()
    ->weight('medium')
    ->description(fn(Game $record): ?string => 
        $record->team1Category 
            ? $record->team1Category->category_name . ' (' . $record->team1Category->age_group . ')'
            : null
    ),

Tables\Columns\TextColumn::make('team2.name')
    ->label('Away Team')
    ->searchable()
    ->sortable()
    ->weight('medium')
    ->description(fn(Game $record): ?string => 
        $record->team2Category 
            ? $record->team2Category->category_name . ' (' . $record->team2Category->age_group . ')'
            : null
    ),

                Tables\Columns\TextColumn::make('score')
                    ->label('Score')
                    ->alignCenter()
                    ->weight('bold')
                    ->color('success')
                    ->default('-')
                    ->description(
                        fn(Game $record): ?string =>
                        $record->status === 'finished' && !$record->score
                            ? '⚠️ Belum input score'
                            : null
                    ),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'upcoming' => 'warning',
                        'live' => 'danger',
                        'finished' => 'success',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('series')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'Regular Season' => 'primary',
                        'Playoff' => 'warning',
                        'Finals' => 'success',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('region')
                    ->badge()
                    ->color('info')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('venue')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'upcoming' => 'Upcoming',
                        'live' => 'Live',
                        'finished' => 'Finished',
                    ])
                    ->label('Status'),

                Tables\Filters\SelectFilter::make('series')
                    ->options([
                        'Regular Season' => 'Regular Season',
                        'Playoff' => 'Playoff',
                        'Finals' => 'Finals',
                    ])
                    ->label('Series'),

                Tables\Filters\SelectFilter::make('region')
                    ->options([
                        'Jakarta' => 'Jakarta',
                        'Bandung' => 'Bandung',
                        'Surabaya' => 'Surabaya',
                        'Semarang' => 'Semarang',
                        'Medan' => 'Medan',
                        'Bali' => 'Bali',
                    ])
                    ->label('Region'),
            ])
            ->actions([
                // ============ MODAL VIEW DETAIL ============
                Tables\Actions\Action::make('view_details')
                    ->label('Detail Match')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->modalHeading(
                        fn(Game $record): string =>
                        'Detail Match: ' . $record->team1->name . ' vs ' . $record->team2->name
                    )
                    ->modalContent(
                        fn(Game $record): \Illuminate\View\View =>
                        view('filament.admin.resources.game.view-modal', ['record' => $record])
                    )

                    ->modalWidth('4xl')
                    ->slideOver()
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),

                Tables\Actions\Action::make('edit_match')
                    ->label('Update Statistic')
                    ->icon('heroicon-o-pencil-square')
                    ->color('warning')
                    ->modalHeading(fn(Game $record): string => 'Edit Match: ' . $record->team1->name . ' vs ' . $record->team2->name)
                    ->modalWidth('7xl')
                    ->slideOver()
                    ->fillForm(function (Game $record): array {
                        // Load quarters
                        $quarters = $record->quarters ?? ['team1' => [0, 0, 0, 0], 'team2' => [0, 0, 0, 0]];

                        // Load box score data dari player_stats table
                        $boxScoreTeam1 = $record->playerStats()
                            ->where('team_id', $record->team1_id)
                            ->with('player')
                            ->get()
                            ->map(function ($stat) {
                                return [
                                    'player_id' => $stat->player_id,
                                    'minutes' => $stat->minutes,
                                    'points' => $stat->points,
                                    'assists' => $stat->assists,
                                    'rebounds' => $stat->rebounds,
                                    'is_mvp' => $stat->is_mvp,
                                ];
                            })
                            ->toArray();

                        $boxScoreTeam2 = $record->playerStats()
                            ->where('team_id', $record->team2_id)
                            ->with('player')
                            ->get()
                            ->map(function ($stat) {
                                return [
                                    'player_id' => $stat->player_id,
                                    'minutes' => $stat->minutes,
                                    'points' => $stat->points,
                                    'assists' => $stat->assists,
                                    'rebounds' => $stat->rebounds,
                                    'is_mvp' => $stat->is_mvp,
                                ];
                            })
                            ->toArray();

                        // ✅ Auto-load players jika belum ada data
                        if (empty($boxScoreTeam1)) {
                            $boxScoreTeam1 = Player::where('team_id', $record->team1_id)
                                ->where('is_active', true)
                                ->orderBy('jersey_no')
                                ->get()
                                ->map(function ($player) {
                                    return [
                                        'player_id' => $player->id,
                                        'minutes' => 0,
                                        'points' => 0,
                                        'assists' => 0,
                                        'rebounds' => 0,
                                        'is_mvp' => false,
                                    ];
                                })
                                ->toArray();
                        }

                        if (empty($boxScoreTeam2)) {
                            $boxScoreTeam2 = Player::where('team_id', $record->team2_id)
                                ->where('is_active', true)
                                ->orderBy('jersey_no')
                                ->get()
                                ->map(function ($player) {
                                    return [
                                        'player_id' => $player->id,
                                        'minutes' => 0,
                                        'points' => 0,
                                        'assists' => 0,
                                        'rebounds' => 0,
                                        'is_mvp' => false,
                                    ];
                                })
                                ->toArray();
                        }

                        return [
                            'quarters' => $quarters,
                            'box_score_team1' => $boxScoreTeam1,
                            'box_score_team2' => $boxScoreTeam2,
                            'stat_fg_team1' => $record->stat_fg_team1,
                            'stat_fg_team2' => $record->stat_fg_team2,
                            'stat_2pt_team1' => $record->stat_2pt_team1,
                            'stat_2pt_team2' => $record->stat_2pt_team2,
                            'stat_3pt_team1' => $record->stat_3pt_team1,
                            'stat_3pt_team2' => $record->stat_3pt_team2,
                            'stat_ft_team1' => $record->stat_ft_team1,
                            'stat_ft_team2' => $record->stat_ft_team2,
                            'stat_reb_team1' => $record->stat_reb_team1,
                            'stat_reb_team2' => $record->stat_reb_team2,
                            'stat_ast_team1' => $record->stat_ast_team1,
                            'stat_ast_team2' => $record->stat_ast_team2,
                            'stat_stl_team1' => $record->stat_stl_team1,
                            'stat_stl_team2' => $record->stat_stl_team2,
                            'stat_blk_team1' => $record->stat_blk_team1,
                            'stat_blk_team2' => $record->stat_blk_team2,
                            'stat_to_team1' => $record->stat_to_team1,
                            'stat_to_team2' => $record->stat_to_team2,
                            'stat_foul_team1' => $record->stat_foul_team1,
                            'stat_foul_team2' => $record->stat_foul_team2,
                            'stat_pot_team1' => $record->stat_pot_team1,
                            'stat_pot_team2' => $record->stat_pot_team2,
                        ];
                    })
                   ->form([
    // ============ SECTION 0: MATCH STATUS (NEW) ============
    Forms\Components\Section::make('Match Status')
        ->description('Update status pertandingan')
        ->schema([
            Forms\Components\Select::make('status')
                ->label('Status')
                ->options([
                    'upcoming' => 'Upcoming',
                    'live' => 'Live',
                    'finished' => 'Finished',
                ])
                ->required()
                ->default(fn($record) => $record->status)
                ->live()
                ->afterStateUpdated(function ($state, $record) {
                    $record->update(['status' => $state]);
                    
                    Notification::make()
                        ->title('Status Updated!')
                        ->body("Status changed to: {$state}")
                        ->success()
                        ->send();
                })
                ->helperText('Status akan otomatis tersimpan saat Anda mengubahnya'),
        ])
        ->columns(1)
        ->collapsible(),
                        // ============ SECTION 1: QUARTER SCORES ============
                        Forms\Components\Section::make('Quarter Scores')
                            ->schema([
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        // Team 1 Quarters
                                        Forms\Components\Group::make()
                                            ->schema([
                                                Forms\Components\Placeholder::make('team1_header')
                                                    ->label('')
                                                    ->content(
                                                        fn($record): \Illuminate\Support\HtmlString =>
                                                        new \Illuminate\Support\HtmlString('<strong>' . $record->team1->name . '</strong>')
                                                    ),

                                                Forms\Components\Grid::make(4)
                                                    ->schema([
                                                        Forms\Components\TextInput::make('quarters.team1.0')
                                                            ->label('Q1')
                                                            ->numeric()
                                                            ->required(),
                                                        Forms\Components\TextInput::make('quarters.team1.1')
                                                            ->label('Q2')
                                                            ->numeric()
                                                            ->required(),
                                                        Forms\Components\TextInput::make('quarters.team1.2')
                                                            ->label('Q3')
                                                            ->numeric()
                                                            ->required(),
                                                        Forms\Components\TextInput::make('quarters.team1.3')
                                                            ->label('Q4')
                                                            ->numeric()
                                                            ->required(),
                                                    ]),
                                            ]),

                                        // Team 2 Quarters
                                        Forms\Components\Group::make()
                                            ->schema([
                                                Forms\Components\Placeholder::make('team2_header')
                                                    ->label('')
                                                    ->content(
                                                        fn($record): \Illuminate\Support\HtmlString =>
                                                        new \Illuminate\Support\HtmlString('<strong>' . $record->team2->name . '</strong>')
                                                    ),

                                                Forms\Components\Grid::make(4)
                                                    ->schema([
                                                        Forms\Components\TextInput::make('quarters.team2.0')
                                                            ->label('Q1')
                                                            ->numeric()
                                                            ->required(),
                                                        Forms\Components\TextInput::make('quarters.team2.1')
                                                            ->label('Q2')
                                                            ->numeric()
                                                            ->required(),
                                                        Forms\Components\TextInput::make('quarters.team2.2')
                                                            ->label('Q3')
                                                            ->numeric()
                                                            ->required(),
                                                        Forms\Components\TextInput::make('quarters.team2.3')
                                                            ->label('Q4')
                                                            ->numeric()
                                                            ->required(),
                                                    ]),
                                            ]),
                                    ]),
                            ])
                            ->columns(1),

                        // ============ SECTION 2: TEAM STATISTICS ============
                        Forms\Components\Section::make('Team Statistics')
                            ->schema([
                                // Header Row
                                Forms\Components\Grid::make(3)
                                    ->schema([
                                        Forms\Components\Placeholder::make('header_stat')
                                            ->label('')
                                            ->content(
                                                fn(): \Illuminate\Support\HtmlString =>
                                                new \Illuminate\Support\HtmlString('<strong>STATISTIK</strong>')
                                            ),
                                        Forms\Components\Placeholder::make('header_team1')
                                            ->label('')
                                            ->content(
                                                fn($record): \Illuminate\Support\HtmlString =>
                                                new \Illuminate\Support\HtmlString('<strong>' . $record->team1->name . '</strong>')
                                            ),
                                        Forms\Components\Placeholder::make('header_team2')
                                            ->label('')
                                            ->content(
                                                fn($record): \Illuminate\Support\HtmlString =>
                                                new \Illuminate\Support\HtmlString('<strong>' . $record->team2->name . '</strong>')
                                            ),
                                    ]),

                                // Field Goals
                                Forms\Components\Grid::make(3)
                                    ->schema([
                                        Forms\Components\Placeholder::make('fg_label')->label('')->content('Field Goals'),
                                        Forms\Components\TextInput::make('stat_fg_team1')->label('')->placeholder('40/80 (50%)'),
                                        Forms\Components\TextInput::make('stat_fg_team2')->label('')->placeholder('38/82 (46%)'),
                                    ]),

                                // 2 Points
                                Forms\Components\Grid::make(3)
                                    ->schema([
                                        Forms\Components\Placeholder::make('2pt_label')->label('')->content('2 Points'),
                                        Forms\Components\TextInput::make('stat_2pt_team1')->label('')->placeholder('30/50 (60%)'),
                                        Forms\Components\TextInput::make('stat_2pt_team2')->label('')->placeholder('28/52 (54%)'),
                                    ]),

                                // 3 Points
                                Forms\Components\Grid::make(3)
                                    ->schema([
                                        Forms\Components\Placeholder::make('3pt_label')->label('')->content('3 Points'),
                                        Forms\Components\TextInput::make('stat_3pt_team1')->label('')->placeholder('10/30 (33%)'),
                                        Forms\Components\TextInput::make('stat_3pt_team2')->label('')->placeholder('10/30 (33%)'),
                                    ]),

                                // Free Throws
                                Forms\Components\Grid::make(3)
                                    ->schema([
                                        Forms\Components\Placeholder::make('ft_label')->label('')->content('Free Throws'),
                                        Forms\Components\TextInput::make('stat_ft_team1')->label('')->placeholder('15/20 (75%)'),
                                        Forms\Components\TextInput::make('stat_ft_team2')->label('')->placeholder('12/18 (67%)'),
                                    ]),

                                // Rebounds
                                Forms\Components\Grid::make(3)
                                    ->schema([
                                        Forms\Components\Placeholder::make('reb_label')->label('')->content('Rebounds (O/D)'),
                                        Forms\Components\TextInput::make('stat_reb_team1')->label('')->placeholder('10/30'),
                                        Forms\Components\TextInput::make('stat_reb_team2')->label('')->placeholder('8/28'),
                                    ]),

                                // Assist
                                Forms\Components\Grid::make(3)
                                    ->schema([
                                        Forms\Components\Placeholder::make('ast_label')->label('')->content('Assist'),
                                        Forms\Components\TextInput::make('stat_ast_team1')->label('')->placeholder('20'),
                                        Forms\Components\TextInput::make('stat_ast_team2')->label('')->placeholder('18'),
                                    ]),

                                // Steals
                                Forms\Components\Grid::make(3)
                                    ->schema([
                                        Forms\Components\Placeholder::make('stl_label')->label('')->content('Steals'),
                                        Forms\Components\TextInput::make('stat_stl_team1')->label('')->placeholder('8'),
                                        Forms\Components\TextInput::make('stat_stl_team2')->label('')->placeholder('6'),
                                    ]),

                                // Blocks
                                Forms\Components\Grid::make(3)
                                    ->schema([
                                        Forms\Components\Placeholder::make('blk_label')->label('')->content('Blocks'),
                                        Forms\Components\TextInput::make('stat_blk_team1')->label('')->placeholder('5'),
                                        Forms\Components\TextInput::make('stat_blk_team2')->label('')->placeholder('4'),
                                    ]),

                                // Turnovers
                                Forms\Components\Grid::make(3)
                                    ->schema([
                                        Forms\Components\Placeholder::make('to_label')->label('')->content('Turnovers'),
                                        Forms\Components\TextInput::make('stat_to_team1')->label('')->placeholder('12'),
                                        Forms\Components\TextInput::make('stat_to_team2')->label('')->placeholder('15'),
                                    ]),

                                // Fouls
                                Forms\Components\Grid::make(3)
                                    ->schema([
                                        Forms\Components\Placeholder::make('foul_label')->label('')->content('Fouls'),
                                        Forms\Components\TextInput::make('stat_foul_team1')->label('')->placeholder('18'),
                                        Forms\Components\TextInput::make('stat_foul_team2')->label('')->placeholder('20'),
                                    ]),

                                // Points Off Turnover
                                Forms\Components\Grid::make(3)
                                    ->schema([
                                        Forms\Components\Placeholder::make('pot_label')->label('')->content('Points Off Turnover'),
                                        Forms\Components\TextInput::make('stat_pot_team1')->label('')->placeholder('15'),
                                        Forms\Components\TextInput::make('stat_pot_team2')->label('')->placeholder('12'),
                                    ]),
                            ])
                            ->collapsed(),

                        // ============ SECTION 3: BOX SCORE TEAM 1 (IMPROVED LAYOUT) ============
                        Forms\Components\Section::make('Box Score - Team 1')
                            ->description(fn($record): string => 'Input statistik pemain ' . $record->team1->name)
                            ->schema([
                                Forms\Components\Repeater::make('box_score_team1')
                                    ->label('Player Statistics')
                                    ->schema([
                                        Forms\Components\Select::make('player_id')
                                            ->label('Player')
                                            ->options(function ($record) {
                                                return Player::where('team_id', $record->team1_id)
                                                    ->where('is_active', true)
                                                    ->orderBy('jersey_no')
                                                    ->get()
                                                    ->mapWithKeys(function ($player) {
                                                        return [$player->id => "#{$player->jersey_no} - {$player->name} ({$player->position})"];
                                                    });
                                            })
                                            ->searchable()
                                            ->required()
                                            ->disableOptionWhen(function ($value, $get) {
                                                $allPlayers = $get('../../box_score_team1');
                                                if (!is_array($allPlayers)) return false;
                                                $selectedIds = array_filter(array_column($allPlayers, 'player_id'));
                                                return in_array($value, $selectedIds) && $value != $get('player_id');
                                            })
                                            ->columnSpan(2),

                                        Forms\Components\TextInput::make('minutes')
                                            ->label('Min')
                                            ->numeric()
                                            ->default(0)
                                            ->required()
                                            ->columnSpan(1),

                                        Forms\Components\TextInput::make('points')
                                            ->label('Pts')
                                            ->numeric()
                                            ->default(0)
                                            ->required()
                                            ->columnSpan(1),

                                        Forms\Components\TextInput::make('assists')
                                            ->label('Ast')
                                            ->numeric()
                                            ->default(0)
                                            ->required()
                                            ->columnSpan(1),

                                        Forms\Components\TextInput::make('rebounds')
                                            ->label('Reb')
                                            ->numeric()
                                            ->default(0)
                                            ->required()
                                            ->columnSpan(1),

                                        Forms\Components\Toggle::make('is_mvp')
                                            ->label('MVP')
                                            ->default(false)
                                            ->columnSpan(1),
                                    ])
                                    ->columns(7)
                                    ->reorderable()
                                    ->collapsible()
                                    ->itemLabel(
                                        fn(array $state): ?string =>
                                        isset($state['player_id']) && $state['player_id']
                                            ? Player::find($state['player_id'])?->name
                                            : 'New Player'
                                    )
                                    ->defaultItems(0)
                                    ->maxItems(5)
                                    ->addActionLabel('Add Player'),
                            ])
                            ->collapsed(),

                        // ============ SECTION 4: BOX SCORE TEAM 2 (IMPROVED LAYOUT) ============
                        Forms\Components\Section::make('Box Score - Team 2')
                            ->description(fn($record): string => 'Input statistik pemain ' . $record->team2->name)
                            ->schema([
                                Forms\Components\Repeater::make('box_score_team2')
                                    ->label('Player Statistics')
                                    ->schema([
                                        Forms\Components\Select::make('player_id')
                                            ->label('Player')
                                            ->options(function ($record) {
                                                return Player::where('team_id', $record->team2_id)
                                                    ->where('is_active', true)
                                                    ->orderBy('jersey_no')
                                                    ->get()
                                                    ->mapWithKeys(function ($player) {
                                                        return [$player->id => "#{$player->jersey_no} - {$player->name} ({$player->position})"];
                                                    });
                                            })
                                            ->searchable()
                                            ->required()
                                            ->disableOptionWhen(function ($value, $get) {
                                                $allPlayers = $get('../../box_score_team2');
                                                if (!is_array($allPlayers)) return false;
                                                $selectedIds = array_filter(array_column($allPlayers, 'player_id'));
                                                return in_array($value, $selectedIds) && $value != $get('player_id');
                                            })
                                            ->columnSpan(2),

                                        Forms\Components\TextInput::make('minutes')
                                            ->label('Min')
                                            ->numeric()
                                            ->default(0)
                                            ->required()
                                            ->columnSpan(1),

                                        Forms\Components\TextInput::make('points')
                                            ->label('Pts')
                                            ->numeric()
                                            ->default(0)
                                            ->required()
                                            ->columnSpan(1),

                                        Forms\Components\TextInput::make('assists')
                                            ->label('Ast')
                                            ->numeric()
                                            ->default(0)
                                            ->required()
                                            ->columnSpan(1),

                                        Forms\Components\TextInput::make('rebounds')
                                            ->label('Reb')
                                            ->numeric()
                                            ->default(0)
                                            ->required()
                                            ->columnSpan(1),

                                        Forms\Components\Toggle::make('is_mvp')
                                            ->label('MVP')
                                            ->default(false)
                                            ->columnSpan(1),
                                    ])
                                    ->columns(7)
                                    ->reorderable()
                                    ->collapsible()
                                    ->itemLabel(
                                        fn(array $state): ?string =>
                                        isset($state['player_id']) && $state['player_id']
                                            ? Player::find($state['player_id'])?->name
                                            : 'New Player'
                                    )
                                    ->defaultItems(0)
                                    ->maxItems(5)
                                    ->addActionLabel('Add Player'),
                            ])
                            ->collapsed(),
                    ])
                    ->action(function (Game $record, array $data): void {
                        // Calculate total score from quarters
                        $team1Quarters = $data['quarters']['team1'] ?? [0, 0, 0, 0];
                        $team2Quarters = $data['quarters']['team2'] ?? [0, 0, 0, 0];
                        $total1 = array_sum(array_map('intval', $team1Quarters));
                        $total2 = array_sum(array_map('intval', $team2Quarters));

                        // Update game data
                        $record->update([
                            'quarters' => $data['quarters'],
                            'score' => "{$total1} - {$total2}",
                            'stat_fg_team1' => $data['stat_fg_team1'] ?? null,
                            'stat_fg_team2' => $data['stat_fg_team2'] ?? null,
                            'stat_2pt_team1' => $data['stat_2pt_team1'] ?? null,
                            'stat_2pt_team2' => $data['stat_2pt_team2'] ?? null,
                            'stat_3pt_team1' => $data['stat_3pt_team1'] ?? null,
                            'stat_3pt_team2' => $data['stat_3pt_team2'] ?? null,
                            'stat_ft_team1' => $data['stat_ft_team1'] ?? null,
                            'stat_ft_team2' => $data['stat_ft_team2'] ?? null,
                            'stat_reb_team1' => $data['stat_reb_team1'] ?? null,
                            'stat_reb_team2' => $data['stat_reb_team2'] ?? null,
                            'stat_ast_team1' => $data['stat_ast_team1'] ?? null,
                            'stat_ast_team2' => $data['stat_ast_team2'] ?? null,
                            'stat_stl_team1' => $data['stat_stl_team1'] ?? null,
                            'stat_stl_team2' => $data['stat_stl_team2'] ?? null,
                            'stat_blk_team1' => $data['stat_blk_team1'] ?? null,
                            'stat_blk_team2' => $data['stat_blk_team2'] ?? null,
                            'stat_to_team1' => $data['stat_to_team1'] ?? null,
                            'stat_to_team2' => $data['stat_to_team2'] ?? null,
                            'stat_foul_team1' => $data['stat_foul_team1'] ?? null,
                            'stat_foul_team2' => $data['stat_foul_team2'] ?? null,
                            'stat_pot_team1' => $data['stat_pot_team1'] ?? null,
                            'stat_pot_team2' => $data['stat_pot_team2'] ?? null,
                        ]);

                        // Save Box Score Team 1
                        if (isset($data['box_score_team1']) && !empty($data['box_score_team1'])) {
                            PlayerStat::where('game_id', $record->id)
                                ->where('team_id', $record->team1_id)
                                ->delete();

                            foreach ($data['box_score_team1'] as $stat) {
                                if (isset($stat['player_id'])) {
                                    PlayerStat::create([
                                        'game_id' => $record->id,
                                        'player_id' => $stat['player_id'],
                                        'team_id' => $record->team1_id,
                                        'minutes' => $stat['minutes'] ?? 0,
                                        'points' => $stat['points'] ?? 0,
                                        'assists' => $stat['assists'] ?? 0,
                                        'rebounds' => $stat['rebounds'] ?? 0,
                                        'is_mvp' => $stat['is_mvp'] ?? false,
                                    ]);
                                }
                            }
                        }

                        // Save Box Score Team 2
                        if (isset($data['box_score_team2']) && !empty($data['box_score_team2'])) {
                            PlayerStat::where('game_id', $record->id)
                                ->where('team_id', $record->team2_id)
                                ->delete();

                            foreach ($data['box_score_team2'] as $stat) {
                                if (isset($stat['player_id'])) {
                                    PlayerStat::create([
                                        'game_id' => $record->id,
                                        'player_id' => $stat['player_id'],
                                        'team_id' => $record->team2_id,
                                        'minutes' => $stat['minutes'] ?? 0,
                                        'points' => $stat['points'] ?? 0,
                                        'assists' => $stat['assists'] ?? 0,
                                        'rebounds' => $stat['rebounds'] ?? 0,
                                        'is_mvp' => $stat['is_mvp'] ?? false,
                                    ]);
                                }
                            }
                        }

                        Notification::make()
                            ->title('Match Updated Successfully!')
                            ->success()
                            ->body("{$record->team1->name} {$total1} - {$total2} {$record->team2->name}")
                            ->send();
                    })
                    ->modalSubmitActionLabel('Save Changes')
                    ->modalCancelActionLabel('Cancel'),

                // ✅ Pastikan ada koma di sini
                // ============ MODAL UPDATE SCORE ============
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('date', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGames::route('/'),
            'create' => Pages\CreateGame::route('/create'),
            'edit' => Pages\EditGame::route('/{record}/edit'),
        ];
    }
}
