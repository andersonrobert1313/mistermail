                <div class="row">
                    <div class="col-md-3">
                        <div style="background-color:#607D8B;border-color:#607D8B" class="panel panel-white bg-teal-400">
                            <div class="panel-body text-center">
                                <h2 class="text-semibold mb-10 mt-0">{{ $campaign->openCount() }}</h2>
                                <div class="text-muted">{{ trans('messages.opened') }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div style="background-color:#03A9F4;border-color:#03A9F4" class="panel panel-white bg-teal-400">
                            <div class="panel-body text-center">
                                <h2 class="text-semibold mb-10 mt-0">{{ $campaign->clickCount() }}</h2>
                                <div class="text-muted">{{ trans('messages.clicked') }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div style="background-color:#795548;border-color:#795548" class="panel panel-white bg-teal-400">
                            <div class="panel-body text-center">
                                <h2 class="text-semibold mb-10 mt-0">{{ $campaign->bounceCount() }}</h2>
                                <div class="text-muted">{{ trans('messages.bounced') }}</div>
                            </div>
                        </div>
                    </div>
                    <div  class="col-md-3">
                        <div style="background-color:#d81b60;border-color:#d81b60" class="panel panel-white bg-teal-400">
                            <div class="panel-body text-center">
                                <h2 class="text-semibold mb-10 mt-0">{{ $campaign->unsubscribeCount() }}</h2>
                                <div class="text-muted">{{ trans('messages.unsubscribed') }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-3">
                        <div  style="background-color:#ffffff;border:2px solid #FF9800;color:#000000" class="panel panel-white bg-teal-400">
                            <div class="panel-body text-center">
                                <h2 class="text-semibold mb-10 mt-0">${{ $campaign->sales }}</h2>
                                <div class="text-muted" style="color:#000000">Sales</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div style="background-color:#ffffff;border:2px solid #26A69A;color:#000000" class="panel panel-white bg-teal-400">
                            <div class="panel-body text-center">
                                <h2 class="text-semibold mb-10 mt-0">{{ $campaign->total_orders }}</h2>
                                <div class="text-muted" style="color:#000000">Orders</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div style="background-color:#ffffff;border:2px solid #7cb342;color:#000000" class="panel panel-white bg-teal-400">
                            <div class="panel-body text-center">
                                <h2 class="text-semibold mb-10 mt-0">${{ $campaign->sales/$campaign->total_orders }}</h2>
                                <div class="text-muted" style="color:#000000">Average Order Value</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div style="background-color:#ffffff;border:2px solid #e53935;color:#000000" class="panel panel-white bg-teal-400">
                            <div class="panel-body text-center">
                                <h2 class="text-semibold mb-10 mt-0">{{ @(($campaign->total_orders/$campaign->clickCount())*100) }}%</h2>
                                <div class="text-muted" style="color:#000000">Conversion Rate</div>
                            </div>
                        </div>
                    </div>
                </div>