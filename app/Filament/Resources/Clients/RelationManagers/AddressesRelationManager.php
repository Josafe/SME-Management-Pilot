<?php

namespace App\Filament\Resources\Clients\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AddressesRelationManager extends RelationManager
{
    protected static string $relationship = 'addresses';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('label')
                    ->required()
                    ->maxLength(100),
                TextInput::make('street')->label('Street and number')->required()->maxLength(255),
                TextInput::make('postal_code')->required()->maxLength(10),
                TextInput::make('city')->required()->maxLength(100),
                TextInput::make('province')->maxLength(100),
                TextInput::make('country')->label('Country (ISO code)')->required()->length(2)->default('ES')
                    ->dehydrateStateUsing(fn (?string $state): ?string => $state === null ? null : strtoupper($state)),
                Toggle::make('is_default')->label('Default address'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('label')
            ->columns([
                TextColumn::make('label')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('street')->searchable(),
                TextColumn::make('postal_code'),
                TextColumn::make('city')->searchable()->sortable(),
                TextColumn::make('country'),
                IconColumn::make('is_default')->label('Default')->boolean(),
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
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('is_default', 'desc');
    }
}
