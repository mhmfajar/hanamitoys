<?php

namespace App\Http\Controllers;

use App\Models\AttributeOption;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductAttributeValue;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
  public function __construct()
  {
    $this->data['q'] = null;
    $this->data['as'] = null;
    $this->data['cn'] = null;
    $this->data['tf'] = null;

    $this->data['categories'] = Category::parentCategories()
      ->orderBy('name', 'asc')
      ->get();

    $this->data['minPrice'] = Product::min('price');
    $this->data['maxPrice'] = Product::max('price');

    $this->data['figures'] = AttributeOption::whereHas('attribute', function ($query) {
      $query->where('code', 'figure')
        ->where('is_filterable', 1);
    })->orderBy('name', 'asc')->get();

    $this->data['sorts'] = [
      url('products') => 'Default',
      url('products?sort=price-asc') => 'Harga - Rendah ke Tinggi',
      url('products?sort=price-desc') => 'Harga - Tinggi ke Rendah',
      url('products?sort=created_at-desc') => 'Baru ke Lama',
      url('products?sort=created_at-asc') => 'Lama ke Baru',
    ];

    $this->data['selectedSort'] = url('products');
  }
  /**
   * Display a listing of the resource.
   *
   * @return \Illuminate\Http\Response
   */
  public function index(Request $request)
  {
    $products = Product::active();

    $products = $this->_searchProducts($products, $request);
    $products = $this->_filterProductsByPriceRange($products, $request);
    $products = $this->_filterProductsByAttribute($products, $request);
    $products = $this->_sortProducts($products, $request);

    $this->data['products'] = $products->paginate(12);
    return view('customer.products.index', $this->data);
  }

  private function _searchProducts($products, $request)
  {
    if ($q = $request->query('q')) {
      $q = str_replace('-', ' ', Str::slug($q));

      $products = $products->whereRaw('MATCH(name, slug, short_description, description) AGAINST (? IN NATURAL LANGUAGE MODE)', [$q]);

      $this->data['q'] = $q;
    }

    if ($categoryId = $request->query('as')) {
      $category = Category::where('id', $categoryId)->firstOrFail();

      $childIds = Category::childIds($category->id);
      $categoryIds = array_merge([$category->id], $childIds);

      $products = $products->whereHas('categories', function ($query) use ($categoryIds) {
        $query->whereIn('categories.id', $categoryIds);
      });

      $this->data['as'] = $categoryId;
    }

    if ($categoryId = $request->query('cn')) {
      $category = Category::where('id', $categoryId)->firstOrFail();

      $childIds = Category::childIds($category->id);
      $categoryIds = array_merge([$category->id], $childIds);

      $products = $products->whereHas('categories', function ($query) use ($categoryIds) {
        $query->whereIn('categories.id', $categoryIds);
      });

      $this->data['cn'] = $categoryId;
    }

    return $products;
  }

  private function _filterProductsByPriceRange($products, $request)
  {
    $lowPrice = null;
    $highPrice = null;

    if ($priceSlider = $request->query('price')) {
      $prices = explode('-', $priceSlider);

      $lowPrice = !empty($prices[0]) ? (float)$prices[0] : $this->data['minPrice'];
      $highPrice = !empty($prices[1]) ? (float)$prices[1] : $this->data['maxPrice'];

      if ($lowPrice && $highPrice) {
        $products = $products->where('price', '>=', $lowPrice)
          ->where('price', '<=', $highPrice)
          ->orWhereHas('variants', function ($query) use ($lowPrice, $highPrice) {
            $query->where('price', '>=', $lowPrice)
              ->where('price', '<=', $highPrice);
          });

        $this->data['minPrice'] = $lowPrice;
        $this->data['maxPrice'] = $highPrice;
      }
    }

    return $products;
  }

  private function _filterProductsByAttribute($products, $request)
  {
    if ($attributeOptionID = $request->query('tf')) {
      $attributeOption = AttributeOption::findOrFail($attributeOptionID);

      $products = $products->whereHas('ProductAttributeValues', function ($query) use ($attributeOption) {
        $query->where('attribute_id', $attributeOption->attribute_id)
          ->where('text_value', $attributeOption->name);
      });

      $this->data['tf'] = $attributeOptionID;
    }

    return $products;
  }

  private function _sortProducts($products, $request)
  {
    if ($sort = preg_replace('/\s+/', '', $request->query('sort'))) {
      $availableSorts = ['price', 'created_at'];
      $availableOrder = ['asc', 'desc'];
      $sortAndOrder = explode('-', $sort);

      $sortBy = strtolower($sortAndOrder[0]);
      $orderBy = strtolower($sortAndOrder[1]);

      if (in_array($sortBy, $availableSorts) && in_array($orderBy, $availableOrder)) {
        $products = $products->orderBy($sortBy, $orderBy);
      }

      $this->data['selectedSort'] = url('products?sort=' . $sort);
    }

    return $products;
  }

  /**
   * Display the specified resource.
   *
   * @param  string  $slug
   * @return \Illuminate\Http\Response
   */
  public function show($slug)
  {
    $product = Product::active()->where('slug', $slug)->first();

    if (!$product) {
      return redirect('products');
    }

    if ($product->configurable()) {
      $this->data['figures'] = ProductAttributeValue::getAttributeOptions($product, 'figure')->pluck('text_value', 'text_value');
      $this->data['attributesOption'] = ProductAttributeValue::getAttributeOptions($product, 'figure');
    }
    $attribute = AttributeOption::all()->where('name', $this->data['attributesOption'][0]->text_value)->first();

    $this->data['product'] = $product;
    $this->data['attributes'] = $attribute;

    return view('customer.products.show', $this->data);
  }

  public function categories(Request $request)
  {
    $cn = Category::where('parent_id', $request->query('as'))->get();
    return response()->json(['cn' => $cn]);
  }

  // /**
  //  * Quick view product.
  //  *
  //  * @param string $slug product slug
  //  *
  //  * @return \Illuminate\Http\Response
  //  */
  // public function quickView($slug)
  // {
  // 	$product = Product::active()->where('slug', $slug)->firstOrFail();
  // 	if ($product->configurable()) {
  // 		$this->data['colors'] = ProductAttributeValue::getAttributeOptions($product, 'color')->pluck('text_value', 'text_value');
  // 		$this->data['sizes'] = ProductAttributeValue::getAttributeOptions($product, 'size')->pluck('text_value', 'text_value');
  // 	}

  // 	$this->data['product'] = $product;

  // 	return $this->loadTheme('products.quick_view', $this->data);
  // }
}
