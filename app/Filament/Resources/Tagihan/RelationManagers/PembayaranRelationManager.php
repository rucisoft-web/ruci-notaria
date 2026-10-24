<?php

namespace App\Filament\Resources\Tagihan\RelationManagers;

use App\Enums\StatusTagihan;
use App\Models\Pembayaran;
use App\Models\Tagihan;
use App\Support\Money;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/** Pembayaran tidak dapat diubah atau dihapus; setiap pembayaran mendapat nomor kuitansi. */
class PembayaranRelationManager extends RelationManager
{
    protected static string $relationship = 'pembayaran';

    protected static ?string $title = 'Pembayaran';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        /** @var Tagihan $t */
        $t = $this->getOwnerRecord();

        return $schema->components([
            DatePicker::make('tanggal')->label('Tanggal')->default(now())->required(),
            TextInput::make('jumlah')->label('Jumlah (Rp)')->numeric()->required()->minValue(1)->maxValue(fn () => $t->sisa())
                ->default(fn () => $t->sisa())->helperText(fn () => 'Sisa tagihan '.Money::format($t->sisa())),
            Select::make('metode')->label('Metode')->options(Pembayaran::METODE)->default('transfer')->required(),
            Textarea::make('catatan')->label('Catatan')->rows(2)->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table->defaultSort('tanggal', 'desc')
            ->columns([
                TextColumn::make('nomor_kuitansi')->label('Kuitansi'),
                TextColumn::make('tanggal')->label('Tanggal')->date('d/m/Y'),
                TextColumn::make('jumlah')->label('Jumlah')->formatStateUsing(fn ($state) => Money::format($state))->alignEnd(),
                TextColumn::make('metode')->label('Metode')->formatStateUsing(fn ($state) => Pembayaran::METODE[$state] ?? $state),
                TextColumn::make('catatan')->label('Catatan')->limit(40)->placeholder('-'),
            ])
            ->headerActions([CreateAction::make()->label('Catat pembayaran')
                ->visible(fn () => $this->getOwnerRecord()->status === StatusTagihan::Terbit)])
            ->recordActions([Action::make('kuitansi')->label('Kuitansi')->icon(Heroicon::OutlinedPrinter)
                ->url(fn (Pembayaran $r) => route('cetak.kuitansi', $r))->openUrlInNewTab()]);
    }
}
