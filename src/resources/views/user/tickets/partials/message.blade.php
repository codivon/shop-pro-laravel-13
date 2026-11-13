
{{-- پیام تیکت — کامپوننت گفتگوی قالب سانیار (ticket-message.html) --}}
<div class="comment shadow-none {{ $own ? '' : 'active' }}">
    <div class="comment-author-ava">
        @if($message->admin_id)
            <img src="{{ $message->admin->imageUrl }}" alt="avatar" title="{{ $message->admin->fullname }}" />
        @else
            <img src="{{ $message->user->imageUrl }}" alt="avatar" title="{{ $message->user->fullname }}" />
        @endif
    </div>
    <div class="comment-body">
        <p class="comment-text">{{ $message->message }}</p>

        <div class="message-files">
            @foreach ($message->files as $file)
                <a target="_blank" title="{{ asset($file->file) }}" href="{{ asset($file->file) }}">فایل {{ $loop->iteration }}</a>
            @endforeach
        </div>

        <div class="comment-footer">
            <span class="comment-meta">
                @if($message->admin_id)
                    {{ $message->admin->fullname }}
                @else
                    {{ $message->user->fullname }}
                @endif
            </span>
            <div class="comment-date float-end"><span class="comment-meta">{{ jdate($message->created_at)->ago() }}</span></div>
        </div>
    </div>
</div>
