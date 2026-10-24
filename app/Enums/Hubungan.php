<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/** Hubungan antarsubjek. Hubungan keluarga dipakai memeriksa Pasal 40 dan 52 UUJN. */
enum Hubungan: string implements HasColor, HasLabel
{
    use HasOptions;

    case SuamiIstri = 'suami_istri';
    case OrangTua = 'orang_tua';
    case Anak = 'anak';
    case KakekNenek = 'kakek_nenek';
    case Cucu = 'cucu';
    case Saudara = 'saudara';
    case PamanBibi = 'paman_bibi';
    case Keponakan = 'keponakan';
    case Sepupu = 'sepupu';
    case Mertua = 'mertua';
    case Menantu = 'menantu';
    case Ipar = 'ipar';
    case Pengurus = 'pengurus';
    case PemilikManfaat = 'pemilik_manfaat';
    case Kuasa = 'kuasa';

    public function getLabel(): string
    {
        return match ($this) {
            self::SuamiIstri => 'Suami/istri',
            self::OrangTua => 'Orang tua',
            self::Anak => 'Anak',
            self::KakekNenek => 'Kakek/nenek',
            self::Cucu => 'Cucu',
            self::Saudara => 'Saudara kandung',
            self::PamanBibi => 'Paman/bibi',
            self::Keponakan => 'Keponakan',
            self::Sepupu => 'Sepupu',
            self::Mertua => 'Mertua',
            self::Menantu => 'Menantu',
            self::Ipar => 'Ipar',
            self::Pengurus => 'Pengurus badan',
            self::PemilikManfaat => 'Pemilik manfaat',
            self::Kuasa => 'Penerima kuasa',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::SuamiIstri => 'danger',
            self::OrangTua => 'danger',
            self::Anak => 'danger',
            self::KakekNenek => 'danger',
            self::Cucu => 'danger',
            self::Saudara => 'warning',
            self::PamanBibi => 'warning',
            self::Keponakan => 'warning',
            self::Sepupu => 'gray',
            self::Mertua => 'warning',
            self::Menantu => 'warning',
            self::Ipar => 'warning',
            self::Pengurus => 'info',
            self::PemilikManfaat => 'info',
            self::Kuasa => 'info',
        };
    }

    /**
     * Garis dan derajat hubungan dari subjek ke terkait.
     *
     * @return array{garis: string, derajat: int}
     */
    public function garis(): array
    {
        return match ($this) {
            self::SuamiIstri => ['garis' => 'perkawinan', 'derajat' => 0],
            self::OrangTua, self::Anak => ['garis' => 'lurus', 'derajat' => 1],
            self::KakekNenek, self::Cucu => ['garis' => 'lurus', 'derajat' => 2],
            self::Saudara => ['garis' => 'samping', 'derajat' => 2],
            self::PamanBibi, self::Keponakan => ['garis' => 'samping', 'derajat' => 3],
            self::Sepupu => ['garis' => 'samping', 'derajat' => 4],
            self::Mertua, self::Menantu => ['garis' => 'semenda_lurus', 'derajat' => 1],
            self::Ipar => ['garis' => 'semenda_samping', 'derajat' => 2],
            default => ['garis' => 'lain', 'derajat' => 99],
        };
    }

    public function kebalikan(): self
    {
        return match ($this) {
            self::OrangTua => self::Anak,
            self::Anak => self::OrangTua,
            self::KakekNenek => self::Cucu,
            self::Cucu => self::KakekNenek,
            self::PamanBibi => self::Keponakan,
            self::Keponakan => self::PamanBibi,
            self::Mertua => self::Menantu,
            self::Menantu => self::Mertua,
            self::Pengurus => self::Pengurus,
            default => $this,
        };
    }
}
