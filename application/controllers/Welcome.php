<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Welcome extends CI_Controller {

	public function index()
	{
		// Si la sesión está activa, redirigir al panel interno
		if (is_logged_in()) {
			redirect(base_url() . 'sisvent/dashboard');
			return;
		}
		// Mostrar landing pública con CTA a la tienda y botón Ingresar
		$this->load->model('Tienda_model');
		$catalog = $this->Tienda_model->get_catalog();

		// Hero: priorizar MÓDULOS LED (códigos 3LED-, 6LED-, 12LED-, 2835-) variando colores
		$modulePatterns = array('/^3LED-/i', '/^6LED-/i', '/^12LED-/i', '/^2835-/i');
		$moduleProducts = array();
		foreach ($catalog['families'] as $fam) {
			foreach ($fam['products'] as $p) {
				foreach ($modulePatterns as $pat) {
					if (preg_match($pat, $p['id'])) {
						$moduleProducts[] = $p;
						break;
					}
				}
			}
		}
		// Tomar uno de cada familia-voltaje para mostrar variedad (ej: un 3LED-12V, un 6LED-12V, etc)
		$seen = array();
		$featured = array();
		foreach ($moduleProducts as $p) {
			// Familia-voltaje = todos los segmentos antes del último guion (ej: "3LED-12V" de "3LED-12V-A")
			$parts = explode('-', $p['id']);
			array_pop($parts);
			$key = implode('-', $parts);
			if (isset($seen[$key])) continue;
			$seen[$key] = true;
			$featured[] = $p;
			if (count($featured) >= 8) break;
		}
		// Si no llegamos a 8, completar con más productos del mismo conjunto
		if (count($featured) < 8) {
			foreach ($moduleProducts as $p) {
				if (count($featured) >= 8) break;
				if (!in_array($p, $featured, true)) $featured[] = $p;
			}
		}

		$this->load->view('welcome', array('featured' => $featured));
	}
}
