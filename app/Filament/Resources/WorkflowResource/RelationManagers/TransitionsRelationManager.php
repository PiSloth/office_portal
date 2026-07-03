<?php

namespace App\Filament\Resources\WorkflowResource\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TransitionsRelationManager extends RelationManager
{
    protected static string $relationship = 'transitions';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('from_state_id')
                    ->label('From State')
                    ->options(fn ($livewire) => $livewire->getOwnerRecord()->states->pluck('name', 'id'))
                    ->required(),
                Select::make('to_state_id')
                    ->label('To State')
                    ->options(fn ($livewire) => $livewire->getOwnerRecord()->states->pluck('name', 'id'))
                    ->required(),
                TextInput::make('action_name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('required_permission')
                    ->maxLength(255),
                Select::make('validation_rule_set_id')
                    ->label('Validation Rule Set')
                    ->options(\App\Modules\Core\Validation\Models\ValidationRuleSet::pluck('name', 'id'))
                    ->nullable(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('action_name')
            ->columns([
                TextColumn::make('fromState.name')
                    ->label('From State')
                    ->sortable(),
                TextColumn::make('toState.name')
                    ->label('To State')
                    ->sortable(),
                TextColumn::make('action_name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('required_permission')
                    ->sortable(),
                TextColumn::make('validationRuleSet.name')
                    ->label('Validation Rule Set')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
