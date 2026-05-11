<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder; use Spatie\Permission\Models\Role; use Spatie\Permission\Models\Permission;
class RolePermissionSeeder extends Seeder { public function run(): void {
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    foreach(['view_magazines','create_magazines','edit_magazines','delete_magazines','view_clients','create_clients','edit_clients','delete_clients','view_subscriptions','create_subscriptions','edit_subscriptions','delete_subscriptions','view_invoices','create_invoices','edit_invoices','delete_invoices','view_contacts','edit_contacts','delete_contacts','view_reports','manage_settings','manage_users'] as $p) Permission::firstOrCreate(['name'=>$p]);
    $admin = Role::firstOrCreate(['name'=>'admin']); $admin->givePermissionTo(Permission::all());
    Role::firstOrCreate(['name'=>'director']); Role::firstOrCreate(['name'=>'manager']); Role::firstOrCreate(['name'=>'accountant']); Role::firstOrCreate(['name'=>'client']);
}}
