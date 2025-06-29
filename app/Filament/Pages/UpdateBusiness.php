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
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use App\Models\Village;
use App\Models\Sls;
use App\Models\BusinessCategory;
use Illuminate\Support\Facades\Log;
use App\Models\District;

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

            // If user has assignments, filter by them; otherwise show all businesses
            if (!empty($assignedVillageIds)) {
                $query->whereIn('village_id', $assignedVillageIds);
            }
            // If no assignments, show all businesses (for testing purposes)
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
                            ->sortable()
                            ->formatStateUsing(function ($state, $record) {
                                $flag = $record->name_error
                                    ? '<span style="color: #e3342f; vertical-align: middle;" title="Flagged for review"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="inline w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M6 3v18m0 0l6-6m-6 6l6-6m0 0l6 6m-6-6l6-6" /></svg></span> '
                                    : '';
                                return $flag . e($state);
                            })
                            ->html(),
                        TextColumn::make('description')
                            ->label('Description')
                            ->color('gray')
                            ->size('sm')
                            ->wrap()
                            ->limit(50)
                            ->formatStateUsing(function ($state, $record) {
                                $flag = $record->description_error
                                    ? '<span style="color: #e3342f; vertical-align: middle;" title="Flagged for review"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="inline w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M6 3v18m0 0l6-6m-6 6l6-6m0 0l6 6m-6-6l6-6" /></svg></span> '
                                    : '';
                                return $flag . e($state);
                            })
                            ->html(),
                        TextColumn::make('address')
                            ->label('Address')
                            ->color('gray')
                            ->size('sm')
                            ->formatStateUsing(function ($state, $record) {
                                $flag = $record->address_error
                                    ? '<span style="color: #e3342f; vertical-align: middle;" title="Flagged for review"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="inline w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M6 3v18m0 0l6-6m-6 6l6-6m0 0l6 6m-6-6l6-6" /></svg></span> '
                                    : '';
                                return $flag . e($state);
                            })
                            ->html(),
                    ]),

                    Stack::make([
                        TextColumn::make('village.name')
                            ->label('Village')
                            ->sortable(),
                        TextColumn::make('sls.name')
                            ->label('SLS')
                            ->sortable(),
                        TextColumn::make('business_category')
                            ->label('Category')
                            ->getStateUsing(function ($record) {
                                $flag = $record->business_category_id_error
                                    ? '<span style="color: #e3342f; vertical-align: middle;" title="Flagged for review"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="inline w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M6 3v18m0 0l6-6m-6 6l6-6m0 0l6 6m-6-6l6-6" /></svg></span> '
                                    : '';
                                $cat = optional($record->businessCategory)->code . ' - ' . optional($record->businessCategory)->description;
                                return $flag . e($cat);
                            })
                            ->html(),
                    ]),

                    Stack::make([
                        TextColumn::make('status_bangunan')
                            ->label('Building Status')
                            ->badge()
                            ->color('warning')
                            ->formatStateUsing(fn ($state) => $state === 'Tetap' ? 'Tetap (Permanent)' : 'Tidak Tetap (Temporary)')
                            ->sortable(),
                        TextColumn::make('catatan')
                            ->label('Catatan')
                            ->color('gray')
                            ->badge()
                            ->size('sm')
                            ->limit(30),
                        TextColumn::make('pertokoan')
                            ->label('Pertokoan')
                            ->badge()
                            ->color('success')
                            ->formatStateUsing(fn ($state) => $state === 'Ya' ? 'Ya (Termasuk Pertokoan)' : ($state === 'Tidak' ? 'Tidak (Bukan Pertokoan)' : ($state === '-' ? '-' : '- (Not Specified)')))
                            ->sortable(),
                        TextColumn::make('online_status')
                            ->label('Toko Online')
                            ->badge()
                            ->color('info')
                            ->formatStateUsing(fn ($state) => $state === 'Ya' ? 'Ya (Memiliki Toko Online)' : ($state === 'Tidak' ? 'Tidak (Tidak Memiliki Toko Online)' : ($state === '-' ? '-' : '- (Not Specified)')))
                            ->sortable(),
                        TextColumn::make('pembinaan')
                            ->label('Pembinaan')
                            ->badge()
                            ->color('warning')
                            ->formatStateUsing(fn ($state) => $state === 'Ya' ? 'Ya (Mau Mengikuti Pembinaan)' : ($state === 'Tidak' ? 'Tidak (Tidak Mau Mengikuti Pembinaan)' : ($state === '-' ? '-' : '- (Not Specified)')))
                            ->sortable(),
                    ]),

                    Stack::make([
                        TextColumn::make('owner_name')
                            ->label('Nama Pemilik')
                            ->color('primary')
                            ->sortable()
                            ->searchable()
                            ->formatStateUsing(fn ($state) => 'Nama Pemilik: ' . ((!$state || $state === '-') ? '-' : $state)),
                        TextColumn::make('owner_gender')
                            ->label('Jenis Kelamin')
                            ->color('gray')
                            ->sortable()
                            ->formatStateUsing(fn ($state) => 'Jenis Kelamin: ' . ((!$state || $state === '-') ? '-' : $state)),
                        TextColumn::make('owner_age')
                            ->label('Usia')
                            ->sortable()
                            ->formatStateUsing(fn ($state) => 'Usia: ' . ((!$state || $state === '-') ? '-' : $state)),
                        TextColumn::make('phone')
                            ->label('Nomor Handphone')
                            ->icon('heroicon-m-phone')
                            ->sortable(),
                        TextColumn::make('email')
                            ->label('Email')
                            ->icon('heroicon-m-envelope')
                            ->sortable(),
                    ]),
                ])->from('md'),


            ])
            ->actions([
                EditAction::make()
                    ->form([
                        \Filament\Forms\Components\Tabs::make('Business Information')
                            ->tabs([
                                \Filament\Forms\Components\Tabs\Tab::make('Basic Information')
                                    ->schema([
                                        TextInput::make('name')
                                            ->required()
                                            ->maxLength(255)
                                            ->label('Business Name'),
                                        TextInput::make('description')
                                            ->required()
                                            ->maxLength(255)
                                            ->label('Description'),
                                        TextInput::make('address')
                                            ->required()
                                            ->maxLength(255)
                                            ->label('Address'),
                                        Select::make('status_bangunan')
                                            ->label('Building Status')
                                            ->options([
                                                'Tetap' => 'Tetap',
                                                'Tidak Tetap' => 'Tidak Tetap',
                                            ])
                                            ->required(),
                                        Select::make('business_category_id')
                                            ->label('Category')
                                            ->relationship('businessCategory', 'code')
                                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->code . ' - ' . $record->description)
                                            ->required(),
                                        Select::make('sls_id')
                                            ->label('SLS')
                                            ->options(function ($record) {
                                                if (!$record || !$record->village_id) {
                                                    return [];
                                                }

                                                $slsOptions = \App\Models\Sls::where('village_id', $record->village_id)
                                                    ->get()
                                                    ->mapWithKeys(function ($sls) {
                                                        return [$sls->id => $sls->name];
                                                    })
                                                    ->toArray();

                                                // Sort by RW first, then by RT within each RW
                                                uasort($slsOptions, function ($a, $b) {
                                                    // Extract RW and RT numbers from SLS names
                                                    preg_match('/RT\s*(\d+)\s*RW\s*(\d+)/i', $a, $matchesA);
                                                    preg_match('/RT\s*(\d+)\s*RW\s*(\d+)/i', $b, $matchesB);

                                                    if (count($matchesA) >= 3 && count($matchesB) >= 3) {
                                                        $rwA = (int) $matchesA[2];
                                                        $rtA = (int) $matchesA[1];
                                                        $rwB = (int) $matchesB[2];
                                                        $rtB = (int) $matchesB[1];

                                                        // Sort by RW first
                                                        if ($rwA !== $rwB) {
                                                            return $rwA <=> $rwB;
                                                        }

                                                        // If RW is same, sort by RT
                                                        return $rtA <=> $rtB;
                                                    }

                                                    // Fallback to string comparison if pattern doesn't match
                                                    return strcmp($a, $b);
                                                });

                                                return $slsOptions;
                                            })
                                            ->searchable()
                                            ->required(),
                                        TextInput::make('catatan')
                                            ->required()
                                            ->maxLength(255)
                                            ->label('Catatan'),
                                    ]),
                                \Filament\Forms\Components\Tabs\Tab::make('Additional Information')
                                    ->schema([
                                        Select::make('pertokoan')
                                            ->label('Apakah Termasuk Pertokoan?')
                                            ->options([
                                                '-' => '-',
                                                'Ya' => 'Ya',
                                                'Tidak' => 'Tidak',
                                            ])
                                            ->nullable(),
                                        TextInput::make('owner_name')
                                            ->label('Nama Pemilik')
                                            ->maxLength(255)
                                            ->nullable(),
                                        Select::make('owner_gender')
                                            ->label('Gender')
                                            ->options([
                                                '-' => '-',
                                                'Laki-Laki' => 'Laki-Laki',
                                                'Perempuan' => 'Perempuan',
                                            ])
                                            ->nullable(),
                                        TextInput::make('owner_age')
                                            ->label('Age')
                                            ->nullable(),
                                        TextInput::make('phone')
                                            ->label('Nomor Handphone')
                                            ->maxLength(255)
                                            ->nullable(),
                                        TextInput::make('email')
                                            ->label('Email')
                                            ->email()
                                            ->maxLength(255)
                                            ->nullable(),
                                        Select::make('online_status')
                                            ->label('Apakah Memiliki Toko Online?')
                                            ->options([
                                                '-' => '-',
                                                'Ya' => 'Ya',
                                                'Tidak' => 'Tidak',
                                            ])
                                            ->nullable(),
                                        Select::make('pembinaan')
                                            ->label('Apakah mau mengikuti Pembinaan')
                                            ->options([
                                                '-' => '-',
                                                'Ya' => 'Ya',
                                                'Tidak' => 'Tidak',
                                            ])
                                            ->nullable(),
                                        \Filament\Forms\Components\Select::make('certifications')
                                            ->label('Kepemilikan Sertifikat')
                                            ->relationship('certifications', 'name')
                                            ->multiple()
                                            ->preload()
                                            ->searchable()
                                            ->nullable(),
                                    ]),
                            ]),
                    ])
                    ->action(function (array $data, $record) {
                        $certifications = $data['certifications'] ?? [];
                        unset($data['certifications']);
                        $record->update($data);
                        $record->certifications()->sync($certifications);
                    }),
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
            ->striped()
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('district_id')
                    ->label('Kecamatan')
                    ->options(function () {
                        $user = \Illuminate\Support\Facades\Auth::user();
                        if ($user->roles->contains('name', 'super_admin')) {
                            // Superadmin: all districts
                            return \App\Models\District::pluck('name', 'id')->toArray();
                        } else {
                            // Others: assigned villages only, districts derived from those villages
                            $assignedVillageIds = \App\Models\Assignment::where('user_id', $user->id)
                                ->where('area_type', 'App\\Models\\Village')
                                ->pluck('area_id')
                                ->toArray();
                            $districtIds = \App\Models\Village::whereIn('id', $assignedVillageIds)
                                ->pluck('district_id')
                                ->unique()
                                ->toArray();
                            return \App\Models\District::whereIn('id', $districtIds)->pluck('name', 'id')->toArray();
                        }
                    })
                    ->searchable()
                    ->placeholder('Semua Kecamatan')
                    ->query(function ($query, $data) {
                        if (!empty($data['value'])) {
                            $query->whereHas('village.district', function ($q) use ($data) {
                                $q->where('id', $data['value']);
                            });
                        }
                    }),
                \Filament\Tables\Filters\SelectFilter::make('village_id')
                    ->label('Desa')
                    ->options(function () {
                        $user = \Illuminate\Support\Facades\Auth::user();
                        if ($user->roles->contains('name', 'super_admin')) {
                            // Superadmin: all villages
                            return \App\Models\Village::pluck('name', 'id')->toArray();
                        } else {
                            // Others: only assigned villages
                            $assignedVillageIds = \App\Models\Assignment::where('user_id', $user->id)
                                ->where('area_type', 'App\\Models\\Village')
                                ->pluck('area_id')
                                ->toArray();
                            return \App\Models\Village::whereIn('id', $assignedVillageIds)->pluck('name', 'id')->toArray();
                        }
                    })
                    ->searchable()
                    ->placeholder('Semua Desa'),
            ]);
    }
}
