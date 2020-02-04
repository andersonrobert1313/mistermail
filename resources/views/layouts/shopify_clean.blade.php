<!DOCTYPE html>
<html lang="en">
<head>
	<title>Shopify Mister Mail</title>

	@include('layouts._favicon')

	@include('layouts._head')

	<!-- Global stylesheets -->
	<link href="https://fonts.googleapis.com/css?family=Roboto:400,300,100,500,700,900" rel="stylesheet" type="text/css">
	<link href="{{ URL::asset('assets/css/icons/icomoon/styles.css') }}" rel="stylesheet" type="text/css">
	<link href="{{ URL::asset('assets/css/bootstrap.css') }}" rel="stylesheet" type="text/css">
	<link href="{{ URL::asset('assets/css/core.css') }}" rel="stylesheet" type="text/css">
	<link href="{{ URL::asset('assets/css/components.css') }}" rel="stylesheet" type="text/css">
	<link href="{{ URL::asset('assets/css/colors.css') }}" rel="stylesheet" type="text/css">
	<link href="{{ URL::asset('css/app.css') }}" rel="stylesheet" type="text/css">
	<!-- /global stylesheets -->

	<!-- Core JS files -->
	<script type="text/javascript" src="{{ URL::asset('assets/js/plugins/loaders/pace.min.js') }}"></script>
	<script type="text/javascript" src="{{ URL::asset('assets/js/core/libraries/jquery.min.js') }}"></script>
	<script type="text/javascript" src="{{ URL::asset('assets/js/core/libraries/bootstrap.min.js') }}"></script>
	<script type="text/javascript" src="{{ URL::asset('assets/js/plugins/loaders/blockui.min.js') }}"></script>
	<script type="text/javascript" src="{{ URL::asset('assets/js/plugins/ui/nicescroll.min.js') }}"></script>
	<script type="text/javascript" src="{{ URL::asset('assets/js/plugins/ui/drilldown.js') }}"></script>
	<script type="text/javascript" src="{{ URL::asset('assets/js/plugins/notifications/sweet_alert.min.js') }}"></script>
	<!-- /core JS files -->

	<!-- Theme JS files -->
	<script type="text/javascript" src="{{ URL::asset('assets/js/plugins/forms/styling/uniform.min.js') }}"></script>

	<script type="text/javascript" src="{{ URL::asset('assets/js/core/app.js') }}"></script>
	<!-- /theme JS files -->

	<script>
		$(function() {

			// Style checkboxes and radios
			$('.styled').uniform();

		});
	</script>

	<!-- display flash message -->
	@include('common.flash')
<style>
.has-feedback-left .form-control-feedback
{
	left:8px !important;
	border-right: 1px solid #ddd;
}
.has-feedback-left .form-control
{
	padding-left: 66px!important;
}
.form-control-feedback
{
	width: 50px!important;
}
</style>
</head>

<body class="bg-slate-800">

	<!-- Page container -->
	<div class="page-container login-container">

		<!-- Page content -->
		<div class="page-content">

			<!-- Main content -->
			<div class="content-wrapper">
				<div class="row">
					<div class="col-sm-2 col-md-3">

					</div>
					<div class="col-sm-8 col-md-5">

					

						@yield('content')

					</div>
				</div>
			</div>
			<!-- /main content -->

		</div>
		<!-- /page content -->


		<!-- Footer -->
		
		<!-- /footer -->

	</div>
	<!-- /page container -->

</body>
</html>
