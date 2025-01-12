@extends('admin.master')

@section('title', 'Dashboard')

@section('content')
<div class="container-fluid">
	<div class="panel shadow">
		<div class="header">
			<h2 class="title"><i class="fas fa-chart-bar"></i> Estadísticas rápidas.</h2>
		</div>
	</div>

	<div class="row mtop16">
		<div class="col-md-3">
			<div class="panel shadow">
				<div class="header">
					<h2 class="title"><i class="fas fa-credit-card"></i> Facturación del mes.</h2>
				</div>
				<div class="inside">
					<div class="big_count">
						CLP {{ $facturado_mes }}
					</div>
				</div>
			</div>
		</div>

		<div class="col-md-3">
			<div class="panel shadow">
				<div class="header">
					<h2 class="title"><i class="fas fa-credit-card"></i> Facturado hoy.</h2>
				</div>
				<div class="inside">
					<div class="big_count">
						CLP {{ $facturado_hoy }}
					</div>
				</div>
			</div>
		</div>

		<div class="col-md-3">
			<div class="panel shadow">
				<div class="header">
					<h2 class="title"><i class="fas fa-shopping-basket"></i> Ordenes de hoy.</h2>
				</div>
				<div class="inside">
					<div class="big_count">{{ $ordenes }}</div>
				</div>
			</div>
		</div>

		<div class="col-md-3">
			<div class="panel shadow">
				<div class="header">
					<h2 class="title"><i class="fas fa-boxes"></i> Productos listados.</h2>
				</div>
				<div class="inside">
					<div class="big_count">{{ $products }}</div>
				</div>
			</div>
		</div>
	</div>
</div>
@endsection