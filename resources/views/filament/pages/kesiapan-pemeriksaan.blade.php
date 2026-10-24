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
            <label>Tahun
                <select wire:model.live="tahun">
                    @foreach (range(now()->year, now()->year - 10) as $t)
                        <option value="{{ $t }}">{{ $t }}</option>
                    @endforeach
                </select>
            </label>
        </div>

        <div class="nt-panel">
            <div class="nt-check">
                <span class="n {{ $celah ? 'ada' : '' }}">{{ count($celah) }}</span>
                <div>
                    <b>Celah nomor akta</b><br>
                    <small>{{ $celah ? 'Nomor urut yang hilang: '.implode(', ', array_slice($celah, 0, 30)) : 'Nomor akta berurutan tanpa celah.' }}</small>
                </div>
            </div>
            @foreach ($hasil as $h)
                <div class="nt-check">
                    <span class="n {{ $h['jumlah'] ? 'ada' : '' }}">{{ $h['jumlah'] }}</span>
                    <div>
                        <b>{{ $h['judul'] }}</b><br>
                        @if ($h['contoh'])
                            <small>{{ implode(' · ', $h['contoh']) }}{{ $h['jumlah'] > count($h['contoh']) ? ' · …' : '' }}</small>
                        @else
                            <small>Tidak ada.</small>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
        <p class="nt-muted" style="margin-top: 10px">Pemeriksaan ini membantu persiapan; tidak menggantikan pemeriksaan fisik minuta, buku daftar, dan protokol.</p>
    </div>
</x-filament-panels::page>
