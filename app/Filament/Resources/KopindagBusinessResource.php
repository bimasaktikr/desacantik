<?php

namespace App\Filament\Resources;

use App\Filament\Resources\KopindagBusinessResource\Pages;
use App\Filament\Resources\KopindagBusinessResource\RelationManagers;
use App\Models\KopindagBusiness;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class KopindagBusinessResource extends Resource
{
    protected static ?string $model = KopindagBusiness::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Data Usaha';

    protected static ?string $navigationLabel = 'Data Usaha KOPINDAG';

    protected static ?string $title = 'Data Usaha Kopindag';

    protected static ?string $slug = 'data-usaha-kopindag';


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Usaha')
                    ->schema([
                        Forms\Components\TextInput::make('nama_usaha')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('alamat_usaha')
                            ->rows(2)
                            ->maxLength(65535)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('jenis_usaha')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('sektor_usaha')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('status_usaha')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('tahun_berdiri')
                            ->numeric(),
                    ])->columns(2),

                Forms\Components\Section::make('Informasi Pemilik')
                    ->schema([
                        Forms\Components\TextInput::make('nama_pemilik')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('nik_pemilik')
                            ->label('NIK Pemilik')
                            ->maxLength(50),
                        Forms\Components\TextInput::make('hp_pemilik')
                            ->label('No. HP Pemilik')
                            ->tel()
                            ->maxLength(50),
                        Forms\Components\Textarea::make('alamat_pemilik')
                            ->rows(2)
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ])->columns(3),

                Forms\Components\Section::make('Lokasi & Survei')
                    ->schema([
                        Forms\Components\Select::make('district_id')
                            ->label('Kecamatan')
                            ->relationship('district', 'name')
                            ->searchable()
                            ->required(),
                        Forms\Components\Select::make('village_id')
                            ->label('Kelurahan')
                            ->relationship('village', 'name')
                            ->searchable()
                            ->required(),
                        Forms\Components\DatePicker::make('tanggal_survei'),
                        Forms\Components\TextInput::make('latitude')
                            ->numeric(),
                        Forms\Components\TextInput::make('longitude')
                            ->numeric(),
                    ])->columns(3),

                Forms\Components\Section::make('Data Finansial & SDM')
                    ->schema([
                        Forms\Components\TextInput::make('tenaga_kerja_l')
                            ->label('Tenaga Kerja (Laki-laki)')
                            ->numeric(),
                        Forms\Components\TextInput::make('tenaga_kerja_p')
                            ->label('Tenaga Kerja (Perempuan)')
                            ->numeric(),
                        Forms\Components\TextInput::make('omset_per_tahun')
                            ->numeric()
                            ->prefix('Rp'),
                        Forms\Components\TextInput::make('modal_usaha')
                            ->numeric()
                            ->prefix('Rp'),
                    ])->columns(2),

                Forms\Components\Section::make('Informasi Lainnya')
                    ->schema([
                        Forms\Components\Textarea::make('perizinan_yang_dimiliki')->rows(3),
                        Forms\Components\Textarea::make('sarana_prasarana')->rows(3),
                        Forms\Components\Textarea::make('kendala_usaha')->rows(3),
                        Forms\Components\Textarea::make('kebutuhan_pelatihan')->rows(3),
                        Forms\Components\Textarea::make('kebutuhan_fasilitasi')->rows(3),
                    ])->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nama_perusahaan')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('nama_user')
                    ->label('Nama Pemilik')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('district.name')->label('Kecamatan')->searchable(),
                Tables\Columns\TextColumn::make('village.name')->label('Kelurahan')->searchable(),
                Tables\Columns\TextColumn::make('jenis_usaha')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('tanggal_survei')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // You can add filters here if needed
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListKopindagBusinesses::route('/'),
            'create' => Pages\CreateKopindagBusiness::route('/create'),
            'edit' => Pages\EditKopindagBusiness::route('/{record}/edit'),
        ];
    }
}
