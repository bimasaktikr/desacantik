<?php

namespace App\Filament\Pages;

use App\Settings\KaidoSetting;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Pages\SettingsPage;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Filament\Notifications\Notification;

class ManageSetting extends SettingsPage
{
    use HasPageShield;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static string $settings = KaidoSetting::class;
    protected static ?string $navigationGroup = 'Settings';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Site Information')->columns(1)->schema([
                    TextInput::make('site_name')
                        ->label('Site Name')
                        ->required(),

                    Toggle::make('site_active')
                        ->label('Site Active (Maintenance Toggle)')
                        ->helperText('Nonaktifkan untuk masuk ke mode pemeliharaan.')
                        ->afterStateUpdated(fn ($state) => $this->handleMaintenanceMode($state)),

                    Toggle::make('registration_enabled')->label('Registration Enabled'),
                    Toggle::make('password_reset_enabled')->label('Password Reset Enabled'),
                    Toggle::make('sso_enabled')->label('SSO Enabled'),
                ]),
            ]);
    }

    protected function handleMaintenanceMode(bool $isActive): void
    {
        if ($isActive) {
            Artisan::call('up');
            Notification::make()
                ->title('Site is Live')
                ->success()
                ->body('Situs telah diaktifkan kembali.')
                ->send();

            Log::info('Site is now live. Activated by user ID: ' . auth()->id());
        } else {
            Artisan::call('down', [
                '--secret' => 'kaido-maintenance-bypass', // optional bypass link
            ]);
            Notification::make()
                ->title('Site in Maintenance Mode')
                ->danger()
                ->body('Situs sedang dalam mode pemeliharaan.')
                ->send();

            Log::warning('Site is now in maintenance mode. Triggered by user ID: ' . auth()->id());
        }
    }
}

