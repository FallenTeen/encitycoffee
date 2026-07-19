<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Analisis Penjualan</title>
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
        .summary-item.green strong { color: #059669; }
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
            <div class="report-title">Laporan Analisis Penjualan</div>
        </div>
        <div class="meta">
            <div><strong>Periode</strong> {{ $tanggal_mulai }} s/d {{ $tanggal_selesai }}</div>
            <div><strong>Tipe</strong> {{ ucfirst($tipe) }}</div>
            <div><strong>Dibangkitkan</strong> {{ $generated_at }}</div>
        </div>
    </div>

    @if(in_array($tipe, ['penjualan', 'all']))
        <div class="section-title">Top 10 Produk</div>
        @if(!empty($penjualan_data))
            <div class="summary-bar">
                <div class="summary-item">
                    <strong>{{ count($penjualan_data) }}</strong>
                    Jumlah Produk
                </div>
                <div class="summary-item green">
                    <strong>Rp {{ number_format((float) $total_pendapatan, 0, ',', '.') }}</strong>
                    Total Pendapatan
                </div>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Produk</th>
                        <th class="right">Terjual</th>
                        <th class="right">Pendapatan</th>
                        <th class="right">Rata-rata/Trx</th>
                        <th class="right">Kontribusi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($penjualan_data as $i => $p)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>{{ $p['nama'] }}</td>
                            <td class="right">{{ $p['total_terjual'] }}</td>
                            <td class="right">Rp {{ number_format((float) $p['pendapatan'], 0, ',', '.') }}</td>
                            <td class="right">Rp {{ number_format((float) $p['rata_rata'], 0, ',', '.') }}</td>
                            <td class="right">{{ $p['kontribusi'] }}%</td>
                        </tr>
                    @endforeach
                    <tr class="total-row">
                        <td colspan="2"><strong>TOTAL</strong></td>
                        <td class="right"><strong>{{ collect($penjualan_data)->sum('total_terjual') }}</strong></td>
                        <td class="right"><strong>Rp {{ number_format((float) $total_pendapatan, 0, ',', '.') }}</strong></td>
                        <td colspan="2"></td>
                    </tr>
                </tbody>
            </table>
        @else
            <div class="empty">Tidak ada data penjualan produk pada periode ini.</div>
        @endif
    @endif

    @if(in_array($tipe, ['kategori', 'all']))
        <div class="section-title">Pendapatan per Kategori</div>
        @if(!empty($kategori_data))
            <div class="summary-bar">
                <div class="summary-item">
                    <strong>{{ count($kategori_data) }}</strong>
                    Jumlah Kategori
                </div>
                <div class="summary-item">
                    <strong>Rp {{ number_format((float) $total_kotor, 0, ',', '.') }}</strong>
                    Total Pendapatan Kotor
                </div>
                <div class="summary-item green">
                    <strong>Rp {{ number_format((float) $total_margin, 0, ',', '.') }}</strong>
                    Total Margin
                </div>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Kategori</th>
                        <th class="right">Pendapatan Kotor</th>
                        <th class="right">Total Modal</th>
                        <th class="right">Margin</th>
                        <th class="right">Margin %</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($kategori_data as $i => $k)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>{{ $k['kategori'] }}</td>
                            <td class="right">Rp {{ number_format((float) $k['pendapatan_kotor'], 0, ',', '.') }}</td>
                            <td class="right">Rp {{ number_format((float) $k['total_modal'], 0, ',', '.') }}</td>
                            <td class="right">Rp {{ number_format((float) $k['margin'], 0, ',', '.') }}</td>
                            <td class="right">{{ $k['margin_persen'] }}%</td>
                        </tr>
                    @endforeach
                    <tr class="total-row">
                        <td colspan="2"><strong>TOTAL</strong></td>
                        <td class="right"><strong>Rp {{ number_format((float) $total_kotor, 0, ',', '.') }}</strong></td>
                        <td class="right"><strong>Rp {{ number_format((float) collect($kategori_data)->sum('total_modal'), 0, ',', '.') }}</strong></td>
                        <td class="right"><strong>Rp {{ number_format((float) $total_margin, 0, ',', '.') }}</strong></td>
                        <td class="right"><strong>{{ $total_kotor > 0 ? round(($total_margin / $total_kotor) * 100, 2) : 0 }}%</strong></td>
                    </tr>
                </tbody>
            </table>
        @else
            <div class="empty">Tidak ada data kategori pada periode ini.</div>
        @endif
    @endif

    <div class="footer">
        <span>Dokumen ini dicetak dari {{ config('app.name', 'Sistem') }}</span>
        <span>Dibangkitkan pada {{ $generated_at }}</span>
    </div>
</body>
</html>
