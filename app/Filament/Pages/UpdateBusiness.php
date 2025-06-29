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
use Filament\Tables\Actions\DeleteAction;
use Illuminate\Support\Facades\Activity;

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

        Log::info('User roles', [
            'user_id' => Auth::id(),
            'roles' => Auth::user()?->roles?->pluck('name')->toArray(),
        ]);

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
                                return ($record->name_error ? '🚩 ' : '') . e($state);
                            })
                            ->html(),
                        TextColumn::make('description')
                            ->label('Description')
                            ->color('gray')
                            ->size('sm')
                            ->wrap()
                            ->limit(50)
                            ->formatStateUsing(function ($state, $record) {
                                return ($record->description_error ? '🚩 ' : '') . e($state);
                            })
                            ->html(),
                        TextColumn::make('address')
                            ->label('Address')
                            ->color('gray')
                            ->size('sm')
                            ->formatStateUsing(function ($state, $record) {
                                return ($record->address_error ? '🚩 ' : '') . e($state);
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
                                $cat = optional($record->businessCategory)->code . ' - ' . optional($record->businessCategory)->description;
                                return ($record->business_category_id_error ? '🚩 ' : '') . e($cat);
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
                            ->limit(30)
                            ->formatStateUsing(function ($state, $record) {
                                return ($record->catatan_error ? '🚩 ' : '') . e($state);
                            })
                            ->html(),
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
                                            ->label(fn($record) => ($record->name_error ? '🚩 ' : '') . 'Business Name'),
                                        TextInput::make('description')
                                            ->required()
                                            ->maxLength(255)
                                            ->label(fn($record) => ($record->description_error ? '🚩 ' : '') . 'Description'),
                                        TextInput::make('address')
                                            ->required()
                                            ->maxLength(255)
                                            ->label(fn($record) => ($record->address_error ? '🚩 ' : '') . 'Address'),
                                        Select::make('status_bangunan')
                                            ->label(fn($record) => ($record->status_bangunan_error ? '🚩 ' : '') . 'Building Status')
                                            ->options([
                                                'Tetap' => 'Tetap',
                                                'Tidak Tetap' => 'Tidak Tetap',
                                            ])
                                            ->required(),
                                        Select::make('business_category_id')
                                            ->label(fn($record) => ($record->business_category_id_error ? '🚩 ' : '') . 'Category')
                                            ->relationship('businessCategory', 'code')
                                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->code . ' - ' . $record->description)
                                            ->required(),
                                        Select::make('sls_id')
                                            ->label(fn($record) => ($record->sls_id_error ? '🚩 ' : '') . 'SLS')
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
                                                uasort($slsOptions, function ($a, $b) {
                                                    preg_match('/RT\s*(\d+)\s*RW\s*(\d+)/i', $a, $matchesA);
                                                    preg_match('/RT\s*(\d+)\s*RW\s*(\d+)/i', $b, $matchesB);
                                                    if (count($matchesA) >= 3 && count($matchesB) >= 3) {
                                                        $rwA = (int) $matchesA[2];
                                                        $rtA = (int) $matchesA[1];
                                                        $rwB = (int) $matchesB[2];
                                                        $rtB = (int) $matchesB[1];
                                                        if ($rwA !== $rwB) {
                                                            return $rwA <=> $rwB;
                                                        }
                                                        return $rtA <=> $rtB;
                                                    }
                                                    return strcmp($a, $b);
                                                });
                                                return $slsOptions;
                                            })
                                            ->searchable()
                                            ->required(),
                                        TextInput::make('catatan')
                                            ->required()
                                            ->maxLength(255)
                                            ->label(fn($record) => ($record->catatan_error ? '🚩 ' : '') . 'Catatan'),
                                    ]),
                                \Filament\Forms\Components\Tabs\Tab::make('Additional Information')
                                    ->schema([
                                        Select::make('pertokoan')
                                            ->label(fn($record) => ($record->pertokoan_error ? '🚩 ' : '') . 'Apakah Termasuk Pertokoan?')
                                            ->options([
                                                '-' => '-',
                                                'Ya' => 'Ya',
                                                'Tidak' => 'Tidak',
                                            ])
                                            ->nullable(),
                                        TextInput::make('owner_name')
                                            ->label(fn($record) => ($record->owner_name_error ? '🚩 ' : '') . 'Nama Pemilik')
                                            ->maxLength(255)
                                            ->nullable(),
                                        Select::make('owner_gender')
                                            ->label(fn($record) => ($record->owner_gender_error ? '🚩 ' : '') . 'Gender')
                                            ->options([
                                                '-' => '-',
                                                'Laki-Laki' => 'Laki-Laki',
                                                'Perempuan' => 'Perempuan',
                                            ])
                                            ->nullable(),
                                        TextInput::make('owner_age')
                                            ->label(fn($record) => ($record->owner_age_error ? '🚩 ' : '') . 'Age')
                                            ->nullable(),
                                        TextInput::make('phone')
                                            ->label(fn($record) => ($record->phone_error ? '🚩 ' : '') . 'Nomor Handphone')
                                            ->maxLength(255)
                                            ->nullable(),
                                        TextInput::make('email')
                                            ->label(fn($record) => ($record->email_error ? '🚩 ' : '') . 'Email')
                                            ->email()
                                            ->maxLength(255)
                                            ->nullable(),
                                        Select::make('online_status')
                                            ->label(fn($record) => ($record->online_status_error ? '🚩 ' : '') . 'Apakah Memiliki Toko Online?')
                                            ->options([
                                                '-' => '-',
                                                'Ya' => 'Ya',
                                                'Tidak' => 'Tidak',
                                            ])
                                            ->nullable(),
                                        Select::make('pembinaan')
                                            ->label(fn($record) => ($record->pembinaan_error ? '🚩 ' : '') . 'Apakah mau mengikuti Pembinaan')
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
                    })
                    ->visible(function ($record) {
                        $user = \Illuminate\Support\Facades\Auth::user();
                        if ($record->user_id === $user->id) {
                            return true;
                        }
                        if ($user->roles->contains('name', 'Employee')) {
                            $assignedVillageIds = \App\Models\Assignment::where('user_id', $user->id)
                                ->where('area_type', 'App\\Models\\Village')
                                ->pluck('area_id')
                                ->toArray();
                            return in_array($record->village_id, $assignedVillageIds);
                        }
                        return false;
                    }),
                DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('Are you sure you want to delete this business?')
                    ->modalDescription('This action cannot be undone.')
                    ->visible(function ($record) {
                        $user = \Illuminate\Support\Facades\Auth::user();
                        if ($record->user_id === $user->id) {
                            return true;
                        }
                        if ($user->roles->contains('name', 'Employee')) {
                            $assignedVillageIds = \App\Models\Assignment::where('user_id', $user->id)
                                ->where('area_type', 'App\\Models\\Village')
                                ->pluck('area_id')
                                ->toArray();
                            return in_array($record->village_id, $assignedVillageIds);
                        }
                        return false;
                    }),
                Action::make('flag')
                    ->label('Flag Fields')
                    ->icon('heroicon-o-flag')
                    ->color('warning')
                    ->form([
                        \Filament\Forms\Components\Tabs::make('Flag Fields')
                            ->tabs([
                                \Filament\Forms\Components\Tabs\Tab::make('Basic Information')
                                    ->schema([
                                        Section::make('Business Name')
                                            ->description(fn($record) => $record->name)
                                            ->aside()
                                            ->schema([
                                                Toggle::make('name_error')->label('Flag Business Name')->default(fn($record) => (bool) $record->name_error),
                                            ]),
                                        Section::make('Description')
                                            ->description(fn($record) => $record->description)
                                            ->aside()
                                            ->schema([
                                                Toggle::make('description_error')->label('Flag Description')->default(fn($record) => (bool) $record->description_error),
                                            ]),
                                        Section::make('Address')
                                            ->description(fn($record) => $record->address)
                                            ->aside()
                                            ->schema([
                                                Toggle::make('address_error')->label('Flag Address')->default(fn($record) => (bool) $record->address_error),
                                            ]),
                                        Section::make('Village')
                                            ->description(fn($record) => optional($record->village)->name)
                                            ->aside()
                                            ->schema([
                                                Toggle::make('village_id_error')->label('Flag Village')->default(fn($record) => (bool) $record->village_id_error),
                                            ]),
                                        Section::make('SLS')
                                            ->description(fn($record) => optional($record->sls)->name)
                                            ->aside()
                                            ->schema([
                                                Toggle::make('sls_id_error')->label('Flag SLS')->default(fn($record) => (bool) $record->sls_id_error),
                                            ]),
                                        Section::make('Building Status')
                                            ->description(fn($record) => $record->status_bangunan)
                                            ->aside()
                                            ->schema([
                                                Toggle::make('status_bangunan_error')->label('Flag Building Status')->default(fn($record) => (bool) $record->status_bangunan_error),
                                            ]),
                                        Section::make('Category')
                                            ->description(fn($record) => optional($record->businessCategory)->code . ' - ' . optional($record->businessCategory)->description)
                                            ->aside()
                                            ->schema([
                                                Toggle::make('business_category_id_error')->label('Flag Category')->default(fn($record) => (bool) $record->business_category_id_error),
                                            ]),
                                        Section::make('Catatan')
                                            ->description(fn($record) => $record->catatan)
                                            ->aside()
                                            ->schema([
                                                Toggle::make('catatan_error')->label('Flag Catatan')->default(fn($record) => (bool) $record->catatan_error),
                                            ]),
                                    ]),
                                \Filament\Forms\Components\Tabs\Tab::make('Additional Information')
                                    ->schema([
                                        Section::make('Pertokoan')
                                            ->description(fn($record) => $record->pertokoan)
                                            ->aside()
                                            ->schema([
                                                Toggle::make('pertokoan_error')->label('Flag Pertokoan')->default(fn($record) => (bool) $record->pertokoan_error),
                                            ]),
                                        Section::make('Owner Name')
                                            ->description(fn($record) => $record->owner_name)
                                            ->aside()
                                            ->schema([
                                                Toggle::make('owner_name_error')->label('Flag Owner Name')->default(fn($record) => (bool) $record->owner_name_error),
                                            ]),
                                        Section::make('Owner Gender')
                                            ->description(fn($record) => $record->owner_gender)
                                            ->aside()
                                            ->schema([
                                                Toggle::make('owner_gender_error')->label('Flag Owner Gender')->default(fn($record) => (bool) $record->owner_gender_error),
                                            ]),
                                        Section::make('Owner Age')
                                            ->description(fn($record) => $record->owner_age)
                                            ->aside()
                                            ->schema([
                                                Toggle::make('owner_age_error')->label('Flag Owner Age')->default(fn($record) => (bool) $record->owner_age_error),
                                            ]),
                                        Section::make('Phone')
                                            ->description(fn($record) => $record->phone)
                                            ->aside()
                                            ->schema([
                                                Toggle::make('phone_error')->label('Flag Phone')->default(fn($record) => (bool) $record->phone_error),
                                            ]),
                                        Section::make('Email')
                                            ->description(fn($record) => $record->email)
                                            ->aside()
                                            ->schema([
                                                Toggle::make('email_error')->label('Flag Email')->default(fn($record) => (bool) $record->email_error),
                                            ]),
                                        Section::make('Online Status')
                                            ->description(fn($record) => $record->online_status)
                                            ->aside()
                                            ->schema([
                                                Toggle::make('online_status_error')->label('Flag Online Status')->default(fn($record) => (bool) $record->online_status_error),
                                            ]),
                                        Section::make('Pembinaan')
                                            ->description(fn($record) => $record->pembinaan)
                                            ->aside()
                                            ->schema([
                                                Toggle::make('pembinaan_error')->label('Flag Pembinaan')->default(fn($record) => (bool) $record->pembinaan_error),
                                            ]),
                                        Section::make('User')
                                            ->description(fn($record) => optional($record->user)->name)
                                            ->aside()
                                            ->schema([
                                                Toggle::make('user_id_error')->label('Flag User')->default(fn($record) => (bool) $record->user_id_error),
                                            ]),
                                    ]),
                            ]),
                    ])
                    ->modalWidth('2xl')
                    ->visible(fn () => \Illuminate\Support\Facades\Auth::user()->roles->contains('name', 'Employee'))
                    ->action(function (array $data, $record) {
                        $fields = [
                            'name_error',
                            'description_error',
                            'address_error',
                            'village_id_error',
                            'sls_id_error',
                            'status_bangunan_error',
                            'business_category_id_error',
                            'catatan_error',
                            'pertokoan_error',
                            'owner_name_error',
                            'owner_gender_error',
                            'owner_age_error',
                            'phone_error',
                            'email_error',
                            'online_status_error',
                            'pembinaan_error',
                            'user_id_error',
                        ];
                        foreach ($fields as $field) {
                            if (!array_key_exists($field, $data)) {
                                $data[$field] = false;
                            }
                        }
                        $record->update($data);
                        activity()
                            ->performedOn($record)
                            ->causedBy(\Illuminate\Support\Facades\Auth::user())
                            ->withProperties(['attributes' => $data])
                            ->log('Flag fields updated');
                    }),
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
