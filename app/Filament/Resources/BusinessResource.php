<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BusinessResource\Pages;
use App\Filament\Resources\BusinessResource\RelationManagers;
use App\Filament\Resources\CertificationRelationManagerResource\RelationManagers\CertificationRelationManager;
use App\Models\Business;
use App\Models\District;
use App\Models\Sls;
use App\Models\Village;
use Filament\Forms;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Wizard\Step;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BusinessResource extends Resource
{
    protected static ?string $model = Business::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Pendataan';

    protected static ?string $navigationLabel = 'Data Usaha';

    protected static ?string $title = 'Data Usaha';

    protected static ?string $slug = 'data-usaha';


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Wizard::make([
                    Step::make('Data Usaha')
                        ->schema([
                            TextInput::make('name')
                                ->label('Nama Usaha')
                                ->required()
                                ->maxLength(255)
                                ->placeholder('Masukkan Nama Usaha'),
                            Textarea::make('address')
                                ->label('Alamat Usaha')
                                ->required()
                                ->maxLength(255)
                                ->placeholder('Masukkan Alamat Usaha'),
                            Grid::make(2)
                                ->schema([
                                    TextInput::make('phone')
                                        ->label('No. Telepon Usaha')
                                        ->required()
                                        ->maxLength(255)
                                        ->placeholder('Masukkan No. Telepon Usaha'),
                                    TextInput::make('email')
                                        ->label('Email Usaha')
                                        ->email()
                                        ->required()
                                        ->maxLength(255)
                                        ->placeholder('Masukkan Email Usaha'),
                                ]),
                            TextInput::make('description')
                                ->label('Deskripsi Usaha')
                                ->required()
                                ->maxLength(255)
                                ->placeholder('Masukkan Deskripsi Usaha secara lengkap'),
                            Select::make('business_category_id')
                                ->relationship('businessCategory', 'code')
                                ->label('Kategori Usaha')
                                ->reactive()
                                ->preload()
                                ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->code}. {$record->description}")
                                ->required()
                                ->searchable()
                                ->placeholder('Pilih Kategori Usaha'),
                            Grid::make(2)
                                ->schema([
                                    Select::make('status_bangunan')
                                    ->label('Status Bangunan')
                                    ->options([
                                        'Tetap' => 'Tetap',
                                        'Tidak Tetap' => 'Tidak Tetap',
                                    ])
                                    ->required()
                                    ->placeholder('Pilih Status Bangunan'),
                                    Select::make('online_status')
                                    ->label('Status Online')
                                    ->options([
                                        'Ya' => 'Ya',
                                        'Tidak' => 'Ya',
                                    ])
                                    ->required()
                                    ->placeholder('Pilih Status Online'),
                                ]),
                        ]),
                    Step::make('Lokasi Usaha')
                        ->schema([
                            Fieldset::make('Lokasi Usaha')
                                ->schema([
                                    Grid::make(3)
                                        ->schema([
                                            Select::make('district_id')
                                                ->label('Kecamatan')
                                                ->options(fn (callable $get) =>
                                                    District::pluck('name', 'id')
                                                )
                                                ->reactive()
                                                ->afterStateUpdated(fn ($state, callable $set) => $set('village_id', null))
                                                ->required(),

                                            Select::make('village_id')
                                                ->label('Desa/Kelurahan')
                                                ->options(fn (callable $get) =>
                                                    Village::where('district_id', $get('district_id'))
                                                        ->pluck('name', 'id')
                                                )
                                                ->reactive()
                                                ->afterStateUpdated(fn ($state, callable $set) => $set('sls_id', null))
                                                ->required(),

                                            Select::make('sls_id')
                                                ->label('SLS')
                                                ->options(fn (callable $get) =>
                                                    SLS::where('village_id', $get('village_id'))
                                                        ->pluck('name', 'id')
                                                )
                                                ->required(),
                                        ]),
                                    TextInput::make('latitude')
                                        ->label('Latitude')
                                        ->required()
                                        ->numeric()
                                        ->rules(['numeric', 'between:-90,90'])
                                        ->placeholder('Contoh: -7.77455123'),
                                    TextInput::make('longitude')
                                        ->label('Longitude')
                                        ->required()
                                        ->numeric()
                                        ->rules(['numeric', 'between:-180,180'])
                                        ->placeholder('Contoh: 112.63239112'),
                                ]),
                            ]),
                    Step::make('Data Pemilik Usaha')
                        ->schema([
                            TextInput::make('owner_name')
                                ->label('Nama Pemilik Usaha')
                                ->required()
                                ->maxLength(255)
                                ->placeholder('Masukkan Nama Pemilik Usaha'),
                            TextInput::make('owner_address')
                                ->label('Alamat Pemilik Usaha')
                                ->required()
                                ->maxLength(255)
                                ->placeholder('Masukkan Alamat Pemilik Usaha'),
                            TextInput::make('owner_phone')
                                ->label('No. Telepon Pemilik Usaha')
                                ->required()
                                ->maxLength(255)
                                ->placeholder('Masukkan No. Telepon Pemilik Usaha'),
                            Select::make('pembinaan')
                                ->label('Apakah Pemilik Usaha ingin dibina?')
                                ->options([
                                    'Ya' => 'Ya',
                                    'Tidak' => 'Tidak',
                                ])
                                ->required()
                                ->placeholder('Pilih Pembinaan'),
                        ])
                ])
                ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Usaha')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('address')
                    ->label('Alamat Usaha')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('description')
                    ->label('Deskripsi Usaha')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('businessCategory.code')
                    ->label('Kategori Usaha')
                    ->getStateUsing(fn ($record) => optional($record->businessCategory)?->code . '. ' . optional($record->businessCategory)?->description)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('sls.name')
                    ->label('SLS')
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),

            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            CertificationRelationManager::class,
        ];
    }


    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBusinesses::route('/'),
            'create' => Pages\CreateBusiness::route('/create'),
            'edit' => Pages\EditBusiness::route('/{record}/edit'),
        ];
    }


}
