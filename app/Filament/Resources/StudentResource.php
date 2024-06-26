<?php

namespace App\Filament\Resources;

use App\Events\PromoteStudent;
use App\Filament\Resources\StudentResource\Pages;
use App\Filament\Resources\StudentResource\RelationManagers;
use App\Filament\Resources\StudentResource\RelationManagers\GuardiansRelationManager;
use App\Models\Certificate;
use App\Models\Student;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\GlobalSearch\Actions\Action;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;

class StudentResource extends Resource
{
    protected static ?string $model = Student::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Wizard::make([
                    Forms\Components\Wizard\Step::make('Personal Information')
                        ->schema([
                            Forms\Components\TextInput::make('name')
                                ->required()
                                ->minLength(3),
                            Forms\Components\TextInput::make('student_id')
                                ->required(),
                        ])
                        ->description('Enter the student\'s personal information')
                        ->icon('heroicon-o-users'),
                    Forms\Components\Wizard\Step::make('Address')
                        ->schema([
                            Forms\Components\TextInput::make('address_1')
                                ->required(),
                            Forms\Components\TextInput::make('address_2'),
                        ])
                        ->description('Add the student\'s address information')
                        ->icon('heroicon-o-home'),
                    Forms\Components\Wizard\Step::make('School')
                        ->schema([
                            Forms\Components\Select::make('standard_id')
                                ->required()
                                ->relationship('standard', 'name'),
                        ])
                        ->icon('heroicon-o-academic-cap'),
                ])
                ->columnSpan('full')
                ->skippable(),

                Forms\Components\Section::make('Certificates')
                    ->description('Add student certificate information')
                    ->schema ([
                        Forms\Components\Repeater::make('certificates')
                            ->relationship()
                            ->schema([
                                Forms\Components\Select::make('certificate_id')
                                    ->options(Certificate::all()->pluck('name', 'id')),
                                Forms\Components\TextInput::make('description')
                            ])
                            ->columns(2),
                    ]),

                Forms\Components\Section::make('Vitals')
                    ->schema([
                        Forms\Components\Repeater::make('vitals')
                            ->schema([
                                Forms\Components\Select::make('name')
                                    ->options([
                                        'height' => 'Height',
                                        'weight' => 'Weight',
                                        'blood_type' => 'Blood Type',
                                    ])
                                    ->required(),
                                Forms\Components\TextInput::make('value')
                                    ->required(),
                            ])
                            ->columns(2)
                    ])
                    ->description('Add the student\'s vitals')
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('standard.name')
            ])
            ->filters([
                Tables\Filters\Filter::make('Start')
                    ->query(fn (Builder $query): Builder => $query->where('standard_id',1)),
                Tables\Filters\SelectFilter::make('standard_id')
                    ->options([
                        1 => 'Standard 1',
                        2 => 'Standard 2',
                        3 => 'Standard 3',
                        4 => 'Standard 4',
                        5 => 'Standard 5',
                    ])
                    ->label('Standard'),
                Tables\Filters\SelectFilter::make('All standards')
                    ->relationship('standard', 'name'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('Promote')
                        ->action(fn (Student $student) => $student->update(['standard_id' => $student->standard_id + 1]))
                        ->visible(fn (Student $student) => $student->standard_id < 5)
                        ->color('success')
                        ->requiresConfirmation(),
                    Tables\Actions\Action::make('Demote')
                        ->action(function (Student $student) {
                            if ($student->standard_id > 1) {
                                $student->update(['standard_id' => $student->standard_id - 1]);
                            }
                        })
                        ->visible(fn (Student $student) => $student->standard_id > 1)
                        ->color('danger')
                        ->requiresConfirmation(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('Promote all')
                        ->action(function (Collection $records) {
                            $records->each(function (Student $student) {
//                                $student->update(['standard_id' => $student->standard_id + 1]);
                                event(new PromoteStudent($student));
                            });
                        })
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            GuardiansRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStudents::route('/'),
            'create' => Pages\CreateStudent::route('/create'),
            'edit' => Pages\EditStudent::route('/{record}/edit'),
        ];
    }

    public static function getGlobalSearchResultTitle(Model $record): string|Htmlable
    {
        return 'Search result: ' .$record->name;
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Name' => $record->name,
            'Standard' => $record->standard->name,
        ];
    }

    public static function getGlobalSearchResultActions(Model $record): array
    {
        return [
            Action::make('Edit')
                ->icon('heroicon-o-pencil')
                ->url(static::getUrl('edit', ['record' => $record])),
            Action::make('Delete')
                ->icon('heroicon-o-trash')
                ->url(static::getUrl('edit', ['record' => $record])),
        ];
    }
}
