<?php

namespace App\Http\Controllers\User;
/**
 * Transaction Controller
 *
 *
 * @package TokenLite
 * @author Softnio
 * @version 1.0.0
 */
use Auth;
use App\Models\IcoStage;
use App\PayModule\Module;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Notifications\TnxStatus;
use App\Http\Controllers\Controller;
use Models\MatrixDownline;
use CoinpaymentsAPI;
use App\Helpers\ReferralHelper;
class TransactionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     * @version 1.0.0
     * @since 1.0
     * @return void
     */
    public function index()
    {
        Transaction::where(['user' => auth()->id(), 'status' => 'new'])->delete();
        $trnxs = Transaction::where('user', Auth::id())
                    ->where('status', '!=', 'deleted')
                    ->where('status', '!=', 'new')
                    ->whereNotIn('tnx_type', ['withdraw'])
                    ->orderBy('created_at', 'DESC')->get();
        $transfers = Transaction::get_by_own(['tnx_type' => 'transfer'])->get()->count();
        $referrals = Transaction::get_by_own(['tnx_type' => 'referral'])->get()->count();
        $bonuses   = Transaction::get_by_own(['tnx_type' => 'bonus'])->get()->count();
        $refunds   = Transaction::get_by_own(['tnx_type' => 'refund'])->get()->count();
        $bounty   = Transaction::get_by_own(['tnx_type' => 'bounty'])->get()->count();
        $has_trnxs = (object) [
            'transfer' => ($transfers > 0) ? true : false,
            'referral' => ($referrals > 0) ? true : false,
            'bonus' => ($bonuses > 0) ? true : false,
            'refund' => ($refunds > 0) ? true : false,
            'bounty' => ($bounty > 0) ? true : false
        ];
        $this->checkPayments();
        return view('user.transactions', compact('trnxs', 'has_trnxs'));
    }
    

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     * @version 1.0.0
     * @since 1.0
     * @return void
     *
     * @throws \Throwable
     */
    public function show(Request $request, $id='')
    {
        $module = new Module();
        $tid = ($id == '' ? $request->input('tnx_id') : $id);
        if ($tid != null) {
            $tnx = Transaction::find($tid);
            return $module->show_details($tnx);
        } else {
            return false;
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Request $request
     * @param string $id
     * @return \Illuminate\Http\Response
     * @version 1.0.0
     * @since 1.0
     */
    public function destroy(Request $request, $id='')
    {
        $tid = ($id == '' ? $request->input('tnx_id') : $id);
        if ($tid != null) {
            $tnx = Transaction::FindOrFail($tid);
            if ($tnx) {
                $old = $tnx->status;
                $tnx->status = 'deleted';
                $tnx->save();
                if ($old == 'pending' || $old == 'onhold') {
                    IcoStage::token_add_to_account($tnx, 'sub');
                }
                $ret['msg'] = 'error';
                $ret['message'] = __('messages.delete.delete', ['what'=>'Transaction']);
            } else {
                $ret['msg'] = 'warning';
                $ret['message'] = 'This transaction is not available now!';
            }
        } else {
            $ret['msg'] = 'warning';
            $ret['message'] = __('messages.delete.failed', ['what'=>'Transaction']);
        }

        if ($request->ajax()) {
            return response()->json($ret);
        }
        return back()->with([$ret['msg'] => $ret['message']]);
    }
    public function checkPayments(){
 

        $transactionStatus = Transaction::where('user', Auth::id())->whereIn('status',['onhold','pending'])->get();
      
          
          try {
                $cps_api = new CoinpaymentsAPI(env('COINPAYMENT_PRIVATE_KEY'), env('COINPAYMENT_PUBLIC_KEY'), 'json');
               // $information = $cps_api->GetDepositAddress('bnb');
            } catch (Exception $e) {
                echo 'Error: ' . $e->getMessage();
                exit();
            }
          foreach($transactionStatus as $statuses) {
            
                         // Make call to API to create the transaction
                if(!empty($statuses->coinspaymentid)){
                    try {
                        $transaction_response = $cps_api->GetTxInfoSingle($statuses->coinspaymentid);
                    } catch (Exception $e) {
                        // echo 'Error: ' . $e->getMessage();
                       // exit();
                    }

                    // Output the response of the API call
                    if ($transaction_response["error"] == "ok") {
                      
                     
                        if($transaction_response["result"]["status_text"] == "Complete"){
                             $trnx = Transaction::find($statuses->id);
                            $trnx->receive_currency = $statuses->currency;
                            $trnx->receive_amount = $statuses->amount;
                            $trnx->status = 'approved';
                            $trnx->checked_by = json_encode(['name' => 'Admin', 'id' => '1']);
                            $trnx->checked_time = date('Y-m-d H:i:s');
                            $trnx->save();
                            IcoStage::token_add_to_account($trnx, null, 'add');
                            IcoStage::token_adjust_to_stage($trnx, abs($statuses->total_tokens), abs($statuses->base_amount), 'add');
                              if($trnx->status == 'approved' && is_active_referral_system()){
                                    $referral = new ReferralHelper($trnx);
                                    $referral->addToken('refer_to');
                                    $referral->addToken('refer_by');
                                    
                                }
                        }
                       
                    } else {
                       // echo $transaction_response["error"];
                    }

            }
        }


    }
}
