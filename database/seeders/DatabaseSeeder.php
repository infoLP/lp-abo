<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
class DatabaseSeeder extends Seeder { public function run(): void { $this->call([
            AccountingSeeder::class,RolePermissionSeeder::class, AdminUserSeeder::class, MagazineSeeder::class]); } }
