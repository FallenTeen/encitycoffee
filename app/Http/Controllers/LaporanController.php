<?php
namespace App\Http\Controllers;

use App\Models\Shift;
use App\Models\Transaksi;
use App\Models\ItemTransaksi;
use App\Models\Kalibrasi;
use App\Models\StokEtalase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LaporanController extends Controller
{
    public function ringkasanShift(Shift $shift)
    {
        $shift->load(['user','cabang','transaksi.item.produk','kalibrasi']);

        $total_penjualan = $shift->transaksi()->where('status','selesai')->sum('total');
        $total_tunai = $shift->transaksi()->where('status','selesai')->get()->sum('total_tunai');
        $total_qris = $shift->transaksi()->where('status','selesai')->get()->sum('total_qris');

        $data = [
            'shift' => $shift,
            'keuangan' => [
                'saldo_awal' => $shift->saldo_awal,
                'saldo_akhir' => $shift->saldo_akhir,
                'saldo_diharapkan' => $shift->saldo_diharapkan,
                'selisih' => $shift->selisih,
                'total_tunai' => $total_tunai,
                'total_qris' => $total_qris,
                'total_penjualan' => $total_penjualan,
            ],
            'transaksi_stats' => [
                'total' => $shift->transaksi()->count(),
                'selesai' => $shift->transaksi()->where('status','selesai')->count(),
                'batal' => $shift->transaksi()->where('status','batal')->count(),
            ],
            'produk_terjual' => [],
            'kalibrasi' => $shift->kalibrasi,
        ];

        // group items by produk
        $items = ItemTransaksi::whereHas('transaksi', function($q) use ($shift) {
            $q->where('shift_id', $shift->id)->where('status','selesai');
        })->with('produk')->get();

        $group = [];
        foreach ($items as $it) {
            $key = $it->produk->id;
            if (! isset($group[$key])) $group[$key] = ['produk' => $it->produk, 'jumlah' => 0, 'pendapatan' => 0];
            $group[$key]['jumlah'] += $it->jumlah;
            $group[$key]['pendapatan'] += $it->subtotal;
        }

        $data['produk_terjual'] = array_values($group);

        return response()->json($data);
    }

    public function laporanHarian(Request $request, $cabangId)
    {
        $date = $request->get('tanggal');
        if (! $date) return response()->json(['error' => 'tanggal required'], 422);

        $shifts = Shift::where('cabang_id', $cabangId)
            ->whereDate('waktu_buka', $date)
            ->where('status','tutup')
            ->get();

        $total_penjualan = 0; $total_tunai = 0; $total_qris = 0; $total_transaksi = 0;

        foreach ($shifts as $s) {
            $total_penjualan += $s->transaksi()->where('status','selesai')->sum('total');
            $total_tunai += $s->transaksi()->where('status','selesai')->get()->sum('total_tunai');
            $total_qris += $s->transaksi()->where('status','selesai')->get()->sum('total_qris');
            $total_transaksi += $s->transaksi()->where('status','selesai')->count();
        }

        // simple grouping per hour
        $perJam = Transaksi::where('cabang_id',$cabangId)
            ->whereDate('created_at',$date)
            ->where('status','selesai')
            ->get()
            ->groupBy(function($t){ return $t->created_at->format('H'); });

        $produkTerlaris = ItemTransaksi::whereHas('transaksi', function($q) use ($cabangId, $date){
            $q->where('cabang_id',$cabangId)->whereDate('created_at',$date)->where('status','selesai');
        })->with('produk')->get()->groupBy('produk_id')->map(function($group){
            $jumlah = $group->sum('jumlah');
            $pendapatan = $group->sum('subtotal');
            return ['produk' => $group->first()->produk, 'jumlah' => $jumlah, 'pendapatan' => $pendapatan];
        })->sortByDesc('pendapatan')->values()->take(10);

        return response()->json([
            'tanggal' => $date,
            'total_penjualan' => $total_penjualan,
            'total_tunai' => $total_tunai,
            'total_qris' => $total_qris,
            'total_transaksi' => $total_transaksi,
            'per_jam' => $perJam->map(function($g){ return $g->sum('total'); }),
            'produk_terlaris' => $produkTerlaris,
            'breakdown_shift' => $shifts,
        ]);
    }

    public function penjualanProduk(Request $request, $cabangId)
    {
        $start = $request->get('tanggal_mulai');
        $end = $request->get('tanggal_selesai');
        if (! $start || ! $end) return response()->json(['error' => 'tanggal_mulai dan tanggal_selesai required'], 422);

        $items = ItemTransaksi::whereHas('transaksi', function($q) use ($cabangId, $start, $end){
            $q->where('cabang_id',$cabangId)->where('status','selesai')->whereBetween('created_at', [$start, $end]);
        })->with('produk')->get();

        $group = $items->groupBy('produk_id')->map(function($group){
            $produk = $group->first()->produk;
            $jumlah = $group->sum('jumlah');
            $pendapatan = $group->sum('subtotal');
            $avg = $pendapatan / ($jumlah ?: 1);
            return ['produk' => $produk, 'jumlah' => $jumlah, 'pendapatan' => $pendapatan, 'rata_rata' => $avg];
        })->sortByDesc('pendapatan')->values();

        return response()->json(['data' => $group]);
    }

    public function laporanStok($cabangId)
    {
        $stok = StokEtalase::with(['produk','batch'])->where('cabang_id', $cabangId)->get();

        $grouped = $stok->groupBy('tipe_stok')->map(function($g){
            return $g->values();
        });

        $stok_rendah = $stok->filter(function($s){ return $s->jumlah <= $s->stok_minimum; })->values();
        $mendekati = collect();
        foreach ($stok as $s) {
            foreach ($s->batch as $b) {
                if ($b->mendekati_kadaluarsa) $mendekati->push($b);
            }
        }

        $nilai = $stok->sum(function($s){ return $s->jumlah * $s->produk->harga_modal; });

        return response()->json([
            'ringkasan_per_tipe' => $grouped,
            'stok_rendah' => $stok_rendah,
            'mendekati_kadaluarsa' => $mendekati->values(),
            'nilai_inventori' => $nilai,
        ]);
    }

    public function kinerjaUser(Request $request, $userId)
    {
        $start = $request->get('tanggal_mulai');
        $end = $request->get('tanggal_selesai');
        if (! $start || ! $end) return response()->json(['error' => 'periode required'], 422);

        $shifts = Shift::where('user_id', $userId)
            ->whereBetween('waktu_buka', [$start, $end])->where('status','tutup')
            ->get();

        $total_shift = $shifts->count();
        $total_jam = $shifts->sum(function($s){ return $s->waktu_tutup ? $s->waktu_tutup->diffInHours($s->waktu_buka) : 0; });
        $total_penjualan = $shifts->sum(function($s){ return $s->transaksi()->where('status','selesai')->sum('total'); });
        $total_selisih = $shifts->sum('selisih');
        $akurasi = $total_shift ? ($shifts->filter(fn($s)=>$s->selisih==0)->count() / $total_shift) * 100 : 0;
        $total_transaksi = $shifts->sum(fn($s) => $s->transaksi()->where('status','selesai')->count());

        return response()->json([
            'user_id' => $userId,
            'periode' => [$start,$end],
            'metrik' => [
                'total_shift' => $total_shift,
                'total_jam' => $total_jam,
                'total_penjualan' => $total_penjualan,
                'avg_penjualan_per_shift' => $total_shift ? $total_penjualan / $total_shift : 0,
                'total_selisih' => $total_selisih,
                'akurasi' => $akurasi,
                'total_transaksi' => $total_transaksi,
            ],
            'riwayat_shift_terakhir' => $shifts->sortByDesc('waktu_buka')->take(10)->values(),
        ]);
    }
}
