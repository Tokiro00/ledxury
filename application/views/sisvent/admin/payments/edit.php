<?php
defined('BASEPATH') OR exit('No direct script access allowed');

    //$permissions = $this->session->userdata('user_data')['permissions'];
    $role = $this->session->userdata('user_data')['role'];
    //$showAdmin = (!empty($permissions) && ($permissions['2']['read'] || $permissions['3']['read']));
?>
<!DOCTYPE html>
<html lang="en">
    <title>Pagos</title>
    <?php $this->load->view('sisvent/layouts/meta_header'); ?>
<head>

</head>
  <body>
    <div id="bars" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
    	<?php $this->load->view('sisvent/layouts/sidebar',array('thisFile' => $_ci_view,'role' => $role)); ?>

    	 <div class="flex flex-col flex-1 w-full">
    		<?php $this->load->view('sisvent/layouts/navbar'); ?>
    	 	<main class="h-full overflow-y-auto">
    	 		<div class="px-6 mx-auto grid">
                    <h2 class="mb-4 text-lg font-semibold text-gray-600 mt-2">
                        Editar Pago
                    </h2>
                    
                    <form action="<?php echo base_url();?>sisvent/admin/payments/update" method="POST">
                      <?php if($this->session->flashdata("error")):?>
                          <div class="flex items-center p-4 mb-8 text-sm font-semibold text-white bg-red-600 rounded-lg shadow-md">
                              <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                              <p><?php echo $this->session->flashdata("error"); ?></p>
                           </div>
                      <?php endif;?>
                      <div class="px-4 py-3 mb-8 bg-white rounded-lg shadow-md">
                        <input class="form-input" type="hidden" name="payment_id" value="<?php echo $payment->idPayment;?>" readonly/>
                        <label class="block mt-4 text-sm">
                          <span class="text-gray-700">
                            Factura
                          </span>
                          <input id="invoice-id" class="form-input" type="text" value="#<?php echo str_pad($payment->invoiceId, 6, "0", STR_PAD_LEFT); ?>" disabled/>
                        </label>

                        <label class="block mt-4 text-sm">
                          <span class="text-gray-700">
                            Vendedor
                          </span>
                          <input class="form-input" type="hidden" name="vendor" value="<?php echo $payment->vendorId;?>" readonly/>
                          <input class="form-input" type="text" value="<?php echo $payment->vendor_name;?>" disabled/>
                        </label>

                        <div class="flex flex-row gap-4">
                          <div class="flex-1 mt-4 text-sm col-span-12 sm:col-span-6">
                            <span class="text-gray-700">
                              Cliente
                            </span>
                            <input class="form-input" type="hidden" name="client" value="<?php echo $payment->clientId;?>" readonly/>
                            <input class="form-input" type="text" value="<?php echo $payment->client_name;?>" disabled/>
                          </div>
                        </div>

                        <label class="block mt-4 text-sm">
                          <span class="text-gray-700">
                            Método de Pago
                          </span>
                          <input class="form-input" type="hidden" name="method" value="<?php echo $payment->paymentMethod;?>" readonly/>
                          <input class="form-input" type="text" value="<?php echo $payment->method_name;?>" disabled/>
                        </label>

                        <label class="flex flex-row text-xl mt-4">
                          <span class="form-input nb font-bold w-18">Abono $</span>
                          <input id="invoice-payment-val" class="form-input font-bold" type="number" name="payment" value="<?php echo set_value('payment',$payment->payment);?>" readonly/>
                        </label>

                        <label class="block text-sm mt-4">
                          <span class="text-gray-700">Observaciones</span>
                          <textarea class="form-input" name="comments"><?php echo set_value('comments',$payment->comments); ?></textarea>
                        </label>

                        <div class="block text-sm mt-4">
                            <input type="submit" class="px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-dark border border-transparent rounded-lg active:bg-mam-blue-dark hover:bg-mam-blue-dark focus:outline-none focus:shadow-outline-mam-blue-dark" value="Guardar">
                        </div>
                      </div>
                    </form>

    	 		    </div>
	        </main>
	      </div>
    </div>
  </body>
</html>