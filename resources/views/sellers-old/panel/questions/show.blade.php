<div class="table-responsive">
    <form id="comment-edit-form" action="{{ route('seller.questions.update', ['question' => $question]) }}">
        @method('put')
        @csrf
        <table class="table">
            <tbody>

                <tr>
                    <th scope="row">نام</th>
                    <td>
                        @if($question->user)
                            [کاربر]{{ @$question->user ? @$question->user->fullname : @$question->name }}
                        @elseif($question->admin)
                            [مدیر سایت]
                        @elseif($question->seller)  [فروشنده]
                        {{ @$question->seller ? @$question->seller->business_name : '' }}
                        @endif

                    </td>

                </tr>
                <tr>
                    <th scope="row">در </th>
                    <td>{{ $question->commentable->title }} <a class="float-right" href="{{ $question->commentable->link() }}" target="_blank"><i class="feather icon-external-link"></i></a></td>

                </tr>

                <tr>
                    <th scope="row" style="min-width: 100px;">متن دیدگاه</th>
                    <td>
                        <div id="comment-body">
                            {{ $question->body }}

                            @if($question->comment_id!=null and $question->seller_id==sellerID())
                            <div class="mt-1">
                                <button id="edit-comment-btn" type="button" class="btn btn-flat-primary waves-effect waves-light"><i class="feather icon-edit"></i> ویرایش</button>
                            </div>
                                @endif
                        </div>


                        <fieldset id="edit-comment-body" class="form-group" style="display: none;">
                            <textarea name="body" class="form-control" rows="4" required>{{ $question->body }}</textarea>
                        </fieldset>
                    </td>

                </tr>

                @if (!$question->comment_id)
                    <tr>
                        <th scope="row">تعداد پاسخ ها</th>
                        <td>{{ $question->comments->count() }}</td>

                    </tr>

                    <tr>
                        <th scope="row">پاسخ</th>
                        <td>
                            <fieldset class="form-group">
                                <textarea name="replay" class="form-control" rows="4"></textarea>
                            </fieldset>
                        </td>

                    </tr>

                @endif

                <tr>
                    <th scope="row">تاریخ ارسال</th>
                    <td>{{ jdate($question->created_at) }}  ( {{ jdate($question->created_at)->ago() }} )</td>
                </tr>


            </tbody>
        </table>

    </form>
</div>
<div class="modal-footer" style="padding: 12px 0 0;">
    @if($question->seller_id==sellerID())
        <button id="comment-form-submit-btn" type="button" class="btn btn-outline-success">ذخیره</button>
    @endif

    <button type="button" class="btn btn-outline-primary" data-dismiss="modal">بستن</button>
</div>
