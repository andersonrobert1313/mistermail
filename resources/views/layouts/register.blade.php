<!DOCTYPE html>
<html lang="en">
<head>
	<title>@yield('title') - {{ \Acelle\Model\Setting::get("site_name") }}</title>

	@include('layouts._favicon')

	@include('layouts._head')

	@include('layouts._css')

	@include('layouts._js')

</head>

<body>

	<!-- Page header -->
	<div class="page-header">
		<div class="page-header-content">

			@yield('page_header')

		</div>
	</div>
	<!-- /page header -->

	<!-- Page container -->
	<div class="page-container" style="min-height: 100vh">

		<!-- Page content -->
		<div class="page-content">

			<!-- Main content -->
			<div class="content-wrapper">

				<!-- main inner content -->
				@yield('content')

			</div>
			<!-- /main content -->

		</div>
		<!-- /page content -->


		<!-- Footer -->
		<div class="footer text-muted">
							Mister Mail - We put the AI in mail. © 2020 | All Rights Reserved | <a target='_BLANK' href="https://themistermail.com/terms-of-service.html">Privacy Policy</a> | <a target='_BLANK' href="https://themistermail.com/terms-of-service.html">Terms Of Use</a>

		</div>
		
		<!-- /footer -->

	</div>
	<!-- /page container -->

    {!! \Acelle\Model\Setting::get('custom_script') !!}

</body>
</html>
