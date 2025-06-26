<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\EditAction;
use App\Models\Business;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Actions\Action;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Grid as InfolistGrid;
use Filament\Forms\Components\Section;

class UpdateBusiness extends Page implements HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static string $view = 'filament.pages.update-business';
    protected static ?string $navigationIcon = 'heroicon-o-pencil-square';
    protected static ?string $title = 'Update Business';
    protected static ?string $slug = 'update-business';
    protected static ?string $navigationLabel = 'Update Data Usaha';
    protected static ?string $navigationGroup = 'Pendataan';

    public static function shouldRegisterNavigation(): bool
    {
        $user = Auth::user();
        // Debug: dump the user's roles
        // dd($user ? $user->roles->pluck('name') : null);
        return $user && ($user->roles->contains('name', 'Employee') || $user->roles->contains('name', 'Mahasiswa'));
    }

    public function table(Tables\Table $table): Tables\Table
    {
        $user = Auth::user();
        $query = Business::query();

        // Both Employee and Mahasiswa: show all businesses in villages assigned to them
        if ($user->roles->contains('name', 'Employee') || $user->roles->contains('name', 'Mahasiswa')) {
            $assignedVillageIds = \App\Models\Assignment::where('user_id', $user->id)
                ->where('area_type', 'App\\Models\\Village')
                ->pluck('area_id')
                ->toArray();
            $query->whereIn('village_id', $assignedVillageIds);
        }
        // Other roles see all businesses

        return $table
            ->query($query)
            ->columns([
                Split::make([
                    Stack::make([
                        TextColumn::make('name')
                            ->label('Business Name')
                            ->weight(FontWeight::Bold)
                            ->searchable()
                            ->sortable(),
                        TextColumn::make('description')
                            ->label('Description')
                            ->color('gray')
                            ->size('sm')
                            ->wrap()
                            ->limit(50),
                    ]),

                    Stack::make([
                        TextColumn::make('address')
                            ->label('Address')
                            ->color('gray')
                            ->size('sm'),
                        TextColumn::make('village.name')
                            ->label('Village')
                            ->sortable(),
                        TextColumn::make('sls.name')
                            ->label('SLS')
                            ->sortable(),

                    ]),
                ])->from('md'),

                TextColumn::make('business_category')
                    ->label('Category')
                    ->badge()
                    ->color('info')
                    ->getStateUsing(function ($record) {
                        return optional($record->businessCategory)->code . ' - ' . optional($record->businessCategory)->description;
                    }),
                TextColumn::make('status_bangunan')
                    ->label('Building Status')
                    ->badge()
                    ->color('warning')
                    ->sortable(),
                TextColumn::make('owner_name')
                    ->label('Owner Name')
                    ->color('primary')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('phone')
                    ->label('Phone')
                    ->icon('heroicon-m-phone')
                    ->sortable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->icon('heroicon-m-envelope')
                    ->sortable(),
            ])
            ->actions([
                EditAction::make()
                    ->form([
                        TextInput::make('name')
                            ->required()
                            ->label('Business Name'),
                        TextInput::make('description')
                            ->required()
                            ->label('Description'),
                        TextInput::make('address')
                            ->required()
                            ->label('Address'),
                        Select::make('business_category_id')
                            ->label('Category')
                            ->relationship('businessCategory', 'code')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->code . ' - ' . $record->description)
                            ->required(),
                    ]),
                Action::make('flag')
                    ->label('Flag Fields')
                    ->icon('heroicon-o-flag')
                    ->color('warning')
                    ->form([
                        Section::make('Business Name')
                            ->description(fn($record) => $record->name)
                            ->aside()
                            ->schema([
                                Toggle::make('name_error')->label('Flag for review'),
                            ]),
                        Section::make('Description')
                            ->description(fn($record) => $record->description)
                            ->aside()
                            ->schema([
                                Toggle::make('description_error')->label('Flag for review'),
                            ]),
                        Section::make('Address')
                            ->description(fn($record) => $record->address)
                            ->aside()
                            ->schema([
                                Toggle::make('address_error')->label('Flag for review'),
                            ]),
                        Section::make('Business Category')
                            ->description(fn($record) => optional($record->businessCategory)->code . ' - ' . optional($record->businessCategory)->description)
                            ->aside()
                            ->schema([
                                Toggle::make('business_category_id_error')->label('Flag for review'),
                            ]),
                    ])
                    ->modalWidth('2xl')
                    ->visible(fn () => \Illuminate\Support\Facades\Auth::user()->roles->contains('name', 'Employee')),
            ])
            ->striped();
    }
}
