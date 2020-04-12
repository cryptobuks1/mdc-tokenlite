<?php

namespace App\Http\Controllers\Auth;
/**
 * Register Controller
 *
 * @package TokenLite
 * @author Softnio
 * @version 1.1.2
 */

use Cookie;
use Carbon\Carbon;
use App\Models\User;
use App\Models\MatrixDownline;
use App\Models\UserMeta;
use App\Models\Referral;
use App\Notifications\ConfirmEmail;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Auth\RegistersUsers;
use DB;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
     */

    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     * @version 1.0.0
     */
    protected $redirectTo = '/register/success';

    /**
     * Create a new controller instance.
     *
     * @version 1.0.0
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    public function showRegistrationForm()
    {
        if (application_installed(true) == false) {
            return redirect(url('/install'));
        }
        return view('auth.register');
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @version 1.0.1
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        $term = get_page('terms', 'status') == 'active' ? 'required' : 'nullable';
        return Validator::make($data, [
            'name' => ['required', 'string', 'min:3', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'terms' => [$term],
        ], [
            'terms.required' => __('messages.agree'),
            'email.unique' => 'The email address you have entered is already registered. Did you <a href="' . route('password.request') . '">forget your login</a> information?',
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @version 1.2.1
     * @since 1.0.0
     * @return \App\Models\User
     */
    protected function create(array $data)
    {

        $walletInfo = $this->createWallet() ;

      
        $have_user = User::where('role', 'admin')->count();
        $type = ($have_user >= 1) ? 'user' : 'admin';
        $email_verified = ($have_user >= 1) ? null : now();
        $user = User::create([
            'name' => strip_tags($data['name']),
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'lastLogin' => date('Y-m-d H:i:s'),
            'role' => $type,
            'walletAddress' => '0x'.$walletInfo['address'] ,
            'prev_token'   => $walletInfo['private'],
            'pub_token'   => $walletInfo['public']
        ]);
        if ($user) {
            if ($have_user <= 0) {
                save_gmeta('site_super_admin', 1, $user->id);
            }
            $user->email_verified_at = $email_verified;
            $refer_blank = true;
            if(is_active_referral_system()) {
                if (Cookie::has('ico_nio_ref_by')) {
                    $ref_id = (int) Cookie::get('ico_nio_ref_by');
                    $ref_user = User::where('id', $ref_id)->where('email_verified_at', '!=', null)->first();
                    if ($ref_user) {
                        $user->referral = $ref_user->id;
                        $user->referralInfo = json_encode([
                            'user' => $ref_user->id,
                            'name' => $ref_user->name,
                            'time' => now(),
                        ]);
                        $refer_blank = false;
                        $this->create_referral_or_not($user->id, $ref_user->id);
                        $this->create_downlines($ref_user->id,$user->id);
                        Cookie::queue(Cookie::forget('ico_nio_ref_by'));
                     
                    }
                }
            }
            if($user->role=='user' && $refer_blank==true) {
                $this->create_referral_or_not($user->id,2);
            }
            
            $user->save();
            $meta = UserMeta::create([ 'userId' => $user->id ]);

            $meta->notify_admin = ($type=='user')?0:1;
            $meta->email_token = str_random(65);
            $cd = Carbon::now(); //->toDateTimeString();
            $meta->email_expire = $cd->copy()->addMinutes(75);
            $meta->save();

            if ($user->email_verified_at == null) {
                try {
                    $user->notify(new ConfirmEmail($user));
                } catch (\Exception $e) {
                    session('warning', 'User registered successfully, but we unable to send confirmation email!');
                }
            }
        }
        return $user;
    }

    public function createWallet() {

        $token = "3008c047d491485ea8214f05392bb96b";

        $fields_string = "";
        $url = 'https://api.blockcypher.com/v1/eth/main/addrs';
        $fields = array(
            'token' => $token,
        );

            //url-ify the data for the POST
            foreach($fields as $key=>$value) { $fields_string .= $key.'='.$value.'&'; }
            rtrim($fields_string, '&');

            $options = array(
                CURLOPT_RETURNTRANSFER => true,     // return web page
                CURLOPT_HEADER         => false,    // don't return headers
                CURLOPT_FOLLOWLOCATION => true,     // follow redirects
                CURLOPT_ENCODING       => "",       // handle all encodings
                CURLOPT_USERAGENT      => "spider", // who am i
                CURLOPT_AUTOREFERER    => true,     // set referer on redirect
                CURLOPT_CONNECTTIMEOUT => 120,      // timeout on connect
                CURLOPT_TIMEOUT        => 120,      // timeout on response
                CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
                CURLOPT_SSL_VERIFYPEER => false,     // Disabled SSL Cert checks
                CURLOPT_POST            => 1 ,
                CURLOPT_POSTFIELDS      =>  $fields_string
            );
    
            $ch      = curl_init( $url );
            curl_setopt_array( $ch, $options );
            $result = curl_exec( $ch );
            $err     = curl_errno( $ch );
            $errmsg  = curl_error( $ch );
            $header  = curl_getinfo( $ch );
            curl_close( $ch );
    

            
            return json_decode($result,true);
    }

    /**
     * Create user in referral table.
     *
     * @param  $user, $refer
     * @version 1.0
     * @since 1.1.2
     * @return \App\Models\User
     */
    protected function create_referral_or_not($user, $refer=0) {
        Referral::create([ 'user_id' => $user, 'user_bonus' => 0, 'refer_by' => $refer, 'refer_bonus' => 0 ]);
    }

    public function create_downlines($upline_id,$downline_id) {


            $upline     =   $upline_id ;			
            $level      =   1 ;
            $downline_data = [] ;
            while($level != 4 ){
                    if( $upline != 1){
                        array_push($downline_data,[
                            'downline_id'   => $downline_id,
                            'upline_id'     => $upline,
                            'level'         => $level
                        ]);
                           if(!empty($upline)) {  
                               $matrix_downline = new MatrixDownline() ;
                               $matrix_downline->downline_id  = $downline_id ;
                               $matrix_downline->upline_id    = $upline ;
                               $matrix_downline->level        = $level ;
                               $matrix_downline->save() ;
                                $fetchupline = DB::table('users')->where('id',$upline)->first(['referral']);
                                $upline=$fetchupline->referral;
                                $level++;
                            } 
                            else {
                                $level = 4 ;
                                $upline = 1;
                            }
                        }
                }
               
    }
}
