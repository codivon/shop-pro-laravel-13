@if($posts->hasPages())
    <div class="d-flex justify-content-center mt-4" data-saniar-articles-pagination>
        {{ $posts->appends(request()->query())->links('front::components.paginate') }}
    </div>
@endif
