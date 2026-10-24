{{-- مودال راهنمای سایزبندی سانیار — $product --}}
@if ($product->sizeType)
    <div class="modal fade" id="sizeModal" tabindex="-1" aria-labelledby="sizeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-md modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content rounded-4 overflow-hidden">
                <div class="modal-header">
                    <h5 class="modal-title font-16" id="sizeModalLabel">
                        <i class="bi bi-rulers main-color-one-color me-1"></i>راهنمای سایزبندی
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
                </div>
                <div class="modal-body">
                    <div class="col-mb-12 text-muted font-14 mb-3">{!! $product->sizeType->description !!}</div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle text-center font-13">
                            <thead class="table-light">
                                <tr>
                                    @foreach ($product->sizeType->sizes as $size)
                                        <th scope="col">{{ $size->title }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($product->sizes->groupBy('pivot.group') as $sizes)
                                    <tr>
                                        @foreach ($sizes->sortBy('pivot.ordering') as $size)
                                            <td>{{ $size->pivot->value }}</td>
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
@endif
