<?php

namespace Acelle\Http\Controllers\Auth;

use Acelle\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Acelle\Model\Setting;
use DB;
use Acelle\Model\Plan;
use Acelle\Cashier\Cashier;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest', ['except' => 'logout']);
    }
    
    /**
     * Validate the user login request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    protected function validateLogin(\Illuminate\Http\Request $request)
    {
        $rules = [
            $this->username() => 'required',
            'password' => 'required'
        ];

        if (Setting::isYes('login_recaptcha') && !Setting::isYes('theme.beta')) {
            if (!\Acelle\Library\Tool::checkReCaptcha($request)) {
                $rules['recaptcha_invalid'] = 'required';
            }
        }

        $this->validate($request, $rules);
    }

    public function authenticated($request, $user)
    {
        // If user is not activated
        if (!$user->activated) {
            $uid = $user->uid;
            auth()->logout();
            return view('notActivated', ['uid' => $uid]);
        }
           /* DB::table('fields')->insert(array('uid'=>uniqid(),'mail_list_id'=>9,'label'=>  'NAME',   'type'=>'text','tag'=>'NAME','visible'=>1,'required'=>0,'custom_order'=>1));
            DB::table('fields')->insert(array('uid'=>uniqid(),'mail_list_id'=>9,'label'=>  'STORE NAME',   'type'=>'text','tag'=>'STORE_NAME','visible'=>1,'required'=>0,'custom_order'=>2));
            DB::table('fields')->insert(array('uid'=>uniqid(),'mail_list_id'=>9,'label'=>  'STORE URL',   'type'=>'text','tag'=>'STORE_URL','visible'=>1,'required'=>0,'custom_order'=>3));
            DB::table('fields')->insert(array('uid'=>uniqid(),'mail_list_id'=>9,'label'=>  'PLAN',   'type'=>'text','tag'=>'PLAN','visible'=>1,'required'=>0,'custom_order'=>4));
            DB::table('fields')->insert(array('uid'=>uniqid(),'mail_list_id'=>9,'label'=>  'TOTAL ORDERS',   'type'=>'text','tag'=>'TOTAL_ORDERS','visible'=>1,'required'=>0,'custom_order'=>5));
            DB::table('fields')->insert(array('uid'=>uniqid(),'mail_list_id'=>9,'label'=>  'PHONE',   'type'=>'text','tag'=>'PHONE','visible'=>1,'required'=>0,'custom_order'=>6));
            DB::table('fields')->insert(array('uid'=>uniqid(),'mail_list_id'=>9,'label'=>  'COUNTRY',   'type'=>'text','tag'=>'COUNTRY','visible'=>1,'required'=>0,'custom_order'=>7));
            DB::table('fields')->insert(array('uid'=>uniqid(),'mail_list_id'=>9,'label'=>  'ADDRESS',   'type'=>'text','tag'=>'ADDRESS','visible'=>1,'required'=>0,'custom_order'=>8));
            DB::table('fields')->insert(array('uid'=>uniqid(),'mail_list_id'=>9,'label'=>  'STATUS',   'type'=>'text','tag'=>'STATUS','visible'=>1,'required'=>0,'custom_order'=>9));
            DB::table('fields')->insert(array('uid'=>uniqid(),'mail_list_id'=>9,'label'=>  'DOMAIN',   'type'=>'text','tag'=>'DOMAIN','visible'=>1,'required'=>0,'custom_order'=>10));
            DB::table('fields')->insert(array('uid'=>uniqid(),'mail_list_id'=>9,'label'=>  'PROVINCE',   'type'=>'text','tag'=>'PROVINCE','visible'=>1,'required'=>0,'custom_order'=>11));
            DB::table('fields')->insert(array('uid'=>uniqid(),'mail_list_id'=>9,'label'=>  'CITY',   'type'=>'text','tag'=>'CITY','visible'=>1,'required'=>0,'custom_order'=>12));
            DB::table('fields')->insert(array('uid'=>uniqid(),'mail_list_id'=>9,'label'=>  'PRIMARY LOCALE',   'type'=>'text','tag'=>'PRIMARY_LOCALE','visible'=>1,'required'=>0,'custom_order'=>13));
            DB::table('fields')->insert(array('uid'=>uniqid(),'mail_list_id'=>9,'label'=>  'CREATED AT',   'type'=>'text','tag'=>'CREATED_AT','visible'=>1,'required'=>0,'custom_order'=>14));
            DB::table('fields')->insert(array('uid'=>uniqid(),'mail_list_id'=>9,'label'=>  'CURRENCY',   'type'=>'text','tag'=>'CURRENCY','visible'=>1,'required'=>0,'custom_order'=>15));
            DB::table('fields')->insert(array('uid'=>uniqid(),'mail_list_id'=>9,'label'=>  'TIME ZONE',   'type'=>'text','tag'=>'TIME_ZONE','visible'=>1,'required'=>0,'custom_order'=>16));
            DB::table('fields')->insert(array('uid'=>uniqid(),'mail_list_id'=>9,'label'=>  'SHOP OWNER',   'type'=>'text','tag'=>'SHOP_OWNER','visible'=>1,'required'=>0,'custom_order'=>17));
            DB::table('fields')->insert(array('uid'=>uniqid(),'mail_list_id'=>9,'label'=>  'MYSHOPIFY DOMAIN',   'type'=>'text','tag'=>'MYSHOPIFY_DOMAIN','visible'=>1,'required'=>0,'custom_order'=>18));
            DB::table('fields')->insert(array('uid'=>uniqid(),'mail_list_id'=>9,'label'=>  'PLAN DISPLAY NAME',   'type'=>'text','tag'=>'PLAN_DISPLAY_NAME','visible'=>1,'required'=>0,'custom_order'=>19));

echo "done";die;*/

         if(empty($user->store_name))
        {
            $plan = Plan::findByUid('58bd48f91fcab');
            $gateway = Cashier::getPaymentGateway();
            //echo "<pre>"; print_r($plan);die;
            // Create subscription
            $subscription = $gateway->create($user->customer, $plan);
            if(!empty($request->store_name)){
                DB::table("users")->where("id",$user->id)->update(array('store_name'=>$request->store_name,'list_created'=>1));
                $customer=DB::table("customers")->where("user_id",$user->id)->first();
                $data=DB::table("saveJson")->where("store_name",$request->store_name)->first();
                $shopp=json_decode($data->json);
                $mister_list_array=array(
                                        'name'=>'All',
                                        'from_email'=>$user->email,
                                        'from_name'=>$customer->first_name.' '.$customer->last_name,
                                        'default_subject'=>'Mister Mail',
                                        'contact[company]'=>$shopp->shop->name,
                                        'contact[state]'=>$shopp->shop->city,
                                        'contact[address_1]'=>$shopp->shop->address1,
                                        'contact[address_2]'=>$shopp->shop->address2,
                                        'contact[city]'=>$shopp->shop->city,
                                        'contact[zip]'=>$shopp->shop->zip,
                                        'contact[phone]'=>$shopp->shop->phone,
                                        'contact[country_id]'=>1,
                                        'contact[email]'=>$user->email,
                                        'subscribe_confirmation'=>0,
                                        'send_welcome_email'=>0,
                                        'unsubscribe_notification'=>0
                                        );
                $url_listt='https://app.themistermail.com/api/v1/lists?api_token='.$user->api_token;
                $chh = curl_init();
                curl_setopt($chh, CURLOPT_URL,$url_listt);
                curl_setopt($chh, CURLOPT_POST, 1);
                curl_setopt($chh, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($chh, CURLOPT_POSTFIELDS,$mister_list_array);
                $server_output = curl_exec($chh);
                $result=json_decode($server_output);
                $mailList=DB::table('mail_lists')->where('uid',$result->list_uid)->first();
                //echo "<pre>"; print_r($mailList);die;
                DB::table('fields')->insert(array('uid'=>uniqid(),'mail_list_id'=>$mailList->id,'label'=>'Status','type'=>'text','tag'=>'STATUS','visible'=>1,'required'=>0,'custom_order'=>0));
                DB::table('fields')->insert(array('uid'=>uniqid(),'mail_list_id'=>$mailList->id,'label'=>'Created At','type'=>'text','tag'=>'CREATED_AT','visible'=>1,'required'=>0,'custom_order'=>1));
                DB::table('fields')->insert(array('uid'=>uniqid(),'mail_list_id'=>$mailList->id,'label'=>'Orders Count','type'=>'text','tag'=>'ORDERS_COUNT','visible'=>1,'required'=>0,'custom_order'=>2));
                DB::table('fields')->insert(array('uid'=>uniqid(),'mail_list_id'=>$mailList->id,'label'=>'Total Spent','type'=>'text','tag'=>'TOTAL_SPENT','visible'=>1,'required'=>0,'custom_order'=>3));
                DB::table('fields')->insert(array('uid'=>uniqid(),'mail_list_id'=>$mailList->id,'label'=>'Last Order Id','type'=>'text','tag'=>'LAST_ORDER_ID','visible'=>1,'required'=>0,'custom_order'=>4));
                DB::table('fields')->insert(array('uid'=>uniqid(),'mail_list_id'=>$mailList->id,'label'=>'Phone','type'=>'text','tag'=>'PHONE','visible'=>1,'required'=>0,'custom_order'=>5));
                DB::table('fields')->insert(array('uid'=>uniqid(),'mail_list_id'=>$mailList->id,'label'=>'Address','type'=>'text','tag'=>'ADDRESS','visible'=>1,'required'=>0,'custom_order'=>6));
                DB::table('fields')->insert(array('uid'=>uniqid(),'mail_list_id'=>$mailList->id,'label'=>'City','type'=>'text','tag'=>'CITY','visible'=>1,'required'=>0,'custom_order'=>7));
                DB::table('fields')->insert(array('uid'=>uniqid(),'mail_list_id'=>$mailList->id,'label'=>'Country','type'=>'text','tag'=>'COUNTRY','visible'=>1,'required'=>0,'custom_order'=>8));
                DB::table('fields')->insert(array('uid'=>uniqid(),'mail_list_id'=>$mailList->id,'label'=>'Zip','type'=>'text','tag'=>'ZIP','visible'=>1,'required'=>0,'custom_order'=>9));
                DB::table('fields')->insert(array('uid'=>uniqid(),'mail_list_id'=>$mailList->id,'label'=>'Welcome Points','type'=>'text','tag'=>'WELCOME_POINTS','visible'=>1,'required'=>0,'custom_order'=>10));
                DB::table('fields')->insert(array('uid'=>uniqid(),'mail_list_id'=>$mailList->id,'label'=>'Last Points Earn','type'=>'text','tag'=>'POINTS_EARN','visible'=>1,'required'=>0,'custom_order'=>11));
                DB::table('fields')->insert(array('uid'=>uniqid(),'mail_list_id'=>$mailList->id,'label'=>'Total Points','type'=>'text','tag'=>'TOTAL_POINTS','visible'=>1,'required'=>0,'custom_order'=>12));
                DB::table('fields')->insert(array('uid'=>uniqid(),'mail_list_id'=>$mailList->id,'label'=>'Anniversary','type'=>'text','tag'=>'ANNIVERSARY','visible'=>1,'required'=>0,'custom_order'=>13));
                DB::table('fields')->insert(array('uid'=>uniqid(),'mail_list_id'=>$mailList->id,'label'=>'Birthday','type'=>'text','tag'=>'BIRTHDAY','visible'=>1,'required'=>0,'custom_order'=>14));
                DB::table('fields')->insert(array('uid'=>uniqid(),'mail_list_id'=>$mailList->id,'label'=>'Points Redeem','type'=>'text','tag'=>'POINTS_REDEEM','visible'=>1,'required'=>0,'custom_order'=>15));
                DB::table('fields')->insert(array('uid'=>uniqid(),'mail_list_id'=>$mailList->id,'label'=>'Vip Status','type'=>'text','tag'=>'VIP_STATUS','visible'=>1,'required'=>0,'custom_order'=>16));
                DB::table('fields')->insert(array('uid'=>uniqid(),'mail_list_id'=>$mailList->id,'label'=>'Order1 Title','type'=>'text','tag'=>'ORDER1_TITLE','visible'=>1,'required'=>0,'custom_order'=>17));
                DB::table('fields')->insert(array('uid'=>uniqid(),'mail_list_id'=>$mailList->id,'label'=>'Order1 Image','type'=>'text','tag'=>'ORDER1_IMAGE','visible'=>1,'required'=>0,'custom_order'=>18));
                DB::table('fields')->insert(array('uid'=>uniqid(),'mail_list_id'=>$mailList->id,'label'=>'Order2 Title','type'=>'text','tag'=>'ORDER2_TITLE','visible'=>1,'required'=>0,'custom_order'=>19));
                DB::table('fields')->insert(array('uid'=>uniqid(),'mail_list_id'=>$mailList->id,'label'=>'Order2 Image','type'=>'text','tag'=>'ORDER2_IMAGE','visible'=>1,'required'=>0,'custom_order'=>20));
                DB::table('fields')->insert(array('uid'=>uniqid(),'mail_list_id'=>$mailList->id,'label'=>'Order3 Title','type'=>'text','tag'=>'ORDER3_TITLE','visible'=>1,'required'=>0,'custom_order'=>21));
                DB::table('fields')->insert(array('uid'=>uniqid(),'mail_list_id'=>$mailList->id,'label'=>'Order3 Image','type'=>'text','tag'=>'ORDER3_IMAGE','visible'=>1,'required'=>0,'custom_order'=>22));
                DB::table('fields')->insert(array('uid'=>uniqid(),'mail_list_id'=>$mailList->id,'label'=>'Order4 Title','type'=>'text','tag'=>'ORDER4_TITLE','visible'=>1,'required'=>0,'custom_order'=>23));
                DB::table('fields')->insert(array('uid'=>uniqid(),'mail_list_id'=>$mailList->id,'label'=>'Order4 Image','type'=>'text','tag'=>'ORDER4_IMAGE','visible'=>1,'required'=>0,'custom_order'=>24));
                DB::table('fields')->insert(array('uid'=>uniqid(),'mail_list_id'=>$mailList->id,'label'=>'Order5 Title','type'=>'text','tag'=>'ORDER5_TITLE','visible'=>1,'required'=>0,'custom_order'=>25));
                DB::table('fields')->insert(array('uid'=>uniqid(),'mail_list_id'=>$mailList->id,'label'=>'Order5 Image','type'=>'text','tag'=>'ORDER5_IMAGE','visible'=>1,'required'=>0,'custom_order'=>26));
                DB::table('fields')->insert(array('uid'=>uniqid(),'mail_list_id'=>$mailList->id,'label'=>'ip address','type'=>'text','tag'=>'IP_ADDR','visible'=>1,'required'=>0,'custom_order'=>27));
                DB::table('fields')->insert(array('uid'=>uniqid(),'mail_list_id'=>$mailList->id,'label'=>'product1_title','type'=>'text','tag'=>'PRODUCT1_TITLE','visible'=>1,'required'=>0,'custom_order'=>28));
                DB::table('fields')->insert(array('uid'=>uniqid(),'mail_list_id'=>$mailList->id,'label'=>'product1_image','type'=>'text','tag'=>'PRODUCT1_IMAGE','visible'=>1,'required'=>0,'custom_order'=>29));
                DB::table('fields')->insert(array('uid'=>uniqid(),'mail_list_id'=>$mailList->id,'label'=>'product1_date','type'=>'text','tag'=>'PRODUCT1_DATE','visible'=>1,'required'=>0,'custom_order'=>30));
                DB::table('fields')->insert(array('uid'=>uniqid(),'mail_list_id'=>$mailList->id,'label'=>'product2_title','type'=>'text','tag'=>'PRODUCT2_TITLE','visible'=>1,'required'=>0,'custom_order'=>31));
                DB::table('fields')->insert(array('uid'=>uniqid(),'mail_list_id'=>$mailList->id,'label'=>'product2_image','type'=>'text','tag'=>'PRODUCT2_IMAGE','visible'=>1,'required'=>0,'custom_order'=>32));
                DB::table('fields')->insert(array('uid'=>uniqid(),'mail_list_id'=>$mailList->id,'label'=>'product2_date','type'=>'text','tag'=>'PRODUCT2_DATE','visible'=>1,'required'=>0,'custom_order'=>33));
                DB::table('fields')->insert(array('uid'=>uniqid(),'mail_list_id'=>$mailList->id,'label'=>'product3_title','type'=>'text','tag'=>'PRODUCT3_TITLE','visible'=>1,'required'=>0,'custom_order'=>34));
                DB::table('fields')->insert(array('uid'=>uniqid(),'mail_list_id'=>$mailList->id,'label'=>'product3_image','type'=>'text','tag'=>'PRODUCT3_IMAGE','visible'=>1,'required'=>0,'custom_order'=>35));
                DB::table('fields')->insert(array('uid'=>uniqid(),'mail_list_id'=>$mailList->id,'label'=>'product3_date','type'=>'text','tag'=>'PRODUCT3_DATE','visible'=>1,'required'=>0,'custom_order'=>36));
                DB::table('fields')->insert(array('uid'=>uniqid(),'mail_list_id'=>$mailList->id,'label'=>'product4_title','type'=>'text','tag'=>'PRODUCT4_TITLE','visible'=>1,'required'=>0,'custom_order'=>37));
                DB::table('fields')->insert(array('uid'=>uniqid(),'mail_list_id'=>$mailList->id,'label'=>'product4_image','type'=>'text','tag'=>'PRODUCT4_IMAGE','visible'=>1,'required'=>0,'custom_order'=>38));
                DB::table('fields')->insert(array('uid'=>uniqid(),'mail_list_id'=>$mailList->id,'label'=>'product4_date','type'=>'text','tag'=>'PRODUCT4_DATE','visible'=>1,'required'=>0,'custom_order'=>39));
                DB::table('fields')->insert(array('uid'=>uniqid(),'mail_list_id'=>$mailList->id,'label'=>'product5_title','type'=>'text','tag'=>'PRODUCT5_TITLE','visible'=>1,'required'=>0,'custom_order'=>40));
                DB::table('fields')->insert(array('uid'=>uniqid(),'mail_list_id'=>$mailList->id,'label'=>'product5_image','type'=>'text','tag'=>'PRODUCT5_IMAGE','visible'=>1,'required'=>0,'custom_order'=>41));
                DB::table('fields')->insert(array('uid'=>uniqid(),'mail_list_id'=>$mailList->id,'label'=>'product5_date','type'=>'text','tag'=>'PRODUCT5_DATE','visible'=>1,'required'=>0,'custom_order'=>42));
                DB::table('fields')->insert(array('uid'=>uniqid(),'mail_list_id'=>$mailList->id,'label'=>'cart1 title','type'=>'text','tag'=>'CART1_TITLE','visible'=>1,'required'=>0,'custom_order'=>43));
                DB::table('fields')->insert(array('uid'=>uniqid(),'mail_list_id'=>$mailList->id,'label'=>'cart1 quantity','type'=>'text','tag'=>'CART1_QUANTITY','visible'=>1,'required'=>0,'custom_order'=>44));
                DB::table('fields')->insert(array('uid'=>uniqid(),'mail_list_id'=>$mailList->id,'label'=>'cart1 image','type'=>'text','tag'=>'CART1_IMAGE','visible'=>1,'required'=>0,'custom_order'=>45));
                DB::table('fields')->insert(array('uid'=>uniqid(),'mail_list_id'=>$mailList->id,'label'=>'cart2 title','type'=>'text','tag'=>'CART2_TITLE','visible'=>1,'required'=>0,'custom_order'=>46));
                DB::table('fields')->insert(array('uid'=>uniqid(),'mail_list_id'=>$mailList->id,'label'=>'cart2 quantity','type'=>'text','tag'=>'CART2_QUANTITY','visible'=>1,'required'=>0,'custom_order'=>47));
                DB::table('fields')->insert(array('uid'=>uniqid(),'mail_list_id'=>$mailList->id,'label'=>'cart2 image','type'=>'text','tag'=>'CART2_IMAGE','visible'=>1,'required'=>0,'custom_order'=>48));
                DB::table('fields')->insert(array('uid'=>uniqid(),'mail_list_id'=>$mailList->id,'label'=>'cart3 title','type'=>'text','tag'=>'CART3_TITLE','visible'=>1,'required'=>0,'custom_order'=>49));
                DB::table('fields')->insert(array('uid'=>uniqid(),'mail_list_id'=>$mailList->id,'label'=>'cart3 quantity','type'=>'text','tag'=>'CART3_QUANTITY','visible'=>1,'required'=>0,'custom_order'=>50));
                DB::table('fields')->insert(array('uid'=>uniqid(),'mail_list_id'=>$mailList->id,'label'=>'cart3 image','type'=>'text','tag'=>'CART3_IMAGE','visible'=>1,'required'=>0,'custom_order'=>51));
                DB::table('fields')->insert(array('uid'=>uniqid(),'mail_list_id'=>$mailList->id,'label'=>'cart4 title','type'=>'text','tag'=>'CART4_TITLE','visible'=>1,'required'=>0,'custom_order'=>52));
                DB::table('fields')->insert(array('uid'=>uniqid(),'mail_list_id'=>$mailList->id,'label'=>'cart4 quantity','type'=>'text','tag'=>'CART4_QUANTITY','visible'=>1,'required'=>0,'custom_order'=>53));
                DB::table('fields')->insert(array('uid'=>uniqid(),'mail_list_id'=>$mailList->id,'label'=>'cart4 image','type'=>'text','tag'=>'CART4_IMAGE','visible'=>1,'required'=>0,'custom_order'=>54));
                DB::table('fields')->insert(array('uid'=>uniqid(),'mail_list_id'=>$mailList->id,'label'=>'cart5 title','type'=>'text','tag'=>'CART5_TITLE','visible'=>1,'required'=>0,'custom_order'=>55));
                DB::table('fields')->insert(array('uid'=>uniqid(),'mail_list_id'=>$mailList->id,'label'=>'cart5 quantity','type'=>'text','tag'=>'CART5_QUANTITY','visible'=>1,'required'=>0,'custom_order'=>56));
                DB::table('fields')->insert(array('uid'=>uniqid(),'mail_list_id'=>$mailList->id,'label'=>'cart5 image','type'=>'text','tag'=>'CART5_IMAGE','visible'=>1,'required'=>0,'custom_order'=>57));

                DB::table('fields')->insert(array('uid'=>uniqid(),'mail_list_id'=>$mailList->id,'label'=>'Average Order Value','type'=>'text','tag'=>'AVERAGE_ORDER_VALUE','visible'=>1,'required'=>0,'custom_order'=>58));
                DB::table('fields')->insert(array('uid'=>uniqid(),'mail_list_id'=>$mailList->id,'label'=>'Total Products In Cart','type'=>'text','tag'=>'TOTAL_PRODUCTS_IN_CART','visible'=>1,'required'=>0,'custom_order'=>59));
                DB::table('fields')->insert(array('uid'=>uniqid(),'mail_list_id'=>$mailList->id,'label'=>'Cart Last Updated','type'=>'text','tag'=>'CART_LAST_UPDATED','visible'=>1,'required'=>0,'custom_order'=>60));
                DB::table('fields')->insert(array('uid'=>uniqid(),'mail_list_id'=>$mailList->id,'label'=>'Cart Value','type'=>'text','tag'=>'CART_VALUE','visible'=>1,'required'=>0,'custom_order'=>61));
                DB::table('fields')->insert(array('uid'=>uniqid(),'mail_list_id'=>$mailList->id,'label'=>'Last Purchase','type'=>'text','tag'=>'LAST_PURCHASE','visible'=>1,'required'=>0,'custom_order'=>62));
                DB::table('fields')->insert(array('uid'=>uniqid(),'mail_list_id'=>$mailList->id,'label'=>'Last Seen','type'=>'text','tag'=>'LAST_SEEN','visible'=>1,'required'=>0,'custom_order'=>63));
                DB::table('fields')->insert(array('uid'=>uniqid(),'mail_list_id'=>$mailList->id,'label'=>'Total Purchases','type'=>'text','tag'=>'TOTAL_PURCHASES','visible'=>1,'required'=>0,'custom_order'=>64));
                DB::table('fields')->insert(array('uid'=>uniqid(),'mail_list_id'=>$mailList->id,'label'=>'Total Page Views','type'=>'text','tag'=>'TOTAL_PAGE_VIEWS','visible'=>1,'required'=>0,'custom_order'=>65));
                DB::table('fields')->insert(array('uid'=>uniqid(),'mail_list_id'=>$mailList->id,'label'=>'Total Viewed Product','type'=>'text','tag'=>'TOTAL_VIEWED_PRODUCTS','visible'=>1,'required'=>0,'custom_order'=>66));
                DB::table('fields')->insert(array('uid'=>uniqid(),'mail_list_id'=>$mailList->id,'label'=>'Last Page View','type'=>'text','tag'=>'LAST_PAGE_VIEW','visible'=>1,'required'=>0,'custom_order'=>67));
                curl_close ($chh);
            }
           /* else
            {
                echo "Store Name Required";die;
            }*/
        } 
        
        return redirect()->intended('/');
    }
}
