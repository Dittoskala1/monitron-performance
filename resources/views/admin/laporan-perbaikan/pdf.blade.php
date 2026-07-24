<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: 'Helvetica', Arial, sans-serif; font-size: 10px; color: #222; }
        h1 { text-align: center; font-size: 15px; margin: 0 0 14px; text-transform: uppercase; }

        .info-table { width: 100%; margin-bottom: 14px; border-collapse: collapse; }
        .info-table td { padding: 2px 4px; vertical-align: top; }
        .info-table .label { width: 90px; font-weight: bold; }
        .info-table .sep { width: 10px; }

        table.data { width: 100%; border-collapse: collapse; margin-top: 6px; }
        table.data th, table.data td {
            border: 1px solid #999;
            padding: 5px 6px;
            font-size: 9.5px;
            vertical-align: top;
        }
        table.data th {
            background-color: #ffffff;
            color: #000;
            text-align: center;
            font-weight: bold;
            border: 1.3px solid #333;
        }
        table.data td.center { text-align: center; }

        .ttd {
            margin-top: 40px;
            width: 100%;
        }
        .ttd td {
            text-align: center;
            font-size: 10px;
            padding: 3px;
        }
        .ttd .nama-jabatan {
            font-weight: bold;
            padding-top: 50px;
        }
    </style>
</head>
<body>

    <h1>Daftar Kegiatan Perbaikan</h1>

    <table class="info-table">
        <tr>
            <td class="label">Bandara Udara</td>
            <td class="sep">:</td>
            <td>{{ $kodeBandara }} - {{ $namaBandara }}</td>
        </tr>
        <tr>
            <td class="label">Unit</td>
            <td class="sep">:</td>
            <td>..........................</td>
        </tr>
        <tr>
            <td class="label">Tanggal</td>
            <td class="sep">:</td>
            <td>{{ $rangeTanggal }}</td>
        </tr>
    </table>

    <table class="data">
        <thead>
            <tr>
                <th rowspan="2" style="width:3%;">No</th>
                <th rowspan="2" style="width:16%;">Peralatan</th>
                <th colspan="2" style="width:20%;">Kerusakan</th>
                <th rowspan="2" style="width:17%;">Tindakan</th>
                <th rowspan="2" style="width:11%;">Tgl/Jam Kerusakan</th>
                <th rowspan="2" style="width:11%;">Tgl/Jam Selesai</th>
                <th rowspan="2" style="width:11%;">Total Jam Ops Terputus</th>
                <th rowspan="2" style="width:11%;">Keterangan</th>
            </tr>
            <tr>
                <th style="width:8%;">Kategori</th>
                <th style="width:12%;">Bagian</th>
            </tr>
        </thead>
        <tbody>
            @forelse($laporan as $i => $l)
            <tr>
                <td class="center">{{ $i + 1 }}</td>
                <td>{{ $l->nama_peralatan ?? ($l->alat->nama_alat ?? '-') }}</td>
                <td class="center">{{ $l->kategori_kerusakan ?? '-' }}</td>
                <td>{{ $l->bagian_kerusakan ?? '-' }}</td>
                <td>{{ $l->tindakan ?? '-' }}</td>
                <td class="center">{{ $l->tanggal_kerusakan ? \Carbon\Carbon::parse($l->tanggal_kerusakan)->format('d M Y H:i') : '-' }}</td>
                <td class="center">{{ $l->tanggal_selesai ? \Carbon\Carbon::parse($l->tanggal_selesai)->format('d M Y H:i') : '-' }}</td>
                <td class="center">{{ $jamMenit($l->jam_terputus) }}</td>
                <td>{{ $l->keterangan ?? '-' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="9" class="center">Tidak ada data laporan perbaikan</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <table class="ttd">
        <tr>
            <td style="width:55%;"></td>
            <td style="width:45%;">..........., .......................... 20....</td>
        </tr>
        <tr>
            <td></td>
            <td class="nama-jabatan">KEPALA BANDAR UDARA/CABANG</td>
        </tr>
        <tr>
            <td></td>
            <td>( .......................................... )</td>
        </tr>
    </table>

</body>
</html>