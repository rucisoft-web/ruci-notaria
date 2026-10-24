<?php

namespace App\Filament\Resources\Tenggat;

use App\Filament\Concerns\WithRole;
use App\Filament\Resources\Akta\AktaResource;
use App\Filament\Resources\Berkas\BerkasResource;
use App\Filament\Resources\Tenggat\Pages\ManageTenggat;
use App\Models\Tenggat;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

/** Kalender tenggat hukum: dibuat otomatis dari akta bernomor dan setiap awal bulan. */
class TenggatResource extends Resource
{
    use WithRole;

    protected static ?string $model = Tenggat::class;

    protected static ?string $slug = 'tenggat';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static string|UnitEnum|null $navigationGroup = 'Kerja Harian';

    protected static ?int $navigationSort = 50;

    protected static ?string $modelLabel = 'tenggat';

    protected static ?string $pluralModelLabel = 'Tenggat';

    public static function getNavigationBadge(): ?string
    {
        $n = Tenggat::query()->terbuka()->whereDate('jatuh_tempo', '<=', today()->addDays(7))->count();

        return $n ? (string) $n : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return Tenggat::query()->terlambat()->exists() ? 'danger' : 'warning';
    }

    public static function getEloquentQuery(): Builder
    {
        // Tenggat yang menempel pada berkas rahasia mengikuti hak akses berkasnya.
        return parent::getEloquentQuery()->where(fn (Builder $q) => $q->whereNull('berkas_id')
            ->orWhereHas('berkas', fn (Builder $b) => $b->terlihatOleh(static::user())));
    }

    public static function canViewAny(): bool
    {
        return static::canViewFiles();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function sisaHari(Tenggat $r): ?string
    {
        if ($r->selesai_at) {
            return null;
        }
        $hari = (int) today()->diffInDays($r->jatuh_tempo, false);

        return match (true) {
            $hari < 0 => 'terlambat '.abs($hari).' hari',
            $hari === 0 => 'hari ini',
            default => 'H-'.$hari,
        };
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('jatuh_tempo')
            ->columns([
                TextColumn::make('jatuh_tempo')->label('Jatuh tempo')->date('d/m/Y')->sortable()
                    ->description(fn (Tenggat $r) => static::sisaHari($r))
                    ->color(fn (Tenggat $r) => match ($r->status()) {
                        'terlambat' => 'danger', 'segera' => 'warning', 'selesai' => 'success', default => null
                    }),
                TextColumn::make('kode')->label('Kode')->badge()->tooltip(fn (Tenggat $r) => config("notaria.tenggat_akta.{$r->kode}.dasar") ?? config("notaria.tenggat_bulanan.{$r->kode}.dasar")),
                TextColumn::make('judul')->label('Tenggat')->wrap()->searchable(),
                TextColumn::make('berkas.nomor')->label('Berkas')->placeholder('—')
                    ->url(fn (Tenggat $r) => $r->berkas_id ? BerkasResource::getUrl('view', ['record' => $r->berkas_id]) : null),
                TextColumn::make('akta.nomor_bulanan')->label('Akta')->prefix('No. ')->placeholder('—')
                    ->url(fn (Tenggat $r) => $r->akta_id ? AktaResource::getUrl('view', ['record' => $r->akta_id]) : null),
                TextColumn::make('selesai_at')->label('Selesai')->dateTime('d/m/Y')->placeholder('—')
                    ->description(fn (Tenggat $r) => $r->selesaiOleh?->name),
                TextColumn::make('catatan')->label('Catatan')->placeholder('—')->wrap()->toggleable(),
            ])
            ->filters([
                TernaryFilter::make('selesai')->label('Status')->placeholder('Semua')->trueLabel('Selesai')->falseLabel('Terbuka')->default(false)
                    ->queries(true: fn (Builder $query) => $query->whereNotNull('selesai_at'), false: fn (Builder $query) => $query->whereNull('selesai_at')),
                SelectFilter::make('kode')->label('Kode')->options(fn () => collect(config('notaria.tenggat_akta'))->merge(config('notaria.tenggat_bulanan'))
                    ->map(fn ($a, $k) => $k.' — '.$a['judul'])->all()),
            ])
            ->recordActions([
                Action::make('selesai')->label('Tandai selesai')->icon(Heroicon::OutlinedCheck)->color('success')
                    ->visible(fn (Tenggat $r) => ! $r->selesai_at && (static::canWork() || static::canArchive()))
                    ->schema([Textarea::make('catatan')->label('Catatan (misal nomor tanda terima, nomor SK, NTPN)')->rows(2)])
                    ->action(fn (Tenggat $r, array $data) => $r->update(['selesai_at' => now(), 'selesai_oleh' => auth()->id(), 'catatan' => $data['catatan'] ?? null])),
                Action::make('buka_lagi')->label('Buka lagi')->icon(Heroicon::OutlinedArrowUturnLeft)->color('gray')
                    ->visible(fn (Tenggat $r) => $r->selesai_at && static::isNotaris())
                    ->action(fn (Tenggat $r) => $r->update(['selesai_at' => null, 'selesai_oleh' => null])),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageTenggat::route('/')];
    }
}
