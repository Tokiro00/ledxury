<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Roles extends CI_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->backend_lib->control([1, 10]);
		$this->load->model('roles_model');
	}

	/**
	 * Listado de roles.
	 */
	public function index()
	{
		$data = array(
			'roles' => $this->roles_model->getRoles()
		);
		$this->load->view('sisvent/business/roles/list', $data);
	}

	/**
	 * Formulario para agregar rol.
	 */
	public function add()
	{
		$this->load->view('sisvent/business/roles/add');
	}

	/**
	 * Guardar nuevo rol (POST).
	 */
	public function store()
	{
		$this->outh_model->CSRFVerify();
		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit;

		$this->form_validation->set_rules('name', 'Nombre', 'required|max_length[100]');
		$this->form_validation->set_rules('description', 'Descripcion', 'max_length[255]');

		if ($this->form_validation->run()) {
			$data = array(
				'name' => $this->input->post('name'),
				'description' => $this->input->post('description'),
				'puc_code' => $this->input->post('puc_code')
			);
			if ($this->roles_model->save($data)) {
				$this->session->set_flashdata('success', 'Rol creado exitosamente');
				redirect(base_url() . 'sisvent/business/roles');
			} else {
				$this->session->set_flashdata('error', 'No se pudo crear el rol');
				$this->add();
			}
		} else {
			$this->add();
		}
	}

	/**
	 * Formulario para editar rol.
	 */
	public function edit($id)
	{
		$role = $this->roles_model->getRole($id);
		if (!$role) {
			redirect(base_url() . 'sisvent/business/roles');
		}
		$data = array(
			'role' => $role
		);
		$this->load->view('sisvent/business/roles/edit', $data);
	}

	/**
	 * Actualizar rol (POST).
	 */
	public function update($id)
	{
		$this->outh_model->CSRFVerify();
		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit;

		$this->form_validation->set_rules('name', 'Nombre', 'required|max_length[100]');
		$this->form_validation->set_rules('description', 'Descripcion', 'max_length[255]');

		if ($this->form_validation->run()) {
			$data = array(
				'name' => $this->input->post('name'),
				'description' => $this->input->post('description'),
				'puc_code' => $this->input->post('puc_code')
			);
			if ($this->roles_model->update($id, $data)) {
				$this->session->set_flashdata('success', 'Rol actualizado exitosamente');
				redirect(base_url() . 'sisvent/business/roles');
			} else {
				$this->session->set_flashdata('error', 'No se pudo actualizar el rol');
				$this->edit($id);
			}
		} else {
			$this->edit($id);
		}
	}

	/**
	 * Gestion de permisos de un rol.
	 */
	public function permissions($id)
	{
		$role = $this->roles_model->getRole($id);
		if (!$role) {
			redirect(base_url() . 'sisvent/business/roles');
		}

		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			$this->outh_model->CSRFVerify();
			$permissions = $this->input->post('permissions');
			if (!is_array($permissions)) {
				$permissions = array();
			}
			$this->roles_model->savePermissions($id, $permissions);
			$this->session->set_flashdata('success', 'Permisos actualizados para el rol: ' . $role->name);
			redirect(base_url() . 'sisvent/business/roles');
		}

		$data = array(
			'role' => $role,
			'currentPermissions' => $this->roles_model->getPermissions($id),
			'allModuleKeys' => $this->roles_model->getAllModuleKeys()
		);
		$this->load->view('sisvent/business/roles/permissions', $data);
	}

	/**
	 * Matriz de permisos - todos los roles y modulos en una sola vista.
	 */
	public function matrix()
	{
		$roles = $this->roles_model->getRoles();
		$allModuleKeys = $this->roles_model->getAllModuleKeys();

		// Get permissions for each role
		$permissionsByRole = array();
		foreach ($roles as $role) {
			$permissionsByRole[$role->idRoles] = $this->roles_model->getPermissions($role->idRoles);
		}

		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			$this->outh_model->CSRFVerify();
			// Save permissions for all roles
			foreach ($roles as $role) {
				if ($role->idRoles == 1) continue; // Skip superadmin
				$perms = $this->input->post('perms_' . $role->idRoles);
				if (!is_array($perms)) $perms = array();
				$this->roles_model->savePermissions($role->idRoles, $perms);
			}
			$this->session->set_flashdata('success', 'Permisos actualizados correctamente');
			redirect(base_url() . 'sisvent/business/roles/matrix');
		}

		$data = array(
			'roles' => $roles,
			'allModuleKeys' => $allModuleKeys,
			'permissionsByRole' => $permissionsByRole
		);
		$this->load->view('sisvent/business/roles/matrix', $data);
	}

	/**
	 * Eliminar rol (soft delete).
	 */
	public function remove($id)
	{
		$this->outh_model->CSRFVerify();
		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit;

		// No permitir eliminar rol superadmin (id=1)
		if ($id == 1) {
			$this->session->set_flashdata('error', 'No se puede eliminar el rol de superadmin');
			redirect(base_url() . 'sisvent/business/roles');
		}

		$this->roles_model->remove($id);
		echo base_url() . 'sisvent/business/roles';
	}
}
