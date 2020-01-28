<div class="mb-20">
    <input type="hidden" name="options[type]" value="api" />
    
    @include('helpers.form_control', [
        'type' => 'text',
        'class' => '',
        'readonly' => true,
        'label' => '',
        'name' => 'options[endpoint]',
        'value' => 'POST ' . action('Automation2Controller@executeApi', [
            'uid' => $automation->uid,
        ]),
        'help_class' => 'trigger',
        'rules' => [],
    ])
</div>