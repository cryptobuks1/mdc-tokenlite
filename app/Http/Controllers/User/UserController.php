<?php

namespace App\Http\Controllers\User;

/**
 * User Controller
 *
 *
 * @package TokenLite
 * @author Softnio
 * @version 1.0.6
 */
use Auth;
use Validator;
use IcoHandler;
use Carbon\Carbon;
use App\Models\Page;
use App\Models\User;
use App\Models\TokenStaked;
use App\Models\IcoStage;
use App\Models\UserMeta;
use App\Models\Activity;
use App\Helpers\NioTrans;
use App\Helpers\NioModule;
use App\Models\GlobalMeta;
use App\Models\Transaction;
use Illuminate\Http\Request;
use PragmaRX\Google2FA\Google2FA;
use App\Helpers\TokenCalculate as TC;
use App\Notifications\PasswordChange;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use App\Models\MatrixDownline;
use DB;
use CoinpaymentsAPI;
use App\Models\Setting;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');

    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     * @version 1.0.0
     * @since 1.0
     * @return void
     */
    public function index(){
        $this->checkPayments();
        $user = Auth::user();
        $stage = active_stage();
        $contribution = Transaction::user_contribution();
        $tc = new \App\Helpers\TokenCalculate();
        $active_bonus = $tc->get_current_bonus('active');
        $semiannual  = TokenStaked::where('user_id',$user->id)->where('staking_tenure','semiannual')->where('status',1)->sum('token_staked');
        $annual  = TokenStaked::where('user_id',$user->id)->where('staking_tenure','annual')->where('status',1)->sum('token_staked');

      

        return view('user.dashboard', compact('user', 'stage', 'active_bonus', 'contribution','semiannual','annual'));
    }


    /**
     * Show the user account page.
     *
     * @return \Illuminate\Http\Response
     * @version 1.0.0
     * @since 1.0
     * @return void
     */
    public function account() {
        $countries = \IcoHandler::getCountries();
        $user = Auth::user();
        $userMeta = UserMeta::getMeta($user->id);

        $g2fa = new Google2FA();
        $google2fa_secret = $g2fa->generateSecretKey();
        $google2fa = $g2fa->getQRCodeUrl(
            site_info().'-'.$user->name,
            $user->email,
            $google2fa_secret
        );

        return view('user.account', compact('user', 'userMeta','countries', 'google2fa', 'google2fa_secret'));
    }

    /**
     * Show the user account activity page.
     * and Delete Activity
     *
     * @return \Illuminate\Http\Response
     * @version 1.0.0
     * @since 1.0
     * @return void
     */
    public function account_activity()
    {
        $user = Auth::user();
        $activities = Activity::where('user_id', $user->id)->orderBy('created_at', 'DESC')->limit(50)->get();

        return view('user.activity', compact('user', 'activities'));
    }

    /**
     * Show the user account token management page.
     *
     * @return \Illuminate\Http\Response
     * @version 1.0.0
     * @since 1.1.2
     * @return void
     */
    public function mytoken_balance()
    {
        if(gws('user_mytoken_page')!=1) {
            return redirect()->route('user.home');
        }
        $user = Auth::user();
        $token_account = Transaction::user_mytoken('balance');
        $token_stages = Transaction::user_mytoken('stages');
        $user_modules = nio_module()->user_modules();
        $tokenstaked  = TokenStaked::where('user_id',$user->id)->where('status',1)->get();
        return view('user.account-token', compact('user', 'token_account', 'token_stages', 'user_modules','tokenstaked'));
    }




     /**
     * Show the user airdrop page.
     *
     * @return \Illuminate\Http\Response
     * @version 1.0.0
     * @since 1.1.2
     * @return void
     */
    public function airdrop()
    {
        if(gws('user_mytoken_page')!=1) {
            return redirect()->route('user.home');
        }
        $user = Auth::user();
        $token_account = Transaction::user_mytoken('balance');
        $token_stages = Transaction::user_mytoken('stages');
        $user_modules = nio_module()->user_modules();
        return view('user.airdrop', compact('user', 'token_account', 'token_stages', 'user_modules'));
    }

    /**
     * Activity delete
     *
     * @version 1.0.0
     * @since 1.0
     * @return void
     */
    public function account_activity_delete(Request $request)
    {
        $id = $request->input('delete_activity');
        $ret['msg'] = 'info';
        $ret['message'] = "Nothing to do!";

        if ($id !== 'all') {
            $remove = Activity::where('id', $id)->where('user_id', Auth::id())->delete();
        } else {
            $remove = Activity::where('user_id', Auth::id())->delete();
        }
        if ($remove) {
            $ret['msg'] = 'success';
            $ret['message'] = __('messages.delete.delete', ['what'=>'Activity']);
        } else {
            $ret['msg'] = 'danger';
            $ret['message'] = __('messages.form.wrong');
        }
        if ($request->ajax()) {
            return response()->json($ret);
        }
        return back()->with([$ret['msg'] => $ret['message']]);
    }

    /**
     * update the user account page.
     *
     * @return \Illuminate\Http\Response
     * @version 1.2
     * @since 1.0
     * @return void
     */
    public function account_update(Request $request)
    {
        $type = $request->input('action_type');
        $ret['msg'] = 'info';
        $ret['message'] = __('messages.nothing');

        if ($type == 'personal_data') {
            $validator = Validator::make($request->all(), [
                'name' => 'required|min:3',
                'email' => 'required|email',
                'dateOfBirth' => 'required|date_format:"m/d/Y"'
            ]);

            if ($validator->fails()) {
                $msg = '';
                if ($validator->errors()->has('name')) {
                    $msg = NioTrans::do('name', $validator->errors()->first(), ['min' => 3]);
                } elseif ($validator->errors()->has('email')) {
                    $msg = NioTrans::do('email', $validator->errors()->first());
                } elseif ($validator->errors()->has('dateOfBirth')) {
                    $msg = NioTrans::do('date of birth', $validator->errors()->first(), ['format' => 'm/d/Y']);
                } else {
                    $msg = __('messages.form.wrong');
                }

                $ret['msg'] = 'warning';
                $ret['message'] = $msg;
                return response()->json($ret);
            } else {
                $user = User::FindOrFail(Auth::id());
                $user->name = strip_tags($request->input('name'));
                $user->email = $request->input('email');
                $user->mobile = strip_tags($request->input('mobile'));
                $user->dateOfBirth = $request->input('dateOfBirth');
                $user->nationality = strip_tags($request->input('nationality'));
                $user_saved = $user->save();

                if ($user) {
                    $ret['msg'] = 'success';
                    $ret['message'] = __('messages.update.success', ['what' => 'Account']);
                } else {
                    $ret['msg'] = 'danger';
                    $ret['message'] = __('messages.update.warning');
                }
            }
        }
        if ($type == 'wallet') {
            $validator = Validator::make($request->all(), [
                'wallet_name' => 'required',
                'wallet_address' => 'required|min:10'
            ]);

            if ($validator->fails()) {
                $msg = '';
                if ($validator->errors()->has('wallet_name')) {
                    $msg = NioTrans::do('wallet name', $validator->errors()->first());
                } elseif ($validator->errors()->has('wallet_address')) {
                    $msg = NioTrans::do('wallet address', $validator->errors()->first(), ['min' => 10]);
                } else {
                    $msg = __('messages.form.wrong');
                }

                $ret['msg'] = 'warning';
                $ret['message'] = $msg;
                return response()->json($ret);
            } else {
                $is_valid = IcoHandler::validate_address($request->input('wallet_address'), $request->input('wallet_name'));
                if ($is_valid) {
                    $user = User::FindOrFail(Auth::id());
                    $user->walletType = $request->input('wallet_name');
                    $user->walletAddress = $request->input('wallet_address');
                    $user_saved = $user->save();

                    if ($user) {
                        $ret['msg'] = 'success';
                        $ret['message'] = __('messages.update.success', ['what' => 'Wallet']);
                    } else {
                        $ret['msg'] = 'danger';
                        $ret['message'] = __('messages.update.warning');
                    }
                } else {
                    $ret['msg'] = 'warning';
                    $ret['message'] = __('messages.invalid.address');
                }
            }
        }
        if ($type == 'wallet_request') {
            $validator = Validator::make($request->all(), [
                'wallet_name' => 'required',
                'wallet_address' => 'required|min:10'
            ]);

            if ($validator->fails()) {
                $msg = '';
                if ($validator->errors()->has('wallet_name')) {
                    $msg = NioTrans::do('wallet name', $validator->errors()->first());
                } elseif ($validator->errors()->has('wallet_address')) {
                    $msg = NioTrans::do('wallet address', $validator->errors()->first(), ['min' => 10]);
                } else {
                    $msg = __('messages.form.wrong');
                }

                $ret['msg'] = 'warning';
                $ret['message'] = $msg;
                return response()->json($ret);
            } else {
                $is_valid = IcoHandler::validate_address($request->input('wallet_address'), $request->input('wallet_name'));
                if ($is_valid) {
                    $meta_data = ['name' => $request->input('wallet_name'), 'address' => $request->input('wallet_address')];
                    $meta_request = GlobalMeta::save_meta('user_wallet_address_change_request', json_encode($meta_data), auth()->id());

                    if ($meta_request) {
                        $ret['msg'] = 'success';
                        $ret['message'] = __('messages.wallet.change');
                    } else {
                        $ret['msg'] = 'danger';
                        $ret['message'] = __('messages.wallet.failed');
                    }
                } else {
                    $ret['msg'] = 'warning';
                    $ret['message'] = __('messages.invalid.address');
                }
            }
        }
        if ($type == 'notification') {
            $notify_admin = $newsletter = $unusual = 0;

            if (isset($request['notify_admin'])) {
                $notify_admin = 1;
            }
            if (isset($request['newsletter'])) {
                $newsletter = 1;
            }
            if (isset($request['unusual'])) {
                $unusual = 1;
            }

            $user = User::FindOrFail(Auth::id());
            if ($user) {
                $userMeta = UserMeta::where('userId', $user->id)->first();
                if ($userMeta == null) {
                    $userMeta = new UserMeta();
                    $userMeta->userId = $user->id;
                }
                $userMeta->notify_admin = $notify_admin;
                $userMeta->newsletter = $newsletter;
                $userMeta->unusual = $unusual;
                $userMeta->save();
                $ret['msg'] = 'success';
                $ret['message'] = __('messages.update.success', ['what' => 'Notification']);
            } else {
                $ret['msg'] = 'danger';
                $ret['message'] = __('messages.update.warning');
            }
        }
        if ($type == 'security') {
            $save_activity = $mail_pwd = 'FALSE';

            if (isset($request['save_activity'])) {
                $save_activity = 'TRUE';
            }
            if (isset($request['mail_pwd'])) {
                $mail_pwd = 'TRUE';
            }

            $user = User::FindOrFail(Auth::id());
            if ($user) {
                $userMeta = UserMeta::where('userId', $user->id)->first();
                if ($userMeta == null) {
                    $userMeta = new UserMeta();
                    $userMeta->userId = $user->id;
                }
                $userMeta->pwd_chng = $mail_pwd;
                $userMeta->save_activity = $save_activity;
                $userMeta->save();
                $ret['msg'] = 'success';
                $ret['message'] = __('messages.update.success', ['what' => 'Security']);
            } else {
                $ret['msg'] = 'danger';
                $ret['message'] = __('messages.update.warning');
            }
        }
        if ($type == 'account_setting') {
            $notify_admin = $newsletter = $unusual = 0;
            $save_activity = $mail_pwd = 'FALSE';
            $user = User::FindOrFail(Auth::id());

            if (isset($request['save_activity'])) {
                $save_activity = 'TRUE';
            }
            if (isset($request['mail_pwd'])) {
                $mail_pwd = 'TRUE';
            }

            $mail_pwd = 'TRUE'; //by default true
            if (isset($request['notify_admin'])) {
                $notify_admin = 1;
            }
            if (isset($request['newsletter'])) {
                $newsletter = 1;
            }
            if (isset($request['unusual'])) {
                $unusual = 1;
            }


            if ($user) {
                $userMeta = UserMeta::where('userId', $user->id)->first();
                if ($userMeta == null) {
                    $userMeta = new UserMeta();
                    $userMeta->userId = $user->id;
                }

                $userMeta->notify_admin = $notify_admin;
                $userMeta->newsletter = $newsletter;
                $userMeta->unusual = $unusual;

                $userMeta->pwd_chng = $mail_pwd;
                $userMeta->save_activity = $save_activity;

                $userMeta->save();
                $ret['msg'] = 'success';
                $ret['message'] = __('messages.update.success', ['what' => 'Account Settings']);
            } else {
                $ret['msg'] = 'danger';
                $ret['message'] = __('messages.update.warning');
            }
        }
        if ($type == 'pwd_change') {
            //validate data
            $validator = Validator::make($request->all(), [
                'old-password' => 'required|min:6',
                'new-password' => 'required|min:6',
                're-password' => 'required|min:6|same:new-password',
            ]);
            if ($validator->fails()) {
                $msg = '';
                if ($validator->errors()->has('old-password')) {
                    $msg = NioTrans::do('old password', $validator->errors()->first(), ['min' => 6]);
                } elseif ($validator->errors()->has('new-password')) {
                    $msg = NioTrans::do('new password', $validator->errors()->first(), ['min' => 6]);
                } elseif ($validator->errors()->has('re-password')) {
                    $msg = NioTrans::do('confirm password', $validator->errors()->first(), ['other' => 'new password']);
                } else {
                    $msg = __('messages.form.wrong');
                }

                $ret['msg'] = 'warning';
                $ret['message'] = $msg;
                return response()->json($ret);
            } else {
                $user = Auth::user();
                if ($user) {
                    if (! Hash::check($request->input('old-password'), $user->password)) {
                        $ret['msg'] = 'warning';
                        $ret['message'] = __('messages.password.old_err');
                    } else {
                        $userMeta = UserMeta::where('userId', $user->id)->first();
                        $userMeta->pwd_temp = Hash::make($request->input('new-password'));
                        $cd = Carbon::now();
                        $userMeta->email_expire = $cd->copy()->addMinutes(60);
                        $userMeta->email_token = str_random(65);
                        if ($userMeta->save()) {
                            try {
                                $user->notify(new PasswordChange($user, $userMeta));
                                $ret['msg'] = 'success';
                                $ret['message'] = __('messages.password.changed');
                            } catch (\Exception $e) {
                                $ret['msg'] = 'warning';
                                $ret['message'] = __('messages.email.password_change',['email' => get_setting('site_email')]);
                            }
                        } else {
                            $ret['msg'] = 'danger';
                            $ret['message'] = __('messages.form.wrong');
                        }
                    }
                } else {
                    $ret['msg'] = 'danger';
                    $ret['message'] = __('messages.form.wrong');
                }
            }
        }
        if($type == 'google2fa_setup'){
            $google2fa = $request->input('google2fa', 0);
            $user = User::FindOrFail(Auth::id());
            if($user){
                // Google 2FA
                $ret['link'] = route('user.account');
                if(!empty($request->google2fa_code)){
                    $g2fa = new Google2FA();
                    if($google2fa == 1){
                        $verify = $g2fa->verifyKey($request->google2fa_secret, $request->google2fa_code);
                    }else{
                        $verify = $g2fa->verify($request->google2fa_code, $user->google2fa_secret);
                    }

                    if($verify){
                        $user->google2fa = $google2fa;
                        $user->google2fa_secret = ($google2fa == 1 ? $request->google2fa_secret : null);
                        $user->save();
                        $ret['msg'] = 'success';
                        $ret['message'] = __('Successfully '.($google2fa == 1 ? 'enable' : 'disable').' 2FA security in your account.');
                    }else{
                        $ret['msg'] = 'error';
                        $ret['message'] = __('You have provide a invalid 2FA authentication code!');
                    }
                }else{
                    $ret['msg'] = 'warning';
                    $ret['message'] = __('Please enter a valid authentication code!');
                }
            }
        }

        if ($request->ajax()) {
            return response()->json($ret);
        }
        return back()->with([$ret['msg'] => $ret['message']]);
    }

    public function password_confirm($token)
    {
        $user = Auth::user();
        $userMeta = UserMeta::where('userId', $user->id)->first();
        if ($token == $userMeta->email_token) {
            if (_date($userMeta->email_expire, 'Y-m-d H:i:s') >= date('Y-m-d H:i:s')) {
                $user->password = $userMeta->pwd_temp;
                $user->save();
                $userMeta->pwd_temp = null;
                $userMeta->email_token = null;
                $userMeta->email_expire = null;
                $userMeta->save();

                $ret['msg'] = 'success';
                $ret['message'] = __('messages.password.success');
            } else {
                $ret['msg'] = 'danger';
                $ret['message'] = __('messages.password.failed');
            }
        } else {
            $ret['msg'] = 'danger';
            $ret['message'] = __('messages.password.token');
        }

        return redirect()->route('user.account')->with([$ret['msg'] => $ret['message']]);
    }

    /**
     * Get pay now form
     *
     * @version 1.0.0
     * @since 1.0
     * @return void
     */
    public function get_wallet_form(Request $request)
    {
        return view('modals.user_wallet')->render();
    }

    /**
     * Show the user Referral page
     *
     * @version 1.0.0
     * @since 1.0.3
     * @return void
     */
    public function referral()
    {
        $page = Page::where('slug', 'referral')->where('status', 'active')->first();
        $reffered = User::where('referral', auth()->id())->get();
        if(get_page('referral', 'status') == 'active'){
            return view('user.referral', compact('page', 'reffered'));
        }else{
            abort(404);
        }
    }

    public function referrals(Request $request) {
        
      

        $role_data  = '';
        $per_page   = gmvl('user_per_page', 10);
        $order_by   = (gmvl('user_order_by', 'id')=='token') ? 'tokenBalance' : gmvl('user_order_by', 'id');
        $ordered    = gmvl('user_ordered', 'DESC');

        $is_page    = (empty($role) ? 'all' : ($role=='user' ? 'investor' : $role));

        $users = DB::table('matrix_downlines')
                    ->select('users.*','matrix_downlines.level')
                    ->join('users', 'matrix_downlines.downline_id', '=', 'users.id')
                    ->where('upline_id',auth()->user()->id)
                    ->paginate($per_page);
       
            // $users = User::whereNotIn('status', ['deleted'])->orderBy($order_by, $ordered)->paginate($per_page);
        

        if($request->s){
           
                $users = DB::table('matrix_downlines')
                        ->select('users.*','matrix_downlines.level')
                        ->join('users', 'matrix_downlines.downline_id', '=', 'users.id')
                        ->where('upline_id',auth()->user()->id)
                        ->where(function($q) use ($request){
                            $id_num = (int)(str_replace(config('icoapp.user_prefix'), '', $request->s));
                            $q->orWhere('users.id', $id_num)->orWhere('users.email', 'like', '%'.$request->s.'%')->orWhere('users.name', 'like', '%'.$request->s.'%');
                        })
                        ->paginate($per_page);
        }

       
        $pagi = $users->appends(request()->all());
        return view('user.referrals', compact('users', 'role_data', 'is_page', 'pagi'));
        
      

    }

    public function checkPayments(){
 

        $transactionStatus = Transaction::where('user', Auth::id())->whereIn('status',['onhold','pending'])->get();
      
        if(!$transactionStatus->isEmpty()) {
          
          try {
                $cps_api = new CoinpaymentsAPI(env('COINPAYMENT_PRIVATE_KEY'), env('COINPAYMENT_PUBLIC_KEY'), 'json');
               // $information = $cps_api->GetDepositAddress('bnb');
            } catch (Exception $e) {
                //echo 'Error: ' . $e->getMessage();
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
public function airdropPage() {
    
    $check = User::find(Auth::id());
        if ($check && !isset($check->kyc_info->status)) {
                return redirect(route('user.kyc'))->with(['warning' => __('messages.kyc.mandatory')]);
        } else {
                if ($check->kyc_info->status != 'approved') {
                    return redirect(route('user.kyc.application'))->with(['warning' => __('messages.kyc.mandatory')]);
                }
        }
        return view('public.airdrop');
    }

    public function searchUser(Request $request){

        $receiver = $request->input('transferto');

        $receiver_id_or_email = (is_numeric($receiver)) ? intval($receiver) : $receiver;

        $user = User::where('id',$receiver_id_or_email)->orWhere('walletAddress',$receiver_id_or_email)->first();
        $receiptient_info = ['userid' => 0] ;
        if($user) {
            $receiptient_info = [
                                    'userid' => $user->id ,
                                    'email'  => $user->email,
                                    'name'  => $user->name
                                ]   ;
        }

        return response()->json($receiptient_info);
    }

}
