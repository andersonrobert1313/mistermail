<!doctype html>
<html>
  <head>
    <title>{{ trans('messages.edit_template') }} - {{ $template->name }}</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    
    @include('layouts._favicon')
    
    <link href="{{ URL::asset('builder/builder.css') }}" rel="stylesheet" type="text/css">
    <script type="text/javascript" src="{{ URL::asset('builder/builder.js') }}"></script>
    
    <script>
        var CSRF_TOKEN = "{{ csrf_token() }}";
        var editor;
        
        var templates = JSON.parse('{!! json_encode($templates) !!}');
        
        $( document ).ready(function() {
            editor = new Editor({
                url: '{{ action('Admin\TemplateController@builderEditContent', $template->uid) }}',
                urlBack: '{{ action('Admin\TemplateController@index') }}',
                uploadAssetUrl: '{{ action('Admin\TemplateController@builderAsset', $template->uid) }}',
                uploadAssetMethod: 'POST',
                saveUrl: '{{ action('Admin\TemplateController@builderEdit', $template->uid) }}',
                saveMethod: 'POST',
                tags: {!! json_encode(Acelle\Model\Template::builderTags((isset($list) ? $list : null))) !!},
                root: '{{ URL::asset('builder') }}/',
                templates: templates,
            });
          
            editor.init();
        });
    </script>
  </head>
  <body>
  </body>
</html>