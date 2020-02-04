@extends('layouts.frontend')

@section('title', $list->name . ": " . trans('messages.create_subscriber'))

@section('page_script')
    <script type="text/javascript" src="{{ URL::asset('assets/js/plugins/forms/styling/uniform.min.js') }}"></script>
    <script type="text/javascript" src="{{ URL::asset('assets/js/plugins/pickers/anytime.min.js') }}"></script>
    <script type="text/javascript" src="{{ URL::asset('js/validate.js') }}"></script>
@endsection
<style type="text/css">
    .borderClass
    {
        border: 1px solid #aaa;
        border-radius: 5px;
        margin-left: 10px;
    }
    .padding-l0
    {
        padding-left:30px !important;
    }
    .lastDiv
    {
        width: 30% !important;
    }
    .imgClass{
       width: 50px;border-radius: 50%;border: 1px solid #ddd;margin-bottom:10px;margin-left:10px
    }
</style>
@section('page_header')

    @include("lists._header")

@endsection

@section('content')
    @include("lists._menu")

    <div class="row">
        <div  class="borderClass col-sm-12 col-md-4 col-lg-4">
            <div class="sub-section">
                <form enctype="multipart/form-data"  action="{{ action('SubscriberController@update', ['list_uid' => $list->uid, "uid" => $subscriber->uid]) }}" method="POST" class="form-validate-jqueryz">
                    {{ csrf_field() }}
                    <input type="hidden" name="_method" value="PATCH">
                    <input type="hidden" name="list_uid" value="{{ $list->uid }}" />
                    @include('helpers._upload',['src' => action('SubscriberController@avatar',  $subscriber->uid), 'dragId' => 'upload-avatar', 'preview' => 'image'])
                  
                    <h3 style="color:#335eea !important" class="clear-both">Contact Info</h3>
                    <?php 
                    if($list->name == 'All'){
                        foreach ($list->getFields as $field) {
                            if ($field->visible || !isset($is_page)){
                                if ($field->label == 'Email' && !empty($values[$field->tag])){ 
                                    ?>
                                    <h5><b>{{ $field->label }}</b> : {{ $values[$field->tag] }}</h5>
                                <?php } 
                                 if ($field->label == 'First name' && !empty($values[$field->tag])){ 
                                    ?>
                                        <h5><b>{{ $field->label }}</b> : {{ $values[$field->tag] }}</h5>
                                <?php } 
                                 if ($field->label == 'Last name' && !empty($values[$field->tag])){ ?>
                                        <h5><b>{{ $field->label }}</b> : {{ $values[$field->tag] }}</h5>
                                <?php } 
                                
                                 if ($field->label == 'Orders Count' && $field->tag != 0){ ?>
                                        <h5><b>{{ $field->label }}</b> : {{ $values[$field->tag] }}</h5>
                                <?php } 
                                
                                 if ($field->label == 'Phone' && !empty($values[$field->tag])){ ?>
                                        <h5><b>{{ $field->label }}</b> : {{ $values[$field->tag] }}</h5>
                                <?php } 
                                 if ($field->label == 'Address' && !empty($values[$field->tag])){ ?>
                                        <h5><b>{{ $field->label }}</b> : {{ $values[$field->tag] }}</h5>
                                <?php } 
                                 if ($field->label == 'City' && !empty($values[$field->tag])){ ?>
                                        <h5><b>{{ $field->label }}</b> : {{ $values[$field->tag] }}</h5>
                                <?php } 
                                 if ($field->label == 'Country' && !empty($values[$field->tag])){ ?>
                                        <h5><b>{{ $field->label }}</b> : {{ $values[$field->tag] }}</h5>
                                <?php } 
                                if ($field->label == 'Zip' && !empty($values[$field->tag])){ ?>
                                        <h5><b>{{ $field->label }}</b> : {{ $values[$field->tag] }}</h5>
                                <?php }
                               
                                
                            }
                        }
                        ?>
                    <h3 style="color:#335eea !important"  class="clear-both">Email Activity</h3>    
                    <?php 
                        foreach ($list->getFields as $field) {
                            if ($field->visible || !isset($is_page)){
                                 if ($field->label == 'Status' && !empty($values[$field->tag])){ ?>
                                        <h5><b>{{ $field->label }}</b> : {{ $values[$field->tag] }}</h5>
                                <?php } 
                            }
                        }?>
                    <h3 style="color:#335eea !important"  class="clear-both">Web Activity</h3>    
                    <?php 
                        foreach ($list->getFields as $field) {
                            if ($field->visible || !isset($is_page)){
                                 if ($field->label == 'Created At' && !empty($values[$field->tag])){ ?>
                                        <h5><b>{{ $field->label }}</b> : {{ date("Y-m-d h:i:s", strtotime($values[$field->tag])) }}</h5>
                                <?php } 
                                 if ($field->tag == 'TOTAL_SPENT' && !empty($values[$field->tag])){ ?>
                                        <h5><b>{{ $field->label }}</b> : {{ $values[$field->tag] }}</h5>
                                <?php } 
                                 if ($field->label == 'Last Order Id' && !empty($values[$field->tag])){ ?>
                                        <h5><b>{{ $field->label }}</b> : {{ $values[$field->tag] }}</h5>
                                <?php } 
                                if ($field->tag == 'AVERAGE_ORDER_VALUE' && !empty($values[$field->tag])){ ?>
                                        <h5><b>{{ $field->label }}</b> : {{ $values[$field->tag] }}</h5>
                                <?php }
                                 if ($field->tag == 'TOTAL_PRODUCTS_IN_CART' && !empty($values[$field->tag])){ ?>
                                        <h5><b>{{ $field->label }}</b> : {{ $values[$field->tag] }}</h5>
                                <?php }
                                 if ($field->tag == 'CART_LAST_UPDATED' && !empty($values[$field->tag])){ ?>
                                        <h5><b>{{ $field->label }}</b> : {{  date("Y-m-d h:i:s", strtotime($values[$field->tag])) }}</h5>
                                <?php }
                                 if ($field->tag == 'CART_VALUE' && !empty($values[$field->tag])){ ?>
                                        <h5><b>{{ $field->label }}</b> : {{ $values[$field->tag] }}</h5>
                                <?php }
                                 if ($field->tag == 'LAST_PURCHASE' && !empty($values[$field->tag])){ ?>
                                        <h5><b>{{ $field->label }}</b> : {{  date("Y-m-d h:i:s", strtotime($values[$field->tag])) }}</h5>
                                <?php }
                                 if ($field->tag == 'LAST_SEEN' && !empty($values[$field->tag])){ ?>
                                        <h5><b>{{ $field->label }}</b> : {{ $values[$field->tag] }}</h5>
                                <?php }
                                 if ($field->tag == 'TOTAL_PURCHASES' && !empty($values[$field->tag])){ ?>
                                        <h5><b>{{ $field->label }}</b> : {{ $values[$field->tag] }}</h5>
                                <?php }
                                 if ($field->tag == 'TOTAL_PAGE_VIEWS' && !empty($values[$field->tag])){ ?>
                                        <h5><b>{{ $field->label }}</b> : {{ $values[$field->tag] }}</h5>
                                <?php }
                                 if ($field->tag == 'TOTAL_VIEWED_PRODUCTS' && !empty($values[$field->tag])){ ?>
                                        <h5><b>{{ $field->label }}</b> : {{ $values[$field->tag] }}</h5>
                                <?php }
                                 if ($field->tag == 'LAST_PAGE_VIEW' && !empty($values[$field->tag])){ ?>
                                        <h5><b>{{ $field->label }}</b> : {{ $values[$field->tag] }}</h5>
                                <?php }
                            }
                        }?>
                    <h3 style="color:#335eea !important"  class="clear-both">Rewards Activity</h3>    
                    <?php 
                        foreach ($list->getFields as $field) {
                            if ($field->visible || !isset($is_page)){
                                  if ($field->label == 'Welcome Points' && $field->tag != 0){ ?>
                                        <h5><b>{{ $field->label }}</b> : {{ $values[$field->tag] }}</h5>
                                <?php }
                                if ($field->label == 'Last Points Earn' && $field->tag != 0){ ?>
                                        <h5><b>{{ $field->label }}</b> : {{ $values[$field->tag] }}</h5>
                                <?php }
                                if ($field->label == 'Total Points' && $field->tag != 0){ ?>
                                        <h5><b>{{ $field->label }}</b> : {{ $values[$field->tag] }}</h5>
                                <?php }
                                if ($field->label == 'Anniversary' && !empty($values[$field->tag])){ ?>
                                        <h5><b>{{ $field->label }}</b> : {{ $values[$field->tag] }}</h5>
                                <?php }
                                if ($field->label == 'Points Redeem' && $field->tag != 0){ ?>
                                        <h5><b>{{ $field->label }}</b> : {{ $values[$field->tag] }}</h5>
                                <?php }
                                 if ($field->label == 'Vip Status' && !empty($values[$field->tag])){ ?>
                                        <h5><b>{{ $field->label }}</b> : {{ $values[$field->tag] }}</h5>
                                <?php }
                            }
                        }                   
                    }
                    else
                    {?>
                        @foreach ($list->getFields as $field)
                            @if ($field->visible || !isset($is_page))
                        <!-- @include("subscribers._form") -->
                                <h5><b>{{ ucfirst(strtolower($field->label)) }}</b> : {{ $values[$field->tag] }}</h5>
                            @endif
                        @endforeach

                    <?php }
                    ?>
                   <!--  @include("subscribers._form") -->
                  <!--   <button class="btn bg-teal mr-10"><i class="icon-check"></i> {{ trans('messages.save') }}</button>
                    <a href="{{ action('SubscriberController@index', $list->uid) }}" class="btn bg-grey-800"><i class="icon-cross2"></i> {{ trans('messages.cancel') }}</a>
                    -->
                </form>
            </div>

            <div class="sub-section">
                <h3 style="color:#335eea !important" class="text-semibold">{{ trans('messages.verification.title.email_verification') }}</h3>

                @if (is_null($subscriber->emailVerification))
                    <p>{!! trans('messages.verification.wording.verify', [ 'email' => sprintf("<strong>%s</strong>", $subscriber->email) ]) !!}</p>
                    <form enctype="multipart/form-data" action="{{ action('SubscriberController@startVerification', ['uid' => $subscriber->uid]) }}" method="POST" class="form-validate-jquery">
                        {{ csrf_field() }}

                        <input type="hidden" name="list_uid" value="{{ $list->uid }}" />

                        @include('helpers.form_control', [
                            'type' => 'select',
                            'name' => 'email_verification_server_id',
                            'value' => '',
                            'options' => \Auth::user()->customer->emailVerificationServerSelectOptions(),
                            'help_class' => 'verification',
                            'rules' => ['email_verification_server_id' => 'required'],
                            'include_blank' => trans('messages.select_email_verification_server')
                        ])
                        <div class="text-left">
                            <button class="btn bg-teal mr-10"> {{ trans('messages.verification.button.verify') }}</button>
                        </div>
                    </form>
                @elseif ($subscriber->emailVerification->isDeliverable())
                    <p>{!! trans('messages.verification.wording.deliverable', [ 'email' => sprintf("<strong>%s</strong>", $subscriber->email), 'at' => sprintf("<strong>%s</strong>", $subscriber->emailVerification->created_at) ]) !!}</p>
                    <form enctype="multipart/form-data" action="{{ action('SubscriberController@resetVerification', ['uid' => $subscriber->uid]) }}" method="POST" class="form-validate-jquery">
                        {{ csrf_field() }}
                        <input type="hidden" name="list_uid" value="{{ $list->uid }}" />

                        <div class="text-left">
                            <button class="btn bg-teal mr-10">{{ trans('messages.verification.button.reset') }}</button>
                        </div>
                    </form>
                @elseif ($subscriber->emailVerification->isUndeliverable())
                    <p>{!! trans('messages.verification.wording.undeliverable', [ 'email' => sprintf("<strong>%s</strong>", $subscriber->email)]) !!}</p>
                    <form enctype="multipart/form-data" action="{{ action('SubscriberController@resetVerification', ['uid' => $subscriber->uid]) }}" method="POST" class="form-validate-jquery">
                        <input type="hidden" name="list_uid" value="{{ $list->uid }}" />

                        <div class="text-left">
                            <button class="btn bg-teal mr-10">{{ trans('messages.verification.button.reset') }}</button>
                        </div>
                    </form>
                @else
                    <p>{!! trans('messages.verification.wording.risky_or_unknown', [ 'email' => sprintf("<strong>%s</strong>", $subscriber->email), 'at' => sprintf("<strong>%s</strong>", $subscriber->emailVerification->created_at), 'result' => sprintf("<strong>%s</strong>", $subscriber->emailVerification->result)]) !!}</p>
                    <form enctype="multipart/form-data" action="{{ action('SubscriberController@resetVerification', ['uid' => $subscriber->uid]) }}" method="POST" class="form-validate-jquery">
                        <input type="hidden" name="list_uid" value="{{ $list->uid }}" />

                        <div class="text-left">
                            <button class="btn bg-teal mr-10">{{ trans('messages.verification.button.reset') }}</button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
         
        <div  class="borderClass col-sm-12 col-md-4 col-lg-4">
            <div class="sub-section">
                <h3 style="color:#335eea !important" class="clear-both">Activity Log</h3>
                 <?php foreach ($list->getFields as $field) { 
                                    if ($field->visible || !isset($is_page)){
                                        if($field->tag == 'FIRST_NAME')
                                        {
                                            $firstname=$values[$field->tag];
                                        }
                                    }} ?>
                  <div class="scrollbar-box action-log-box">
                    <!-- Timeline -->
                    <div style="height:auto" class="timeline timeline-left content-group">
                        <div class="timeline-container" style="padding-right:0 !important;">
                            <?php 
                            foreach ($tracking_logs as $key => $tlogs) {
                               $AllLogs=$tlogs->AllLog($tlogs->message_id);
                               foreach ($AllLogs as $key => $alogs) {
                                ?>
                                    <!-- Sales stats -->
                                    <div class="timeline-row" style="padding-left:0 !important;">
                                        <div class="panel panel-flat timeline-content">
                                            <div class="panel-heading">
                                                <h6 class="panel-title text-semibold">{{ ucfirst($firstname) }}</h6>
                                                <div class="heading-elements">
                                                    <span class="heading-text"><i class="icon-history position-left text-success"></i> {{ $alogs->created_at }}</span>
                                                </div>
                                            </div>
                                            <div class="panel-body">
                                                {{ @($alogs->name) }} an email <b style="color:#335eea">"{{ @($tlogs->Campaign->name) }}"</b>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- /sales stats -->
                            <?php }
                            } ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
       
        <?php if($list->name == 'All'){ ?>
        <div  class="borderClass col-sm-12 col-md-4 col-lg-4 lastDiv">
            <div class="sub-section">
                <form enctype="multipart/form-data"  action="{{ action('SubscriberController@update', ['list_uid' => $list->uid, "uid" => $subscriber->uid]) }}" method="POST" class="form-validate-jqueryz">
                    {{ csrf_field() }}
                    <input type="hidden" name="_method" value="PATCH">
                    <input type="hidden" name="list_uid" value="{{ $list->uid }}" />
                    &nbsp;
                     <h3 style="color:#335eea !important" class="clear-both">Cart Items</h3>
                    <?php 
                        foreach ($list->getFields as $field) {
                            if ($field->visible || !isset($is_page)){
                                if ($field->tag == 'CART1_IMAGE' && !empty($values[$field->tag])){ ?>
                                        <img class="imgClass" src="{{ $values[$field->tag] }}"><br>
                                    <?php } 
                                    if ($field->tag == "CART1_TITLE" && !empty($values[$field->tag])){ ?>
                                    <label><b>{{ $values[$field->tag] }}</b></label>
                                <?php } 
                                if ($field->tag == 'CART2_IMAGE' && !empty($values[$field->tag])){ ?>
                                    <img class="imgClass" src="{{ $values[$field->tag] }}"><br>
                                <?php }
                                if ($field->tag == "CART2_TITLE" && !empty($values[$field->tag])){ ?>
                                    <label><b>{{ $values[$field->tag] }}</b></label>
                                <?php } 
                                 if ($field->tag == 'CART3_IMAGE' && !empty($values[$field->tag])){ ?>
                                    <img class="imgClass" src="{{ $values[$field->tag] }}"><br>
                                <?php }
                                if ($field->tag == "CART3_TITLE" && !empty($values[$field->tag])){ ?>
                                    <label><b>{{ $values[$field->tag] }}</b></label>
                                <?php } 
                                 if ($field->tag == 'CART4_IMAGE' && !empty($values[$field->tag])){ ?>
                                    <img class="imgClass" src="{{ $values[$field->tag] }}"><br>
                                <?php }
                                if ($field->tag == "CART4_TITLE" && !empty($values[$field->tag])){ ?>
                                    <label><b>{{ $values[$field->tag] }}</b></label>
                                <?php } 
                                 if ($field->tag == 'CART5_IMAGE' && !empty($values[$field->tag])){ ?>
                                    <img class="imgClass" src="{{ $values[$field->tag] }}"><br>
                                <?php }
                                if ($field->tag == "CART5_TITLE" && !empty($values[$field->tag])){ ?>
                                    <label><b>{{ $values[$field->tag] }}</b></label>
                                <?php } 
                                }
                        }
                    ?>
                    <hr>
                    <h3 style="color:#335eea !important" class="clear-both">Order Items</h3>
                    <?php 
                        foreach ($list->getFields as $field) {
                            if ($field->visible || !isset($is_page)){
                                if ($field->tag == 'ORDER1_IMAGE' && !empty($values[$field->tag])){ ?>
                                        <img class="imgClass" src="{{ $values[$field->tag] }}"><br>
                                    <?php } 
                                    if ($field->tag == "ORDER1_TITLE" && !empty($values[$field->tag])){ ?>
                                    <label><b>{{ $values[$field->tag] }}</b></label>
                                <?php } 
                                if ($field->tag == 'ORDER2_IMAGE' && !empty($values[$field->tag])){ ?>
                                    <img class="imgClass" src="{{ $values[$field->tag] }}"><br>
                                <?php }
                                if ($field->tag == "ORDER2_TITLE" && !empty($values[$field->tag])){ ?>
                                    <label><b>{{ $values[$field->tag] }}</b></label>
                                <?php } 
                                 if ($field->tag == 'ORDER3_IMAGE' && !empty($values[$field->tag])){ ?>
                                    <img class="imgClass" src="{{ $values[$field->tag] }}"><br>
                                <?php }
                                if ($field->tag == "ORDER3_TITLE" && !empty($values[$field->tag])){ ?>
                                    <label><b>{{ $values[$field->tag] }}</b></label>
                                <?php } 
                                 if ($field->tag == 'ORDER4_IMAGE' && !empty($values[$field->tag])){ ?>
                                    <img class="imgClass" src="{{ $values[$field->tag] }}"><br>
                                <?php }
                                if ($field->tag == "ORDER4_TITLE" && !empty($values[$field->tag])){ ?>
                                    <label><b>{{ $values[$field->tag] }}</b></label>
                                <?php } 
                                 if ($field->tag == 'ORDER5_IMAGE' && !empty($values[$field->tag])){ ?>
                                    <img class="imgClass" src="{{ $values[$field->tag] }}"><br>
                                <?php }
                                if ($field->tag == "ORDER5_TITLE"){ ?>
                                    <label><b>{{ $values[$field->tag] }}</b></label>
                                <?php } 
                                }
                        }
                    ?>
                   
                </form>
            </div>
        </div>
        <?php } ?>
    </div>
<script>
var subscriber_id="<?php echo $subscriber->id; ?>";
    $.ajax({
        method:'GET',
        data:{subscriber_id:subscriber_id},
        url:"{{ url('/getEmailData') }}",
        success:function(resp)
        {
            var response=resp.split('|');
            $('[name=FIRST_OPENED_EMAIL]').val(response[2]);
            $('[name=LAST_OPENED_EMAIL]').val(response[1]);
            $('[name=TOTAL_EMAILS_OPENED]').val(response[0]);

        }
    });
</script>
@endsection

