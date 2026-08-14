<?php $role = $this->session->userdata('user_data')['role']; ?>
<!DOCTYPE html>
<html>
<head>
<title>Mi Perfil - Ledxury</title>
<?php $this->load->view('sisvent/layouts/meta_header'); ?>
</head>
<body>
<div id="bars" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
  <?php $this->load->view('sisvent/layouts/sidebar', array('thisFile' => $_ci_view, 'role' => $role)); ?>

  <div class="flex flex-col flex-1 w-full">
    <?php $this->load->view('sisvent/layouts/navbar'); ?>

    <main class="h-full overflow-y-auto">
      <div class="container max-w-2xl px-6 mx-auto py-8">

        <!-- Header -->
        <div class="flex items-center mb-6">
          <a href="<?= base_url() ?>sisvent/dashboard" class="mr-3 text-gray-400 hover:text-gray-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
          </a>
          <h2 class="text-xl font-semibold text-gray-700">Mi Perfil</h2>
        </div>

        <!-- Flash -->
        <?php if (!empty($success)): ?>
        <div class="p-3 mb-4 text-sm text-green-700 bg-green-100 rounded-lg"><?= $success ?></div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
        <div class="p-3 mb-4 text-sm text-red-700 bg-red-100 rounded-lg"><?= $error ?></div>
        <?php endif; ?>

        <form action="<?= base_url() ?>sisvent/dashboard/updateProfile" method="POST" enctype="multipart/form-data">

          <!-- Avatar -->
          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
            <div class="flex items-center">
              <div class="relative">
                <img id="avatarPreview" class="w-24 h-24 rounded-full object-cover border-4 border-gray-100"
                  src="<?= get_images_path($user->picture_url ?: 'users/general_1.png') ?>" alt="Avatar">
                <label for="photoInput" class="absolute bottom-0 right-0 p-1.5 bg-white rounded-full shadow-md border border-gray-200 cursor-pointer hover:bg-gray-50 transition-colors">
                  <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                </label>
                <input id="photoInput" type="file" name="photo" accept="image/jpeg,image/png" class="hidden">
              </div>
              <div class="ml-6">
                <h3 class="text-lg font-semibold text-gray-800"><?= htmlspecialchars($user->name) ?></h3>
                <p class="text-sm text-gray-400">ID: <?= $user->idUser ?></p>
                <?php
                  $this->db->select('name')->from('roles')->where('idRoles', $user->role);
                  $roleName = $this->db->get()->row();
                ?>
                <span class="inline-block mt-1 px-2 py-0.5 text-xs font-medium rounded-full bg-blue-100 text-blue-700"><?= $roleName ? $roleName->name : 'Rol ' . $user->role ?></span>
              </div>
            </div>
          </div>

          <!-- Datos Personales -->
          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
            <h3 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-4">Datos Personales</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Nombre completo</label>
                <input type="text" name="name" value="<?= htmlspecialchars($user->name) ?>" required
                  class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
              </div>
              <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Correo electronico</label>
                <input type="email" name="email" value="<?= htmlspecialchars($user->email ?: '') ?>"
                  class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
              </div>
              <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Telefono</label>
                <input type="text" name="phone" value="<?= htmlspecialchars($user->phone ?: '') ?>"
                  class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
              </div>
              <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Genero</label>
                <select name="gender" id="genderSelect" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                  <option value="">Sin especificar</option>
                  <option value="M" <?= (isset($user->gender) && $user->gender === 'M') ? 'selected' : '' ?>>Masculino</option>
                  <option value="F" <?= (isset($user->gender) && $user->gender === 'F') ? 'selected' : '' ?>>Femenino</option>
                  <option value="O" <?= (isset($user->gender) && $user->gender === 'O') ? 'selected' : '' ?>>Otro</option>
                </select>
              </div>
              <div class="md:col-span-2">
                <label class="block text-xs font-medium text-gray-500 mb-1">Direccion</label>
                <input type="text" name="address" value="<?= htmlspecialchars($user->address ?: '') ?>"
                  class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
              </div>
            </div>
          </div>

          <!-- Cambiar Contraseña -->
          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
            <h3 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-4">Cambiar Contrasena</h3>
            <p class="text-xs text-gray-400 mb-4">Deja en blanco si no quieres cambiarla</p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Nueva contrasena</label>
                <input type="password" name="new_password" minlength="6"
                  class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500" placeholder="Minimo 6 caracteres">
              </div>
              <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Confirmar contrasena</label>
                <input type="password" name="confirm_password"
                  class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500" placeholder="Repetir contrasena">
              </div>
            </div>
          </div>

          <!-- Guardar -->
          <div class="flex justify-end">
            <button type="submit" class="px-6 py-2.5 text-sm font-medium text-white rounded-lg focus:outline-none transition-colors" style="background: #E63946;" onmouseover="this.style.background='#c5303b'" onmouseout="this.style.background='#E63946'">
              Guardar Cambios
            </button>
          </div>
        </form>

        <!-- Lista negra de mi bot -->
        <?php if (!empty($my_bot)): ?>
        <div class="mt-6 bg-white rounded-2xl border border-slate-200 p-5">
          <div class="flex items-center gap-2 mb-1">
            <span style="font-size:18px;">🚫</span>
            <h3 class="text-base font-semibold text-slate-900">Lista negra de mi bot</h3>
          </div>
          <p class="text-xs text-slate-500 mb-4">Agrega los números de los clientes que quieres atender tú misma. El bot <b><?= htmlspecialchars($my_bot->name) ?></b> NO les enviará mensajes.</p>
          <div class="flex flex-col sm:flex-row gap-2 mb-3">
            <input type="text" id="blNumbers" placeholder="573001234567, 573009876543" class="flex-1 px-3 py-2.5 text-sm border border-slate-300 rounded-lg focus:outline-none focus:border-red-500">
            <button type="button" id="blAddBtn" class="px-4 py-2.5 text-sm font-medium text-white rounded-lg" style="background:#E63946;">Agregar</button>
          </div>
          <div id="blMsg" class="hidden text-sm mb-3 font-medium"></div>
          <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Números bloqueados</span>
            <button type="button" id="blRefresh" class="text-xs text-slate-500 hover:text-slate-800">&#x21BB; Refrescar</button>
          </div>
          <div id="blList" class="bg-slate-50 border border-slate-200 rounded-lg p-3" style="min-height:60px;"></div>
        </div>
        <?php endif; ?>
      </div>
    </main>
  </div>
</div>

<?php $this->load->view('sisvent/layouts/footer'); ?>

<script>
// Preview de foto
$(document).on('change', '#photoInput', function() {
  var file = this.files[0];
  if (file) {
    var reader = new FileReader();
    reader.onload = function(e) {
      $('#avatarPreview').attr('src', e.target.result);
    };
    reader.readAsDataURL(file);
  }
});

// Al cambiar género, preview del avatar por defecto
$(document).on('change', '#genderSelect', function() {
  var currentSrc = $('#avatarPreview').attr('src');
  // Solo cambiar si tiene avatar por defecto
  if (currentSrc.indexOf('general_1') !== -1 || currentSrc.indexOf('avatar_male') !== -1 || currentSrc.indexOf('avatar_female') !== -1) {
    var val = $(this).val();
    if (val === 'F') {
      $('#avatarPreview').attr('src', base_url + 'public/dist/images/users/avatar_female.svg');
    } else if (val === 'M' || val === 'O') {
      $('#avatarPreview').attr('src', base_url + 'public/dist/images/users/avatar_male.svg');
    }
  }
});
</script>

<?php if (!empty($my_bot)): ?>
<script>
// Lista negra del bot del vendedor (endpoints scoped al bot propio en Dashboard)
(function(){
  var CSRF = {}; CSRF['<?= $this->security->get_csrf_token_name() ?>'] = '<?= $this->security->get_csrf_hash() ?>';
  function blShow(type, msg){ var el=$('#blMsg'); el.text(msg).removeClass('hidden').css('color', type==='ok'?'#059669':'#dc2626'); setTimeout(function(){el.addClass('hidden');},5000); }
  function blLoad(){
    var box=$('#blList'); box.html('<p style="color:#64748b;font-size:13px;margin:0;">Cargando...</p>');
    $.getJSON(base_url+'sisvent/dashboard/myBotBlacklist', function(r){
      if(!r || r.http_code<200 || r.http_code>=300 || !r.body){ box.html('<p style="color:#ef4444;font-size:13px;margin:0;">No se pudo cargar</p>'); return; }
      var list = r.body.data || r.body.blacklist || r.body.numbers || r.body.phones || r.body;
      if(!Array.isArray(list)){ for(var k in list){ if(Array.isArray(list[k])){ list=list[k]; break; } } }
      if(!Array.isArray(list) || list.length===0){ box.html('<p style="color:#64748b;font-size:13px;margin:0;">No hay números bloqueados</p>'); return; }
      var html='';
      list.forEach(function(item){
        var num = typeof item==='string'?item:(item.number||item.phone||item.id||JSON.stringify(item));
        html += '<div style="display:flex;justify-content:space-between;align-items:center;padding:8px 12px;background:#fff;border-radius:8px;margin-bottom:6px;border:1px solid #e2e8f0;">'
              + '<span style="font-family:monospace;font-size:13px;color:#1a1a2e;">' + num + '</span>'
              + '<button type="button" class="bl-rm" data-n="' + num + '" style="background:#fee2e2;color:#991b1b;border:none;border-radius:6px;padding:4px 10px;cursor:pointer;font-size:12px;">Quitar</button>'
              + '</div>';
      });
      box.html(html);
    }).fail(function(){ box.html('<p style="color:#ef4444;font-size:13px;margin:0;">Error de conexión</p>'); });
  }
  $(document).on('click','#blAddBtn',function(){
    var n=$('#blNumbers').val().trim(); if(!n){ blShow('err','Ingresa al menos un número'); return; }
    $.post(base_url+'sisvent/dashboard/myBotBlacklistAdd', $.extend({numbers:n},CSRF), function(r){
      if(r && r.http_code>=200 && r.http_code<300){ blShow('ok','Agregado a la lista negra'); $('#blNumbers').val(''); blLoad(); }
      else { blShow('err','No se pudo agregar (' + (r&&r.error?r.error:'error') + ')'); }
    },'json').fail(function(){ blShow('err','Error de conexión'); });
  });
  $(document).on('click','.bl-rm',function(){
    var n=String($(this).data('n'));
    $.post(base_url+'sisvent/dashboard/myBotBlacklistRemove', $.extend({numbers:n},CSRF), function(r){
      if(r && r.http_code>=200 && r.http_code<300){ blShow('ok','Número quitado'); blLoad(); }
      else { blShow('err','No se pudo quitar'); }
    },'json').fail(function(){ blShow('err','Error de conexión'); });
  });
  $(document).on('click','#blRefresh',function(){ blLoad(); });
  blLoad();
})();
</script>
<?php endif; ?>
</body>
</html>
