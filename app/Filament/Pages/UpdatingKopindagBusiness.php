<?php

namespace App\Filament\Pages;

use App\Models\KopindagBusiness;
use App\Models\Assignment;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Columns\Layout\Split;
use Illuminate\Support\Facades\Log;

class UpdatingKopindagBusiness extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-pencil-square';
    protected static ?string $navigationLabel = 'Update Data KOPINDAG';
    protected static ?string $title = 'Update Data KOPINDAG';
    protected static string $view = 'filament.pages.updating-kopindag-business';
    protected static ?string $navigationGroup = 'Pendataan';

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasRole('super_admin');
    }


    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->query($this->getUserKopindagBusinessesQuery())
            ->columns([
                Split::make([
                    Tables\Columns\TextColumn::make('nib')
                        ->label('NIB')
                        ->searchable(),
                    Tables\Columns\TextColumn::make('nama_perusahaan')
                        ->label('Nama Perusahaan')
                        ->searchable(),
                    Tables\Columns\TextColumn::make('tanggal_terbit_oss')
                        ->label('Tanggal Terbit OSS')
                        ->date(),
                    Tables\Columns\TextColumn::make('alamat_usaha')
                        ->label('Alamat Usaha')
                        ->searchable(),
                    Tables\Columns\TextColumn::make('sls.name')
                        ->label('SLS')
                        ->placeholder(fn($record) => $record->sls_id),
                ])->from('md'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->form([
                        \Filament\Forms\Components\Select::make('sls_id')
                            ->label('SLS')
                            ->options(function ($record) {
                                if (!$record) return [];
                                return \App\Models\Sls::where('village_id', $record->village_id)
                                    ->pluck('name', 'id');
                            })
                            ->searchable()
                            ->required(),
                    ])
                    ->after(function ($record, $data) {
                        $userId = auth()->id();
                        $oldSlsId = $record->getOriginal('sls_id');
                        $newSlsId = $data['sls_id'] ?? null;
                        Log::info('KopindagBusiness SLS updated', [
                            'user_id' => $userId,
                            'kopindag_business_id' => $record->id,
                            'old_sls_id' => $oldSlsId,
                            'new_sls_id' => $newSlsId,
                        ]);
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('village_id')
                    ->label('Village')
                    ->options(function () {
                        $user = auth()->user();
                        $villageIds = \App\Models\Assignment::where('user_id', $user->id)
                            ->whereNotNull('area_id')
                            ->pluck('area_id');
                        return \App\Models\Village::whereIn('id', $villageIds)
                            ->pluck('name', 'id');
                    })
                    ->searchable(),
            ]);
    }

    protected function getUserKopindagBusinessesQuery(): Builder
    {
        $user = auth()->user();
        // Get all village_ids from the user's assignments
        $villageIds = \App\Models\Assignment::where('user_id', $user->id)
            ->whereNotNull('area_id')
            ->pluck('area_id');
        return KopindagBusiness::query()->whereIn('village_id', $villageIds);
    }
}
