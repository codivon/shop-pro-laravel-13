{{-- حالت خالی سانیار — $message اختیاری --}}
<div class="content-box my-3">
    <div class="container-fluid py-5 text-center">
        <i class="bi bi-inbox text-muted" style="font-size: 3.5rem;" aria-hidden="true"></i>
        <h5 class="font-18 mt-3 mb-2">{{ $message ?? 'موردی برای نمایش وجود ندارد' }}</h5>
        <p class="text-muted font-14 mb-4">می‌توانید از طریق لینک‌های زیر ادامه دهید</p>
        <div class="d-flex align-items-center justify-content-center flex-wrap gap-2">
            <a href="{{ route('front.index') }}" class="btn border-0 main-color-one-bg text-white rounded-3 px-4">
                <i class="bi bi-house-door me-1"></i> صفحه اصلی
            </a>
            <a href="{{ route('front.products.index') }}" class="btn-outline-site">
                <i class="bi bi-grid me-1"></i> همه محصولات
            </a>
        </div>
    </div>
</div>
