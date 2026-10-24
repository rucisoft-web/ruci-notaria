<?php

namespace App\Filament\Forms;

use App\Models\Dokumen;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;

/** Isian unggah dokumen yang dipakai di klien dan berkas. Berkas disimpan privat. */
class DokumenFields
{
    public static function make(string $directory): array
    {
        return [
            Select::make('jenis')->label('Jenis')->options(Dokumen::JENIS)->required()->default('lainnya'),
            TextInput::make('judul')->label('Judul')->required()->maxLength(255),
            DatePicker::make('berlaku_sampai')->label('Berlaku sampai'),
            FileUpload::make('path')->label('Berkas')->required()->disk('local')->directory($directory)->visibility('private')
                ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/webp',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
                ->storeFileNamesIn('nama_asli')
                ->maxSize(config('notaria.uploads.max_kb'))->columnSpanFull(),
        ];
    }
}
