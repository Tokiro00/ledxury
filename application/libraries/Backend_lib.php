<?php

class Backend_lib {

	private $CI;
	public function __construct()
	{
		$this->CI =& get_instance();
	}

	public function control($roles = [])
	{
		// Limpiar dato de sesión corrupto por PHP 8.2 (se puede eliminar después)
		if($this->CI->session->userdata('error') === 'El usuario y/o contraseña son incorrectos') {
			$this->CI->session->unset_userdata('error');
		}

		if(!is_logged_in())
		{
			redirect(base_url().'sisvent/login');
		}

		if(!empty($roles) && !in_array($this->CI->session->userdata('user_data')['role'],$roles))
		{
			redirect(base_url()."sisvent/dashboard");
		}

		// Resolución de tenant (subdominio o fallback)
		$this->resolveTenant();

		// Actualizar last_activity para el chat (máximo cada 60s)
		$lastPing = $this->CI->session->userdata('last_activity_ping');
		if (!$lastPing || time() - $lastPing > 60) {
			$uid = $this->CI->session->userdata('user_data')['uname'];
			if ($uid) {
				$this->CI->db->where('idUser', $uid)->update('users', array('last_activity' => date('Y-m-d H:i:s')));
				$this->CI->session->set_userdata('last_activity_ping', time());
			}
		}
	}

	public function controlBotsAccess()
	{
		if(!is_logged_in())
		{
			redirect(base_url().'sisvent/login');
		}
		$ud = $this->CI->session->userdata('user_data');
		if (empty($ud['bots_access']) || $ud['bots_access'] != 1) {
			redirect(base_url()."sisvent/dashboard");
		}
		$this->resolveTenant();
	}

	public function controlModule($module_key)
	{
		if(!is_logged_in())
		{
			redirect(base_url().'sisvent/login');
		}

		$role = $this->CI->session->userdata('user_data')['role'];
		if ($role == 1 || $role == 10) {
			$this->resolveTenant();
			return;
		}

		// Recargar permisos del rol EN VIVO desde la BD: así un cambio de perfil
		// (matriz de permisos) aplica en la siguiente navegación, sin re-login.
		// También refresca el menú, que lee session('permissions').
		$this->CI->load->model('roles_model');
		$permissions = $this->CI->roles_model->getPermissions($role);
		$this->CI->session->set_userdata('permissions', $permissions);

		if (empty($permissions) || !in_array($module_key, $permissions))
		{
			redirect(base_url()."sisvent/dashboard");
		}
		$this->resolveTenant();
	}

	/**
	 * Resuelve el tenant activo desde subdominio (preferido) o desde el user (fallback legacy).
	 * Setea en sesión: tenant_id, tenant_slug, tenant_name, tenant_brand, tenant_logo.
	 *
	 * Reglas:
	 *  - Si HTTP_HOST = {slug}.pulso.{test|app|local} → cargar tenant por slug.
	 *      - Si user no pertenece a ese tenant y no es platform admin → logout.
	 *  - Si HTTP_HOST = admin.pulso.* → panel cross-tenant (solo platform admin).
	 *  - Si HTTP_HOST = localhost u otro → fallback al tenant del user (modo legacy).
	 */
	private function resolveTenant()
	{
		$userData = $this->CI->session->userdata('user_data');
		if (empty($userData)) return;

		// Hidratar user_data con tenant_id e is_platform_admin si faltan (legacy sessions)
		if (!isset($userData['tenant_id'])) {
			$row = $this->CI->db
				->select('tenant_id, is_platform_admin')
				->where('idUser', $userData['uname'])
				->get('users')->row();
			if ($row) {
				$userData['tenant_id'] = (int)$row->tenant_id;
				$userData['is_platform_admin'] = (int)$row->is_platform_admin;
				$this->CI->session->set_userdata('user_data', $userData);
			} else {
				$userData['tenant_id'] = 1;
				$userData['is_platform_admin'] = 0;
			}
		}

		$host = isset($_SERVER['HTTP_HOST']) ? strtolower($_SERVER['HTTP_HOST']) : '';
		$subdomainSlug = $this->extractSubdomainSlug($host);

		$tenant = null;

		if ($subdomainSlug === 'admin') {
			// Panel cross-tenant: solo platform admin puede entrar
			if (empty($userData['is_platform_admin'])) {
				$this->CI->session->sess_destroy();
				redirect(base_url('sisvent/login') . '?error=admin_only');
			}
			// Usar tenant ya activo en sesión, o el del user
			$activeTenantId = $this->CI->session->userdata('tenant_id') ?: $userData['tenant_id'];
			$tenant = $this->loadTenant($activeTenantId);
		}
		else if ($subdomainSlug !== null) {
			// Acceso vía subdominio Pulso (ledxury.pulso.test, mamonline.pulso.test, etc.)
			$tenant = $this->loadTenantBySlug($subdomainSlug);
			if (!$tenant) {
				show_error("Tenant '{$subdomainSlug}' no existe o está inactivo.", 404);
				return;
			}
			$isPlatformAdmin = !empty($userData['is_platform_admin']);
			if ((int)$userData['tenant_id'] !== (int)$tenant->id && !$isPlatformAdmin) {
				$this->CI->session->sess_destroy();
				redirect(base_url('sisvent/login') . '?error=tenant_mismatch');
			}
		}
		else {
			// Acceso legacy (localhost u otro) → cargar tenant del user
			$tenant = $this->loadTenant($userData['tenant_id']);
		}

		if ($tenant) {
			$this->CI->session->set_userdata([
				'tenant_id'              => (int)$tenant->id,
				'tenant_slug'            => $tenant->slug,
				'tenant_name'            => $tenant->name,
				'tenant_nit'             => $tenant->nit,
				'tenant_brand'           => $tenant->brand_primary,
				'tenant_brand_secondary' => $tenant->brand_secondary,
				'tenant_logo'            => $tenant->logo_url,
				'tenant_invoice_template' => $tenant->invoice_template,
			]);
		}
	}

	/**
	 * Extrae el slug del subdominio si el host coincide con el patrón Pulso.
	 * Retorna null si no es un host Pulso (ej: localhost).
	 *
	 * Patrones aceptados:
	 *   ledxury.pulso.test     → 'ledxury'
	 *   mamonline.pulso.app    → 'mamonline'
	 *   admin.pulso.local      → 'admin'
	 *   pulso.test             → null (sin subdominio)
	 *   localhost              → null
	 */
	private function extractSubdomainSlug($host)
	{
		// Quitar puerto si está
		$host = preg_replace('/:\d+$/', '', $host);
		if (preg_match('/^([a-z0-9][a-z0-9-]*)\.pulso\.(test|app|local|dev)$/i', $host, $m)) {
			return strtolower($m[1]);
		}
		return null;
	}

	private function loadTenant($id)
	{
		return $this->CI->db
			->where('id', (int)$id)
			->where('active', 1)
			->get('tenants')->row();
	}

	private function loadTenantBySlug($slug)
	{
		return $this->CI->db
			->where('slug', $slug)
			->where('active', 1)
			->get('tenants')->row();
	}
}
