<?php

namespace App\Exports;

use App\Models\HasilSaw;
use App\Models\Periode;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Illuminate\Support\Collection;

class HasilSawExport implements
    FromCollection,
    WithHeadings,
    WithTitle,
    WithStyles,
    WithColumnWidths,
    WithEvents
{
    protected $periodeId;
    protected $periode;

    public function __construct(int $periodeId)
    {
        $this->periodeId = $periodeId;
        $this->periode   = Periode::find($periodeId);
    }

    /**
     * @return Collection
     */
public function collection(): Collection 
    {
        // PERBAIKAN 1: Tambahkan withTrashed() pada relasi karyawan
        $data = HasilSaw::with([
                'karyawan' => function($q) {
                    $q->withTrashed();
                }, 
                'sanksi' => fn($q) => $q->where('periode_id', $this->periodeId)
            ])
            // PERBAIKAN 2: Gunakan whereHas yang kebal terhadap soft delete dengan withTrashed()
            ->whereHas('karyawan', function($query) {
                $query->withTrashed(); // Memastikan record lama tetap divalidasi walau sudah dihapus
            })
            ->where('periode_id', $this->periodeId)
            ->orderBy('peringkat')
            ->get()
            ->map(fn($h) => [
                'peringkat'    => $h->peringkat,
                'nip'          => $h->nip,
                // Di sini nama akan tercetak sempurna karena datanya berhasil ditarik via withTrashed()
                'nama'         => $h->karyawan?->nama ?? $h->karyawan?->nama_karyawan ?? 'Karyawan Tidak Ditemukan',
                'nilai_akhir'  => number_format($h->nilai_akhir, 2),
                'sanksi'       => $h->sanksi?->keterangan ?? '-',
                'jumlah_alpa'  => $h->sanksi?->jumlah_alpa ?? 0,
                'potongan'     => $h->sanksi?->potongan_persen
                                    ? $h->sanksi->potongan_persen . '%'
                                    : '0%',
            ]);

        return collect($data);
    }

    public function headings(): array
    {
        return [
            'Peringkat',
            'NIP',
            'Nama Pegawai',
            'Nilai Akhir',
            'Keterangan Sanksi',
            'Jumlah Alpa (hari)',
            'Potongan Tunkin (%)',
        ];
    }

    public function title(): string
    {
        return 'Hasil SAW';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 10,
            'B' => 20,
            'C' => 35,
            'D' => 15,
            'E' => 20,
            'F' => 18,
            'G' => 20,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Hitung baris terakhir (data + header + judul)
        $count = $this->collection()->count();
        $lastRow = $count + 4; // Menyesuaikan dengan insertNewRowBefore(1, 3)

        // Style header tabel (Sekarang di baris 4 karena ada judul di baris 1-2)
        $sheet->getStyle('A4:G4')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1E293B'],
            ],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // Border untuk semua sel data
        $sheet->getStyle("A4:G{$lastRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'E2E8F0'],
                ],
            ],
        ]);

        return [];
    }

    public function registerEvents(): array
    {
        return [
            \Maatwebsite\Excel\Events\AfterSheet::class => function($event) {
                $periode = $this->periode;
                $sheet   = $event->sheet->getDelegate();

                // Tambah baris judul di baris paling atas
                $sheet->insertNewRowBefore(1, 3);
                $sheet->mergeCells('A1:G1');
                $sheet->setCellValue('A1', 'LAPORAN HASIL PENILAIAN KEDISPLINAN PEGAWAI - METODE SAW');
                
                $sheet->mergeCells('A2:G2');
                $teksPeriode = $periode ? "{$periode->bulan} / {$periode->tahun}" : "Semua Periode";
                $sheet->setCellValue('A2', "Periode: " . $teksPeriode);

                $sheet->getStyle('A1:A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
            },
        ];
    }
}