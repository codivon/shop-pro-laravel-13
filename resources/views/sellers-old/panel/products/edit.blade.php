@extends('sellers-old.panel.layouts.master')

@section('content')

    <div class="app-content content">
        <div class="c-content-page c-content-page--plain c-grid__row w-100 mb-2">
            <div class="c-grid__col">
                <div class="c-content-page__header">
                    <span class="c-content-page__header-action">ویرایش محصول </span>
                    <span class="c-content-page__header-desc">تغییر اطلاعات کلی و فعال و غیر فعال کردن تنوع‌های محصول، تغییر قیمت، موجودی و اعمال تخفیف و ... از این قسمت امکان پذیر است.</span>
                </div>
            </div>
        </div>


            <div id="main-card" class="content-body">
                <form class="form" id="product-edit-form" action="{{ route('seller.products.update', ['product' => $product]) }}" data-redirect="{{ route('seller.products.index') }}" method="post">
                    @csrf
                    @method('put')

                    <div class="row match-height">
                        <div class="col-md-12">
                            <div class="card overflow-hidden">
                                <div class="card-header">
                                    <h4 class="card-title">اطلاعات محصول "{{ $product->title }}"</h4>
                                    <div class="row ">
                                        <div class="col-12 text-center">
                                            <button type="submit" class="c-mega-campaigns__btns-green-plus uk-margin-remove border-none">ویرایش محصول
                                            </button>
                                        </div>
                                     </div>
                                </div>
                                <div class="card-content">
                                    <div class="card-body">

                                        <div class="nav-vertical">
                                            <ul class="nav nav-tabs nav-left flex-column" role="tablist">
                                                <li class="nav-item">
                                                    <a class="nav-link active" id="baseVerticalLeft-tab1" data-toggle="tab" aria-controls="tabVerticalLeft1" href="#tabVerticalLeft1" role="tab" aria-selected="true">اطلاعات کلی</a>
                                                </li>
                                                <li class="nav-item">
                                                    <a class="nav-link" id="productMetaTab" data-toggle="tab" aria-controls="tabProductMeta" href="#tabProductMeta" role="tab" aria-selected="false">سئو</a>
                                                </li>
                                                <li class="nav-item physical-item">
                                                    <a class="nav-link" id="product-prices-tab-nav" data-toggle="tab" aria-controls="product-prices-tab" href="#product-prices-tab" role="tab" aria-selected="false">قیمت (تنوع)</a>
                                                </li>
                                                <li class="nav-item download-item">
                                                    <a class="nav-link" id="product-files-tab-nav" data-toggle="tab" aria-controls="product-files-tab" href="#product-files-tab" role="tab" aria-selected="false">فایل</a>
                                                </li>
                                                <li class="nav-item">
                                                    <a class="nav-link" id="productImageTab" data-toggle="tab" aria-controls="tabProductImage" href="#tabProductImage" role="tab" aria-selected="false">تصاویر</a>
                                                </li>
                                                <li class="nav-item">
                                                    <a class="nav-link" id="specification-tab" data-toggle="tab" aria-controls="tabSpecification" href="#tabSpecification" role="tab" aria-selected="false">مشخصات</a>
                                                </li>
                                                <li class="nav-item">
                                                    <a class="nav-link" id="sizes-tab" data-toggle="tab" aria-controls="tabSize" href="#tabSize" role="tab" aria-selected="false">سایز بندی</a>
                                                </li>
                                                <li class="nav-item">
                                                    <a class="nav-link" id="specification-tab" data-toggle="tab" aria-controls="tabSize" href="#rating" role="tab" aria-selected="false">امتیاز به ویژگی‌</a>
                                                </li>
                                            </ul>
                                            <div class="tab-content">
                                                <div class="tab-pane active" id="tabVerticalLeft1" role="tabpanel" aria-labelledby="baseVerticalLeft-tab1">
                                                    <div class="col-md-12">
                                                        <div class="form-body">

                                                            <div class="row">
                                                                <div class="col-md-6">
                                                                    <div class="form-group">
                                                                        <label>عنوان</label>
                                                                        <input type="text" class="form-control" name="title" value="{{ $product->title }}">
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <div class="form-group">
                                                                        <label>عنوان انگلیسی</label>
                                                                        <input type="text" class="form-control" name="title_en" value="{{ $product->title_en }}">
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <div class="form-group">
                                                                        <label>دسته بندی اصلی</label>
                                                                        <select class="form-control product-category" name="category_id">
                                                                            <option value="">انتخاب کنید</option>
                                                                            @foreach ($categories as $category)
                                                                                <option
                                                                                    class="l{{ $category->parents()->count() + 1 }} {{ $category->categories()->count() ? 'non-leaf' : '' }}"
                                                                                    data-pup="{{ $category->category_id }}"
                                                                                    {{ ($product->category_id == $category->id) ? 'selected' : '' }}
                                                                                    value="{{ $category->id }}">{{ $category->title }}
                                                                                </option>
                                                                            @endforeach
                                                                        </select>
                                                                    </div>
                                                                </div>

                                                                <div class="col-md-6 col-12">
                                                                    <div class="form-group">
                                                                        <label>نوع محصول</label>
                                                                        <select name="type" id="product-type" class="form-control">
                                                                            <option value="physical" {{ $product->isPhysical() ? 'selected' : '' }}>محصول فیزیکی</option>
                                                                            <option value="download" {{ $product->isDownload() ? 'selected' : '' }}>محصول دانلودی</option>
                                                                        </select>
                                                                    </div>
                                                                </div>

                                                                <div class="col-md-3 col-12">
                                                                    <div class="form-group">
                                                                        <label>برند</label>
                                                                        <select name="brand" class="form-control">
                                                                            <option>انتخاب کنید</option>
                                                                            @foreach($brands as $brand)
                                                                                <option value="{{$brand->name}}" @if(@$product->brand->name == $brand->name)selected @endif>{{$brand->name}}</option>
                                                                            @endforeach
                                                                        </select>
                                                                    </div>
                                                                </div>

                                                                <div class="col-md-3 col-12 physical-item">
                                                                    <div class="form-group">
                                                                        <label>وزن (گرم)</label>
                                                                        <input type="number" class="form-control" name="weight" value="{{ $product->weight }}">
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-3 col-12 physical-item">
                                                                    <div class="form-group">
                                                                        <label>واحد</label>
                                                                        <input type="text" class="form-control" name="unit" value="{{ $product->unit }}">
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-3 col-12 physical-item">
                                                                    <div class="form-group">
                                                                        <label>ماهیت کالا</label>
                                                                        <select name="shipping_nature" id="shipping-nature" class="form-control">
                                                                            <option value="small" {{ $product->shipping_nature=="small" ? 'selected' : '' }}>کوچک</option>
                                                                            <option value="medium" {{ $product->shipping_nature=="medium" ? 'selected' : '' }}>متوسط</option>
                                                                            <option value="large" {{ $product->shipping_nature=="large" ? 'selected' : '' }}>بزرگ</option>
                                                                        </select>
                                                                    </div>
                                                                </div>

                                                            </div>
                                                            <div class="row">
                                                                <div class="col-md-12">
                                                                    <div class="form-group">
                                                                        <label>دسته بندی ها</label>
                                                                        <select class="form-control product-categories" name="categories[]" multiple>
                                                                            @foreach ($categories as $category)
                                                                                <option
                                                                                    class="l{{ $category->parents()->count() + 1 }} {{ $category->categories()->count() ? 'non-leaf' : '' }}"
                                                                                    data-pup="{{ $category->category_id }}"
                                                                                    {{ ($product->categories()->find($category->id)) ? 'selected' : '' }}
                                                                                    value="{{ $category->id }}">{{ $category->title }}
                                                                                </option>
                                                                            @endforeach
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <fieldset class="form-group">
                                                                        <label>برچسب</label>
                                                                        <input type="text" name="labels" class="form-control labels" data-action="{{ route('admin.get-labels') }}" value="{{ $product->getLabels() }}">
                                                                    </fieldset>
                                                                </div>

                                                                <div class="col-md-6">
                                                                    <div class="form-group">
                                                                        <label>توضیحات کوتاه</label>
                                                                        <textarea class="form-control" name="short_description" rows="4">{{ $product->short_description }}</textarea>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-12">
                                                                    <div class="form-group">
                                                                        <label for="first-name-vertical">توضیحات</label>
                                                                        <textarea id="description" class="form-control" rows="3" name="description">{{ $product->description }}</textarea>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="tab-pane" id="product-prices-tab" role="tabpanel" aria-labelledby="product-prices-tab-nav">

                                                    <div class="card collapse-icon accordion-icon-rotate product-prices-tab">

                                                        <div class="card-body">

                                                            <div id="product-prices-div" class="product-prices-div">
                                                                @if ($product->isPhysical())
                                                                    @foreach ($product->prices as $price)
                                                                       @if($price->seller_id==sellerID())
                                                                            @include('sellers-old.panel.products.partials.prices-include', ['price' => $price])
                                                                       @endif

                                                                    @endforeach
                                                                @endif
                                                            </div>

                                                            <div class="row">
                                                                <div class="col-12 text-center mt-2">
                                                                    <button id="add-product-prices" type="button" class="btn btn-outline-primary waves-effect waves-light"><i class="feather icon-plus"></i> افزودن قیمت</button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                </div>

                                                <div class="tab-pane" id="product-files-tab" role="tabpanel" aria-labelledby="product-files-tab-nav">

                                                    <div class="col-md-12">
                                                        <div id="product-files-area">
                                                            @if ($product->isDownload())
                                                                @foreach ($product->prices()->orderBy('ordering')->get() as $price)
                                                                    @include('sellers-old.panel.products.partials.files-include', ['price' => $price])
                                                                @endforeach
                                                            @endif
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-12 text-center mt-2">
                                                                <button id="add-product-file" type="button" class="btn btn-outline-primary waves-effect waves-light"><i class="feather icon-plus"></i> افزودن فایل</button>
                                                            </div>
                                                        </div>
                                                    </div>

                                                </div>

                                                <div class="tab-pane" id="tabSpecification" role="tabpanel" aria-labelledby="specification-tab">
                                                    <div id="specifications-card" class="card">
                                                        <div class="card-header d-flex justify-content-between align-items-end">
                                                            <h4 class="card-title">مشخصات محصول</h4>
                                                            <p class="font-medium-5 mb-0"><i class="feather icon-help-circle text-muted cursor-pointer"></i></p>
                                                        </div>
                                                        <div class="card-content">
                                                            <div class="card-body ">
                                                                <div class="row">
                                                                    <div class="col-md-7 text-justify">
                                                                        <p>در این بخش میتوانید مشخصات محصول را وارد کنید. دقت  کنید که محصولات بر اساس نوع مشخصات با یکدیگر مقایسه میشوند. به عنوان مثال یک محصول با نوع مشخصات "گوشی موبایل"  فقط با محصولات این نوع مقایسه میشود</p>
                                                                    </div>
                                                                    <div class="col-md-4">
                                                                        <div class="form-group">
                                                                            <label>نوع مشخصات</label>
                                                                            <input id="specifications_type" class="form-control" name="spec_type" placeholder="مثلا گوشی موبایل" value="{{ $product->specType ? $product->specType->name : '' }}">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="form-body pt-2">
                                                                    <div id="specifications-area">
                                                                        @foreach ($product->specificationGroups->unique() as $group)
                                                                            <div class="row mt-2 specification-group">
                                                                                <div class="col-12">
                                                                                    <div class="row group-row">
                                                                                        <div class="col-md-1">
                                                                                            <span>نام گروه</span>
                                                                                        </div>
                                                                                        <div class="col-md-10 form-group">
                                                                                            <input type="text" class="form-control group-input" data-group_name="{{ $loop->index }}" name="specification_group[{{ $loop->index }}][name]" placeholder="مثال: مشخصات کلی" value="{{ $group->name }}" required>
                                                                                        </div>
                                                                                    </div>
                                                                                </div>

                                                                                <div class="all-specifications col-12">
                                                                                    @foreach($product->specifications()->where('specification_group_id', $group->id)->get() as $specification)
                                                                                        <div class="single-specificition">
                                                                                            <div class="row">
                                                                                                <div class="col-md-1">
                                                                                                    <fieldset>
                                                                                                        <label>
                                                                                                            <input name="specification_group[{{ $loop->parent->index }}][specifications][{{ $loop->index }}][special]" type="checkbox" {{ $specification->pivot->special ? 'checked' : '' }}>
                                                                                                        </label>
                                                                                                    </fieldset>
                                                                                                </div>
                                                                                                <div class="col-md-4 form-group">
                                                                                                    <p class="spec-label">عنوان</p>
                                                                                                    <input type="text" class="form-control spec-label" name="specification_group[{{ $loop->parent->index }}][specifications][{{ $loop->index }}][name]" placeholder="مثال: حافظه داخلی" value="{{ $specification->name }}" required>
                                                                                                </div>

                                                                                                <div class="col-md-6 form-group">
                                                                                                    <p class="spec-label">مقدار</p>
                                                                                                    <textarea  class="form-control spec-label" rows="1" name="specification_group[{{ $loop->parent->index }}][specifications][{{ $loop->index }}][value]" placeholder="مثال: 32 گیگابایت" required>{{ $specification->pivot->value }}</textarea>
                                                                                                </div>
                                                                                                <div class="col-md-1">
                                                                                                    <button type="button" class="btn btn-flat-danger waves-effect waves-light remove-specification custom-padding"><i class="feather icon-minus"></i></button>
                                                                                                </div>
                                                                                            </div>
                                                                                        </div>
                                                                                    @endforeach
                                                                                </div>

                                                                                <div class="col-md-12 text-center">
                                                                                    <button type="button" class="btn btn-flat-success waves-effect waves-light add-specifaction">افزودن مشخصات</button>
                                                                                    <button type="button" class="btn btn-flat-danger waves-effect waves-light remove-group">حذف گروه</button>

                                                                                </div>
                                                                            </div>
                                                                        @endforeach
                                                                    </div>

                                                                    <div class="row">
                                                                        <div class="col-12 text-center mt-4">
                                                                            <button id="add-product-specification-group" type="button" class="btn btn-outline-primary waves-effect waves-light"><i class="feather icon-plus"></i> افزودن گروه مشخصات</button>
                                                                        </div>
                                                                    </div>

                                                                </div>
                                                            </div>
                                                        </div>

                                                    </div>
                                                </div>

                                                <div class="tab-pane" id="tabProductMeta" role="tabpanel" aria-labelledby="productMetaTab">
                                                    <div class="col-md-12">
                                                        <div class="form-body">

                                                            <div class="row">
                                                                <div class="col-md-6">
                                                                    <div class="form-group">
                                                                        <label>عنوان سئو</label>
                                                                        <input type="text" class="form-control" name="meta_title" value="{{ $product->meta_title }}">
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <div class="form-group">
                                                                        <label>url</label>
                                                                        <input id="slug" type="text" class="form-control" name="slug" value="{{ $product->slug }}">
                                                                        <p>
                                                                            <small >
                                                                                <a id="generate-product-slug">ایجاد خودکار</a>
                                                                                <span id="slug-spinner" class="spinner-grow spinner-grow-sm text-success" role="status" style="display: none;">
                                                                                    <span class="sr-only">Loading...</span>
                                                                                </span>
                                                                            </small>
                                                                        </p>
                                                                    </div>
                                                                </div>

                                                                <div class="col-md-6">
                                                                    <div class="form-group">
                                                                        <label>توضیحات سئو</label>
                                                                        <textarea class="form-control" name="meta_description" rows="3">{{ $product->meta_description }}</textarea>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <fieldset class="form-group">
                                                                        <label>کلمات کلیدی</label>
                                                                        <input type="text" name="tags" class="form-control tags" data-action="{{ route('admin.get-tags') }}" value="{{ $product->getTags }}">
                                                                    </fieldset>
                                                                </div>

                                                            </div>

                                                        </div>
                                                    </div>
                                                </div>

                                                @include('back.products.partials.sizes-tab')

                                                <div class="tab-pane overflow-hidden" id="tabProductImage" role="tabpanel" aria-labelledby="productImageTab">
                                                    <div class="col-md-12">
                                                        <div class="form-body">

                                                            <div class="row">
                                                                <div class="col-md-12">
                                                                    <label>تصاویر محصول ( <small>بهترین اندازه <span class="text-danger">{{ config('front.imageSizes.productGalleryImage') }}</span> پیکسل میباشد.</small> )</label>

                                                                    <div class="dropzone dropzone-area mb-2" id="product-images">
                                                                        <div class="dz-message">تصاویر را به اینجا بکشید</div>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <fieldset class="form-group">
                                                                        <label>تصویر شاخص</label>
                                                                        <div class="custom-file">
                                                                            <input id="image" type="file" accept="image/*" name="image" class="custom-file-input">
                                                                            <label class="custom-file-label" for="image">{{ $product->image }}</label>
                                                                            <p><small>بهترین اندازه <span class="text-danger">{{ config('front.imageSizes.productImage') }}</span> پیکسل میباشد.</small></p>
                                                                        </div>
                                                                    </fieldset>
                                                                </div>

                                                                <div class="col-md-6">
                                                                    <div class="form-group">
                                                                        <label>متن جایگزین تصویر</label>
                                                                        <input type="text" class="form-control" name="image_alt" value="{{ $product->image_alt }}">
                                                                    </div>
                                                                </div>

                                                            </div>

                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="tab-pane" id="rating" role="tabpanel" aria-labelledby="specification-tab">
                                                    <div id="specifications-card" class="card">
                                                        <div class="card-header d-flex justify-content-between align-items-end">
                                                            <h4 class="card-title">امتیاز به ویژگی های محصول</h4>
                                                            <p class="font-medium-5 mb-0"><i class="feather icon-help-circle text-muted cursor-pointer"></i></p>
                                                        </div>
                                                        <div class="card-content">
                                                            <div class="card-body ">
                                                                <div class="row">
                                                                    <div class="col-md-7 text-justify">
                                                                        <p>در این بخش میتوانید عنوان ویژگی و امتیاز ویژگی را وارد کنید.</p>
                                                                    </div>
                                                                </div>
                                                                <div class="form-body pt-2">
                                                                    <div id="AbilityProductScore-area">
                                                                        @foreach($AbilityScores as $AbilityScore)
                                                                        <div class="single-specificition animated fadeIn">
                                                                            <div class="row">

                                                                                <div class="col-md-6 form-group">
                                                                                    <p class="spec-label">عنوان</p>
                                                                                    <input type="text" class="form-control spec-label" name="AbilityProductScore[{{$loop->index}}][name]" placeholder="مثال: کیفیت ساخت" required="" value="{{$AbilityScore->name}}">
                                                                                </div>

                                                                                <div class="col-md-4 form-group">
                                                                                    <p class="spec-label">امتیاز</p>
                                                                                    <input type="text" class="form-control spec-label" name="AbilityProductScore[{{$loop->index}}][value]" max="5" min="0" placeholder="مثال: 4.5" required="" value="{{$AbilityScore->value}}">
                                                                                </div>
                                                                                <div class="col-md-1">
                                                                                    <button type="button" class="btn btn-flat-danger waves-effect waves-light remove-specification custom-padding"><i class="feather icon-minus"></i></button>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        @endforeach
                                                                    </div>

                                                                    <div class="row">
                                                                        <div class="col-12 text-center mt-4">
                                                                            <button id="AbilityProductScore" type="button" class="btn btn-outline-primary waves-effect waves-light"><i class="feather icon-plus"></i> افزودن ویژگی جدید</button>
                                                                        </div>
                                                                    </div>

                                                                </div>
                                                            </div>
                                                        </div>

                                                    </div>
                                                </div>

                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row mt-3 mb-3">
                                    <div class="col-12 text-center">
                                        <button type="submit" class="c-mega-campaigns__btns-green-plus uk-margin-remove border-none m-auto">ویرایش محصول
                                        </button>
                                    </div>
                                </div>
                            </div>



                        </div>

                    </div>



                    <input name="rounding_amount" type="hidden" value="no">
                    <input name="rounding_type" type="hidden" value="default">
                </form>
                <div id="form-progress" class="progress progress-bar-success progress-xl" style="display: none;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width:0%">0%</div>
                </div>
            </div>

    </div>

    @include('sellers-old.panel.products.partials.specification-template')
    @include('sellers-old.panel.products.partials.AbilityProductScore-template')
    @include('sellers-old.panel.products.partials.prices-template')
    @include('sellers-old.panel.products.partials.files-template')
@endsection

@include('back.partials.plugins', ['plugins' => ['ckeditor', 'jquery-tagsinput', 'jquery.validate', 'jquery-ui', 'jquery-ui-sortable', 'dropzone', 'persian-datepicker']])



@push('scripts')
    <script>
        /* load saved image gallery */
        var mockImages = [];
        @foreach($product->gallery()->orderBy('ordering')->get() as $image)
            mockImages.push({
                name: '{{ $image->image}}',
                galleryImage: true,
                type: 'image/jpeg',
                status: 'success',
                upload: {
                    filename: '{{ $image->image }}',
                },
                prevFile: true,
                accepted: true,
                image: '{{ $image->image }}',
            });
        @endforeach

        var product = {{ $product->id }};

        var groupCount = {{ $product->specificationGroups->unique()->count() }};
        var AbilitygroupCount = {{ $AbilityScores->count() }};
        var specificationCount = {{ $product->specifications->unique()->count() }};

        var availableTypes = [
            @foreach($specTypes as $spec_type)
                '{{ $spec_type->name }}',
            @endforeach
        ];

        var specifications_type_first_change = true;

        var priceCount = {{ $product->prices()->count() }};
        var filesCount = {{ $product->files()->count() }};
        var sizesCount = {{ $product->sizes()->count() }};

    </script>

    <script src="{{ theme_asset('js/pages/sellers/products/all.js') }}?v=7"></script>
    <script src="{{ theme_asset('js/pages/sellers/products/edit.js') }}?v=3"></script>
@endpush
