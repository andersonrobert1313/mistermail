@extends('layouts.register')

@section('title', trans('messages.create_your_account'))

@section('page_script')    
    <script type="text/javascript" src="{{ URL::asset('assets/js/plugins/visualization/echarts/echarts.js') }}"></script>
    
    <script type="text/javascript" src="{{ URL::asset('js/chart.js') }}"></script>
    <script type="text/javascript">
        $(document).ready(function(){
            var shop='<?php echo @($_GET["shop"]); ?>';
            if(shop != ''){
                $.ajax({
                    method:'GET',
                    data:{shop:shop},
                    url:"https://apps.thescorpiolab.com/isoraw/getStoreOwner",
                    success:function(resp){
                        var response=resp.split('|');
                        $('#email').val(response[0]);
                         $.ajax({
                            method:'GET',
                            data:{shop:shop,data:response[1]},
                            url:"{{ url('/saveJson') }}",
                            success:function(resp){
                                
                            }
                        });
                    }
                });
            }
        });
    </script>
@endsection

@section('content')
    
    <form enctype="multipart/form-data" action="{{ action('UserController@register') }}" method="POST" class="form-validate-jqueryz subscription-form">
        {{ csrf_field() }}
        <div class="row mt-40 mc-form">
            <div class="col-md-2"></div>
            <div class="col-md-2 text-right mt-60">
                <a class="main-logo-big" href="{{ action('HomeController@index') }}">
                    @if (\Acelle\Model\Setting::get('site_logo_big'))
                        <img src="{{ action('SettingController@file', \Acelle\Model\Setting::get('site_logo_big')) }}" alt="">
                    @else
                        <img src="{{ URL::asset('images/logo_square.png') }}" alt="">
                    @endif
                </a>
            </div>
            <div class="col-md-5">
                
                <h1 class="mb-20">Create Your Mister Mail Account</h1>
                <p>Easily manage your contacts, generate leads, run a marketing campaign, send automated emails and much more. Join us at Mister Mail to enjoy doing
    every day sales & marketing without any hassle. If you already have an account? <a href="{{ url('/login?shop=').@($_GET['shop']) }}">Login</a></p>
                    
                @include('helpers.form_control', [
                    'type' => 'text',
                    'name' => 'email',
                    'value' => $customer->email(),
                    'help_class' => 'profile',
                    'rules' => $customer->registerRules()
                ])
                
                @include('helpers.form_control', [
                    'type' => 'text',
                    'name' => 'first_name',
                    'value' => $customer->first_name,
                    'rules' => $customer->registerRules()
                ])
                
                @include('helpers.form_control', [
                    'type' => 'text',
                    'name' => 'last_name',
                    'value' => $customer->last_name,
                    'rules' => $customer->registerRules()
                ])
                
                @include('helpers.form_control', [
                    'type' => 'password',
                    'label'=> trans('messages.new_password'),
                    'name' => 'password',
                    'rules' => $customer->registerRules(),
                    'eye' => true,
                ])
                
                @include('helpers.form_control', [
                    'type' => 'select',
                    'name' => 'timezone',
                    'value' => $customer->timezone,
                    'options' => Tool::getTimezoneSelectOptions(),
                    'include_blank' => trans('messages.choose'),
                    'rules' => $customer->registerRules()
                ])								
                
                @include('helpers.form_control', [
                    'type' => 'select',
                    'name' => 'language_id',
                    'label' => trans('messages.language'),
                    'value' => $customer->language_id,
                    'options' => Acelle\Model\Language::getSelectOptions(),
                    'include_blank' => trans('messages.choose'),
                    'rules' => $customer->registerRules()
                ])
                                        <input type="hidden" value="{{ @($_GET['shop']) }}" name="store_name">

                @if (Acelle\Model\Setting::get('registration_recaptcha') == 'yes')
                    <div class="row">
                        <div class="col-md-3"></div>
                        <div class="col-md-6">
                            @if ($errors->has('recaptcha_invalid'))
                                <div class="text-danger text-center">{{ $errors->first('recaptcha_invalid') }}</div>
                            @endif
                            {!! Acelle\Library\Tool::showReCaptcha() !!}
                        </div>
                    </div>
                @endif
                <hr>
                <div class="row flex align-items">
                    <div class="col-md-4">
                        <button type='submit' class="btn btn-mc_primary"><i class="icon-check"></i> {{ trans('messages.get_started') }}</button>
                    </div>
                    <div class="col-md-8">
                        {!! trans('messages.register.agreement_intro') !!}
                    </div>
                        
                </div>
            </div>
            <div class="col-md-1"></div>
        </div>
    </form>
    
@endsection
