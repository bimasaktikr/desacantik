<?php

namespace App\Filament\Pages;

use App\Models\Assignment;
use App\Models\AssignmentUpload;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Support\Facades\Auth;
use Livewire\Features\SupportFileUploads\WithFileUploads;
use Filament\Notifications\Notification;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use App\Jobs\BusinessImportJob;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Storage;
use App\Imports\BusinessesImport;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Filament\Tables\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Grid as InfolistGrid;
use Filament\Infolists\Components\Section as InfolistSection;

class AssignmentUser extends Page implements HasTable, HasForms
{
    use \Filament\Forms\Concerns\InteractsWithForms;
    use \Filament\Tables\Concerns\InteractsWithTable;
    use WithFileUploads;

    // protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static string $view = 'filament.pages.assignment-user';
    protected static ?string $navigationGroup = 'Pendataan';
    protected static ?string $title = 'Assignment Petugas';
    protected static ?string $navigationLabel = 'Unggah Data Usaha';
    protected static ?string $slug = 'upload-assignment';

    public ?int $selectedAssignment = null;
    public $file = null;
    protected $isImporting = false;


    public function mount(): void
    {
        $this->form->fill();
    }

    public static function shouldRegisterNavigation(): bool
    {
        /** @var User|null $user */
        $user = auth()->user();
        return $user?->hasRole('Mahasiswa') || $user?->hasRole('super_admin') ?? false;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Unggah Data Usaha')
                    ->description('Upload data usaha untuk petugas')
                    ->columns(1)
                    ->collapsible()
                    ->schema([
                        Select::make('selectedAssignment')
                            ->label('Pilih Assignment')
                            ->options(
                                Assignment::where('user_id', Auth::id())->get()->mapWithKeys(fn ($a) => [
                                    $a->id => optional($a->area)->name . ' (' . class_basename($a->area_type) . ')'
                                ])
                            )
                            ->required()
                            ->live()
                            ->afterStateUpdated(function () {
                                $this->refreshTable();
                            }),

                        FileUpload::make('file')
                            ->label('Upload Excel')
                            ->disk('public')
                            ->directory('assignment-uploads')
                            ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel'])
                            ->required()
                            ->getUploadedFileNameForStorageUsing(
                                function (TemporaryUploadedFile $file): string {
                                    $user = Auth::user();
                                    $assignment = null;

                                    if ($this->selectedAssignment) {
                                        $assignment = Assignment::with('area')->find($this->selectedAssignment);
                                    }

                                    $prefix = '';
                                    if ($assignment && $assignment->area) {
                                        $prefix .= 'area-' . $assignment->area->id . '_';
                                    }
                                    if ($user) {
                                        $prefix .= 'user-' . str($user->name)->slug() . '_';
                                    }
                                    $prefix .= date('Y-m-d_H-i-s') . '_';

                                    $extension = $file->getClientOriginalExtension();
                                    $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

                                    return $prefix . str($originalName)->slug() . '.' . $extension;
                                }
                            ),

                        Actions::make([
                            Action::make('upload')
                                ->label('Upload')
                                ->action('saveUpload')
                                ->disabled(fn () => $this->isImporting)
                                ->color('success')
                                ->requiresConfirmation(),
                        ]),
                    ])
            ]);
    }

    public function saveUpload()
    {
        try {
            $data = $this->form->getState();

            if (!$data['file']) {
                throw new \Exception('File tidak ditemukan. Silakan pilih file Excel untuk diupload.');
            }

            $upload = AssignmentUpload::create([
                'assignment_id' => $data['selectedAssignment'],
                'user_id' => Auth::id(),
                'file_path' => $data['file'],
                'import_status' => 'pending'
            ]);

            BusinessImportJob::dispatch($upload);

            Notification::make()
                ->title('File berhasil diupload')
                ->body('Data akan diproses dalam background')
                ->success()
                ->send();

            $this->form->fill();
            $this->file = null;
            $this->refreshTable();

        } catch (\Exception $e) {
            Log::error('Error uploading file: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'assignment_id' => $this->selectedAssignment,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            Notification::make()
                ->title('Gagal mengupload file')
                ->body('Terjadi kesalahan: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function getTableQuery()
    {
        $user = Auth::user();
        if ($user->roles->contains('name', 'super_admin')) {
            // Super admin: show all assignment uploads
            return \App\Models\AssignmentUpload::query();
        }
        // For other users, use the default or existing filtering logic
        return AssignmentUpload::query()
            ->where('user_id', Auth::id())
            ->with(['assignment.area'])
            ->when($this->selectedAssignment, fn ($q) =>
                $q->where('assignment_id', $this->selectedAssignment)
            )
            ->latest();
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('assignment.area.name')
                ->label('Wilayah')
                ->sortable(),
            TextColumn::make('created_at')
                ->label('Tanggal Upload')
                ->dateTime('d M Y H:i')
                ->sortable(),
            TextColumn::make('import_status')
                ->label('Status')
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'pending' => 'gray',
                    'processing' => 'warning',
                    'berhasil' => 'success',
                    'gagal' => 'danger',
                    default => 'gray',
                }),
            TextColumn::make('total_rows')
                ->label('Total Data')
                ->numeric(),
            TextColumn::make('success_rows')
                ->label('Berhasil')
                ->numeric(),
            TextColumn::make('failed_rows')
                ->label('Gagal')
                ->numeric(),
            TextColumn::make('error_message')
                ->wrap()
                ->limit(100)
                ->label('Pesan Error'),

        ];
    }

    public function refreshTable(): void
    {
        $this->resetTable();
    }

    protected function getTableActions(): array
    {
        return [
            ViewAction::make()
                ->modalHeading('Detail Assignment User')
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Close')
                ->infolist([
                    InfolistGrid::make(2)
                        ->schema([
                            TextEntry::make('id')->label('ID'),
                            TextEntry::make('user.name')->label('User'),

                        ]),
                    InfolistSection::make('Error Details')
                        ->collapsible()
                        ->schema([
                            TextEntry::make('error_message')->label('Pesan Error'),
                        ]),
                ]),
        ];
    }
}
