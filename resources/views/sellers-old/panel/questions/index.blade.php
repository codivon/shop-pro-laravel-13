@extends('sellers-old.panel.layouts.master')

@section('content')
    <div class="c-content-page c-content-page--plain c-grid__row w-100 mb-2">
        <div class="c-grid__col">
            <div class="c-content-page__header">
                <span class="c-content-page__header-action">پرسش‌ها</span>
                <span class="c-content-page__header-desc">اینجا می‌توانید پرسش‌های کاربران را ببینید و به آنها پاسخ دهید</span>
            </div>
        </div>
    </div>
    @include('sellers-old.panel.partials.sidebar')

    <div class="col-lg-9 col-md-8 col-xs-12 pull-right pr-0">
        <div class="row dashboard-steps-3">
            <div class="col-12 dashboard-steps-3-item ">
                <div class="c-card">
                    <div class="c-card__header d-flex pt-1 pb-1">
                        <h2 class="c-card__title line-height-40">لیست پرسش‌ها</h2>
                        <div class="line-height-40 w-15"><span class="pl-1 color-text-low-emphasis text-body-1">تعداد نتایج:</span><span class="color-text-high-emphasis text-body1-strong">{{count($questions_count)}}</span></div>
                    </div>
                    <div class="card-content" id="main-card">
                       <div class="card-body">
                           <form id="filter-questions-form">
                                <div class="row">


                               <div class="col-md-3">
                                   <label>وضعیت پرسش</label>
                                   <fieldset class="form-group">
                                       <select class="form-control datatable-filter" name="status">
                                           <option value="all" {{ request('status') == 'all' ? 'selected' : '' }}>
                                               همه
                                           </option>
                                           <option value="noanswer" {{ request('status') == 'noanswer' ? 'selected' : '' }}>
                                               پاسخ داده ‌نشده
                                           </option>
                                           <option value="unconfirmed" {{ request('status') == 'unconfirmed' ? 'selected' : '' }}>
                                               پاسخ رد ‌شده
                                           </option>
                                           <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>
                                               پاسخ در حال بررسی
                                           </option>
                                           <option value="answer_accepted" {{ request('status') == 'answer_accepted' ? 'selected' : '' }}>
                                               پاسخ تایید‌ شده
                                           </option>
                                       </select>
                                   </fieldset>
                               </div>

                               <div class="col-md-3">
                                   <label>مرتب سازی</label>
                                   <fieldset class="form-group">
                                       <select class="form-control datatable-filter" name="ordering">
                                           <option value="latest" {{ request('ordering') == 'latest' ? 'selected' : '' }}>
                                               جدیدترین
                                           </option>
                                           <option value="oldest" {{ request('ordering') == 'oldest' ? 'selected' : '' }}>
                                               قدیمی ترین
                                           </option>
                                       </select>
                                   </fieldset>
                               </div>


                           </div>
                           </form>

                           <div class="card-content list-comments" id="main-card">
                               @if(count($questions))
                                   <div class="card-body">
                                       <div class="table-responsive">
                                           <table class="table table-striped mb-0">
                                               <thead>
                                               <tr>
                                                   <th class="text-center">#</th>
                                                   <th>نام</th>
                                                   <th>دیدگاه</th>
                                                   <th class="text-center">نوع</th>
                                                   <th class="text-center">وضعیت</th>
                                                   <th class="text-center">عملیات</th>
                                               </tr>
                                               </thead>
                                               <tbody>
                                               @foreach ($questions as $question)
                                                   <tr id="comment-{{ $question->id }}-tr">
                                                       <td class="text-center">
                                                           {{ $question->id }}
                                                       </td>

                                                       <td>@if($question->user)
                                                               [کاربر]{{ @$question->user ? @$question->user->fullname : @$question->name }}
                                                           @elseif($question->admin)
                                                               [مدیر سایت]
                                                           @elseif($question->seller)  [فروشنده]
                                                               {{ @$question->seller ? @$question->seller->business_name : '' }}
                                                           @endif
                                                       </td>
                                                       <td style="max-width: 300px">{{ short_content($question->body, 20, false) }}</td>
                                                       <td style="max-width: 300px">@if($question->comment_id)<div class="badge badge-success">پاسخ</div>@else <div class="badge badge-warning">پرسش‌</div> @endif</td>
                                                       <td class="text-center">
                                                           @if($question->status == 'pending')
                                                               <div class="badge badge-warning">منتظر تایید</div>
                                                           @elseif($question->status == 'noanswer')
                                                               <div class="badge badge-warning">در انتظار پاسخ</div>
                                                           @elseif($question->status == 'accepted')
                                                               <div class="badge badge-success">تایید شده</div>
                                                           @else
                                                               <div class="badge badge-danger">تایید نشده</div>
                                                           @endif
                                                       </td>

                                                       <td class="text-center">
                                                           @if($question->status!="accepted")
                                                               @if($question->comment_id!=null or $question->status=="noanswer")
                                                                   <button type="button" data-action="{{ route('seller.questions.show',['question'=>$question]) }}" class="btn btn-success mr-1 waves-effect waves-light show-comment">مشاهده</button>
                                                               @else
                                                                   <div class="badge badge-warning">منتظر تایید</div>
                                                               @endif
                                                           @else
                                                               <button type="button" data-action="{{ route('seller.questions.show',['question'=>$question]) }}" class="btn btn-success mr-1 waves-effect waves-light show-comment">مشاهده</button>
                                                           @endif

                                                       </td>
                                                   </tr>
                                               @endforeach

                                               </tbody>
                                           </table>
                                       </div>
                                   </div>
                               @else
                                   <div class="card-content">
                                       <div class="card-body">
                                           <div class="card-text">
                                               <p>چیزی برای نمایش وجود ندارد!</p>
                                           </div>
                                       </div>
                                   </div>
                               @endif


                           </div>
                           {{ $questions->links() }}
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>


@endsection
@push('scripts')
    <!-- Modal -->
    <div class="modal fade" id="show-modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLongTitle" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">مشاهده جزئیات</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div id="comment-detail" class="modal-body">


                </div>

            </div>
        </div>
    </div>

    <script src="{{ theme_asset('js/pages/sellers/questions/index.js') }}?v=4"></script>
@endpush
