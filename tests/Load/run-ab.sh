#!/bin/bash
# =====================================================================
# Apache Bench — تست بار سریع بدون نصب ابزار اضافه
# =====================================================================
# استفاده:
#   ./tests/Load/run-ab.sh https://shop.opashop.ir 1000 100
#        ^ آدرس سایت           ^تعداد کل ^همزمانی
#
# پیش‌فرض: ۱۰۰۰ درخواست با همزمانی ۱۰۰ (معادل ۱۰۰ کاربر همزمان)
# =====================================================================

set -euo pipefail

BASE_URL="${1:-http://127.0.0.1:8000}"
TOTAL="${2:-1000}"
CONCURRENCY="${3:-100}"

echo "=============================================="
echo " Apache Bench — $BASE_URL"
echo " درخواست‌ها: $TOTAL | همزمانی: $CONCURRENCY"
echo "=============================================="

for path in "/" "/login" "/search?q=test"; do
    echo ""
    echo "### مسیر: $path"
    ab -n "$TOTAL" -c "$CONCURRENCY" -t 120 "${BASE_URL}${path}" 2>&1 | \
        grep -E "Requests per second|Time per request|Failed requests|Non-2xx|Complete requests" || true
done

echo ""
echo "تمام شد. معیار کلیدی: «Requests per second» (بالاتر بهتر) و «Failed requests» (باید 0 باشد)"
