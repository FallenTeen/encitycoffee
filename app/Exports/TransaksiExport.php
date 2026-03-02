<?php

namespace App\Exports;

use App\Models\Transaksi;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class TransaksiExport implements FromCollection, WithHeadings, WithStyles, WithTitle, ShouldAutoSize
{
    private string $tanggalMulai;
    private string $tanggalSelesai;
    private ?string $status;

    public function __construct(string $tanggalMulai, string $tanggalSelesai, ?string $status = null)
    {
        $this->tanggalMulai = $tanggalMulai;
        $this->tanggalSelesai = $tanggalSelesai;
        $this->status = $status;
    }

    public function collection()
    {
        $query = Transaksi::with(['cabang', 'user'])->orderByDesc('created_at');

        if (!empty($this->status)) {
            $query->where('status', $this->status);
        }

        $akhirHari = now()->parse($this->tanggalSelesai)->endOfDay()->toDateTimeString();
        $query->whereBetween('created_at', [$this->tanggalMulai, $akhirHari]);

        return $query->get()->map(fn($t) => [
            'Invoice'  => $t->nomor_invoice ?? ('#' . $t->id),
            'Cabang'   => optional($t->cabang)->nama ?? optional($t->cabang)->kode ?? '-',
            'Kasir'    => optional($t->user)->name ?? '-',
            'Total'    => (float) ($t->total ?? 0),
            'Status'   => $t->status ?? '-',
            'Waktu'    => optional($t->created_at)?->format('d/m/Y H:i:s') ?? '-',
        ]);
    }

    public function headings(): array
    {
        return ['Invoice', 'Cabang', 'Kasir', 'Total (Rp)', 'Status', 'Waktu'];
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