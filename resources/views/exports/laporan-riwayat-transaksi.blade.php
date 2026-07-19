<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Riwayat Transaksi</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; font-size: 12px; color: #111827; padding: 20px; }
        .header { padding-bottom: 10px; border-bottom: 2px solid #1e3a5f; margin-bottom: 12px; display: flex; justify-content: space-between; align-items: flex-start; }
        .store-name { font-size: 18px; font-weight: 700; color: #1e3a5f; }
        .report-title { font-size: 13px; font-weight: 600; color: #374151; margin-top: 2px; }
        .meta { text-align: right; font-size: 10px; color: #6b7280; line-height: 1.6; }
        .meta strong { color: #374151; }
        .summary-bar { display: flex; flex-wrap: wrap; gap: 14px; background: #f1f5f9; border-radius: 6px; padding: 8px 12px; margin-bottom: 14px; }
        .summary-item { font-size: 10px; color: #64748b; }
        .summary-item strong { display: block; font-size: 13px; color: #1e3a5f; font-weight: 700; }
        .section-title { font-size: 14px; font-weight: 600; color: #1e3a5f; margin: 16px 0 8px; padding-bottom: 4px; border-bottom: 1px solid #e5e7eb; }
        table { width: 100%; border-collapse: collapse; font-size: 11px; margin-top: 8px; }
        thead tr { background: #1e3a5f; color: #fff; }
        th, td { padding: 7px 8px; border-bottom: 1px solid #e5e7eb; text-align: left; }
        th.right, td.right { text-align: right; }
        tbody tr:nth-child(even) { background: #f9fafb; }
        .total-row { background: #f1f5f9 !important; font-weight: 600; }
        .empty { text-align: center; padding: 24px; color: #94a3b8; font-style: italic; }
        .footer { margin-top: 16px; padding-top: 8px; border-top: 1px solid #e5e7eb; display: flex; justify-content: space-between; font-size: 9px; color: #94a3b8; }
        .print-bar { margin-bottom: 12px; padding: 8px 12px; border-radius: 6px; background: #111827; color: #e5e7eb; display: flex; justify-content: space-between; align-items: center; font-size: 11px; }
        .print-bar button { border: none; border-radius: 5px; padding: 6px 14px; background: #fbbf24; color: #111827; font-size: 11px; font-weight: 600; cursor: pointer; }
        .print-bar button:hover { background: #facc15; }
        @media print { .print-bar { display: none !important; } body { padding: 16px; } }
    </style>
</head>
<body>
    <div class="print-bar">
        <span>Gunakan tombol ini untuk mencetak atau menyimpan sebagai PDF.</span>
        <button onclick="window.print()">Print / Download PDF</button>
    </div>

    <div class="header">
        <div>
            <div class="store-name">{{ config('app.name', 'Laporan') }}</div>
            <div class="report-title">Laporan Riwayat Transaksi</div>
        </div>
        <div class="meta">
            <div><strong>Periode</strong> {{ $tanggal_mulai }} s/d {{ $tanggal_selesai }}</div>
            <div><strong>Tipe</strong> {{ ucfirst($tipe) }}</div>
            <div><strong>Dibangkitkan</strong> {{ $generated_at }}</div>
        </div>
    </div>

    @if(in_array($tipe, ['shift', 'all']))
        <div class="section-title">Data Shift</div>
        @if(!empty($shift_data))
            <div class="summary-bar">
                <div class="summary-item">
                    <strong>{{ count($shift_data) }}</strong>
                    Total Shift
                </div>
                <div class="summary-item">
                    <strong>Rp {{ number_format((float) ($harian_statistik['total_penjualan'] ?? 0), 0, ',', '.') }}</strong>
                    Total Penjualan
                </div>
                <div class="summary-item">
                    <strong>Rp {{ number_format((float) ($harian_statistik['rata_rata'] ?? 0), 0, ',', '.') }}</strong>
                    Rata-rata/Shift
                </div>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Cabang</th>
                        <th>Kasir</th>
                        <th>Waktu Buka</th>
                        <th>Waktu Tutup</th>
                        <th>Status</th>
                        <th class="right">Transaksi</th>
                        <th class="right">Total Penjualan</th>
                        <th class="right">Selisih Kas</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($shift_data as $i => $s)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>{{ $s['cabang'] }}</td>
                            <td>{{ $s['kasir'] }}</td>
                            <td>{{ $s['waktu_buka'] }}</td>
                            <td>{{ $s['waktu_tutup'] }}</td>
                            <td>{{ $s['status'] }}</td>
                            <td class="right">{{ $s['total_transaksi'] }}</td>
                            <td class="right">Rp {{ number_format((float) $s['total_penjualan'], 0, ',', '.') }}</td>
                            <td class="right">Rp {{ number_format((float) $s['selisih'], 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                    <tr class="total-row">
                        <td colspan="6"><strong>TOTAL</strong></td>
                        <td class="right"><strong>{{ collect($shift_data)->sum('total_transaksi') }}</strong></td>
                        <td class="right"><strong>Rp {{ number_format((float) collect($shift_data)->sum('total_penjualan'), 0, ',', '.') }}</strong></td>
                        <td class="right"><strong>Rp {{ number_format((float) collect($shift_data)->sum('selisih'), 0, ',', '.') }}</strong></td>
                    </tr>
                </tbody>
            </table>
        @else
            <div class="empty">Tidak ada data shift pada periode ini.</div>
        @endif
    @endif

    @if(in_array($tipe, ['harian', 'all']))
        <div class="section-title">Top 10 Produk Terlaris</div>
        @if(!empty($top_produk))
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Produk</th>
                        <th class="right">Terjual</th>
                        <th class="right">Pendapatan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($top_produk as $i => $p)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>{{ $p['nama'] ?? ($p->nama ?? '-') }}</td>
                            <td class="right">{{ $p['total_terjual'] ?? ($p->total_terjual ?? 0) }}</td>
                            <td class="right">Rp {{ number_format((float) ($p['pendapatan'] ?? ($p->pendapatan ?? 0)), 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="empty">Tidak ada data produk terlaris pada periode ini.</div>
        @endif
    @endif

    <div class="footer">
        <span>Dokumen ini dicetak dari {{ config('app.name', 'Sistem') }}</span>
        <span>Dibangkitkan pada {{ $generated_at }}</span>
    </div>
</body>
</html>
