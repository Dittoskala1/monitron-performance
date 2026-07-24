<?php

namespace App\Exports;

use App\Models\LaporanPerbaikan;
use App\Models\Bandara;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class LaporanPerbaikanExport
{
    protected $idBandara;
    protected $status;
    protected $kategoriKerusakan;
    protected $tanggalDari;
    protected $tanggalSampai;

    public function __construct($idBandara = null, $status = null, $kategoriKerusakan = null, $tanggalDari = null, $tanggalSampai = null)
    {
        $this->idBandara         = $idBandara;
        $this->status            = $status;
        $this->kategoriKerusakan = $kategoriKerusakan;
        $this->tanggalDari       = $tanggalDari;
        $this->tanggalSampai     = $tanggalSampai;
    }

    public function export()
    {
        $laporan = LaporanPerbaikan::with(['alat.lokasi.bandara', 'pengguna'])
            ->when($this->idBandara, fn($q) => $q->whereHas('alat.lokasi',
                fn($q2) => $q2->where('id_bandara', $this->idBandara)
            ))
            ->when($this->status, fn($q) => $q->where('status', $this->status))
            ->when($this->kategoriKerusakan, fn($q) => $q->where('kategori_kerusakan', $this->kategoriKerusakan))
            ->when($this->tanggalDari, fn($q) => $q->whereDate('tanggal_kerusakan', '>=', $this->tanggalDari))
            ->when($this->tanggalSampai, fn($q) => $q->whereDate('tanggal_kerusakan', '<=', $this->tanggalSampai))
            ->orderBy('tanggal_kerusakan')
            ->get();

        $namaBandara = 'Semua Bandara';
        $kodeBandara = 'ALL';
        if ($this->idBandara) {
            $bandara = Bandara::find($this->idBandara);
            if ($bandara) {
                $namaBandara = $bandara->nama_bandara;
                $kodeBandara = $bandara->kode_bandara;
            }
        }

        $rangeTanggal = $this->formatRangeTanggal();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Laporan Perbaikan');

        $C = [
            'primary'   => '2F3542',
            'secondary' => 'EAEAEA',
            'white'     => 'FFFFFF',
            'text'      => '222222',
            'border'    => 'D0D0D0',
        ];

        $lastCol = 'I'; // No, Peralatan, Kategori, Bagian, Tindakan, Tgl Kerusakan, Tgl Selesai, Jam Terputus, Keterangan

        /*
        |--------------------------------------------------------------------------
        | JUDUL
        |--------------------------------------------------------------------------
        */
        $sheet->mergeCells("A1:{$lastCol}1");
        $sheet->setCellValue('A1', 'DAFTAR KEGIATAN PERBAIKAN');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => $C['white']]],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $C['primary']]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(26);

        /*
        |--------------------------------------------------------------------------
        | INFO HEADER (Bandara, Unit, Tanggal)
        |--------------------------------------------------------------------------
        */
        $sheet->setCellValue('A3', 'BANDARA UDARA');
        $sheet->setCellValue('B3', ': ' . $kodeBandara . ' - ' . $namaBandara);
        $sheet->setCellValue('A4', 'UNIT');
        $sheet->setCellValue('B4', ': ..........................');
        $sheet->setCellValue('A5', 'TANGGAL');
        $sheet->setCellValue('B5', ': ' . $rangeTanggal);

        $sheet->getStyle('A3:A5')->applyFromArray(['font' => ['bold' => true]]);

        /*
        |--------------------------------------------------------------------------
        | HEADER TABEL
        |--------------------------------------------------------------------------
        */
        $headerRow = 7;

        $sheet->mergeCells("A{$headerRow}:A" . ($headerRow + 1));
        $sheet->mergeCells("B{$headerRow}:B" . ($headerRow + 1));
        $sheet->mergeCells("C{$headerRow}:D{$headerRow}");
        $sheet->mergeCells("E{$headerRow}:E" . ($headerRow + 1));
        $sheet->mergeCells("F{$headerRow}:F" . ($headerRow + 1));
        $sheet->mergeCells("G{$headerRow}:G" . ($headerRow + 1));
        $sheet->mergeCells("H{$headerRow}:H" . ($headerRow + 1));
        $sheet->mergeCells("I{$headerRow}:I" . ($headerRow + 1));

        $sheet->setCellValue("A{$headerRow}", 'No');
        $sheet->setCellValue("B{$headerRow}", 'Peralatan');
        $sheet->setCellValue("C{$headerRow}", 'Kerusakan');
        $sheet->setCellValue("E{$headerRow}", 'Tindakan');
        $sheet->setCellValue("F{$headerRow}", 'Tgl/Jam Kerusakan');
        $sheet->setCellValue("G{$headerRow}", 'Tgl/Jam Selesai');
        $sheet->setCellValue("H{$headerRow}", 'Total Jam Ops Terputus');
        $sheet->setCellValue("I{$headerRow}", 'Keterangan');

        $sheet->setCellValue('C' . ($headerRow + 1), 'Kategori');
        $sheet->setCellValue('D' . ($headerRow + 1), 'Bagian');

        $sheet->getStyle("A{$headerRow}:I" . ($headerRow + 1))->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => $C['text']]],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $C['white']]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '333333']]],
        ]);

        /*
        |--------------------------------------------------------------------------
        | DATA
        |--------------------------------------------------------------------------
        */
        $row = $headerRow + 2;
        $no  = 1;

        foreach ($laporan as $l) {
            $sheet->setCellValue("A{$row}", $no++);
            $sheet->setCellValue("B{$row}", $l->nama_peralatan ?? ($l->alat->nama_alat ?? '-'));
            $sheet->setCellValue("C{$row}", $l->kategori_kerusakan ?? '-');
            $sheet->setCellValue("D{$row}", $l->bagian_kerusakan ?? '-');
            $sheet->setCellValue("E{$row}", $l->tindakan ?? '-');
            $sheet->setCellValue("F{$row}", $l->tanggal_kerusakan ? Carbon::parse($l->tanggal_kerusakan)->format('d M Y H:i') : '-');
            $sheet->setCellValue("G{$row}", $l->tanggal_selesai ? Carbon::parse($l->tanggal_selesai)->format('d M Y H:i') : '-');
            $sheet->setCellValue("H{$row}", $this->formatJamMenit($l->jam_terputus));
            $sheet->setCellValue("I{$row}", $l->keterangan ?? '-');

            $sheet->getStyle("A{$row}:I{$row}")->applyFromArray([
                'alignment' => ['vertical' => Alignment::VERTICAL_TOP, 'wrapText' => true],
            ]);

            $row++;
        }

        $lastDataRow = $row - 1;

        /*
        |--------------------------------------------------------------------------
        | TANDA TANGAN
        |--------------------------------------------------------------------------
        */
        $row += 3;
        $sheet->mergeCells("F{$row}:I{$row}");
        $sheet->setCellValue("F{$row}", '..........., .......................... 20....');
        $sheet->getStyle("F{$row}")->applyFromArray(['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]);

        $row += 1;
        $sheet->mergeCells("F{$row}:I{$row}");
        $sheet->setCellValue("F{$row}", 'KEPALA BANDAR UDARA/CABANG');
        $sheet->getStyle("F{$row}")->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $row += 4;
        $sheet->mergeCells("F{$row}:I{$row}");
        $sheet->setCellValue("F{$row}", '..........................');
        $sheet->getStyle("F{$row}")->applyFromArray(['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]);

        /*
        |--------------------------------------------------------------------------
        | BORDER & LEBAR KOLOM
        |--------------------------------------------------------------------------
        */
        $sheet->getStyle("A{$headerRow}:I{$lastDataRow}")->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
        ]);

        // Pastikan header tabel tetap punya border tegas (di-apply ulang setelah border umum)
        $sheet->getStyle("A{$headerRow}:I" . ($headerRow + 1))->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '000000']]],
        ]);

        $sheet->getColumnDimension('A')->setWidth(5);
        $sheet->getColumnDimension('B')->setWidth(28);
        $sheet->getColumnDimension('C')->setWidth(14);
        $sheet->getColumnDimension('D')->setWidth(22);
        $sheet->getColumnDimension('E')->setWidth(28);
        $sheet->getColumnDimension('F')->setWidth(18);
        $sheet->getColumnDimension('G')->setWidth(18);
        $sheet->getColumnDimension('H')->setWidth(18);
        $sheet->getColumnDimension('I')->setWidth(28);

        /*
        |--------------------------------------------------------------------------
        | NAMA FILE & OUTPUT
        |--------------------------------------------------------------------------
        */
        $fileName = "LAPORAN_PERBAIKAN_{$kodeBandara}_" . now()->format('Ymd_His') . ".xlsx";

        $writer = new Xlsx($spreadsheet);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition: attachment; filename=\"{$fileName}\"");
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit;
    }

    private function formatRangeTanggal()
    {
        if ($this->tanggalDari && $this->tanggalSampai) {
            return Carbon::parse($this->tanggalDari)->format('d M Y') . ' - ' . Carbon::parse($this->tanggalSampai)->format('d M Y');
        }

        if ($this->tanggalDari) {
            return 'Mulai ' . Carbon::parse($this->tanggalDari)->format('d M Y');
        }

        return 'Semua Tanggal';
    }

    private function formatJamMenit($jam)
    {
        if (! $jam) {
            return '0 Jam 0 Menit';
        }

        $jamBulat = floor($jam);
        $menit    = round(($jam - $jamBulat) * 60);

        return "{$jamBulat} Jam {$menit} Menit";
    }
}