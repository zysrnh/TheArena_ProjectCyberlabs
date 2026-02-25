<?php

namespace App\Filament\Admin\Resources\GameResource\Pages;

use App\Filament\Admin\Resources\GameResource;
use App\Models\PlayerStat;
use Filament\Resources\Pages\CreateRecord;

class CreateGame extends CreateRecord
{
    protected static string $resource = GameResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /**
     * Setelah record di-create, sync box score ke PlayerStat table
     */
    protected function afterCreate(): void
    {
        $data = $this->form->getState();
        $record = $this->record;

        // ✅ Sync Box Score Team 1 → PlayerStat
        if (!empty($data['box_score_team1'])) {
            PlayerStat::where('game_id', $record->id)
                ->where('team_id', $record->team1_id)
                ->delete();

            foreach ($data['box_score_team1'] as $stat) {
                if (!empty($stat['player_id'])) {
                    PlayerStat::create([
                        'game_id'   => $record->id,
                        'player_id' => $stat['player_id'],
                        'team_id'   => $record->team1_id,
                        'minutes'   => $stat['minutes'] ?? 0,
                        'points'    => $stat['points'] ?? 0,
                        'assists'   => $stat['assists'] ?? 0,
                        'rebounds'  => $stat['rebounds'] ?? 0,
                        'is_mvp'    => $stat['is_mvp'] ?? false,
                    ]);
                }
            }
        }

        // ✅ Sync Box Score Team 2 → PlayerStat
        if (!empty($data['box_score_team2'])) {
            PlayerStat::where('game_id', $record->id)
                ->where('team_id', $record->team2_id)
                ->delete();

            foreach ($data['box_score_team2'] as $stat) {
                if (!empty($stat['player_id'])) {
                    PlayerStat::create([
                        'game_id'   => $record->id,
                        'player_id' => $stat['player_id'],
                        'team_id'   => $record->team2_id,
                        'minutes'   => $stat['minutes'] ?? 0,
                        'points'    => $stat['points'] ?? 0,
                        'assists'   => $stat['assists'] ?? 0,
                        'rebounds'  => $stat['rebounds'] ?? 0,
                        'is_mvp'    => $stat['is_mvp'] ?? false,
                    ]);
                }
            }
        }
    }
}