@if ($product->sizeType)
    <div class="modal fade text-left" id="size-modal" tabindex="-1" role="dialog"  aria-hidden="true">
        <div class="modal-dialog modal-md" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">راهنمای سایز بندی</h4>

                    <h5 class="modal-title"></h5>

                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>

                <div class="modal-body text-right">

                    <div class="col-mb-12">{!! $product->sizeType->description !!}</div>
                    <div class="col-mb-6">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        @foreach ($product->sizeType->sizes as $size)
                                            <th scope="col">{{ $size->title }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($product->sizes->groupBy('pivot.group') as $sizes)
                                        <tr>
                                            @foreach ($product->sizetype->sizes as $size)

                                                @php
                                                    $group = $sizes->first()->pivot->group;
                                                    $value = $product->sizes()->where('size_id', $size->id)->where('group', $group)->first()->pivot->value ?? '';
                                                @endphp

                                                <td>{{ $value }}</td>
                                            @endforeach
                                        </tr>
                                    @endforeach

                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
