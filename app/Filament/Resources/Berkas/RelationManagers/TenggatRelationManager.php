<?php

namespace App\Filament\Resources\Berkas\RelationManagers;

use App\Models\Tenggat;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TenggatRelationManager extends RelationManager
{
    protected static string $relationship = 'tenggat';

    protected static ?string $title = 'Tenggat';

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('jatuh_tempo')
            ->columns([
                TextColumn::make('kode')->label('Kode')->badge(),
                TextColumn::make('judul')->label('Tenggat')->wrap(),
                TextColumn::make('jatuh_tempo')->label('Jatuh tempo')->date('d/m/Y')
                    ->color(fn (Tenggat $r) => match ($r->status()) {
                        'terlambat' => 'danger', 'segera' => 'warning', 'selesai' => 'success', default => null
                    }),
                TextColumn::make('selesai_at')->label('Selesai')->dateTime('d/m/Y')->placeholder('—'),
            ])
            ->recordActions([
                Action::make('selesai')->label('Tandai selesai')->icon(Heroicon::OutlinedCheck)->color('success')
                    ->visible(fn (Tenggat $r) => ! $r->selesai_at && auth()->user()->role->canWork())
                    ->action(fn (Tenggat $r) => $r->update(['selesai_at' => now(), 'selesai_oleh' => auth()->id()])),
            ]);
    }
}
