<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Tenants — Gestión de empresas/tenants en la plataforma Pulso.
 * Solo accesible para platform admin (users.is_platform_admin = 1).
 */
class Tenants extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->backend_lib->control([1]); // solo role 1
        $this->load->model('tenants_model');
        $this->load->helper('form');
    }

    private function requirePlatformAdmin()
    {
        $ud = $this->session->userdata('user_data');
        if (empty($ud['is_platform_admin'])) {
            show_error('Solo el administrador de plataforma puede acceder a esta sección.', 403);
            exit;
        }
    }

    public function index()
    {
        $this->requirePlatformAdmin();
        $data = array();
        $data['role'] = $this->session->userdata('user_data')['role'];
        $data['tenants'] = $this->tenants_model->all(true);
        $data['countsByTenant'] = array();
        foreach ($data['tenants'] as $t) {
            $data['countsByTenant'][$t->id] = $this->tenants_model->counts($t->id);
        }
        $this->load->view('sisvent/admin/tenants/list', $data);
    }

    public function edit($id = null)
    {
        $this->requirePlatformAdmin();
        $data = array();
        $data['role'] = $this->session->userdata('user_data')['role'];
        $data['tenant'] = $id ? $this->tenants_model->find($id) : null;
        if ($id && !$data['tenant']) show_404();
        $this->load->view('sisvent/admin/tenants/edit', $data);
    }

    public function save()
    {
        $this->requirePlatformAdmin();
        $this->outh_model->CSRFVerify();

        $id = (int)$this->input->post('id');
        $slug = strtolower(trim($this->input->post('slug')));
        if (!preg_match('/^[a-z0-9][a-z0-9-]*$/', $slug)) {
            $this->session->set_flashdata('error', 'Slug inválido (solo letras minúsculas, números y guiones).');
            redirect(base_url('sisvent/admin/tenants/edit/' . ($id ?: '')));
        }

        // Validar slug único
        $existing = $this->tenants_model->findBySlug($slug);
        if ($existing && (int)$existing->id !== $id) {
            $this->session->set_flashdata('error', "Slug '{$slug}' ya está en uso.");
            redirect(base_url('sisvent/admin/tenants/edit/' . ($id ?: '')));
        }

        $data = array(
            'slug'                 => $slug,
            'name'                 => trim($this->input->post('name')),
            'nit'                  => trim($this->input->post('nit')) ?: null,
            'razon_social'         => trim($this->input->post('razon_social')) ?: null,
            'inter_sucursal_id'    => (int)$this->input->post('inter_sucursal_id') ?: null,
            'inter_pickup_address' => trim($this->input->post('inter_pickup_address')) ?: null,
            'inter_pickup_city'    => trim($this->input->post('inter_pickup_city')) ?: null,
            'brand_primary'        => trim($this->input->post('brand_primary')) ?: '#FF5A36',
            'brand_secondary'      => trim($this->input->post('brand_secondary')) ?: '#FFF7EE',
            'logo_url'             => trim($this->input->post('logo_url')) ?: null,
            'invoice_template'     => trim($this->input->post('invoice_template')) ?: 'pulso',
            'invoice_account'      => $this->input->post('invoice_account') ?: null,
            'invoice_support'      => $this->input->post('invoice_support') ?: null,
            'bot_enabled'          => $this->input->post('bot_enabled') ? 1 : 0,
            'active'               => $this->input->post('active') ? 1 : 0,
        );

        if ($id) {
            $this->tenants_model->update($id, $data);
            $this->session->set_flashdata('success', "Tenant '{$data['name']}' actualizado.");
        } else {
            $newId = $this->tenants_model->create($data);
            $this->session->set_flashdata('success', "Tenant '{$data['name']}' creado (id {$newId}).");
        }

        redirect(base_url('sisvent/admin/tenants'));
    }

    /**
     * Tenant switcher para platform admin: cambiar el tenant_id en sesión.
     * Solo afecta la sesión actual, no a la columna users.tenant_id del platform admin.
     */
    public function switch_to($tenantId)
    {
        $this->requirePlatformAdmin();
        $tenantId = (int)$tenantId;
        $tenant = $this->tenants_model->find($tenantId);
        if (!$tenant || !$tenant->active) {
            show_error('Tenant no disponible.', 404);
            exit;
        }
        $this->session->set_userdata(array(
            'tenant_id'              => (int)$tenant->id,
            'tenant_slug'            => $tenant->slug,
            'tenant_name'            => $tenant->name,
            'tenant_nit'             => $tenant->nit,
            'tenant_brand'           => $tenant->brand_primary,
            'tenant_brand_secondary' => $tenant->brand_secondary,
            'tenant_logo'            => $tenant->logo_url,
            'tenant_invoice_template' => $tenant->invoice_template,
        ));
        $this->session->set_flashdata('success', "Cambiado a {$tenant->name}.");
        redirect(base_url('sisvent/dashboard'));
    }
}
