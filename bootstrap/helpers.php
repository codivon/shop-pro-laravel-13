<?php

// helper functions

use App\Helpers\ShortcodeHelper;
use App\Models\Admin;
use App\Models\Brand;
use App\Models\Carrier;
use App\Models\Cart;
use App\Models\Category;
use App\Models\Currency;
use App\Models\FieldValue;
use App\Models\Fild;
use App\Models\Gateway;
use App\Models\Option;
use App\Models\Order;
use App\Models\Post;
use App\Models\Product;
use App\Models\Seller;
use App\Models\SellerInfo;
use App\Models\Slider;
use App\Models\Specification;
use App\Models\SpecificationGroup;
use App\Models\SpecType;
use App\Models\Story;
use App\Models\Tag;
use App\Models\Ticket;
use App\Models\User;
use App\Models\UserOption;
use App\Models\Viewer;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Milon\Barcode\DNS1D;
use Spatie\ImageOptimizer\OptimizerChainFactory;

/* add active class to li */

function update_url()
{
    /*dd(env('APP_ENV'));
    if (env('APP_ENV') === 'local') {
        return '';
    }*/

    $url = 'https://update.weblak.ir/api/v1/check-update?token='.env('SELF_UPDATER_HTTP_PRIVATE_ACCESS_TOKEN', config('self-update.updater_token'));
    $headers_url = @get_headers($url);
    if ($headers_url && strpos($headers_url[0], '200')) {
        /* return $url . '/'; */
        return $url;
    }

    return '';
}
function Admin()
{
    return Auth::guard('adminPanel')->user();
}

function active_class($route_name, $class = 'active')
{
    return Route::is($route_name) || Route::is(app()->getLocale().'.'.$route_name) ? $class : '';
}

function open_class($route_list, $class = 'open')
{
    $text = '';

    foreach ($route_list as $route) {
        if (Route::is($route) || Route::is(app()->getLocale().'.'.$route)) {
            $text = $class;
            break;
        }
    }

    return $text;
}

function option_update($option_name, $option_value)
{
    $option = Option::firstOrNew([
        'option_name' => $option_name,
        'lang' => app()->getLocale(),
    ]);

    $option->option_value = $option_value;
    $option->save();

    Cache::forever('options.'.app()->getLocale().'.'.$option_name, $option_value);
}

function option($option_name, $default_value = '')
{
    /*
     * فاز ۵۱ — پیش از نصب، هیچ گزینه‌ای از دیتابیس خوانده نمی‌شود.
     * روی کلون خام (بدون نشان storage/installed و با .envِ خودکارِ ساخته‌شده از
     * .env.example که اعتبارنامه‌ی دیتابیسش خالی است)، هر فراخوانی option()
     * که به دیتابیس برسد (کش گزینه‌ها هم سرد است) با «Access denied» ۵۰۰ می‌دهد؛
     * نمونه‌ی واقعی: SecurityHeaders → SecuritySettings::headersEnabled() →
     * option('security_headers_enabled') روی صفحات /install خودِ نصب‌کننده.
     * چون option() در میدلورها/کامپوزرها/سرویس‌های مختلف صدا زده می‌شود، گارد
     * را در همین نقطه‌ی مرکزی می‌گذاریم تا هر مصرف‌کننده‌ای ایمن باشد؛ مقدار
     * پیش‌فرض آرگومان برمی‌گردد و بعد از نصب رفتار عادی است.
     */
    if (! application_installed()) {
        return $default_value;
    }

    $non_language_options = config('general.non_language_options');

    if (in_array($option_name, $non_language_options)) {
        $language = 'fa';
    } else {
        $language = app()->getLocale();
    }

    $prefix = 'options.'.$language.'.';

    // بهینه‌سازی کارایی: همه گزینه‌های این زبان با «یک» کوئری بارگذاری و کش
    // می‌شوند (فلگ __loaded__). قبلاً هر option() یک کوئری جدا اجرا می‌کرد
    // و صفحات پنل/فروشگاه ده‌ها کوئری تکراری options داشتند.
    if (! Cache::get($prefix.'__loaded__')) {
        foreach (Option::where('lang', $language)->get() as $opt) {
            Cache::forever($prefix.$opt->option_name, is_null($opt->option_value) ? false : $opt->option_value);
        }
        Cache::forever($prefix.'__loaded__', true);
    }

    $value = Cache::get($prefix.$option_name, '__option_missing__');

    if ($value === '__option_missing__' || is_null($value) || $value === false || $value == '') {
        return $default_value;
    }

    return $value;
}

function user_option_update($option_name, $option_value, $user_id = null)
{
    if (! $user_id) {
        $user_id = auth('adminPanel')->user()->id;
    }

    $option = UserOption::firstOrNew([
        'option_name' => $option_name,
        'admin_id' => $user_id,
    ]);

    $option->option_value = $option_value;
    $option->save();
}

function user_option($option_name, $default_value = '', $user_id = null)
{
    if (! $user_id) {
        if (! auth('adminPanel')->check()) {
            return $default_value;
        }

        $user_id = auth('adminPanel')->user()->id;
    }

    $option = UserOption::where('option_name', $option_name)->where('admin_id', $user_id)->first();

    return $option ? $option->option_value : $default_value;
}

// add new tags and return tags id
function addTags($tags, $separator = ',')
{
    $tags = explode($separator, $tags);
    $tags_id = [];

    foreach ($tags as $item) {

        if (! $item) {
            continue;
        }

        try {
            $tag = Tag::firstOrCreate([
                'name' => $item,
                'lang' => app()->getLocale(),
            ]);
        } catch (Exception $e) {
            Log::error($e->getMessage());
        }

        if (isset($tag) && $tag) {
            $tags_id[] = $tag->id;
        }
    }

    return $tags_id;
}

function get_cart()
{
    $cart = null;

    if (auth()->check()) {
        $cart = auth()->user()->cart;
    } else {
        $cart_id = Cookie::get('cart_id');

        if ($cart_id) {
            $cart = Cart::whereNull('user_id')->find($cart_id);
        }
    }

    return $cart;
}

/* return true if cart products quantity is ok
 * and return false if cart products quantity is more than product stock
 */
function check_cart_quantity()
{
    $cart = get_cart();

    if (! $cart || ! $cart->products()->count()) {
        return true;
    }

    foreach ($cart->products as $product) {
        $price = $product->prices()->find($product->pivot->price_id);

        // فاز ۷۸/۸۱ — پیش‌فروش: بدون کنترل موجودی (فقط سقف یک عدد)
        if ($product->isPreOrder()) {
            if ($product->pivot->quantity > 1) {
                return false;
            }

            continue;
        }

        $has_stock = $price->hasStock($product->pivot->quantity);
        if (! $has_stock['status']) {
            return false;
        }
    }

    return true;
}

function check_cart_discount()
{
    $cart = get_cart();

    if (! $cart || ! $cart->products()->count()) {
        return ['status' => true];
    }

    if ($cart->discount) {
        $status = $cart->canUseDiscount();

        return $status;
    }

    return ['status' => true];
}

function check_cart()
{
    return check_cart_quantity() && check_cart_discount()['status'];
}

// get user address
function user_address($key)
{
    if (old($key)) {
        return old($key);
    }

    return auth()->user()->address ? auth()->user()->address->$key : '';
}

function short_content($str, $words = 20, $strip_tags = true)
{
    if ($strip_tags) {
        $str = strip_tags($str);
    }

    return Str::words($str, $words);
}

function spec_type($request)
{
    if (! $request->spec_type || ! $request->specification_group) {
        return null;
    }

    $spec_type = SpecType::firstOrCreate([
        'name' => $request->spec_type,
        'lang' => app()->getLocale(),
    ]);

    $group_ordering = 0;

    foreach ($request->specification_group as $group) {

        if (! isset($group['specifications'])) {
            continue;
        }

        $spec_group = SpecificationGroup::firstOrCreate([
            'name' => $group['name'],
            'lang' => app()->getLocale(),
        ]);

        $specification_ordering = 0;

        foreach ($group['specifications'] as $specification) {
            $spec = Specification::firstOrCreate([
                'name' => $specification['name'],
            ]);

            if (! $spec_type->specifications()->where('specification_id', $spec->id)->where('specification_group_id', $spec_group->id)->first()) {
                $spec_type->specifications()->attach([
                    $spec->id => [
                        'specification_group_id' => $spec_group->id,
                        'group_ordering' => $group_ordering,
                        'specification_ordering' => $specification_ordering++,
                    ],
                ]);
            }
        }

        $group_ordering++;
    }

    return $spec_type->id;
}

function viewers_data($number = 7)
{
    $data = [];

    for ($i = 0; $i < $number; $i++) {
        $date = Carbon::now()->subDays($i);

        if ($date->isToday() && Cache::get('admin.views-count-'.$date->format('Y-m-d')) <= 1) {
            Cache::forget('admin.views-count-'.$date->format('Y-m-d'));
        }

        $views = Cache::rememberForever('admin.views-count-'.$date->format('Y-m-d'), function () use ($date) {
            return Viewer::whereDate('created_at', $date)->count();
        });

        $data[jdate($date)->format('l')] = $views;
    }

    return $data;
}

function ip_data($number = 7)
{
    $data = [];

    for ($i = 0; $i < $number; $i++) {
        $date = Carbon::now()->subDays($i);

        $views = Cache::remember('admin.views-ip-'.$date->format('Y-m-d'), now()->addMinutes(10), function () use ($date) {
            return Viewer::whereDate('created_at', $date)->distinct('ip')->count();
        });

        $data[jdate($date)->format('l')] = $views;
    }

    return $data;
}

function array_to_string($array)
{
    $comma_separated = implode("','", $array);
    $comma_separated = "'".$comma_separated."'";

    return $comma_separated;
}

function get_discount_price($price, $discount, $product = null)
{
    $price = $price - ($price * ($discount / 100));

    return to_round_price($price, $product);
}

function to_round_price($price, $product)
{
    if ($product && $product->currency) {
        $price = $price * $product->currency->amount;
    }

    if ($product) {
        $rounding_amount = $product->rounding_amount;

        if ($rounding_amount == 'default') {
            $rounding_amount = option('default_rounding_amount', 'no');
        }

        $rounding_type = $product->rounding_type;

        if ($rounding_type == 'default') {
            $rounding_type = option('default_rounding_type', 'close');
        }

        switch ($rounding_amount) {
            case '100':
            case '1000':
            case '10000':
            case '100000':
                if ($rounding_type == 'up') {
                    $price = ceil($price / $rounding_amount) * $rounding_amount;
                } elseif ($rounding_type == 'down') {
                    $price = floor($price / $rounding_amount) * $rounding_amount;
                } else {
                    $price = round($price / $rounding_amount) * $rounding_amount;
                }
                break;

        }
    }

    return (float) $price;
}

function category_group($key)
{
    switch ($key) {
        case 'postcat':
            return 'دسته بندی وبلاگ';

        case 'productcat':
            return 'دسته بندی محصول';

    }
}

function convert2english($string)
{
    $newNumbers = range(0, 9);
    // 1. Persian HTML decimal
    $persianDecimal = ['&#1776;', '&#1777;', '&#1778;', '&#1779;', '&#1780;', '&#1781;', '&#1782;', '&#1783;', '&#1784;', '&#1785;'];
    // 2. Arabic HTML decimal
    $arabicDecimal = ['&#1632;', '&#1633;', '&#1634;', '&#1635;', '&#1636;', '&#1637;', '&#1638;', '&#1639;', '&#1640;', '&#1641;'];
    // 3. Arabic Numeric
    $arabic = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
    // 4. Persian Numeric
    $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];

    $string = str_replace($persianDecimal, $newNumbers, $string);
    $string = str_replace($arabicDecimal, $newNumbers, $string);
    $string = str_replace($arabic, $newNumbers, $string);

    return str_replace($persian, $newNumbers, $string);
}

function carbon($string)
{
    return Carbon::createFromFormat('Y-m-d H:i:s', $string, 'Asia/Tehran')->timestamp;
}

function datatable($request, $query)
{
    $page = 1;

    if ($request->pagination && isset($request->pagination['page'])) {
        $page = $request->pagination['page'];
    }

    $columns = ['*'];
    $pageName = 'page';
    $perPage = 10;

    if ($request->pagination && isset($request->pagination['perpage']) && $request->pagination['perpage'] > 0) {
        $perPage = $request->pagination['perpage'];
    }

    if ($query->paginate($perPage, $columns, $pageName, $page)->lastPage() >= $page) {
        return $query->paginate($perPage, $columns, $pageName, $page);
    } else {
        return $query->paginate($perPage, $columns, $pageName, 1);
    }
}

function cart_min($selected_price)
{
    // فاز ۸۱ — پیش‌فروش: حداقل ۱ عدد حتی با موجودی صفر (سقف پیش‌فروش = ۱)
    if ($selected_price->product?->isPreOrder()) {
        return 1;
    }

    if ($selected_price->cart_min !== null) {
        return min($selected_price->cart_min, $selected_price->stock);
    }

    return min($selected_price->stock, 1);
}

function cart_max($selected_price)
{
    // فاز ۸۱ — پیش‌فروش: سقف خرید هر کالای پیش‌فروشی ۱ عدد است
    if ($selected_price->product?->isPreOrder()) {
        return 1;
    }

    if ($selected_price->cart_max !== null) {
        return min($selected_price->cart_max, $selected_price->stock);
    }

    return $selected_price->stock;
}

function remove_id_from_url($id)
{
    $segments = request()->segments();

    if (($key = array_search($id, $segments)) !== false) {
        unset($segments[$key]);
    }

    return url(implode('/', $segments));
}

function get_separated_values($array, $separator)
{
    if (! $separator) {
        return $array;
    }

    $result = [];

    foreach ($array as $item) {
        foreach (explode($separator, $item) as $val) {
            $result[] = trim($val);
        }
    }

    return array_unique($result);
}

function get_option_property($obj, $property)
{
    $obj = json_decode($obj);

    if (! is_object($obj)) {
        return null;
    }

    if (property_exists($obj, $property)) {
        return $obj->$property;
    }

    return null;
}

function application_installed()
{
    return file_exists(storage_path('installed'));
}

/*
 * امنیتی: اعتبارسنجی نام قالب/ماژول — جلوگیری از path-traversal
 * (LFI در customConfig، symlink attack، حذف دایرکتوری‌های دلخواه و ...)
 * نام معتبر: فقط حروف لاتین، عدد، خط تیره و زیرخط.
 */
function is_safe_extension_name(string $name): bool
{
    return preg_match('/^[A-Za-z0-9_-]{1,100}$/', $name) === 1;
}

/*
 * امنیتی (XSS): پاک‌سازی HTML غنیِ ورودی کاربر (توضیحات محصول/مقاله/صفحه و ...)
 * قبل از ذخیره‌سازی. HTML مجاز (تگ‌های فرمت‌بندی CKEditor) نگه داشته می‌شود و
 * بردارهای اجرای اسکریپت حذف می‌شوند: <script>، event handlers،
 * javascript:/vbscript:/data: URLs، <iframe> خارج از لیست مجاز، <object/embed/form> ...
 */
function sanitize_rich_text(?string $html): ?string
{
    if ($html === null || trim($html) === '') {
        return $html;
    }

    // ۱) حذف کامل بلوک‌های خطرناک همراه با محتوایشان
    $html = preg_replace('/<script\b[^>]*>.*?<\/script\s*>/is', '', $html) ?? $html;
    $html = preg_replace('/<style\b[^>]*>.*?<\/style\s*>/is', '', $html) ?? $html;
    $html = preg_replace('/<object\b[^>]*>.*?<\/object\s*>/is', '', $html) ?? $html;
    $html = preg_replace('/<embed\b[^>]*>.*?(<\/embed\s*>)?/is', '', $html) ?? $html;
    $html = preg_replace('/<form\b[^>]*>.*?<\/form\s*>/is', '', $html) ?? $html;
    $html = preg_replace('/<meta\b[^>]*>/is', '', $html) ?? $html;
    $html = preg_replace('/<link\b[^>]*>/is', '', $html) ?? $html;
    $html = preg_replace('/<base\b[^>]*>/is', '', $html) ?? $html;
    $html = preg_replace('/<applet\b[^>]*>.*?(<\/applet\s*>)?/is', '', $html) ?? $html;
    $html = preg_replace('/<iframe(?![^>]*(youtube|youtube-nocookie|aparat|vimeo)\.com)[^>]*>.*?(<\/iframe\s*>)?/is', '', $html) ?? $html;

    // ۲) حذف ویژگی‌های رویداد (on*) در همه‌ی تگ‌ها
    $html = preg_replace('/\son\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/is', '', $html) ?? $html;

    // ۳) خنثی‌سازی URL های خطرناک در href/src/action
    $html = preg_replace(
        '/(\s(?:href|src|action|xlink:href)\s*=\s*)("\s*(?:javascript|vbscript|data:text\/html)[^"]*"|\'\s*(?:javascript|vbscript|data:text\/html)[^\']*\'|(?:javascript|vbscript|data:text\/html)[^\s>]*)/is',
        '$1"#"',
        $html
    ) ?? $html;

    // ۴) حذف استایل‌های حاوی expression/javascript
    $html = preg_replace('/style\s*=\s*("[^"]*(?:javascript:|expression\()[^"]*"|\'[^\']*(?:javascript:|expression\()[^\']*\')/is', '', $html) ?? $html;

    return $html;
}

/*
 * فاز ۲۷ — پاک‌سازی HTML توضیحات پکیج‌ها (بازار — مثل پنل قدیمی)
 *
 * توضیحات بلندِ API پکیج‌ها «صفحه‌ی معرفی» کامل است (hero، گالری، جدول ویژگی‌ها
 * و…) و استایل‌های خودش را داخل تگ <style> همان HTML می‌آورد — پنل قدیمی همان را
 * خام تزریق می‌کرد و ظاهر مودال دقیقاً همین بود. sanitize_rich_text تگ <style> را
 * «با محتوا» حذف می‌کند و باعث می‌شد توضیحات پکیج در پنل جدید لخت و به‌هم‌ریخته
 * دیده شود. این نسخه تگ <style> را نگه می‌دارد (اسکوپ CSS API پکیج، امن است) ولی
 * بقیه‌ی بردارها (script، on*، javascript: URLs، فرم/آی‌فریم ناامن) مثل قبل حذف می‌شوند.
 */
function sanitize_package_html(?string $html): ?string
{
    if ($html === null || trim($html) === '') {
        return $html;
    }

    $html = preg_replace('/<script\b[^>]*>.*?<\/script\s*>/is', '', $html) ?? $html;
    $html = preg_replace('/<object\b[^>]*>.*?<\/object\s*>/is', '', $html) ?? $html;
    $html = preg_replace('/<embed\b[^>]*>.*?(<\/embed\s*>)?/is', '', $html) ?? $html;
    $html = preg_replace('/<form\b[^>]*>.*?<\/form\s*>/is', '', $html) ?? $html;
    $html = preg_replace('/<meta\b[^>]*>/is', '', $html) ?? $html;
    $html = preg_replace('/<link\b[^>]*>/is', '', $html) ?? $html;
    $html = preg_replace('/<base\b[^>]*>/is', '', $html) ?? $html;
    $html = preg_replace('/<applet\b[^>]*>.*?(<\/applet\s*>)?/is', '', $html) ?? $html;
    $html = preg_replace('/<iframe(?![^>]*(youtube|youtube-nocookie|aparat|vimeo)\.com)[^>]*>.*?(<\/iframe\s*>)?/is', '', $html) ?? $html;

    $html = preg_replace('/\son\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/is', '', $html) ?? $html;

    $html = preg_replace(
        '/(\s(?:href|src|action|xlink:href)\s*=\s*)("\s*(?:javascript|vbscript|data:text\/html)[^"]*"|\'\s*(?:javascript|vbscript|data:text\/html)[^\']*\'|(?:javascript|vbscript|data:text\/html)[^\s>]*)/is',
        '$1"#"',
        $html
    ) ?? $html;

    $html = preg_replace('/style\s*=\s*("[^"]*(?:javascript:|expression\()[^"]*"|\'[^\']*(?:javascript:|expression\()[^\']*\')/is', '', $html) ?? $html;

    return $html;
}

function change_env($key, $value, bool $cacheConfig = true)
{
    /*
     * امنیتی (env-injection): مقدار نباید شامل کاراکترهای خط جدید یا نویسه‌های
     * کنترلی باشد وگرنه یک مهاجم می‌تواند کلیدهای دلخواه .env (مثل APP_KEY یا
     * DB_PASSWORD) را تزریق کند. کلید هم فقط [A-Z0-9_] مجاز است.
     */
    $key = strtoupper(trim((string) $key));
    $value = str_replace(["\r", "\n", "\0"], '', (string) $value);
    $value = trim($value);

    if (! preg_match('/^[A-Z][A-Z0-9_]*$/', $key) || $value === '') {
        return false;
    }

    // اگر مقدار فاصله/کاراکتر خاص دارد، داخل کوتیشن قرار می‌گیرد
    if (preg_match('/[\s"\'#\\\\]/', $value) && ! preg_match('/^".*"$/', $value)) {
        $value = '"'.str_replace('"', '\\"', $value).'"';
    }

    /*
     * فاز ۲۵ — بازنویسی «خط‌به‌خط» به‌جای split روی همه‌ی فاصله‌ها
     *
     * قبلاً preg_split('/\s+/') کل فایل را تکه‌تکه می‌کرد؛ در نتیجه کامنت‌های
     * چندکلمه‌ای .env (مثل «# Security flags (Phase 15)») به خط‌های بی‌معنی
     * می‌شکستند و بعد از ثبت‌نام مدیر، پارسر dotenv با خطای
     * «Failed to parse dotenv file. Encountered an invalid name»
     * کل برنامه را ۵۰۰ می‌کرد. حالا فقط خط‌های KEY=VALUE بازنویسی می‌شوند
     * و کامنت‌ها/خط‌های خالی دست‌نخورده باقی می‌مانند.
     */
    $envPath = base_path().'/.env';

    if (! file_exists($envPath)) {
        return false;
    }

    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    $key_exists = false;

    foreach ($lines as $env_key => $line) {
        $trimmed = ltrim($line);

        // حفظ کامنت‌ها و خط‌های خالی
        if ($trimmed === '' || $trimmed[0] === '#' || $trimmed[0] === ';') {
            continue;
        }

        $entry = explode('=', $line, 2);

        if (trim((string) $entry[0]) === $key) {
            /*
             * فاز ۳۰ — مقدار از قبل درست است؟ هیچ کاری نکن.
             * بازنویسیِ بی‌دلیل .env در هر ذخیره (حتی وقتی هیچ مقداری عوض نشده)
             * در artisan serve باعث «Environment modified. Restarting server...» و
             * کشته‌شدن همان درخواست می‌شد (ذخیره‌ی تنظیمات دیگر: envها نوشته می‌شدند
             * ولی optionها هرگز!). در php-fpm هم فقط نوشتنِ بیهوده روی دیسک +
             * config:cache اضافی بود. حالا فقط تغییر واقعی فایل را می‌نویسد.
             */
            $existing = trim((string) ($entry[1] ?? ''));
            if (strlen($existing) >= 2
                && ((str_starts_with($existing, '"') && str_ends_with($existing, '"'))
                    || (str_starts_with($existing, "'") && str_ends_with($existing, "'")))) {
                $existing = substr($existing, 1, -1);
            }
            if ($existing === $value) {
                return true;
            }

            $lines[$env_key] = $key.'='.$value;
            $key_exists = true;
            break;
        }
    }

    if (! $key_exists) {
        $lines[] = $key.'='.$value;
    }

    // And overwrite the .env with the new data
    file_put_contents($envPath, implode("\n", $lines)."\n");

    // فاز ۲۵ — کش کانفیگ اختیاری است: در جریان درخواستِ فعال، config:cache
    // یک نمونه‌ی دوم Application را بوت می‌کند و Facadeها را جابه‌جا می‌کند
    // که برای ادامه‌ی همان درخواست (مثل افکت ریدایرکت Livewire) خطرناک است.
    if ($cacheConfig) {
        Artisan::call('config:cache');
    }

    return true;
}

function get_current_theme()
{
    $current_theme = config('general.current_theme');

    if (file_exists(base_path().'/themes/'.$current_theme)) {
        $theme = [];
        $theme['name'] = $current_theme;
        $theme['service_provider'] = "Themes\\$current_theme\src\ThemeServiceProvider";

        return $theme;
    }

    return null;
}

function current_theme_name()
{
    return config('front.theme_name');
}

function customConfig($path)
{
    if (file_exists($path)) {
        $config = include $path;

        return $config;
    }
}

function str_random($length)
{
    return Str::random($length);
}

function get_svg_contents($path, $default = '')
{
    if (file_exists(public_path($path))) {

        $file_parts = pathinfo($path);

        if ($file_parts['extension'] == 'svg') {
            return file_get_contents(public_path($path));
        }
    }

    return $default;
}

function to_sql($query)
{
    return vsprintf(str_replace(['?'], ['\'%s\''], $query->toSql()), $query->getBindings());
}

function store_user_cart(User $user)
{
    $cart_id = Cookie::get('cart_id');

    if ($cart_id) {
        $cart = Cart::find($cart_id);

        if ($cart && $cart->user_id == null) {

            $user_cart = Cart::where('user_id', $user->id)->first();

            if (! $user_cart) {
                $cart->update([
                    'user_id' => $user->id,
                ]);
            } else {
                foreach ($cart->products as $product) {
                    $query = DB::table('cart_product')->where('cart_id', $user_cart->id)->where('product_id', $product->id)->where('price_id', $product->pivot->price_id);
                    $user_cart_product = $query->first();

                    if (! $user_cart_product) {

                        DB::table('cart_product')->insert([
                            'cart_id' => $user_cart->id,
                            'product_id' => $product->id,
                            'quantity' => $product->pivot->quantity,
                            'price_id' => $product->pivot->price_id,
                        ]);
                    } else {

                        $query->update([
                            'quantity' => $product->pivot->quantity,
                        ]);
                    }
                }

                $cart->delete();
            }

            Cookie::queue(Cookie::forget('cart_id'));
        }
    }
}

function ellips_text($str, $char)
{
    $out = mb_strlen($str, 'utf-8') > $char ? mb_substr($str, 0, $char, 'utf-8').'...' : $str;

    return $out;
}

function gateway_key($driver_name)
{
    if ($driver_name == 'behpardakht') {
        return 'mellat';
    }

    return $driver_name;
}
function get_gateway_configs($gateway)
{
    $gateway = Gateway::where('key', $gateway)->first();
    $configs = [];

    switch ($gateway->key) {
        case 'zarinpal':
            $configs['merchantId'] = $gateway->config('merchantId');
            break;

        case 'toman':
            $user = auth()->user();
            $order = $user->orders->last();
            $items = [];
            foreach ($order->items as $product) {

                $data = [
                    'name' => $product->title,
                    'price' => $product->price. 0,
                    'quantity' => $product->quantity,
                ];
                array_push($items, $data);
                $data = null;
            }

            if ($order->shipping_cost > 0) {
                $shipping = [
                    'name' => $order->carrier->title,
                    'price' => $order->shipping_cost. 0,
                    'quantity' => 1,
                ];
                array_push($items, $shipping);
            }

            $data = [
                'res_number' => $order->id,
                'return_to' => route('front.orders.verify', ['gateway' => 'toman']),
                'items' => $items,
            ];

            $configs['shop_slug'] = $gateway->config('shop_slug');
            $configs['auth_code'] = $gateway->config('auth_code');
            $configs['data'] = $data;
            break;

        case 'payping':
            $configs['merchantId'] = $gateway->config('merchantId');
            break;

        case 'irankish':
            $configs['terminalId'] = $gateway->config('terminalId');
            $configs['password'] = $gateway->config('password');
            $configs['acceptorId'] = $gateway->config('acceptorId');
            $configs['pubKey'] = $gateway->config('pubKey');
            break;

        case 'idpay':
            $configs['merchantId'] = $gateway->config('merchantId');
            break;

        case 'saman':
            $configs['merchantId'] = $gateway->config('merchantId');
            break;

        case 'behpardakht':
            $configs['terminalId'] = $gateway->config('terminalId');
            $configs['username'] = $gateway->config('username');
            $configs['password'] = $gateway->config('password');
            break;

        case 'payir':
            $configs['merchantId'] = $gateway->config('merchantId');
            break;

        case 'sepehr':
            $configs['terminalId'] = $gateway->config('terminalId');
            break;

        case 'sadad':
            $configs['key'] = $gateway->config('key');
            $configs['merchantId'] = $gateway->config('merchantId');
            $configs['terminalId'] = $gateway->config('terminalId');
            break;

        case 'zibal':
            $configs['merchantId'] = $gateway->config('merchantId');
            break;

    }

    return $configs;
}

// function get_gateway_configs($gateway)
// {
//     $gateway = Gateway::where('key', $gateway)->first();
//     $configs = [];

//     switch ($gateway->key) {
//         case "zarinpal": {
//                 $configs['merchantId'] = $gateway->config('merchantId');
//                 break;
//             }
//         case "payping": {
//                 $configs['merchantId'] = $gateway->config('merchantId');
//                 break;
//             }
//         case "idpay": {
//                 $configs['merchantId'] = $gateway->config('merchantId');
//                 break;
//             }
//         case "saman": {
//                 $configs['merchantId'] = $gateway->config('merchantId');
//                 break;
//             }
//         case "behpardakht": {
//                 $configs['terminalId'] = $gateway->config('terminalId');
//                 $configs['username']   = $gateway->config('username');
//                 $configs['password']   = $gateway->config('password');
//                 break;
//             }
//         case "payir": {
//                 $configs['merchantId'] = $gateway->config('merchantId');
//                 break;
//             }
//         case "sepehr": {
//                 $configs['terminalId'] = $gateway->config('terminalId');
//                 break;
//             }
//         case "sadad": {
//                 $configs['key']        = $gateway->config('key');
//                 $configs['merchantId'] = $gateway->config('merchantId');
//                 $configs['terminalId'] = $gateway->config('terminalId');
//                 break;
//             }
//         case "zibal": {
//                 $configs['merchantId'] = $gateway->config('merchantId');
//                 break;
//             }
//     }

//     return $configs;
// }

function sluggable_helper_function($string, $separator = '-')
{
    $_transliteration = [
        '/ö|œ/' => 'e',
        '/ü/' => 'e',
        '/Ä/' => 'e',
        '/Ü/' => 'e',
        '/Ö/' => 'e',
        '/À|Á|Â|Ã|Å|Ǻ|Ā|Ă|Ą|Ǎ/' => '',
        '/à|á|â|ã|å|ǻ|ā|ă|ą|ǎ|ª/' => '',
        '/Ç|Ć|Ĉ|Ċ|Č/' => '',
        '/ç|ć|ĉ|ċ|č/' => '',
        '/Ð|Ď|Đ/' => '',
        '/ð|ď|đ/' => '',
        '/È|É|Ê|Ë|Ē|Ĕ|Ė|Ę|Ě/' => '',
        '/è|é|ê|ë|ē|ĕ|ė|ę|ě/' => '',
        '/Ĝ|Ğ|Ġ|Ģ/' => '',
        '/ĝ|ğ|ġ|ģ/' => '',
        '/Ĥ|Ħ/' => '',
        '/ĥ|ħ/' => '',
        '/Ì|Í|Î|Ï|Ĩ|Ī| Ĭ|Ǐ|Į|İ/' => '',
        '/ì|í|î|ï|ĩ|ī|ĭ|ǐ|į|ı/' => '',
        '/Ĵ/' => '',
        '/ĵ/' => '',
        '/Ķ/' => '',
        '/ķ/' => '',
        '/Ĺ|Ļ|Ľ|Ŀ|Ł/' => '',
        '/ĺ|ļ|ľ|ŀ|ł/' => '',
        '/Ñ|Ń|Ņ|Ň/' => '',
        '/ñ|ń|ņ|ň|ŉ/' => '',
        '/Ò|Ó|Ô|Õ|Ō|Ŏ|Ǒ|Ő|Ơ|Ø|Ǿ/' => '',
        '/ò|ó|ô|õ|ō|ŏ|ǒ|ő|ơ|ø|ǿ|º/' => '',
        '/Ŕ|Ŗ|Ř/' => '',
        '/ŕ|ŗ|ř/' => '',
        '/Ś|Ŝ|Ş|Ș|Š/' => '',
        '/ś|ŝ|ş|ș|š|ſ/' => '',
        '/Ţ|Ț|Ť|Ŧ/' => '',
        '/ţ|ț|ť|ŧ/' => '',
        '/Ù|Ú|Û|Ũ|Ū|Ŭ|Ů|Ű|Ų|Ư|Ǔ|Ǖ|Ǘ|Ǚ|Ǜ/' => '',
        '/ù|ú|û|ũ|ū|ŭ|ů|ű|ų|ư|ǔ|ǖ|ǘ|ǚ|ǜ/' => '',
        '/Ý|Ÿ|Ŷ/' => '',
        '/ý|ÿ|ŷ/' => '',
        '/Ŵ/' => '',
        '/ŵ/' => '',
        '/Ź|Ż|Ž/' => '',
        '/ź|ż|ž/' => '',
        '/Æ|Ǽ/' => 'E',
        '/ß/' => 's',
        '/Ĳ/' => 'J',
        '/ĳ/' => 'j',
        '/Œ/' => 'E',
        '/ƒ/' => '',
    ];
    $quotedReplacement = preg_quote($separator, '/');
    $merge = [
        '/[^\s\p{Zs}\p{Ll}\p{Lm}\p{Lo}\p{Lt}\p{Lu}\p{Nd}]/mu' => ' ',
        '/[\s\p{Zs}]+/mu' => $separator,
        sprintf('/^[%s]+|[%s]+$/', $quotedReplacement, $quotedReplacement) => '',
    ];
    $map = $_transliteration + $merge;
    unset($_transliteration);

    return preg_replace(array_keys($map), array_values($map), $string);
}

function admin_route_prefix()
{
    return config('general.admin_route_prefix');
}

function formatSizeUnits($bytes)
{
    if ($bytes >= 1073741824) {
        $bytes = number_format($bytes / 1073741824, 2).' GB';
    } elseif ($bytes >= 1048576) {
        $bytes = number_format($bytes / 1048576, 2).' MB';
    } elseif ($bytes >= 1024) {
        $bytes = number_format($bytes / 1024, 2).' KB';
    } elseif ($bytes > 1) {
        $bytes = $bytes.' bytes';
    } elseif ($bytes == 1) {
        $bytes = $bytes.' byte';
    } else {
        $bytes = '0 bytes';
    }

    return $bytes;
}

function formatPriceUnits($price)
{
    if ($price >= 1000000000) {
        $price = number_format($price / 1000000000, 2).' میلیارد';
    } elseif ($price >= 1000000) {
        $price = number_format($price / 1000000, 2).' میلیون';
    } elseif ($price >= 1000) {
        $price = number_format($price / 1000, 2).' هزار';
    } else {
        $price = round($price, 2);
    }

    return $price;
}

function convertPersianToEnglish($string)
{
    $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    $english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

    $output = str_replace($persian, $english, $string);

    return $output;
}

function get_langs()
{
    return config('app.locales');
}

function get_current_url($lang)
{
    $locale = request()->segment(1);
    $current_url = request()->url();

    if (! $locale || ! array_key_exists($locale, get_langs())) {
        $index = url('/');

        $url = str_replace_first($index, $index.'/'.$lang, $current_url);
    } else {
        $url = str_replace_first($locale, $lang, $current_url);
    }

    return $url;
}

function local_info()
{
    $local = app()->getLocale();

    $locals = get_langs();

    return $locals[$local];
}

function locale_date($date)
{
    if (app()->getLocale() == 'fa') {
        return jdate($date);
    }

    return carbon($date);
}

function str_replace_first($from, $to, $content)
{
    $from = '/'.preg_quote($from, '/').'/';

    return preg_replace($from, $to, $content, 1);
}

function aparat_iframe($string)
{
    $p = '/^(?:https?:\/\/)?(?:www\.)?(?:aparat\.com\/v\/)(\w*)(?:\S+)?$/';
    preg_match($p, $string, $matches);

    if (empty($matches)) {
        return '';
    }

    return '<div class="h_iframe-aparat_embed_frame"><span style="display: block;padding-top: 57%">.</span><iframe data-src="https://www.aparat.com/video/video/embed/videohash/'.$matches[1].'/vt/frame" allowFullScreen="true" webkitallowfullscreen="true" mozallowfullscreen="true"></iframe></div>';
}

function module_asset(string $module, string $path): string
{
    $moduleLower = strtolower($module);
    $path = ltrim($path, '/');

    // مسیر صحیح برای فایل‌های assets
    return asset('modules/'.$moduleLower.'/'.$path);
}
function theme_asset($path)
{
    return asset(config('front.asset_path').$path);
}

function theme_path($path)
{
    return base_path(config('front.theme_path').$path);
}

function prepareNumber($num)
{
    if (gettype($num) == 'integer' || gettype($num) == 'double') {
        $num = (string) $num;
    }
    $length = strlen($num) % 3;
    if ($length == 1) {
        $num = '00'.$num;
    } elseif ($length == 2) {
        $num = '0'.$num;
    }

    return str_split($num, 3);
}
function threeNumbersToLetter($num, $words, $splitter)
{
    if ((int) preg_replace('/\D/', '', $num) == 0) {
        return '';
    }
    $parsedInt = (int) preg_replace('/\D/', '', $num);
    if ($parsedInt < 10) {
        return $words[0][$parsedInt];
    }
    if ($parsedInt <= 20) {
        return $words[1][$parsedInt - 10];
    }
    if ($parsedInt < 100) {
        $one = $parsedInt % 10;
        $ten = ($parsedInt - $one) / 10;
        if ($one > 0) {
            return $words[2][$ten].$splitter.$words[0][$one];
        }

        return $words[2][$ten];
    }
    $one = $parsedInt % 10;
    $hundreds = ($parsedInt - $parsedInt % 100) / 100;
    $ten = ($parsedInt - (($hundreds * 100) + $one)) / 10;
    $out = [$words[3][$hundreds]];
    $secondPart = (($ten * 10) + $one);
    if ($secondPart > 0) {
        if ($secondPart < 10) {
            array_push($out, $words[0][$secondPart]);
        } elseif ($secondPart <= 20) {
            array_push($out, $words[1][$secondPart - 10]);
        } else {
            array_push($out, $words[2][$ten]);
            if ($one > 0) {
                array_push($out, $words[0][$one]);
            }
        }
    }

    return implode($splitter, $out);
}

function convert_number($number)
{
    $words = [
        [
            '',
            'یک',
            'دو',
            'سه',
            'چهار',
            'پنج',
            'شش',
            'هفت',
            'هشت',
            'نه',
        ],
        [
            'ده',
            'یازده',
            'دوازده',
            'سیزده',
            'چهارده',
            'پانزده',
            'شانزده',
            'هفده',
            'هجده',
            'نوزده',
            'بیست',
        ],
        [
            '',
            '',
            'بیست',
            'سی',
            'چهل',
            'پنجاه',
            'شصت',
            'هفتاد',
            'هشتاد',
            'نود',
        ],
        [
            '',
            'یکصد',
            'دویست',
            'سیصد',
            'چهارصد',
            'پانصد',
            'ششصد',
            'هفتصد',
            'هشتصد',
            'نهصد',
        ],
        [
            '',
            ' هزار ',
            ' میلیون ',
            ' میلیارد ',
            ' بیلیون ',
            ' بیلیارد ',
            ' تریلیون ',
            ' تریلیارد ',
            ' کوآدریلیون ',
            ' کادریلیارد ',
            ' کوینتیلیون ',
            ' کوانتینیارد ',
            ' سکستیلیون ',
            ' سکستیلیارد ',
            ' سپتیلیون ',
            ' سپتیلیارد ',
            ' اکتیلیون ',
            ' اکتیلیارد ',
            ' نانیلیون ',
            ' نانیلیارد ',
            ' دسیلیون ',
        ],
    ];
    $splitter = ' و ';

    $zero = 'صفر';
    if ($number == 0) {
        return $zero;
    }
    if (strlen($number) > 66) {
        return 'خارج از محدوده';
    }
    // Split to sections
    $splittedNumber = prepareNumber($number);
    $result = [];
    $splitLength = count($splittedNumber);
    for ($i = 0; $i < $splitLength; $i++) {
        $sectionTitle = $words[4][$splitLength - ($i + 1)];
        $converted = threeNumbersToLetter($splittedNumber[$i], $words, $splitter);
        if ($converted !== '') {
            array_push($result, $converted.$sectionTitle);
        }
    }

    return implode($splitter, $result);
}

function run_theme_config()
{
    if (function_exists('theme_first_config')) {
        theme_first_config();
    }
}

function barcode($str)
{
    $barcode = new DNS1D;

    if (! is_dir(public_path().'/uploads/barcodes')) {
        File::makeDirectory(public_path().'/uploads/barcodes/');
    }

    return asset($barcode->getBarcodePNGPath($str, 'C39E', 3, 33, [69, 78, 89]));
}

function notification_link($notification)
{
    if ($notification->type == 'OrderPaid') {
        if (isset($notification->data['order_id']) && Order::find($notification->data['order_id'])) {
            return route('admin.orders.show', ['order' => $notification->data['order_id']]);
        }
    }

    if ($notification->type == 'UserRegistered') {
        if (isset($notification->data['user_id']) && User::find($notification->data['user_id'])) {
            return route('admin.users.show', ['user' => $notification->data['user_id']]);
        }
    }
    if ($notification->type == 'SellerRegistered') {
        if (isset($notification->data['seller_id']) && Seller::find($notification->data['seller_id'])) {
            return route('admin.sellers.show', ['seller' => Seller::find($notification->data['seller_id'])->slug]);
        }
    }
    if ($notification->type == 'SellerEditProfile') {
        if (isset($notification->data['seller_id']) && Seller::find($notification->data['seller_id'])) {
            return route('admin.sellers.show', ['seller' => Seller::find($notification->data['seller_id'])->slug]);
        }
    }
    if ($notification->type == 'SellerRequestDeposit') {
        return route('admin.request-deposit.index');
    }
    if ($notification->type == 'UserRequestDeposit') {
        return route('admin.request-deposit.index');
    }

    if ($notification->type == 'TicketCreated') {
        if (isset($notification->data['ticket_id']) && Ticket::find($notification->data['ticket_id'])) {
            return route('admin.tickets.show', ['ticket' => Ticket::find($notification->data['ticket_id'])]);
        }
    }

    if ($notification->type == 'CommentPostCreated') {
        return route('admin.comments.posts');
    }

    if ($notification->type == 'CommentProductCreated') {
        return route('admin.reviews.index');
    }

    if ($notification->type == 'QuestionProductCreated') {
        return route('admin.comments.products');
    }

    if ($notification->type == 'SellerProductCreated') {
        return route('admin.sellers.products');
    }

    if ($notification->type == 'SellerProductUpdate') {
        return route('admin.sellers.products');
    }

    return null;
}

function random_code($letter_count = 2, $number_count = 3)
{
    $numbers = '0123456789';
    $letters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';

    for ($i = 0; $i < $letter_count; $i++) {
        $index = rand(0, strlen($numbers) - 1);
        $randomString .= $letters[$index];
    }

    for ($i = 0; $i < $number_count; $i++) {
        $index = rand(0, strlen($numbers) - 1);
        $randomString .= $numbers[$index];
    }

    return str_shuffle($randomString);
}

function get_slider_search()
{
    return Slider::whereJsonContains('groups', 'search_sliders')
        ->where('published', true)
        ->orderBy('ordering')
        ->get();
}
function seller()
{
    $seller = Seller::where('id', Auth::guard('seller')->id())->first();
    if (! $seller) {
        return null;
    }

    return $seller;
}

function sellerID()
{
    $seller = Seller::where('id', Auth::guard('seller')->id())->first();

    if (! $seller) {
        return null;
    }

    return $seller->id;
}

function seller_info()
{
    $seller = SellerInfo::where('seller_id', Auth::guard('seller')->id())->first();
    if (! $seller) {
        return null;
    }

    return $seller;
}

function get_seller($id)
{
    $seller = Seller::where('id', $id)->first();
    if (! $seller) {
        return null;
    }

    return $seller;
}
function get_seller_info($id)
{

    $seller = SellerInfo::where('seller_id', $id)->first();
    if ($seller) {
        return $seller;
    }

    return null;
}

function checkAiToken($token, $created_by = null)
{
    $response = Http::get('https://ai.webtpro.ir/api/check-token', [
        'token_key' => $token,
        'created_by' => $created_by,
    ]);

    return json_decode($response);
}
function multi_language()
{
    $languages = ['persian', 'English (US)', 'English (UK)', 'French', 'Spanish', 'German', 'Italian', 'Dutch', 'Portuguese', 'Portuguese (BR)', 'Swedish', 'Norwegian', 'Danish', 'Romanian', 'Czech', 'Slovak', 'Slovenian', 'Hungarian', 'Croatian', 'Polish', 'Greek', 'Turkish', 'Russian', 'Hindi', 'Thai', 'Japanese', 'Chinese (Simplified)', 'Korean'];

    return $languages;
}

function run_fix_helper()
{
    $brand = Brand::where('slug', 'گیرا-GIRA')->first();
    $products = Product::where('brand_id', $brand->id)->get();

    $tags = addTags(',ای قفل ,آی قفل,اقای قفل,آقای قفل,قفل من,Hd rtg,Hrhd rtg,Rtg lk,Ghoflman,Ay ghofl,Aghaye ghofl,خرید از ای قفل,خرید از اقای قفل,خرید از آی قفل,خرید از آقای قفل,خرید از قفل من,خرید قفل از ای قفل,خرید قفل از اقای قفل,خرید قفل از آی قفل,خرید قفل از آقای قفل,خرید قفل از قفل من,خرید قفل کتابی گیرا از ای قفل,خرید قفل کتابی گیرا از اقای قفل,خرید قفل کتابی گیرا از آی قفل,خرید قفل کتابی گیرا از آقای قفل,خرید قفل کتابی گیرا از قفل من,خرید قفل دیسک گیرا از ای قفل,خرید قفل دیسک گیرا از اقای قفل,خرید قفل دیسک گیرا از آی قفل,خرید قفل دیسک گیرا از آقای قفل,خرید قفل دیسک گیرا از قفل من,خرید قفل موتورسیکلت گیرا از ای قفل,خرید قفل موتورسیکلت گیرا از اقای قفل,خرید قفل موتورسیکلت گیرا از آی قفل,خرید قفل موتورسیکلت گیرا از آقای قفل,خرید قفل موتورسیکلت گیرا از قفل من,خرید قفل اویز گیرا از ای قفل,خرید قفل اویز گیرا از اقای قفل,خرید قفل اویز گیرا از آی قفل,خرید قفل اویز گیرا از آقای قفل,خرید قفل اویز گیرا از قفل من,خرید قفل آویز گیرا از ای قفل,خرید قفل آویز گیرا از اقای قفل,خرید قفل آویز گیرا از آی قفل,خرید قفل آویز گیرا از آقای قفل,خرید قفل آویز گیرا از قفل من,خرید قفل گیرا از ای قفل,خرید قفل گیرا از اقای قفل,خرید قفل گیرا از آی قفل,خرید قفل گیرا از آقای قفل,خرید قفل گیرا از قفل من,قفل گیرا,خرید قفل گیرا,قفل کتابی گیرا,خرید قفل کتابی گیرا,قفل آویز گیرا,خرید قفل آویز گیرا,قفل دیسک گیرا,خرید قفل دیسک گیرا,قفل موتورسیکلت گیرا,خرید قفل موتورسیکلت گیرا,قفل اویز گیرا,خرید قفل اویز گیرا,خرید قفل عصایی گیرا از ای قفل,خرید قفل عصایی گیرا از اقای قفل,خرید قفل عصایی گیرا از آی قفل,خرید قفل عصایی گیرا از آقای قفل,خرید قفل عصایی گیرا از قفل من,قفل کتابی,قفل دیسک,قفل اویز,قفل آویز,قفل موتورسیکلت,قفل عصایی,خرید قفل آپارتمانی گیرا از ای قفل,خرید قفل آپارتمانی گیرا از اقای قفل,خرید قفل آپارتمانی گیرا از آی قفل,خرید قفل آپارتمانی گیرا از آقای قفل,خرید قفل آپارتمانی گیرا از قفل من,Rtg,Rtg uwhdd,RTG ;JHFD,RTG NDS;,RTG UWHDD,خرید قفل اپارتمانی گیرا از ای قفل,خرید قفل اپارتمانی گیرا از اقای قفل,خرید قفل اپارتمانی گیرا از آی قفل,خرید قفل اپارتمانی گیرا از آقای قفل,خرید قفل اپارتمانی گیرا از قفل من');

    foreach ($products as $product) {
        $product_tags = $product->tags()->pluck('tags.id')->toArray();
        $all_tags = array_merge($tags, $product_tags);

        $product->tags()->sync(array_unique($all_tags));
    }
}

function saveFieldValues(array $filds, string $belongsTo, int $relatedId)
{
    $requiredFilds = Fild::whereIn('id', array_keys($filds))->get();

    $rules = [];
    foreach ($requiredFilds as $fild) {
        if ($fild->required) {
            $rules["filds.{$fild->id}"] = 'required';

            if ($fild->type == 'email') {
                $rules["filds.{$fild->id}"] .= '|email';
            } elseif ($fild->type == 'number') {
                $rules["filds.{$fild->id}"] .= '|numeric';
            }
        }
    }

    foreach ($filds as $fieldId => $value) {
        FieldValue::updateOrCreate(
            [
                'field_id' => $fieldId,
                'belongs_to' => $belongsTo,
                'related_id' => $relatedId,
            ],
            ['value' => $value]
        );
    }
}

/* ═══════════════════════════════════════════════════════════════════════
 * فاز ۷۵ — گارد یکپارچگی vendor Intervention Image
 *
 * خطای جدید سرور لایو هنگام ذخیرهٔ محصول:
 *   Class Intervention\Image\EncodedImage contains 5 abstract methods and
 *   must therefore be declared abstract or implement the remaining methods
 *   (Intervention\Image\Interfaces\FileInterface::save, toFilePointer, size, ...)
 *   at vendor/intervention/image/src/EncodedImage.php:9
 *
 * ریشه: فایل‌های vendor روی هاست از دو نسخهٔ مختلف v3 مخلوط شده‌اند —
 * «FileInterface» جدید است و «File/EncodedImage» قدیمی؛ این خطا E_ERROR /
 * E_COMPILE_ERROR است و با try/catch قابل شکار نیست (در لاگ، مسیر
 * handleShutdown دیده می‌شود). یعنی هیچ try/catchی دور encode نجات نمی‌دهد.
 *
 * راه‌حل: پیش از بارگذاری «هر» کلاسی از Intervention، یکپارچگی فایل‌ها با
 * تحلیل متنی (بدون autoload — چون خود autoload نقطهٔ انفجار است) بررسی
 * می‌شود؛ اگر vendor مخلوط/معیوب بود، کل خط تصویر به پیاده‌سازی «GD خالص»
 * می‌رود که به هیچ کلاسی از Intervention وابسته نیست — آپلود محصول روی
 * بدترین سرور ممکن هم کار می‌کند.
 * ═══════════════════════════════════════════════════════════════════════ */

if (! function_exists('shop_vendor_php_methods')) {
    /** استخراج نام متدهای یک فایل PHP (بدون بارگذاری کلاس) — کامنت‌ها حذف می‌شوند */
    function shop_vendor_php_methods(string $code): array
    {
        $code = (string) preg_replace('/\/\*.*?\*\//s', '', $code);
        $code = (string) preg_replace('/\/\/[^\n]*/', '', $code);
        $code = (string) preg_replace('/(?<![:"\'])#[^\n]*/', '', $code);

        if (! preg_match_all('/function\s+&?\s*(\w+)\s*\(/', $code, $m)) {
            return [];
        }

        return $m[1];
    }
}

if (! function_exists('shop_vendor_php_uses')) {
    /** نقشهٔ alias های use در سربرگ فایل — برای حل نام کوتاه به FQN */
    function shop_vendor_php_uses(string $code): array
    {
        $map = [];

        if (preg_match_all('/^\s*use\s+([\w\\\\]+)(?:\s+as\s+(\w+))?\s*;/m', $code, $m, PREG_SET_ORDER)) {
            foreach ($m as $hit) {
                $fqn = trim($hit[1], '\\');
                $pos = strrpos($fqn, '\\');
                $alias = $hit[2] ?? ($pos === false ? $fqn : substr($fqn, $pos + 1));
                $map[strtolower($alias)] = $fqn;
            }
        }

        return $map;
    }
}

if (! function_exists('shop_vendor_resolve_file')) {
    /**
     * فایل کلاس/ترِیت/اینترفیس را از روی نام پیدا می‌کند.
     * ریشهٔ namespace پکیج (Intervention\Image\) همان پوشهٔ src است؛ نام‌های
     * کوتاهِ هم-namespace هم با کاندیدهای استاندارد پکیج حل می‌شوند.
     * خروجی null یعنی در این پکیج قابل حل نیست (مثل Stringable سراسری).
     */
    function shop_vendor_resolve_file(string $srcDir, string $name): ?string
    {
        $name = trim($name, '\\');

        if ($name === '') {
            return null;
        }

        $candidates = [];

        if (str_contains($name, '\\')) {
            $candidates[] = $name; // FQN کامل
        } else {
            // نام کوتاه — کاندیدهای استاندارد پکیج Intervention Image
            $candidates[] = 'Intervention\\Image\\'.$name;
            $candidates[] = 'Intervention\\Image\\Interfaces\\'.$name;
            $candidates[] = 'Intervention\\Image\\Traits\\'.$name;
            $candidates[] = 'Intervention\\Image\\Drivers\\Gd\\'.$name;
            $candidates[] = 'Intervention\\Image\\Drivers\\Gd\\Encoders\\'.$name;
            $candidates[] = 'Intervention\\Image\\Encoders\\'.$name;
        }

        foreach ($candidates as $candidate) {
            $relative = (string) preg_replace('/^Intervention\\\\Image\\\\/', '', $candidate);
            $file = $srcDir.'/'.str_replace('\\', '/', $relative).'.php';

            if (is_file($file)) {
                return $file;
            }
        }

        return null;
    }
}

if (! function_exists('shop_vendor_class_actual_methods')) {
    /**
     * جمع همهٔ متدهای «قابل ارائه» یک کلاس فقط از روی فایل‌ها:
     * خود کلاس + بدنهٔ آن (traitها) + والد(ها) به‌صورت بازگشتی
     */
    function shop_vendor_class_actual_methods(string $srcDir, string $classFqn, array &$seen, int $depth = 0): array
    {
        $methods = [];

        if ($depth > 8) {
            return $methods;
        }

        $key = strtolower(trim($classFqn, '\\'));

        if (isset($seen[$key])) {
            return $methods;
        }

        $seen[$key] = true;

        $file = shop_vendor_resolve_file($srcDir, $classFqn);

        if ($file === null) {
            return $methods; // والد/trait خارج از این پکیج (مثل Stringable) — نادیده
        }

        $code = (string) file_get_contents($file);
        $uses = shop_vendor_php_uses($code);
        $methods = array_merge($methods, shop_vendor_php_methods($code));

        // فقط بدنهٔ کلاس/ترِیت — تا use های سربرگ فایل با trait اشتباه نشوند
        if (preg_match('/\b(?:class|trait|interface)\s+\w+[^\{]*\{/', $code, $cm, PREG_OFFSET_CAPTURE)) {
            $body = (string) substr($code, (int) $cm[0][1]);

            if (preg_match_all('/^\s*use\s+([\w\\\\,\s]+);/m', $body, $traitHits, PREG_SET_ORDER)) {
                foreach ($traitHits as $hit) {
                    foreach (explode(',', $hit[1]) as $trait) {
                        $trait = trim($trait);

                        if ($trait === '' || ! preg_match('/^[\w\\\\]+$/', $trait)) {
                            continue; // شکل‌های پیچیده (adaptation) پشتیبانی نمی‌شوند — کافی است
                        }

                        $traitFqn = trim($uses[strtolower($trait)] ?? $trait, '\\');
                        $methods = array_merge(
                            $methods,
                            shop_vendor_class_actual_methods($srcDir, $traitFqn, $seen, $depth + 1)
                        );
                    }
                }
            }

            if (preg_match('/\bextends\s+([\w\\\\]+)/', $cm[0][0], $pm)) {
                $parent = trim($uses[strtolower($pm[1])] ?? $pm[1], '\\');
                $methods = array_merge(
                    $methods,
                    shop_vendor_class_actual_methods($srcDir, $parent, $seen, $depth + 1)
                );
            }
        }

        return $methods;
    }
}

if (! function_exists('shop_vendor_interface_required_methods')) {
    /**
     * جمع متدهای لازم از زنجیرهٔ اینترفیس‌ها.
     * خروجی null یعنی اینترفیسِ Intervention قابل حل نبود → vendor معیوب است.
     */
    function shop_vendor_interface_required_methods(string $srcDir, string $ifaceFqn, array &$seen, int $depth = 0): ?array
    {
        if ($depth > 8) {
            return [];
        }

        $key = strtolower(trim($ifaceFqn, '\\'));

        if (isset($seen[$key])) {
            return [];
        }

        $seen[$key] = true;

        $file = shop_vendor_resolve_file($srcDir, $ifaceFqn);

        if ($file === null) {
            // اینترفیس بیرونی (مثل Stringable) نادیده گرفته می‌شود؛
            // اینترفیسِ خود Intervention که پیدا نشد یعنی فایل‌ها ناقص‌اند
            $isPackageOwned = str_starts_with(trim($ifaceFqn, '\\'), 'Intervention\\Image\\');

            return $isPackageOwned ? null : [];
        }

        $code = (string) file_get_contents($file);
        $uses = shop_vendor_php_uses($code);
        $methods = shop_vendor_php_methods($code);

        if (preg_match('/interface\s+\w+[^\{]*extends\s+([^\{]+?)\s*\{/', $code, $m)) {
            foreach (explode(',', $m[1]) as $parent) {
                $parent = trim($parent);

                if ($parent === '') {
                    continue;
                }

                $parentFqn = trim($uses[strtolower($parent)] ?? $parent, '\\');
                $sub = shop_vendor_interface_required_methods($srcDir, $parentFqn, $seen, $depth + 1);

                if ($sub === null) {
                    return null;
                }

                $methods = array_merge($methods, $sub);
            }
        }

        return $methods;
    }
}

if (! function_exists('shop_intervention_vendor_ok')) {
    /**
     * فاز ۷۵ — vendor Intervention سالم است؟ (تحلیل متنی، بدون بارگذاری کلاس)
     *
     * نتیجه در همان درخواست کش می‌شود؛ پارامتر اختیاری برای تست‌هاست
     * (مسیر src یک کپی دیگر — مثل vendor داخلی ماژول‌ها).
     */
    function shop_intervention_vendor_ok(?string $interventionSrcDir = null): bool
    {
        static $cached = null;

        if ($cached !== null && $interventionSrcDir === null) {
            return $cached;
        }

        $verdict = true;

        try {
            $src = $interventionSrcDir ?? base_path('vendor/intervention/image/src');

            if (! is_dir($src)) {
                $verdict = false; // پوشهٔ src اصلاً نیست — Intervention قابل اتکا نیست
            } elseif (is_file($src.'/EncodedImage.php')) {
                // v3 — زنجیرهٔ EncodedImage باید همهٔ متدهای اینترفیس‌ها را بدهد
                $seenI = [];
                $seenC = [];

                $required = shop_vendor_interface_required_methods(
                    $src,
                    'Intervention\\Image\\Interfaces\\EncodedImageInterface',
                    $seenI
                );
                $actual = shop_vendor_class_actual_methods(
                    $src,
                    'Intervention\\Image\\EncodedImage',
                    $seenC
                );

                $verdict = $required !== null
                    && $required !== []
                    && empty(array_diff(array_unique($required), array_unique($actual)));
            }
            // بدون EncodedImage.php → v2 است → لایهٔ سازگار فاز ۷۳ خودش هندل می‌کند
        } catch (Throwable) {
            $verdict = false;
        }

        // فاز ۷۷ — دیسک سالم است، اما OPcache (با validate_timestamps=0) ممکن است
        // کلاسِ کامپایل‌شدهٔ کهنه (از دورِ مخلوط) را سرو کند — همان دام لاگ ۰۶:۴۶.
        if ($verdict) {
            $stale = shop_intervention_opcache_stale($src);

            if ($stale) {
                $verdict = false;
            }
        }

        if ($interventionSrcDir === null) {
            $cached = $verdict;
        }

        return $verdict;
    }
}

if (! function_exists('shop_intervention_opcache_stale')) {
    /**
     * فاز ۷۷ — آیا OPcache کلاسِ کامپایل‌شدهٔ کهنه از این پوشهٔ src دارد؟
     *
     * دام واقعی لاگ ۰۶:۴۶ کاربر: دیسک بعد از git pull سالم است (پروب متنی سبز
     * می‌شود) اما با opcache.validate_timestamps=0، کلاس‌های کامپایل‌شدهٔ دورِ
     * مخلوط در حافظه می‌مانند و همان لحظهٔ load منفجر می‌شوند. این پروب mtime
     * نسخهٔ کامپایل‌شده را با mtime دیسک مقایسه می‌کند.
     *
     * خروجی: true = کهنه (نامعتبر) | false = تازه | null = OPcache در دسترس نیست/نمی‌توان گفت
     */
    function shop_intervention_opcache_stale(string $srcDir): ?bool
    {
        if (! function_exists('opcache_get_status') || ! function_exists('opcache_get_configuration')) {
            return null; // OPcache لود نیست — کلاس‌ها از دیسک خوانده می‌شوند؛ پروب دیسک کافی است
        }

        try {
            $directives = @opcache_get_configuration()['directives'] ?? [];

            // با بازاعتبارسنجی فعال، خود OPcache با دیسک هم‌تراز می‌شود
            if ((bool) ($directives['opcache.validate_timestamps'] ?? true)) {
                return false;
            }

            $status = @opcache_get_status(true);

            if (! is_array($status)) {
                return null; // (مثلاً enable_cli=0 در CLI)
            }

            $prefix = rtrim($srcDir, '/').'/';

            foreach ((array) ($status['scripts'] ?? []) as $script) {
                $fullPath = (string) ($script['full_path'] ?? '');

                if ($fullPath === '' || ! str_starts_with($fullPath, $prefix)) {
                    continue;
                }

                $diskAt = @filemtime($fullPath);

                if ($diskAt !== false && (int) $diskAt !== (int) ($script['timestamp'] ?? 0)) {
                    return true; // نسخهٔ کامپایل‌شده ≠ دیسک — کلاس‌های کهنه
                }
            }

            return false; // چیزی از Intervention در کش نبود یا همه هم‌عصر دیسک‌اند
        } catch (Throwable) {
            return null;
        }
    }
}

if (! function_exists('shop_register_intervention_trap')) {
    /**
     * فاز ۷۵ — تلهٔ تشخیصی برای کرش Fatal مربوط به Intervention
     *
     * چون خطای «abstract methods» غیرقابل شکار است، در shutdown آخرین خطا
     * بررسی می‌شود؛ اگر از Intervention بود، لاگِ شفاف با راهنمای درمان
     * نوشته می‌شود تا دفعهٔ بعد ریشه در چند ثانیه پیدا شود.
     */
    function shop_register_intervention_trap(): void
    {
        static $registered = false;

        if ($registered) {
            return;
        }

        $registered = true;

        register_shutdown_function(static function (): void {
            $last = error_get_last();

            if (! $last || ! in_array((int) $last['type'], [E_ERROR, E_COMPILE_ERROR, E_PARSE, E_CORE_ERROR], true)) {
                return;
            }

            $message = (string) ($last['message'] ?? '');
            $file = (string) ($last['file'] ?? '');

            if (! str_contains($message, 'Intervention') && ! str_contains($file, 'intervention')) {
                return;
            }

            try {
                Log::error(
                    'فاز ۷۵ — کرش Fatal در Intervention Image: «'.$message.'» @ '.$file
                    .' — به احتمال زیاد vendor روی هاست مخلوط/معیوب است. درمان: '
                    .'php artisan ops:intervention-doctor (خروجی --json را برای بررسی بفرستید) '
                    .'سپس composer install + optimize:clear + ریست OPcache از پنل هاست.'
                );
            } catch (Throwable) {
                // لاگ در دسترس نیست — کاری نمی‌توان کرد
            }
        });
    }
}

/* ═══════════════════════════════════════════════════════════════════════
 * فاز ۷۳ — لایهٔ سازگاری Intervention Image
 *
 * مشکل واقعی کاربر (سرور لایو): با vendor قدیمی (v3 آلفا فاقد read() و
 * انکودرهای نهایی) هنگام ذخیرهٔ محصول (افزودن از دیجی‌کالا و ویرایش) این خطا
 * می‌آمد: Call to undefined method Intervention\Image\ImageManager::read()
 *
 * این لایه با «تشخیص قابلیت» روی هر سه نسل کار می‌کند:
 *   v2        → make() / resize()+constraint / insert() / encode(فرمت, کیفیت)
 *   v3 آلفا   → make() با سازندهٔ آرایه‌ای / انکودر آبجکتی در صورت وجود
 *   v3 نهایی  → read() / scale() / place() / انکودر آبجکتی
 * ═══════════════════════════════════════════════════════════════════════ */

if (! function_exists('shop_image_manager')) {
    /**
     * ImageManager سازگار با هر نسخهٔ Intervention — یک‌بار ساخته، با پروب واقعی
     * اعتبارسنجی و کش می‌شود
     *
     * فاز ۷۴ — درس تازه از سرور لایو (خطای «Unknown driver type.» هنگام ذخیرهٔ
     * محصول دیجی‌کالا): تشخیص نسخه با class_exists(Driver::class) غلط از آب درمی‌آید
     * چون روی هاست کاربر، ماژول SeoAudit vendor خودش را با Intervention v2 دارد و
     * سیمبل ImageManager از vendor ماژول لود می‌شود، در حالی که کلاس Driver v3 هنوز
     * از vendor اصلی قابل لود است → به سازندهٔ v2 آرایهٔ ['driver' => DriverObject]
     * داده می‌شد؛ v2 آرایه را می‌پذیرفت اما هنگام make() در createDriver منفجر می‌شد.
     *
     * راه‌حل قطعی: هیچ حدس نسخه‌ای در کار نیست — کاندیدها به ترتیب ساخته می‌شوند و
     * هر کاندید با «پروب» واقعی (بازکردن یک PNG یک‌پیکسلی در حافظه) اعتبارسنجی
     * می‌شود؛ هر نسخه‌ای که decode واقعی را رد کند کنار گذاشته می‌شود.
     */
    function shop_image_manager(): object
    {
        static $manager = null;

        if ($manager !== null) {
            return $manager;
        }

        // PNG یک‌پیکسلی شفاف معتبر — پروب باید decode واقعی باشد نه فقط ساخت آبجکت
        $probePng = (string) base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==',
            true
        );

        $candidates = [];

        if (class_exists(Driver::class)) {
            // v3 نهایی — سازندهٔ (string|DriverInterface)
            $candidates[] = static fn (): object => new ImageManager(new Driver);

            // v3 آلفا — سازندهٔ آرایه‌ای؛ توجه: v2 هم آرایه را می‌پذیرد ولی در
            // createDriver هنگام make() منفجر می‌شود — پروب واقعی آن را می‌گیرد
            $candidates[] = static fn (): object => new ImageManager(['driver' => new Driver]);
        }

        // v2 — درایور رشته‌ای ('gd' یا 'imagick')
        foreach (extension_loaded('imagick') ? ['imagick', 'gd'] : ['gd'] as $v2Driver) {
            $candidates[] = static fn (): object => new ImageManager(['driver' => $v2Driver]);
        }

        $constructed = null;

        foreach ($candidates as $build) {
            try {
                $candidate = $build();
            } catch (Throwable) {
                continue; // این شکل سازنده با کلاسِ لودشده نمی‌سازد
            }

            try {
                shop_image_open($candidate, $probePng);

                return $manager = $candidate; // پروب سبز — همین نسخه درست است
            } catch (Throwable) {
                $constructed ??= $candidate; // ساخته می‌شود ولی decode نمی‌کند — پشتیبان بدترین حالت
            }
        }

        // هیچ نسخه‌ای پروب را رد نکرد (مثلاً GD در دسترس نیست) — مثل قبل fail-safe
        if ($constructed !== null) {
            return $manager = $constructed;
        }

        // پناه آخر — خطای بعدی در نقطهٔ مصرف کنترل‌شده نمایش داده می‌شود
        return $manager = new ImageManager(['driver' => 'gd']);
    }
}

if (! function_exists('shop_image_open')) {
    /** بازکردن تصویر از باینری/مسیر/فایل — v3 نهایی: read() | آلفا و v2: make() */
    function shop_image_open(object $manager, mixed $input): object
    {
        if (method_exists($manager, 'read')) {
            return $manager->read($input);
        }

        return $manager->make($input);
    }
}

if (! function_exists('shop_image_scale')) {
    /** تغییر اندازه با حفظ نسبت — best-effort؛ خطا آپلود را نمی‌شکند */
    function shop_image_scale(object $image, ?int $width, ?int $height): void
    {
        if (! $width && ! $height) {
            return;
        }

        try {
            if (method_exists($image, 'scale')) {
                $image->scale($width, $height); // v3 (آلفا و نهایی)

                return;
            }

            if (method_exists($image, 'resize')) {
                // v2 — قید حفظ نسبت + عدم بزرگ‌نمایی
                $image->resize($width, $height, function ($constraint) {
                    if (method_exists($constraint, 'aspectRatio')) {
                        $constraint->aspectRatio();
                    }
                    if (method_exists($constraint, 'upsize')) {
                        $constraint->upsize();
                    }
                });
            }
        } catch (Throwable) {
            // تغییر اندازه اختیاری است — تصویر اصلی ذخیره می‌شود
        }
    }
}

if (! function_exists('shop_image_watermark')) {
    /** واترمارک — v3: place() | v2: insert() — خطا بی‌صدا نادیده گرفته می‌شود */
    function shop_image_watermark(object $image, object $manager, string $watermarkPath, string $position = 'bottom-right', int $offset = 25): void
    {
        try {
            $watermark = shop_image_open($manager, $watermarkPath);

            if (method_exists($image, 'place')) {
                $image->place($watermark, $position, $offset, $offset, 100);

                return;
            }

            if (method_exists($image, 'insert')) {
                $image->insert($watermark, $position, $offset, $offset);
            }
        } catch (Throwable) {
            // واترمارک اختیاری است — نباید آپلود را خراب کند
        }
    }
}

if (! function_exists('shop_image_encode')) {
    /**
     * انکود به فرمت/کیفیت — خروجی: [باینری, فرمت واقعی]
     * v3: انکودر آبجکتی | v2: encode(فرمت, کیفیت)
     * اگر فرمت هدف (مثل webp) پشتیبانی نشود → jpg → png → پیش‌فرض؛ آپلود هرگز نمی‌شکند
     */
    function shop_image_encode(object $image, string $format, int $quality): array
    {
        $format = strtolower($format) === 'jpeg' ? 'jpg' : strtolower($format);

        // انکودرهای آبجکتی — دو namespace برای پوشش v3 آلفا و نهایی
        $encoderCandidates = [
            'webp' => ['Intervention\\Image\\Encoders\\WebpEncoder', 'Intervention\\Image\\Drivers\\Gd\\Encoders\\WebpEncoder'],
            'jpg' => ['Intervention\\Image\\Encoders\\JpegEncoder', 'Intervention\\Image\\Drivers\\Gd\\Encoders\\JpegEncoder'],
            'png' => ['Intervention\\Image\\Encoders\\PngEncoder', 'Intervention\\Image\\Drivers\\Gd\\Encoders\\PngEncoder'],
        ];

        foreach ($encoderCandidates[$format] ?? [] as $encoderClass) {
            if (! class_exists($encoderClass)) {
                continue;
            }

            try {
                $encoder = $format === 'png' ? new $encoderClass : new $encoderClass($quality);
                $encoded = $image->encode($encoder);

                return [is_object($encoded) ? (string) $encoded : $encoded, $format];
            } catch (Throwable) {
                // سقوط به مسیر بعدی
            }
        }

        // مسیر رشته‌ای — v2 (و بعضی آلفاها)
        $tryFormats = $format === 'webp' ? ['webp', 'jpg', 'png'] : [$format, 'png'];

        foreach ($tryFormats as $try) {
            try {
                $encoded = $image->encode($try, $quality);

                return [is_object($encoded) ? (string) $encoded : $encoded, $try];
            } catch (Throwable) {
                // فرمت بعدی
            }
        }

        // آخرین پناه — انکود پیش‌فرض (فرمت ورودی حفظ می‌شود)؛ فرمت واقعی از mime خوانده می‌شود
        $encoded = $image->encode();
        $bytes = is_object($encoded) ? (string) $encoded : $encoded;

        if (is_object($encoded) && method_exists($encoded, 'mimeType')) {
            $ext = match ($encoded->mimeType()) {
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp',
                'image/gif' => 'gif',
                'image/avif' => 'avif',
                default => $format,
            };

            return [$bytes, $ext];
        }

        return [$bytes, $format];
    }
}

if (! function_exists('shop_upload_image_options')) {
    /**
     * فاز ۷۵ — ساخت تنظیمات پیش‌فرض آپلود (مشترک بین مسیر Intervention و GD خالص)
     */
    function shop_upload_image_options(string $storagePath, array $options = []): array
    {
        return array_merge([
            'optimize' => option('optimizeImage', true),
            'quality' => option('optimizeImage', 80),
            'format' => option('changePhotoFormat', 'webp'), // فرمت: webp, jpg, png
            'watermark' => option('watermarkStatus', false),
            'watermarkPath' => option('watermarkImage'),
            'watermarkImagePosition' => option('watermarkImagePosition', 'bottom-right'),
            'size' => null, // در صورت نیاز، محدودیت اندازه
            'path' => "uploads/$storagePath/", // مسیر پیش‌فرض ذخیره
            'field' => 'image', // نام فیلد پیش‌فرض
        ], $options);
    }
}

if (! function_exists('shop_delete_previous_upload')) {
    /** فاز ۷۵ — حذف تصویر قبلی مدل (مشترک بین دو مسیر آپلود) */
    function shop_delete_previous_upload(string $storagePath, $postId, array $options): void
    {
        if (! $postId) {
            return;
        }

        $model = null;
        switch ($storagePath) {
            case 'posts': $model = Post::find($postId);
                break;
            case 'products': $model = Product::find($postId);
                break;
            case 'categories': $model = Category::find($postId);
                break;
            case 'users': $model = User::find($postId);
                break;
            case 'users/avatars': $model = User::find($postId);
                break;
            case 'brands': $model = Brand::find($postId);
                break;
            case 'carriers': $model = Carrier::find($postId);
                break;
            case 'admins': $model = Admin::find($postId);
                break;
            case 'stories': $model = Story::find($postId);
                break;
            case 'sellers': $model = Seller::find($postId);
                break;
            case 'sellers/logo': $model = Seller::find($postId);
                break;
            case 'sellers/documents': $model = Seller::find($postId);
                break;
            case 'sellerInfo': $model = SellerInfo::where('seller_id', $postId)->first();
                break;
            case 'options': $model = Option::where('option_name', $postId)->first();
                break;
        }

        // حذف تصویر قبلی در صورتی که مدل موجود باشد و تصویر داشته باشد
        if ($model && $model->{$options['field']}) {
            $oldImagePath = str_replace('/uploads/', '', $model->{$options['field']});
            if (Storage::disk('public')->exists($oldImagePath)) {
                Storage::disk('public')->delete($oldImagePath);
            }
        }
    }
}

function uploadOptimizedImage($file, $storagePath, $postId = null, $options = [])
{
    // فاز ۷۵ — تلهٔ تشخیصی برای کرش‌های Fatal مربوط به Intervention (لاگ درمان‌محور)
    shop_register_intervention_trap();

    $options = shop_upload_image_options($storagePath, $options);

    // فاز ۷۷ — مسیر GD خالص «مسیرِ اصلی» آپلود است؛ Intervention فقط پشتیبانِ آخر.
    // چرا: روی هاست کاربر حتی بعد از pull، OPcache با validate_timestamps=0 کلاس‌های
    // کهنهٔ مخلوط را از حافظه سرو می‌کند — پروب متنی دیسک را سالم می‌بیند اما کلاسی
    // که واقعاً load می‌شود کهنه است و همان کرش غیرقابل‌شکار «abstract methods» را
    // می‌دهد (لاگ ۰۶:۴۶ کاربر بعد از pull فاز ۷۵). GD هیچ وابستگی به کلاس‌ها ندارد؛
    // در بدترین سرور ممکن (vendor مخلوط + OPcache کهنه) هم آپلود کار می‌کند.
    if (function_exists('imagecreatefromstring')) {
        return upload_optimized_image_via_gd($file, $storagePath, $postId, $options);
    }

    // بدون GD (فوق‌العاده نادر) — پروب فاز ۷۵ تصمیم می‌گیرد (vendor مخلوط = ذخیرهٔ خام)
    if (! shop_intervention_vendor_ok()) {
        return upload_optimized_image_via_gd($file, $storagePath, $postId, $options);
    }

    // حذف تصویر قبلی (اگر postId مشخص شده باشد)
    shop_delete_previous_upload($storagePath, $postId, $options);

    // فاز ۷۳ — ImageManager سازگار با v2 / v3 آلفا / v3 نهایی
    $manager = shop_image_manager();

    // خواندن تصویر — فاز ۷۳: read() در v3 نهایی، make() در v3 آلفا و v2
    // فاز ۷۴ — فرمت‌هایی که درایورِ فعلی سرور باز نمی‌کند (مثل webp روی GD قدیمی،
    // دقیقاً باینری «RIFF…WEBP» لاگ کاربر): فایل خام ذخیره می‌شود تا آپلود محصول
    // هرگز ۵۰۰ ندهد؛ نمایش تصویر مهم‌تر از بهینه‌سازی است.
    try {
        $image = shop_image_open($manager, $file->getContent());
    } catch (Throwable) {
        $rawExtension = strtolower($file->getClientOriginalExtension() ?: 'bin');
        $rawPath = $options['path'].str_replace('/', '-', $storagePath).'-'.uniqid().'.'.$rawExtension;

        Storage::disk('public')->put($rawPath, $file->getContent());

        return $rawPath;
    }

    if (! empty($options['size'])) {
        if (is_array($options['size']) && count($options['size']) === 2) {
            // اگر مقدار size آرایه‌ای باشد [عرض, ارتفاع]
            shop_image_scale($image, (int) $options['size'][0], (int) $options['size'][1]);
        } elseif (is_numeric($options['size'])) {
            // اگر مقدار size فقط یک عدد باشد (مثلاً 100)
            shop_image_scale($image, (int) $options['size'], (int) $options['size']);
        }
    }

    if ($options['watermark'] and $options['watermarkPath'] and file_exists(public_path($options['watermarkPath']))) {
        // اعمال واترمارک (فاز ۷۳ — سازگار با v2 و v3)
        shop_image_watermark($image, $manager, public_path($options['watermarkPath']), $options['watermarkImagePosition'], 25);
    }

    // **انکود به فرمت هدف — فاز ۷۳: اگر فرمت (مثل webp) پشتیبانی نشود خودکار
    // به jpg و سپس png سقوط می‌کند تا آپلود محصول هرگز شکست نخورد**
    [$encodedImage, $actualFormat] = shop_image_encode($image, $options['format'], (int) $options['quality']);

    // ایجاد نام منحصربه‌فرد برای فایل — با فرمت واقعیِ خروجی (سازگار با fallback انکودر)
    $modified_storagePath = str_replace('/', '-', $storagePath);
    $fileName = $modified_storagePath.'-'.uniqid().'.'.$actualFormat;
    $filePath = $options['path'].$fileName; // مسیر ذخیره در `public`

    // ذخیره تصویر در مسیر تعیین‌شده
    Storage::disk('public')->put($filePath, $encodedImage);

    // بهینه‌سازی تصویر
    if ($options['optimize']) {
        $optimizerChain = OptimizerChainFactory::create();
        $optimizerChain->optimize(public_path($filePath));
    }

    return $filePath;
}
// تابع کمکی جدید برای پردازش تصاویر ذخیره شده
function uploadOptimizedImageFromPath($imagePath, $storagePath, $postId = null, $options = [])
{
    // ایجاد یک شیء شبیه UploadedFile از مسیر فایل
    $imageContent = file_get_contents($imagePath);

    // ایجاد یک کلاس موقت که شبیه UploadedFile رفتار کند
    $tempFile = new UploadedFile(
        $imagePath,
        basename($imagePath),
        mime_content_type($imagePath),
        null,
        true // test mode
    );

    // فراخوانی تابع اصلی
    return uploadOptimizedImage($tempFile, $storagePath, $postId, $options);
}

if (! function_exists('shop_gd_fallback_encode')) {
    /**
     * فاز ۷۵ — پردازش/انکود تصویر با GD خالص — بدون بارگذاری هیچ کلاسی از Intervention
     *
     * چرا: خطای «EncodedImage contains 5 abstract methods...» سرور لایو یک
     * E_ERROR غیرقابل شکار است و در لحظهٔ autoload رخ می‌دهد؛ تنها راه نجات،
     * نرسیدن به آن کلاس‌هاست.
     *
     * خروجی: [باینری, پسوند واقعی] — یا [null, پسوند اصلی] یعنی decode نشد
     * (تماس‌گیرنده باید فایل خام را ذخیره کند — همان fail-safe فاز ۷۴)
     */
    function shop_gd_fallback_encode(string $binary, string $format, int $quality, ?int $maxWidth = null, ?int $maxHeight = null, ?string $watermarkPath = null, string $watermarkPosition = 'bottom-right', int $watermarkOffset = 25): array
    {
        $format = strtolower($format) === 'jpeg' ? 'jpg' : strtolower($format);

        if (! function_exists('imagecreatefromstring')) {
            return [null, $format]; // اصلاً GD نیست — خام ذخیره شود
        }

        $src = @imagecreatefromstring($binary);

        if ($src === false) {
            return [null, $format];
        }

        // چرخش بر اساس EXIF (بهترین تلاش — برای عکس‌های موبایلِ چرخیده)
        try {
            if (function_exists('exif_read_data') && function_exists('imagerotate')) {
                $tmp = @tempnam(sys_get_temp_dir(), 'apori');

                if ($tmp !== false) {
                    @file_put_contents($tmp, $binary);
                    $exif = @exif_read_data($tmp);
                    @unlink($tmp);

                    $orientation = (int) ($exif['Orientation'] ?? 1);
                    $angle = match ($orientation) {
                        3 => 180,
                        6 => -90,
                        8 => 90,
                        default => 0,
                    };

                    if ($angle !== 0) {
                        $rotated = @imagerotate($src, $angle, 0);

                        if ($rotated !== false) {
                            imagedestroy($src);
                            $src = $rotated;
                        }
                    }
                }
            }
        } catch (Throwable) {
            // چرخش اختیاری است
        }

        // آلفای سالم برای png/webp
        if (function_exists('imagepalettetotruecolor')) {
            @imagepalettetotruecolor($src);
        }

        if (function_exists('imagealphablending') && function_exists('imagesavealpha')) {
            @imagealphablending($src, false);
            @imagesavealpha($src, true);
        }

        // مقیاس با حفظ نسبت — فقط کوچک‌کردن (هرگز بزرگ‌نمایی نمی‌کنیم)
        if ($maxWidth || $maxHeight) {
            try {
                $w = (int) imagesx($src);
                $h = (int) imagesy($src);
                $scaleW = $maxWidth ? $maxWidth / max(1, $w) : PHP_FLOAT_MAX;
                $scaleH = $maxHeight ? $maxHeight / max(1, $h) : PHP_FLOAT_MAX;
                $scale = min($scaleW, $scaleH, 1.0);

                if ($scale < 1.0) {
                    $nw = max(1, (int) round($w * $scale));
                    $nh = max(1, (int) round($h * $scale));
                    $dst = false;

                    if (function_exists('imagescale')) {
                        $dst = @imagescale($src, $nw, $nh);
                    }

                    if ($dst === false && function_exists('imagecreatetruecolor') && function_exists('imagecopyresampled')) {
                        $dst = @imagecreatetruecolor($nw, $nh);

                        if ($dst !== false) {
                            @imagealphablending($dst, false);
                            @imagesavealpha($dst, true);
                            @imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
                        }
                    }

                    if ($dst !== false) {
                        imagedestroy($src);
                        $src = $dst;

                        if (function_exists('imagealphablending') && function_exists('imagesavealpha')) {
                            @imagealphablending($src, false);
                            @imagesavealpha($src, true);
                        }
                    }
                }
            } catch (Throwable) {
                // مقیاس اختیاری است — ابعاد اصلی ذخیره می‌شود
            }
        }

        // فاز ۷۷ — واترمارک با GD خالص (بعد از مقیاس — هم‌تراز با ترتیب مسیر Intervention:
        // مقیاس ← واترمارک ← انکود)؛ خطا = بدون واترمارک، آپلود نمی‌شکند
        if ($watermarkPath !== null && $watermarkPath !== '' && is_file($watermarkPath)) {
            $withWatermark = shop_gd_apply_watermark($src, $watermarkPath, $watermarkPosition, $watermarkOffset);

            if ($withWatermark !== null) {
                $src = $withWatermark;
            }
        }

        // انکود — فقط با توابعی که واقعاً در بیلد GD سرور وجود دارند؛
        // فرمت درخواستی → جایگزین‌های بعدی (webp → jpg → png)
        $encoders = [
            'webp' => ['imagewebp' => 'webp', 'imagejpeg' => 'jpg', 'imagepng' => 'png'],
            'jpg' => ['imagejpeg' => 'jpg', 'imagepng' => 'png'],
            'png' => ['imagepng' => 'png'],
        ];

        foreach ($encoders[$format] ?? ['imagepng' => 'png'] as $function => $ext) {
            if (! function_exists($function)) {
                continue;
            }

            $out = '';

            try {
                ob_start();
                $ok = $function === 'imagepng'
                    ? $function($src, null, min(9, max(0, (int) round((100 - max(1, min(100, $quality))) / 11))))
                    : $function($src, null, max(1, min(100, $quality)));
                $out = (string) ob_get_clean();

                if (! $ok) {
                    $out = '';
                }
            } catch (Throwable) {
                @ob_end_clean();
                $out = '';
            }

            if ($out !== '') {
                imagedestroy($src);

                return [$out, $ext];
            }
        }

        imagedestroy($src);

        return [null, $format];
    }
}

if (! function_exists('shop_gd_apply_watermark')) {
    /**
     * فاز ۷۷ — درج واترمارک با GD خالص (هم‌رفتار با shop_image_watermark مسیر Intervention)
     *
     * واترمارک (معمولاً PNG آلفادار) با imagecopy و blending سالم درج می‌شود؛
     * اگر بزرگ‌تر از تصویر بود متناسب کوچک می‌شود؛ خطا = بدون واترمارک (fail-safe).
     */
    function shop_gd_apply_watermark($src, string $watermarkPath, string $position = 'bottom-right', int $offset = 25)
    {
        try {
            $wmBinary = (string) @file_get_contents($watermarkPath);

            if ($wmBinary === '' || ! function_exists('imagecreatefromstring')) {
                return null;
            }

            $wm = @imagecreatefromstring($wmBinary);

            if ($wm === false || ! is_object($wm)) {
                return null;
            }

            if (function_exists('imagepalettetotruecolor')) {
                @imagepalettetotruecolor($wm);
            }

            if (function_exists('imagealphablending') && function_exists('imagesavealpha')) {
                @imagealphablending($wm, true);
                @imagesavealpha($wm, true);
            }

            $W = (int) imagesx($src);
            $H = (int) imagesy($src);
            $w = (int) imagesx($wm);
            $h = (int) imagesy($wm);

            if ($w > $W || $h > $H) {
                // واترمارک بزرگ‌تر از تصویر — متناسب کوچک می‌شود (هرگز بزرگ‌نمایی نمی‌کنیم)
                $scale = min($W / max(1, $w), $H / max(1, $h), 1.0);
                $nw = max(1, (int) round($w * $scale));
                $nh = max(1, (int) round($h * $scale));
                $resized = function_exists('imagecreatetruecolor') ? @imagecreatetruecolor($nw, $nh) : false;

                if ($resized !== false && function_exists('imagecopyresampled')) {
                    @imagealphablending($resized, false);
                    @imagesavealpha($resized, true);
                    @imagecopyresampled($resized, $wm, 0, 0, 0, 0, $nw, $nh, $w, $h);
                    imagedestroy($wm);
                    $wm = $resized;
                    $w = $nw;
                    $h = $nh;
                }
            }

            if (function_exists('imagealphablending')) {
                @imagealphablending($src, true);
            }

            $x = match ($position) {
                'top-left', 'bottom-left' => $offset,
                'top-right', 'bottom-right' => max(0, $W - $w - $offset),
                'center' => max(0, (int) round(($W - $w) / 2)),
                default => max(0, $W - $w - $offset),
            };

            $y = match ($position) {
                'top-left', 'top-right' => $offset,
                'bottom-left', 'bottom-right' => max(0, $H - $h - $offset),
                'center' => max(0, (int) round(($H - $h) / 2)),
                default => max(0, $H - $h - $offset),
            };

            // imagecopy (نه imagecopymerge) — آلفای خود واترمارک حفظ می‌شود
            @imagecopy($src, $wm, $x, $y, 0, 0, $w, $h);
            imagedestroy($wm);

            return $src;
        } catch (Throwable) {
            return null;
        }
    }
}

if (! function_exists('upload_optimized_image_via_gd')) {
    /**
     * فاز ۷۵ — کل مسیر آپلود با GD خالص (بدون هیچ کلاسی از Intervention)
     *
     * رفتار و خروجی دقیقاً مثل uploadOptimizedImage است:
     * حذف تصویر قبلی → decode (خطا = ذخیرهٔ خام) → مقیاس → انکود با سقوط فرمت
     * → ذخیره با پسوند واقعی → بهینه‌ساز اختیاری
     */
    function upload_optimized_image_via_gd($file, string $storagePath, $postId = null, $options = [])
    {
        $options = shop_upload_image_options($storagePath, $options);
        shop_delete_previous_upload($storagePath, $postId, $options);

        $binary = (string) $file->getContent();
        $originalExt = strtolower($file->getClientOriginalExtension() ?: 'bin');

        // اندازهٔ هدف — همان قرارداد options['size'] مسیر اصلی
        $maxWidth = $maxHeight = null;

        if (! empty($options['size'])) {
            if (is_array($options['size']) && count($options['size']) === 2) {
                $maxWidth = (int) $options['size'][0];
                $maxHeight = (int) $options['size'][1];
            } elseif (is_numeric($options['size'])) {
                $maxWidth = (int) $options['size'];
                $maxHeight = (int) $options['size'];
            }
        }

        [$encoded, $ext] = shop_gd_fallback_encode(
            $binary,
            (string) $options['format'],
            (int) $options['quality'],
            $maxWidth,
            $maxHeight,
            // فاز ۷۷ — واترمارک (هم‌شرط با مسیر Intervention: watermark + watermarkPath + وجود فایل)
            (! empty($options['watermark']) && ! empty($options['watermarkPath']) && is_file(public_path((string) $options['watermarkPath'])))
                ? public_path((string) $options['watermarkPath'])
                : null,
            (string) ($options['watermarkImagePosition'] ?? 'bottom-right')
        );

        $modified_storagePath = str_replace('/', '-', $storagePath);

        if ($encoded === null) {
            // decode نشد — فایل خام با پسوند اصلی ذخیره می‌شود؛ آپلود هرگز ۵۰۰ نمی‌دهد
            $rawPath = $options['path'].$modified_storagePath.'-'.uniqid().'.'.$originalExt;
            Storage::disk('public')->put($rawPath, $binary);

            return $rawPath;
        }

        $fileName = $modified_storagePath.'-'.uniqid().'.'.$ext;
        $filePath = $options['path'].$fileName;

        Storage::disk('public')->put($filePath, $encoded);

        if ($options['optimize']) {
            try {
                OptimizerChainFactory::create()->optimize(public_path($filePath));
            } catch (Throwable) {
                // بهینه‌ساز اختیاری است — نباید آپلود را بشکند
            }
        }

        return $filePath;
    }
}

if (! function_exists('parse_shortcodes')) {
    function parse_shortcodes($content)
    {
        return ShortcodeHelper::parse($content);
    }
}

function currencyTitle()
{

    $option = Option::where('option_name', 'default_currency_id')->first();

    $currency_id = null;
    if ($option) {
        $currency = Currency::detectLang()->find($option->option_value);
        if ($currency) {
            return $currency->title;
        }

        return 'ریال';
    }

    $value = Cache::rememberForever('options.default_currency_id', function () {
        $currency = Currency::detectLang()->find(option('default_currency_id'));

        if ($currency) {
            return $currency->title;
        }

        return 'تومان';
    });

    if (is_null($value) || $value === false) {
        return 'تومان';
    }

    return $value;
}

if (! function_exists('module_is_active')) {
    function module_is_active(string $moduleName): bool
    {
        try {
            return app('modules')->isEnabled($moduleName);
        } catch (Exception $e) {
            return false;
        }
    }
}
if (! function_exists('module_is_active')) {
    function module_is_active(string $moduleName): bool
    {
        try {
            return app('modules')->isEnabled($moduleName);
        } catch (Throwable $e) {
            return false;
        }
    }
}

if (! function_exists('module_is_ready')) {
    /**
     * چک می‌کند ماژول فعال است و (در صورت مشخص کردن) جداول حیاتی آن وجود دارند.
     *
     * @param  array  $requiredTables  اگر خالی باشد، فقط فعال بودن ماژول چک می‌شود.
     */
    function module_is_ready(string $moduleName, array $requiredTables = []): bool
    {
        if (! module_is_active($moduleName)) {
            return false;
        }

        // اگر جدولی برای چک کردن مشخص نشده، همین فعال بودن کافی است
        if (empty($requiredTables)) {
            return true;
        }

        try {
            foreach ($requiredTables as $table) {
                if (! Schema::hasTable($table)) {
                    return false;
                }
            }

            return true;
        } catch (Throwable $e) {
            return false;
        }
    }
}

if (! function_exists('ap_clean_legacy_class')) {
    /**
     * فاز ۱۶ — حذف کلاس‌های ستونِ بوت‌استرپ (col، col-6، col-md-3، col-xl-4 و…)
     * از کلاس‌های دریافتی از کانفیگ قالب/ماژول‌ها.
     *
     * چرا: تیلویند ۴ برای هر عدد یک ابزارک داینامیک col-* می‌سازد
     * (مثلاً .col-6 { grid-column: 6 }) — پس کلاس قدیمی «col-6» بوت‌استرپ در
     * گریدهای تیلویند، آیتم را به ستون ۶ می‌برد و چیدمان کارت/فرم را به‌هم می‌ریزد.
     * کلاس‌های معتبر تیلویند مثل col-span-2 دست‌نخورده می‌مانند.
     */
    function ap_clean_legacy_class(?string $class): string
    {
        if (! $class) {
            return '';
        }

        // فقط ستون‌های بوت‌استرپ: col / col-N / col-{bp} / col-{bp}-N (N ∈ 1..12)
        $clean = preg_replace(
            '/\bcol(?:-(?:1[0-2]|[1-9])|-(?:sm|md|lg|xl|xxl)(?:-(?:1[0-2]|[1-9]))?)(?=\s|$)/i',
            '',
            $class
        );

        return trim((string) preg_replace('/\s+/', ' ', (string) $clean));
    }
}
