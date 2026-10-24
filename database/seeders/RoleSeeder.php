<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $permissions = Permission::pluck('id');

        /*
         * فاز ۲۸ — idempotent: نصاب هر سیدر را «در یک درخواست جدا» اجرا می‌کند؛ اگر
         * ردیاب وسط کار از دست برود و سیدر دوباره اجرا شود، create() با خطای ۱۰۶۲
         * (Duplicate entry 'مدیر کل') کل نصب را می‌کُشت. با firstOrCreate اجرای
         * مجدد امن است و دسترسی‌ها همیشه sync می‌شوند.
         */
        $role = Role::firstOrCreate([
            'title' => 'مدیر کل',
        ], [
            'description' => 'دسترسی به همه قسمت های وبسایت',
        ]);

        $role->permissions()->sync($permissions);
    }
}
