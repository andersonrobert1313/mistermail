<h5 class="mb-3">
    {{ trans('messages.automation.action.set_up_your_condition') }}
</h5>
<p class="mb-3">
    {{ trans('messages.automation.action.condition.intro') }}
</p>
<div class="mb-20">
    @include('helpers.form_control', [
        'type' => 'select',
        'class' => '',
        'label' => 'Select criterion',
        'name' => 'type',
        'value' => $element->getOption('type'),
        'help_class' => 'trigger',
        'options' => [
            ['text' => 'Contact did not make a Purchase?', 'value' => 'makeapurchase'],
            ['text' => 'Cart value less than?', 'value' => 'cartlessthan'],
            ['text' => 'Total spent less than?', 'value' => 'orderlessthan'],
            ['text' => 'Contact`s country from?', 'value' => 'contactcountryfrom'],
            ['text' => 'Received email less than?', 'value' => 'recievedemailslessthan'],
        ],
        'rules' => [],
    ])
</div>
    
<div class="mb-20" data-condition="cartlessthan" style="display:none">
    <label>Enter cart value</label>
    <input type="text" placeholder="$" class="form-control" value="{{ @($automation->cartlessthan) }}" name="cart_value">
</div>

<div class="mb-20" data-condition="orderlessthan" style="display:none">
    <label>Enter order value</label>
    <input type="text" placeholder="$" class="form-control" value="{{ @($automation->orderlessthan) }}" name="order_value">
</div>


<div class="mb-20" data-condition="contactcountryfrom" style="display:none">
    <label>Select Country</label>
    <select class="form-control" name="country" id="countrySelect">

    </select>
</div>


<div class="mb-20" data-condition="open" style="display:none">
    @include('helpers.form_control', [
        'type' => 'select',
        'class' => 'required',
        'label' => 'Which email subscriber reads',
        'name' => 'email',
        'value' => $element->getOption('email'),
        'help_class' => 'trigger',
        'include_blank' => trans('messages.automation.condition.choose_email'),
        'required' => true,
        'options' => $automation->getEmailOptions(),
        'rules' => [],
    ])
</div>


    
<div class="mb-20" data-condition="click" style="display:none">
    @include('helpers.form_control', [
        'type' => 'select',
        'class' => 'required',
        'label' => 'Which Link subscriber clicks',
        'name' => 'email_link',
        'value' => $element->getOption('email_link'),
        'help_class' => 'trigger',
        'options' => $automation->getEmailLinkOptions(),
        'include_blank' => trans('messages.automation.condition.choose_link'),
        'required' => true,
        'rules' => [],
    ])
</div>
    
<script>
    function toggleCriterion() {
        var value = $('[name=type]').val();
        
        $('[data-condition]').hide();
        $('[data-condition='+value+']').show();
    }

    // Toggle condition options
    $(document).on('change', '[name=type]', function() {
        toggleCriterion();
    });
    
    toggleCriterion();

$(document).ready(function(){
    var automation_id="<?php echo $automation->uid ?>";
    var country="<?php echo $automation->country ?>";
    $.ajax({
        method:'GET',
        data:{automation_id:automation_id},
        url:"{{ url('/getUsersLocations') }}",
        success:function(resp)
        {
            //alert(resp);
            $('#countrySelect').html(resp);
            $('#countrySelect').val(country);
        }
    });
});
</script>