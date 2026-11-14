<?php

namespace Database\Seeders;

use App\Models\Province;
use App\Models\Ward;
use Illuminate\Database\Seeder;

/** Nạp 34 tỉnh/thành và các phường/xã (dữ liệu sau sáp nhập 01/07/2025, nguồn provinces.open-api.vn v2). */
class ProvinceSeeder extends Seeder
{
    public function run(): void
    {
        $units = json_decode(file_get_contents(__DIR__.'/data/vn-units.json'), true);
        $wards = [];

        foreach ($units as $unit) {
            foreach ($unit['wards'] as $ward) {
                $wards[] = ['code' => $ward['code'], 'province_code' => $unit['code'], 'name' => $ward['name']];
            }
        }

        Province::upsert(array_map(fn ($u) => ['code' => $u['code'], 'name' => $u['name']], $units), ['code'], ['name']);

        foreach (array_chunk($wards, 500) as $chunk) {
            Ward::upsert($chunk, ['code'], ['province_code', 'name']);
        }
    }
}
