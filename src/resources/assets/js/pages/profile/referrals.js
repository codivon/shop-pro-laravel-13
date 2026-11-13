function showReferralToast(message, type = 'success') {
    let toast = document.getElementById('referralToast');
    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'referralToast';
        toast.className = 'referral-toast';
        document.body.appendChild(toast);
    }
    const icon = type === 'success' ? 'icon-check-circle' : 'icon-alert-circle';
    toast.innerHTML = '<i class="feather ' + icon + '"></i> ' + message;
    toast.className = 'referral-toast ' + type + ' show';
    clearTimeout(toast._timer);
    toast._timer = setTimeout(() => {
        toast.className = 'referral-toast ' + type;
    }, 2500);
}

function flashButton(btn) {
    if (!btn) return;
    const original = btn.dataset.originalText || btn.innerHTML;
    btn.innerHTML = '<i class="feather icon-check"></i> کپی شد';
    btn.classList.add('btn-copied');
    setTimeout(() => {
        btn.innerHTML = original;
        btn.classList.remove('btn-copied');
    }, 1800);
}

function copyReferralCode(btn){
    const text = document.getElementById('referralCodeText').innerText;
    navigator.clipboard.writeText(text).then(function(){
        showReferralToast('کد معرف کپی شد');
        flashButton(btn);
    }).catch(function(){
        showReferralToast('خطا در کپی کردن', 'error');
    });
}

function copyReferralLink(btn){
    const input = document.getElementById('referralLinkInput');
    input.select();
    input.setSelectionRange(0, 99999);
    navigator.clipboard.writeText(input.value).then(function(){
        showReferralToast('لینک دعوت کپی شد');
        flashButton(btn);
    }).catch(function(){
        showReferralToast('خطا در کپی کردن', 'error');
    });
}
function copyDiscountCode(btn){
    const text = $(btn).text();
    navigator.clipboard.writeText(text).then(function(){
        showReferralToast('کد معرف کپی شد');
        flashButton(btn);
    }).catch(function(){
        showReferralToast('خطا در کپی کردن', 'error');
    });
}
function shareTelegram(e){
    e.preventDefault();
    const url = 'https://t.me/share/url?url=' + encodeURIComponent(referralLink) + '&text=' + encodeURIComponent('با این لینک ثبت‌نام کن و تخفیف بگیر!');
    window.open(url, '_blank');
}
function shareWhatsapp(e){
    e.preventDefault();
    const url = 'https://wa.me/?text=' + encodeURIComponent('با این لینک ثبت‌نام کن و تخفیف بگیر! ' + referralLink);
    window.open(url, '_blank');
}
function shareTwitter(e){
    e.preventDefault();
    const url = 'https://twitter.com/intent/tweet?text=' + encodeURIComponent('با این لینک ثبت‌نام کن و تخفیف بگیر!') + '&url=' + encodeURIComponent(referralLink);
    window.open(url, '_blank');
}
function shareEmail(e){
    e.preventDefault();
    const url = 'mailto:?subject=' + encodeURIComponent('دعوت به عضویت') + '&body=' + encodeURIComponent('با این لینک ثبت‌نام کن و تخفیف بگیر! ' + referralLink);
    window.location.href = url;
}
