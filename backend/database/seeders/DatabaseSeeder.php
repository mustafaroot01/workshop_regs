<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Interest;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create admin user
        User::updateOrCreate(
            ['email' => 'admin@workshop.com'],
            [
                'name'     => 'المشرف',
                'email'    => 'admin@workshop.com',
                'password' => Hash::make('admin123'),
            ]
        );

        // Default departments
        $departments = ['علوم الحاسوب', 'هندسة المعلومات', 'الشبكات', 'نظم المعلومات'];
        foreach ($departments as $dept) {
            Department::firstOrCreate(['name' => $dept]);
        }

        // Default interests
        $interests = ['البرمجة', 'الذكاء الاصطناعي', 'تصميم المواقع', 'الأمن السيبراني', 'تطوير التطبيقات'];
        foreach ($interests as $interest) {
            Interest::firstOrCreate(['name' => $interest]);
        }
    }
}
