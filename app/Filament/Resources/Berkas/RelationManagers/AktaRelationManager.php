<?php

namespace App\Filament\Resources\Berkas\RelationManagers;

use App\Enums\KategoriAkta;
use App\Enums\SifatAkta;
use App\Filament\Resources\Akta\AktaResource;
use App\Models\Akta;
use App\Models\JenisLayanan;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AktaRelationManager extends RelationManager
{
    protected static string $relationship = 'akta';

    protected static ?string $title = 'Akta';

    public function isReadOnly(): bool
    {
        return ! auth()->user()?->role?->canWork();
    }

    public function form(Schema $schema): Schema
    {
        $berkas = $this->getOwnerRecord();
        $layanan = $berkas->jenisLayanan;

        return $schema->components([
            TextInput::make('judul')->label('Judul akta')->required()->default($layanan?->kategori === 'akta' ? $layanan->nama : null)->columnSpanFull(),
            Select::make('jenis_layanan_id')->label('Jenis')->options(fn () => JenisLayanan::query()->whereIn('kategori', ['akta', 'ppat'])->orderBy('urutan')->pluck('nama', 'id'))
                ->default($layanan?->id)->live()
                ->afterStateUpdated(function ($state, Set $set) {
                    if ($j = JenisLayanan::find($state)) {
                        $set('sifat', $j->sifat_akta);
                        $set('kategori', $j->kategori_akta);
                        $set('judul', $j->nama);
                    }
                }),
            Select::make('sifat')->label('Sifat')->options(SifatAkta::class)->default($layanan?->sifat_akta ?? SifatAkta::Minuta)->required(),
            Select::make('kategori')->label('Kategori')->options(KategoriAkta::class)->default($layanan?->kategori_akta ?? KategoriAkta::Umum)->required(),
            TextInput::make('nilai_ekonomis')->label('Nilai ekonomis objek')->numeric()->prefix('Rp')->default($berkas->nilai_transaksi),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nomor_bulanan')->label('No.')->placeholder('—'),
                TextColumn::make('tanggal')->label('Tanggal')->date('d/m/Y')->placeholder('—'),
                TextColumn::make('judul')->label('Judul')->wrap()->weight('bold'),
                TextColumn::make('status')->label('Status')->badge(),
                TextColumn::make('jadwal_at')->label('Jadwal')->dateTime('d/m/Y H:i')->placeholder('—'),
            ])
            ->headerActions([CreateAction::make()->label('Tambah akta')
                ->mutateDataUsing(fn (array $data) => $data + ['jabatan_id' => $this->getOwnerRecord()->jabatan_id])
                ->successRedirectUrl(fn (Akta $record) => AktaResource::getUrl('view', ['record' => $record]))])
            ->recordActions([Action::make('buka')->label('Buka')->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                ->url(fn (Akta $r) => AktaResource::getUrl('view', ['record' => $r]))]);
    }
}
