<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use App\Models\Role;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationGroup = 'Administración';
    protected static ?string $navigationLabel = 'Usuarios';
    protected static ?string $pluralModelLabel = 'Usuarios';

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('name')
                ->required()
                ->maxLength(255),

            TextInput::make('email')
                ->email()
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(255),

            TextInput::make('telefono')
                ->maxLength(20)
                ->tel(),

            Textarea::make('direccion')
                ->maxLength(255)
                ->rows(2),

            FileUpload::make('avatar')
                ->image()
                ->imageEditor()
                ->directory('avatars')
                ->maxSize(1024)
                ->label('Foto de perfil')
                ->nullable(),

            Select::make('role_id')
                ->label('Rol')
                ->required()
                ->relationship('role', 'name'),

            Toggle::make('estado')
                ->label('Activo')
                ->default(true),

            TextInput::make('password')
                ->password()
                ->label('Contraseña (dejar vacío para no cambiar)')
                ->maxLength(255)
                ->required(fn(string $context): bool => $context === 'create'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('telefono')
                    ->label('Teléfono'),

                TextColumn::make('role.name')
                    ->label('Rol')
                    ->sortable(),

                ToggleColumn::make('estado')
                    ->label('Activo'),

                TextColumn::make('created_at')
                    ->label('Creado el')
                    ->dateTime()
                    ->sortable(),
            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        $adminRoleId = Role::where('name', 'Admin')->value('id');
        $jefeRoleId = Role::where('name', 'Jefe')->value('id');

        // Si el usuario logueado es Jefe → no mostrar usuarios con rol Admin
        if ($user && $user->role_id === $jefeRoleId) {
            $query->where('role_id', '!=', $adminRoleId);
        }

        return $query;
    }

    public static function canViewAny(): bool
    {
        $user = Auth::user();

        $adminRoleId = Role::where('name', 'Admin')->value('id');
        $jefeRoleId = Role::where('name', 'Jefe')->value('id');

        return $user && ($user->role_id === $adminRoleId || $user->role_id === $jefeRoleId);
    }
}
