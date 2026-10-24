<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/** Nomor dokumen internal berurutan per tahun: BRK/2026/00001, INV/2026/00001. Bukan untuk nomor akta. */
class Sequence
{
    public static function next(string $code, ?int $year = null): string
    {
        $year ??= (int) now()->format('Y');

        $number = DB::transaction(function () use ($code, $year) {
            DB::table('sequences')->insertOrIgnore(['code' => $code, 'year' => $year, 'last_number' => 0]);
            $row = DB::table('sequences')->where(['code' => $code, 'year' => $year])->lockForUpdate()->first();
            DB::table('sequences')->where('id', $row->id)->update(['last_number' => $row->last_number + 1]);

            return $row->last_number + 1;
        });

        return sprintf('%s/%d/%05d', $code, $year, $number);
    }
}
