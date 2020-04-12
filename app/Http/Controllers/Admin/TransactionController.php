<?php

namespace App\Http\Controllers\Admin;
/**
 * Transactions Controller
 *
 * @package TokenLite
 * @author Softnio
 * @version 1.1.0
 */
use Auth;
use Validator;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Setting;
use App\Models\IcoStage;
use App\Helpers\NioTrans;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Models\PaymentMethod;
use App\Helpers\ReferralHelper;
use App\Notifications\TnxStatus;
use App\Notifications\Refund;
use App\Http\Controllers\Controller;
use App\Helpers\TokenCalculate as TC;
use App\Models\MatrixDownline;
use DB;

class TransactionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     * @version 1.1
     * @since 1.0
     * @return void
     */
    public function index(Request $request, $status = '')
    {
        $per_page = gmvl('tnx_per_page', 10);
        $order_by = gmvl('tnx_order_by', 'id');
        $ordered  = gmvl('tnx_ordered', 'DESC');

        if($status=='referral' || $status=='bonus') {
            $trnxs = Transaction::whereNotIn('status', ['deleted', 'new'])->where('tnx_type', $status)->orderBy($order_by, $ordered)->paginate($per_page);
        } elseif($status=='bonuses') {
            $trnxs = Transaction::whereNotIn('status', ['deleted', 'new'])->whereNotIn('tnx_type', ['withdraw'])->whereIn('tnx_type', ['referral', 'bonus'])->orderBy($order_by, $ordered)->paginate($per_page);
        } elseif($status=='approved') {
            $trnxs = Transaction::whereNotIn('status', ['deleted', 'new'])->whereNotIn('tnx_type', ['withdraw', 'bonus', 'referral'])->where('status', $status)->orderBy($order_by, $ordered)->paginate($per_page);
        }  elseif($status=='pending') {
            $trnxs = Transaction::whereNotIn('status', ['deleted', 'new'])->whereNotIn('tnx_type', ['withdraw'])->whereIn('status', [$status, 'onhold'])->orderBy($order_by, $ordered)->paginate($per_page);
        } elseif($status!=null) {
            $trnxs = Transaction::whereNotIn('status', ['deleted', 'new'])->whereNotIn('tnx_type', ['withdraw'])->where('status', $status)->orderBy($order_by, $ordered)->paginate($per_page);
        } else {
            $trnxs = Transaction::whereNotIn('status', ['deleted', 'new'])->whereNotIn('tnx_type', ['withdraw'])->orderBy($order_by, $ordered)->paginate($per_page);
        }

        // Advance search v1.1.0
        if($request->s){
            $trnxs  = Transaction::AdvancedFilter($request)
                                ->orderBy($order_by, $ordered)->paginate($per_page);
        }
        if($request->filter){
            $trnxs = Transaction::AdvancedFilter($request)
                                ->orderBy($order_by, $ordered)->paginate($per_page);
        }

        $is_page = (empty($status) ? 'all' : $status);
        $pmethods = PaymentMethod::where('status', 'active')->get();
        $gateway = PaymentMethod::all()->pluck('payment_method');
        $stages = IcoStage::whereNotIn('status', ['deleted'])->get();
        $pm_currency = PaymentMethod::Currency;
        $users = User::where('status', 'active')->whereNotNull('email_verified_at')->where('role', '!=', 'admin')->get();
        $pagi = $trnxs->appends(request()->all());
        return view('admin.transactions', compact('trnxs', 'users', 'stages', 'pmethods', 'pm_currency', 'gateway', 'is_page', 'pagi'));
    }

    /**
     * Display the specified resource.
     *
     * @param string $trnx_id
     * @return \Illuminate\Http\Response
     * @throws \Throwable
     * @version 1.0.0
     * @since 1.0
     */
    public function show($trnx_id = '')
    {
        if ($trnx_id == '') {
            return __('messages.wrong');
        } else {
            $trnx = Transaction::FindOrFail($trnx_id);
            return view('admin.trnx_details', compact('trnx'))->render();
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     * @version 1.0.0
     * @since 1.0
     */
    public function update(Request $request)
    {
        if (version_compare(phpversion(), '7.1', '>=')) {
            ini_set('precision', 17);
            ini_set('serialize_precision', -1);
        }

        $type = $request->input('req_type');
        $id = $request->input('tnx_id');
        $ret['msg'] = 'info';
        $ret['message'] = __('messages.nothing');
        if ($id != null) {
            $trnx = Transaction::find($id);
        }

        if ($type == 'canceled') {
            if ($trnx) {
                $old_status = $trnx->status;
                if ($old_status != 'deleted') {
                    if ($old_status == 'approved') {
                        $ret['msg'] = 'info';
                        $ret['message'] = __('messages.trnx.admin.already_approved');
                    } else {
                        $trnx->status = 'canceled';
                        $trnx->checked_by = json_encode(['name' => Auth::user()->name, 'id' => Auth::id()]);
                        $trnx->checked_time = date('Y-m-d H:i:s');
                        $trnx->save();

                        if ($old_status == 'pending' || $old_status == 'onhold') {
                            IcoStage::token_add_to_account($trnx, 'sub');
                        }
                        $when = now()->addMinutes(1);

                        try {
                            $trnx->tnxUser->notify((new TnxStatus($trnx, 'rejected-user')));
                            $ret['msg'] = 'success';
                            $ret['message'] = __('messages.trnx.admin.canceled');
                        } catch (\Exception $e) {
                            $ret['errors'] = $e->getMessage();
                            $ret['msg'] = 'warning';
                            $ret['message'] = __('messages.trnx.admin.canceled').' '.__('messages.email.failed');
                            ;
                        }

                        // Notification::send($trnx->tnxUser, new TnxStatus($trnx));
                    }
                } else {
                    $ret['msg'] = 'warning';
                    $ret['message'] = __('messages.trnx.admin.already_deleted');
                }
            }
        }

        if ($type == 'deleted') {
            if ($trnx) {
                $old_status = $trnx->status;
                if ($old_status == 'canceled') {
                    $trnx->status = 'deleted';
                    $trnx->checked_by = json_encode(['name' => Auth::user()->name, 'id' => Auth::id()]);
                    $trnx->checked_time = date('Y-m-d H:i:s');
                    $trnx->save();

                    $ret['msg'] = 'success';
                    $ret['message'] = __('messages.trnx.admin.deleted');
                } else {
                    $ret['msg'] = 'warning';
                    $ret['message'] = 'Canceled the transaction first!';
                }
            }
        }

        if ($type == 'approved') {
            $validator = Validator::make($request->all(), [
                'amount' => 'gt:0',
            ]);

            if ($validator->fails()) {
                if ($validator->errors()->has('amount')) {
                    $msg = NioTrans::do('amount', $validator->errors()->first(), ['value' => 0]);
                } else {
                    $msg = __('messages.form.wrong');
                }

                $ret['msg'] = 'warning';
                $ret['message'] = $msg;
            } else {
                $chk_adjust = $request->input('chk_adjust');
                $receive_amount = round($request->input('amount'), max_decimal());
                $adjust_token = round($request->input('adjusted_token'), min_decimal());
                $token = round($request->input('token'), min_decimal());
                $base_bonus = round($request->input('base_bonus'), min_decimal());
                $token_bonus = round($request->input('token_bonus'), min_decimal());
                if ($trnx) {
                    $old_status = $trnx->status;
                    $old_tokens = $trnx->total_tokens;
                    $old_base_amount = $trnx->base_amount;

                    if ($old_status != 'deleted') {
                        if ($chk_adjust == 1) {
                            $trnx->tokens = $token;
                            $trnx->base_amount = $token * $trnx->base_currency_rate;
                            $trnx->total_bonus = $base_bonus + $token_bonus;
                            $trnx->bonus_on_base = $base_bonus;
                            $trnx->bonus_on_token = $token_bonus;
                            $trnx->total_tokens = $adjust_token;
                            $trnx->amount = $receive_amount;

                            if ($old_status != 'canceled') {
                                $adjust_stage_token = $old_tokens - $trnx->total_tokens;
                                $adjust_base_amount = $old_base_amount - $trnx->base_amount;

                                if ($adjust_stage_token < 0) {
                                    IcoStage::token_adjust_to_stage($trnx, abs($adjust_stage_token), abs($adjust_base_amount), 'add');
                                } elseif ($adjust_stage_token > 0) {
                                    IcoStage::token_adjust_to_stage($trnx, abs($adjust_stage_token), abs($adjust_base_amount), 'sub');
                                }
                            }
                        }

                        $trnx->receive_currency = $trnx->currency;
                        $trnx->receive_amount = $receive_amount;
                        $trnx->status = 'approved';
                        $trnx->checked_by = json_encode(['name' => Auth::user()->name, 'id' => Auth::id()]);
                        $trnx->checked_time = date('Y-m-d H:i:s');
                        $trnx->save();

                     

                        if($trnx->status == 'approved' && is_active_referral_system()){
                            $referral = new ReferralHelper($trnx);
                            // $referral->addToken('refer_to');
                            // $referral->addToken('refer_by');
                            $this->addBonus($id,$trnx->user,$trnx->base_currency_rate);
                        }

                        if ($old_status == 'canceled') {
                            IcoStage::token_add_to_account($trnx, 'add');
                        }

                        IcoStage::token_add_to_account($trnx, null, 'add');

                        try {
                            
                            $trnx->tnxUser->notify((new TnxStatus($trnx, 'successful-user')));
                            $ret['msg'] = 'success';
                            $ret['message'] = __('messages.trnx.admin.approved');
                        } catch (\Exception $e) {
                            $ret['errors'] = $e->getMessage();
                            $ret['msg'] = 'warning';
                            $ret['message'] = __('messages.trnx.admin.approved').' '.__('messages.email.failed');
                        }
                    } else {
                        $ret['msg'] = 'warning';
                        $ret['message'] = __('messages.trnx.admin.already_deleted');
                    }
                }
            }
        }

        if($type == 'refund' && $trnx){
            $refund = $this->refund($trnx, $request->input('message'));
            if($refund){
                $ret['refund'] = $refund;
                $ret['msg'] = 'success';
                $ret['message'] = __('Refund Successful!');
            }else{
                $ret['msg'] = 'warning';
                $ret['message'] = __('Refund Failed!');
            }
        }

        $ret['data'] = $trnx;
          
        if ($request->ajax()) {
            return response()->json($ret);
        }
        return back()->with([$ret['msg'] => $ret['message']]);
    }

    /**
     * Create Refund Transaction by Admin
     *
     * @version 1.0.0
     * @since 1.1.2
     */
    protected function refund(Transaction $transaction, $message = '')
    {
        $refund = new Transaction();
        $refund->fill($transaction->only([
            'tnx_id', 'tnx_type', 'tnx_time', 'tokens', 'bonus_on_base', 'bonus_on_token', 'total_bonus', 'total_tokens', 'stage', 'user', 'amount', 'receive_amount', 'receive_currency', 'base_amount', 'base_currency', 'base_currency_rate', 'currency', 'currency_rate', 'all_currency_rate', 'wallet_address', 'payment_method', 'payment_id', 'payment_to', 'checked_by', 'added_by', 'checked_time', 'status', 'dist'
        ]))->save();
        IcoStage::token_add_to_account($transaction, 'sub');
        IcoStage::token_add_to_account($transaction, null, 'sub');
        $refund->fill([
            'tnx_id' => set_id($refund->id, 'refund'),
            'tnx_type' => 'refund',
            'tnx_time'=> now()->toDateTimeString(),
            'total_tokens' => (- $transaction->total_tokens),
            'amount' => (- $transaction->amount),
            'receive_amount' => (- $transaction->receive_amount),
            'base_amount' => (- $transaction->base_amount),
            'checked_by' => json_encode(['name' => Auth::user()->name, 'id' => Auth::id()]),
            'added_by' => set_added_by(Auth::id(), Auth::user()->role),
            'details' => 'Refund for #'.$transaction->tnx_id,
            'extra' => json_encode(['trnx' => $transaction->id, 'message' => $message])
        ])->save();
        $transaction->refund = $refund->id;
        $transaction->save();
        $this->refund_email($refund, $transaction);
        return $refund;
    }

    /**
     * Refund Email sent to user.
     *
     * @version 1.0.0
     * @since 1.1.2
     */
    protected function refund_email($refund, $transaction)
    {
        try {
            $refund->tnxUser->notify(new Refund($refund, $transaction));
            return true;
        } catch (\Exception $e) {
            // info($e->getMessage());
            return false;
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     * @version 1.2
     * @since 1.0
     * @return void
     */
    public function store(Request $request)
    {
        if (version_compare(phpversion(), '7.1', '>=')) {
            ini_set('precision', 17);
            ini_set('serialize_precision', -1);
        }
        $ret['msg'] = 'info';
        $ret['message'] = __('messages.nothing');
        $validator = Validator::make($request->all(), [
            'total_tokens' => 'required|integer|min:1',
        ], [
            'total_tokens.required' => "Token amount is required!.",
        ]);

        if ($validator->fails()) {
            if ($validator->errors()->has('total_tokens')) {
                $msg = NioTrans::do('total token', $validator->errors()->first(), ['min' => 1]);
            } else {
                $msg = __('messages.form.wrong');
            }

            $ret['msg'] = 'warning';
            $ret['message'] = $msg;
        } else {
            $tc = new TC();
            $token = $request->input('total_tokens');
            $bonus_calc = isset($request->bonus_calc) ? true : false;
            $tnx_type = $request->input('type');
            $currency = strtolower($request->input('currency'));
            $currency_rate = Setting::exchange_rate($tc->get_current_price(), $currency);
            $base_currency = strtolower(base_currency());
            $base_currency_rate = Setting::exchange_rate($tc->get_current_price(), $base_currency);
            $all_currency_rate = json_encode(Setting::exchange_rate($tc->get_current_price(), 'except'));
            $added_time = Carbon::now()->toDateTimeString();
            $tnx_date   = $request->tnx_date.' '.date('H:i');

            // v1.2
            if($tnx_type=='purchase' && $bonus_calc==true) {
                $trnx_data = [
                    'token' => round($token, min_decimal()),
                    'bonus_on_base' => $tc->calc_token($token, 'bonus-base'),
                    'bonus_on_token' => $tc->calc_token($token, 'bonus-token'),
                    'total_bonus' => $tc->calc_token($token, 'bonus'),
                    'total_tokens' => $tc->calc_token($token),
                    'base_price' => $tc->calc_token($token, 'price')->base,
                    'amount' => round($tc->calc_token($token, 'price')->$currency, max_decimal()),
                ];
            } else {
                $trnx_data = [
                    'token' => round($token, min_decimal()),
                    'bonus_on_base' => 0,
                    'bonus_on_token' => 0,
                    'total_bonus' => 0,
                    'total_tokens' => round($token, min_decimal()),
                    'base_price' => $tc->calc_token($token, 'price')->base,
                    'amount' => round($tc->calc_token($token, 'price')->$currency, max_decimal()),
                ];
            }
            $save_data = [
                'created_at' => $added_time,
                'tnx_id' => set_id(rand(100, 999), 'trnx'),
                'tnx_type' => $tnx_type,
                'tnx_time' => ($request->tnx_date) ? _cdate($tnx_date)->toDateTimeString() : $added_time,
                'tokens' => $trnx_data['token'],
                'bonus_on_base' => $trnx_data['bonus_on_base'],
                'bonus_on_token' => $trnx_data['bonus_on_token'],
                'total_bonus' => $trnx_data['total_bonus'],
                'total_tokens' => $trnx_data['total_tokens'],
                'stage' => (int) $request->input('stage', active_stage()->id),
                'user' => (int) $request->input('user'),
                'amount' => $trnx_data['amount'],
                'receive_amount' => $request->input('amount') != '' ? $request->input('amount') : $trnx_data['amount'],
                'receive_currency' => $currency,
                'base_amount' => $trnx_data['base_price'],
                'base_currency' => $base_currency,
                'base_currency_rate' => $base_currency_rate,
                'currency' => $currency,
                'currency_rate' => $currency_rate,
                'all_currency_rate' => $all_currency_rate,
                'payment_method' => $request->input('payment_method', 'manual'),
                'payment_to' => '',
                'payment_id' => rand(1000, 9999),
                'details' => ($tnx_type =='bonus' ? 'Bonus Token' : 'Token Purchase'),
                'status' => 'onhold',
            ];

            $iid = Transaction::insertGetId($save_data);

            if ($iid != null) {
                $ret['msg'] = 'info';
                $ret['message'] = __('messages.trnx.manual.success');

                $address = $request->input('wallet_address');
                $transaction = Transaction::where('id', $iid)->first();
                $transaction->tnx_id = set_id($iid, 'trnx');
                $transaction->wallet_address = $address;
                $transaction->extra = ($address) ? json_encode(['address' => $address]) : null;
                $transaction->status = 'approved';
                $transaction->save();

                IcoStage::token_add_to_account($transaction, 'add');

                $transaction->checked_by = json_encode(['name' => Auth::user()->name, 'id' => Auth::id()]);

                $transaction->added_by = set_added_by(Auth::id(), Auth::user()->role);
                $transaction->checked_time = now();
                $transaction->save();
                // Start adding
                IcoStage::token_add_to_account($transaction, '', 'add');

                $ret['link'] = route('admin.transactions');
                $ret['msg'] = 'success';
                $ret['message'] = __('messages.token.success');
            } else {
                $ret['msg'] = 'error';
                $ret['message'] = __('messages.token.failed');
                Transaction::where('id', $iid)->delete();
            }
        }

        if ($request->ajax()) {
            return response()->json($ret);
        }
        return back()->with([$ret['msg'] => $ret['message']]);
    }

    /**
    * Adjustment modal function for token verified.
    *
    * @param  \Illuminate\Http\Request  $request
    * @return \Illuminate\Http\Response
    * @version 1.0.0
    * @since 1.0
    * @return void
    */

    public function adjustment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tnx_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            if ($validator->errors()->has('tnx_id')) {
                $msg = NioTrans::do('transaction id', $validator->errors()->first());
            } else {
                $msg = __('messages.form.wrong');
            }

            $ret['msg'] = 'warning';
            $ret['message'] = $msg;
        } else {
            $trnx = Transaction::findOrFail($request->tnx_id);
            $ret['modal'] = view('modals.adjustment_token', compact('trnx'))->render();
        }
        if ($request->ajax()) {
            return response()->json($ret);
        }
        return back()->with([$ret['msg'] => $ret['message']]);
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

}
