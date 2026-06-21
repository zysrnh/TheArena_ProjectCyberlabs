<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\TeamCategoryResource\Pages;
use App\Models\TeamCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TeamCategoryResource extends Resource
{
    protected static ?string $model = TeamCategory::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationLabel = 'Age Categories';
    protected static ?string $navigationGroup = 'Match & Team Management';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Category Information')
                    ->schema([
                        Forms\Components\TextInput::make('category_name')
                            ->label('Category Name')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->placeholder('e.g., U-16 PA'),

                        Forms\Components\TextInput::make('age_group')
                            ->label('Age Group')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., Under 16'),

                        Forms\Components\TextInput::make('min_age')
                            ->label('Min Age')
                            ->numeric()
                            ->nullable()
                            ->placeholder('e.g., 14'),

                        Forms\Components\TextInput::make('max_age')
                            ->label('Max Age')
                            ->numeric()
                            ->nullable()
                            ->placeholder('e.g., 16'),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->helperText('Only active categories will appear in game creation'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('category_name')
                    ->label('Category')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('age_group')
                    ->label('Age Group')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('min_age')
                    ->label('Min Age')
                    ->sortable()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('max_age')
                    ->label('Max Age')
                    ->sortable()
                    ->placeholder('-'),

                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Active')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->date('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status')
                    ->trueLabel('Active Only')
                    ->falseLabel('Inactive Only')
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('category_name', 'asc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTeamCategories::route('/'),
            'create' => Pages\CreateTeamCategory::route('/create'),
            'edit' => Pages\EditTeamCategory::route('/{record}/edit'),
        ];
    }
}