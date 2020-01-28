<?php

namespace Acelle\Http\Middleware;

use Closure;

class Subscription
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $subscription = $request->user()->customer->subscription;
        
        // Check if customer dose not have subscription
        if (!is_object($subscription) || !$subscription->isActive() || !$subscription->plan->isActive()) {
            return redirect()->action('AccountSubscriptionController@index');
        }

        return $next($request);
    }
}
