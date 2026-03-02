<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Laporan Transaksi</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: Arial, Helvetica, sans-serif; font-size: 11px; color: #1a1a1a; }

    /* Header */
    .header { padding: 16px 0 12px; border-bottom: 2px solid #1e3a5f; margin-bottom: 14px; }
    .header-top { display: flex; justify-content: space-between; align-items: flex-start; }
    .store-name { font-size: 18px; font-weight: 700; color: #1e3a5f; }
    .report-title { font-size: 13px; font-weight: 600; color: #444; margin-top: 2px; }
    .meta { text-align: right; font-size: 10px; color: #666; line-height: 1.6; }
    .meta strong { color: #333; }

    /* Summary bar */
    .summary { display: flex; gap: 20px; background: #f1f5f9; border-radius: 6px; padding: 8px 14px; margin-bottom: 14px; }
    .summary-item { font-size: 10px; color: #64748b; }
    .summary-item strong { display: block; font-size: 13px; color: #1e3a5f; font-weight: 700; }

    /* Table */
    table { width: 100%; border-collapse: collapse; font-size: 10.5px; }
    thead tr { background: #1e3a5f; color: #fff; }
    thead th { padding: 8px 10px; text-align: left; font-weight: 600; letter-spacing: 0.3px; }
    thead th.right { text-align: right; }
    tbody tr { border-bottom: 1px solid #e2e8f0; }
    tbody tr:nth-child(even) { background: #f8fafc; }
    tbody td { padding: 7px 10px; vertical-align: middle; }
    tbody td.right { text-align: right; }
    tbody td.mono { font-family: 'Courier New', monospace; font-size: 9.5px; }

    /* Status badges */
    .badge { display: inline-block; padding: 2px 8px; border-radius: 12px; font-size: 9.5px; font-weight: 600; }
    .badge-selesai { background: #dcfce7; color: #166534; }
    .badge-pending { background: #fef9c3; color: #854d0e; }
    .badge-batal   { background: #fee2e2; color: #991b1b; }
    .badge-default { background: #f1f5f9; color: #475569; }

    /* Empty state */
    .empty { text-align: center; padding: 30px; color: #94a3b8; font-style: italic; }

    /* Footer */
    .footer { margin-top: 18px; padding-top: 10px; border-top: 1px solid #e2e8f0; display: flex; justify-content: space-between; font-size: 9px; color: #94a3b8; }
  </style>
</head>
<body>

  <!-- Header -->
  <div class="header">
    <div class="header-top">
      <div>
        <div class="store-name">{{ $storeName }}</div>
        <div class="report-title">Laporan Transaksi</div>
      </div>
      <div class="meta">
        <div><strong>Periode</strong> {{ $periode }}</div>
        <div><strong>Status</strong> {{ ucfirst($status) }}</div>
        <div><strong>Dicetak</strong> {{ $generated }}</div>
      </div>
    </div>
  </div>

  <!-- Summary bar -->
  @php
    $total     = $rows->count();
    $totalNilai = $rows->sum(fn($t) => (float)($t->total ?? 0));
    $selesai   = $rows->where('status', 'selesai')->count();
    $pending   = $rows->where('status', 'pending')->count();
    $batal     = $rows->where('status', 'batal')->count();
  @endphp
  <div class="summary">
    <div class="summary-item"><strong>{{ $total }}</strong> Total Transaksi</div>
    <div class="summary-item"><strong>Rp {{ number_format($totalNilai, 0, ',', '.') }}</strong> Total Nilai</div>
    <div class="summary-item"><strong>{{ $selesai }}</strong> Selesai</div>
    <div class="summary-item"><strong>{{ $pending }}</strong> Pending</div>
    <div class="summary-item"><strong>{{ $batal }}</strong> Batal</div>
  </div>

  <!-- Table -->
  <table>
    <thead>
      <tr>
        <th style="width:5%">#</th>
        <th style="width:20%">Invoice</th>
        <th style="width:17%">Cabang</th>
        <th style="width:15%">Kasir</th>
        <th class="right" style="width:16%">Total (Rp)</th>
        <th style="width:10%">Status</th>
        <th style="width:17%">Waktu</th>
      </tr>
    </thead>
    <tbody>
      @forelse($rows as $i => $t)
        <tr>
          <td>{{ $i + 1 }}</td>
          <td class="mono">{{ $t->nomor_invoice ?? ('#' . $t->id) }}</td>
          <td>{{ optional($t->cabang)->nama ?? optional($t->cabang)->kode ?? '-' }}</td>
          <td>{{ optional($t->user)->name ?? '-' }}</td>
          <td class="right">{{ number_format((float)($t->total ?? 0), 0, ',', '.') }}</td>
          <td>
            @php $st = $t->status ?? ''; @endphp
            <span class="badge badge-{{ in_array($st, ['selesai','pending','batal']) ? $st : 'default' }}">
              {{ ucfirst($st ?: '-') }}
            </span>
          </td>
          <td>{{ optional($t->created_at)?->format('d/m/Y H:i') ?? '-' }}</td>
        </tr>
      @empty
        <tr>
          <td colspan="7" class="empty">Tidak ada transaksi pada periode ini</td>
        </tr>
      @endforelse
    </tbody>
  </table>

  <!-- Footer -->
  <div class="footer">
    <span>{{ $storeName }} &mdash; Laporan Transaksi {{ $periode }}</span>
    <span>Dicetak: {{ $generated }}</span>
  </div>

</body>
</html>