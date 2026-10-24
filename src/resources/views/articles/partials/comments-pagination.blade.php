@if($comments->hasPages())
    <div class="d-flex justify-content-center mt-3" data-saniar-comments-pagination>
        {{ $comments->appends(request()->query())->links('front::components.paginate') }}
    </div>
@endif
