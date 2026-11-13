
<div class="chat {{ !$own ? 'chat-left' : '' }}">
    <div class="chat-avatar">
        @if($message->admin_id)
            <a class="avatar m-0" data-toggle="tooltip" href="javascript:void(0)" data-placement="right" title="" data-original-title="">
                <img src="{{ $message->admin->imageUrl }}" alt="avatar" height="40" width="40" title="{{ $message->admin->fullname }}" />
            </a>
        @else
            <a class="avatar m-0" data-toggle="tooltip" href="javascript:void(0)" data-placement="right" title="" data-original-title="">
                <img src="{{ $message->user->imageUrl }}" alt="avatar" height="40" width="40" title="{{ $message->user->fullname }}" />
            </a>
        @endif

    </div>
    <div class="chat-body">
        <div class="chat-content">
            <p>{{ $message->message }}</p>
            <p class=" pt-1 font-size-11 {{ !$own ? 'text-right' : 'text-left' }}">{{ jdate($message->created_at)->ago() }}</p>
        </div>

        <div class="message-files">
            @foreach ($message->files as $file)
                <a target="_blank" title="{{ asset($file->file) }}" href="{{ asset($file->file) }}">فایل {{ $loop->iteration }}</a>
            @endforeach
        </div>
    </div>
</div>
