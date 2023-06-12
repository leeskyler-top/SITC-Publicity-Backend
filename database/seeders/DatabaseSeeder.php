<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // \App\Models\User::factory(10)->create();

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        User::create([
                'is_admin' => "1",
                'uid' => '22100484',
                'name' => '李天成',
                'email' => '22100484@shitac.net',
                'password' => '22100484',
                'department' => '信息技术系',
                'classname' => '214L01',
                'note' => '团委学生会宣传部编外人员，生活部部员，系统运维人员',
        ]);
    }
}
