<?php

namespace App\Filament\Resources\Berkas\RelationManagers;

use App\Models\Tugas;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TugasRelationManager extends RelationManager
{
    protected static string $relationship = 'tugas';

    protected static ?string $title = 'Tugas';

    public function isReadOnly(): bool
    {
        return ! auth()->user()?->role?->canWork();
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('judul')->label('Tugas')->required()->columnSpanFull(),
            Select::make('user_id')->label('Ditugaskan ke')->options(fn () => User::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id')),
            DatePicker::make('jatuh_tempo')->label('Jatuh tempo'),
            Textarea::make('catatan')->label('Catatan')->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('jatuh_tempo')
            ->columns([
                TextColumn::make('judul')->label('Tugas')->wrap(),
                TextColumn::make('user.name')->label('PIC')->placeholder('—'),
                TextColumn::make('jatuh_tempo')->label('Jatuh tempo')->date('d/m/Y')->placeholder('—')
                    ->color(fn (Tugas $r) => ! $r->selesai_at && $r->jatuh_tempo?->isPast() ? 'danger' : null),
                TextColumn::make('selesai_at')->label('Selesai')->dateTime('d/m/Y')->placeholder('—'),
            ])
            ->headerActions([CreateAction::make()->label('Tambah tugas')])
            ->recordActions([
                Action::make('selesai')->label('Selesai')->icon(Heroicon::OutlinedCheck)->color('success')
                    ->visible(fn (Tugas $r) => ! $r->selesai_at)->action(fn (Tugas $r) => $r->update(['selesai_at' => now()])),
                EditAction::make(), DeleteAction::make(),
            ]);
    }
}
