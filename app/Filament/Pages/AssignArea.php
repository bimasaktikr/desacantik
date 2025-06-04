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

class AssignArea extends Page implements HasForms, HasTable
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.assign-area';

    protected static ?string $navigationGroup = 'Pendataan';

    protected static ?string $title = 'Assignment Petugas';

    use InteractsWithForms;
    use InteractsWithTable;

    public static function shouldRegisterNavigation(): bool
    {
        /** @var User|null $user */
        $user = auth()->user();
        return $user?->hasRole('super_admin') ?? false;
    }

    public function mount(): void
    {
        /** @var User|null $user */
        $user = auth()->user();
        if (!$user?->hasRole('super_admin')) {
            redirect()->route('filament.admin.pages.dashboard');
        }
    }

    public $selectedDistricts;
    public ?array $selectedVillages = [];
    public ?array $selectedUsers = [];

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

                Select::make('selectedUsers')
                    ->label('Pilih User')
                    ->multiple()
                    ->options(function() {
                        return User::role('Mahasiswa')
                            ->orderBy('name')
                            ->pluck('name', 'id');
                    }),

                Actions::make([
                    Action::make('assign')
                        ->label('Simpan Assignment')
                        ->color('success')
                        ->action('assignUsers')
                    ]),
            ]);
    }


    public function assignUsers()
    {
        // Validasi sederhana
        $this->validate([
            'selectedDistricts' => 'required|exists:districts,id',
            'selectedVillages' => 'required|array',
            'selectedUsers' => 'required|array',
        ]);

        DB::beginTransaction();

        try {
            foreach ($this->selectedVillages as $villageId) {
                $village = Village::findOrFail($villageId);

                foreach ($this->selectedUsers as $userId) {
                    Assignment::updateOrCreate(
                        [
                            'user_id' => $userId,
                            'area_id' => $village->id,
                            'area_type' => get_class($village),
                        ],
                        []
                    );
                }
            }

            DB::commit();

            $this->reset(['selectedDistricts', 'selectedVillages', 'selectedUsers']);
            $this->dispatch('notify', ['type' => 'success', 'message' => 'Assignment berhasil disimpan.']);

        } catch (\Exception $e) {
            DB::rollBack();
            logger()->error('Gagal menyimpan assignment', ['error' => $e->getMessage()]);

            $this->dispatch('notify', ['type' => 'error', 'message' => 'Gagal menyimpan assignment.']);
        }
    }

    protected function getTableQuery()
    {
        return Assignment::query()->with(['user', 'area']);
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('user.name')->label('User'),
            TextColumn::make('area.district')
                ->label('Kecamatan')
                ->formatStateUsing(fn ($state) => $state->name),
            TextColumn::make('area.name')->label('Wilayah (Desa)'),
            TextColumn::make('area_type')->label('Tipe')->formatStateUsing(fn ($state) => class_basename($state)),
        ];
    }


}
