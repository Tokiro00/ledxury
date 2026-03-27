<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Roles_model extends CI_Model {

	/**
	 * Obtener todos los roles activos con conteo de permisos.
	 */
	public function getRoles(){
		$this->db->select('roles.*, (SELECT COUNT(*) FROM role_permissions WHERE role_permissions.role_id = roles.idRoles) as permission_count');
		$this->db->from('roles');
		$this->db->where('roles.deleted', 0);
		$this->db->order_by('roles.idRoles', 'ASC');
		return $this->db->get()->result();
	}

	/**
	 * Obtener un rol por ID.
	 */
	public function getRole($id){
		$this->db->from('roles');
		$this->db->where('idRoles', $id);
		$this->db->where('deleted', 0);
		return $this->db->get()->row();
	}

	/**
	 * Guardar nuevo rol.
	 */
	public function save($data){
		date_default_timezone_set("America/Bogota");
		$data['created_at'] = date('Y-m-d H:i:s');
		$data['updated_at'] = date('Y-m-d H:i:s');
		return $this->db->insert('roles', $data);
	}

	/**
	 * Actualizar rol existente.
	 */
	public function update($id, $data){
		date_default_timezone_set("America/Bogota");
		$data['updated_at'] = date('Y-m-d H:i:s');
		$this->db->where('idRoles', $id);
		return $this->db->update('roles', $data);
	}

	/**
	 * Soft delete de un rol.
	 */
	public function remove($id){
		date_default_timezone_set("America/Bogota");
		$data = array(
			'deleted' => 1,
			'deleted_at' => date('Y-m-d H:i:s')
		);
		$this->db->where('idRoles', $id);
		return $this->db->update('roles', $data);
	}

	/**
	 * Obtener todos los module_keys asignados a un rol.
	 */
	public function getPermissions($roleId){
		$this->db->select('module_key');
		$this->db->from('role_permissions');
		$this->db->where('role_id', $roleId);
		$result = $this->db->get()->result();
		$permissions = array();
		foreach($result as $row){
			$permissions[] = $row->module_key;
		}
		return $permissions;
	}

	/**
	 * Alias para compatibilidad con Login_model.
	 */
	public function getRolePermissions($roleId){
		return $this->getPermissions($roleId);
	}

	/**
	 * Guardar permisos de un rol: eliminar existentes e insertar nuevos.
	 */
	public function savePermissions($roleId, $permissions = array()){
		date_default_timezone_set("America/Bogota");
		// Eliminar permisos existentes
		$this->db->where('role_id', $roleId);
		$this->db->delete('role_permissions');

		// Insertar nuevos permisos
		if(!empty($permissions)){
			$batch = array();
			foreach($permissions as $moduleKey){
				$batch[] = array(
					'role_id' => $roleId,
					'module_key' => $moduleKey,
					'created_at' => date('Y-m-d H:i:s')
				);
			}
			$this->db->insert_batch('role_permissions', $batch);
		}
		return true;
	}

	/**
	 * Retorna todos los module_keys disponibles con etiquetas en espanol,
	 * organizados por seccion.
	 */
	public function getAllModuleKeys(){
		return array(
			'VENTAS' => array(
				'presupuestos' => 'Presupuestos',
				'aprobar_presupuestos' => 'Aprobar Presupuestos',
				'facturas' => 'Facturas',
				'devoluciones' => 'Devoluciones y Garantias',
				'cobro_juridico' => 'Cobro Juridico',
				'editar_precios' => 'Editar Precios',
				'vendedores' => 'Vendedores',
				'clientes' => 'Clientes',
				'clientes_agregar' => 'Agregar Clientes',
			),
			'COMPRAS' => array(
				'gastos' => 'Gastos Operacionales',
				'cuentas_pagar' => 'Cuentas por Pagar Proveedores',
				'pagos_proveedor' => 'Pagos a Proveedores',
				'categorias_gastos' => 'Categorias de Gastos',
				'compras_reorden' => 'ABC y Ordenes de Compra',
			),
			'INVENTARIO' => array(
				'inventario' => 'Stock por Bodega',
				'traspasos' => 'Traspasos entre Bodegas',
				'conteos' => 'Conteos Fisicos',
				'fotos_masivo' => 'Carga Masiva de Fotos',
			),
			'TESORERIA' => array(
				'caja_bancos' => 'Cajas y Bancos',
				'movimientos_caja' => 'Movimientos de Caja/Banco',
				'cierres_caja' => 'Cierres de Caja',
				'conciliaciones' => 'Conciliaciones Bancarias',
			),
			'CARTERA' => array(
				'cartera' => 'Cuentas por Cobrar',
				'estado_cuenta' => 'Estado de Cuenta Clientes',
				'liquidaciones' => 'Liquidaciones Vendedores',
			),
			'CONTABILIDAD' => array(
				'contabilidad' => 'Modulo Contable',
				'plan_cuentas' => 'Plan de Cuentas PUC',
				'apertura' => 'Apertura de Balance',
				'centros_costo' => 'Centros de Costo',
				'periodos' => 'Periodos Contables',
			),
			'REPORTES' => array(
				'reportes_ventas' => 'Reportes de Ventas',
				'reportes_contables' => 'Reportes Contables',
				'reportes_avanzados' => 'Reportes Avanzados',
				'reporte_cartera' => 'Reporte de Cartera',
				'reporte_vendedores' => 'Rendimiento Vendedores',
				'reporte_abc' => 'Clientes ABC',
			),
			'ENVIOS' => array(
				'envios' => 'Dashboard de Envios',
			),
			'CONFIGURACION' => array(
				'usuarios' => 'Gestion de Usuarios',
				'roles_permisos' => 'Roles y Permisos',
				'tiendas' => 'Gestion de Almacenes',
				'config_contable' => 'Configuracion Contable',
				'departamentos' => 'Departamentos y KPIs',
				'importar_datos' => 'Importar Datos Masivo',
				'metodos_pago' => 'Metodos de Pago',
			),
			'HERRAMIENTAS' => array(
				'asistente_ia' => 'Asistente IA',
				'pwa_vendedores' => 'App Movil Vendedores',
			),
		);
	}
}
