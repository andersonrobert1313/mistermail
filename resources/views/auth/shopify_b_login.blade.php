@extends('layouts.shopify_clean')

@section('title', trans('messages.login'))

@section('content')
<!-- Advanced login -->
<form class="" role="form" method="POST" action="{{ url('/shopify-app-install') }}">
    {{ csrf_field() }}

    <div class="panel panel-body">
        <h4 class="text-semibold mt-0">Please Enter your Shopify Store Name to install the app</h4>
        <div class="form-group has-feedback has-feedback-left">
            {{csrf_field()}}
            <input  type="text" class="form-control" name="shop" placeholder="Enter Store Name" value="">
            <div class="form-control-feedback">
                https://
            </div>
        </div>

        <button type="submit" class="btn btn-lg bg-teal btn-block">Submit <i class="icon-circle-right2 position-right"></i></button>
    </div>
</form>
<!-- /advanced login -->

@endsection
