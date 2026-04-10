<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan {{ ucfirst($jenis ?? 'laporan') }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; font-size: 12px; color: #111827; padding: 20px; }

        .header { padding-bottom: 10px; border-bottom: 2px solid #1e3a5f; margin-bottom: 12px; display: flex; justify-content: space-between; align-items: flex-start; }
        .store-name { font-size: 18px; font-weight: 700; color: #1e3a5f; }
        .report-title { font-size: 13px; font-weight: 600; color: #374151; margin-top: 2px; text-transform: capitalize; }
        .meta { text-align: right; font-size: 10px; color: #6b7280; line-height: 1.6; }
        .meta strong { color: #374151; }

        .summary-bar { display: flex; flex-wrap: wrap; gap: 14px; background: #f1f5f9; border-radius: 6px; padding: 8px 12px; margin-bottom: 14px; }
        .summary-item { font-size: 10px; color: #64748b; }
        .summary-item strong { display: block; font-size: 13px; color: #1e3a5f; font-weight: 700; }

        table { width: 100%; border-collapse: collapse; font-size: 11px; margin-top: 8px; }
        thead tr { background: #1e3a5f; color: #fff; }
        th, td { padding: 7px 8px; border-bottom: 1px solid #e5e7eb; text-align: left; }
        th.right, td.right { text-align: right; }
        tbody tr:nth-child(even) { background: #f9fafb; }

        .empty { text-align: center; padding: 24px; color: #94a3b8; font-style: italic; }

        .footer { margin-top: 16px; padding-top: 8px; border-top: 1px solid #e5e7eb; display: flex; justify-content: space-between; font-size: 9px; color: #94a3b8; }

        .print-bar { margin-bottom: 12px; padding: 8px 12px; border-radius: 6px; background: #111827; color: #e5e7eb; display: flex; justify-content: space-between; align-items: center; font-size: 11px; }
        .print-bar button { border: none; border-radius: 5px; padding: 6px 14px; background: #fbbf24; color: #111827; font-size: 11px; font-weight: 600; cursor: pointer; }
        .print-bar button:hover { background: #facc15; }
        @media print {
            .print-bar { display: none !important; }
            body { padding: 16px; }
        }
    </style>
</head>
<body>
@php
    $now = $generated_at ?? now()->toDateTimeString();
    $appName = config('app.name', 'Laporan');
@endphp

@if(($jenis ?? '') === 'produk_favorit')
    @php
        $ring = $data['ringkasan'] ?? [];
        $periode = ($ring['tanggal_mulai'] ?? null) && ($ring['tanggal_selesai'] ?? null)
            ? ($ring['tanggal_mulai'] . ' s/d ' . $ring['tanggal_selesai'])
            : '';
        $totalQty = $ring['total_jumlah_terjual'] ?? 0;
        $totalHasil = $ring['total_hasil'] ?? 0;
        $items = $data['items'] ?? [];
        $interval = $ring['interval'] ?? '';
        $intervalLabel = [
            'hari' => 'Harian',
            'minggu' => 'Mingguan',
            'bulan' => 'Bulanan',
            'range' => 'Range Tanggal',
        ][$interval] ?? ucfirst($interval);
    @endphp

    <div class="print-bar">
        <span>🖨️ Gunakan tombol ini untuk mencetak atau menyimpan sebagai PDF.</span>
        <button onclick="window.print()">Print / Download PDF</button>
    </div>

    <div class="header">
        <div>
            <div class="store-name">{{ $appName }}</div>
            <div class="report-title">Laporan Produk Favorit</div>
        </div>
        <div class="meta">
            @if($periode)
                <div><strong>Periode</strong> {{ $periode }}</div>
            @endif
            <div><strong>Interval</strong> {{ $intervalLabel }}</div>
            <div><strong>Dibangkitkan</strong> {{ $now }}</div>
        </div>
    </div>

    <div class="summary-bar">
        <div class="summary-item">
            <strong>{{ $totalQty }}</strong>
            Total Jumlah Terjual
        </div>
        <div class="summary-item">
            <strong>Rp {{ number_format((float) $totalHasil, 0, ',', '.') }}</strong>
            Total Hasil Penjualan
        </div>
        <div class="summary-item">
            <strong>{{ count($items) }}</strong>
            Jumlah Produk
        </div>
    </div>

    @if(empty($items))
        <div class="empty">Tidak ada data produk favorit pada periode ini.</div>
    @else
        <table>
            <thead>
                <tr>
                    <th style="width:6%">No</th>
                    <th style="width:42%">Produk</th>
                    <th class="right" style="width:12%">Jumlah Terjual</th>
                    <th class="right" style="width:16%">Harga</th>
                    <th class="right" style="width:24%">Total Hasil</th>
                </tr>
            </thead>
            <tbody>
            @foreach($items as $row)
                <tr>
                    <td>{{ $row['nomor'] ?? '' }}</td>
                    <td>{{ $row['nama'] ?? '' }}</td>
                    <td class="right">{{ $row['total_terjual'] ?? 0 }}</td>
                    <td class="right">
                        Rp {{ number_format((float)($row['harga'] ?? 0), 0, ',', '.') }}
                    </td>
                    <td class="right">
                        Rp {{ number_format((float)($row['total_hasil'] ?? 0), 0, ',', '.') }}
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif

    <div class="footer">
        <span>{{ $appName }} — Laporan Produk Favorit {{ $periode }}</span>
        <span>Dicetak: {{ $now }}</span>
    </div>
@else
    <h1>Laporan {{ ucfirst($jenis ?? 'Laporan') }}</h1>
    <div class="meta">
        Dibangkitkan pada: {{ $now }}
    </div>

    @if(isset($data['ringkasan']))
        <div class="summary-bar" style="margin-top:8px;">
            <div class="summary-item">
                <strong>Ringkasan</strong>
                <span>{{ json_encode($data['ringkasan'], JSON_UNESCAPED_UNICODE) }}</span>
            </div>
        </div>
    @endif

    @if(isset($data['top_produk']) && ($jenis ?? '') === 'penjualan_produk')
        <h2 style="margin-top:16px;font-size:14px;">Top Produk</h2>
        <table>
            <thead>
                <tr>
                    <th>Produk</th>
                    <th class="right">Total Terjual</th>
                    <th class="right">Pendapatan</th>
                </tr>
            </thead>
            <tbody>
            @foreach($data['top_produk'] as $row)
                <tr>
                    <td>{{ $row['nama'] ?? '' }}</td>
                    <td class="right">{{ $row['total_terjual'] ?? 0 }}</td>
                    <td class="right">{{ $row['pendapatan'] ?? 0 }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif

    @if(isset($data['data']['kinerja']))
        <h2 style="margin-top:16px;font-size:14px;">Kinerja Kasir</h2>
        <table>
            <thead>
                <tr>
                    <th>Nama</th>
                    <th class="right">Total Shift</th>
                    <th class="right">Total Transaksi</th>
                    <th class="right">Total Penjualan</th>
                </tr>
            </thead>
            <tbody>
            @foreach($data['data']['kinerja'] as $row)
                <tr>
                    <td>{{ $row['name'] ?? '' }}</td>
                    <td class="right">{{ $row['total_shift'] ?? 0 }}</td>
                    <td class="right">{{ $row['total_transaksi'] ?? 0 }}</td>
                    <td class="right">{{ $row['total_penjualan'] ?? 0 }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif

    @if(empty($data))
        <p class="empty">Tidak ada data untuk ditampilkan.</p>
    @endif
@endif
</body>
</html>
