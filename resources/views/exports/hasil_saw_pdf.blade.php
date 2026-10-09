<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Hasil SAW</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #1e293b; }

        .header { text-align: center; margin-bottom: 20px; padding-bottom: 12px; border-bottom: 2px solid #1e293b; }
        .header h1 { font-size: 15px; font-weight: bold; margin-bottom: 4px; }
        .header p { font-size: 11px; color: #64748b; }

        .meta { display: flex; justify-content: space-between; margin-bottom: 16px; font-size: 10px; color: #64748b; }

        table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        thead tr { background: #1e293b; color: #fff; }
        th { padding: 8px 10px; text-align: left; font-size: 10px; font-weight: 600; letter-spacing: 0.04em; }
        th.center { text-align: center; }
        td { padding: 7px 10px; border-bottom: 1px solid #f1f5f9; font-size: 11px; }
        td.center { text-align: center; }
        tr:nth-child(even) td { background: #f8fafc; }

        .rank { display: inline-block; width: 22px; height: 22px; border-radius: 50%; text-align: center; line-height: 22px; font-size: 10px; font-weight: bold; }
        .rank-1 { background: #fef9c3; color: #854d0e; }
        .rank-2 { background: #f1f5f9; color: #475569; }
        .rank-3 { background: #fef3c7; color: #92400e; }
        .rank-n { background: #f1f5f9; color: #94a3b8; }

        .badge-sanksi { background: #fef3c7; color: #92400e; padding: 2px 7px; border-radius: 99px; font-size: 10px; }
        .badge-ok { color: #94a3b8; }
        .badge-potongan { background: #fee2e2; color: #991b1b; padding: 2px 7px; border-radius: 99px; font-size: 10px; }

        .footer { margin-top: 24px; border-top: 1px solid #e2e8f0; padding-top: 10px; display: flex; justify-content: space-between; font-size: 10px; color: #94a3b8; }

        .summary { display: flex; gap: 12px; margin-bottom: 16px; }
        .summary-card { flex: 1; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 8px 12px; }
        .summary-label { font-size: 9px; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em; }
        .summary-val { font-size: 16px; font-weight: bold; color: #1e293b; margin-top: 2px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>LAPORAN HASIL PENILAIAN PEGAWAI</h1>
        <p>Sistem Informasi Kedisiplinan Pegawai (SIKAP) · Metode SAW</p>
        <p>Periode: {{ $periode->bulan }} / {{ $periode->tahun }} | Divisi: {{ $divisi ?? 'Semua Divisi' }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th class="center">No</th>
                <th>NIP</th>
                <th>Nama Pegawai</th>
                <th>Divisi</th>
                <th class="center">Nilai Akhir</th>
                <th class="center">Sanksi</th>
                <th class="center">Alpa</th>
                <th class="center">Potongan Tunkin</th>
            </tr>
        </thead>
        <tbody>
            @foreach($hasil as $h)
            <tr>
                <td class="center">
                    <span class="rank rank-{{ $h->peringkat <= 3 ? $h->peringkat : 'n' }}">
                        {{ $h->peringkat }}
                    </span>
                </td>
                <td style="font-family:monospace">{{ $h->nip }}</td>
                <td><strong>{{ $h->karyawan?->nama ?? $h->karyawan?->nama_karyawan ?? 'Karyawan Resign' }}</strong></td>
                <td>{{ $h->karyawan?->divisi ?? '-' }}</td>
                <td class="center"><strong>{{ number_format($h->nilai_akhir, 2) }}</strong></td>
                <td class="center">
                    @if($h->sanksi)
                        <span class="badge-sanksi">{{ $h->sanksi->keterangan }}</span>
                    @else
                        <span class="badge-ok">—</span>
                    @endif
                </td>
                <td class="center">{{ $h->sanksi?->jumlah_alpa ?? 0 }} hari</td>
                <td class="center">
                    @if($h->sanksi && $h->sanksi->potongan_persen > 0)
                        <span class="badge-potongan">{{ $h->sanksi->potongan_persen }}%</span>
                    @else
                        <span class="badge-ok">0%</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <span>Dicetak pada: {{ now()->format('d/m/Y H:i') }}</span>
        <span>SIKAP · Sistem Informasi Kedisiplinan Pegawai · Metode SAW @ Prototype Team</span>
    </div>
</body>
</html>