<?php

namespace App\Filament\Resources\Akta\RelationManagers;

use App\Enums\DasarHak;
use App\Enums\JenisSalinan;
use App\Models\Akta;
use App\Models\Salinan;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/** Register salinan, kutipan, grosse (Pasal 54 UUJN). Permintaan dari pihak yang tidak berhak dicatat sebagai penolakan. */
class SalinanRelationManager extends RelationManager
{
    protected static string $relationship = 'salinan';

    protected static ?string $title = 'Salinan & grosse';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord->isBernomor();
    }

    public function isReadOnly(): bool
    {
        return ! auth()->user()?->role?->canWork();
    }

    public function form(Schema $schema): Schema
    {
        /** @var Akta $akta */
        $akta = $this->getOwnerRecord();
        $jenis = [JenisSalinan::Salinan->value => 'Salinan', JenisSalinan::Kutipan->value => 'Kutipan']
            + ($akta->kategori->value === 'pengakuan_utang' ? [JenisSalinan::Grosse->value => 'Grosse'] : []);

        return $schema->components([
            Select::make('jenis')->label('Jenis')->options($jenis)->default('salinan')->required(),
            DatePicker::make('tanggal')->label('Tanggal')->default(now())->required(),
            TextInput::make('penerima')->label('Penerima')->required(),
            Select::make('dasar_hak')->label('Dasar hak penerima')->options(DasarHak::class)->required()->live(),
            Textarea::make('keterangan')->label('Keterangan')->columnSpanFull(),
            Textarea::make('alasan_tolak')->label('Alasan penolakan')->columnSpanFull()
                ->visible(fn (Get $get) => in_array($get('dasar_hak'), [DasarHak::TidakBerhak, DasarHak::TidakBerhak->value], true))->required(fn (Get $get) => in_array($get('dasar_hak'), [DasarHak::TidakBerhak, DasarHak::TidakBerhak->value], true)),
            Toggle::make('bermeterai')->label('Dibubuhi meterai')->default(true),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('tanggal', 'desc')
            ->columns([
                TextColumn::make('tanggal')->label('Tanggal')->date('d/m/Y'),
                TextColumn::make('jenis')->label('Jenis')->badge()
                    ->formatStateUsing(fn (Salinan $r) => $r->jenis->getLabel().($r->grosse_ke ? ' ke-'.$r->grosse_ke : '')),
                TextColumn::make('penerima')->label('Penerima'),
                TextColumn::make('dasar_hak')->label('Dasar hak')->badge(),
                IconColumn::make('ditolak')->label('Ditolak')->boolean(),
                IconColumn::make('bermeterai')->label('Meterai')->boolean(),
            ])
            ->headerActions([CreateAction::make()->label('Catat salinan/permintaan')]);
    }
}
