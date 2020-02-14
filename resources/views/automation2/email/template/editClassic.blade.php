@extends('layouts.empty')

@section('title', trans('messages.edit_template'))

@section('page_script')
	<script type="text/javascript" src="{{ URL::asset('tinymce/tinymce.min.js') }}"></script>
        
    <script type="text/javascript" src="{{ URL::asset('js/editor.js') }}"></script>
@endsection

@section('content')    
    <div class="row" style="width: 100%; padding: 40px">
        <div class="col-md-1"></div>
        <div class="col-md-10">

            <h2>{{ trans('messages.automation.classic_builder') }}</h2>
            <form action="{{ action('Automation2Controller@templateEditClassic', [
                'uid' => $automation->uid,
                'email_uid' => $email->uid,
            ]) }}" method="POST" class="ajax_upload_form builder-classic-form form-validate-jquery">
                {{ csrf_field() }}
                
                @include('helpers.form_control', [
                    'class' => 'clean-editor',
                    'required' => true,
                    'type' => 'textarea',
                    'name' => 'content',
                    'value' => $email->render(),
                    'rules' => ['content' => 'required']
                ])
                
                @include('elements._tags', ['tags' => Acelle\Model\Template::tags($automation->mailList)])
                
                <hr>
                <div class="text-right">
                    <button class="btn bg-teal mr-10"><i class="icon-check"></i> {{ trans('messages.save') }}</button>
                    <a href="javascript:;" class="btn bg-grey-800 close-button"><i class="icon-cross2"></i> {{ trans('messages.cancel') }}</a>
                </div>
                
            </form>  
            
        </div>
    </div>

    <script>
    
        $('.close-button').click(function() {
            parent.$('.full-iframe-popup').remove();
            popup.load();
        });

        $('.builder-classic-form').submit(function(e) {
            e.preventDefault();

            tinymce.triggerSave();

            var url = $(this).attr('action');
            var data = $(this).serialize();

            // open builder effects
            addMaskLoading("{{ trans('messages.automation.template.saving') }}", function() {
                $.ajax({
                    url: url,
                    type: 'POST',
                    data: data,
                }).always(function(response) {
                    removeMaskLoading();

                    parent.$('.full-iframe-popup').remove();
                    popup.load();
                });
            });            
        });
    
    </script>
@endsection
