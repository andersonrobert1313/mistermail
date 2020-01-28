<?php

namespace Acelle\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log as LaravelLog;
use Acelle\Cashier\Subscription;
use Acelle\Model\Setting;
use Acelle\Model\Plan;
use Acelle\Cashier\Cashier;
use Acelle\Cashier\Services\StripeGatewayService;
use Carbon\Carbon;
use Acelle\Cashier\SubscriptionParam;

class AccountSubscriptionController extends Controller
{
    /**
     * Customer subscription main page.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     **/
    public function index(Request $request)
    {
        // Check if system dosen't have payment gateway
        if (!Setting::get('system.payment_gateway')) {
            return view('noPrimaryPayment');
        }

        $customer = $request->user()->customer;
        $gateway = Cashier::getPaymentGateway();
        
        // Get current subscription
        $subscription = $customer->subscription;

        // Customer dose not have subscription
        if (!is_object($subscription)) {
            return redirect()->action('AccountSubscriptionController@selectPlan');
        }

        if (!$subscription->plan->isActive()) {
            return view('account.subscription.error', ['message' => __('messages.subscription.error.plan-not-active', [ 'name' => $subscription->plan->name])]);
        }
        
        // Check if subscriotion is new
        if ($subscription->isNew()) {
            return redirect()->action('AccountSubscriptionController@checkout');
        }
        
        // retrieve subscription info
        $subscriptionParam = $gateway->sync($subscription);
        
        // Check if subscriotion is new
        if ($subscription->isPending()) {
            return redirect()->action('AccountSubscriptionController@pending');
        }
        
        // if subscription is ended atfer sygned
        if ($subscription->ended()) {
            return redirect()->action('AccountSubscriptionController@selectPlan');
        }
        
        return view('account.subscription.index', [
            'subscription' => $subscription,
            'gateway' => $gateway,
            'plan' => $subscription->plan,
        ]);
    }
    
    /**
     * Slect plan.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     **/
    public function selectPlan(Request $request)
    {
        $customer = $request->user()->customer;
        $subscription = $customer->subscription;
        $plans = Plan::getAvailablePlans();
        $planCount = Plan::getAllActive()->count();
        $colWidth = ($planCount == 0) ? 0 :  round(85 / $planCount );

        if (isset($subscription) && !$subscription->ended()) {
            $request->session()->flash('alert-error', 'Already subscribed a plan!');
            return redirect()->action('AccountSubscriptionController@index');
        }

        return view('account.subscription.select_plan', [
            'plans' => $plans,
            'colWidth' => $colWidth,
        ]);
    }
    
    /**
     * Store customer subscription.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     **/
    public function create(Request $request)
    {
        // Get current customer
        $customer = $request->user()->customer;
        $plan = Plan::findByUid($request->plan_uid);
        $gateway = Cashier::getPaymentGateway();

        // Create subscription
        $subscription = $gateway->create($customer, $plan);        

        try {
            \Mail::to($customer->user->email)->send(new \Acelle\Mail\SubscriptionDoneMailer($subscription));
        } catch (\Exception $e) {
            $request->session()->flash('alert-error', 'Can not send email: ' . $e->getMessage());
        }
        
        // Check if subscriotion is new
        if ($subscription->isActive()) {
            return redirect()->action('AccountSubscriptionController@index');
        }

        return redirect()->action('AccountSubscriptionController@checkout');
    }
    
    /**
     * Review customer subscription.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     **/
    public function review(Request $request)
    {
        // Get current customer
        $customer = $request->user()->customer;
        $subscription = $customer->subscription;
        $plan = Plan::findByUid($request->plan_uid);
        
        if (isset($subscription) && !$subscription->ended()) {
            $request->session()->flash('alert-error', 'Already subscribed a plan!');
            return redirect()->action('AccountSubscriptionController@index');
        }
        
        return view('account.subscription.review', [
            'plan' => $plan,
        ]);
    }
    
    /**
     * Subscription checkout page.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     **/
    public function checkout(Request $request)
    {
        $customer = $request->user()->customer;
        $subscription = $customer->subscription;
        
        // Authorization
        if (!$request->user()->customer->can('checkout', $subscription)) {
            return $this->notAuthorized();
        }
        
        return redirect()->action("\Acelle\Cashier\Controllers\\" . ucfirst(config('cashier.gateway')) . "Controller@checkout", [
            'subscription_id' => $subscription->uid,
            'return_url' => action('AccountSubscriptionController@index'),
        ]);
    }
    
    /**
     * Subscription pending page.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     **/
    public function pending(Request $request)
    {
        $customer = $request->user()->customer;
        $subscription = $customer->subscription;
        
        // Authorization
        if (!$request->user()->customer->can('checkout', $subscription)) {
            return $this->notAuthorized();
        }
        
        return redirect()->action("\Acelle\Cashier\Controllers\\" . ucfirst(config('cashier.gateway')) . "Controller@pending", [
            'subscription_id' => $subscription->uid,
            'return_url' => action('AccountSubscriptionController@index'),
        ]);
    }
    
    ///**
    // * Subscription charge.
    // *
    // * @param \Illuminate\Http\Request $request
    // *
    // * @return \Illuminate\Http\Response
    // **/
    //public function charge(Request $request)
    //{
    //    // Get current customer
    //    $customer = $request->user()->customer;
    //    $gateway = Cashier::getPaymentGateway();
    //    $subscription = $customer->subscription;
    //
    //    if ($request->isMethod('post')) {
    //        // subscribe to plan
    //        $gateway->charge($subscription);
    //
    //        // Redirect to my subscription page
    //        $request->session()->flash('alert-success', trans('messages.subscription.finished', ['plan' => $subscription->plan->name]));
    //        return redirect()->action('AccountSubscriptionController@index');
    //    }
    //
    //    return view('account.subscription.charge', [
    //        'subscription' => $subscription,
    //    ]);
    //}
    
    /**
     * Cancel subscription at the end of current period.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function cancel(Request $request)
    {
        $customer = $request->user()->customer;
        $gateway = Cashier::getPaymentGateway();
        $subscription = $customer->subscription;

        if ($request->user()->customer->can('cancel', $subscription)) {
            $gateway->cancel($subscription);
        }

        // Redirect to my subscription page
        $request->session()->flash('alert-success', trans('messages.subscription.cancelled'));
        return redirect()->action('AccountSubscriptionController@index');
    }

    /**
     * Cancel subscription at the end of current period.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function resume(Request $request)
    {
        $customer = $request->user()->customer;
        $gateway = Cashier::getPaymentGateway();
        $subscription = $customer->subscription;

        if ($request->user()->customer->can('resume', $subscription)) {
            $gateway->resume($subscription);
        }

        // Redirect to my subscription page
        $request->session()->flash('alert-success', trans('messages.subscription.resumed'));
        return redirect()->action('AccountSubscriptionController@index');
    }
    
    /**
     * Cancel now subscription at the end of current period.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function cancelNow(Request $request)
    {
        $customer = $request->user()->customer;
        // Get current subscription
        $subscription = $customer->subscription;
        $gateway = Cashier::getPaymentGateway();

        if ($request->user()->customer->can('cancelNow', $subscription)) {
            $gateway->cancelNow($subscription);
        }

        // Redirect to my subscription page
        $request->session()->flash('alert-success', trans('messages.subscription.cancelled_now'));
        return redirect()->action('AccountSubscriptionController@index');
    }
    
    /**
     * Change plan.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     **/
    public function changePlan(Request $request)
    {        
        $customer = $request->user()->customer;
        $subscription = $customer->subscription;
        $gateway = Cashier::getPaymentGateway();
        $plans = Plan::getAvailablePlans();
        
        // Authorization
        if (!$request->user()->customer->can('changePlan', $subscription)) {
            return $this->notAuthorized();
        }
        
        if ($request->isMethod('post')) {            
            $plan = Plan::findByUid($request->plan_uid);
            
            try {
                // change plan
                $gateway->changePlan($subscription, $plan);
            } catch (\Exception $e) {
                $request->session()->flash('alert-error', 'Can not change plan: ' . $e->getMessage());
                return redirect()->action('AccountSubscriptionController@index');
            }
            
            $request->session()->flash('alert-success', trans('messages.subscription.plan_changed'));
            return redirect()->away(Cashier::StripeControllerUrl($subscription, action('AccountSubscriptionController@index')));
        }
        
        return view('account.subscription.change_plan', [
            'subscription' => $subscription,
            'gateway' => $gateway,
            'plans' => $plans,
        ]);
    }    
    
    /**
     * Change plan.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     **/
    public function renew(Request $request)
    {        
        $customer = $request->user()->customer;
        $subscription = $customer->subscription;
        $gateway = Cashier::getPaymentGateway();
        
        // Authorization
        if (!$request->user()->customer->can('renew', $subscription)) {
            return $this->notAuthorized();
        }
        
        // check if status is not pending
        if ($gateway->hasPending($subscription)) {
            throw new \Exception("Can not renew pending subscription!");
        }
        
        $gateway->renew($subscription);
        
        return redirect()->away($gateway->getPendingUrl($subscription, action('AccountSubscriptionController@index')));
    }
    
    
    

    ///**
    // * New customer subscription.
    // *
    // * @param \Illuminate\Http\Request $request
    // *
    // * @return \Illuminate\Http\Response
    // **/
    //public function new(Request $request)
    //{
    //    $customer = $request->user()->customer;
    //    $subscription = $customer->subscription;
    //
    //    return view('account.subscription.new');
    //}   

    ///**
    // * Card information.
    // *
    // * @param \Illuminate\Http\Request $request
    // *
    // * @return \Illuminate\Http\Response
    // **/
    //public function card(Request $request)
    //{
    //    // Get current customer
    //    $customer = $request->user()->customer;
    //    $subscription = $customer->subscription;
    //    $gatewayService = Cashier::getPaymentGateway();
    //
    //    return view('account.subscription.card', [
    //        'gateway' => Setting::getPaymentGateway(Setting::get('system.payment_gateway')),
    //        'customer' => $customer,
    //        'gatewayService' => $gatewayService,
    //        'subscription' => $subscription,
    //    ]);
    //}
    //
    ///**
    // * Subscribe with card information.
    // *
    // * @param \Illuminate\Http\Request $request
    // *
    // * @return \Illuminate\Http\Response
    // **/
    //public function updateCard(Request $request)
    //{
    //    // Get current customer
    //    $customer = $request->user()->customer;
    //    $gateway = Cashier::getPaymentGateway();
    //    $subscription = $customer->subscription;
    //
    //    if ($request->isMethod('post')) {
    //        // update card
    //        $gateway->billableUserUpdateCard($customer, $request->all());
    //
    //        // Redirect to my subscription page
    //        // $request->session()->flash('alert-success', trans('messages.subscription.created'));
    //        return redirect()->action('AccountSubscriptionController@pay');
    //    }
    //
    //    return view('account.subscription.update_card', [
    //        'gateway' => Setting::getPaymentGateway(Setting::get('system.payment_gateway')),
    //        'plan' => $subscription->plan,
    //        'subscription' => $subscription,
    //    ]);
    //}

    ///**
    // * Subscription pay.
    // *
    // * @param \Illuminate\Http\Request $request
    // *
    // * @return \Illuminate\Http\Response
    // **/
    //public function pay(Request $request)
    //{
    //    // Get current customer
    //    $customer = $request->user()->customer;
    //    $gateway = Cashier::getPaymentGateway();
    //    $subscription = $customer->subscription;
    //
    //    if ($request->isMethod('post')) {
    //        // subscribe to plan
    //        $subscription->charge($gateway);
    //
    //        // Redirect to my subscription page
    //        $request->session()->flash('alert-success', trans('messages.subscription.finished', ['plan' => $subscription->plan->name]));
    //        return redirect()->action('AccountSubscriptionController@index');
    //    }
    //
    //    return view('account.subscription.pay', [
    //        'subscription' => $subscription,
    //    ]);
    //}
    //
    ///**
    // * New customer subscription.
    // *
    // * @param \Illuminate\Http\Request $request
    // *
    // * @return \Illuminate\Http\Response
    // **/
    //public function pending(Request $request)
    //{
    //    $customer = $request->user()->customer;
    //    $subscription = $customer->subscription;
    //    $gatewayService = Cashier::getPaymentGateway();
    //
    //    if (!isset($subscription)) {
    //        return redirect()->action('AccountSubscriptionController@index');
    //    }
    //
    //    //
    //    if (!$subscription->isPending()) {
    //        return redirect()->action('AccountSubscriptionController@index');
    //    }
    //
    //    // retrieve subscription info
    //    $subscription->retrieve($gatewayService);
    //
    //    return view('account.subscription.pending', [
    //        'subscription' => $subscription,
    //        'gateway' => Setting::getPaymentGateway(Setting::get('system.payment_gateway')),
    //        'gatewayService' => $gatewayService,
    //    ]);
    //}

    

    ///**
    // * Change plan.
    // *
    // * @param \Illuminate\Http\Request $request
    // *
    // * @return \Illuminate\Http\Response
    // **/
    //public function changePlanNoRecurring(Request $request)
    //{
    //    $customer = $request->user()->customer;
    //    // Get current subscription
    //    $subscription = $customer->subscription;
    //    $newPlan = Plan::findByUid($request->plan_id);
    //    $gatewayService = Cashier::getPaymentGateway();
    //
    //    // @todo can not change to lower one
    //    if ($customer->calcChangePlan($newPlan)['amount'] < 0) {
    //        // Redirect to my subscription page
    //        $request->session()->flash('alert-error', trans('messages.subscription.plan_changed.lower_error'));
    //        return redirect()->action('AccountSubscriptionController@index');
    //    }
    //
    //    // Authorization
    //    if (!$request->user()->customer->can('changePlan', $subscription)) {
    //        return redirect()->action('AccountSubscriptionController@index');
    //    }
    //
    //    if ($request->isMethod('post')) {
    //        $gatewayService->changePlan($customer, $newPlan);
    //
    //        // Redirect to my subscription page
    //        $request->session()->flash('alert-success', trans('messages.subscription.plan_changed'));
    //        return redirect()->action('AccountSubscriptionController@index');
    //    }
    //
    //    return view('account.subscription.change_plan_no_recurring', [
    //        'subscription' => $subscription,
    //        'gateway' => Setting::getPaymentGateway(Setting::get('system.payment_gateway')),
    //        'gatewayService' => $gatewayService,
    //        'customer' => $customer,
    //        'newPlan' => $newPlan,
    //    ]);
    //}

    /**
     * Download raw invoices.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     **/
    public function downloadRawInvoice(Request $request)
    {
        $subscription = $request->user()->customer->subscription;
        $gatewayService = Cashier::getPaymentGateway();


        return response(var_export($gatewayService->getRawInvoices($subscription)), 200, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => 'attachment; filename="payment_logs.txt"',
        ]);
    }

    ///**
    // * Claim subscription.
    // *
    // * @param \Illuminate\Http\Request $request
    // *
    // * @return \Illuminate\Http\Response
    // **/
    //public function paymentClaim(Request $request)
    //{
    //    // Get current customer
    //    $customer = $request->user()->customer;
    //    $subscription = $customer->subscription;
    //
    //    // subscribe to plan
    //    $subscription->claimPayment();
    //
    //    // Redirect to my subscription page
    //    return redirect()->action('AccountSubscriptionController@index');
    //}
}
