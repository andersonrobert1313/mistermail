<?php

namespace Acelle\Http\Controllers\Admin;

use Illuminate\Http\Request;

use Acelle\Http\Requests;
use Acelle\Http\Controllers\Controller;
use Acelle\Model\Setting;
use Illuminate\Support\MessageBag;
use Acelle\Cashier\Cashier;
use Acelle\Cashier\Subscription;

class PaymentController extends Controller
{
    /**
     * Display all paymentt.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, MessageBag $message_bag)
    {
        return view('admin.payments.index', [
            'gateways' => Setting::getPaymentGateways(),            
        ]);
    }

    /**
     * Editing payment gateways.
     *
     * @param int $name
     *
     * @return \Illuminate\Http\Response
     */
    public function edit($name)
    {
        $service = Cashier::getPaymentGateway($name);
        $gateway = Setting::getPaymentGateway($name);
        
        return view('admin.payments.edit', [
            'gateway' => $gateway,
            'service' => $service,
        ]);
    }

    /**
     * Update payment gateway.
     *
     * @param \Illuminate\Http\Request $request
     * @param int                      $name
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, MessageBag $message_bag, $name)
    {
        $errors = new MessageBag;
        $gatewayService = Cashier::getPaymentGateway($name, $request->options);        
        
        try {
            $gatewayService->validate();
            Setting::updatePaymentGateway($name, $request->options);

            if ($request->save_and_set_primary) {
                $sc = Subscription::count();

                if ($sc > 0) {
                    throw new \Exception(trans('messages.gateway.error.subscription_exist'));
                }

                Setting::set('system.payment_gateway', $name);
            }

            $request->session()->flash('alert-success', trans('messages.payment_gateway.updated'));
            return redirect()->action('Admin\PaymentController@index');
        } catch (\Exception $ex) {
            // Add example error messages to the MessageBag instance.
            $errors->add('payment',
                trans('messages.payment_gateway.not_valid', ['message' => $ex->getMessage()]));

            return view('admin.payments.edit', [
                'gateway' => Setting::getPaymentGateway($name),
                'errors' => $errors,
                'service' => $gatewayService,
            ]);
        }
    }

    /**
     * Set payment gateway as primary.
     *
     * @param \Illuminate\Http\Request $request
     * @param int                      $name
     *
     * @return \Illuminate\Http\Response
     */
    public function setPrimary(Request $request, $name)
    {
        $service = Cashier::getPaymentGateway($name);

        try {
            $sc = Subscription::count();

            if ($sc > 0) {
                throw new \Exception(trans('messages.gateway.error.subscription_exist'));
            }
        } catch (\Exception $ex) {
            $request->session()->flash('alert-error', trans('messages.payment_gateway.not_valid', ['message' => $ex->getMessage()]));
            return redirect()->action('Admin\PaymentController@index');
        }

        try {
            $service->validate();

            Setting::set('system.payment_gateway', $name);

            $request->session()->flash('alert-success', trans('messages.payment_gateway.updated'));
            return redirect()->action('Admin\PaymentController@index');
        } catch (\Exception $ex) {
            $request->session()->flash('alert-error', trans('messages.payment_gateway.not_valid', ['message' => $ex->getMessage()]));
            return redirect()->action('Admin\PaymentController@edit', $name);
        }
    }
}
