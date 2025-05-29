<?php

namespace App\Exports;

use App\Models\Kandidat; // Diperlukan untuk akses model Kandidat
use App\Models\Suara;    // Diperlukan untuk akses model Suara
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use Illuminate\Support\Collection; // Penting untuk return type Collection

class ElectionResultsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithTitle
{
    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $kandidat = $this->data['kandidat'];
        $totalVotes = $this->data['totalVotes'];
        
        $exportResults = collect();

        foreach ($kandidat as $k) {
            $percentage = ($totalVotes > 0) ? round(($k->jumlah_suara / $totalVotes) * 100, 2) : 0;
            $exportResults->push([
                'nomor_urut' => $k->nomor_urut,
                'nama_lengkap' => $k->nama_lengkap,
                'jumlah_suara' => $k->jumlah_suara,
                'percentage' => $percentage,
            ]);
        }

        return $exportResults;
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'Nomor Urut',
            'Nama Lengkap',
            'Jumlah Suara',
            'Persentase (%)',
        ];
    }

    /**
     * @param mixed $row
     *
     * @return array
     */
    public function map($row): array
    {
        // Data sudah diformat di collection(), jadi kita hanya return row-nya
        return [
            $row['nomor_urut'],
            $row['nama_lengkap'],
            $row['jumlah_suara'],
            $row['percentage'],
        ];
    }

    /**
     * @return string
     */
    public function title(): string
    {
        return 'Hasil Pemilihan';
    }
}