<?php

namespace App\Filament\Resources\Akta\RelationManagers;

use App\Enums\Kedudukan;
use App\Filament\Resources\Berkas\BerkasResource;
use App\Models\Akta;
use App\Services\BukuDaftar;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/** Penghadap akta. Terkunci setelah akta bernomor (data dibekukan sebagai potret). */
class PenghadapRelationManager extends RelationManager
{
    protected static string $relationship = 'penghadap';

    protected static ?string $title = 'Penghadap';

    public function isReadOnly(): bool
    {
        /** @var Akta $akta */
        $akta = $this->getOwnerRecord();

        return $akta->isBernomor() || ! auth()->user()?->role?->canWork();
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            BerkasResource::klienSelect('subjek_id', 'Penghadap (orang)')->required(),
            Select::make('kedudukan')->label('Bertindak')->options(Kedudukan::class)->default(Kedudukan::DiriSendiri)->required()->live(),
            BerkasResource::klienSelect('mewakili_id', 'Yang diwakili (badan/pemberi kuasa)')
                ->visible(fn (Get $get) => ($get('kedudukan') instanceof Kedudukan ? $get('kedudukan')->value : $get('kedudukan')) !== 'diri_sendiri'),
            TextInput::make('keterangan_kedudukan')->label('Dasar kedudukan')->columnSpanFull()
                ->placeholder('Misal: selaku Direktur Utama berdasarkan akta Nomor 5 tanggal 2 Januari 2024')
                ->visible(fn (Get $get) => ($get('kedudukan') instanceof Kedudukan ? $get('kedudukan')->value : $get('kedudukan')) !== 'diri_sendiri'),
            Select::make('tanda_tangan')->label('Tanda tangan')->options(['tanda_tangan' => 'Tanda tangan', 'cap_jempol' => 'Cap jempol', 'tidak' => 'Tidak dapat menandatangani'])->default('tanda_tangan'),
            TextInput::make('alasan_tidak_tanda_tangan')->label('Alasan (bila tidak menandatangani)'),
            Toggle::make('sidik_jari')->label('Sidik jari sudah diambil'),
            TextInput::make('urutan')->label('Urutan')->numeric()->default(fn () => $this->getOwnerRecord()->penghadap()->count() + 1),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('urutan')
            ->columns([
                TextColumn::make('urutan')->label('#'),
                TextColumn::make('uraian')->label('Penghadap')->state(fn ($record) => BukuDaftar::uraianPenghadap($record))->wrap()->weight('bold'),
                TextColumn::make('keterangan_kedudukan')->label('Dasar kedudukan')->placeholder('—')->wrap(),
                TextColumn::make('tanda_tangan')->label('TTD')->badge()
                    ->formatStateUsing(fn ($state) => ['tanda_tangan' => 'Tanda tangan', 'cap_jempol' => 'Cap jempol', 'tidak' => 'Tidak menandatangani'][$state] ?? $state),
                IconColumn::make('sidik_jari')->label('Sidik jari')->boolean(),
            ])
            ->headerActions([CreateAction::make()->label('Tambah penghadap')])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }
}
