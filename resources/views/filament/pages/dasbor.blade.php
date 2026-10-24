@php
    use App\Filament\Resources\Akta\AktaResource;
    use App\Filament\Resources\Berkas\BerkasResource;
    use App\Filament\Resources\Tenggat\TenggatResource;
    use App\Support\Money;
@endphp
<x-filament-panels::page>
    <div class="nt-dash">
        <div class="nt-header">
            <div>
                <div class="nt-eyebrow">{{ $kantor }}</div>
                <p>{{ \App\Support\Terbilang::hari(today()) }}, {{ today()->translatedFormat('d F Y') }}</p>
            </div>
        </div>

        @if (! $bolehLihat)
            <div class="nt-panel">
                <p>Akun admin TI tidak dapat melihat berkas klien. Gunakan menu Pengaturan untuk mengelola pengguna, hari libur, dan pengaturan kantor.</p>
            </div>
        @else
            <div class="nt-kpis">
                <a class="nt-kpi" href="{{ BerkasResource::getUrl() }}"><span>Berkas aktif</span><strong>{{ $kpi['berkas_aktif'] }}</strong></a>
                <a class="nt-kpi good" href="{{ AktaResource::getUrl() }}"><span>Akta bernomor bulan ini</span><strong>{{ $kpi['akta_bulan_ini'] }}</strong></a>
                <div class="nt-kpi"><span>Legalisasi/waarmerking bulan ini</span><strong>{{ $kpi['surat_bulan_ini'] }}</strong></div>
                <a class="nt-kpi {{ $kpi['terlambat'] ? 'danger' : 'good' }}" href="{{ TenggatResource::getUrl() }}"><span>Tenggat terlambat</span><strong>{{ $kpi['terlambat'] }}</strong></a>
                @if (! is_null($kpi['piutang']))
                    <div class="nt-kpi warning"><span>Piutang tagihan terbit</span><strong>{{ Money::short($kpi['piutang']) }}</strong></div>
                @endif
            </div>

            <div class="nt-grid">
                <section class="nt-panel wide">
                    <h3>Tenggat 7 hari ke depan <small>termasuk yang terlambat</small></h3>
                    @forelse ($tenggat as $t)
                        @php($st = $t->status())
                        <div class="nt-row">
                            <span class="nt-dot {{ $st }}"></span>
                            <div>
                                <b>{{ $t->judul }}</b>
                                <small>
                                    {{ $t->jabatan?->nama }}
                                    @if ($t->berkas) · <a href="{{ BerkasResource::getUrl('view', ['record' => $t->berkas_id]) }}">{{ $t->berkas->nomor }}</a>@endif
                                    @if ($t->akta) · <a href="{{ AktaResource::getUrl('view', ['record' => $t->akta_id]) }}">{{ $t->akta->label() }}</a>@endif
                                </small>
                            </div>
                            <span class="nt-due {{ $st }}">
                                {{ $t->jatuh_tempo->format('d/m') }}
                                <small>{{ $st === 'terlambat' ? 'lewat '.$t->jatuh_tempo->diffInDays(today()).' hari' : ($t->jatuh_tempo->isToday() ? 'hari ini' : (int) today()->diffInDays($t->jatuh_tempo).' hari lagi') }}</small>
                            </span>
                        </div>
                    @empty
                        <p class="nt-muted">Tidak ada tenggat dalam 7 hari ke depan.</p>
                    @endforelse
                </section>

                <section class="nt-panel">
                    <h3>Jadwal tanda tangan <small>hari ini dan besok</small></h3>
                    @forelse ($jadwal as $a)
                        <a class="nt-row" href="{{ AktaResource::getUrl('view', ['record' => $a]) }}">
                            <span class="nt-time">{{ $a->jadwal_at->format('H:i') }}<small>{{ $a->jadwal_at->isToday() ? 'Hari ini' : 'Besok' }}</small></span>
                            <div><b>{{ $a->judul }}</b><small>{{ $a->berkas?->klien?->nama }} · {{ $a->tempat }}</small></div>
                        </a>
                    @empty
                        <p class="nt-muted">Belum ada penandatanganan terjadwal.</p>
                    @endforelse
                </section>

                <section class="nt-panel">
                    <h3>Berkas per tahap</h3>
                    @php($maks = max(1, $perTahap->max()))
                    @foreach ($perTahap as $label => $n)
                        <div class="nt-bar"><span class="label">{{ $label }}</span><span class="track"><span class="fill" style="width: {{ round($n / $maks * 100) }}%"></span></span><b>{{ $n }}</b></div>
                    @endforeach
                </section>

                <section class="nt-panel wide">
                    <h3>Berkas tidak bergerak <small>lebih dari {{ \App\Filament\Pages\Dasbor::HARI_MACET }} hari</small></h3>
                    @forelse ($macet as $b)
                        <a class="nt-row" href="{{ BerkasResource::getUrl('view', ['record' => $b]) }}">
                            <span class="nt-dot segera"></span>
                            <div><b>{{ $b->nomor }} · {{ $b->klien?->nama }}</b><small>{{ $b->status->getLabel() }} · PIC {{ $b->pic?->name ?? '-' }}</small></div>
                            <span class="nt-due">{{ (int) $b->updated_at->diffInDays(now()) }} hari</span>
                        </a>
                    @empty
                        <p class="nt-muted">Semua berkas aktif bergerak dalam {{ \App\Filament\Pages\Dasbor::HARI_MACET }} hari terakhir.</p>
                    @endforelse
                </section>
            </div>
        @endif
    </div>
</x-filament-panels::page>
