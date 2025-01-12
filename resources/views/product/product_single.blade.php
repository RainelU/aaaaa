@extends('master')

@section('title', $product->name)

@section('custom_meta')
<meta name="product_id" content="{{ $product->id }}">
@stop

@section('content')
<div class="product_single shadow-lg">
	<div class="inside">
		<div class="container">
			<div class="row">
				<!-- Featured Img & Gallery -->
				<div class="col-md-4 pleft0">
					<div class="slick-slider">
						@if(count($product->getGallery) > 0)
							@foreach($product->getGallery as $gallery)
								<div>
									<img src="{{ url('/uploads/'.$gallery->file_path.'/'.$gallery->file_name) }}" style="height: auto; max-width: 100%;">
								</div>
							@endforeach
						@else
							<div>
								<h3>
									El producto no posee imágenes
								</h3>
							</div>
						@endif
					</div>
				</div>

				<div class="col-md-8">
					<h2 class="title">{{ $product->name }}</h2>
					<div class="category">
						<ul>
							<li><a href="{{ url('/') }}"><i class="fas fa-house-user"></i> Inicio</a></li>
							<li><span class="next"><i class="fas fa-chevron-right"></i></span></li>
							<li><a href="{{ url('/store') }}"><i class="fas fa-store"></i> Tienda</a></li>
							<li><span class="next"><i class="fas fa-chevron-right"></i></span></li>
							<li><a href="{{ url('/store') }}"><i class="far fa-folder"></i> {{ $product->cat->name }}</a></li>
							@if($product->subcategory_id != "0")
							<li><span class="next"><i class="fas fa-chevron-right"></i></span></li>
							<li><a href="{{ url('/store') }}"><i class="far fa-folder"></i> {{ $product->getSubcategory->name }}</a></li>
							@endif
						</ul>
					</div>

					<div class="add_cart">
						{!! Form::open(['url' => '/cart/product/'.$product->id.'/add']) !!}
						{!! Form::hidden('product', $product->id, ['id' => 'field_inventory']) !!}
						{!! Form::hidden('variant', null, ['id' => 'field_variant']) !!}
						<div class="row">
							<div class="col-md-12">
								<div class="variants mtop16">
									@if($product->quantity > 0)
										<label>DISPONIBLES: {{ $product->quantity }}</label>
									@else
										<label class="text-danger"><i class="fa fa-wallet"></i> AGOTADO</label>
									@endif
								</div>
								@if($product->in_discount)
									<div class="variants mtop16">
										<h4 style="color: #3e3e3e">
											PRECIO ANTES: <b><del>CLP {{ number_format($product->price, 0, '', '.') }}</del></b>
										</h4>
									</div>
									<div class="variants mtop16">
										<h4>
											DESCUENTO: <b>{{ number_format($product->discount, 0, '', '.') }}%</b>
										</h4>
									</div>
									<div class="variants mtop16">
										<h4>
											PRECIO TOTAL: <b>CLP {{ number_format(($product->price - (($product->discount / 100) * $product->price)), 0, '', '.') }}</b>
										</h4>
									</div>
								@else
									<div class="variants mtop16">
										<h4>
											PRECIO NORMAL: <b>CLP {{ number_format($product->price, 0, '', '.') }}</b>
										</h4>
									</div>
								@endif

								<div class="variants hidden btop1 ptop16 mtop16" id="variants_div">
									<p><strong>Más opciones del producto:</strong></p>
									<ul id="variants"></ul>
								</div>

							</div>
						</div>
						<div class="before_quantity">
							<h5 class="title">¿Qué cantidad deseas comprar?</h5>
							<div class="row">
								<div class="col-md-4 col-12">
									<div class="quantity">
										<a href="#" class="amount_action" data-action="minus">
											<i class="fas fa-minus"></i>
										</a>
										{{ Form::number('quantity', 1, ['class' => 'form-control', 'min' => '1', 'id' => 'add_to_cart_quantity']) }}
										<a href="#" class="amount_action" data-action="plus">
											<i class="fas fa-plus"></i>
										</a>
									</div>
								</div>
								<div class="col-md-4 col-12">
									<button type="submit" class="btn btn-success"><i class="fas fa-cart-plus"></i> Agregar al carrito</button>
								</div>

								<div class="col-md-4 col-12">
									<a href="#" id="favorite_1_{{ $product->id }}" onclick="add_to_favorites({{ $product->id }}, '1'); return false;" class="btn btn-favorite">
										<i class="fas fa-heart"></i> Agregar a favoritos
									</a>
								</div>
							</div>
						</div>
						{!! Form::close() !!}
					</div>

					<div class="content">
						{!! html_entity_decode($product->content) !!}
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
@endsection