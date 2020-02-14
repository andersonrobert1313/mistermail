<?php

namespace Acelle\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use Session;

class ShopifyController extends Controller
{

    public function __construct()
    {
        header('Access-Control-Allow-Origin: *');  
        header('Set-Cookie: same-site-cookie=foo; SameSite=Lax');
        header('Set-Cookie: cross-site-cookie=bar; SameSite=None; Secure');
    }


     public function install(Request $request)
    {
        if(!empty($request->all()))
        {
            $hmac=DB::table("access_tokens")->where('store_name',$request->shop)->first();
            if(!empty($hmac))
            {
               // $this->appMain();  
                $shop=$request->shop; 
                return redirect('https://app.themistermail.com/login?shop='.$shop);
            }
            if(!empty($request->shop))
            {
                $store_name=$request->shop;
                $nonce = base64_decode(rand(1, 1000));
                $permissions='read_products, read_themes, write_themes, read_script_tags, write_script_tags, read_content, write_content, read_price_rules, write_price_rules, read_customers, write_customers, read_orders, write_orders,write_shipping';
                $url = 'https://'.$store_name.'/admin/oauth/authorize?client_id=4dc87882382401af1fa712ba4d944815&scope='.$permissions.'&redirect_uri=https://app.themistermail.com/getCredentials&state='.$nonce;
                ?>
                    <script>
                        window.top.location.href = "<?=$url ?>";
                    </script>
                <?php
            }
        }
        else
        {
            return view('auth.shopify_login');
        }
    }


    public function getCredentials(Request $request)
    {
        
        $shop = $request->shop;
        $query = array(
            "client_id" => '4dc87882382401af1fa712ba4d944815', // Your API key
            "client_secret" => 'f69558caa617a2d99f360a9004ef13df', // Your app credentials (secret key)
            "code" => $request['code'] // Grab the access key from the URL
        );
        
        // Generate access token URL
        $access_token_url = "https://" . $shop . "/admin/oauth/access_token";
        
        // Configure curl client and execute request
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $access_token_url);
        curl_setopt($ch, CURLOPT_POST, count($query));
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($query));
        $result = curl_exec($ch);
        curl_close($ch);
        
        // Store the access token
        $result = json_decode($result, true);
        $access_token = $result['access_token'];
        DB::table('access_tokens')->insert(array('store_name'=>$shop,'access_token'=>$access_token,'date'=>date('Y-m-d')));

        /*webhooks start*/

        $url = 'https://' . $shop . '/admin/api/2019-10/webhooks.json';
        $session = curl_init();
        curl_setopt($session, CURLOPT_URL, $url);
        curl_setopt($session, CURLOPT_HEADER, false);
        curl_setopt($session, CURLOPT_HTTPHEADER, array('Accept: application/json', 'Content-Type: application/json', 'X-Shopify-Access-Token: ' . $access_token));
        curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
        $webhooks = json_decode(curl_exec($session));

        foreach ($webhooks as $key => $hooks) {
            foreach ($hooks as $key => $hook) {
                if ($hook->topic == 'app/uninstalled') {
                    //435892322382 echo $hook->id;die;
                    //$delete_webhook = $shopify->call(['URL' => '/admin/webhooks/' . $hook->id . '.json', 'METHOD' => 'DELETE']);
                    $delete_webhook = 'https://' . $shop . '/admin/api/2019-10/webhooks/' . $hook->id . '.json';
                    $sessiond = curl_init();
                    curl_setopt($sessiond, CURLOPT_URL, $delete_webhook);
                    curl_setopt($sessiond, CURLOPT_HEADER, false);
                    curl_setopt($sessiond, CURLOPT_CUSTOMREQUEST, "DELETE");
                    curl_setopt($sessiond, CURLOPT_HTTPHEADER, array('Accept: application/json', 'Content-Type: application/json', 'X-Shopify-Access-Token: ' . $access_token));
                    curl_setopt($sessiond, CURLOPT_RETURNTRANSFER, true);
                    curl_exec($sessiond);
                }
            }
        }

        $urlw = 'https://' . $shop . '/admin/webhooks.json';
        $params = '{
        "webhook": {
            "topic": "app/uninstalled",
            "address": "https://' . $_SERVER['HTTP_HOST'] . '/setShopifyUninstall?shop=' . $shop . '",
            "format": "json"}
        }';

        $sessionw = curl_init();

        curl_setopt($sessionw, CURLOPT_URL, $urlw);
        curl_setopt($sessionw, CURLOPT_POST, 1);
        curl_setopt($sessionw, CURLOPT_POSTFIELDS, $params);
        curl_setopt($sessionw, CURLOPT_HEADER, false);
        curl_setopt($sessionw, CURLOPT_HTTPHEADER, array('Accept: application/json', 'Content-Type: application/json', 'X-Shopify-Access-Token: ' . $access_token));
        curl_setopt($sessionw, CURLOPT_RETURNTRANSFER, true);

        if (preg_match("/^(https)/", $urlw)) {
            curl_setopt($sessionw, CURLOPT_SSL_VERIFYPEER, false);
        }

        $response = curl_exec($sessionw);
        curl_close($sessionw);
        $this->createWebhook($shop);

       /*webhooks ends*/
        return redirect('https://'.$shop.'/admin/apps');
       
    }

     public function createWebhook($store_name)
    {
        $url = 'https://' . $store_name . '/admin/webhooks.json';
        $params = '{
        "webhook": { 
            "topic": "orders/create",
            "address": "https://' . $_SERVER['HTTP_HOST'] . '/orderCreateWebhook?shop=' . $store_name . '",
            "format": "json"}
        }';

        $storeData=DB::table('access_tokens')->where("store_name",$store_name)->first();
        $access_token=$storeData->access_token;
        $session = curl_init();
        curl_setopt($session, CURLOPT_URL, $url);
        curl_setopt($session, CURLOPT_POST, 1);
        curl_setopt($session, CURLOPT_POSTFIELDS, $params);
        curl_setopt($session, CURLOPT_HEADER, false);
        curl_setopt($session, CURLOPT_HTTPHEADER, array('Accept: application/json', 'Content-Type: application/json', 'X-Shopify-Access-Token: ' . $access_token));
        curl_setopt($session, CURLOPT_RETURNTRANSFER, true);

        if (preg_match("/^(https)/", $url)) {
            curl_setopt($session, CURLOPT_SSL_VERIFYPEER, false);
        }
        $response = curl_exec($session);
        curl_close($session);
    }


    public function appMain()
    {
        /*$misterUsers=DB::table('users')->where('list_created',1)->get();
        foreach ($misterUsers as $key => $value) {
                $access_token_data=DB::table('access_tokens')->where('store_name',$value->store_name)->first();
                $url = 'https://' . $value->store_name . '/admin/customers.json';
                $session = curl_init();
                curl_setopt($session, CURLOPT_URL, $url);
                curl_setopt($session, CURLOPT_HEADER, false);
                curl_setopt($session, CURLOPT_HTTPHEADER, array('Accept: application/json', 'Content-Type: application/json', 'X-Shopify-Access-Token: ' . $access_token_data->access_token));
                curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
                $getCustomers = json_decode(curl_exec($session));
                foreach ($getCustomers->customers as $key => $customer_value) {
                    $get_list_url='https://app.themistermail.com/api/v1/lists?api_token='.$value->api_token; 
                    $sessione = curl_init();
                    curl_setopt($sessione, CURLOPT_URL, $get_list_url);
                    curl_setopt($sessione, CURLOPT_HEADER, false);
                    curl_setopt($sessione, CURLOPT_RETURNTRANSFER, true);
                    $getAllList = json_decode(curl_exec($sessione));
                    $list_uid='';
                    foreach ($getAllList as $key => $value_list) {
                       if($value_list->name == 'All')
                       {
                            $list_uid=$value_list->uid;
                       }
                    }

                    if($customer_value->accepts_marketing == 1)
                    {
                        $status='Subscribed';
                    }
                    else
                    {
                        $status='Unsubscribed';
                    }
                   if(!empty($customer_value->addresses))
                   {
                        $addr=$customer_value->addresses[0];
                        $city=$addr->city;
                        $country=$addr->country;
                        $zip=$addr->zip;
                        $address=$addr->address1;

                   }    
                   else
                   {
                        $addr='';
                        $city='';
                        $country='';
                        $zip='';
                        $address='';
                   }
                   $mister_list_array=array(
                                            'EMAIL'=>$customer_value->email,
                                            'FIRST_NAME'=>$customer_value->first_name,
                                            'LAST_NAME'=>$customer_value->last_name,
                                            'STATUS' => $status,
                                            'CREATED_AT' => $customer_value->created_at,
                                            'ORDERS_COUNT' => $customer_value->orders_count,
                                            'TOTAL_SPENT' => $customer_value->total_spent,
                                            'LAST_ORDER_ID' => $customer_value->last_order_id,
                                            'PHONE'=> $customer_value->phone,
                                            'ADDRESS'=> $address,
                                            'CITY'=> $city,
                                            'COUNTRY'=> $country,
                                            'ZIP'=> $zip,
                                            'WELCOME_POINTS'=> 0,
                                            'POINTS_EARN'=> 0,
                                            'TOTAL_POINTS'=> 0,
                                            'ANNIVERSARY'=> '',
                                            'BIRTHDAY'=> '',
                                            'POINTS_REDEEM'=> 0,
                                            'VIP_STATUS'=> ''
                                            );
                    $url_list='https://app.themistermail.com/api/v1/lists/'.$list_uid.'/subscribers/store?api_token='.$value->api_token;
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL,$url_list);
                    curl_setopt($ch, CURLOPT_POST, 1);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS,$mister_list_array);
                    $server_output = curl_exec($ch);
                    curl_close ($ch);

                       //order check
                    $order1_title='';
                    $order2_title='';
                    $order3_title='';
                    $order4_title='';
                    $order5_title='';
                    $order1_image='';
                    $order2_image='';
                    $order3_image='';
                    $order4_image='';
                    $order5_image='';
                    if($customer_value->orders_count > 0)
                    {
                        $urlo = 'https://' . $value->store_name . '/admin/customers/'.$customer_value->id.'/orders.json?limit=5';
                        $sessiono = curl_init();
                        curl_setopt($sessiono, CURLOPT_URL, $urlo);
                        curl_setopt($sessiono, CURLOPT_HEADER, false);
                        curl_setopt($sessiono, CURLOPT_HTTPHEADER, array('Accept: application/json', 'Content-Type: application/json', 'X-Shopify-Access-Token: ' . $access_token_data->access_token));
                        curl_setopt($sessiono, CURLOPT_RETURNTRANSFER, true);
                        $getOrders = json_decode(curl_exec($sessiono));
                        foreach($getOrders->orders as $key=>$ord)
                        {
                            $product_id=$ord->line_items[0]->product_id;
                            $urlp = 'https://' . $value->store_name . '/admin/products/'.$product_id.'.json';
                            $sessionp = curl_init();
                            curl_setopt($sessionp, CURLOPT_URL, $urlp);
                            curl_setopt($sessionp, CURLOPT_HEADER, false);
                            curl_setopt($sessionp, CURLOPT_HTTPHEADER, array('Accept: application/json', 'Content-Type: application/json', 'X-Shopify-Access-Token: ' . $access_token_data->access_token));
                            curl_setopt($sessionp, CURLOPT_RETURNTRANSFER, true);
                            $getProduct = json_decode(curl_exec($sessionp));
                            if($key == 0)
                            {
                                $order1_title=$getProduct->product->title;
                                $order1_image=$getProduct->product->images[0]->src;
                                $mister_list_array=array(
                                            'EMAIL'=>$customer_value->email,
                                            'ORDER1_TITLE'=>$order1_title,
                                            'ORDER1_IMAGE'=>$order1_image,
                                             'IP_ADDR'=>$ord->browser_ip
                                            );
                                $url_listt='https://app.themistermail.com/api/v1/lists/'.$list_uid.'/subscribers/store?api_token='.$value->api_token;
                                $chh = curl_init();
                                curl_setopt($chh, CURLOPT_URL,$url_listt);
                                curl_setopt($chh, CURLOPT_POST, 1);
                                curl_setopt($chh, CURLOPT_RETURNTRANSFER, true);
                                curl_setopt($chh, CURLOPT_POSTFIELDS,$mister_list_array);
                                $server_output = curl_exec($chh);
                                curl_close ($chh);
                            }
                            if($key == 1)
                            {
                                $order2_title=$getProduct->product->title;
                                $order2_image=$getProduct->product->images[0]->src;
                                $mister_list_array=array(
                                            'EMAIL'=>$customer_value->email,
                                            'ORDER2_TITLE'=>$order2_title,
                                            'ORDER2_IMAGE'=>$order2_image,
                                             'IP_ADDR'=>$ord->browser_ip
                                            );
                                $url_listt='https://app.themistermail.com/api/v1/lists/'.$list_uid.'/subscribers/store?api_token='.$value->api_token;
                                $chh = curl_init();
                                curl_setopt($chh, CURLOPT_URL,$url_listt);
                                curl_setopt($chh, CURLOPT_POST, 1);
                                curl_setopt($chh, CURLOPT_RETURNTRANSFER, true);
                                curl_setopt($chh, CURLOPT_POSTFIELDS,$mister_list_array);
                                $server_output = curl_exec($chh);
                                curl_close ($chh);
                            }
                            if($key == 2)
                            {
                                $order3_title=$getProduct->product->title;
                                $order3_image=$getProduct->product->images[0]->src;
                                $mister_list_array=array(
                                            'EMAIL'=>$customer_value->email,
                                            'ORDER3_TITLE'=>$order3_title,
                                            'ORDER3_IMAGE'=>$order3_image,
                                             'IP_ADDR'=>$ord->browser_ip
                                            );
                                $url_listt='https://app.themistermail.com/api/v1/lists/'.$list_uid.'/subscribers/store?api_token='.$value->api_token;
                                $chh = curl_init();
                                curl_setopt($chh, CURLOPT_URL,$url_listt);
                                curl_setopt($chh, CURLOPT_POST, 1);
                                curl_setopt($chh, CURLOPT_RETURNTRANSFER, true);
                                curl_setopt($chh, CURLOPT_POSTFIELDS,$mister_list_array);
                                $server_output = curl_exec($chh);
                                curl_close ($chh);
                            }
                            if($key == 3)
                            {
                                $order4_title=$getProduct->product->title;
                                $order4_image=$getProduct->product->images[0]->src;
                                $mister_list_array=array(
                                            'EMAIL'=>$customer_value->email,
                                            'ORDER4_TITLE'=>$order4_title,
                                            'ORDER4_IMAGE'=>$order4_image,
                                             'IP_ADDR'=>$ord->browser_ip
                                            );
                                $url_listt='https://app.themistermail.com/api/v1/lists/'.$list_uid.'/subscribers/store?api_token='.$value->api_token;
                                $chh = curl_init();
                                curl_setopt($chh, CURLOPT_URL,$url_listt);
                                curl_setopt($chh, CURLOPT_POST, 1);
                                curl_setopt($chh, CURLOPT_RETURNTRANSFER, true);
                                curl_setopt($chh, CURLOPT_POSTFIELDS,$mister_list_array);
                                $server_output = curl_exec($chh);
                                curl_close ($chh);
                            }
                            if($key == 4)
                            {
                                $order5_title=$getProduct->product->title;
                                $order5_image=$getProduct->product->images[0]->src;
                                $mister_list_array=array(
                                            'EMAIL'=>$customer_value->email,
                                            'ORDER5_TITLE'=>$order5_title,
                                            'ORDER5_IMAGE'=>$order5_image,
                                             'IP_ADDR'=>$ord->browser_ip
                                            );
                                $url_listt='https://app.themistermail.com/api/v1/lists/'.$list_uid.'/subscribers/store?api_token='.$value->api_token;
                                $chh = curl_init();
                                curl_setopt($chh, CURLOPT_URL,$url_listt);
                                curl_setopt($chh, CURLOPT_POST, 1);
                                curl_setopt($chh, CURLOPT_RETURNTRANSFER, true);
                                curl_setopt($chh, CURLOPT_POSTFIELDS,$mister_list_array);
                                $server_output = curl_exec($chh);
                                curl_close ($chh);
                            }
                        }
                    }
                }
        }*/
        
        $records=DB::table('products_view_main_ip')->where('status',0)->get();
        foreach($records as $rec)
        {
           $inner_records_count=DB::table('products_view_ip')->where('ip_addr',$rec->ip_addr)->where('store_name',$rec->store_name)->count();
           if($inner_records_count > 5)
            {
                $inner_recordss=DB::table('products_view_ip')->where('ip_addr',$rec->ip_addr)->where('store_name',$rec->store_name)->orderBy('id','DESC')->limit(5)->get();
            }
            else
            {
                $inner_recordss=DB::table('products_view_ip')->where('ip_addr',$rec->ip_addr)->where('store_name',$rec->store_name)->orderBy('id','DESC')->get();
            }
            $access_token_data=DB::table('access_tokens')->where("store_name",$rec->store_name)->first();
            $api_token_data=DB::table('tblusers')->select('api_token')->where("store_name",$rec->store_name)->first();
            $subs_fields=DB::table('tblsubscriber_fields')->where('value',$rec->ip_addr)->first();
            if(!empty($subs_fields))
            {
                $subs_data=DB::table('tblsubscribers')->where('id',$subs_fields->subscriber_id)->first();
                $email=$subs_data->email;
                $get_list_url='https://app.themistermail.com/api/v1/lists?api_token='.$api_token_data->api_token; 
                $sessione = curl_init();
                curl_setopt($sessione, CURLOPT_URL, $get_list_url);
                curl_setopt($sessione, CURLOPT_HEADER, false);
                curl_setopt($sessione, CURLOPT_RETURNTRANSFER, true);
                $getAllList = json_decode(curl_exec($sessione));
                $list_uid='';
                foreach ($getAllList as $key => $value_list) {
                   if($value_list->name == 'All')
                   {
                        $list_uid=$value_list->uid;
                   }
                }
                foreach($inner_recordss as $key=>$inr){
                    $urlp = 'https://' . $rec->store_name . '/admin/products/'.$inr->product_id.'.json';
                    $sessionp = curl_init();
                    curl_setopt($sessionp, CURLOPT_URL, $urlp);
                    curl_setopt($sessionp, CURLOPT_HEADER, false);
                    curl_setopt($sessionp, CURLOPT_HTTPHEADER, array('Accept: application/json', 'Content-Type: application/json', 'X-Shopify-Access-Token: ' . $access_token_data->access_token));
                    curl_setopt($sessionp, CURLOPT_RETURNTRANSFER, true);
                    $getProduct = json_decode(curl_exec($sessionp));
                    if($key == 0)
                    {
                        $product1_title=$getProduct->product->title;
                        $product1_image=$getProduct->product->images[0]->src;
                        $mister_list_array=array(
                                    'EMAIL'=>$email,
                                    'PRODUCT1_TITLE'=>$product1_title,
                                    'PRODUCT1_IMAGE'=>$product1_image,
                                    'PRODUCT1_DATE'=>$inr->date
                                    );
                        $url_listt='https://app.themistermail.com/api/v1/lists/'.$list_uid.'/subscribers/store?api_token='.$api_token_data->api_token;
                        $chh = curl_init();
                        curl_setopt($chh, CURLOPT_URL,$url_listt);
                        curl_setopt($chh, CURLOPT_POST, 1);
                        curl_setopt($chh, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($chh, CURLOPT_POSTFIELDS,$mister_list_array);
                        $server_output = curl_exec($chh);
                        curl_close ($chh);
                    }
                    if($key == 1)
                    {
                        $product2_title=$getProduct->product->title;
                        $product2_image=$getProduct->product->images[0]->src;
                        $mister_list_array=array(
                                    'EMAIL'=>$email,
                                    'PRODUCT2_TITLE'=>$product2_title,
                                    'PRODUCT2_IMAGE'=>$product2_image,
                                    'PRODUCT2_DATE'=>$inr->date
                                    );
                        $url_listt='https://app.themistermail.com/api/v1/lists/'.$list_uid.'/subscribers/store?api_token='.$api_token_data->api_token;
                        $chh = curl_init();
                        curl_setopt($chh, CURLOPT_URL,$url_listt);
                        curl_setopt($chh, CURLOPT_POST, 1);
                        curl_setopt($chh, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($chh, CURLOPT_POSTFIELDS,$mister_list_array);
                        $server_output = curl_exec($chh);
                        curl_close ($chh);
                    }
                    if($key == 2)
                    {
                        $product3_title=$getProduct->product->title;
                        $product3_image=$getProduct->product->images[0]->src;
                        $mister_list_array=array(
                                    'EMAIL'=>$email,
                                    'PRODUCT3_TITLE'=>$product3_title,
                                    'PRODUCT3_IMAGE'=>$product3_image,
                                     'PRODUCT3_DATE'=>$inr->date
                                    );
                        $url_listt='https://app.themistermail.com/api/v1/lists/'.$list_uid.'/subscribers/store?api_token='.$api_token_data->api_token;
                        $chh = curl_init();
                        curl_setopt($chh, CURLOPT_URL,$url_listt);
                        curl_setopt($chh, CURLOPT_POST, 1);
                        curl_setopt($chh, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($chh, CURLOPT_POSTFIELDS,$mister_list_array);
                        $server_output = curl_exec($chh);
                        curl_close ($chh);
                    }
                    if($key == 3)
                    {
                        $product4_title=$getProduct->product->title;
                        $product4_image=$getProduct->product->images[0]->src;
                        $mister_list_array=array(
                                    'EMAIL'=>$email,
                                    'PRODUCT4_TITLE'=>$product4_title,
                                    'PRODUCT4_IMAGE'=>$product4_image,
                                     'PRODUCT4_DATE'=>$inr->date
                                    );
                        $url_listt='https://app.themistermail.com/api/v1/lists/'.$list_uid.'/subscribers/store?api_token='.$api_token_data->api_token;
                        $chh = curl_init();
                        curl_setopt($chh, CURLOPT_URL,$url_listt);
                        curl_setopt($chh, CURLOPT_POST, 1);
                        curl_setopt($chh, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($chh, CURLOPT_POSTFIELDS,$mister_list_array);
                        $server_output = curl_exec($chh);
                        curl_close ($chh);
                    }
                    if($key == 4)
                    {
                        $product5_title=$getProduct->product->title;
                        $product5_image=$getProduct->product->images[0]->src;
                        $mister_list_array=array(
                                    'EMAIL'=>$email,
                                    'PRODUCT5_TITLE'=>$product5_title,
                                    'PRODUCT5_IMAGE'=>$product5_image,
                                    'PRODUCT5_DATE'=>$inr->date
                                    );
                        $url_listt='https://app.themistermail.com/api/v1/lists/'.$list_uid.'/subscribers/store?api_token='.$api_token_data->api_token;
                        $chh = curl_init();
                        curl_setopt($chh, CURLOPT_URL,$url_listt);
                        curl_setopt($chh, CURLOPT_POST, 1);
                        curl_setopt($chh, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($chh, CURLOPT_POSTFIELDS,$mister_list_array);
                        $server_output = curl_exec($chh);
                        curl_close ($chh);
                    }
                }
                DB::table('products_view_main_ip')->where('id',$rec->id)->update(array('status'=>1));
            } 
        }
    }

   /* public function appMain()
    {
        $records=DB::table('products_view_main')->where('status',0)->get();
        foreach($records as $rec)
        {
            $inner_records=DB::table('products_view')->where('customer_id',$rec->customer_id)->where('store_name',$rec->store_name)->orderBy('id','DESC')->limit(5)->get();
            $access_token_data=DB::table('access_tokens')->where("store_name",$rec->store_name)->first();
            $api_token_data=DB::table('users')->select('api_token')->where("store_name",$rec->store_name)->first();
            $url_customer = 'https://' . $rec->store_name . '/admin/customers/'.$rec->customer_id.'.json';
            $session = curl_init();
            curl_setopt($session, CURLOPT_URL, $url_customer);
            curl_setopt($session, CURLOPT_HEADER, false);
            curl_setopt($session, CURLOPT_HTTPHEADER, array('Accept: application/json', 'Content-Type: application/json', 'X-Shopify-Access-Token: ' . $access_token_data->access_token));
            curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
            $getCustomer = json_decode(curl_exec($session));
            $email=$getCustomer->customer->email;

            $get_list_url='https://app.themistermail.com/api/v1/lists?api_token='.$api_token_data->api_token; 
            $sessione = curl_init();
            curl_setopt($sessione, CURLOPT_URL, $get_list_url);
            curl_setopt($sessione, CURLOPT_HEADER, false);
            curl_setopt($sessione, CURLOPT_RETURNTRANSFER, true);
            $getAllList = json_decode(curl_exec($sessione));
            $list_uid='';
            foreach ($getAllList as $key => $value_list) {
               if($value_list->name == 'All')
               {
                    $list_uid=$value_list->uid;
               }
            }
            foreach($inner_records as $key=>$inr){
                $urlp = 'https://' . $rec->store_name . '/admin/products/'.$inr->product_id.'.json';
                $sessionp = curl_init();
                curl_setopt($sessionp, CURLOPT_URL, $urlp);
                curl_setopt($sessionp, CURLOPT_HEADER, false);
                curl_setopt($sessionp, CURLOPT_HTTPHEADER, array('Accept: application/json', 'Content-Type: application/json', 'X-Shopify-Access-Token: ' . $access_token_data->access_token));
                curl_setopt($sessionp, CURLOPT_RETURNTRANSFER, true);
                $getProduct = json_decode(curl_exec($sessionp));
                if($key == 0)
                {
                    $product1_title=$getProduct->product->title;
                    $product1_image=$getProduct->product->images[0]->src;
                    $mister_list_array=array(
                                'EMAIL'=>$email,
                                'PRODUCT1_TITLE'=>$product1_title,
                                'PRODUCT1_IMAGE'=>$product1_image,
                                'PRODUCT1_DATE'=>$inr->date
                                );
                    $url_listt='https://app.themistermail.com/api/v1/lists/'.$list_uid.'/subscribers/store?api_token='.$api_token_data->api_token;
                    $chh = curl_init();
                    curl_setopt($chh, CURLOPT_URL,$url_listt);
                    curl_setopt($chh, CURLOPT_POST, 1);
                    curl_setopt($chh, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($chh, CURLOPT_POSTFIELDS,$mister_list_array);
                    $server_output = curl_exec($chh);
                    curl_close ($chh);
                }
                if($key == 1)
                {
                    $product2_title=$getProduct->product->title;
                    $product2_image=$getProduct->product->images[0]->src;
                    $mister_list_array=array(
                                'EMAIL'=>$email,
                                'PRODUCT2_TITLE'=>$product2_title,
                                'PRODUCT2_IMAGE'=>$product2_image,
                                'PRODUCT2_DATE'=>$inr->date
                                );
                    $url_listt='https://app.themistermail.com/api/v1/lists/'.$list_uid.'/subscribers/store?api_token='.$api_token_data->api_token;
                    $chh = curl_init();
                    curl_setopt($chh, CURLOPT_URL,$url_listt);
                    curl_setopt($chh, CURLOPT_POST, 1);
                    curl_setopt($chh, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($chh, CURLOPT_POSTFIELDS,$mister_list_array);
                    $server_output = curl_exec($chh);
                    curl_close ($chh);
                }
                if($key == 2)
                {
                    $product3_title=$getProduct->product->title;
                    $product3_image=$getProduct->product->images[0]->src;
                    $mister_list_array=array(
                                'EMAIL'=>$email,
                                'PRODUCT3_TITLE'=>$product3_title,
                                'PRODUCT3_IMAGE'=>$product3_image,
                                 'PRODUCT3_DATE'=>$inr->date
                                );
                    $url_listt='https://app.themistermail.com/api/v1/lists/'.$list_uid.'/subscribers/store?api_token='.$api_token_data->api_token;
                    $chh = curl_init();
                    curl_setopt($chh, CURLOPT_URL,$url_listt);
                    curl_setopt($chh, CURLOPT_POST, 1);
                    curl_setopt($chh, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($chh, CURLOPT_POSTFIELDS,$mister_list_array);
                    $server_output = curl_exec($chh);
                    curl_close ($chh);
                }
                if($key == 3)
                {
                    $product4_title=$getProduct->product->title;
                    $product4_image=$getProduct->product->images[0]->src;
                    $mister_list_array=array(
                                'EMAIL'=>$email,
                                'PRODUCT4_TITLE'=>$product4_title,
                                'PRODUCT4_IMAGE'=>$product4_image,
                                 'PRODUCT4_DATE'=>$inr->date
                                );
                    $url_listt='https://app.themistermail.com/api/v1/lists/'.$list_uid.'/subscribers/store?api_token='.$api_token_data->api_token;
                    $chh = curl_init();
                    curl_setopt($chh, CURLOPT_URL,$url_listt);
                    curl_setopt($chh, CURLOPT_POST, 1);
                    curl_setopt($chh, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($chh, CURLOPT_POSTFIELDS,$mister_list_array);
                    $server_output = curl_exec($chh);
                    curl_close ($chh);
                }
                if($key == 4)
                {
                    $product5_title=$getProduct->product->title;
                    $product5_image=$getProduct->product->images[0]->src;
                    $mister_list_array=array(
                                'EMAIL'=>$email,
                                'PRODUCT5_TITLE'=>$product5_title,
                                'PRODUCT5_IMAGE'=>$product5_image,
                                'PRODUCT5_DATE'=>$inr->date
                                );
                    $url_listt='https://app.themistermail.com/api/v1/lists/'.$list_uid.'/subscribers/store?api_token='.$api_token_data->api_token;
                    $chh = curl_init();
                    curl_setopt($chh, CURLOPT_URL,$url_listt);
                    curl_setopt($chh, CURLOPT_POST, 1);
                    curl_setopt($chh, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($chh, CURLOPT_POSTFIELDS,$mister_list_array);
                    $server_output = curl_exec($chh);
                    curl_close ($chh);
                }
            }
            DB::table('products_view_main')->where('id',$rec->id)->update(array('status'=>1));
        }
    }*/

    public function setShopifyUninstall(Request $request)
    {
        DB::table('access_tokens')->where("store_name",$request->shop)->delete();
    }


    public function orderCreateWebhook(Request $request)
    {
        $shop=$request->shop;
        $array = file_get_contents("php://input");
        $finalOrder=json_decode($array);
        DB::table('webhook_orders')->insert(array('data'=>$array,'store_name'=>$shop,'email'=>$finalOrder->customer->email));
    }

    public function orderCreateWebhook_old(Request $request)
    {
        $shop=$request->shop;
        $array = file_get_contents("php://input");
        $finalOrder=json_decode($array);
        DB::table('check_cron')->insert(array('test'=>'webhook start'));
        DB::table('webhook_orders')->insert(array('test'=>json_encode($array)));
        $urlo = 'https://' . $shop . '/admin/customers/'.$finalOrder->customer->id.'/orders.json?limit=1';
        //$urlOrdersCount = 'https://' . $shop . '/admin/customers/'.$finalOrder->customer->id.'.json';
        $access_token_data=DB::table('access_tokens')->where('store_name',$shop)->first();
        $value=DB::table('tblusers')->where('store_name',$shop)->first();
        $get_list_url='https://app.themistermail.com/api/v1/lists?api_token='.$value->api_token; 
        $sessione = curl_init();
        curl_setopt($sessione, CURLOPT_URL, $get_list_url);
        curl_setopt($sessione, CURLOPT_HEADER, false);
        curl_setopt($sessione, CURLOPT_RETURNTRANSFER, true);
        $getAllList = json_decode(curl_exec($sessione));
        $list_uid='';
        foreach ($getAllList as $key => $value_list) {
           if($value_list->name == 'All')
           {
                $list_uid=$value_list->uid;
           }
        }
        $sessiono = curl_init();
        curl_setopt($sessiono, CURLOPT_URL, $urlo);
        curl_setopt($sessiono, CURLOPT_HEADER, false);
        curl_setopt($sessiono, CURLOPT_HTTPHEADER, array('Accept: application/json', 'Content-Type: application/json', 'X-Shopify-Access-Token: ' . $access_token_data->access_token));
        curl_setopt($sessiono, CURLOPT_RETURNTRANSFER, true);
        $getOrders = json_decode(curl_exec($sessiono));

       /* $sessioncc = curl_init();
        curl_setopt($sessioncc, CURLOPT_URL, $urlCustomer);
        curl_setopt($sessioncc, CURLOPT_HEADER, false);
        curl_setopt($sessioncc, CURLOPT_HTTPHEADER, array('Accept: application/json', 'Content-Type: application/json', 'X-Shopify-Access-Token: ' . $access_token_data->access_token));
        curl_setopt($sessioncc, CURLOPT_RETURNTRANSFER, true);
        $getCustomer = json_decode(curl_exec($sessioncc));

        $orders_count=$getCustomer->customer->orders_count;*/

        $order1_title='';
        /*$order2_title='';
        $order3_title='';
        $order4_title='';
        $order5_title='';*/
        $order1_image='';
        /*$order2_image='';
        $order3_image='';
        $order4_image='';
        $order5_image='';*/
        foreach($getOrders->orders as $key=>$ord)
        {

            DB::table('check_cron')->insert(array('test'=>'order start'));
            $product_id=$ord->line_items[0]->product_id;
            $urlp = 'https://' . $value->store_name . '/admin/products/'.$product_id.'.json';
            $sessionp = curl_init();
            curl_setopt($sessionp, CURLOPT_URL, $urlp);
            curl_setopt($sessionp, CURLOPT_HEADER, false);
            curl_setopt($sessionp, CURLOPT_HTTPHEADER, array('Accept: application/json', 'Content-Type: application/json', 'X-Shopify-Access-Token: ' . $access_token_data->access_token));
            curl_setopt($sessionp, CURLOPT_RETURNTRANSFER, true);
            $getProduct = json_decode(curl_exec($sessionp));
            if($key == 0)
            {
                DB::table('check_cron')->insert(array('test'=>'order first'));
                $order1_title=$getProduct->product->title;
                $order1_image=$getProduct->product->images[0]->src;
                $mister_list_array=array(
                            'EMAIL'=>$finalOrder->email,
                            'ORDER1_TITLE'=>$order1_title,
                            'ORDER1_IMAGE'=>$order1_image,
                            'IP_ADDR'=>$ord->browser_ip,
                            'ORDERS_COUNT'=>$orders_count,
                            'LAST_PURCHASE'=>$ord->created_at,
                             'CART1_TITLE'=>'',
                             'CART2_TITLE'=>'',
                             'CART3_TITLE'=>'',
                             'CART4_TITLE'=>'',
                             'CART5_TITLE'=>'',
                             'CART1_QuANTITY'=>'',
                             'CART2_QuANTITY'=>'',
                             'CART3_QuANTITY'=>'',
                             'CART4_QuANTITY'=>'',
                             'CART5_QuANTITY'=>'',
                             'CART1_IMAGE'=>'',
                             'CART2_IMAGE'=>'',
                             'CART3_IMAGE'=>'',
                             'CART4_IMAGE'=>'',
                             'CART5_IMAGE'=>''
                            );
                $url_listt='https://app.themistermail.com/api/v1/lists/'.$list_uid.'/subscribers/store?api_token='.$value->api_token;
                $chh = curl_init();
                curl_setopt($chh, CURLOPT_URL,$url_listt);
                curl_setopt($chh, CURLOPT_POST, 1);
                curl_setopt($chh, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($chh, CURLOPT_POSTFIELDS,$mister_list_array);
                $server_output = curl_exec($chh);
                curl_close ($chh);
            }
        }   
        return response('Processed', 200)->header('Content-Type', 'text/plain');    
    }


    public function MisterMailScript(Request $request)
    {
        header('Access-Control-Allow-Origin: *');  
        $access_token_data=DB::table('access_tokens')->where('store_name',$request->shop)->first();
        $value=DB::table('users')->where('store_name',$request->shop)->first();
        $get_list_url='https://app.themistermail.com/api/v1/lists?api_token='.$value->api_token; 
        $sessione = curl_init();
        curl_setopt($sessione, CURLOPT_URL, $get_list_url);
        curl_setopt($sessione, CURLOPT_HEADER, false);
        curl_setopt($sessione, CURLOPT_RETURNTRANSFER, true);
        $getAllList = json_decode(curl_exec($sessione));
        $list_uid='';
        foreach ($getAllList as $key => $value_list) {
           if($value_list->name == 'All')
           {
                $list_uid=$value_list->uid;
           }
        }

        $urlp = 'https://' . $request->shop . '/admin/customers/'.$request->customer_id.'.json';
        $sessionp = curl_init();
        curl_setopt($sessionp, CURLOPT_URL, $urlp);
        curl_setopt($sessionp, CURLOPT_HEADER, false);
        curl_setopt($sessionp, CURLOPT_HTTPHEADER, array('Accept: application/json', 'Content-Type: application/json', 'X-Shopify-Access-Token: ' . $access_token_data->access_token));
        curl_setopt($sessionp, CURLOPT_RETURNTRANSFER, true);
        $customerData = json_decode(curl_exec($sessionp));
        $email=$customerData->customer->email;

        /*if click on cart button*/

        if($request->cart == 1)
        {
            if(!empty($request->items)){
                foreach($request->items as $key=>$itm){
                    if($key == 0)
                    {
                        $abCart=DB::table('abandon_cart')->where('email',$email)->first();
                        if(empty($abCart))
                        {
                            DB::table('abandon_cart')->insert(array('status'=>1,'cart'=>1,'email'=>$email,'date'=>date('Y-m-d')));
                        }
                        $cart1_title=$itm['title'];
                        $cart1_quantity=$itm['quantity'];
                        $cart1_image=$itm['featured_image']['url'];
                        $cart1_price='$'.$itm['price']/100;
                        $cart1_url='https://'.$request->shop.$itm['url'];
                        $mister_list_array=array(
                                     'EMAIL'=>$email,
                                     'LAST_SEEN' => date('Y-m-d h:i:s'),
                                     'TOTAL_PRODUCTS_IN_CART' => $request->item_count,
                                     'CART_LAST_UPDATED' => date("Y-m-d h:i:s"),
                                     'LAST_PAGE_VIEW'=>$request->fullUrl,
                                     'CART_VALUE' => $request->cart_value/100,
                                     'CART2_TITLE'=>'',
                                     'CART3_TITLE'=>'',
                                     'CART4_TITLE'=>'',
                                     'CART5_TITLE'=>'',
                                     'CART2_URL'=>'',
                                     'CART3_URL'=>'',
                                     'CART4_URL'=>'',
                                     'CART5_URL'=>'',
                                     'CART2_PRICE'=>'',
                                     'CART3_PRICE'=>'',
                                     'CART4_PRICE'=>'',
                                     'CART5_PRICE'=>'',
                                     'CART2_QUANTITY'=>'',
                                     'CART3_QUANTITY'=>'',
                                     'CART4_QUANTITY'=>'',
                                     'CART5_QUANTITY'=>'',
                                     'CART1_PRICE'=>$cart1_price,
                                     'CART2_IMAGE'=>'',
                                     'CART3_IMAGE'=>'',
                                     'CART4_IMAGE'=>'',
                                     'CART5_IMAGE'=>'',
                                     'CART1_URL'=>$cart1_url,
                                     'CART1_TITLE'=>$cart1_title,
                                     'CART1_QUANTITY'=>$cart1_quantity,
                                     'CART1_IMAGE'=>$cart1_image
                                    );
                        $url_listt='https://app.themistermail.com/api/v1/lists/'.$list_uid.'/subscribers/store?api_token='.$value->api_token;
                        $chh = curl_init();
                        curl_setopt($chh, CURLOPT_URL,$url_listt);
                        curl_setopt($chh, CURLOPT_POST, 1);
                        curl_setopt($chh, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($chh, CURLOPT_POSTFIELDS,$mister_list_array);
                        $server_output = curl_exec($chh);
                        curl_close ($chh);
                    }
                    if($key == 1)
                    {
                        $cart2_title=$itm['title'];
                        $cart2_quantity=$itm['quantity'];
                        $cart2_image=$itm['featured_image']['url'];
                        $cart2_price='$'.$itm['price']/100;
                        $cart2_url='https://'.$request->shop.$itm['url'];
                        $mister_list_array=array(
                                    'EMAIL'=>$email,
                                    'CART2_TITLE'=>$cart2_title,
                                    'CART2_QUANTITY'=>$cart2_quantity,
                                    'CART2_IMAGE'=>$cart2_image,
                                    'CART2_PRICE'=>$cart2_price,
                                    'CART2_URL'=>$cart2_url
                                    );
                        $url_listt='https://app.themistermail.com/api/v1/lists/'.$list_uid.'/subscribers/store?api_token='.$value->api_token;
                        $chh = curl_init();
                        curl_setopt($chh, CURLOPT_URL,$url_listt);
                        curl_setopt($chh, CURLOPT_POST, 1);
                        curl_setopt($chh, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($chh, CURLOPT_POSTFIELDS,$mister_list_array);
                        $server_output = curl_exec($chh);
                        curl_close ($chh);
                    }
                    if($key == 2)
                    {
                        $cart3_title=$itm['title'];
                        $cart3_quantity=$itm['quantity'];
                        $cart3_image=$itm['featured_image']['url'];
                        $cart3_price='$'.$itm['price']/100;
                        $cart3_url='https://'.$request->shop.$itm['url'];
                        $mister_list_array=array(
                                    'EMAIL'=>$email,
                                    'CART3_TITLE'=>$cart3_title,
                                    'CART3_QUANTITY'=>$cart3_quantity,
                                    'CART3_IMAGE'=>$cart3_image,
                                    'CART3_PRICE'=>$cart3_price,
                                    'CART3_URL'=>$cart3_url
                                    );
                        $url_listt='https://app.themistermail.com/api/v1/lists/'.$list_uid.'/subscribers/store?api_token='.$value->api_token;
                        $chh = curl_init();
                        curl_setopt($chh, CURLOPT_URL,$url_listt);
                        curl_setopt($chh, CURLOPT_POST, 1);
                        curl_setopt($chh, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($chh, CURLOPT_POSTFIELDS,$mister_list_array);
                        $server_output = curl_exec($chh);
                        curl_close ($chh);
                    }
                    if($key == 3)
                    {
                        $cart4_title=$itm['title'];
                        $cart4_quantity=$itm['quantity'];
                        $cart4_image=$itm['featured_image']['url'];
                        $cart4_price='$'.$itm['price']/100;
                        $cart4_url='https://'.$request->shop.$itm['url'];
                        $mister_list_array=array(
                                    'EMAIL'=>$email,
                                    'CART4_TITLE'=>$cart4_title,
                                    'CART4_QUANTITY'=>$cart4_quantity,
                                    'CART4_IMAGE'=>$cart4_image,
                                    'CART4_PRICE'=>$cart4_price,
                                    'CART4_URL'=>$cart4_url
                                    );
                        $url_listt='https://app.themistermail.com/api/v1/lists/'.$list_uid.'/subscribers/store?api_token='.$value->api_token;
                        $chh = curl_init();
                        curl_setopt($chh, CURLOPT_URL,$url_listt);
                        curl_setopt($chh, CURLOPT_POST, 1);
                        curl_setopt($chh, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($chh, CURLOPT_POSTFIELDS,$mister_list_array);
                        $server_output = curl_exec($chh);
                        curl_close ($chh);
                    }
                    if($key == 4)
                    {
                        $cart5_title=$itm['title'];
                        $cart5_quantity=$itm['quantity'];
                        $cart5_image=$itm['featured_image']['url'];
                        $cart5_price='$'.$itm['price']/100;
                        $cart5_url='https://'.$request->shop.$itm['url'];
                        $mister_list_array=array(
                                    'EMAIL'=>$email,
                                    'CART5_TITLE'=>$cart5_title,
                                    'CART5_QUANTITY'=>$cart5_quantity,
                                    'CART5_IMAGE'=>$cart5_image,
                                    'CART5_PRICE'=>$cart5_price,
                                    'CART5_URL'=>$cart5_url
                                    );
                        $url_listt='https://app.themistermail.com/api/v1/lists/'.$list_uid.'/subscribers/store?api_token='.$value->api_token;
                        $chh = curl_init();
                        curl_setopt($chh, CURLOPT_URL,$url_listt);
                        curl_setopt($chh, CURLOPT_POST, 1);
                        curl_setopt($chh, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($chh, CURLOPT_POSTFIELDS,$mister_list_array);
                        $server_output = curl_exec($chh);
                        curl_close ($chh);
                    }
                }
            }
            else
            {
                /*$abCart=DB::table('abandon_cart')->where('email',$email)->first();
                if(empty($abCart))
                {
                    DB::table('abandon_cart')->insert(array('cart'=>1,'email'=>$email,'date'=>date('Y-m-d')));
                }
                else
                {
                     DB::table('abandon_cart')->where('email',$email)->update(array('cart'=>1));
                }*/
                $mister_list_array=array(
                             'EMAIL'=>$email,
                             'LAST_SEEN' => date('Y-m-d h:i:s'),
                             'TOTAL_PRODUCTS_IN_CART' => 0,
                             'LAST_PAGE_VIEW'=>$request->fullUrl,
                             'CART1_TITLE'=>'',
                             'CART2_TITLE'=>'',
                             'CART3_TITLE'=>'',
                             'CART4_TITLE'=>'',
                             'CART5_TITLE'=>'',
                             'CART1_URL'=>'',
                             'CART2_URL'=>'',
                             'CART3_URL'=>'',
                             'CART4_URL'=>'',
                             'CART5_URL'=>'',
                             'CART1_QUANTITY'=>'',
                             'CART2_QUANTITY'=>'',
                             'CART3_QUANTITY'=>'',
                             'CART4_QUANTITY'=>'',
                             'CART5_QUANTITY'=>'',
                             'CART1_IMAGE'=>'',
                             'CART2_IMAGE'=>'',
                             'CART3_IMAGE'=>'',
                             'CART4_IMAGE'=>'',
                             'CART5_IMAGE'=>'',
                             'CART1_PRICE'=>'',
                             'CART2_PRICE'=>'',
                             'CART3_PRICE'=>'',
                             'CART4_PRICE'=>'',
                             'CART5_PRICE'=>''
                            );
                $url_listt='https://app.themistermail.com/api/v1/lists/'.$list_uid.'/subscribers/store?api_token='.$value->api_token;
                $chhcar = curl_init();
                curl_setopt($chhcar, CURLOPT_URL,$url_listt);
                curl_setopt($chhcar, CURLOPT_POST, 1);
                curl_setopt($chhcar, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($chhcar, CURLOPT_POSTFIELDS,$mister_list_array);
                $server_output = curl_exec($chhcar);
                curl_close ($chhcar);
            }
        }

        /*end if clicks on cart button */

        //product view starts
        $MailListData=DB::table('mail_lists')->where('uid',$list_uid)->first();
        $subscriberData=DB::table('subscribers')->where('mail_list_id',$MailListData->id)->where('email',$email)->first();
        $new_total_product_views=$subscriberData->total_product_views+1;
        DB::table('subscribers')->where('id',$subscriberData->id)->update(array('total_product_views'=>$new_total_product_views));
        //product view ends

        if(!empty($request->items)){
            foreach($request->items as $key=>$itm){
                if($key == 0)
                {
                    $abCart=DB::table('abandon_cart')->where('email',$email)->first();
                    if(empty($abCart))
                    {
                        DB::table('abandon_cart')->insert(array('status'=>1,'cart'=>1,'email'=>$email,'date'=>date('Y-m-d')));
                    }
                    else
                    {
                         DB::table('abandon_cart')->where('email',$email)->update(array('cart'=>1,'status'=>1));
                    }
                    $cart1_title=$itm['title'];
                    $cart1_quantity=$itm['quantity'];
                    $cart1_price='$'.$itm['price']/100;
                    $cart1_url='https://'.$request->shop.$itm['url'];
                    $cart1_image=$itm['featured_image']['url'];
                    $mister_list_array=array(
                                 'EMAIL'=>$email,
                                 'LAST_SEEN' => date('Y-m-d h:i:s'),
                                 'LAST_PAGE_VIEW'=>$request->fullUrl,
                                 'TOTAL_VIEWED_PRODUCTS' => $new_total_product_views,
                                 'TOTAL_PRODUCTS_IN_CART' => $request->item_count,
                                 'CART_LAST_UPDATED' => date("Y-m-d h:i:s"),
                                 'CART_VALUE' => $request->cart_value/100,
                                 'CART2_TITLE'=>'',
                                 'CART3_TITLE'=>'',
                                 'CART4_TITLE'=>'',
                                 'CART5_TITLE'=>'',
                                 'CART2_URL'=>'',
                                 'CART3_URL'=>'',
                                 'CART4_URL'=>'',
                                 'CART5_URL'=>'',
                                 'CART2_QUANTITY'=>'',
                                 'CART3_QUANTITY'=>'',
                                 'CART4_QUANTITY'=>'',
                                 'CART5_QUANTITY'=>'',
                                 'CART2_IMAGE'=>'',
                                 'CART3_IMAGE'=>'',
                                 'CART4_IMAGE'=>'',
                                 'CART5_IMAGE'=>'',
                                 'CART1_URL'=>$cart1_url,
                                 'CART1_TITLE'=>$cart1_title,
                                 'CART1_QUANTITY'=>$cart1_quantity,
                                 'CART1_IMAGE'=>$cart1_image,
                                 'CART1_PRICE'=>$cart1_price,
                                 'CART1_URL'=>$cart1_url,
                                 'CART2_PRICE'=>'',
                                 'CART3_PRICE'=>'',
                                 'CART4_PRICE'=>'',
                                 'CART5_PRICE'=>''
                                );
                    $url_listt='https://app.themistermail.com/api/v1/lists/'.$list_uid.'/subscribers/store?api_token='.$value->api_token;
                    $chh = curl_init();
                    curl_setopt($chh, CURLOPT_URL,$url_listt);
                    curl_setopt($chh, CURLOPT_POST, 1);
                    curl_setopt($chh, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($chh, CURLOPT_POSTFIELDS,$mister_list_array);
                    $server_output = curl_exec($chh);
                    curl_close ($chh);
                }
                if($key == 1)
                {
                    $cart2_title=$itm['title'];
                    $cart2_quantity=$itm['quantity'];
                    $cart2_image=$itm['featured_image']['url'];
                    $cart2_price='$'.$itm['price']/100;
                    $cart2_url='https://'.$request->shop.$itm['url'];
                    $mister_list_array=array(
                                'EMAIL'=>$email,
                                'CART2_TITLE'=>$cart2_title,
                                'CART2_QUANTITY'=>$cart2_quantity,
                                'CART2_IMAGE'=>$cart2_image,
                                'CART2_PRICE'=>$cart2_price,
                                'CART2_URL'=>$cart2_url
                                );
                    $url_listt='https://app.themistermail.com/api/v1/lists/'.$list_uid.'/subscribers/store?api_token='.$value->api_token;
                    $chh = curl_init();
                    curl_setopt($chh, CURLOPT_URL,$url_listt);
                    curl_setopt($chh, CURLOPT_POST, 1);
                    curl_setopt($chh, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($chh, CURLOPT_POSTFIELDS,$mister_list_array);
                    $server_output = curl_exec($chh);
                    curl_close ($chh);
                }
                if($key == 2)
                {
                    $cart3_title=$itm['title'];
                    $cart3_quantity=$itm['quantity'];
                    $cart3_image=$itm['featured_image']['url'];
                    $cart3_price='$'.$itm['price']/100;
                    $cart3_url='https://'.$request->shop.$itm['url'];
                    $mister_list_array=array(
                                'EMAIL'=>$email,
                                'CART3_TITLE'=>$cart3_title,
                                'CART3_QUANTITY'=>$cart3_quantity,
                                'CART3_IMAGE'=>$cart3_image,
                                'CART3_PRICE'=>$cart3_price,
                                'CART3_URL'=>$cart3_url
                                );
                    $url_listt='https://app.themistermail.com/api/v1/lists/'.$list_uid.'/subscribers/store?api_token='.$value->api_token;
                    $chh = curl_init();
                    curl_setopt($chh, CURLOPT_URL,$url_listt);
                    curl_setopt($chh, CURLOPT_POST, 1);
                    curl_setopt($chh, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($chh, CURLOPT_POSTFIELDS,$mister_list_array);
                    $server_output = curl_exec($chh);
                    curl_close ($chh);
                }
                if($key == 3)
                {
                    $cart4_title=$itm['title'];
                    $cart4_quantity=$itm['quantity'];
                    $cart4_image=$itm['featured_image']['url'];
                    $cart4_price='$'.$itm['price']/100;
                    $cart4_url='https://'.$request->shop.$itm['url'];
                    $mister_list_array=array(
                                'EMAIL'=>$email,
                                'CART4_TITLE'=>$cart4_title,
                                'CART4_QUANTITY'=>$cart4_quantity,
                                'CART4_IMAGE'=>$cart4_image,
                                'CART4_PRICE'=>$cart4_price,
                                'CART4_URL'=>$cart4_url
                                );
                    $url_listt='https://app.themistermail.com/api/v1/lists/'.$list_uid.'/subscribers/store?api_token='.$value->api_token;
                    $chh = curl_init();
                    curl_setopt($chh, CURLOPT_URL,$url_listt);
                    curl_setopt($chh, CURLOPT_POST, 1);
                    curl_setopt($chh, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($chh, CURLOPT_POSTFIELDS,$mister_list_array);
                    $server_output = curl_exec($chh);
                    curl_close ($chh);
                }
                if($key == 4)
                {
                    $cart5_title=$itm['title'];
                    $cart5_quantity=$itm['quantity'];
                    $cart5_image=$itm['featured_image']['url'];
                    $cart5_price='$'.$itm['price']/100;
                    $cart5_url='https://'.$request->shop.$itm['url'];
                    $mister_list_array=array(
                                'EMAIL'=>$email,
                                'CART5_TITLE'=>$cart5_title,
                                'CART5_QUANTITY'=>$cart5_quantity,
                                'CART5_IMAGE'=>$cart5_image,
                                'CART5_PRICE'=>$cart2_price,
                                'CART5_URL'=>$cart5_url
                                );
                    $url_listt='https://app.themistermail.com/api/v1/lists/'.$list_uid.'/subscribers/store?api_token='.$value->api_token;
                    $chh = curl_init();
                    curl_setopt($chh, CURLOPT_URL,$url_listt);
                    curl_setopt($chh, CURLOPT_POST, 1);
                    curl_setopt($chh, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($chh, CURLOPT_POSTFIELDS,$mister_list_array);
                    $server_output = curl_exec($chh);
                    curl_close ($chh);
                }
            }
        }
        else
        {
            $abCart=DB::table('abandon_cart')->where('email',$email)->first();
            if(empty($abCart))
            {
                DB::table('abandon_cart')->insert(array('browse'=>1,'email'=>$email,'date'=>date('Y-m-d')));
            }
            $mister_list_array=array(
                         'EMAIL'=>$email,
                         'LAST_SEEN' => date('Y-m-d h:i:s'),
                         'LAST_PAGE_VIEW'=>$request->fullUrl,
                         'TOTAL_VIEWED_PRODUCTS' => $new_total_product_views,
                         'TOTAL_PRODUCTS_IN_CART' => 0,
                         'CART1_TITLE'=>'',
                         'CART2_TITLE'=>'',
                         'CART3_TITLE'=>'',
                         'CART4_TITLE'=>'',
                         'CART5_TITLE'=>'',
                         'CART1_PRICE'=>'',
                         'CART2_PRICE'=>'',
                         'CART3_PRICE'=>'',
                         'CART4_PRICE'=>'',
                         'CART5_PRICE'=>'',
                         'CART1_URL'=>'',
                         'CART2_URL'=>'',
                         'CART3_URL'=>'',
                         'CART4_URL'=>'',
                         'CART5_URL'=>'',
                         'CART1_URL'=>'',
                         'CART2_URL'=>'',
                         'CART3_URL'=>'',
                         'CART4_URL'=>'',
                         'CART5_URL'=>'',
                         'CART1_QUANTITY'=>'',
                         'CART2_QUANTITY'=>'',
                         'CART3_QUANTITY'=>'',
                         'CART4_QUANTITY'=>'',
                         'CART5_QUANTITY'=>'',
                         'CART1_IMAGE'=>'',
                         'CART2_IMAGE'=>'',
                         'CART3_IMAGE'=>'',
                         'CART4_IMAGE'=>'',
                         'CART5_IMAGE'=>''
                        );
            $url_listt='https://app.themistermail.com/api/v1/lists/'.$list_uid.'/subscribers/store?api_token='.$value->api_token;
            $chhcar = curl_init();
            curl_setopt($chhcar, CURLOPT_URL,$url_listt);
            curl_setopt($chhcar, CURLOPT_POST, 1);
            curl_setopt($chhcar, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($chhcar, CURLOPT_POSTFIELDS,$mister_list_array);
            $server_output = curl_exec($chhcar);
            curl_close ($chhcar);
        }

        // product track starts
            $checkProduct=DB::table('products_view')->where('store_name',$request->shop)->where('customer_id',$request->customer_id)->where('product_id',$request->product_id)->where('date',date('Y-m-d'))->first();
            if(empty($checkProduct))
            {
                $checkProductMain=DB::table('products_view_main')->where('store_name',$request->shop)->where('customer_id',$request->customer_id)->first();
                if(empty($checkProductMain)){
                    DB::table('products_view_main')->insert(array('store_name'=>$request->shop,'customer_id'=>$request->customer_id));
                }
                else
                {
                   DB::table('products_view_main')->where('customer_id',$request->customer_id)->where('store_name',$request->shop)->update(array('status'=>0));
                }
                DB::table('products_view')->insert(array('url'=>$request->fullUrl,'store_name'=>$request->shop,'customer_id'=>$request->customer_id,'product_id'=>$request->product_id,'date'=>date('Y-m-d')));
            }
        //product track endse
        echo "done";die;
    }

    public function pageViews(Request $request)
    {
        header('Access-Control-Allow-Origin: *'); 
        echo "<pre>"; print_r($request->all());die;
    }

    public function saveJsonn()
    {
        $misterUsers=DB::table('users')->where('list_created',1)->get();
        foreach ($misterUsers as $key => $value) {
           // echo "<pre>"; print_r($value);die;
                $access_token_data=DB::table('access_tokens')->where('store_name',$value->store_name)->first();
                $url_Count = 'https://' . $value->store_name . '/admin/customers/count.json';
                $sessionCount = curl_init();
                curl_setopt($sessionCount, CURLOPT_URL, $url_Count);
                curl_setopt($sessionCount, CURLOPT_HEADER, false);
                curl_setopt($sessionCount, CURLOPT_HTTPHEADER, array('Accept: application/json', 'Content-Type: application/json', 'X-Shopify-Access-Token: ' . $access_token_data->access_token));
                curl_setopt($sessionCount, CURLOPT_RETURNTRANSFER, true);
                $getCustomersCount = json_decode(curl_exec($sessionCount));
                if($value->page == 0)
                {
                    if($getCustomersCount->count >250)
                    {
                        $page=intval(floor($getCustomersCount->count/250 + 0.5));
                        DB::table('users')->where('id',$value->id)->update(array('page'=>$page));
                    }
                    else
                    {
                        $page=1;
                        DB::table('users')->where('id',$value->id)->update(array('page'=>$page));
                    }
                }
                $getUserData=DB::table('users')->where('id',$value->id)->first();
                $url = 'https://' . $value->store_name . '/admin/customers.json?limit=250&page='.$getUserData->page;
                $session = curl_init();
                curl_setopt($session, CURLOPT_URL, $url);
                curl_setopt($session, CURLOPT_HEADER, false);
                curl_setopt($session, CURLOPT_HTTPHEADER, array('Accept: application/json', 'Content-Type: application/json', 'X-Shopify-Access-Token: ' . $access_token_data->access_token));
                curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
                $getCustomers = json_decode(curl_exec($session));
               // echo "<pre>"; print_r($getCustomers);die;
                foreach ($getCustomers->customers as $key => $customer_value) {
                    /*$get_list_url='https://app.themistermail.com/api/v1/lists?api_token='.$value->api_token; 
                    $sessione = curl_init();
                    curl_setopt($sessione, CURLOPT_URL, $get_list_url);
                    curl_setopt($sessione, CURLOPT_HEADER, false);
                    curl_setopt($sessione, CURLOPT_RETURNTRANSFER, true);
                    $getAllList = json_decode(curl_exec($sessione));
                    $list_uid='';
                    foreach ($getAllList as $key => $value_list) {
                       if($value_list->name == 'All')
                       {
                            $list_uid=$value_list->uid;
                       }
                    }*/
                    $list_uid='5e44eaedc491e';
                    if($customer_value->accepts_marketing == 1)
                    {
                        $status='Subscribed';
                    }
                    else
                    {
                        $status='Unsubscribed';
                    }
                   if(!empty($customer_value->addresses))
                   {
                        $addr=$customer_value->addresses[0];
                        $city=$addr->city;
                        $country=$addr->country;
                        $zip=$addr->zip;
                        $address=$addr->address1;

                   }    
                   else
                   {
                        $addr='';
                        $city='';
                        $country='';
                        $zip='';
                        $address='';
                   }
                   $cstom_total_value=intval($customer_value->total_spent);
                   $cstomorders_count=intval($customer_value->orders_count);
                   if($cstom_total_value != 0 && $cstomorders_count != 0)
                   {
                    $avg_order_value=$cstom_total_value/$cstomorders_count;
                   }
                   else
                   {
                    $avg_order_value=0;
                   }
                  // if($customer_value->email == 'support@thescorpiolab.com'){
                   $mister_list_array=array(
                                            'EMAIL'=>$customer_value->email,
                                            'FIRST_NAME'=>$customer_value->first_name,
                                            'LAST_NAME'=>$customer_value->last_name,
                                            'STATUS' => $status,
                                            'CREATED_AT' => $customer_value->created_at,
                                            'ORDERS_COUNT' => $customer_value->orders_count,
                                            'TOTAL_SPENT' => $customer_value->total_spent,
                                            'AVERAGE_ORDER_VALUE' => $avg_order_value,
                                            'TOTAL_PURCHASES' => $customer_value->orders_count,
                                            'LAST_ORDER_ID' => $customer_value->last_order_name,
                                            'PHONE'=> $customer_value->phone,
                                            'ADDRESS'=> $address,
                                            'CITY'=> $city,
                                            'COUNTRY'=> $country,
                                            'ZIP'=> $zip,
                                            'WELCOME_POINTS'=> 0,
                                            'POINTS_EARN'=> 0,
                                            'TOTAL_POINTS'=> 0,
                                            'ANNIVERSARY'=> '',
                                            'BIRTHDAY'=> '',
                                            'POINTS_REDEEM'=> 0,
                                            'VIP_STATUS'=> ''
                                            );
                    $url_list='https://app.themistermail.com/api/v1/lists/'.$list_uid.'/subscribers/store?api_token='.$value->api_token;
                    $checkSubscriber=DB::table('subscribers')->where('mail_list_id',1)->where('email',$customer_value->email)->first();
                    if(empty($checkSubscriber)){
                        $ch = curl_init();
                        curl_setopt($ch, CURLOPT_URL,$url_list);
                        curl_setopt($ch, CURLOPT_POST, 1);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_POSTFIELDS,$mister_list_array);
                        $server_output = curl_exec($ch);
                        //echo "<pre>"; print_r($server_output);die;
                        curl_close ($ch);
                        //}
                         //order check
                        $order1_title='';
                        $order2_title='';
                        $order3_title='';
                        $order4_title='';
                        $order5_title='';
                        $order1_image='';
                        $order2_image='';
                        $order3_image='';
                        $order4_image='';
                        $order5_image='';
                        if($customer_value->orders_count > 0)
                        {

                            $urlo = 'https://' . $value->store_name . '/admin/customers/'.$customer_value->id.'/orders.json?limit=5';
                            $sessiono = curl_init();
                            curl_setopt($sessiono, CURLOPT_URL, $urlo);
                            curl_setopt($sessiono, CURLOPT_HEADER, false);
                            curl_setopt($sessiono, CURLOPT_HTTPHEADER, array('Accept: application/json', 'Content-Type: application/json', 'X-Shopify-Access-Token: ' . $access_token_data->access_token));
                            curl_setopt($sessiono, CURLOPT_RETURNTRANSFER, true);
                            $getOrders = json_decode(curl_exec($sessiono));
                            if(!empty($getOrders->orders)){
                                    foreach($getOrders->orders as $key=>$ord)
                                    {
                                        $product_id=$ord->line_items[0]->product_id;
                                        $urlp = 'https://' . $value->store_name . '/admin/products/'.$product_id.'.json';
                                        $sessionp = curl_init();
                                        curl_setopt($sessionp, CURLOPT_URL, $urlp);
                                        curl_setopt($sessionp, CURLOPT_HEADER, false);
                                        curl_setopt($sessionp, CURLOPT_HTTPHEADER, array('Accept: application/json', 'Content-Type: application/json', 'X-Shopify-Access-Token: ' . $access_token_data->access_token));
                                        curl_setopt($sessionp, CURLOPT_RETURNTRANSFER, true);
                                        $getProduct = json_decode(curl_exec($sessionp));
                                        if($key == 0)
                                        {
                                            $order1_title=$getProduct->product->title;
                                            $order1_image=$getProduct->product->images[0]->src;
                                            $mister_list_array=array(
                                                        'EMAIL'=>$customer_value->email,
                                                        'ORDER1_TITLE'=>$order1_title,
                                                        'ORDER1_IMAGE'=>$order1_image,
                                                        'IP_ADDR'=>$ord->browser_ip,
                                                        'LAST_PURCHASE'=>$ord->created_at
                                                        );
                                            $url_listt='https://app.themistermail.com/api/v1/lists/'.$list_uid.'/subscribers/store?api_token='.$value->api_token;
                                            $chh = curl_init();
                                            curl_setopt($chh, CURLOPT_URL,$url_listt);
                                            curl_setopt($chh, CURLOPT_POST, 1);
                                            curl_setopt($chh, CURLOPT_RETURNTRANSFER, true);
                                            curl_setopt($chh, CURLOPT_POSTFIELDS,$mister_list_array);
                                            $server_output = curl_exec($chh);
                                            curl_close ($chh);
                                        }
                                        if($key == 1)
                                        {
                                            $order2_title=$getProduct->product->title;
                                            $order2_image=$getProduct->product->images[0]->src;
                                            $mister_list_array=array(
                                                        'EMAIL'=>$customer_value->email,
                                                        'ORDER2_TITLE'=>$order2_title,
                                                        'ORDER2_IMAGE'=>$order2_image,
                                                         'IP_ADDR'=>$ord->browser_ip
                                                        );
                                            $url_listt='https://app.themistermail.com/api/v1/lists/'.$list_uid.'/subscribers/store?api_token='.$value->api_token;
                                            $chh = curl_init();
                                            curl_setopt($chh, CURLOPT_URL,$url_listt);
                                            curl_setopt($chh, CURLOPT_POST, 1);
                                            curl_setopt($chh, CURLOPT_RETURNTRANSFER, true);
                                            curl_setopt($chh, CURLOPT_POSTFIELDS,$mister_list_array);
                                            $server_output = curl_exec($chh);
                                            curl_close ($chh);
                                        }
                                        if($key == 2)
                                        {
                                            $order3_title=$getProduct->product->title;
                                            $order3_image=$getProduct->product->images[0]->src;
                                            $mister_list_array=array(
                                                        'EMAIL'=>$customer_value->email,
                                                        'ORDER3_TITLE'=>$order3_title,
                                                        'ORDER3_IMAGE'=>$order3_image,
                                                         'IP_ADDR'=>$ord->browser_ip
                                                        );
                                            $url_listt='https://app.themistermail.com/api/v1/lists/'.$list_uid.'/subscribers/store?api_token='.$value->api_token;
                                            $chh = curl_init();
                                            curl_setopt($chh, CURLOPT_URL,$url_listt);
                                            curl_setopt($chh, CURLOPT_POST, 1);
                                            curl_setopt($chh, CURLOPT_RETURNTRANSFER, true);
                                            curl_setopt($chh, CURLOPT_POSTFIELDS,$mister_list_array);
                                            $server_output = curl_exec($chh);
                                            curl_close ($chh);
                                        }
                                        if($key == 3)
                                        {
                                            $order4_title=$getProduct->product->title;
                                            $order4_image=$getProduct->product->images[0]->src;
                                            $mister_list_array=array(
                                                        'EMAIL'=>$customer_value->email,
                                                        'ORDER4_TITLE'=>$order4_title,
                                                        'ORDER4_IMAGE'=>$order4_image,
                                                         'IP_ADDR'=>$ord->browser_ip
                                                        );
                                            $url_listt='https://app.themistermail.com/api/v1/lists/'.$list_uid.'/subscribers/store?api_token='.$value->api_token;
                                            $chh = curl_init();
                                            curl_setopt($chh, CURLOPT_URL,$url_listt);
                                            curl_setopt($chh, CURLOPT_POST, 1);
                                            curl_setopt($chh, CURLOPT_RETURNTRANSFER, true);
                                            curl_setopt($chh, CURLOPT_POSTFIELDS,$mister_list_array);
                                            $server_output = curl_exec($chh);
                                            curl_close ($chh);
                                        }
                                        if($key == 4)
                                        {
                                            $order5_title=$getProduct->product->title;
                                            $order5_image=$getProduct->product->images[0]->src;
                                            $mister_list_array=array(
                                                        'EMAIL'=>$customer_value->email,
                                                        'ORDER5_TITLE'=>$order5_title,
                                                        'ORDER5_IMAGE'=>$order5_image,
                                                         'IP_ADDR'=>$ord->browser_ip
                                                        );
                                            $url_listt='https://app.themistermail.com/api/v1/lists/'.$list_uid.'/subscribers/store?api_token='.$value->api_token;
                                            $chh = curl_init();
                                            curl_setopt($chh, CURLOPT_URL,$url_listt);
                                            curl_setopt($chh, CURLOPT_POST, 1);
                                            curl_setopt($chh, CURLOPT_RETURNTRANSFER, true);
                                            curl_setopt($chh, CURLOPT_POSTFIELDS,$mister_list_array);
                                            $server_output = curl_exec($chh);
                                            curl_close ($chh);
                                        }
                                    }
                            }
                        }
                        echo $customer_value->email.'<br>';
                    }
                }
            if($getUserData->page == 1)
            {
                DB::table('users')->where('id',$value->id)->update(array('list_created'=>2));
            }
            else
            {
                DB::table('users')->where('id',$value->id)->update(array('page'=>$getUserData->page-1));
                echo "next page";die;
            }
        }
        echo "done";
    }

    public function saveJson(Request $request)
    {
        /*$get_list_url='https://app.themistermail.com/api/v1/lists?api_token=mCBbRLIVSGEQFH7cg0UbwGKKQJNSxdkotzPUOCdKglrPfND7DQGC7PXEltVi'; 
        $sessione = curl_init();
        curl_setopt($sessione, CURLOPT_URL, $get_list_url);
        curl_setopt($sessione, CURLOPT_HEADER, false);
        curl_setopt($sessione, CURLOPT_RETURNTRANSFER, true);
        $getAllList = json_decode(curl_exec($sessione));
        $list_uid='';
        foreach ($getAllList as $key => $value_list) {
           if($value_list->name == 'Sweatcoin')
           {
                $list_uid=$value_list->uid;
           }
        }
       // echo $list_uid;die;
        $path=public_path('/sheet/Book1.csv');
        ini_set('max_execution_time', 3000);
        $file = fopen($path,"r");
        $array=[];
        $i=0;
        while (($data = fgetcsv($file)) !== FALSE) {
            if($i > 0)
            {
                //echo "<pre>"; print_r($data);die;
                $created_at = date("Y-m-d", strtotime($data[1]));
                $updated_at = date("Y-m-d", strtotime($data[2])); 
                $country=  $data[3];
                $city=  $data[4];
                $address=  $data[5];
                $timezone=  $data[6];
                $last_opened_email=  $data[7];
                $first_opened_email=  $data[8];
                $total_emails_opened=  $data[9];
                $status=  $data[10];
                $shopify_plan=  $data[11];
                $currency=  $data[12];
                $name=  $data[13];
                $shop_domain=  $data[14];
                $shop_id=  $data[15];
                $shopify_domain=  $data[16];


                 $mister_list_array=array(
                            'EMAIL'=> $data[0],
                            'NAME'=>$name,
                            'STORE_NAME'=>$shopify_domain,
                            'STORE_URL'=>$shop_domain,
                            'PLAN'=>$shopify_plan,
                            'COUNTRY'=>$country,
                            'ADDRESS'=>$address,
                            'STATUS'=>$status,
                            'DOMAIN'=>$shop_domain,
                            'CITY'=>$city,
                            'CREATED_AT'=>$created_at,
                            'CURRENCY'=>$updated_at,
                            'TIME_ZONE'=>$timezone,
                            'SHOP_OWNER'=>$name,
                            'MYSHOPIFY_DOMAIN'=>$shopify_domain,
                            'UPDATED_AT'=>$updated_at,
                            'LAST_OPENED_EMAIL'=>$last_opened_email,
                            'FIRST_OPENED_EMAIL'=>$first_opened_email,
                            'TOTAL_EMAILS_OPENED'=>$total_emails_opened,
                            'SHOP_ID'=>$shop_id
                            );
//echo "<pre>";print_r($mister_list_array);die;
                $url_listt='https://app.themistermail.com/api/v1/lists/'.$list_uid.'/subscribers/store?api_token=mCBbRLIVSGEQFH7cg0UbwGKKQJNSxdkotzPUOCdKglrPfND7DQGC7PXEltVi';
                $chh = curl_init();
                curl_setopt($chh, CURLOPT_URL,$url_listt);
                curl_setopt($chh, CURLOPT_POST, 1);
                curl_setopt($chh, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($chh, CURLOPT_POSTFIELDS,$mister_list_array);
                $server_output = curl_exec($chh);
                //echo "<pre>"; print_r($server_output);die;
                curl_close ($chh);


            }
            echo "done".'<br>';
            $i++;
        }
        fclose($file);*/
       DB::table('saveJson')->insert(array('store_name'=>$request->shop,'json'=>$request->data));
    }
}
