<?php

namespace App\Filament\Resources\AuditLog;

use App\Filament\Concerns\WithRole;
use App\Filament\Resources\AuditLog\Pages\ListAuditLog;
use App\Models\AuditLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

/** Jejak audit hanya dibaca. Notaris dan admin TI dapat melihat dan memverifikasi rantai hash. */
class AuditLogResource extends Resource
{
    use WithRole;

    protected static ?string $model = AuditLog::class;

    protected static ?string $slug = 'jejak-audit';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static string|UnitEnum|null $navigationGroup = 'Pengaturan';

    protected static ?int $navigationSort = 90;

    protected static ?string $modelLabel = 'jejak audit';

    protected static ?string $pluralModelLabel = 'Jejak Audit';

    public static function canViewAny(): bool
    {
        return static::canAdminister();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('id', 'desc')
            ->modifyQueryUsing(fn ($query) => $query->with('user'))
            ->columns([
                TextColumn::make('created_at')->label('Waktu')->dateTime('d/m/Y H:i:s')->sortable(),
                TextColumn::make('user.name')->label('Pengguna')->placeholder('Sistem'),
                TextColumn::make('event')->label('Peristiwa')->badge(),
                TextColumn::make('auditable_type')->label('Data')->state(fn (AuditLog $r) => $r->typeLabel()),
                TextColumn::make('label')->label('Keterangan')->searchable()->wrap(),
                TextColumn::make('changes')->label('Perubahan')->wrap()->limit(120)
                    ->state(fn (AuditLog $r) => collect($r->changes ?? [])->map(fn ($v, $k) => $k.': '.(is_array($v) ? json_encode($v, JSON_UNESCAPED_UNICODE) : $v))->implode('; ')),
                TextColumn::make('ip_address')->label('IP')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('event')->label('Peristiwa')->options(fn () => AuditLog::query()->distinct()->pluck('event', 'event')->all()),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListAuditLog::route('/')];
    }
}
