<div class="row condition-line" rel="__index__">
	<div class="col-md-4">
		<div class="form-group">
			<select class="select condition-field-select" name="conditions[__index__][field_id]">
				<optgroup label="Contact Info">
					@foreach($list->getFields as $field)
					<?php if($field->tag == 'EMAIL' || $field->tag == 'STATUS'|| $field->tag == 'ORDERS_COUNT'|| $field->tag == 'TOTAL_SPENT'|| $field->tag == 'COUNTRY' ){ ?>
						<option value="{{ $field->uid }}">{{ $field->label }}</option>
					<?php }?>
					@endforeach
				</optgroup>
				<optgroup label="{{ trans('messages.email_verification') }}">
					<option value="verification">{{ trans('messages.verification_result') }}</option>
				</optgroup>
			</select>
		</div>
	</div>
	<div class="col-md-7 operator_value_col" data-url="{{ action('SegmentController@conditionValueControl') }}">

	</div>
	<div class="col-md-1">
		<a onclick="$(this).parents('.condition-line').remove()" href="#delete" class="btn bg-danger-400"><i class="icon-trash"></i></a>
	</div>
</div>
