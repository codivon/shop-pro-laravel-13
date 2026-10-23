// =====================================================================
// k6 Load Test — فروشگاه (تست بار واقعی ۱۰۰۰ کاربر همزمان)
// =====================================================================
// نصب k6:  https://k6.io/docs/get-started/installation/
//   sudo apt install k6        (دبیان/اوبونتو)
//   brew install k6            (مک)
//
// اجرا روی ۱۰۰۰ کاربر همزمان:
//   k6 run -e BASE_URL=https://shop.opashop.ir tests/Load/k6-shop-load.js
//
// سناریوی سفارشی (مثلاً ۵۰۰۰ کاربر):
//   k6 run -e BASE_URL=https://shop.opashop.ir -e MAX_VUS=5000 tests/Load/k6-shop-load.js
// =====================================================================

import http from 'k6/http';
import { check, sleep } from 'k6';

const BASE_URL = __ENV.BASE_URL || 'http://127.0.0.1:8000';
const MAX_VUS = parseInt(__ENV.MAX_VUS || '1000', 10); // ۱۰۰۰ کاربر همزمان

export const options = {
  // الگوی ترافیک: شروع آرام → اوج (روز حراج) → پایین آمدن
  stages: [
    { duration: '1m',  target: Math.round(MAX_VUS * 0.25) }, // گرم شدن: ۲۵٪
    { duration: '2m',  target: Math.round(MAX_VUS * 0.50) }, // ۵۰٪
    { duration: '2m',  target: MAX_VUS },                     // اوج: ۱۰۰٪
    { duration: '3m',  target: MAX_VUS },                     // تداوم اوج
    { duration: '1m',  target: 0 },                           // خروج
  ],
  thresholds: {
    // آستانه‌های SLO — اگر رد شوند k6 کد خروج غیرصفر می‌دهد
    http_req_failed:   ['rate<0.01'],               // کمتر از ۱٪ خطا
    http_req_duration: ['p(95)<3000'],              // ۹۵٪ درخواست‌ها زیر ۳ ثانیه
    http_req_duration: ['p(99)<5000'],              // ۹۹٪ زیر ۵ ثانیه
  },
};

// ترکیب رفتار کاربر واقعی: ۷۰٪ مرور، ۲۰٪ جستجو، ۱۰٪ ورود
export default function () {
  const roll = Math.random();

  if (roll < 0.7) {
    // مرور صفحه‌ی اصلی و لیست محصولات
    const res = http.get(`${BASE_URL}/`);
    check(res, { 'home 2xx': (r) => r.status >= 200 && r.status < 400 });
    sleep(2 + Math.random() * 3);

    http.get(`${BASE_URL}/search`);
    check(res, { 'search 2xx': (r) => r.status >= 200 && r.status < 400 });
  } else if (roll < 0.9) {
    // جستجوی محصول
    const q = encodeURIComponent(['گوشی', 'لپ‌تاپ', 'هدفون', 'ساعت'][Math.floor(Math.random() * 4)]);
    const res = http.get(`${BASE_URL}/search?q=${q}`);
    check(res, { 'query 2xx': (r) => r.status >= 200 && r.status < 400 });
  } else {
    // ورود (صفحه لاگین — بدون creds واقعی)
    const res = http.get(`${BASE_URL}/login`);
    check(res, { 'login 2xx': (r) => r.status >= 200 && r.status < 400 });
  }

  sleep(1 + Math.random() * 2); // زمان فکر کردن کاربر
}
