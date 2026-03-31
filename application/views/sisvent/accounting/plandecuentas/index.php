<?php
defined('BASEPATH') OR exit('No direct script access allowed');

    $role = $this->session->userdata('user_data')['role'];
?>
<!DOCTYPE html>
<html lang="en">
    <title>Plan de Cuentas</title>
    <?php $this->load->view('sisvent/layouts/meta_header'); ?>
<head>

</head>
  <body>
    <div id="bars" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
    	<?php $this->load->view('sisvent/layouts/sidebar',array('thisFile' => $_ci_view,'role' => $role)); ?>

    	 <div class="flex flex-col flex-1 w-full">
    		<?php $this->load->view('sisvent/layouts/navbar'); ?>
    	 	<main class="h-full overflow-y-auto pb-8">
    	 		<div class="px-6 mx-auto grid">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between mt-2 mb-4">
                <h2 class="text-2xl font-bold text-gray-800">
                    Plan de Cuentas (PUC)
                </h2>
                <div class="flex items-center space-x-2 mt-2 md:mt-0">
                    <input type="text" id="searchInput" placeholder="Buscar cuenta..."
                           class="px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                           onkeyup="filterTree(this.value)">
                    <button onclick="expandAll()" class="px-3 py-2 text-sm text-white bg-mam-blue-petroleo rounded-md hover:opacity-90">Expandir todo</button>
                    <button onclick="collapseAll()" class="px-3 py-2 text-sm text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300">Colapsar todo</button>
                </div>
            </div>

            <!-- Tree View -->
            <div class="bg-white rounded-lg shadow-xs overflow-hidden">
                <?php if(!empty($tree)): ?>
                    <?php foreach($tree as $cls): ?>
                    <div class="tree-class border-b border-gray-200" data-search="<?php echo strtolower($cls->classID . ' ' . $cls->className); ?>">
                        <!-- Class Level -->
                        <div class="flex items-center px-4 py-3 bg-gray-100 cursor-pointer hover:bg-gray-200 transition-colors" onclick="toggleChildren(this)">
                            <svg class="w-5 h-5 mr-2 text-gray-500 tree-arrow transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                            <span class="inline-flex items-center justify-center w-8 h-8 text-sm font-bold text-white bg-blue-600 rounded-full mr-3">
                                <?php echo $cls->classID; ?>
                            </span>
                            <span class="text-base font-bold text-gray-800"><?php echo $cls->className; ?></span>
                            <?php if($cls->store_name): ?>
                            <span class="ml-2 text-xs text-gray-500">(<?php echo $cls->store_name; ?>)</span>
                            <?php endif; ?>
                        </div>

                        <!-- Groups -->
                        <div class="tree-children hidden">
                            <?php if(!empty($cls->groups)): ?>
                                <?php foreach($cls->groups as $grp): ?>
                                <div class="tree-group" data-search="<?php echo strtolower($grp->groupID . ' ' . $grp->groupName); ?>">
                                    <div class="flex items-center px-4 py-2 pl-12 bg-gray-50 cursor-pointer hover:bg-gray-100 transition-colors" onclick="toggleChildren(this)">
                                        <svg class="w-4 h-4 mr-2 text-gray-400 tree-arrow transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                        </svg>
                                        <span class="text-sm font-semibold text-blue-700 mr-3"><?php echo $grp->groupID; ?></span>
                                        <span class="text-sm font-semibold text-gray-700"><?php echo $grp->groupName; ?></span>
                                    </div>

                                    <!-- Accounts -->
                                    <div class="tree-children hidden">
                                        <?php if(!empty($grp->accounts)): ?>
                                            <?php foreach($grp->accounts as $acc): ?>
                                            <div class="tree-account" data-search="<?php echo strtolower($acc->accountID . ' ' . $acc->accountName); ?>">
                                                <div class="flex items-center px-4 py-2 pl-20 cursor-pointer hover:bg-blue-50 transition-colors" onclick="toggleChildren(this)">
                                                    <svg class="w-4 h-4 mr-2 text-gray-300 tree-arrow transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                                    </svg>
                                                    <span class="text-sm text-blue-600 mr-3 font-mono"><?php echo $acc->accountID; ?></span>
                                                    <span class="text-sm text-gray-700"><?php echo $acc->accountName; ?></span>
                                                    <?php if(!empty($acc->subaccounts)): ?>
                                                    <span class="ml-2 text-xs text-gray-400">(<?php echo count($acc->subaccounts); ?>)</span>
                                                    <?php endif; ?>
                                                </div>

                                                <!-- Subaccounts -->
                                                <div class="tree-children hidden">
                                                    <?php if(!empty($acc->subaccounts)): ?>
                                                        <?php foreach($acc->subaccounts as $sub): ?>
                                                        <div class="tree-subaccount flex items-center px-4 py-2 pl-28 hover:bg-green-50 transition-colors" data-search="<?php echo strtolower($sub->accountID . ' ' . $sub->accountName); ?>">
                                                            <span class="w-2 h-2 bg-green-400 rounded-full mr-3 flex-shrink-0"></span>
                                                            <span class="text-sm text-green-700 mr-3 font-mono"><?php echo $sub->accountID; ?></span>
                                                            <span class="text-sm text-gray-600"><?php echo $sub->accountName; ?></span>
                                                            <span class="ml-auto text-xs text-gray-400"><?php echo isset($sub->sideName) ? $sub->sideName : ''; ?></span>
                                                            <?php if(isset($sub->accountBalance) && $sub->accountBalance != 0): ?>
                                                            <span class="ml-2 text-xs font-semibold <?php echo $sub->accountBalance >= 0 ? 'text-green-600' : 'text-red-600'; ?>">
                                                                $ <?php echo number_format($sub->accountBalance, 2); ?>
                                                            </span>
                                                            <?php endif; ?>
                                                        </div>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <div class="px-4 py-2 pl-28 text-sm text-gray-400 italic">Sin subcuentas</div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <div class="px-4 py-2 pl-20 text-sm text-gray-400 italic">Sin cuentas</div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="px-4 py-2 pl-12 text-sm text-gray-400 italic">Sin grupos</div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="px-6 py-8 text-center text-gray-500">
                        No se encontraron cuentas en el plan de cuentas.
                    </div>
                <?php endif; ?>
            </div>

    	 		</div>
        </main>
      </div>
    </div>
    <?php $this->load->view('sisvent/layouts/footer'); ?>

    <script>
        function toggleChildren(element) {
            var children = element.nextElementSibling;
            var arrow = element.querySelector('.tree-arrow');
            if (children && children.classList.contains('tree-children')) {
                children.classList.toggle('hidden');
                if (arrow) {
                    arrow.classList.toggle('rotate-90');
                }
            }
        }

        function expandAll() {
            document.querySelectorAll('.tree-children').forEach(function(el) {
                el.classList.remove('hidden');
            });
            document.querySelectorAll('.tree-arrow').forEach(function(el) {
                el.classList.add('rotate-90');
            });
        }

        function collapseAll() {
            document.querySelectorAll('.tree-children').forEach(function(el) {
                el.classList.add('hidden');
            });
            document.querySelectorAll('.tree-arrow').forEach(function(el) {
                el.classList.remove('rotate-90');
            });
        }

        function filterTree(term) {
            term = term.toLowerCase().trim();
            var allItems = document.querySelectorAll('[data-search]');

            if (term === '') {
                // Reset: show all, collapse all
                allItems.forEach(function(el) {
                    el.style.display = '';
                });
                collapseAll();
                return;
            }

            // First hide all
            allItems.forEach(function(el) {
                el.style.display = 'none';
            });

            // Show matching items and their parents
            allItems.forEach(function(el) {
                if (el.dataset.search.indexOf(term) !== -1) {
                    el.style.display = '';
                    // Show all parent elements
                    var parent = el.parentElement;
                    while (parent) {
                        if (parent.dataset && parent.dataset.search !== undefined) {
                            parent.style.display = '';
                        }
                        if (parent.classList && parent.classList.contains('tree-children')) {
                            parent.classList.remove('hidden');
                        }
                        parent = parent.parentElement;
                    }
                    // Expand children
                    var children = el.querySelectorAll('.tree-children');
                    children.forEach(function(child) {
                        child.classList.remove('hidden');
                    });
                }
            });

            // Rotate arrows for visible expanded items
            document.querySelectorAll('.tree-children:not(.hidden)').forEach(function(el) {
                var prev = el.previousElementSibling;
                if (prev) {
                    var arrow = prev.querySelector('.tree-arrow');
                    if (arrow) arrow.classList.add('rotate-90');
                }
            });
        }
    </script>
  </body>
</html>
