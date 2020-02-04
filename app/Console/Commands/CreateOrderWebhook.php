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
                    DB::table('webhook_orders')->where('id',$value->id)->update(array('status'=>1));
                    $get_list_url='https://app.themistermail.com/api/v1/lists?api_token='.$getUser->api_token; 
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
                    DB::table('check_cron')->insert(array('test'=>$list_uid));
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
