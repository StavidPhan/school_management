<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StudentResource\Pages;
use App\Filament\Resources\StudentResource\RelationManagers;
use App\Models\Student;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class StudentResource extends Resource
{
    protected static ?string $model = Student::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->minLength(3),
                Forms\Components\TextInput::make('student_id')
                    ->required(),
                Forms\Components\TextInput::make('address_1')
                    ->required(),
                Forms\Components\TextInput::make('address_2'),
                Forms\Components\Select::make('standard_id')
                    ->required()
                    ->relationship('standard', 'name'),
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
            'index' => Pages\ListStudents::route('/'),
            'create' => Pages\CreateStudent::route('/create'),
            'edit' => Pages\EditStudent::route('/{record}/edit'),
        ];
    }
}
