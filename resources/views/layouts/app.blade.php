
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
	<link rel="stylesheet" href="{{ asset('assets/icons/bootstrap-icons/font/bootstrap-icons.min.css') }}">
	<!-- /global stylesheets -->

	<!-- Core JS files -->
	<script src="{{ asset('assets/demo/demo_configurator.js') }}"></script>
	<script src="{{ asset('assets/js/bootstrap/bootstrap.bundle.min.js') }}"></script>
	<!-- /core JS files -->

	<!-- Theme JS files -->
	<script src="{{ asset('assets/js/jquery/jquery.min.js') }}"></script>
	<script src="{{ asset('assets/js/vendor/forms/validation/validate.min.js') }}"></script>
    <script src="{{ asset('assets/js/vendor/forms/validation/messages_es.js') }}"></script>
    <script src="{{ asset('assets/js/vendor/forms/selects/select2.min.js') }}"></script>
	<link rel="stylesheet" href="{{ asset('assets/js/vendor/jquery-confirm/dist/jquery-confirm.min.css') }}">
	<script src="{{ asset('assets/js/vendor/jquery-confirm/dist/jquery-confirm.min.js') }}"></script>
	@stack('scriptsHeader')

	<script src="{{ asset('assets/js/app.js') }}"></script>
	<!-- /theme JS files -->

</head>

<body>

	<!-- Main navbar -->
	<div class="navbar navbar-dark navbar-expand-lg navbar-static border-bottom border-bottom-white border-opacity-10">
		<div class="container-fluid">
			<div class="d-flex d-lg-none me-2">
				<button type="button" class="navbar-toggler sidebar-mobile-main-toggle rounded-pill">
					<i class="ph-list"></i>
				</button>
			</div>

			<div class="navbar-brand flex-1 flex-lg-0">
				<a href="{{ route('dashboard') }}" class="d-inline-flex align-items-center">
					<img src="{{ asset('assets/images/logo_icon.svg') }}" alt="">
					<img src="{{ asset('assets/images/logo_text_light.svg') }}" class="d-none d-sm-inline-block h-16px ms-3" alt="">
				</a>
			</div>

			<ul class="nav flex-row">
				<li class="nav-item d-lg-none">
					<a href="#navbar_search" class="navbar-nav-link navbar-nav-link-icon rounded-pill" data-bs-toggle="collapse">
						<i class="ph-magnifying-glass"></i>
					</a>
				</li>


				{{-- menu brouses --}}
				{{-- <li class="nav-item nav-item-dropdown-lg dropdown">
					<a href="#" class="navbar-nav-link navbar-nav-link-icon rounded-pill" data-bs-toggle="dropdown">
						<i class="ph-squares-four"></i>
					</a>

					<div class="dropdown-menu dropdown-menu-scrollable-sm wmin-lg-600 p-0">
						<div class="d-flex align-items-center border-bottom p-3">
							<h6 class="mb-0">Browse apps</h6>
							<a href="#" class="ms-auto">
								View all
								<i class="ph-arrow-circle-right ms-1"></i>
							</a>
						</div>

						<div class="row row-cols-1 row-cols-sm-2 g-0">
							<div class="col">
								<button type="button" class="dropdown-item text-wrap h-100 align-items-start border-end-sm border-bottom p-3">
									<div>
										<img src="{{ asset('assets/images/demo/logos/1.svg') }}" class="h-40px mb-2" alt="">
										<div class="fw-semibold my-1">Customer data platform</div>
										<div class="text-muted">Unify customer data from multiple sources</div>
									</div>
								</button>
							</div>

							<div class="col">
								<button type="button" class="dropdown-item text-wrap h-100 align-items-start border-bottom p-3">
									<div>
										<img src="{{ asset('assets/images/demo/logos/2.svg') }}" class="h-40px mb-2" alt="">
										<div class="fw-semibold my-1">Data catalog</div>
										<div class="text-muted">Discover, inventory, and organize data assets</div>
									</div>
								</button>
							</div>

							<div class="col">
								<button type="button" class="dropdown-item text-wrap h-100 align-items-start border-end-sm border-bottom border-bottom-sm-0 rounded-bottom-start p-3">
									<div>
										<img src="{{ asset('assets/images/demo/logos/3.svg') }}" class="h-40px mb-2" alt="">
										<div class="fw-semibold my-1">Data governance</div>
										<div class="text-muted">The collaboration hub and data marketplace</div>
									</div>
								</button>
							</div>

							<div class="col">
								<button type="button" class="dropdown-item text-wrap h-100 align-items-start rounded-bottom-end p-3">
									<div>
										<img src="{{ asset('assets/images/demo/logos/4.svg') }}" class="h-40px mb-2" alt="">
										<div class="fw-semibold my-1">Data privacy</div>
										<div class="text-muted">Automated provisioning of non-production datasets</div>
									</div>
								</button>
							</div>
						</div>
					</div>
				</li> --}}

				{{-- menu messages --}}
				{{-- <li class="nav-item nav-item-dropdown-lg dropdown ms-lg-2">
					<a href="#" class="navbar-nav-link navbar-nav-link-icon rounded-pill" data-bs-toggle="dropdown" data-bs-auto-close="outside">
						<i class="ph-chats"></i>
						<span class="badge bg-yellow text-black position-absolute top-0 end-0 translate-middle-top zindex-1 rounded-pill mt-1 me-1">8</span>
					</a>

					<div class="dropdown-menu wmin-lg-400 p-0">
						<div class="d-flex align-items-center p-3">
							<h6 class="mb-0">Messages</h6>
							<div class="ms-auto">
								<a href="#" class="text-body rounded-pill">
									<i class="ph-plus-circle"></i>
								</a>
								<a href="#search_messages" class="collapsed text-body rounded-pill ms-2" data-bs-toggle="collapse">
									<i class="ph-magnifying-glass"></i>
								</a>
							</div>
						</div>

						<div class="collapse" id="search_messages">
							<div class="px-3 mb-2">
								<div class="form-control-feedback form-control-feedback-start">
									<input type="text" class="form-control" placeholder="Search messages">
									<div class="form-control-feedback-icon">
										<i class="ph-magnifying-glass"></i>
									</div>
								</div>
							</div>
						</div>

						<div class="dropdown-menu-scrollable pb-2">
							<a href="#" class="dropdown-item align-items-start text-wrap py-2">
								<div class="status-indicator-container me-3">
									<img src="{{ asset('assets/images/demo/users/face10.jpg') }}" class="w-40px h-40px rounded-pill" alt="">
									<span class="status-indicator bg-warning"></span>
								</div>

								<div class="flex-1">
									<span class="fw-semibold">James Alexander</span>
									<span class="text-muted float-end fs-sm">04:58</span>
									<div class="text-muted">who knows, maybe that would be the best thing for me...</div>
								</div>
							</a>

							<a href="#" class="dropdown-item align-items-start text-wrap py-2">
								<div class="status-indicator-container me-3">
									<img src="{{ asset('assets/images/demo/users/face3.jpg') }}" class="w-40px h-40px rounded-pill" alt="">
									<span class="status-indicator bg-success"></span>
								</div>

								<div class="flex-1">
									<span class="fw-semibold">Margo Baker</span>
									<span class="text-muted float-end fs-sm">12:16</span>
									<div class="text-muted">That was something he was unable to do because...</div>
								</div>
							</a>

							<a href="#" class="dropdown-item align-items-start text-wrap py-2">
								<div class="status-indicator-container me-3">
									<img src="{{ asset('assets/images/demo/users/face24.jpg') }}" class="w-40px h-40px rounded-pill" alt="">
									<span class="status-indicator bg-success"></span>
								</div>
								<div class="flex-1">
									<span class="fw-semibold">Jeremy Victorino</span>
									<span class="text-muted float-end fs-sm">22:48</span>
									<div class="text-muted">But that would be extremely strained and suspicious...</div>
								</div>
							</a>

							<a href="#" class="dropdown-item align-items-start text-wrap py-2">
								<div class="status-indicator-container me-3">
									<img src="{{ asset('assets/images/demo/users/face4.jpg') }}" class="w-40px h-40px rounded-pill" alt="">
									<span class="status-indicator bg-grey"></span>
								</div>
								<div class="flex-1">
									<span class="fw-semibold">Beatrix Diaz</span>
									<span class="text-muted float-end fs-sm">Tue</span>
									<div class="text-muted">What a strenuous career it is that I've chosen...</div>
								</div>
							</a>

							<a href="#" class="dropdown-item align-items-start text-wrap py-2">
								<div class="status-indicator-container me-3">
									<img src="{{ asset('assets/images/demo/users/face25.jpg') }}" class="w-40px h-40px rounded-pill" alt="">
									<span class="status-indicator bg-danger"></span>
								</div>
								<div class="flex-1">
									<span class="fw-semibold">Richard Vango</span>
									<span class="text-muted float-end fs-sm">Mon</span>
									<div class="text-muted">Other travelling salesmen live a life of luxury...</div>
								</div>
							</a>
						</div>

						<div class="d-flex border-top py-2 px-3">
							<a href="#" class="text-body">
								<i class="ph-checks me-1"></i>
								Dismiss all
							</a>
							<a href="#" class="text-body ms-auto">
								View all
								<i class="ph-arrow-circle-right ms-1"></i>
							</a>
						</div>
					</div>
				</li> --}}


			</ul>

			{{-- menu buscador central --}}

			{{-- <div class="navbar-collapse justify-content-center flex-lg-1 order-2 order-lg-1 collapse" id="navbar_search">
				<div class="navbar-search flex-fill position-relative mt-2 mt-lg-0 mx-lg-3">
					<div class="form-control-feedback form-control-feedback-start flex-grow-1" data-color-theme="dark">
						<input type="text" class="form-control bg-transparent rounded-pill" placeholder="Search" data-bs-toggle="dropdown">
						<div class="form-control-feedback-icon">
							<i class="ph-magnifying-glass"></i>
						</div>
						<div class="dropdown-menu w-100" data-color-theme="light">
							<button type="button" class="dropdown-item">
								<div class="text-center w-32px me-3">
									<i class="ph-magnifying-glass"></i>
								</div>
								<span>Search <span class="fw-bold">"in"</span> everywhere</span>
							</button>

							<div class="dropdown-divider"></div>

							<div class="dropdown-menu-scrollable-lg">
								<div class="dropdown-header">
									Contacts
									<a href="#" class="float-end">
										See all
										<i class="ph-arrow-circle-right ms-1"></i>
									</a>
								</div>

								<div class="dropdown-item cursor-pointer">
									<div class="me-3">
										<img src="{{ asset('assets/images/demo/users/face3.jpg') }}" class="w-32px h-32px rounded-pill" alt="">
									</div>

									<div class="d-flex flex-column flex-grow-1">
										<div class="fw-semibold">Christ<mark>in</mark>e Johnson</div>
										<span class="fs-sm text-muted">c.johnson@awesomecorp.com</span>
									</div>

									<div class="d-inline-flex">
										<a href="#" class="text-body ms-2">
											<i class="ph-user-circle"></i>
										</a>
									</div>
								</div>

								<div class="dropdown-item cursor-pointer">
									<div class="me-3">
										<img src="{{ asset('assets/images/demo/users/face24.jpg') }}" class="w-32px h-32px rounded-pill" alt="">
									</div>

									<div class="d-flex flex-column flex-grow-1">
										<div class="fw-semibold">Cl<mark>in</mark>ton Sparks</div>
										<span class="fs-sm text-muted">c.sparks@awesomecorp.com</span>
									</div>

									<div class="d-inline-flex">
										<a href="#" class="text-body ms-2">
											<i class="ph-user-circle"></i>
										</a>
									</div>
								</div>

								<div class="dropdown-divider"></div>

								<div class="dropdown-header">
									Clients
									<a href="#" class="float-end">
										See all
										<i class="ph-arrow-circle-right ms-1"></i>
									</a>
								</div>

								<div class="dropdown-item cursor-pointer">
									<div class="me-3">
										<img src="{{ asset('assets/images/brands/adobe.svg') }}" class="w-32px h-32px rounded-pill" alt="">
									</div>

									<div class="d-flex flex-column flex-grow-1">
										<div class="fw-semibold">Adobe <mark>In</mark>c.</div>
										<span class="fs-sm text-muted">Enterprise license</span>
									</div>

									<div class="d-inline-flex">
										<a href="#" class="text-body ms-2">
											<i class="ph-briefcase"></i>
										</a>
									</div>
								</div>

								<div class="dropdown-item cursor-pointer">
									<div class="me-3">
										<img src="{{ asset('assets/images/brands/holiday-inn.svg') }}" class="w-32px h-32px rounded-pill" alt="">
									</div>

									<div class="d-flex flex-column flex-grow-1">
										<div class="fw-semibold">Holiday-<mark>In</mark>n</div>
										<span class="fs-sm text-muted">On-premise license</span>
									</div>

									<div class="d-inline-flex">
										<a href="#" class="text-body ms-2">
											<i class="ph-briefcase"></i>
										</a>
									</div>
								</div>

								<div class="dropdown-item cursor-pointer">
									<div class="me-3">
										<img src="{{ asset('assets/images/brands/ing.svg') }}" class="w-32px h-32px rounded-pill" alt="">
									</div>

									<div class="d-flex flex-column flex-grow-1">
										<div class="fw-semibold"><mark>IN</mark>G Group</div>
										<span class="fs-sm text-muted">Perpetual license</span>
									</div>

									<div class="d-inline-flex">
										<a href="#" class="text-body ms-2">
											<i class="ph-briefcase"></i>
										</a>
									</div>
								</div>
							</div>
						</div>
					</div>

					<a href="#" class="navbar-nav-link align-items-center justify-content-center w-40px h-32px rounded-pill position-absolute end-0 top-50 translate-middle-y p-0 me-1" data-bs-toggle="dropdown" data-bs-auto-close="outside">
						<i class="ph-faders-horizontal"></i>
					</a>

					<div class="dropdown-menu w-100 p-3">
						<div class="d-flex align-items-center mb-3">
							<h6 class="mb-0">Search options</h6>
							<a href="#" class="text-body rounded-pill ms-auto">
								<i class="ph-clock-counter-clockwise"></i>
							</a>
						</div>

						<div class="mb-3">
							<label class="d-block form-label">Category</label>
							<label class="form-check form-check-inline">
								<input type="checkbox" class="form-check-input" checked>
								<span class="form-check-label">Invoices</span>
							</label>
							<label class="form-check form-check-inline">
								<input type="checkbox" class="form-check-input">
								<span class="form-check-label">Files</span>
							</label>
							<label class="form-check form-check-inline">
								<input type="checkbox" class="form-check-input">
								<span class="form-check-label">Users</span>
							</label>
						</div>

						<div class="mb-3">
							<label class="form-label">Addition</label>
							<div class="input-group">
								<select class="form-select w-auto flex-grow-0">
									<option value="1" selected>has</option>
									<option value="2">has not</option>
								</select>
								<input type="text" class="form-control" placeholder="Enter the word(s)">
							</div>
						</div>

						<div class="mb-3">
							<label class="form-label">Status</label>
							<div class="input-group">
								<select class="form-select w-auto flex-grow-0">
									<option value="1" selected>is</option>
									<option value="2">is not</option>
								</select>
								<select class="form-select">
									<option value="1" selected>Active</option>
									<option value="2">Inactive</option>
									<option value="3">New</option>
									<option value="4">Expired</option>
									<option value="5">Pending</option>
								</select>
							</div>
						</div>

						<div class="d-flex">
							<button type="button" class="btn btn-light">Reset</button>

							<div class="ms-auto">
								<button type="button" class="btn btn-light">Cancel</button>
								<button type="button" class="btn btn-primary ms-2">Apply</button>
							</div>
						</div>
					</div>
				</div>
			</div> --}}

			<ul class="nav flex-row justify-content-end order-1 order-lg-2">
				<li class="nav-item">
					<a href="#" class="navbar-nav-link navbar-nav-link-icon rounded-pill" data-bs-toggle="offcanvas" data-bs-target="#notifications">
						<i class="ph-bell"></i>
						<span class="badge bg-yellow text-black position-absolute top-0 end-0 translate-middle-top zindex-1 rounded-pill mt-1 me-1">2</span>
					</a>
				</li>

				<li class="nav-item nav-item-dropdown-lg dropdown ms-lg-2">
					<a href="#" class="navbar-nav-link align-items-center rounded-pill p-1" data-bs-toggle="dropdown">
						<div class="status-indicator-container">
							@if (Auth::user()->perfil?->foto_perfil)
								<img src="{{ asset('storage/' . Auth::user()->perfil->foto_perfil) }}" class="w-32px h-32px rounded-pill" alt="Foto de perfil">
							@else
								<img src="{{ asset('assets/images/demo/users/face11.jpg') }}" class="w-32px h-32px rounded-pill" alt="">	
							@endif
							
							<span class="status-indicator bg-success"></span>
						</div>
						
						<span class="d-none d-lg-inline-block mx-lg-2">
							{{ Auth::user()->name }}
						</span>
						
					</a>

					<div class="dropdown-menu dropdown-menu-end">
						

						<a class="dropdown-item {{ request()->routeIs('perfil.*') ? 'active' : '' }}" href="{{ route('perfil.completar') }}">
							<i class="ph ph-user-circle me-2"></i> 
							Mi perfil SIMETSA
						</a>

						
						<div class="dropdown-divider"></div>
						<a href="{{ route('profile.edit') }}" class="dropdown-item {{ request()->routeIs('profile.*') ? 'active' : '' }}">
							<i class="ph-user-circle me-2"></i>
							Configuración de cuenta
						</a>
						<form method="POST" action="{{ route('logout') }}">
							@csrf
							<button type="submit" class="dropdown-item">
								<i class="ph-sign-out me-2"></i>
								Cerrar sesion
							</button>
						</form>
					</div>
				</li>
			</ul>
		</div>
	</div>
	<!-- /main navbar -->


	<!-- Page content -->
	<div class="page-content">

		<!-- Main sidebar -->
		@include('layouts.navigation')
		<!-- /main sidebar -->


		<!-- Main content -->
		<div class="content-wrapper">

			<!-- Inner content -->
			<div class="content-inner">

				<!-- Page header -->
				<div class="page-header page-header-light shadow">
					{{-- <div class="page-header-content d-lg-flex">
						<div class="d-flex">
							<h4 class="page-title mb-0">
								Components - <span class="fw-normal">Alerts</span>
							</h4>

							<a href="#page_header" class="btn btn-light align-self-center collapsed d-lg-none border-transparent rounded-pill p-0 ms-auto" data-bs-toggle="collapse">
								<i class="ph-caret-down collapsible-indicator ph-sm m-1"></i>
							</a>
						</div>

						<div class="collapse d-lg-block my-lg-auto ms-lg-auto" id="page_header">
							<div class="d-sm-flex align-items-center mb-3 mb-lg-0 ms-lg-3">
								<div class="dropdown w-100 w-sm-auto">
									<a href="#" class="d-flex align-items-center text-body lh-1 dropdown-toggle py-sm-2" data-bs-toggle="dropdown" data-bs-display="static">
										<img src="{{ asset('assets/images/demo/users/face24.jpg') }}" class="w-32px h-32px me-2" alt="">
										<div class="me-auto me-lg-1">
											<div class="fs-sm text-muted mb-1">Customer</div>
											<div class="fw-semibold">Tesla Motors Inc</div>
										</div>
									</a>

									<div class="dropdown-menu dropdown-menu-lg-end w-100 w-lg-auto wmin-300 wmin-sm-350 pt-0">
										<div class="d-flex align-items-center p-3">
											<h6 class="fw-semibold mb-0">Customers</h6>
											<a href="#" class="ms-auto">
												View all
												<i class="ph-arrow-circle-right ms-1"></i>
											</a>
										</div>
										<a href="#" class="dropdown-item active py-2">
											<img src="{{ asset('assets/images/brands/tesla.svg') }}" class="w-32px h-32px me-2" alt="">
											<div>
												<div class="fw-semibold">Tesla Motors Inc</div>
												<div class="fs-sm text-muted">42 users</div>
											</div>
										</a>
										<a href="#" class="dropdown-item py-2">
											<img src="{{ asset('assets/images/brands/debijenkorf.svg') }}" class="w-32px h-32px me-2" alt="">
											<div>
												<div class="fw-semibold">De Bijenkorf</div>
												<div class="fs-sm text-muted">49 users</div>
											</div>
										</a>
										<a href="#" class="dropdown-item py-2">
											<img src="{{ asset('assets/images/brands/klm.svg') }}" class="w-32px h-32px me-2" alt="">
											<div>
												<div class="fw-semibold">Royal Dutch Airlines</div>
												<div class="fs-sm text-muted">18 users</div>
											</div>
										</a>
										<a href="#" class="dropdown-item py-2">
											<img src="{{ asset('assets/images/brands/shell.svg') }}" class="w-32px h-32px me-2" alt="">
											<div>
												<div class="fw-semibold">Royal Dutch Shell</div>
												<div class="fs-sm text-muted">54 users</div>
											</div>
										</a>
										<a href="#" class="dropdown-item py-2">
											<img src="{{ asset('assets/images/brands/bp.svg') }}" class="w-32px h-32px me-2" alt="">
											<div>
												<div class="fw-semibold">BP plc</div>
												<div class="fs-sm text-muted">23 users</div>
											</div>
										</a>
									</div>
								</div>

								<div class="vr d-none d-sm-block flex-shrink-0 my-2 mx-3"></div>

								<div class="d-inline-flex mt-3 mt-sm-0">
									<a href="#" class="status-indicator-container ms-1">
										<img src="{{ asset('assets/images/demo/users/face24.jpg') }}" class="w-32px h-32px rounded-pill" alt="">
										<span class="status-indicator bg-warning"></span>
									</a>
									<a href="#" class="status-indicator-container ms-1">
										<img src="{{ asset('assets/images/demo/users/face1.jpg') }}" class="w-32px h-32px rounded-pill" alt="">
										<span class="status-indicator bg-success"></span>
									</a>
									<a href="#" class="status-indicator-container ms-1">
										<img src="{{ asset('assets/images/demo/users/face3.jpg') }}" class="w-32px h-32px rounded-pill" alt="">
										<span class="status-indicator bg-danger"></span>
									</a>
									<a href="#" class="btn btn-outline-primary btn-icon w-32px h-32px rounded-pill ms-3">
										<i class="ph-plus"></i>
									</a>
								</div>
							</div>
						</div>
					</div> --}}

					@if(View::hasSection('breadcrumb') || View::hasSection('breadcrumb_elements'))
						<div class="page-header-content d-lg-flex border-top">
							<div class="d-flex">
								<div class="breadcrumb py-2">
									@yield('breadcrumb')
								</div>

								@hasSection('breadcrumb_elements')
									<a href="#breadcrumb_elements"
									class="btn btn-light align-self-center collapsed d-lg-none border-transparent rounded-pill p-0 ms-auto"
									data-bs-toggle="collapse">
										<i class="ph-caret-down collapsible-indicator ph-sm m-1"></i>
									</a>
								@endif
							</div>

							<div class="collapse d-lg-block ms-lg-auto" id="breadcrumb_elements">
								@yield('breadcrumb_elements')
							</div>
						</div>
					@endif
				</div>
				<!-- /page header -->


				<!-- Content area -->
				<div class="content">

					@include('partials.alertas')
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


	<!-- Notifications -->
	@include('partials.notifications')
	<!-- /notifications -->


	<!-- Demo config -->
	@include('partials.demo_config')
	<!-- /demo config -->
	<script src="{{ asset('assets/js/page_footer.js') }}"></script>
	@stack('scriptsFooter')
	

</body>
</html>
