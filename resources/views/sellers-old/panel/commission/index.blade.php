@extends('sellers-old.panel.layouts.master')

@section('content')
    <div class="c-content-page c-content-page--plain c-grid__row w-100 mb-2">
        <div class="c-grid__col">
            <div class="c-content-page__header">
                <span class="c-content-page__header-action">اطلاعات گروه کالایی و هزینه ها </span>
                <span class="c-content-page__header-desc">اطلاعات هر گروه از محصولات و هزینه های مربوط به پردازش و ارسال را در این قسمت مشاهده کنید.</span>
            </div>
        </div>
    </div>
    @include('sellers-old.panel.partials.sidebar')

    <div class="col-lg-9 col-md-8 col-xs-12 pull-right pr-0">

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="c-card__header">
                        <h2 class="c-card__title">   📊 جدول کمیسیون دسته‌بندی‌ها </h2>
                    </div>
                    <div class="card-body">
                        <!-- خلاصه اطلاعات -->
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <div class="info-box bg-info">
                                    <span class="info-box-icon"><i class="fas fa-percent"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">حداکثر کمیسیون</span>
                                        <span class="info-box-number">{{ $categoriesWithCommission->max('commission') ?? 0 }}%</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="info-box bg-success">
                                    <span class="info-box-icon"><i class="fas fa-chart-line"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">میانگین کمیسیون</span>
                                        <span class="info-box-number">{{ round($categoriesWithCommission->avg('commission') ?? 0) }}%</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="info-box bg-warning">
                                    <span class="info-box-icon"><i class="fas fa-tag"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">تعداد دسته‌بندی‌ها</span>
                                        <span class="info-box-number">{{ $categoriesWithCommission->count() }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- جدول اصلی -->
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover" id="commissionTable">
                                <thead class="thead-light">
                                <tr>
                                    <th style="width: 5%">#</th>
                                    <th style="width: 40%">نام دسته‌بندی</th>
                                    <th style="width: 20%">درصد کمیسیون</th>
                                    <th style="width: 20%">نوع کمیسیون</th>
                                    <th style="width: 15%">وضعیت</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($categoriesWithCommission as $index => $category)
                                    @include('sellers-old.panel.commission.category-row', ['category' => $category, 'level' => 0, 'index' => $index + 1])
                                @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- توضیحات پایین جدول -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="alert alert-info" role="alert">
                                    <h5><i class="icon fas fa-info-circle"></i> راهنمای علائم:</h5>
                                    <ul class="mb-0">
                                        <li><span class="badge badge-success">●</span> کمیسیون مستقیم - مقدار تعیین شده برای این دسته</li>
                                        <li><span class="badge badge-warning">●</span> کمیسیون ارثی - از دسته والد به ارث برده شده</li>
                                        <li><span class="badge badge-secondary">●</span> پیش‌فرض صفر - هیچ کمیسیونی تعیین نشده</li>
                                        <li><span class="badge badge-danger">●</span> صفر عمدی - مدیر این دسته را بدون کمیسیون قرار داده</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .info-box {
            display: flex;
            align-items: center;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            color: white;
        }

        .info-box:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }

        .info-box-icon {
            font-size: 2.5rem;
            margin-left: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 60px;
            height: 60px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
        }

        .info-box-content {
            flex: 1;
        }

        .info-box-text {
            font-size: 14px;
            opacity: 0.9;
            margin-bottom: 5px;
        }

        .info-box-number {
            font-size: 28px;
            font-weight: bold;
        }

        .bg-info {
            background: linear-gradient(135deg, #17a2b8, #138496);
        }

        .bg-success {
            background: linear-gradient(135deg, #28a745, #1e7e34);
        }

        .bg-warning {
            background: linear-gradient(135deg, #ffc107, #e0a800);
        }

        /* برای حالت دارک و واکنشگرا */
        @media (max-width: 768px) {
            .info-box-icon {
                width: 45px;
                height: 45px;
                font-size: 1.8rem;
            }
            .info-box-number {
                font-size: 22px;
            }
            .info-box-text {
                font-size: 12px;
            }
        }
        .tree-level-0 {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .tree-level-1 {
            background-color: #ffffff;
            padding-right: 30px !important;
        }
        .tree-level-2 {
            background-color: #f8f9fa;
            padding-right: 60px !important;
        }
        .tree-level-3 {
            background-color: #ffffff;
            padding-right: 90px !important;
        }
        .commission-badge {
            font-size: 14px;
            padding: 5px 10px;
            border-radius: 20px;
        }
        .inherit-icon {
            font-size: 12px;
            margin-right: 5px;
            cursor: help;
        }
        .category-row:hover {
            background-color: #e8f4f8 !important;
            transition: all 0.3s;
        }
        .info-box {
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        #commissionTable {
            font-size: 14px;
        }
        #commissionTable tbody tr {
            cursor: pointer;
        }
    </style>
@endpush

@push('scripts')
    <script>
        $(document).ready(function() {
            // جستجوی زنده
            $('#searchInput').on('keyup', function() {
                var value = $(this).val().toLowerCase();
                $('#commissionTable tbody tr').filter(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
                });
            });

            // tooltip
            $('[data-toggle="tooltip"]').tooltip();

            // کلیک روی ردیف
            $('.category-row').click(function() {
                var categoryId = $(this).data('id');
                // میتونید لینک به صفحه دسته‌بندی اضافه کنید
                // window.location.href = '/seller/categories/' + categoryId;
            });
        });
    </script>
@endpush

