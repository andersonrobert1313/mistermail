<style>
  

</style>
<h5 class="mb-3">
    {{ trans('messages.automation.action.wait') }}
</h5>
<p class="mb-3">
    {{ trans('messages.automation.action.wait.intro') }}
</p>

<div class="row">
     <div class="col-md-6">    
        <div class="form-group">
            <select class="form-control selectBox" name="timeValue">
                <option value="">Select</option>
                <option ref="minuteSelect" value="minute">Minutes</option>
                <option ref="hourSelect" value="hour">Hours</option>
                <option ref="daySelect" value="day">Days</option>
                <option ref="weekSelect" value="week">Weeks</option>
                <option ref="monthSelect" value="month">Months</option>
            </select>
        </div>
    </div>

    <div class="col-md-6">    
        <div class="form-group">
            <select  name="time" style="display:block" class="form-control selectOptions">
                
            </select>
        </div>
    </div>
   <!--  <div class="col-md-6">    
        @include('helpers.form_control', [
            'type' => 'select',
            'class' => '',
            'label' => '',
            'name' => 'time',
            'value' => $element->getOption('time'),
            'help_class' => 'trigger',
            'options' => $automation->getDelayOptions(),
            'rules' => [],
        ])
    </div> -->
   
</div>
<script>
    $(document).on('change','.selectBox',function(){
        var ref = $(this).children("option:selected").attr('ref');
        var options='';
        if(ref == 'minuteSelect')
        {
            var options='<option value="1 minute">1 minute</option> <option value="5 minutes">5 minutes</option> <option value="10 minutes">10 minutes</option> <option value="15 minutes">15 minutes</option> <option value="20 minutes">20 minutes</option> <option value="25 minutes">25 minutes</option> <option value="30 minutes">30 minutes</option> <option value="35 minutes">35 minutes</option> <option value="40 minutes">40 minutes</option> <option value="45 minutes">45 minutes</option> <option value="50 minutes">50 minutes</option> <option value="55 minutes">55 minutes</option> <option value="60 minutes">60 minutes</option>';
        }
        else if(ref == 'hourSelect')
        {
            var options='<option value="1 hour">1 hour</option> <option value="2 hours">2 hours</option> <option value="3 hours">3 hours</option> <option value="4 hours">4 hours</option> <option value="5 hours">5 hours</option> <option value="6 hours">6 hours</option> <option value="7 hours">7 hours</option> <option value="8 hours">8 hours</option> <option value="9 hours">9 hours</option> <option value="10 hours">10 hours</option> <option value="11 hours">11 hours</option> <option value="12 hours">12 hours</option> <option value="13 hours">13 hours</option> <option value="14 hours">14 hours</option> <option value="15 hours">15 hours</option> <option value="16 hours">16 hours</option> <option value="17 hours">17 hours</option> <option value="18 hours">18 hours</option> <option value="19 hours">19 hours</option> <option value="20 hours">20 hours</option> <option value="21 hours">21 hours</option> <option value="22 hours">22 hours</option> <option value="23 hours">23 hours</option> <option value="24 hours">24 hours</option>'; 
        }
        else if(ref == 'daySelect')
        {
            var options='<option value="1 day">1 day</option> <option value="2 days">2 days</option> <option value="3 days">3 days</option> <option value="4 days">4 days</option> <option value="5 days">5 days</option> <option value="6 days">6 days</option> <option value="7 days">7 days</option> <option value="8 days">8 days</option> <option value="9 days">9 days</option> <option value="10 days">10 days</option> <option value="11 days">11 days</option> <option value="12 days">12 days</option> <option value="13 days">13 days</option> <option value="14 days">14 days</option> <option value="15 days">15 days</option> <option value="16 days">16 days</option> <option value="17 days">17 days</option> <option value="18 days">18 days</option> <option value="19 days">19 days</option> <option value="20 days">20 days</option> <option value="21 days">21 days</option> <option value="22 days">22 days</option> <option value="23 days">23 days</option> <option value="24 days">24 days</option> <option value="25 days">25 days</option> <option value="26 days">26 days</option> <option value="27 days">27 days</option> <option value="28 days">28 days</option> <option value="29 days">29 days</option> <option value="30 days">30 days</option>'; 
        }
        else if(ref == 'weekSelect')
        {
            var options='<option value="1 week">1 week</option> <option value="2 weeks">2 weeks</option> <option value="3 weeks">3 weeks</option> <option value="4 weeks">4 weeks</option> <option value="5 weeks">5 weeks</option> <option value="6 weeks">6 weeks</option> <option value="7 weeks">7 weeks</option> <option value="8 weeks">8 weeks</option> <option value="9 weeks">9 weeks</option> <option value="10 weeks">10 weeks</option> <option value="11 weeks">11 weeks</option> <option value="12 weeks">12 weeks</option>'; 
        }
        else 
        {
            var options=' <option value="1 month">1 month</option> <option value="2 months">2 months</option> <option value="3 months">3 months</option> <option value="4 months">4 months</option> <option value="5 months">5 months</option> <option value="6 months">6 months</option> <option value="7 months">7 months</option> <option value="8 months">8 months</option> <option value="9 months">9 months</option> <option value="10 months">10 months</option> <option value="11 months">11 months</option> <option value="12 months">12 months</option>'; 
        }
        $('.selectOptions').html(options);
    });
</script>
