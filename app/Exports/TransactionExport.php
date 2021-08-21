<?php

namespace App\Exports;

use App\Models\Transaction;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMapping;

use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class TransactionExport implements FromQuery , WithHeadings ,WithMapping ,ShouldAutoSize
{
    /**
    * @return \Illuminate\Support\Collection
    */
   
     public function query(){
        return Transaction::query();
    }


    public function map($trnx): array {
        $base_amount =  $trnx->base_amount . ' ' .strtoupper($trnx->base_currency);
        $amount =  $trnx->amount . ' '. strtoupper($trnx->currency);
        if($trnx->tnx_type=='referral'||$trnx->tnx_type=='bonus') {
             $base_amount = '~' ;
             $amount =  '~';
        }
        $payFrom = '' ;
        if ($trnx->tnx_type=='bonus' && $trnx->added_by!=set_added_by('0'))  {
                        $payFrom= 'Added by '.transaction_by($trnx->added_by);
            }elseif($trnx->tnx_type == 'refund') {
                            $payFrom = $trnx->details ;
            }elseif($trnx->tnx_type == 'transfer') {
                    $payFrom = $trnx->details  ;
            }else {
                                        $payFrom =  (is_gateway($trnx->payment_method, 'internal') ? gateway_type($trnx->payment_method, 'name') : ( (is_gateway($trnx->payment_method, 'online') || $trnx->payment_method=='bank') ? 'Pay via '.ucfirst($trnx->payment_method) : 'Pay with '.strtoupper($trnx->currency) ) ) ;
                                         if($trnx->wallet_address && $trnx->tnx_type!='bonus') {
                                            $payFrom = $payFrom . ' .Wallet Address :' .$trnx->wallet_address ;

                                        }
                    }
        return [
            $trnx->tnx_id,
            _date($trnx->tnx_time),
             $trnx->tnx_type,
            (starts_with($trnx->total_tokens, '-') ? '' : '+').$trnx->total_tokens  .' ' .token('symbol'),
            $amount ,
             $base_amount ,
            $payFrom,
           
            $trnx->status
        ];
    }
    public function headings(): array
    {
        return [
            'Tranx ID',
            'Date/Time',
            'Transaction Type',
            'Tokens',
            'Amount',
            'Base Amount',
            'Details',
            'Status'
           
        ];
    }
    

}
