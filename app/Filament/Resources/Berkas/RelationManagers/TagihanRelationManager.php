<?php

namespace App\Filament\Resources\Berkas\RelationManagers;

use App\Filament\Resources\Tagihan\TagihanResource;
use App\Models\Tagihan;
use App\Support\Money;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class TagihanRelationManager extends RelationManager
{
    protected static string $relationship = 'tagihan';

    protected static ?string $title = 'Tagihan';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return (bool) auth()->user()?->role?->canViewFinance();
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            DatePicker::make('tanggal')->label('Tanggal')->default(now())->required(),
            DatePicker::make('jatuh_tempo')->label('Jatuh tempo'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nomor')->label('Nomor'),
                TextColumn::make('tanggal')->label('Tanggal')->date('d/m/Y'),
                TextColumn::make('total')->label('Total')->state(fn (Tagihan $r) => Money::format($r->total())),
                TextColumn::make('sisa')->label('Sisa')->state(fn (Tagihan $r) => Money::format($r->sisa())),
                TextColumn::make('status')->label('Status')->badge(),
            ])
            ->headerActions([CreateAction::make()->label('Buat tagihan')
                ->successRedirectUrl(fn (Tagihan $record) => TagihanResource::getUrl('edit', ['record' => $record]))])
            ->recordActions([Action::make('buka')->label('Buka')->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                ->url(fn (Tagihan $r) => TagihanResource::getUrl(TagihanResource::canEdit($r) ? 'edit' : 'view', ['record' => $r]))]);
    }
}
