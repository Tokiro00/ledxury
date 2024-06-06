<label class="block text-sm mt-4">
  <span class="text-green-700">Cliente nuevo, por favor ingrese los datos para completar la compra</span>
</label>

<input class="form-input" type="hidden" name="isnew" value="1" readonly/>

<label class="block text-sm mt-4 <?php echo !empty(form_error('name')) ? 'border-red-600':'';?>">
  <span class="text-gray-700">Nombre</span>
  <input class="form-input" type="text" name="name" value="<?php echo set_value('name');?>" required/>
  <?php echo form_error("name","<span class='text-xs text-red-600'>","</span>");?>
</label>

<label class="block text-sm mt-4 <?php echo !empty(form_error('address')) ? 'border-red-600':'';?>">
  <span class="text-gray-700">Dirección</span>
  <input class="form-input" type="text" minlength="15" name="address" value="<?php echo set_value('address');?>" required/>
  <?php echo form_error("address","<span class='text-xs text-red-600'>","</span>");?>
</label>

<label class="block text-sm mt-4 <?php echo !empty(form_error('zone')) ? 'border-red-600':'';?>">
  <span class="text-gray-700">Zona</span>
  <input class="form-input" type="text" name="zone" value="<?php echo set_value('zone');?>"/>
  <?php echo form_error("zone","<span class='text-xs text-red-600'>","</span>");?>
</label>

<label class="block text-sm mt-4 <?php echo !empty(form_error('city')) ? 'border-red-600':'';?>">
  <span class="text-gray-700">Ciudad</span>
  <input class="form-input" type="text" name="city" value="<?php echo set_value('city');?>" required/>
  <?php echo form_error("city","<span class='text-xs text-red-600'>","</span>");?>
</label>

<label class="block text-sm mt-4 <?php echo !empty(form_error('state')) ? 'border-red-600':'';?>">
  <span class="text-gray-700">Departamento</span>
  <input class="form-input" type="text" name="state" value="<?php echo set_value('state');?>" required/>
  <?php echo form_error("state","<span class='text-xs text-red-600'>","</span>");?>
</label>

<label class="block text-sm mt-4 <?php echo !empty(form_error('phone')) ? 'border-red-600':'';?>">
  <span class="text-gray-700">Teléfono</span>
  <input class="form-input" type="text" name="phone" value="<?php echo set_value('phone');?>" required/>
  <?php echo form_error("phone","<span class='text-xs text-red-600'>","</span>");?>
</label>

<label class="block text-sm mt-4 <?php echo !empty(form_error('cellphone')) ? 'border-red-600':'';?>">
  <span class="text-gray-700">Celular</span>
  <input class="form-input" type="text" name="cellphone" value="<?php echo set_value('cellphone');?>" />
  <?php echo form_error("cellphone","<span class='text-xs text-red-600'>","</span>");?>
</label>

<label class="block text-sm mt-4 <?php echo !empty(form_error('email')) ? 'border-red-600':'';?>">
  <span class="text-gray-700">Email</span>
  <input class="form-input" type="email" value="<?php echo set_value('email');?>" name="email" required/>
  <?php echo form_error("email","<span class='text-xs text-red-600'>","</span>");?>
</label>

<div class="flex-1 mt-4 text-sm col-span-12 sm:col-span-6">
    <span class="text-gray-700">
      Tipo de domicilio
    </span>
    <div class="flex flex-row gap-4">
      <select id="delivery-type" name="delivery-type" class="form-input form-select">
        <option value="1" <?php echo set_select("delivery-type",1);?>>Envió en Bogotá  - El domicilio tiene un costo de $10.000</option>
        <option value="2" selected <?php echo set_select("delivery-type",2);?>>Envió en Medellín - El domicilio tiene un costo de $10.000</option>
        <option value="3" <?php echo set_select("delivery-type",3);?>>Envió en Cali - El domicilio tiene un costo de $10.000</option>
        <option value="4" <?php echo set_select("delivery-type",4);?>>Envió en Barranquilla - El domicilio tiene un costo de $10.000</option>
        <option value="5" <?php echo set_select("delivery-type",5);?>>Envió a otra parte del país - El envió lo paga el cliente en el momento de la entrega</option>
        <option value="6" <?php echo set_select("delivery-type",6);?>>Recoger en Bogotá</option>
        <option value="7" <?php echo set_select("delivery-type",7);?>>Recoger en Medellín</option>
        <option value="8" <?php echo set_select("delivery-type",8);?>>Recoger en Cali</option>
      </select>
    </div>
</div>

<label class="block text-sm mt-4">
  <span class="text-gray-700">Observaciones</span>
  <textarea id="invoice-payment-comment" class="form-input" name="comments"><?php echo set_value('comments'); ?></textarea>
</label>

<div class="block text-sm mt-4">
    <input type="submit" class="px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-dark border border-transparent rounded-lg active:bg-mam-blue-dark hover:bg-mam-blue-dark focus:outline-none focus:shadow-outline-mam-blue-dark" value="Comprar">
</div>