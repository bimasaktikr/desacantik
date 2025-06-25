<?php

namespace App\Filament\Pages;

use App\Models\Assignment;
use App\Models\District;
use App\Models\User;
use App\Models\Village;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Filters\SelectFilter;
use Filament\Resources\Components\Tab;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Filament\Tables\Columns\BadgeColumn;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\View\View;

class AssignUser extends Page implements HasForms, HasTable
{
    // protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static string $view = 'filament.pages.assign-user';

    protected static ?string $navigationGroup = 'Assignment Petugas';

    protected static ?string $title = 'Wilayah Tugas Petugas';

    use InteractsWithForms;
    use InteractsWithTable;

    public static function shouldRegisterNavigation(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();
        return $user?->hasRole('super_admin') ?? false;
    }

    public function mount(): void
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (!$user?->hasRole('super_admin')) {
            redirect()->route('filament.admin.pages.dashboard');
        }
    }

    public $selectedDistricts;
    public ?array $selectedVillages = [];
    public ?array $selectedUsers = [];
    public $selectedRole = 'Mahasiswa';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(2)
                    ->schema([
                        Select::make('selectedDistricts')
                            ->label('Kecamatan')
                            ->options(District::all()->pluck('name', 'id'))
                            ->reactive()
                            ->afterStateUpdated(function (callable $set) {
                                $set('selectedVillages', []); // Reset villages when district changes
                            }),

                        Select::make('selectedVillages')
                            ->label('Desa')
                            ->multiple()
                            ->options(fn () => Village::where('district_id', $this->selectedDistricts)->pluck('name', 'id'))
                            ->reactive(),
                    ]),

                Select::make('selectedEmployee')
                    ->label('Pilih Supervisor (Employee)')
                    ->options(function() {
                        return User::role('Employee')
                            ->orderBy('name')
                            ->pluck('name', 'id');
                    })
                    ->required(),

                Select::make('selectedStudents')
                    ->label('Pilih Mahasiswa')
                    ->multiple()
                    ->options(function() {
                        return User::role('Mahasiswa')
                            ->orderBy('name')
                            ->pluck('name', 'id');
                    })
                    ->required(),

                Actions::make([
                    Action::make('assign')
                        ->label('Simpan Assignment')
                        ->color('success')
                        ->action('assignUsers')
                ]),
            ]);
    }

    public $selectedEmployee;
    public $selectedStudents = [];

    public function assignUsers()
    {
        // Validasi sederhana
        $this->validate([
            'selectedDistricts' => 'required|exists:districts,id',
            'selectedVillages' => 'required|array',
            'selectedEmployee' => 'required|exists:users,id',
            'selectedStudents' => 'required|array',
        ]);

        DB::beginTransaction();

        try {
            foreach ($this->selectedVillages as $villageId) {
                $village = Village::findOrFail($villageId);

                // Assign supervisor first
                Assignment::updateOrCreate(
                    [
                        'user_id' => $this->selectedEmployee,
                        'area_id' => $village->id,
                        'area_type' => get_class($village),
                    ],
                    []
                );

                // Then assign students
                foreach ($this->selectedStudents as $studentId) {
                    Assignment::updateOrCreate(
                        [
                            'user_id' => $studentId,
                            'area_id' => $village->id,
                            'area_type' => get_class($village),
                        ],
                        []
                    );
                }
            }

            DB::commit();

            $this->reset(['selectedDistricts', 'selectedVillages', 'selectedEmployee', 'selectedStudents']);
            $this->dispatch('notify', ['type' => 'success', 'message' => 'Assignment berhasil disimpan.']);

        } catch (\Exception $e) {
            DB::rollBack();
            logger()->error('Gagal menyimpan assignment', ['error' => $e->getMessage()]);

            $this->dispatch('notify', ['type' => 'error', 'message' => 'Gagal menyimpan assignment.']);
        }
    }

    protected function getTableFilters(): array
    {
        return [
            SelectFilter::make('district')
                ->label('Kecamatan')
                ->form([
                    Select::make('district_id')
                        ->label('Kecamatan')
                        ->options(\App\Models\District::pluck('name', 'id'))
                        ->reactive()
                        ->afterStateUpdated(fn (callable $set) => $set('village_id', null))
                        ->columnSpan(6),
                    Select::make('village_id')
                        ->label('Desa')
                        ->options(function (callable $get) {
                            $districtId = $get('district_id');
                            return $districtId
                                ? \App\Models\Village::where('district_id', $districtId)->pluck('name', 'id')
                                : [];
                        })
                        ->reactive()
                        ->afterStateUpdated(fn (callable $set) => $set('sls_id', null))
                        ->columnSpan(6),
                    Select::make('sls_id')
                        ->label('SLS')
                        ->options(function (callable $get) {
                            $villageId = $get('village_id');
                            return $villageId
                                ? \App\Models\Sls::where('village_id', $villageId)->pluck('name', 'id')
                                : [];
                        })
                        ->columnSpan(6),
                ])
                ->query(function (Builder $query, array $data) {
                    if (!empty($data['district_id'])) {
                        $query->whereHas('district', function ($q) use ($data) {
                            $q->where('id', $data['district_id']);
                        });
                    }
                    if (!empty($data['village_id'])) {
                        $query->where('id', $data['village_id']);
                    }
                    if (!empty($data['sls_id'])) {
                        $query->whereHas('sls', function ($q) use ($data) {
                            $q->where('id', $data['sls_id']);
                        });
                    }
                }),
        ];
    }

    protected function getTableFiltersLayout(): FiltersLayout
    {
        return FiltersLayout::AboveContentCollapsible;
    }

    protected function getTableQuery()
    {
        return Village::query()
            ->with([
                'district',
                'assignments' => function ($query) {
                    $query->with(['user' => function ($query) {
                        $query->with('roles');
                    }]);
                }
            ])
            ->withCount([
                'assignments as employee_count' => function ($query) {
                    $query->whereHas('user.roles', function ($q) {
                        $q->where('name', 'Employee');
                    });
                },
                'assignments as mahasiswa_count' => function ($query) {
                    $query->whereHas('user.roles', function ($q) {
                        $q->where('name', 'Mahasiswa');
                    });
                }
            ]);
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('district.name')
                ->label('Kecamatan')
                ->searchable()
                ->sortable(),
            TextColumn::make('name')
                ->label('Desa')
                ->searchable()
                ->sortable(),
            TextColumn::make('employee_count')
                ->label('Jumlah Supervisor')
                ->badge()
                ->color('success'),
            TextColumn::make('mahasiswa_count')
                ->label('Jumlah Mahasiswa')
                ->badge()
                ->color('warning'),
            TextColumn::make('assigned_supervisors')
                ->label('Supervisor')
                ->listWithLineBreaks()
                ->bulleted()
                ->state(function ($record) {
                    $names = [];
                    foreach ($record->assignments as $assignment) {
                        if ($assignment->user && $assignment->user->roles->contains('name', 'Employee')) {
                            $names[] = "👨‍💼 {$assignment->user->name}";
                        }
                    }
                    return $names;
                }),
            TextColumn::make('assigned_mahasiswa')
                ->label('Mahasiswa')
                ->listWithLineBreaks()
                ->bulleted()
                ->state(function ($record) {
                    $names = [];
                    foreach ($record->assignments as $assignment) {
                        if ($assignment->user && $assignment->user->roles->contains('name', 'Mahasiswa')) {
                            $names[] = "👨‍🎓 {$assignment->user->name}";
                        }
                    }
                    return $names;
                }),
        ];
    }

    protected function getTableGlobalSearchableColumns(): array
    {
        return [
            'user.name',
            'area.name',
            'user.roles.name'
        ];
    }

    protected function getTableGlobalSearchQuery(): Builder
    {
        $search = $this->getTableGlobalSearch();

        return parent::getTableGlobalSearchQuery()
            ->orWhereHas('area', function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhereHas('district', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            });
    }


    protected function getTableContentGrid(): ?array
    {
        return [
            'default' => 1,
        ];
    }

    protected function getTableExpandableContent(): ?View
    {
        return view('filament.pages.assign-student-details', [
            'record' => $this->getRecord(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns($this->getTableColumns())
            ->filters($this->getTableFilters())
            ->filtersLayout(FiltersLayout::AboveContentCollapsible)
            ->striped()
            ->poll('10s')
           ;
    }

}
