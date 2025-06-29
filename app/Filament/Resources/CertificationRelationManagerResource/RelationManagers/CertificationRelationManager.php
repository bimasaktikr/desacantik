<?php

namespace App\Filament\Resources\CertificationRelationManagerResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CertificationRelationManager extends RelationManager
{
    protected static string $relationship = 'certifications';

    protected static ?string $recordTitleAttribute = 'name'; // asumsikan certificate ada kolom name

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('pivot.issue_date')
                    ->label('Issue Date')
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Certificate Name'),
                Tables\Columns\TextColumn::make('pivot.issue_date')->label('Issue Date')->date(),
            ])
            ->filters([])
            ->headerActions([
                Tables\Actions\AttachAction::make(),
            ])
            ->actions([
                Tables\Actions\DetachAction::make(),
                Tables\Actions\EditAction::make(),
            ]);
    }
}
