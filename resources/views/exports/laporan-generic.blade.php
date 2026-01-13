<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan {{ ucfirst($jenis ?? 'laporan') }}</title>
    <style>
        body { font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; font-size: 12px; }
        h1 { font-size: 18px; margin-bottom: 4px; }
        h2 { font-size: 14px; margin-top: 16px; margin-bottom: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #e5e7eb; padding: 4px 6px; text-align: left; }
        th { background-color: #f3f4f6; }
        .meta { font-size: 11px; color: #6b7280; margin-bottom: 8px; }
        .summary { margin-top: 8px; }
    </style>
</head>
<body>
    <h1>Laporan {{ ucfirst($jenis ?? 'Laporan') }}</h1>
    <div class="meta">
        Dibangkitkan pada: {{ $generated_at ?? now()->toDateTimeString() }}
    </div>

    @if(isset($data['ringkasan']))
        <div class="summary">
            <h2>Ringkasan</h2>
            <pre>{{ json_encode($data['ringkasan'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
        </div>
    @endif

    @if(isset($data['top_produk']))
        <h2>Top Produk</h2>
        <table>
            <thead>
                <tr>
                    <th>Produk</th>
                    <th>Total Terjual</th>
                    <th>Pendapatan</th>
                </tr>
            </thead>
            <tbody>
            @foreach($data['top_produk'] as $row)
                <tr>
                    <td>{{ $row['nama'] ?? '' }}</td>
                    <td>{{ $row['total_terjual'] ?? 0 }}</td>
                    <td>{{ $row['pendapatan'] ?? 0 }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif

    @if(isset($data['data']['kinerja']))
        <h2>Kinerja Kasir</h2>
        <table>
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Total Shift</th>
                    <th>Total Transaksi</th>
                    <th>Total Penjualan</th>
                </tr>
            </thead>
            <tbody>
            @foreach($data['data']['kinerja'] as $row)
                <tr>
                    <td>{{ $row['name'] ?? '' }}</td>
                    <td>{{ $row['total_shift'] ?? 0 }}</td>
                    <td>{{ $row['total_transaksi'] ?? 0 }}</td>
                    <td>{{ $row['total_penjualan'] ?? 0 }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif

    @if(empty($data))
        <p>Tidak ada data untuk ditampilkan.</p>
    @endif
</body>
</html>

