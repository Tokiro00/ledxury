<?php
    $role = $this->session->userdata('user_data')['role'];
?>
<!DOCTYPE html>
<html lang="en">
    <title>Cargar Extracto — <?php echo $bankAccount->bankName; ?></title>
    <?php $this->load->view('sisvent/layouts/meta_header'); ?>
<body>
    <div id="bars" class="flex h-screen bg-gray-50"
         v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">

        <?php $this->load->view('sisvent/layouts/sidebar',
            array('thisFile' => $_ci_view, 'role' => $role)); ?>

        <div class="flex flex-col flex-1 w-full">
            <?php $this->load->view('sisvent/layouts/navbar'); ?>

            <main class="h-full overflow-y-auto">
                <div class="px-4 py-4 mx-auto w-full">

                    <!-- Header -->
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-gray-600">
                            Cargar Extracto Bancario — <?php echo $bankAccount->bankName; ?> (***<?php echo substr($bankAccount->accountNumber, -4); ?>)
                        </h2>
                        <a href="<?php echo base_url(); ?>sisvent/admin/bankaccounts/view/<?php echo $bankAccount->idBankAccount; ?>"
                           class="text-sm text-mam-blue-petroleo hover:underline">&larr; Volver</a>
                    </div>

                    <!-- Flash Messages -->
                    <?php if($this->session->flashdata('error')): ?>
                        <div class="bg-red-50 border border-red-200 rounded-lg p-3 mb-4">
                            <p class="text-sm text-red-700"><?php echo $this->session->flashdata('error'); ?></p>
                        </div>
                    <?php endif; ?>

                    <?php if($this->session->flashdata('success')): ?>
                        <div class="bg-green-50 border border-green-200 rounded-lg p-3 mb-4">
                            <p class="text-sm text-green-700"><?php echo $this->session->flashdata('success'); ?></p>
                        </div>
                    <?php endif; ?>

                    <!-- Bank Info -->
                    <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
                        <div class="grid grid-cols-4 gap-4 text-sm">
                            <div>
                                <p class="text-gray-500">Banco</p>
                                <p class="font-semibold text-gray-700"><?php echo $bankAccount->bankName; ?></p>
                            </div>
                            <div>
                                <p class="text-gray-500">Cuenta</p>
                                <p class="font-semibold text-gray-700"><?php echo $bankAccount->accountNumber; ?></p>
                            </div>
                            <div>
                                <p class="text-gray-500">Tipo</p>
                                <p class="font-semibold text-gray-700 capitalize"><?php echo $bankAccount->accountType; ?></p>
                            </div>
                            <div>
                                <p class="text-gray-500">Saldo Actual</p>
                                <p class="font-semibold text-gray-700">$<?php echo number_format($bankAccount->currentBalance, 0, ',', '.'); ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Upload Form -->
                    <form method="post" action="<?php echo base_url(); ?>sisvent/admin/bankaccounts/processStatement" enctype="multipart/form-data" id="upload-form">
                        <input type="hidden" name="bankAccountId" value="<?php echo $bankAccount->idBankAccount; ?>"/>

                        <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                            <h3 class="text-md font-semibold text-gray-700 mb-4">Datos del Extracto</h3>

                            <div class="grid grid-cols-3 gap-6 mb-6">
                                <label class="block text-sm">
                                    <span class="text-gray-700 font-medium">Fecha del Extracto</span>
                                    <input type="date" name="statementDate" value="<?php echo date('Y-m-d'); ?>"
                                           class="form-input mt-1 w-full" required/>
                                </label>
                                <label class="block text-sm">
                                    <span class="text-gray-700 font-medium">Mes del Periodo</span>
                                    <select name="periodMonth" class="form-input form-select mt-1 w-full" required>
                                        <?php
                                        $months = array(1=>'Enero',2=>'Febrero',3=>'Marzo',4=>'Abril',5=>'Mayo',6=>'Junio',7=>'Julio',8=>'Agosto',9=>'Septiembre',10=>'Octubre',11=>'Noviembre',12=>'Diciembre');
                                        foreach($months as $num => $name): ?>
                                            <option value="<?php echo $num; ?>" <?php echo ($num == date('n')) ? 'selected' : ''; ?>><?php echo $name; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                                <label class="block text-sm">
                                    <span class="text-gray-700 font-medium">Ano del Periodo</span>
                                    <input type="number" name="periodYear" value="<?php echo date('Y'); ?>"
                                           min="2020" max="2030" class="form-input mt-1 w-full" required/>
                                </label>
                            </div>

                            <label class="block text-sm mb-4">
                                <span class="text-gray-700 font-medium">Observaciones</span>
                                <textarea name="notes" class="form-input mt-1 w-full" rows="2"
                                          placeholder="Notas sobre el extracto..."></textarea>
                            </label>

                            <!-- File Upload -->
                            <div class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center mb-4" id="drop-zone">
                                <svg class="mx-auto h-12 w-12 text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                </svg>
                                <p class="text-gray-600 mb-2">Arrastra el archivo aqui o haz clic para seleccionar</p>
                                <p class="text-xs text-gray-400 mb-4">Formatos aceptados: .xlsx, .xls, .csv (max 5MB)</p>
                                <input type="file" name="statement_file" id="statement-file" accept=".xlsx,.xls,.csv"
                                       class="hidden" required/>
                                <button type="button" id="btn-select-file"
                                        class="px-4 py-2 text-sm font-medium text-white bg-mam-blue-petroleo rounded-lg hover:bg-mam-blue">
                                    Seleccionar Archivo
                                </button>
                                <p class="text-sm text-gray-600 mt-3 hidden" id="file-name"></p>
                            </div>

                            <!-- Format Instructions -->
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                <h4 class="text-sm font-semibold text-blue-700 mb-2">Formato esperado del archivo</h4>
                                <p class="text-xs text-blue-600 mb-2">El archivo debe tener las siguientes columnas en orden:</p>
                                <div class="overflow-x-auto">
                                    <table class="w-full text-xs">
                                        <thead>
                                            <tr style="background:#1B365D; color:white;">
                                                <th class="px-3 py-2 text-left">Col A</th>
                                                <th class="px-3 py-2 text-left">Col B</th>
                                                <th class="px-3 py-2 text-left">Col C</th>
                                                <th class="px-3 py-2 text-left">Col D</th>
                                                <th class="px-3 py-2 text-left">Col E</th>
                                                <th class="px-3 py-2 text-left">Col F</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr class="bg-white">
                                                <td class="px-3 py-2 border">Fecha</td>
                                                <td class="px-3 py-2 border">Descripcion</td>
                                                <td class="px-3 py-2 border">Referencia</td>
                                                <td class="px-3 py-2 border">Debito</td>
                                                <td class="px-3 py-2 border">Credito</td>
                                                <td class="px-3 py-2 border">Saldo</td>
                                            </tr>
                                            <tr class="bg-gray-50">
                                                <td class="px-3 py-2 border text-gray-500">2026-03-01</td>
                                                <td class="px-3 py-2 border text-gray-500">Transferencia recibida</td>
                                                <td class="px-3 py-2 border text-gray-500">REF123</td>
                                                <td class="px-3 py-2 border text-gray-500">0</td>
                                                <td class="px-3 py-2 border text-gray-500">500.000</td>
                                                <td class="px-3 py-2 border text-gray-500">1.500.000</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <p class="text-xs text-blue-500 mt-2">La primera fila se usa como encabezado y se omite.</p>
                            </div>
                        </div>

                        <!-- Buttons -->
                        <div class="flex items-center justify-between mb-6">
                            <a href="<?php echo base_url(); ?>sisvent/admin/bankaccounts/view/<?php echo $bankAccount->idBankAccount; ?>"
                               class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">Cancelar</a>
                            <button type="submit" id="btn-upload"
                                    class="px-6 py-2 text-sm font-medium text-white bg-mam-blue-petroleo rounded-lg hover:bg-mam-blue disabled:opacity-50"
                                    disabled>
                                Cargar y Conciliar
                            </button>
                        </div>
                    </form>

                </div>
            </main>
        </div>
    </div>

    <?php $this->load->view('sisvent/layouts/footer'); ?>

    <script>
    (function() {
        $(document).on('click', '#btn-select-file', function() {
            $('#statement-file').click();
        });

        $(document).on('change', '#statement-file', function() {
            var fileName = $(this).val().split('\\').pop();
            if (fileName) {
                $('#file-name').text(fileName).removeClass('hidden');
                $('#btn-upload').prop('disabled', false);
                $('#drop-zone').addClass('border-green-400 bg-green-50').removeClass('border-gray-300');
            } else {
                $('#file-name').addClass('hidden');
                $('#btn-upload').prop('disabled', true);
                $('#drop-zone').removeClass('border-green-400 bg-green-50').addClass('border-gray-300');
            }
        });
    })();
    </script>
</body>
</html>
