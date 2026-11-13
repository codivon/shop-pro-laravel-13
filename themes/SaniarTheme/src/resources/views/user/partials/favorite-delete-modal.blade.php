{{-- مودال حذف از لیست علاقمندی‌ها — مورد استفاده در profile و favorites --}}
<div class="modal fade" id="favorite-delete-modal" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-md" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-geo-alt"></i>
                    حذف از لیست علاقمندی ها
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-12">
                        <p>آیا تمایل به حذف این محصول از لیست علاقمندی ها دارید؟</p>
                        <form id="favorite-remove-form" action="#" method="POST">
                            <div class="text-center">
                                @csrf
                                @method('delete')
                                <button type="submit" class="btn btn-danger btn-md">بله حذف شود</button>
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">لغو</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
