<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/** Tài khoản nhân sự demo, mật khẩu đều là `matkhau123`. */
class StaffSeeder extends Seeder
{
    public function run(): void
    {
        $people = [
            ['owner@demo.test', 'Chủ shop Hà', 'owner'],
            ['nhanvien1@demo.test', 'Nhân viên Minh', 'staff'],
            ['nhanvien2@demo.test', 'Nhân viên Lan', 'staff'],
        ];

        foreach ($people as [$email, $name, $role]) {
            User::updateOrCreate(['email' => $email], [
                'name' => $name, 'password' => 'matkhau123', 'role' => $role, 'is_active' => true,
            ]);
        }
    }
}
