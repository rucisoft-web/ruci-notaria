<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Berkas;
use App\Models\Dokumen;
use App\Models\DrafAkta;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Unduhan berkas privat. Setiap unduhan dokumen klien dicatat di jejak audit. */
class UnduhController extends Controller
{
    public function dokumen(Dokumen $dokumen): StreamedResponse
    {
        /** @var User $user */
        $user = auth()->user();
        abort_unless($user->role->canViewFiles(), 403);
        if ($dokumen->dokumenable instanceof Berkas) {
            abort_unless(Berkas::query()->terlihatOleh($user)->whereKey($dokumen->dokumenable_id)->exists(), 403);
        }
        abort_unless(Storage::disk('local')->exists($dokumen->path), 404);
        AuditLog::record('unduh', $dokumen);

        return Storage::disk('local')->download($dokumen->path, $dokumen->nama_asli ?: basename($dokumen->path));
    }

    public function draf(DrafAkta $draf): StreamedResponse
    {
        /** @var User $user */
        $user = auth()->user();
        abort_unless($user->role->canViewFiles() && Berkas::query()->terlihatOleh($user)->whereKey($draf->akta->berkas_id)->exists(), 403);
        abort_unless(Storage::disk('local')->exists($draf->path), 404);

        return Storage::disk('local')->download($draf->path, str($draf->akta->judul)->slug().'-draf-v'.$draf->versi.'.docx');
    }
}
