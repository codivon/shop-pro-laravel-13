<div class="widget widget-product  blog-article-image-box-container">
    <div class="row d-flex">
        @foreach($posts as $post)
            <div class="col-lg-3 col-md-4 col-sm-6 col-12 mb-3">
                <div class="card article-image shadow-1">
                    <img class="article-image--center-crop"
                         src="{{ $post->image ? asset($post->image) : asset('/no-image-product.svg') }}"
                         alt="{{ $post->title }}" loading="lazy">


                    @if($post->post_type=="video")
                        <div class="post-grid-thumbnail-video-type minimal">
                            <i class=" fas fa-play" style="color:red"></i>
                        </div>
                    @elseif($post->post_type=="podcast")
                        <div class="post-grid-thumbnail-video-type minimal">
                            <i class=" fa fa-headphones" style="color:red"></i>
                        </div>
                    @endif

                    <a class="article-link" href="{{ route('front.articles.show', $post->slug) }}"></a>

                    <div class="article--footer">
                        <h3 class="article-title mb-2">
                            <a class="link" href="{{ route('front.articles.show', $post->slug) }}">
                                {{ $post->title }}
                            </a>
                        </h3>
                        <ul class="article-meta">
                            <li>
                                <img src="{{$post->admin->imageUrl}}"
                                     alt="{{ $post->admin->full_name ?? 'ناشناس' }}"
                                     class="article--meta-avatar shadow-1 me-2" height="32" width="32" loading="lazy">
                                <a class="article--meta-username lts-05" href="{{ route('front.articles.index', ['profile' => $post->admin->username]) }}">
                                    {{ $post->admin->full_name ?? 'ناشناس' }}
                                </a>
                            </li>
                            <li class="divider"></li>
                            <li><span class="date">{{ jdate($post->created_at)->format('d F Y') }}</span></li>
                            <li class="divider"></li>
                            <li><span class="date">{{ number_format($post->view) }} <i class="far fa-eye"></i></span></li>
                        </ul>
                    </div>

                    @if($post->categories->isNotEmpty())
                        <span class="category-badge">
                            <a class="link text-white" href="?cat={{ $post->categories->first()->slug }}">
                                {{ $post->categories->first()->title }}
                            </a>
                        </span>
                    @endif

                    @if($post->is_editor_pick)
                        <span class="editor-choice-badge badge bg-secondary shadow-secondary"
                              data-bs-toggle="tooltip" data-bs-placement="top"
                              aria-label="انتخاب سردبیر" data-bs-original-title="انتخاب سردبیر">
                            <i class="fas fa-crown"></i>
                        </span>
                    @endif
                </div>
            </div>

        @endforeach
    </div>

    @if($posts->isEmpty())
        <div class="text-center py-5">
            <p>مقاله‌ای برای نمایش وجود ندارد.</p>
        </div>
    @endif
</div>
