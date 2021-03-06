@extends('customer.master')

@section('title', ' | Products')

@section('content')
<div class="container content-product">
  <div class="row">
    <div class="col-md-3">
      <section class="panel">
        <header class="panel-heading">
          Filter
        </header>
        <div class="panel-body">
          <form action="{{ route('cproducts.index') }}" method="GET">
            <div class="form-group">
              <label>Anime Series</label>
              <select class="form-control" name="as" id="as">
                <option value="">-- Pilih --</option>
                @foreach($categories as $category)
                <option value="{{ $category->id }}" {{ $category->id == $as ? 'selected' : '' }}>{{ $category->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="form-group">
              <label>Character Name</label>
              <select class="form-control" name="cn" id="cn">
                <option value="">-- Pilih --</option>
              </select>
            </div>
            <div class="form-group">
              <label>Type Figure</label>
              <select class="form-control" name="tf">
                <option value="">-- Pilih --</option>
                @foreach($figures as $figure)
                <option value="{{ $figure->id }}" {{ $figure->id == $tf ? 'selected' : '' }}>{{ $figure->name }}</option>
                @endforeach
              </select>
            </div>
            <button class="btn btn-primary" type="submit">Filter</button>
          </form>
        </div>
      </section>
    </div>
    <div class="products-place-wrapper col-md-9">
      <form action="{{ route('cproducts.index') }}" class="form-inline mb-3" method="GET">
        <p><span>{{ count($products) }}</span> Produk ditemukan <span>{{ $products->total() }}</span><span class="ml-5">Sort by : {{ Form::select('sort', $sorts , $selectedSort ,array('onChange' => 'this.options[this.selectedIndex].value && (window.location = this.options[this.selectedIndex].value);', 'class' => 'form-control ml-2')) }}</span></p>
      </form>
      <div class="row">
        @foreach($products as $product)
        <div class="col-12 col-lg-3 col-md-4 col-sm-6 mb-4 product-list">
          <section class="panel mb-md-3 mb-sm-3 mb-4">
            <div class="pro-img-box">
              @if ($product->productImages->first())
              <img class="img-fluid" src="{{ asset('storage/'.$product->productImages->first()->medium) }}" alt="{{ $product->name }}">
              @endif
              <a class="add-to-fav" title="Wishlist" product-slug="{{ $product->slug }}" href="">
                <i class="far fa-heart"></i>
              </a>
            </div>

            <div class="panel-body text-center">
              <h4>
                <a href="{{ url('products/'. $product->slug) }}" class="pro-title">
                  {{ Str::limit($product->name, 25) }}
                </a>
              </h4>
              <p class="price">@currency($product->priceLabel())</p>
            </div>
          </section>
        </div>
        @endforeach
      </div>
      {{ $products->links() }}
    </div>
  </div>
</div>
@endsection

@section('script')
<script>
  function getQuickView(product_slug) {
    $.ajax({
      type: 'GET',
      url: '/products/quick-view/' + product_slug,
      success: function(response) {
        $('#exampleModal').html(response);
        $('#exampleModal').modal();
      }
    });
  }

  (function($) {
    $('#as').on('change', function(e) {
      var anime_id = e.target.value;

      $.get('/products/categories?as=' + anime_id, function(data) {
        $('#cn').empty();
        $('#cn').append('<option value>-- Pilih --</option>');

        $.each(data.cn, function(child, value) {

          $('#cn').append('<option value="' + value.id + '">' + value.name + '</option>');
        });
      });
    });

    // $('.quick-view').on('click', function(e) {
    //   e.preventDefault();

    //   var product_slug = $(this).attr('product-slug');

    //   getQuickView(product_slug);
    // });

    // $('.add-to-card').on('click', function(e) {
    //   e.preventDefault();

    //   var product_type = $(this).attr('product-type');
    //   var product_id = $(this).attr('product-id');
    //   var product_slug = $(this).attr('product-slug');

    //   if (product_type == 'configurable') {
    //     getQuickView(product_slug);
    //   } else {
    //     $.ajax({
    //       type: 'POST',
    //       url: '/carts',
    //       data: {
    //         _token: $('meta[name="csrf-token"]').attr('content'),
    //         product_id: product_id,
    //         qty: 1
    //       },
    //       success: function(response) {
    //         location.reload(true);
    //       }
    //     });
    //   }
    // });
  })(jQuery);
</script>
@endsection