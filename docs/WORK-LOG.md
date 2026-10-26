# Work Log — CustomerClub

## فاز ۹۰ — تولد پکیج (v1.0.0)
- بازسازی کامل از قرارداد تست `Phase90Test` (۱۷ تست/۱۳۴ assertion) پس از ریست سندباکس؛ مخزن مستقل `shop-pro-CustomerClub-module` از قبل ساخته نشده بود.
- ساختار: الگوی MarketingMessaging v1.3.1 (provider register→EventServiceProvider؛ درس باگ singleton) + LicenseGuard سرخودکفا + ActivePanel + قرارداد InstallPermissions.
- الزام جدید کاربر (فاز ۹۰+): «هر امکان از تنظیمات کنترل شود» → ۵ کلید مستقل + `is_active` هر قانون + enforcement در Listenerها/کنترلرها/کامندها + منوها.
- self-heal: `LoyaltyRule::for()` firstOrCreate + `ReferralTier::ensureSeeded()` در boot — بدون کامند هم seed تضمین است.
- بدون پچ هسته: relationهای لازم با زیرکوئری (بجای whereHas روی مدل User) و Observer پکیجی.
