<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public $ordering = 1;
    public $ids = [];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $permissions = config('general.permissions');

        foreach ($permissions as $name => $value) {
            $this->create($name, $value);
        }

        // فاز ۱۳۲ — فقط پرمیژن‌های «هسته» که دیگر در config نیستند حذف می‌شوند.
        //
        // باگ: این seeder بعد از هر آپدیت (updater:after) اجرا می‌شد و
        // Permission::whereNotIn('id', ...)->delete() همهٔ پرمیژن‌های
        // پکیج‌ها (smart-assembly.* ، seo.* ، ai.* و…) را پاک می‌کرد —
        // درحالی‌که لاگ نصب پکیج «✅ پرمیژن نصب شد» چاپ می‌کرد. نتیجه:
        // «پرمیژن‌ها وارد جدول نمی‌شوند» + ناپدیدشدن منوی پکیج‌ها (گشت can:).
        //
        // راه‌حل: حذف فقط برای نام‌هایی انجام شود که ریشهٔ (segment اول)
        // آن‌ها یکی از ریشه‌های پرمیژن‌های هسته در همین config است —
        // پرمیژن‌های ماژول‌ها ریشهٔ خارج از این مجموعه دارند و محفوظ می‌مانند.
        $coreRoots = [];
        foreach (array_keys($permissions) as $name) {
            $root = strtok($name, '.');
            if ($root !== false && $root !== '') {
                $coreRoots[$root] = true;
            }
        }
        $coreRoots = array_keys($coreRoots);

        Permission::query()
            ->whereNotIn('id', $this->ids)
            ->where(function ($query) use ($coreRoots) {
                $query->whereIn('name', $coreRoots);
                foreach ($coreRoots as $root) {
                    $query->orWhere('name', 'like', $root.'.%');
                }
            })
            ->delete();
    }

    private function create($name, $value, $permission_id = null)
    {
        if (is_array($value)) {

            $permission = Permission::updateOrCreate(
                [
                    'name' => $name
                ],
                [
                    'title'         => $value['title'],
                    'ordering'      => $this->ordering++,
                    'permission_id' => $permission_id
                ]
            );

            $this->ids[] = $permission->id;

            foreach ($value['values'] as $n => $val) {
                $this->create($name . '.' . $n, $val, $permission->id);
            }
        } else {
            $permission = Permission::updateOrCreate(
                [
                    'name' => $name
                ],
                [
                    'title'         => $value,
                    'ordering'      => $this->ordering++,
                    'permission_id' => $permission_id
                ]
            );

            $this->ids[] = $permission->id;
        }
    }
}
