<?php

namespace App\Exports;

use App\Models\KYC;


use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMapping;

use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class KYCExport implements FromQuery , WithHeadings ,WithMapping ,ShouldAutoSize
{
    /**
    * @return \Illuminate\Support\Collection
    */
      use Exportable;
    
    public function query(){
         return KYC::query();
     }


     public function map($kyc): array {
        return [
             $kyc->id,
            $kyc->firstName,
            $kyc->lastName,
            $kyc->email,
            $kyc->phone,
            $kyc->dob,
            kyc_address($kyc, '&nbsp;'),
            $kyc->walletName,
            $kyc->walletAddress,
            $kyc->documentType,
            $kyc->status
        ];
    }

     public function headings(): array
    {
        return [
            '#',
            'First Name',
            'Last Name',
            'Email Address',
            'Phone Number',
            'Date of Birth',
            'Full Address',
            'Wallet Type',
            'Wallet Address',
            'ID Submitted',
            'Status'
        ];
    }

}
