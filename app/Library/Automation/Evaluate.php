<?php

namespace Acelle\Library\Automation;

use Acelle\Model\Email;
use DB;
use Acelle\Model\Subscriber;


class Evaluate extends Action
{
    protected $childYes;
    protected $childNo;

    public function __construct($params = [])
    {
        parent::__construct($params);

        $this->childYes = array_key_exists('childYes', $params) ? $params['childYes'] : null;
        $this->childNo = array_key_exists('childNo', $params) ? $params['childNo'] : null;
    }

    public function toJson()
    {
        $json = parent::toJson();
        $json = array_merge($json, [
            'childYes' => $this->childYes,
            'childNo' => $this->childNo,
        ]);

        return $json;
    }

    public function execute()
    {   
        // IMPORTANT
        // If this is the latest also the last action of the workflow
        // no more execute, just return true
        // UPDATE: check always, wait for new action
        // if (!is_null($this->last_executed)) {
        //     $this->autoTrigger->logger()->info('Latest also last action');
        //     return true;
        // }
        $result = $this->evaluateCondition();

        if (config('app.demo') == true) {
            $result = (bool)random_int(0, 1);
        }
        $this->evaluationResult = $result;

        $this->recordLastExecutedTime();
        return $result;
    }

    public function evaluateCondition()
    {
        $criterion = $this->getOption('type');
        $result = null;
        switch ($criterion) {
            case 'open':
                $result = $this->evaluateEmailOpenCondition();
                break;
                case 'makeapurchase':
                $result = $this->evaluateMakeApurchase();
                break;
                 case 'cartlessthan':
                $result = $this->evaluateCartLessThan();
                break;
                case 'orderlessthan':
                $result = $this->evaluateOrderLessThan();
                break;
                case 'contactcountryfrom':
                $result = $this->evaluateContactCountryFrom();
                break;
            default:
                # code...
                break;
        }

        return $result;
    }

    public function evaluateMakeApurchase()
    {
        //$this->autoTrigger->logger()->info(sprintf('automation id "%s",', $this->autoTrigger->automation2->mail_list_id));
        if($this->autoTrigger->automation2->automation_name == 'abandon-cart' || $this->autoTrigger->automation2->automation_name == 'browse-abandon')
        {
            $checkAbandon=DB::table('abandon_cart')->where('email',$this->autoTrigger->subscriber->email)->where('browse',1)->where('cart',1)->first();
            if(!empty($checkAbandon))
            {
                DB::table('auto_triggers')->where('id',$checkAbandon->browse_trigger_id)->delete();
            }
            $getSubscriber=Subscriber::where('mail_list_id',$this->autoTrigger->automation2->mail_list_id)->where('email',$this->autoTrigger->subscriber->email)->first();
            if(!empty($getSubscriber))
            {
                $date = $getSubscriber->getValueByTag('LAST_PURCHASE');
                if(!empty($date)){
                    $exDate=explode("T", $date);
                    $date2 = date('Y-m-d');
                    $timestamp1 = strtotime($exDate[0]);
                    $timestamp2 = strtotime($date2);
                    $hours = abs($timestamp2 - $timestamp1)/(60*60); 
                    ///$this->autoTrigger->logger()->info(sprintf('Difference between two dates is  "%s",', $hour));
                    if($hours >= 48)
                    {   
                        return true;
                    }   
                    else
                    {
                         DB::table('abandon_cart')->where('email',$this->autoTrigger->subscriber->email)->delete();
                         DB::table('auto_triggers')->where('id',$this->autoTrigger->id)->delete();
                    }
                }
                else
                {
                    return true;
                }
            }
        }
        elseif($this->autoTrigger->automation2->automation_name == 'winback-after-order')
        {
            //$this->autoTrigger->logger()->info(sprintf('enter winback order'));
            $getSubscriber=Subscriber::where('mail_list_id',$this->autoTrigger->automation2->mail_list_id)->where('email',$this->autoTrigger->subscriber->email)->first();
            if(!empty($getSubscriber))
            {
                $date = $getSubscriber->getValueByTag('LAST_PURCHASE');
                if(!empty($date)){
                    $exDate=explode("T", $date);
                    $last_order_Date=$exDate[0];
                    $minus="-".$this->autoTrigger->automation2->automation_wait;
                    $minusDate = date("Y-m-d", strtotime($minus));

                    if($last_order_Date > $minusDate && $last_order_Date < date('Y-m-d')) {
                        //$this->autoTrigger->logger()->info(sprintf('between'));
                        DB::table('auto_triggers')->where('id',$this->autoTrigger->id)->delete();
                    } else {
                         //$this->autoTrigger->logger()->info(sprintf('No between'));
                         return true;
                    } 
                }
                else
                {
                    return true;
                }
            }
        }
        elseif($this->autoTrigger->automation2->automation_name == 'winback-no-order')
        {
            $getSubscriber=Subscriber::where('mail_list_id',$this->autoTrigger->automation2->mail_list_id)->where('email',$this->autoTrigger->subscriber->email)->first();
            if(!empty($getSubscriber))
            {
                $date = $getSubscriber->getValueByTag('LAST_PURCHASE');
                if(empty($date)){
                    return true;
                }
                else
                {
                      DB::table('auto_triggers')->where('id',$this->autoTrigger->id)->delete();
                }
            }
        }
        else
        {
            $getSubscriber=Subscriber::where('mail_list_id',$this->autoTrigger->automation2->mail_list_id)->where('email',$this->autoTrigger->subscriber->email)->first();
            if(!empty($getSubscriber))
            {
                $date = $getSubscriber->getValueByTag('LAST_PURCHASE');
                if(!empty($date)){
                    $exDate=explode("T", $date);
                    $date2 = date('Y-m-d');
                    $timestamp1 = strtotime($exDate[0]);
                    $timestamp2 = strtotime($date2);
                    $hours = abs($timestamp2 - $timestamp1)/(60*60); 
                    ///$this->autoTrigger->logger()->info(sprintf('Difference between two dates is  "%s",', $hour));
                    if($hours >= 48)
                    {   
                        return true;
                    }   
                    else
                    {
                         DB::table('auto_triggers')->where('id',$this->autoTrigger->id)->delete();
                    }
                }
                else
                {
                    return true;
                }
            }
        }
    }

    public function evaluateCartLessThan()
    {
        $getSubscriber=Subscriber::where('mail_list_id',$this->autoTrigger->automation2->mail_list_id)->where('email',$this->autoTrigger->subscriber->email)->first();
        if(!empty($getSubscriber))
        {
            $this->autoTrigger->logger()->info(sprintf('subscriber found'));
            $cart_value = $getSubscriber->getValueByTag('CART_VALUE');
            if(!empty($cart_value)){
                $cartlessthan=$this->autoTrigger->automation2->cartlessthan;
                if($cart_value < $cartlessthan)
                {   
                    $this->autoTrigger->logger()->info(sprintf('cart less send email'));
                    return true;
                }   
                else
                {
                     $this->autoTrigger->logger()->info(sprintf('cart not less delete'));
                     DB::table('auto_triggers')->where('id',$this->autoTrigger->id)->delete();
                }
            }
            else
            {
                DB::table('auto_triggers')->where('id',$this->autoTrigger->id)->delete();
            }
        }
    }

    public function evaluateOrderLessThan()
    {
        return false;
        $getSubscriber=Subscriber::where('mail_list_id',$this->autoTrigger->automation2->mail_list_id)->where('email',$this->autoTrigger->subscriber->email)->first();
        if(!empty($getSubscriber))
        {
            $this->autoTrigger->logger()->info(sprintf('subscriber found'));
            $cart_value = $getSubscriber->getValueByTag('CART_VALUE');
            if(!empty($cart_value)){
                $cartlessthan=$this->autoTrigger->automation2->cartlessthan;
                if($cart_value < $cartlessthan)
                {   
                    $this->autoTrigger->logger()->info(sprintf('cart less send email'));
                    return true;
                }   
                else
                {
                     $this->autoTrigger->logger()->info(sprintf('cart not less delete'));
                     DB::table('auto_triggers')->where('id',$this->autoTrigger->id)->delete();
                }
            }
            else
            {
                DB::table('auto_triggers')->where('id',$this->autoTrigger->id)->delete();
            }
        }
    }

    public function evaluateContactCountryFrom()
    {
        $getSubscriber=Subscriber::where('mail_list_id',$this->autoTrigger->automation2->mail_list_id)->where('email',$this->autoTrigger->subscriber->email)->first();
        if(!empty($getSubscriber))
        {
            $this->autoTrigger->logger()->info(sprintf('subscriber found'));
            $country = $getSubscriber->getValueByTag('COUNTRY');
            if(!empty($country)){
                $selectCountry=$this->autoTrigger->automation2->country;
                if($country == $selectCountry)
                {   
                    $this->autoTrigger->logger()->info(sprintf('country match'));
                    return true;
                }   
                else
                {
                     $this->autoTrigger->logger()->info(sprintf('country not match'));
                     DB::table('auto_triggers')->where('id',$this->autoTrigger->id)->delete();
                }
            }
            else
            {
                DB::table('auto_triggers')->where('id',$this->autoTrigger->id)->delete();
            }
        }
    }




    public function evaluateEmailOpenCondition()
    {
        $emailUid = $this->getOption('email');
        $email = Email::findByUid($emailUid);
        return $email->isOpened($this->autoTrigger->subscriber);
    }

    public function getActionDescription()
    {
        $nameOrEmail = $this->autoTrigger->subscriber->getFullNameOrEmail();
        return sprintf('User %s reads email entitled "Welcome email"', $nameOrEmail);
    }

    public function hasChild($e)
    {
        if (is_null($this->childYes) && is_null($this->childNo)) {
            return false;
        }

        return ($e->getId() == $this->childYes || $e->getId() == $this->childNo);
    }

    public function getNextActionId()
    {
        if ($this->evaluationResult) {
            return $this->childYes;
        } else {
            return $this->childNo;
        }
    }
}
