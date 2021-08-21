<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMapping;

use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class UsersExport implements FromQuery , WithHeadings ,WithMapping ,ShouldAutoSize
{
    /**
    * @return \Illuminate\Support\Collection
    */
    
     use Exportable;

    public function query(){
        return User::query();
    }


     public function map($user): array {
        return [
            set_id($user->id, 'user'),
            $user->name,
            $user->email,
            $user->tokenBalance,
            $user->contributed,
            $user->walletAddress,
            date('F j, Y ',strtotime($user->created_at)),
            $user->mobile,
            $user->dateOfBirth,
            $user->nationality
        ];
    }
    public function headings(): array
    {
        return [
            'User ID',
            'Name',
            'Email',
            'Token Balance',
            'Contributed',
            'Wallet Address',
            'Date Registered',
            'Mobile',
            'Date of Birth',
            'Nationality'
        ];
    }
    
}
