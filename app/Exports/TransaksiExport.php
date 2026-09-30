<?php

namespace App\Exports;

use App\Models\Transaksi;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class TransaksiExport implements FromCollection, WithHeadings, WithStyles, WithTitle, ShouldAutoSize
{
    private string $tanggalMulai;
    private string $tanggalSelesai;
    private ?string $status;
    private array $cabangIds;
    private ?int $cabangId;

    public function __construct(
        string $tanggalMulai,
        string $tanggalSelesai,
        array $cabangIds = [],
        ?string $status = null,
        ?int $cabangId = null
    ) {
        $this->tanggalMulai = $tanggalMulai;
        $this->tanggalSelesai = $tanggalSelesai;
        $this->cabangIds = $cabangIds;
        $this->status = $status;
        $this->cabangId = $cabangId;
    }

    public function collection()
    {
        $akhirHari = now()->parse($this->tanggalSelesai)->endOfDay()->toDateTimeString();

        $query = Transaksi::with(['cabang', 'user'])
            ->whereIn('cabang_id', $this->cabangIds)
            ->whereBetween('waktu_selesai', [$this->tanggalMulai, $akhirHari])
            ->orderByDesc('waktu_selesai');

        if (!empty($this->status)) {
            $query->where('status', $this->status);
        }

        if (!empty($this->cabangId)) {
            $query->where('cabang_id', $this->cabangId);
        }

        return $query->get()->map(function ($t) {
            $diskon = (float) ($t->diskon ?? 0);
            $diskonPersen = $t->diskon_persen !== null ? (float) $t->diskon_persen : null;

            $labelDiskon = '-';
            if ($diskon > 0) {
                $labelDiskon = $diskonPersen
                    ? number_format($diskon, 0, ',', '.') . ' (' . number_format($diskonPersen, 2, ',', '.') . '%)'
                    : number_format($diskon, 0, ',', '.');
            }

            return [
                'Invoice'      => $t->nomor_invoice ?? ('#' . $t->id),
                'Waktu'        => $t->waktu_selesai ? $t->waktu_selesai->format('d/m/Y H:i') : '-',
                'Cabang'       => optional($t->cabang)->nama ?? optional($t->cabang)->kode ?? '-',
                'Kasir'        => optional($t->user)->name ?? '-',
                'Nama Pelanggan' => $t->nama_pelanggan ?? '-',
                'Subtotal'     => (float) ($t->subtotal ?? 0),
                'Diskon'       => $labelDiskon,
                'Total'        => (float) ($t->total ?? 0),
                'Status'       => $t->status ?? '-',
                'Tipe Pembayaran' => ucfirst($t->tipe_pembayaran ?? '-'),
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Invoice',
            'Waktu',
            'Cabang',
            'Kasir',
            'Nama Pelanggan',
            'Subtotal (Rp)',
            'Diskon',
            'Total (Rp)',
            'Status',
            'Tipe Pembayaran',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1E3A5F']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
        ];
    }

    public function title(): string
    {
        return 'Laporan Transaksi';
    }
}