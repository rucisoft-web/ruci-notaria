<?php

namespace App\Filament\Resources\Users;

use App\Enums\Role;
use App\Filament\Concerns\AdminPermissions;
use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class UserResource extends Resource
{
    use AdminPermissions;

    protected static ?string $model = User::class;

    protected static ?string $slug = 'pengguna';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|UnitEnum|null $navigationGroup = 'Pengaturan';

    protected static ?int $navigationSort = 50;

    protected static ?string $modelLabel = 'pengguna';

    protected static ?string $pluralModelLabel = 'Pengguna';

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('name')->label('Nama')->required(),
            TextInput::make('email')->label('Email')->email()->required()->unique(ignoreRecord: true),
            TextInput::make('phone')->label('Telepon'),
            Select::make('role')->label('Peran')->options(Role::class)->required()->default(Role::Staf)
                ->disableOptionWhen(fn (string $value) => $value === Role::Tamu->value || ($value === Role::Notaris->value && ! auth()->user()?->role?->isNotaris()))
                ->helperText('Notaris: semua termasuk penomoran. Staf: berkas & akta. Keuangan: tagihan. Arsiparis: protokol. Admin TI: pengguna & pengaturan, tanpa isi berkas.'),
            TextInput::make('password')->label('Kata sandi')->password()->revealable()->minLength(10)
                ->required(fn (?User $record) => ! $record?->exists)->dehydrated(fn ($state) => filled($state))
                ->helperText(fn (?User $record) => $record?->exists ? 'Kosongkan bila tidak diubah.' : null),
            Toggle::make('is_active')->label('Aktif')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')->label('Nama')->searchable()->weight('bold'),
                TextColumn::make('email')->label('Email')->searchable(),
                TextColumn::make('role')->label('Peran')->badge(),
                IconColumn::make('mfa')->label('MFA')->boolean()->state(fn (User $r) => filled($r->app_authentication_secret)),
                IconColumn::make('is_active')->label('Aktif')->boolean(),
            ])
            ->recordActions([
                EditAction::make()->visible(fn (User $r) => $r->role !== Role::Notaris || auth()->user()?->role?->isNotaris()),
                Action::make('reset_mfa')->label('Reset MFA')->icon(Heroicon::OutlinedKey)->color('gray')->requiresConfirmation()
                    ->visible(fn (User $r) => filled($r->app_authentication_secret))
                    ->action(fn (User $r) => $r->forceFill(['app_authentication_secret' => null, 'app_authentication_recovery_codes' => null])->save()),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageUsers::route('/')];
    }
}
