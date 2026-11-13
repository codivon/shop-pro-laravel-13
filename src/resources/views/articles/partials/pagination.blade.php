@if($posts->hasPages())
    <div class="pagination-wrapper d-flex justify-content-center mt-4">
        {{ $posts->appends(request()->query())->links('front::components.paginate') }}
    </div>
@endif
