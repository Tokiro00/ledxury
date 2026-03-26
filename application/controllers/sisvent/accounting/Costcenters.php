<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Centros de Costo (Cost Centers)
 * CRUD for cost centers with hierarchical structure
 */
class Costcenters extends CI_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->backend_lib->controlModule('centros_costo');
		$this->load->model("stores_model");
	}

	public function index()
	{
		$costCenters = $this->db->select('cost_centers.*, stores.name as store_name, parent.name as parent_name')
			->from('cost_centers')
			->join('stores', 'stores.idStore = cost_centers.store_id', 'left')
			->join('cost_centers parent', 'parent.id = cost_centers.parent_id', 'left')
			->where('cost_centers.deleted', 0)
			->order_by('cost_centers.code', 'asc')
			->get()->result();

		$data = array(
			'costCenters' => $costCenters,
			'stores' => $this->stores_model->getStores(),
		);
		$this->load->view("sisvent/accounting/costcenters/index", $data);
	}

	/**
	 * Save a new cost center
	 */
	public function save()
	{
		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit;

		$code = $this->input->post('code');
		$name = $this->input->post('name');
		$type = $this->input->post('type');
		$storeId = $this->input->post('store_id');
		$parentId = $this->input->post('parent_id') ?: null;
		$description = $this->input->post('description');

		$this->form_validation->set_rules('code', 'Codigo', 'required');
		$this->form_validation->set_rules('name', 'Nombre', 'required');
		$this->form_validation->set_rules('type', 'Tipo', 'required');

		if ($this->form_validation->run()) {
			// Check if code already exists
			$exists = $this->db->from('cost_centers')
				->where('code', $code)
				->where('deleted', 0)
				->count_all_results();

			if ($exists > 0) {
				$this->session->set_flashdata('error', 'Ya existe un centro de costo con el codigo ' . $code);
				redirect(base_url() . 'sisvent/accounting/costcenters');
				return;
			}

			date_default_timezone_set("America/Bogota");
			$data = array(
				'code' => $code,
				'name' => $name,
				'type' => $type,
				'store_id' => $storeId,
				'parent_id' => $parentId,
				'description' => $description,
				'status' => 'activo',
				'created_at' => date('Y-m-d H:i:s'),
				'updated_at' => date('Y-m-d H:i:s'),
				'deleted' => 0
			);

			if ($this->db->insert('cost_centers', $data)) {
				$userId = $this->session->userdata('user_data')['uname'];
				$this->logs_model->logMessage("info", "Usuario $userId creo centro de costo: $name ($code)");
				$this->session->set_flashdata('success', 'Centro de costo creado exitosamente.');
			} else {
				$this->session->set_flashdata('error', 'No se pudo crear el centro de costo.');
			}
		} else {
			$this->session->set_flashdata('error', validation_errors());
		}

		redirect(base_url() . 'sisvent/accounting/costcenters');
	}

	/**
	 * Update a cost center
	 */
	public function update()
	{
		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit;

		$id = $this->input->post('id');
		$code = $this->input->post('code');
		$name = $this->input->post('name');
		$type = $this->input->post('type');
		$storeId = $this->input->post('store_id');
		$parentId = $this->input->post('parent_id') ?: null;
		$description = $this->input->post('description');
		$status = $this->input->post('status');

		$this->form_validation->set_rules('code', 'Codigo', 'required');
		$this->form_validation->set_rules('name', 'Nombre', 'required');

		if ($this->form_validation->run()) {
			// Check if code already exists (excluding current record)
			$exists = $this->db->from('cost_centers')
				->where('code', $code)
				->where('id !=', $id)
				->where('deleted', 0)
				->count_all_results();

			if ($exists > 0) {
				$this->session->set_flashdata('error', 'Ya existe un centro de costo con el codigo ' . $code);
				redirect(base_url() . 'sisvent/accounting/costcenters');
				return;
			}

			date_default_timezone_set("America/Bogota");
			$data = array(
				'code' => $code,
				'name' => $name,
				'type' => $type,
				'store_id' => $storeId,
				'parent_id' => $parentId,
				'description' => $description,
				'status' => $status ?: 'activo',
				'updated_at' => date('Y-m-d H:i:s')
			);

			$this->db->where('id', $id);
			if ($this->db->update('cost_centers', $data)) {
				$this->session->set_flashdata('success', 'Centro de costo actualizado exitosamente.');
			} else {
				$this->session->set_flashdata('error', 'No se pudo actualizar el centro de costo.');
			}
		} else {
			$this->session->set_flashdata('error', validation_errors());
		}

		redirect(base_url() . 'sisvent/accounting/costcenters');
	}

	/**
	 * Soft delete a cost center
	 */
	public function delete($id)
	{
		$this->outh_model->CSRFVerify();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') exit;

		date_default_timezone_set("America/Bogota");
		$this->db->where('id', $id);
		$this->db->update('cost_centers', array(
			'deleted' => 1,
			'deleted_at' => date('Y-m-d H:i:s')
		));

		echo base_url() . 'sisvent/accounting/costcenters';
	}
}
