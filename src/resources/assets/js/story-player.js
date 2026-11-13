/**
 * SaniarTheme — پخش‌کننده ویدیوی استوری‌ها (Plyr)
 * استخراج‌شده از widgets/main-story.blade.php
 */
document.addEventListener('DOMContentLoaded', function () {
    if (typeof Plyr === 'undefined') return;
    new Plyr('.story-video-player', {
        controls: ['current-time'],
        settings: ['captions', 'quality', 'speed']
    });
});
