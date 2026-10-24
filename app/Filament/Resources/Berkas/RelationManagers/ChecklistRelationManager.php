<?php

namespace App\Filament\Resources\Berkas\RelationManagers;

use App\Enums\StatusChecklist;
use App\Models\BerkasChecklist;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/** Daftar periksa dokumen; diisi otomatis dari jenis layanan saat berkas dibuat. */
class ChecklistRelationManager extends RelationManager
{
    protected static string $relationship = 'checklist';

    protected static ?string $title = 'Daftar periksa dokumen';

    public function isReadOnly(): bool
    {
        return ! auth()->user()?->role?->canWork();
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('nama')->label('Dokumen')->required()->columnSpanFull(),
            Select::make('status')->label('Status')->options(StatusChecklist::class)->default(StatusChecklist::Diminta)->required(),
            Select::make('dokumen_id')->label('Dokumen terunggah')
                ->options(fn () => $this->getOwnerRecord()->dokumen()->pluck('judul', 'id')),
            TextInput::make('catatan')->label('Catatan')->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        $set = fn (StatusChecklist $s, string $label, $icon, string $color) => Action::make('set_'.$s->value)->label($label)->icon($icon)->color($color)
            ->visible(fn (BerkasChecklist $r) => $r->status !== $s && ! $this->isReadOnly())
            ->action(fn (BerkasChecklist $r) => $r->update(['status' => $s]));

        return $table
            ->defaultSort('urutan')
            ->reorderable('urutan')
            ->columns([
                TextColumn::make('nama')->label('Dokumen')->wrap(),
                TextColumn::make('status')->label('Status')->badge(),
                TextColumn::make('dokumen.judul')->label('Berkas')->placeholder('—'),
                TextColumn::make('catatan')->label('Catatan')->placeholder('—')->wrap(),
            ])
            ->headerActions([CreateAction::make()->label('Tambah')])
            ->recordActions([
                $set(StatusChecklist::Diterima, 'Diterima', Heroicon::OutlinedInboxArrowDown, 'info'),
                $set(StatusChecklist::Diverifikasi, 'Verifikasi', Heroicon::OutlinedCheckBadge, 'success'),
                EditAction::make(), DeleteAction::make(),
            ]);
    }
}
