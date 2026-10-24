<?php

namespace App\Filament\Resources\Akta\RelationManagers;

use App\Models\PeminjamanMinuta;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class PeminjamanRelationManager extends RelationManager
{
    protected static string $relationship = 'peminjaman';

    protected static ?string $title = 'Peminjaman minuta';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord->isBernomor() && $ownerRecord->sifat->value === 'minuta';
    }

    public function isReadOnly(): bool
    {
        return ! auth()->user()?->role?->canArchive();
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('peminjam_id')->label('Peminjam')->options(fn () => User::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id'))->required(),
            TextInput::make('alasan')->label('Alasan')->required(),
            DatePicker::make('jatuh_tempo')->label('Kembali paling lambat')->default(now()->addDays(1))->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('dipinjam_at', 'desc')
            ->columns([
                TextColumn::make('peminjam.name')->label('Peminjam'),
                TextColumn::make('alasan')->label('Alasan'),
                TextColumn::make('dipinjam_at')->label('Dipinjam')->dateTime('d/m/Y H:i'),
                TextColumn::make('jatuh_tempo')->label('Jatuh tempo')->date('d/m/Y')
                    ->color(fn (PeminjamanMinuta $r) => ! $r->dikembalikan_at && $r->jatuh_tempo->isPast() ? 'danger' : null),
                TextColumn::make('dikembalikan_at')->label('Kembali')->dateTime('d/m/Y H:i')->placeholder('Belum'),
            ])
            ->headerActions([CreateAction::make()->label('Catat peminjaman')->mutateDataUsing(fn (array $d) => $d + ['dipinjam_at' => now()])])
            ->recordActions([Action::make('kembali')->label('Dikembalikan')->icon(Heroicon::OutlinedArrowUturnLeft)->color('success')
                ->visible(fn (PeminjamanMinuta $r) => ! $r->dikembalikan_at && ! $this->isReadOnly())
                ->action(fn (PeminjamanMinuta $r) => $r->update(['dikembalikan_at' => now()]))]);
    }
}
