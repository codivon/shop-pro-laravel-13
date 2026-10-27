@extends('sellers-old.panel.layouts.master')
@push('styles')

@endpush

@section('content')
    <div class="content-wrapper">

        <div class="content-body">
            <div class="c-content-page c-content-page--plain c-grid__row w-100 mb-2">
                <div class="c-grid__col">
                    <div class="c-content-page__header">
                        <span class="c-content-page__header-action">مدیریت تنوع و قیمت‌گذاری</span>
                        <span class="c-content-page__header-desc">فعال و غیر فعال کردن تنوع‌های محصول، تغییر قیمت، موجودی و اعمال تخفیف از این قسمت امکان پذیر است.</span>
                    </div>
                </div>
            </div>
            <!-- filter start -->
            <!-- filter end -->
            <form id="filter-products-form" method="post"
                  action="{{ route('seller.products.variant.store',$product) }}">
                @csrf
                <div class="card">



                    <div class="card-content collapse show">
                        <div class="card-body">


                            <div class="c-content-modal__notes">
                                <span class="c-content-modal__notes-title">توجه:</span>
                                <ul class="c-content-modal__notes-list">
                                    <li>لطفاً قبل از درج تنوع، مشخصات فنی کالا (مانند: رنگ، ابعاد، اقلام همراه کالا، جنس کالا، تصویر بسته‌بندی و ...) را در سایت چک کرده و اطمینان حاصل کنید که تنوع شما با مشخصات فنی کالا در سایت مطابقت داشته باشد. عدم تطابق مشخصات فنی کالا با تنوع شما، علاوه بر ایجاد نارضایتی مشتریان و تاثیر در امتیاز عملکرد شما، موجب مرجوع شدن سفارشات به علت مغایرت شده و همچنین موجب غیر‌فعال شدن کالا/گروه کالایی خواهد شد.</li>
                                </ul>
                            </div>

                            <div class="product-variant c-variant__header">
                                <div class="c-variant__img-container">
                                    <img src="{{ $product->image ? asset($product->image) : asset('/no-image-product.svg') }}" alt="{{ $product->title }}" class="c-variant__img">
                                </div>
                                <div class="c-variant__descr">
                                    <h2 class="c-variant__title">{{$product->title}}</h2>
                                    <div class="c-variant__sub-title"></div>
                                    <div class="c-variant__secondary-info c-variant__secondary-info--top">
                                        <ul class="c-variant__secondary-info--table">
                                            <li class="c-variant__secondary-info--table-row">
                                                <div class="c-variant__secondary-info--table-cell">
                                                    <span class="c-variant__info">دسته بندی:</span>
                                                    <span class="c-variant__info--main">{{$product->category->title}}</span>
                                                </div>

                                                <div class="c-variant__secondary-info--table-cell">
                                                    <span class="c-variant__info">برند کالا:</span>
                                                    <span class="c-variant__info--main">@if($product->brand){{$product->brand->name}}@else بدون برند @endif</span>
                                                </div>

                                                <div class="c-variant__secondary-info--table-cell">
                                                    <span class="c-variant__info">کمترین قیمت:</span>
                                                    <span class="c-variant__info--main">{{number_format(@$product->lowestPrice->discount_price)}} تومان </span>
                                                </div>
                                                <div class="c-variant__secondary-info--table-cell">
                                                    <span class="c-variant__info">کمیسیون فروش این کالا:</span>
                                                    <span class="c-variant__info--main">
                                                        @if($product->category->commission)
                                                            {{$product->category->commission}}%
                                                        @else
                                                            بدون کمیسیون
                                                        @endif
                                                    </span>
                                                </div>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>


                            <div class="c-grid__row c-grid__row--gap-lg mt-30">
                                <div class="c-grid__col c-grid__col--gap-lg c-grid__col--flex-initial relative">

                                   <div class="c-variant-box js-new-variant-container">
                                       <div class="row">
                                           <div class="col-12 text-right mt-1 mb-0 pr-2">
                                               <button id="add-product-prices" type="button" class="btn btn-outline-primary waves-effect waves-light"><i class="feather icon-plus"></i> افزودن تنوع</button>
                                           </div>
                                       </div>

                                       <div class="c-variant-box__main" id="product-prices-div">
                                           @if ($product->isPhysical())
                                                   @foreach ($product->prices_seller as $price)
                                                       <div class="row single-price">

                                                           <div class="col-12">
                                                               <div class="row">
                                                                   @foreach ($attributeGroups as $attributeGroup)
                                                                       <div class="col-md-3 col-12">
                                                                           <div class="form-group">
                                                                               <label>{{ $attributeGroup->name }}</label>
                                                                               <select class="form-control price-attribute-select" name="prices[{{ $loop->parent->iteration }}][attributes][]" >
                                                                                   <option value="">انتخاب کنید</option>
                                                                                   @foreach ($attributeGroup->get_attributes as $attribute)
                                                                                       <option value="{{ $attribute->id }}" {{ $price->get_attributes()->find($attribute->id) ? 'selected' : '' }}>{{ $attribute->name }}</option>
                                                                                   @endforeach
                                                                               </select>
                                                                           </div>
                                                                       </div>
                                                                   @endforeach
                                                               </div>
                                                           </div>

                                                           <div class="col-md-3 col-12">
                                                               <div class="form-group">
                                                                   <label>قیمت
                                                                       <span class="required-star" style="color:red;">*</span>
                                                                   </label>
                                                                   <input type="number" data-unit="تومان" class="form-control amount-input price" name="prices[{{ $loop->iteration }}][price]" value="{{ $price->price() }}" required>
                                                               </div>
                                                           </div>

                                                           <div class="col-md-3 col-12">
                                                               <div class="form-group">
                                                                   <label>تخفیف</label>
                                                                   <input type="number" class="form-control discount" name="prices[{{ $loop->iteration }}][discount]" value="{{ $price->discount }}" min="0" max="100" placeholder="%">
                                                               </div>
                                                           </div>

                                                           <div class="col-md-3 col-12">
                                                               <div class="form-group">
                                                                   <label>بیشترین تعداد مجاز در هر سفارش</label>
                                                                   <input type="number" class="form-control" name="prices[{{ $loop->iteration }}][cart_max]" value="{{ $price->cart_max }}" min="1">
                                                               </div>
                                                           </div>
                                                           <div class="col-md-3 col-12">
                                                               <div class="form-group">
                                                                   <label>کمترین تعداد مجاز در هر سفارش</label>
                                                                   <input type="number" class="form-control" name="prices[{{ $loop->iteration }}][cart_min]" value="{{ $price->cart_min }}" min="1">
                                                               </div>
                                                           </div>
                                                           <div class="col-md-3 col-12">
                                                               <div class="form-group">
                                                                   <label>موجودی محصول نزد شما
                                                                       <span class="required-star" style="color:red;">*</span>
                                                                   </label>
                                                                   <input type="number" class="form-control" name="prices[{{ $loop->iteration }}][stock]" value="{{ $price->stock }}" min="0" required>
                                                               </div>
                                                           </div>
                                                           <div class="col-md-3 col-12">
                                                               <div class="form-group">
                                                                   <label>قیمت نهایی</label>
                                                                   <input type="text" class="form-control final-price" disabled>
                                                               </div>
                                                           </div>
                                                           <div class="col-md-3 col-12">
                                                               <div class="form-group">
                                                                   <label>وضعیت</label>
                                                                   <select class="form-control" name="prices[{{ $loop->iteration }}][published]">
                                                                       <option @if($price->published==1) selected @endif value="1">نمایش</option>
                                                                       <option @if($price->published==0) selected @endif value="0">عدم نمایش</option>

                                                                   </select>
                                                               </div>
                                                           </div>
                                                           <div class="col-md-6 d-none">
                                                               <div class="form-group">
                                                                   <select class="form-control sellers" name="prices[{{ $loop->iteration }}][attributes_seller][]">
                                                                           <option selected value="{{seller_info()->seller_id}}"></option>
                                                                   </select>
                                                               </div>
                                                           </div>


                                                           <div class="col-md-12 text-right">
                                                               <button type="button" class="btn btn-flat-danger waves-effect waves-light remove-product-price custom-padding">حذف<i class="fa fa-trash text-danger"></i></button>
                                                           </div>

                                                           <div class="col-md-12"><hr class="mt-4 mb-4"></div>
                                                       </div>
                                                   @endforeach

                                           @endif
                                       </div>


                                       <div class="c-grid__col c-grid__col--gap-lg mb-2 justify-content-end">
                                           <button type="submit" class="c-ui-btn c-ui-btn--dkpc height-auto" style="line-height: 30px" id="saveNewVariantsButton">
                                               @if(count($product->prices_seller))
                                               ویرایش لیست تنوع
                                               @else
                                                   فروش این محصول
                                                   و افزودن به لیست تنوع
                                               @endif
                                           </button>
                                       </div>
                                   </div>

                                </div>

                            </div>


                        </div>
                    </div>
                </div>

            </form>

        </div>
    </div>

    @include('sellers-old.panel.products.partials.prices-template')
@endsection
@push('scripts')
    <script>
        var groupCount = {{ $product ? $product->specificationGroups->unique()->count() : '0' }};
        var specificationCount = {{ $product ? $product->specifications->unique()->count() : '0' }};

        var availableTypes = [];

        var specifications_type_first_change = {{ $product ? 'true' : 'false' }};

        var priceCount = {{ $product ? count($product->prices_seller) : '0' }};
        var filesCount = 0;
        var AbilitygroupCount = 0;
        var sizesCount = {{ $product ? $product->sizes()->count() : '0' }};
    </script>
    <script src="{{ asset('back/app-assets/plugins/jquery-ui-sortable/jquery-ui.min.js') }}"></script>
    <script src="{{ theme_asset('js/plugins/jquery-validation/jquery.validate.min.js') }}"></script>
    <script src="{{ theme_asset('js/pages/sellers/products/variant.js') }}?v=7"></script>
@endpush
