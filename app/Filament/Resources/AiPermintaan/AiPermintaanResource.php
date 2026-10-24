<?php

namespace App\Filament\Resources\AiPermintaan;

use App\Filament\Concerns\WithRole;
use App\Filament\Resources\AiPermintaan\Pages\ListAiPermintaan;
use App\Models\AiPermintaan;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

/** Log penggunaan AI: setiap panggilan, biaya, dan keputusan pengguna atas hasilnya. */
class AiPermintaanResource extends Resource
{
    use WithRole;

    protected static ?string $model = AiPermintaan::class;

    protected static ?string $slug = 'log-ai';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCpuChip;

    protected static string|UnitEnum|null $navigationGroup = 'Pengaturan';

    protected static ?int $navigationSort = 91;

    protected static ?string $modelLabel = 'log AI';

    protected static ?string $pluralModelLabel = 'Log AI';

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
            ->modifyQueryUsing(fn ($query) => $query->with('user', 'berkas'))
            ->columns([
                TextColumn::make('created_at')->label('Waktu')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('user.name')->label('Pengguna'),
                TextColumn::make('fitur')->label('Fitur')->formatStateUsing(fn ($state) => AiPermintaan::FITUR[$state] ?? $state),
                TextColumn::make('berkas.nomor')->label('Berkas')->placeholder('-'),
                TextColumn::make('status')->label('Status')->badge()
                    ->color(fn ($state) => match ($state) {
                        'berhasil' => 'success', 'ditolak' => 'warning', default => 'danger'
                    }),
                TextColumn::make('input_tokens')->label('Token masuk')->numeric()->toggleable(),
                TextColumn::make('output_tokens')->label('Token keluar')->numeric()->toggleable(),
                TextColumn::make('biaya_usd')->label('Biaya (USD)')->numeric(4)
                    ->summarize(Sum::make()->label('Total')->numeric(4)),
                TextColumn::make('keputusan')->label('Keputusan')->placeholder('-'),
                TextColumn::make('galat')->label('Alasan/galat')->limit(60)->placeholder('-')->wrap(),
            ])
            ->filters([
                SelectFilter::make('fitur')->label('Fitur')->options(AiPermintaan::FITUR),
                SelectFilter::make('status')->label('Status')->options(['berhasil' => 'Berhasil', 'gagal' => 'Gagal', 'ditolak' => 'Ditolak']),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListAiPermintaan::route('/')];
    }
}
