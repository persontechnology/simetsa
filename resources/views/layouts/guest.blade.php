
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="ltr">
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

	<!-- Global stylesheets -->
	<link href="{{ asset('assets/fonts/inter/inter.css') }}" rel="stylesheet" type="text/css">
	<link href="{{ asset('assets/icons/phosphor/styles.min.css') }}" rel="stylesheet" type="text/css">
	<link href="{{ asset('assets/css/ltr/all.min.css') }}" id="stylesheet" rel="stylesheet" type="text/css">
	<!-- /global stylesheets -->

	<!-- Core JS files -->
	<script src="{{ asset('assets/demo/demo_configurator.js') }}"></script>
	<script src="{{ asset('assets/js/bootstrap/bootstrap.bundle.min.js') }}"></script>
	<!-- /core JS files -->

	<!-- Theme JS files -->
	<script src="{{ asset('assets/js/app.js') }}"></script>
	<!-- /theme JS files -->

</head>

<body>

	<!-- Main navbar -->
	<div class="navbar navbar-dark navbar-static py-2">
		<div class="container-fluid">
			<div class="navbar-brand">
				<a href="{{ url('/') }}" class="d-inline-flex align-items-center">
					<img src="{{ asset('assets/images/logo_icon.svg') }}" alt="">
					<img src="{{ asset('assets/images/logo_text_light.svg') }}" class="d-none d-sm-inline-block h-16px ms-3" alt="">
				</a>
			</div>

			<div class="d-flex justify-content-end align-items-center ms-auto">
				<ul class="navbar-nav flex-row">
					
					@guest
						@if (Route::has('register'))
							<li class="nav-item">
								<a href="{{ route('register') }}" class="navbar-nav-link navbar-nav-link-icon rounded ms-1">
									<div class="d-flex align-items-center mx-md-1">
									<i class="ph-user-circle-plus"></i>
									<span class="d-none d-md-inline-block ms-2">Registrar</span>
									</div>
								</a>
							</li>
						@endif
						<li class="nav-item">
							<a href="{{ route('login') }}" class="navbar-nav-link navbar-nav-link-icon rounded ms-1">
								<div class="d-flex align-items-center mx-md-1">
									<i class="ph-user-circle"></i>
									<span class="d-none d-md-inline-block ms-2">Ingresar</span>
								</div>
							</a>
						</li>
					@endguest

					@auth
						
						<li class="nav-item">
							<a href="{{ route('dashboard') }}" class="navbar-nav-link navbar-nav-link-icon rounded ms-1">
								<div class="d-flex align-items-center mx-md-1">
									<i class="ph-house"></i>
									<span class="d-none d-md-inline-block ms-2">Panel</span>
								</div>
							</a>
						</li>
						<li class="nav-item">
							<a href="{{ route('profile.edit') }}" class="navbar-nav-link navbar-nav-link-icon rounded ms-1">
								<div class="d-flex align-items-center mx-md-1">
									<i class="ph-user-circle"></i>
									<span class="d-none d-md-inline-block ms-2">Perfil</span>
								</div>
							</a>
						</li>
						<li class="nav-item">
							<form method="POST" action="{{ route('logout') }}" class="d-inline">
								@csrf
								<button type="submit" class="navbar-nav-link navbar-nav-link-icon rounded ms-1 btn btn-link text-decoration-none">
									<div class="d-flex align-items-center mx-md-1">
									<i class="ph-sign-out"></i>
									<span class="d-none d-md-inline-block ms-2">Cerrar sesión</span>
									</div>
								</button>
							</form>
						</li>
					@endauth
				</ul>
			</div>
		</div>
	</div>
	<!-- /main navbar -->


	<!-- Page content -->
	<div class="page-content">

		<!-- Main content -->
		<div class="content-wrapper">

			<!-- Inner content -->
			<div class="content-inner">

				<!-- Content area -->
				<div class="content d-flex justify-content-center align-items-center">

					@yield('content')

				</div>
				<!-- /content area -->

                <!-- Footer -->
				@include('partials.footer')
                <!-- /footer -->

			</div>
			<!-- /inner content -->

		</div>
		<!-- /main content -->

	</div>
	<!-- /page content -->


	<!-- Demo config -->
	@include('partials.demo_config')
	<!-- /demo config -->

</body>
</html>
