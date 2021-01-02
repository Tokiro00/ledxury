<?php 

function get_images_path($image = '') {
    return base_url() . 'public/dist/images/' . $image;
}

function get_public_path($asset = '') {
    return base_url() . 'public/dist/' . $asset;
}

function test_input($data) {
	$data = trim($data);
	$data = stripslashes($data);
	$data = htmlspecialchars($data);
	return $data;
}