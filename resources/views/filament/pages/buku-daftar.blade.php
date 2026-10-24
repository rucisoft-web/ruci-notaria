<x-filament-panels::page>
    <div class="nt-dash">
        <div class="nt-filter">
            <label>Notaris
                <select wire:model.live="jabatanId">
                    @foreach ($jabatanOptions as $id => $nama)
                        <option value="{{ $id }}">{{ $nama }}</option>
                    @endforeach
                </select>
            </label>
            <label>Buku
                <select wire:model.live="jenis">
                    @foreach (\App\Services\BukuDaftar::JENIS as $kode => $label)
                        <option value="{{ $kode }}">{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label>Bulan
                <select wire:model.live="bulan">
                    @foreach (\App\Filament\Pages\BukuDaftar::BULAN as $n => $nama)
                        <option value="{{ $n }}">{{ $nama }}</option>
                    @endforeach
                </select>
            </label>
            <label>Tahun
                <select wire:model.live="tahun">
                    @foreach (range(now()->year, now()->year - 10) as $t)
                        <option value="{{ $t }}">{{ $t }}</option>
                    @endforeach
                </select>
            </label>
            @if ($urlCetak)
                <x-filament::button tag="a" :href="$urlCetak" target="_blank" icon="heroicon-o-printer">Cetak buku ini</x-filament::button>
                <x-filament::button tag="a" :href="$urlMpd" target="_blank" color="gray" icon="heroicon-o-paper-airplane">Paket laporan bulanan MPD</x-filament::button>
            @endif
        </div>

        <div class="nt-panel">
            @if (! $jabatan)
                <p class="nt-muted">Tambahkan data notaris di menu Pengaturan › Jabatan Notaris terlebih dahulu.</p>
            @else
                @include('cetak.partials.buku', ['jenis' => $jenis, 'isi' => $isi])
            @endif
        </div>
    </div>
</x-filament-panels::page>
