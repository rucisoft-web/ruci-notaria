<?php

namespace App\Filament\Resources\Akta\RelationManagers;

use App\Models\Akta;
use App\Models\AktaSaksi;
use App\Models\Subjek;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SaksiRelationManager extends RelationManager
{
    protected static string $relationship = 'saksi';

    protected static ?string $title = 'Saksi';

    public function isReadOnly(): bool
    {
        /** @var Akta $akta */
        $akta = $this->getOwnerRecord();

        return $akta->isBernomor() || ! auth()->user()?->role?->canWork();
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('subjek_id')->label('Saksi')->required()->searchable()
                ->options(fn () => Subjek::saksiOptions())
                ->getSearchResultsUsing(fn (string $search) => Subjek::query()->where('jenis', 'orang')->cari($search)->limit(30)->pluck('nama', 'id'))
                ->getOptionLabelUsing(fn ($value) => Subjek::find($value)?->nama)
                ->helperText('Pilihan awal: staf yang ditandai sebagai saksi kantor.'),
            Select::make('jenis')->label('Jenis')->options(AktaSaksi::JENIS)->default('akta')->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        /** @var Akta $akta */
        $akta = $this->getOwnerRecord();

        return $table
            ->description('Minimal '.$akta->kategori->minimumSaksi().' saksi akta (Pasal 16 ayat 1 huruf m UUJN). Saksi tidak boleh berhubungan keluarga dengan notaris atau para pihak (Pasal 40).')
            ->columns([
                TextColumn::make('subjek.nama')->label('Nama')->weight('bold'),
                TextColumn::make('jenis')->label('Jenis')->formatStateUsing(fn ($state) => AktaSaksi::JENIS[$state] ?? $state)->badge(),
                TextColumn::make('subjek.pekerjaan')->label('Pekerjaan')->placeholder('—'),
            ])
            ->headerActions([CreateAction::make()->label('Tambah saksi')])
            ->recordActions([DeleteAction::make()]);
    }
}
