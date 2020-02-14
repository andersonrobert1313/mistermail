<?php

namespace Acelle\Console\Commands;

use Illuminate\Console\Command;
use DB;


class CreateOrderWebhook extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'createorderwebhook:cron';

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
         DB::table('check_cron')->insert(array('test'=>'createorderwebhook'));
         $getOrders=DB::table('webhook_orders')->where('status',0)->get();
         foreach ($getOrders as $key => $value) {
             $getUser=DB::table('users')->where('store_name',$value->store_name)->first();
             if(!empty($getUser))
             {
                $getAutomation=DB::table('automation2s')->where('customer_id',$getUser->id)->where('automation_name','after-order')->first();
                if(!empty($getAutomation))
                {
                    $access_token_data=DB::table('access_tokens')->where('store_name',$value->store_name)->first();
                    $get_list_url='https://app.themistermail.com/api/v1/lists?api_token='.$getUser->api_token; 
                    $sessione = curl_init();
                    curl_setopt($sessione, CURLOPT_URL, $get_list_url);
                    curl_setopt($sessione, CURLOPT_HEADER, false);
                    curl_setopt($sessione, CURLOPT_RETURNTRANSFER, true);
                    $getAllList = json_decode(curl_exec($sessione));
                    $list_uid='';
                    foreach ($getAllList as $key => $value_list) {
                       if($value_list->name == 'Test')
                       {
                            $list_uid=$value_list->uid;
                       }
                    }
                    DB::table('check_cron')->insert(array('test'=>$list_uid));
                    $finalOrder=json_decode($value->data);
                    $product_id=$finalOrder->line_items[0]->product_id;
                    $urlp = 'https://' . $value->store_name . '/admin/products/'.$product_id.'.json';
                    $sessionp = curl_init();
                    curl_setopt($sessionp, CURLOPT_URL, $urlp);
                    curl_setopt($sessionp, CURLOPT_HEADER, false);
                    curl_setopt($sessionp, CURLOPT_HTTPHEADER, array('Accept: application/json', 'Content-Type: application/json', 'X-Shopify-Access-Token: ' . $access_token_data->access_token));
                    curl_setopt($sessionp, CURLOPT_RETURNTRANSFER, true);
                    $getProduct = json_decode(curl_exec($sessionp));
                    $order1_title=$getProduct->product->title;
                    $order1_image=$getProduct->product->images[0]->src;
                    $mister_list_array=array(
                            'EMAIL'=>$value->email,
                            'ORDER1_TITLE'=>$order1_title,
                            'ORDER1_IMAGE'=>$order1_image,
                            'LAST_PURCHASE'=>$finalOrder->created_at
                            );
                   // DB::table('check_cron')->insert(array('test'=>$getUser->api_token));
                    $url_listt='https://app.themistermail.com/api/v1/lists/'.$list_uid.'/subscribers/store?api_token='.$getUser->api_token;
                    $chh = curl_init();
                    curl_setopt($chh, CURLOPT_URL,$url_listt);
                    curl_setopt($chh, CURLOPT_POST, 1);
                    curl_setopt($chh, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($chh, CURLOPT_POSTFIELDS,$mister_list_array);
                    $server_output = curl_exec($chh);
                    curl_close ($chh);
                    $mailList=DB::table('mail_lists')->where('uid',$list_uid)->first();
                    $getSub=DB::table('subscribers')->where('mail_list_id',$mailList->id)->where('email',$value->email)->first();
                    $trackLog=DB::table('tracking_logs')->where('subscriber_id',$getSub->id)->orderBy('id','DESC')->first();
                    if(!empty($trackLog)){
                        $ClickLog=DB::table('click_logs')->where('message_id',$trackLog->message_id)->first();
                    }
                    if(!empty($ClickLog))
                    {
                        $campaingData=DB::table('campaigns')->where('id',$trackLog->campaign_id)->first();
                        DB::table('check_cron')->insert(array('test'=>'track'.$trackLog->campaign_id));
                        DB::table('check_cron')->insert(array('test'=>'track2'.$campaingData->id));
                        DB::table('check_cron')->insert(array('test'=>'finalOrder'.$finalOrder->total_price));
                        DB::table('check_cron')->insert(array('test'=>'totalOrders'.$campaingData->total_orders));

                        $newOrders=$campaingData->total_orders+1;
                        $newSales=$campaingData->sales+$finalOrder->total_price;

                        DB::table('check_cron')->insert(array('test'=>'sales'.$newSales));
                        DB::table('check_cron')->insert(array('test'=>'newOrders'.$newOrders));

                        DB::table('campaigns')->where('id',$campaingData->id)->update(array('sales'=>$newSales,'total_orders'=>$newOrders));
                    }
                    DB::table('check_cron')->insert(array('test'=>'done'));

                    DB::table('webhook_orders')->where('id',$value->id)->update(array('status'=>1));
                }
                else
                {
                    DB::table('webhook_orders')->where('id',$value->id)->delete();
                }
             }
             else
             {
                DB::table('webhook_orders')->where('id',$value->id)->delete();
             }
         }
    }
}
