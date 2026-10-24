<?php

namespace App\Filament\Resources\Berkas\RelationManagers;

use App\Models\DanaTitipan;
use App\Support\Money;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/** Buku dana titipan per berkas. Mutasi tidak dapat diubah atau dihapus. */
class DanaTitipanRelationManager extends RelationManager
{
    protected static string $relationship = 'danaTitipan';

    protected static ?string $title = 'Dana titipan';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return (bool) auth()->user()?->role?->canFinance();
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            DatePicker::make('tanggal')->label('Tanggal')->default(now())->required(),
            Select::make('arah')->label('Arah')->options(['masuk' => 'Masuk (diterima dari klien)', 'keluar' => 'Keluar (dibayarkan/dikembalikan)'])->required(),
            TextInput::make('jumlah')->label('Jumlah')->numeric()->minValue(0.01)->prefix('Rp')->required(),
            TextInput::make('uraian')->label('Uraian')->required()->placeholder('Misal: PNBP pengesahan PT, BPHTB, pengembalian sisa titipan'),
            TextInput::make('bukti')->label('Nomor bukti')->placeholder('Voucher/NTPN/nomor transfer'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('tanggal')
            ->description(fn () => 'Saldo: '.Money::format($this->getOwnerRecord()->saldoTitipan()))
            ->columns([
                TextColumn::make('tanggal')->label('Tanggal')->date('d/m/Y'),
                TextColumn::make('uraian')->label('Uraian')->wrap(),
                TextColumn::make('bukti')->label('Bukti')->placeholder('—'),
                TextColumn::make('masuk')->label('Masuk')->state(fn (DanaTitipan $r) => $r->arah === 'masuk' ? Money::format($r->jumlah) : ''),
                TextColumn::make('keluar')->label('Keluar')->state(fn (DanaTitipan $r) => $r->arah === 'keluar' ? Money::format($r->jumlah) : ''),
            ])
            ->headerActions([CreateAction::make()->label('Catat mutasi')]);
    }
}
