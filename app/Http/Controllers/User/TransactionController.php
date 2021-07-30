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
use Carbon\Carbon;
use CoinpaymentsAPI;
use App\Helpers\ReferralHelper;
use App\Models\TokenStaked;
use App\Models\Setting;
use App\Helpers\TokenCalculate as TC;
use App\Models\MatrixDownline;
use App\Models\User;
use DB;
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
         $this->checkPayments();
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
                            TokenStaked::where('trnx_id',$statuses->id)->update(['status'=>1]);
                            IcoStage::token_add_to_account($trnx, null, 'add');
                            IcoStage::token_adjust_to_stage($trnx, abs($statuses->total_tokens), abs($statuses->base_amount), 'add');

                             $this->addBonus($statuses->id,Auth::user()->id,$trnx->base_currency_rate);
                              // if($trnx->status == 'approved' && is_active_referral_system()){
                              //       // $referral = new ReferralHelper($trnx);
                              //       // $referral->addToken('refer_to');
                              //       // $referral->addToken('refer_by');
                              //        $this->addBonus($statuses->id,$trnx->user,$trnx->base_currency_rate);
                                    
                              //   }

                        }
                       
                    } else {
                       // echo $transaction_response["error"];
                    }

            }
        }


    }


public function addBonus($tokens,$downline_id,$base_rate) {

        $tc = new TC();
        $tokentotal = DB::table('transactions')->where('id',$tokens)->sum('tokens');
      
        $tnx_type = 'referral';
        $currency = 'usd';
        $currency_rate = Setting::exchange_rate($tc->get_current_price(), $currency);
        $base_currency = strtolower(base_currency());
        $base_currency_rate = Setting::exchange_rate($tc->get_current_price(), $base_currency);
        $all_currency_rate = json_encode(Setting::exchange_rate($tc->get_current_price(), 'except'));
        $added_time = Carbon::now()->toDateTimeString();
        $tnx_date   = date('Y-m-d').' '.date('H:i');

        $uplines =  MatrixDownline::where('downline_id',$downline_id)->get();
        //$username = User::where('id',$downline_id)->fi;
        $percentage = ['level1' => 5 ,'level2' => 3 , 'level3' => 2 ];
       foreach($uplines as $upline) {
       
           $level = 'level'.$upline->level;
        $bonus_tokens = number_format($tokentotal * ($percentage[$level] / 100),2) ;
        $amount_in_usd = $bonus_tokens * $base_rate ;
       
        $save_data = [
            'created_at' => $added_time,
            'tnx_id' => set_id(rand(100, 999), 'trnx'),
            'tnx_type' => $tnx_type,
            'tnx_time' => $added_time,
            'tokens' => $bonus_tokens,
            'bonus_on_base' => 0,
            'bonus_on_token' => 0,
            'total_bonus' => 0,
            'total_tokens' => $bonus_tokens,
            'stage' =>  1,
            'user' => $upline->upline_id,
            'amount' => $amount_in_usd,
            'receive_amount' => $amount_in_usd,
            'receive_currency' => 'usd',
            'base_amount' => $amount_in_usd,
            'base_currency' => $base_currency,
            'base_currency_rate' => $base_currency_rate,
            'currency' => $currency,
            'currency_rate' => $currency_rate,
            'all_currency_rate' => $all_currency_rate,
            'payment_method' => 'system',
            'payment_to' => '',
            'payment_id' => rand(1000, 9999),
            'details' =>'Level ' . $upline->level . ' Bonus Token ',
            'status' => 'approved',
        ];
        DB::table('users')->where('id',$upline->upline_id)->increment('tokenBalance',$bonus_tokens + 0);
        DB::table('transactions')->insert($save_data);
    }

 }
 
 public function tokenTransfer(Request $request) {
    $tc = new TC();

    $message = [
                    'message_type' => 'info' ,
                    'message'      => 'Transfer token'
            ];

    $currentTokenBalance = User::where('id',auth()->user()->id)->first();

    $tokenAmount = $request->input('amount');
    $recepient = $request->input('recepient');
    $receiver = User::where('id',$recepient)->first();
    $stage = active_stage();

    $kyc = DB::table('kycs')->where('userId',$recepient)->first();
    if($recepient != auth()->user()->id) {
    if( $currentTokenBalance >= $tokenAmount  ) {

        $kycstatus = ($kyc) ? $kyc->status : 'pending';
        
        if( $kycstatus != 'approved') {

                  $message = [
                    'message_type' => 'error' ,
                    'message'      => 'Recipient must be KYC verified'
            ]; 

        }
        else {

        $tnx_type = 'transfer';
        $currency = 'usd';
        $base_rate = 0.1;
        $currency_rate = Setting::exchange_rate($tc->get_current_price(), $currency);
        $base_currency = strtolower(base_currency());
        $base_currency_rate = Setting::exchange_rate($tc->get_current_price(), $base_currency);
        $all_currency_rate = json_encode(Setting::exchange_rate($tc->get_current_price(), 'except'));
         $added_time = Carbon::now()->toDateTimeString();
         $tnx_date   = date('Y-m-d').' '.date('H:i');
          $amount_in_usd = $tokenAmount * $base_rate ;
         $sender_data = [
            'created_at' => $added_time,
            'tnx_id' => set_id(rand(100, 999), 'trnx'),
            'tnx_type' => $tnx_type,
            'tnx_time' => $added_time,
            'tokens' => $tokenAmount,
            'bonus_on_base' => 0,
            'bonus_on_token' => 0,
            'total_bonus' => 0,
            'total_tokens' => $tokenAmount,
            'stage' =>  1,
            'user' => auth()->user()->id,
            'amount' => $amount_in_usd,
            'receive_amount' => $amount_in_usd,
            'receive_currency' => 'usd',
            'base_amount' => $amount_in_usd,
            'base_currency' => $base_currency,
            'base_currency_rate' => $base_currency_rate,
            'currency' => $currency,
            'currency_rate' => $currency_rate,
            'all_currency_rate' => $all_currency_rate,
            'payment_method' => 'system',
            'payment_to' =>  $receiver->walletAddress,
            'payment_id' => rand(1000, 9999),
            'details' =>'Token transfer to ' . $receiver->name,
            'status' => 'approved',
            'extra'  => 'sent'
        ];
        $receiver_data = [
            'created_at' => $added_time,
            'tnx_id' => set_id(rand(100, 999), 'trnx'),
            'tnx_type' => $tnx_type,
            'tnx_time' => $added_time,
            'tokens' => $tokenAmount,
            'bonus_on_base' => 0,
            'bonus_on_token' => 0,
            'total_bonus' => 0,
            'total_tokens' => $tokenAmount,
            'stage' =>  1,
            'user' => $recepient,
            'amount' => $amount_in_usd,
            'receive_amount' => $amount_in_usd,
            'receive_currency' => 'usd',
            'base_amount' => $amount_in_usd,
            'base_currency' => $base_currency,
            'base_currency_rate' => $base_currency_rate,
            'currency' => $currency,
            'currency_rate' => $currency_rate,
            'all_currency_rate' => $all_currency_rate,
            'payment_method' => 'system',
            'payment_to' => auth()->user()->walletAddress,
            'payment_id' => rand(1000, 9999),
            'details' =>'Token transfer from' . auth()->user()->name,
            'status' => 'approved',
        ];
        if($receiver) {
                DB::table('users')->where('id',auth()->user()->id)->decrement('tokenBalance',$tokenAmount + 0);
                DB::table('transactions')->insert($sender_data);

                DB::table('users')->where('id',$recepient)->increment('tokenBalance',$tokenAmount + 0);
                DB::table('transactions')->insert($receiver_data);

                 $message = [
                            'message_type' => 'success' ,
                            'message'      => $tokenAmount . 'MDT has been successfully transferred' 
                ];
        }
        else {

                   $message = [
                            'message_type' => 'error' ,
                            'message'      => 'Unknown receiver'
                ];

        }

    }
       

    }
    else {

        $message = [
                    'message_type' => 'error' ,
                    'message'      => 'Not enough remaining token balance'
            ]; 
    }

    }
    else {
             $message = [
                    'message_type' => 'info' ,
                    'message'      => 'Transaction not recorded. You are only sending to yourself'
            ]; 
    }


    return response()->json($message);


 }

}
