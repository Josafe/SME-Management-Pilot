<?php

namespace App\Filament\Resources\Clients\Schemas;

use App\Enums\ClientType;
use App\Models\Client;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ClientForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Client details')
                    ->columns(2)
                    ->columnSpanFull()
                    ->components([
                        Select::make('type')
                            ->options(ClientType::class)
                            ->default(ClientType::Company)
                            ->required(),
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('tax_id')
                            ->label('Tax ID (NIF/CIF)')
                            ->maxLength(20)
                            ->mutateStateForValidationUsing(fn (?string $state): ?string => Client::normalizeTaxId($state))
                            ->unique(ignoreRecord: true),
                        TextInput::make('email')
                            ->label('Email address')
                            ->email()
                            ->maxLength(255),
                        TextInput::make('phone')
                            ->tel()
                            ->maxLength(30),
                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                        Textarea::make('notes')
                            ->maxLength(5000)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
