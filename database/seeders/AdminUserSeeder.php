<?php
namespace Database\Seeders;
use App\Models\User; use Illuminate\Database\Seeder; use Illuminate\Support\Facades\Hash;
class AdminUserSeeder extends Seeder { public function run(): void {
    $admin = User::firstOrCreate(['email'=>'admin@lpabonnements.fr'],['name'=>'Administrateur','first_name'=>'Admin','last_name'=>'LPA','password'=>Hash::make('Admin2024!'),'is_active'=>true,'email_verified_at'=>now()]);
    $admin->assignRole('admin');
}}
