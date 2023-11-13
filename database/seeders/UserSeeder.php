<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $admin = new User();
        $admin->role_id = 1;
        $admin->first_name = 'Icot';
        $admin->last_name = 'Solutions';
        $admin->email = 'icotstest@gmail.com';
        $admin->password = Hash::make(12345678);
        $admin->save();
    }
}
