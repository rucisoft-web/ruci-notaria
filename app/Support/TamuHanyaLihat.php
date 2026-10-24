<?php

namespace App\Support;

use App\Models\AuditLog;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;

/**
 * Akun tamu situs demo hanya melihat.
 *
 * Lapis pertama: peran Tamu tidak punya hak ubah dan Gate menolak kemampuan ubah, sehingga tombol buat/ubah/hapus bawaan tersembunyi.
 * Lapis kedua: tombol aksi khusus yang mengubah data disembunyikan menurut namanya.
 * Lapis ketiga: penyimpanan dan penghapusan model apa pun ditolak bila pengguna yang masuk adalah tamu.
 */
class TamuHanyaLihat
{
    /** Aksi yang mengubah data. Aksi lain (cetak, unduh, buka, periksa kesiapan, temuan) tetap tampil. */
    public const AKSI_UBAH = [
        'ai_isi_identitas', 'batal', 'batalkan', 'bawaan', 'beri_nomor', 'buat_draf', 'buka_lagi', 'bulanan', 'dijilid',
        'final', 'jadwalkan', 'kembali', 'periksa_ai', 'periksa_ulang', 'reset_mfa', 'selesai', 'setujui', 'susun',
        'terbit', 'ubah_tahap', 'unggah_draf', 'usangkan', 'versiBaru',
    ];

    /** Kemampuan Filament/Gate yang mengubah data. */
    public const KEMAMPUAN_UBAH = [
        'create', 'update', 'delete', 'deleteAny', 'forceDelete', 'forceDeleteAny', 'restore', 'restoreAny', 'replicate', 'reorder',
        'attach', 'attachAny', 'detach', 'detachAny', 'associate', 'associateAny', 'dissociate', 'dissociateAny',
    ];

    public const PESAN = 'Akun tamu hanya dapat melihat data contoh.';

    public static function aktif(): bool
    {
        return (bool) auth()->user()?->role?->isTamu();
    }

    public static function daftarkan(): void
    {
        Gate::before(fn ($user, string $ability) => $user->role?->isTamu() && in_array($ability, static::KEMAMPUAN_UBAH, true) ? false : null);
        Action::configureUsing(fn (Action $action) => $action->hidden(fn () => static::aktif() && static::mengubah($action)));

        Event::listen(['eloquent.saving: *', 'eloquent.deleting: *'], function (string $event, array $data) {
            $model = $data[0] ?? null;
            if ($model instanceof Model && ! $model instanceof AuditLog && static::aktif()) {
                throw new AuthorizationException(static::PESAN);
            }
        });
    }

    public static function mengubah(Action $action): bool
    {
        $nama = (string) $action->getName();

        return $action instanceof BulkAction || in_array($nama, static::AKSI_UBAH, true) || str_starts_with($nama, 'set_');
    }
}
