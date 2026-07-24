<?php

namespace App\Exports;

use App\Models\Alat;
use App\Models\Bandara;
use App\Models\LogHarian;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class RekapBulananExport
{
    protected $bulan;
    protected $tahun;
    protected $id_bandara;

    public function __construct($bulan, $tahun, $id_bandara = null)
    {
        $this->bulan      = $bulan;
        $this->tahun      = $tahun;
        $this->id_bandara = $id_bandara;
    }

    public function export()
    {
        $alat = Alat::with(['lokasi.bandara', 'kategori'])
            ->when($this->id_bandara, fn($q) => $q->whereHas(
                'lokasi',
                fn($q) => $q->where('id_bandara', $this->id_bandara)
            ))
            ->orderBy('id_kategori')
            ->orderBy('jenis_alat')
            ->orderBy('id_lokasi')
            ->get();

        $logs = LogHarian::whereMonth('tanggal', $this->bulan)
            ->whereYear('tanggal', $this->tahun)
            ->get()
            ->groupBy('id_alat');

        $jumlahHari = Carbon::create($this->tahun, $this->bulan)->daysInMonth;
        $bulanNama  = Carbon::create($this->tahun, $this->bulan, 1)
            ->locale('id')
            ->translatedFormat('F');

        // Nama bandara untuk file
        $kodeBandara = 'ALL';

        if ($this->id_bandara) {
            $bandara = Bandara::find($this->id_bandara);
            $kodeBandara = $bandara ? $bandara->kode_bandara : 'ALL';
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rekap Bulanan');

        /*
        |--------------------------------------------------------------------------
        | WARNA CORPORATE MINIMALIS
        |--------------------------------------------------------------------------
        */

        $C = [
            'primary'   => '2F3542',
            'secondary' => 'EAEAEA',
            'white'     => 'FFFFFF',
            'text'      => '222222',
            'danger'    => 'C00000',
            'border'    => 'D0D0D0',
        ];

        $totalCols = 8 + $jumlahHari + 3;

        $lastCol  = Coordinate::stringFromColumnIndex($totalCols);
        $colJam   = Coordinate::stringFromColumnIndex(8 + $jumlahHari + 1);
        $colAvail = Coordinate::stringFromColumnIndex(8 + $jumlahHari + 2);
        $colTotal = Coordinate::stringFromColumnIndex(8 + $jumlahHari + 3);

        /*
        |--------------------------------------------------------------------------
        | HEADER JUDUL
        |--------------------------------------------------------------------------
        */

        $sheet->mergeCells("A1:{$lastCol}1");

        $judul = "LAPORAN AVAILABILITY PERALATAN - "
            . strtoupper($bulanNama)
            . " {$this->tahun}";

        if ($kodeBandara !== 'ALL') {
            $judul .= " - {$kodeBandara}";
        }

        $sheet->setCellValue('A1', $judul);

        $sheet->getStyle('A1')->applyFromArray([
            'font' => [
                'bold'  => true,
                'size'  => 13,
                'color' => ['rgb' => $C['white']],
            ],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['rgb' => $C['primary']],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->getRowDimension(1)->setRowHeight(28);

        /*
        |--------------------------------------------------------------------------
        | HEADER KOLOM
        |--------------------------------------------------------------------------
        */

        $headers = [
            'No',
            'Kode Alat',
            'Cabang',
            'Unit',
            'Fasilitas',
            'Nama Peralatan',
            'Bulan',
            'Tahun'
        ];

        foreach ($headers as $i => $h) {
            $col = Coordinate::stringFromColumnIndex($i + 1);
            $sheet->setCellValue("{$col}2", $h);
        }

        for ($d = 1; $d <= $jumlahHari; $d++) {
            $col = Coordinate::stringFromColumnIndex(8 + $d);
            $sheet->setCellValue("{$col}2", $d);
        }

        $sheet->setCellValue("{$colJam}2", 'Jumlah Jam Terputus');
        $sheet->setCellValue("{$colAvail}2", 'Availability');
        $sheet->setCellValue("{$colTotal}2", 'Total Availability');

        $sheet->getStyle("A2:{$lastCol}2")->applyFromArray([
            'font' => [
                'bold'  => true,
                'color' => ['rgb' => $C['white']],
            ],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['rgb' => $C['primary']],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->getRowDimension(2)->setRowHeight(22);

        /*
        |--------------------------------------------------------------------------
        | DATA
        |--------------------------------------------------------------------------
        */

        $romanNums       = ['I','II','III','IV','V','VI','VII','VIII','IX','X'];
        $alatPerKategori = $alat->groupBy('id_kategori');

        $rowNum = 3;
        $noKategori = 0;

        $totalAvailAll = [];

        foreach ($alatPerKategori as $kategoriId => $alatKategori) {

            $namaKategori = strtoupper(
                $alatKategori->first()->kategori->nama_kategori ?? '-'
            );

            $romanNum = $romanNums[$noKategori] ?? ($noKategori + 1);

            $totalAvailKategori = [];

            /*
            |--------------------------------------------------------------------------
            | HEADER KATEGORI
            |--------------------------------------------------------------------------
            */

            $sheet->mergeCells("A{$rowNum}:{$lastCol}{$rowNum}");

            $sheet->setCellValue(
                "A{$rowNum}",
                "{$romanNum}. {$namaKategori}"
            );

            $sheet->getStyle("A{$rowNum}:{$lastCol}{$rowNum}")
                ->applyFromArray([
                    'font' => [
                        'bold'  => true,
                        'color' => ['rgb' => $C['text']],
                    ],
                    'fill' => [
                        'fillType'   => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => $C['secondary']],
                    ],
                    'alignment' => [
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

            $rowNum++;
            $noKategori++;

            $alatPerJenis = $alatKategori->groupBy('jenis_alat');

            foreach ($alatPerJenis as $jenisAlat => $alatJenis) {

                $jumlahUnit = $alatJenis->count();

                $totalAvailJenis = [];

                /*
                |--------------------------------------------------------------------------
                | HEADER JENIS ALAT
                |--------------------------------------------------------------------------
                */

                $sheet->mergeCells("A{$rowNum}:{$lastCol}{$rowNum}");

                $sheet->setCellValue(
                    "A{$rowNum}",
                    "{$jenisAlat} ({$jumlahUnit} Unit)"
                );

                $sheet->getStyle("A{$rowNum}:{$lastCol}{$rowNum}")
                    ->applyFromArray([
                        'font' => [
                            'bold'  => true,
                            'color' => ['rgb' => $C['text']],
                        ],
                        'fill' => [
                            'fillType'   => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'F5F5F5'],
                        ],
                    ]);

                $rowNum++;

                $noUrut = 1;

                foreach ($alatJenis as $a) {

                    $row = $rowNum;

                    $alatLogs = $logs->get($a->id_alat, collect());

                    $logPerTanggal = $alatLogs->groupBy(
                        fn($l) => Carbon::parse($l->tanggal)->day
                    );

                    $totalJamTerputus = $alatLogs->sum('jam_terputus');

                    $totalJamOperasi = $jumlahHari * 24;

                    $availability = $totalJamOperasi > 0
                        ? round(
                            (
                                ($totalJamOperasi - $totalJamTerputus)
                                / $totalJamOperasi
                            ) * 100,
                            2
                        )
                        : 100;

                    $totalAvailJenis[]    = $availability;
                    $totalAvailKategori[] = $availability;
                    $totalAvailAll[]      = $availability;

                    /*
                    |--------------------------------------------------------------------------
                    | DATA KOLOM
                    |--------------------------------------------------------------------------
                    */

                    $sheet->setCellValue("A{$row}", $noUrut);
                    $sheet->setCellValue("B{$row}", $a->kode_alat ?? '-');
                    $sheet->setCellValue("C{$row}", $a->lokasi->bandara->kode_bandara ?? '-');
                    $sheet->setCellValue("D{$row}", $a->unit_kerja ?? '-');
                    $sheet->setCellValue("E{$row}", $a->kategori->nama_kategori ?? '-');
                    $sheet->setCellValue("F{$row}", $a->nama_alat);
                    $sheet->setCellValue("G{$row}", strtoupper($bulanNama));
                    $sheet->setCellValue("H{$row}", $this->tahun);

                    /*
                    |--------------------------------------------------------------------------
                    | LOG HARIAN
                    |--------------------------------------------------------------------------
                    */

                    for ($d = 1; $d <= $jumlahHari; $d++) {

                        $col = Coordinate::stringFromColumnIndex(8 + $d);

                        $dayLogs = $logPerTanggal->get($d, collect());

                        $jamTerputus = $dayLogs->sum('jam_terputus');

                        if ($jamTerputus > 0) {

                            $sheet->setCellValue(
                                "{$col}{$row}",
                                $jamTerputus
                            );

                            $sheet->getStyle("{$col}{$row}")
                                ->applyFromArray([
                                    'font' => [
                                        'bold'  => true,
                                        'color' => ['rgb' => $C['danger']],
                                    ],
                                    'alignment' => [
                                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                                    ],
                                ]);

                        } else {

                            $sheet->setCellValue("{$col}{$row}", 'O');

                            $sheet->getStyle("{$col}{$row}")
                                ->applyFromArray([
                                    'alignment' => [
                                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                                    ],
                                ]);
                        }
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | TOTAL JAM & AVAILABILITY
                    |--------------------------------------------------------------------------
                    */

                    $jamMenit =
                        floor($totalJamTerputus)
                        . ' Jam '
                        . round(
                            ($totalJamTerputus - floor($totalJamTerputus)) * 60
                        )
                        . ' Menit';

                    $sheet->setCellValue("{$colJam}{$row}", $jamMenit);

                    $sheet->setCellValue(
                        "{$colAvail}{$row}",
                        number_format($availability, 2) . '%'
                    );

                    $sheet->setCellValue("{$colTotal}{$row}", '');

                    $rowNum++;
                    $noUrut++;
                }

                /*
                |--------------------------------------------------------------------------
                | TOTAL PER JENIS
                |--------------------------------------------------------------------------
                */

                $avgJenis = count($totalAvailJenis) > 0
                    ? round(
                        array_sum($totalAvailJenis)
                        / count($totalAvailJenis),
                        2
                    )
                    : 0;

                $sheet->mergeCells("A{$rowNum}:{$colAvail}{$rowNum}");

                $sheet->setCellValue(
                    "A{$rowNum}",
                    "TOTAL AVAILABILITY {$jenisAlat}"
                );

                $sheet->setCellValue(
                    "{$colTotal}{$rowNum}",
                    number_format($avgJenis, 2) . '%'
                );

                $sheet->getStyle("A{$rowNum}:{$lastCol}{$rowNum}")
                    ->applyFromArray([
                        'font' => [
                            'bold' => true,
                        ],
                        'fill' => [
                            'fillType'   => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'F5F5F5'],
                        ],
                    ]);

                $rowNum++;
            }

            /*
            |--------------------------------------------------------------------------
            | TOTAL KATEGORI
            |--------------------------------------------------------------------------
            */

            $avgKategori = count($totalAvailKategori) > 0
                ? round(
                    array_sum($totalAvailKategori)
                    / count($totalAvailKategori),
                    2
                )
                : 0;

            $sheet->mergeCells("A{$rowNum}:{$colAvail}{$rowNum}");

            $sheet->setCellValue(
                "A{$rowNum}",
                "TOTAL AVAILABILITY {$namaKategori}"
            );

            $sheet->setCellValue(
                "{$colTotal}{$rowNum}",
                number_format($avgKategori, 2) . '%'
            );

            $sheet->getStyle("A{$rowNum}:{$lastCol}{$rowNum}")
                ->applyFromArray([
                    'font' => [
                        'bold' => true,
                    ],
                    'fill' => [
                        'fillType'   => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'EDEDED'],
                    ],
                ]);

            $rowNum++;
        }

        /*
        |--------------------------------------------------------------------------
        | GRAND TOTAL
        |--------------------------------------------------------------------------
        */

        $avgAll = count($totalAvailAll) > 0
            ? round(
                array_sum($totalAvailAll)
                / count($totalAvailAll),
                2
            )
            : 0;

        $sheet->mergeCells("A{$rowNum}:{$colAvail}{$rowNum}");

        $sheet->setCellValue(
            "A{$rowNum}",
            "TOTAL AVAILABILITY KESELURUHAN"
        );

        $sheet->setCellValue(
            "{$colTotal}{$rowNum}",
            number_format($avgAll, 2) . '%'
        );

        $sheet->getStyle("A{$rowNum}:{$lastCol}{$rowNum}")
            ->applyFromArray([
                'font' => [
                    'bold'  => true,
                    'color' => ['rgb' => $C['white']],
                ],
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => $C['primary']],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
            ]);

        /*
        |--------------------------------------------------------------------------
        | BORDER
        |--------------------------------------------------------------------------
        */

        $lastRow = $rowNum;

        $sheet->getStyle("A2:{$lastCol}{$lastRow}")
            ->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_HAIR,
                        'color' => ['rgb' => $C['border']],
                    ],
                ],
            ]);

        /*
        |--------------------------------------------------------------------------
        | AUTO SIZE
        |--------------------------------------------------------------------------
        */

        $sheet->getColumnDimension('A')->setWidth(6);
        $sheet->getColumnDimension('B')->setWidth(25);
        $sheet->getColumnDimension('C')->setWidth(10);
        $sheet->getColumnDimension('D')->setWidth(20);
        $sheet->getColumnDimension('E')->setWidth(22);
        $sheet->getColumnDimension('F')->setWidth(35);
        $sheet->getColumnDimension('G')->setWidth(12);
        $sheet->getColumnDimension('H')->setWidth(10);

        for ($d = 1; $d <= $jumlahHari; $d++) {

            $col = Coordinate::stringFromColumnIndex(8 + $d);

            $sheet->getColumnDimension($col)->setWidth(5);
        }

        $sheet->getColumnDimension($colJam)->setWidth(24);
        $sheet->getColumnDimension($colAvail)->setWidth(16);
        $sheet->getColumnDimension($colTotal)->setWidth(18);

        /*
        |--------------------------------------------------------------------------
        | FREEZE PANE
        |--------------------------------------------------------------------------
        */

        $sheet->freezePane('I3');

        /*
        |--------------------------------------------------------------------------
        | NAMA FILE
        |--------------------------------------------------------------------------
        */

        $bulanFile = ucfirst($bulanNama);

        $fileName = "LAPBUL_{$bulanFile}_{$this->tahun}_{$kodeBandara}.xlsx";

        /*
        |--------------------------------------------------------------------------
        | EXPORT
        |--------------------------------------------------------------------------
        */

        $writer = new Xlsx($spreadsheet);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        header(
            "Content-Disposition: attachment; filename=\"{$fileName}\""
        );

        header('Cache-Control: max-age=0');

        $writer->save('php://output');

        exit;
    }
}