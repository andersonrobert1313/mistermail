@extends('layouts.frontend')

@section('title', trans('messages.campaigns') . " - " . trans('messages.template'))
	
@section('page_script')
	<script type="text/javascript" src="{{ URL::asset('assets/js/plugins/forms/styling/uniform.min.js') }}"></script>        
    <script type="text/javascript" src="{{ URL::asset('tinymce/tinymce.min.js') }}"></script>
	<script type="text/javascript" src="{{ URL::asset('assets/js/core/libraries/jquery_ui/interactions.min.js') }}"></script>
	<script type="text/javascript" src="{{ URL::asset('assets/js/core/libraries/jquery_ui/touch.min.js') }}"></script>
        
    <script type="text/javascript" src="{{ URL::asset('js/editor.js') }}"></script>
    <script type="text/javascript" src="{{ URL::asset('js/validate.js') }}"></script>
	<script type="text/javascript" src="{{ URL::asset('js/listing.js') }}"></script>	
@endsection

@section('page_header')
	
    <div class="page-title">
        <ul class="breadcrumb breadcrumb-caret position-right">
            <li><a href="{{ action("HomeController@index") }}">{{ trans('messages.home') }}</a></li>
            <li><a href="{{ action("CampaignController@index") }}">{{ trans('messages.campaigns') }}</a></li>
        </ul>
        <h1>
            <span class="text-semibold"><i class="icon-paperplane"></i> {{ $campaign->name }}</span>
        </h1>

        @include('campaigns._steps', ['current' => 3])
    </div>

@endsection

@section('content')
    <form action="{{ action('CampaignController@builderClassic', $campaign->uid) }}" method="POST" class="form-validate-jqueryz">
        {{ csrf_field() }}

        <h2 class="mt-0">{{ trans('messages.email_content') }}</h2>
        
        <div class="tab-content">
            
            <div class="tab-pane active" id="top-justified-divided-tab1">
                @include('helpers.form_control', ['type' => 'textarea',
                    'class' => 'clean-editor',
                    'name' => 'html',
                    'label' => '',
                    'value' => $campaign->render(),
                    'rules' => [],
                    'help_class' => 'campaign'
                ])
            </div>
        </div>
            
        @include('elements._tags', ['tags' => Acelle\Model\Template::tags($campaign->defaultMailList)])
        
        <hr>
        <div class="text-right">
            <button class="btn bg-teal-800">{{ trans('messages.save_and_next') }} <i class="icon-arrow-right7"></i> </button>
        </div>
        
    <form>			
@endsection
