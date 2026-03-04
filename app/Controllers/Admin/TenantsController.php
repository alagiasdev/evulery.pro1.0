<?php

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Models\Tenant;
use App\Models\User;
use App\Models\MealCategory;

class TenantsController
{
    public function index(Request $request): void
    {
        $tenants = (new Tenant())->all();

        view('admin/tenants/index', [
            'title'      => 'Ristoranti',
            'activeMenu' => 'tenants',
            'tenants'    => $tenants,
        ], 'admin');
    }

    public function create(Request $request): void
    {
        view('admin/tenants/create', [
            'title'      => 'Nuovo Ristorante',
            'activeMenu' => 'tenants',
        ], 'admin');
    }

    public function store(Request $request): void
    {
        $data = $request->all();

        $v = Validator::make($data)
            ->required('name', 'Nome')
            ->required('email', 'Email')
            ->email('email', 'Email')
            ->required('owner_email', 'Email proprietario')
            ->email('owner_email', 'Email proprietario')
            ->required('owner_password', 'Password proprietario')
            ->minLength('owner_password', 8, 'Password proprietario')
            ->required('owner_first_name', 'Nome proprietario')
            ->required('owner_last_name', 'Cognome proprietario');

        if ($v->fails()) {
            flash('danger', $v->firstError());
            \App\Core\Session::flash('old_input', $data);
            Response::redirect(url('admin/tenants/create'));
        }

        $slug = slugify($data['name']);

        // Check slug uniqueness
        $tenantModel = new Tenant();
        if ($tenantModel->findBySlug($slug)) {
            $slug .= '-' . rand(100, 999);
        }

        // Create tenant
        $tenantId = $tenantModel->create([
            'slug'      => $slug,
            'name'      => $data['name'],
            'email'     => $data['email'],
            'phone'     => $data['phone'] ?? null,
            'address'   => $data['address'] ?? null,
            'plan'      => $data['plan'] ?? 'base',
            'is_active' => isset($data['is_active']) ? 1 : 0,
        ]);

        // Seed default meal categories
        (new MealCategory())->seedDefaults($tenantId);

        // Create owner user
        $userModel = new User();
        $userModel->create([
            'tenant_id'  => $tenantId,
            'email'      => $data['owner_email'],
            'password'   => $data['owner_password'],
            'first_name' => $data['owner_first_name'],
            'last_name'  => $data['owner_last_name'],
            'role'       => 'owner',
        ]);

        flash('success', "Ristorante \"{$data['name']}\" creato con successo.");
        Response::redirect(url('admin/tenants'));
    }

    public function edit(Request $request): void
    {
        $id = (int)$request->param('id');
        $tenant = (new Tenant())->findById($id);

        if (!$tenant) {
            flash('danger', 'Ristorante non trovato.');
            Response::redirect(url('admin/tenants'));
        }

        $users = (new User())->findByTenant($id);

        view('admin/tenants/edit', [
            'title'      => 'Modifica Ristorante',
            'activeMenu' => 'tenants',
            'tenant'     => $tenant,
            'users'      => $users,
        ], 'admin');
    }

    public function update(Request $request): void
    {
        $id = (int)$request->param('id');
        $data = $request->all();

        $v = Validator::make($data)
            ->required('name', 'Nome')
            ->required('email', 'Email')
            ->email('email', 'Email');

        if ($v->fails()) {
            flash('danger', $v->firstError());
            Response::redirect(url("admin/tenants/{$id}/edit"));
        }

        (new Tenant())->update($id, [
            'name'           => $data['name'],
            'email'          => $data['email'],
            'phone'          => $data['phone'] ?? null,
            'address'        => $data['address'] ?? null,
            'plan'           => $data['plan'] ?? 'base',
            'table_duration' => $data['table_duration'] ?? 90,
            'time_step'      => $data['time_step'] ?? 30,
            'is_active'      => isset($data['is_active']) ? 1 : 0,
        ]);

        flash('success', 'Ristorante aggiornato con successo.');
        Response::redirect(url("admin/tenants/{$id}/edit"));
    }

    public function toggle(Request $request): void
    {
        $id = (int)$request->param('id');
        (new Tenant())->toggleActive($id);
        flash('success', 'Stato aggiornato.');
        Response::redirect(url('admin/tenants'));
    }
}
