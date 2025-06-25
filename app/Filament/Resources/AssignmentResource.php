<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AssignmentResource\Pages;
use App\Filament\Resources\AssignmentResource\RelationManagers;
use App\Models\Assignment;
use App\Models\District;
use App\Models\Village;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Select;

class AssignmentResource extends Resource
{
    protected static ?string $model = Assignment::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Assignment Petugas';

    protected static ?string $slug = 'assignment';

    protected static ?string $title = 'Daftar Assignments';

    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();
        return $user && $user->roles->contains('name', 'super_admin');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('area_type')
                    ->label('Area Type')
                    ->sortable(),
                Tables\Columns\TextColumn::make('area_id')
                    ->label('Area ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('area.name')
                    ->label('Area Name')
                    ->getStateUsing(function ($record) {
                        return $record->area?->name ?? '-';
                    })
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([

                Tables\Filters\SelectFilter::make('area_location')
                    ->label('Location')
                    ->form([
                        Select::make('district_id')
                            ->label('District')
                            ->options(District::pluck('name', 'id'))
                            ->reactive()
                            ->afterStateUpdated(fn (callable $set) => $set('village_id', null)),
                        Select::make('village_id')
                            ->label('Village')
                            ->options(function (callable $get) {
                                $districtId = $get('district_id');
                                return $districtId
                                    ? Village::where('district_id', $districtId)->pluck('name', 'id')
                                    : [];
                            })
                            ->reactive(),
                    ])
                    ->query(function ($query, array $data) {
                        if (!empty($data['district_id'])) {
                            $villageIds = Village::where('district_id', $data['district_id'])->pluck('id')->toArray();
                            $query->where('area_type', 'App\\Models\\Village')
                                  ->whereIn('area_id', $villageIds);
                        }
                        if (!empty($data['village_id'])) {
                            $query->where('area_type', 'App\\Models\\Village')
                                  ->where('area_id', $data['village_id']);
                        }
                    }),
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAssignments::route('/'),
            'create' => Pages\CreateAssignment::route('/create'),
            'edit' => Pages\EditAssignment::route('/{record}/edit'),
        ];
    }
}
