<?php

namespace Acelle\Console\Commands;

use Illuminate\Console\Command;
use DB;

class ProductViewCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'productviewcron:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */

    public function handle()
    {
        DB::table('check_cron')->insert(array('test'=>'tt'));
        $misterUsers=DB::table('users')->where('list_created',1)->get();
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
            DB::table('users')->where('id',$value->id)->update(array('list_created'=>2));
        }
        /*for login*/
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
        /*end for login*/


         /*for ip*/
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
            $api_token_data=DB::table('users')->select('api_token')->where("store_name",$rec->store_name)->first();
            $subs_fields=DB::table('subscriber_fields')->where('value',$rec->ip_addr)->first();
            if(!empty($subs_fields))
            {
                 $subs_data=DB::table('subscribers')->where('id',261)->first();
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
        /*end for ip */
    }

   /* public function handle()
    {
        
    }*/
}
