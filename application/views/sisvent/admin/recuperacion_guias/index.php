<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!DOCTYPE html>
<html lang="es">
    <title>Recuperación de Guías - Ledxury</title>
    <?php $this->load->view('sisvent/layouts/meta_header'); ?>
<body>
    <div id="bars" class="flex h-screen bg-gray-100" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
        <?php $this->load->view('sisvent/layouts/sidebar', array('thisFile' => 'sisvent/admin/recuperacion_guias/index', 'role' => $role)); ?>
        <div class="flex flex-col flex-1 w-full">
            <?php $this->load->view('sisvent/layouts/navbar'); ?>
            <main class="h-full overflow-y-auto">
                <div class="px-6 py-5 w-full max-w-screen-xl mx-auto">

                    <!-- Header -->
                    <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between mb-6">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-700">Recuperación de Guías</h2>
                            <p class="text-xs text-gray-400 mt-0.5">Guías huérfanas de contrapagos y cortes, consultadas contra Interrapidísimo (instancia perdida 23/08)</p>
                        </div>
                        <div class="flex items-center gap-3 mt-2 lg:mt-0">
                            <button type="button" id="btn-consultar-lote"
                               class="inline-flex items-center px-4 py-2 text-xs font-bold text-white rounded-lg transition-colors" style="background:#1B365D;">
                                Consultar 10 pendientes
                            </button>
                            <button type="button" id="btn-consultar-todas"
                               class="inline-flex items-center px-4 py-2 text-xs font-bold text-white rounded-lg transition-colors" style="background:#0F766E;">
                                Consultar todas
                            </button>
                            <a href="<?= base_url() ?>sisvent/admin/contrapagos"
                               class="inline-flex items-center px-4 py-2 text-xs font-bold text-gray-600 bg-white border border-gray-300 rounded-lg">
                                ← Contrapagos
                            </a>
                        </div>
                    </div>

                    <!-- KPIs: los tres grupos lado a lado, clicables como filtro -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                        <div class="kpi-card bg-white rounded-lg p-4 border border-gray-200" data-f="pagadas" style="cursor:pointer; border-left:4px solid #047857;">
                            <p class="text-xs text-gray-400 font-semibold">ENTREGADAS Y PAGADAS</p>
                            <p class="text-2xl font-bold mt-1" style="color:#047857;" id="kpi-pagadas">—</p>
                            <p class="text-xs text-gray-400 mt-0.5" id="kpi-pagadas-valor">—</p>
                        </div>
                        <div class="kpi-card bg-white rounded-lg p-4 border border-gray-200" data-f="devoluciones" style="cursor:pointer; border-left:4px solid #DC2626;">
                            <p class="text-xs text-gray-400 font-semibold">DEVOLUCIONES</p>
                            <p class="text-2xl font-bold mt-1" style="color:#DC2626;" id="kpi-devoluciones">—</p>
                            <p class="text-xs text-gray-400 mt-0.5" id="kpi-devoluciones-valor">con su fecha de devolución</p>
                        </div>
                        <div class="kpi-card bg-white rounded-lg p-4 border border-gray-200" data-f="pendiente_pago" style="cursor:pointer; border-left:4px solid #B45309;">
                            <p class="text-xs text-gray-400 font-semibold">PENDIENTES POR PAGO</p>
                            <p class="text-2xl font-bold mt-1" style="color:#B45309;" id="kpi-pendiente-pago">—</p>
                            <p class="text-xs text-gray-400 mt-0.5" id="kpi-pendiente-pago-valor">entregadas, sin pago de Interrapidísimo</p>
                        </div>
                        <div class="kpi-card bg-white rounded-lg p-4 border border-gray-200" data-f="sin_consultar" style="cursor:pointer; border-left:4px solid #9CA3AF;">
                            <p class="text-xs text-gray-400 font-semibold">SIN CONSULTAR</p>
                            <p class="text-2xl font-bold mt-1" style="color:#6B7280;" id="kpi-pendientes"><?= number_format($total_huerfanas - $total_consultadas, 0, ',', '.') ?></p>
                            <p class="text-xs text-gray-400 mt-0.5">de <span id="kpi-total"><?= number_format($total_huerfanas, 0, ',', '.') ?></span> huérfanas</p>
                        </div>
                    </div>

                    <!-- Progreso del barrido -->
                    <div id="progreso" class="hidden bg-white rounded-lg p-4 border border-gray-200 mb-4">
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-xs font-semibold text-gray-600" id="progreso-texto">Consultando…</p>
                            <button type="button" id="btn-detener" class="text-xs font-bold" style="color:#DC2626;">Detener</button>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full" style="height:8px;">
                            <div id="progreso-barra" style="height:8px; border-radius:9999px; width:0%; background:#0F766E; transition:width .3s;"></div>
                        </div>
                    </div>

                    <!-- Filtros -->
                    <div class="flex flex-wrap items-center gap-2 mb-4">
                        <input type="text" id="buscar" placeholder="Buscar guía o destinatario…"
                               class="px-3 py-2 text-xs border border-gray-300 rounded-lg bg-white" style="min-width:220px;">
                        <button type="button" class="filtro px-3 py-2 text-xs font-bold rounded-lg border" data-f="todas" style="background:#1B365D; color:#fff;">Todas</button>
                        <button type="button" class="filtro px-3 py-2 text-xs font-bold rounded-lg border bg-white text-gray-600" data-f="pagadas">Pagadas</button>
                        <button type="button" class="filtro px-3 py-2 text-xs font-bold rounded-lg border bg-white text-gray-600" data-f="devoluciones">Devoluciones</button>
                        <button type="button" class="filtro px-3 py-2 text-xs font-bold rounded-lg border bg-white text-gray-600" data-f="pendiente_pago">Pendiente pago</button>
                        <button type="button" class="filtro px-3 py-2 text-xs font-bold rounded-lg border bg-white text-gray-600" data-f="transito">En tránsito</button>
                        <button type="button" class="filtro px-3 py-2 text-xs font-bold rounded-lg border bg-white text-gray-600" data-f="sin_consultar">Sin consultar</button>
                        <span class="text-xs text-gray-400 ml-2" id="conteo-filtro"></span>
                    </div>

                    <!-- Tabla -->
                    <div class="bg-white rounded-lg border border-gray-200 overflow-x-auto">
                        <table class="w-full text-xs">
                            <thead>
                                <tr class="text-left text-gray-400 border-b border-gray-100">
                                    <th class="px-4 py-3 font-semibold">GUÍA</th>
                                    <th class="px-4 py-3 font-semibold">FACTURA / VENDEDOR</th>
                                    <th class="px-4 py-3 font-semibold">FECHA VENTA</th>
                                    <th class="px-4 py-3 font-semibold">DESTINATARIO / DESTINO</th>
                                    <th class="px-4 py-3 font-semibold text-right">VALOR</th>
                                    <th class="px-4 py-3 font-semibold text-right">FLETE</th>
                                    <th class="px-4 py-3 font-semibold">EMPRESA</th>
                                    <th class="px-4 py-3 font-semibold">SITUACIÓN</th>
                                    <th class="px-4 py-3 font-semibold">FUENTE</th>
                                </tr>
                            </thead>
                            <tbody id="tabla-guias">
                                <tr><td colspan="9" class="px-4 py-8 text-center text-gray-400">Cargando…</td></tr>
                            </tbody>
                            <tfoot>
                                <tr class="border-t-2 border-gray-200" style="background:#F9FAFB;">
                                    <td class="px-4 py-3 font-bold text-gray-700" colspan="4" id="tot-etiqueta">TOTALES</td>
                                    <td class="px-4 py-3 text-right font-bold text-gray-800" id="tot-valor">—</td>
                                    <td class="px-4 py-3 text-right font-bold text-gray-600" id="tot-flete">—</td>
                                    <td class="px-4 py-3" colspan="3" class="text-gray-400" id="tot-detalle"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                </div>
            </main>
        </div>
    </div>

    <?php $this->load->view('sisvent/layouts/footer'); ?>
    <script>
    (function () {
        var BASE = '<?= base_url() ?>sisvent/admin/recuperacionguias/';
        var datos = [];          // listado completo
        var filtro = 'todas';
        var barriendo = false;

        function fmt(n) { return n === null || n === undefined || n === '' ? '—' : '$' + Number(n).toLocaleString('es-CO'); }
        function fecha(f) { return f ? String(f).substring(0, 10) : '—'; }
        function esDevolucion(r) {
            if (!r.rec || !r.rec.estado) return false;
            var e = r.rec.estado.toLowerCase();
            return e.indexOf('devol') !== -1 || e.indexOf('devuel') !== -1 || (r.rec.motivo && r.rec.motivo.length > 0);
        }
        function esEntregada(r) {
            if (!r.rec || !r.rec.estado) return false;
            var e = r.rec.estado.toLowerCase();
            return e.indexOf('entrega') !== -1 || e.indexOf('archivada') !== -1;
        }

        // Situación consolidada de cada guía:
        //  pagadas        -> Interrapidísimo ya nos consignó (está en un lote de pago)
        //  devoluciones   -> el API dice devolución (gana sobre pagada: hay que revisarla)
        //  pendiente_pago -> el API dice entregada pero NO está en ningún lote de pago
        //  transito       -> consultada, ni entregada ni devuelta
        //  sin_consultar  -> aún no se ha barrido
        function situacion(r) {
            if (esDevolucion(r)) return 'devoluciones';
            if (r.pagada || r.valor_cobrado) return 'pagadas';
            if (esEntregada(r)) return 'pendiente_pago';
            if (r.rec && r.rec.consultada_at) return 'transito';
            return 'sin_consultar';
        }

        function badgeEstado(r) {
            var s = situacion(r);
            var estadoTxt = r.rec && r.rec.estado ? r.rec.estado : '';
            if (s === 'sin_consultar') return '<span class="px-2 py-1 rounded-full font-bold" style="background:#F3F4F6; color:#9CA3AF;">sin consultar</span>';
            var html = '';
            if (s === 'pagadas') {
                html = '<span class="px-2 py-1 rounded-full font-bold" style="background:#D1FAE5; color:#047857;">PAGADA</span>' +
                    (r.fecha_pago ? '<div class="text-gray-400 mt-0.5">pagada el ' + fecha(r.fecha_pago) + '</div>' : '');
            } else if (s === 'devoluciones') {
                html = '<span class="px-2 py-1 rounded-full font-bold" style="background:#FEE2E2; color:#DC2626;">DEVOLUCIÓN</span>' +
                    '<div class="mt-0.5 font-bold" style="color:#DC2626;">devuelta el ' + (r.rec ? fecha(r.rec.fecha_ultimo) : '—') + '</div>' +
                    (r.rec && r.rec.motivo ? '<div class="text-gray-400" style="max-width:200px; white-space:normal;">' + r.rec.motivo.substring(0, 60) + '</div>' : '');
            } else if (s === 'pendiente_pago') {
                html = '<span class="px-2 py-1 rounded-full font-bold" style="background:#FEF3C7; color:#B45309;">PENDIENTE POR PAGO</span>' +
                    '<div class="text-gray-400 mt-0.5">entregada el ' + (r.rec ? fecha(r.rec.fecha_ultimo) : '—') + '</div>';
            } else {
                html = '<span class="px-2 py-1 rounded-full font-bold" style="background:#DBEAFE; color:#1D4ED8;">' + (estadoTxt || 'EN TRÁNSITO') + '</span>';
            }
            if (estadoTxt && s !== 'transito') html += '<div class="text-gray-400 mt-0.5">' + estadoTxt + '</div>';
            return html;
        }

        function badgeEmpresa(c) {
            var map = { ledxury: ['#DBEAFE', '#1D4ED8'], mam: ['#FCE7F3', '#BE185D'], mam_online: ['#EDE9FE', '#6D28D9'], sin_revisar: ['#F3F4F6', '#6B7280'] };
            var m = map[c] || map.sin_revisar;
            return '<span class="px-2 py-1 rounded-full font-bold" style="background:' + m[0] + '; color:' + m[1] + ';">' + (c || '—') + '</span>';
        }

        function pasaFiltro(r) {
            if (filtro !== 'todas' && situacion(r) !== filtro) return false;
            var q = $('#buscar').val().toLowerCase().trim();
            if (q && (r.guia + ' ' + (r.destinatario || '') + ' ' + (r.vendedor || '') + ' ' + (r.cliente || '') + ' ' + (r.factura_erp || '')).toLowerCase().indexOf(q) === -1) return false;
            return true;
        }

        function render() {
            var visibles = datos.filter(pasaFiltro);
            var html = visibles.map(function (r) {
                var dest = r.destinatario || '—';
                if (r.cliente && (!r.destinatario || r.destinatario.indexOf(r.cliente) === -1)) dest = r.cliente + (r.destinatario ? '<div class="text-gray-400">' + r.destinatario + '</div>' : '');
                if (r.rec && r.rec.destino) dest += '<div class="text-gray-400">' + r.rec.destino + '</div>';
                var factVend = '—';
                if (r.factura_erp) {
                    factVend = '<a href="<?= base_url() ?>sisvent/commercial/invoices?q=' + r.factura_erp + '" target="_blank" class="font-bold" style="color:#1D4ED8;">#' + r.factura_erp + '</a>' +
                        (r.vendedor ? '<div class="text-gray-500">' + r.vendedor + '</div>' : '');
                } else if (r.vendedor) {
                    factVend = '<div class="text-gray-500">' + r.vendedor + '</div>';
                }
                var valor = r.valor_cobrado ? fmt(r.valor_cobrado) : (r.valor_declarado ? fmt(r.valor_declarado) + '<div class="text-gray-400 font-normal">declarado</div>' : '—');
                return '<tr class="border-b border-gray-50 hover:bg-gray-50">' +
                    '<td class="px-4 py-2 font-mono font-bold text-gray-700">' + r.guia + '</td>' +
                    '<td class="px-4 py-2">' + factVend + '</td>' +
                    '<td class="px-4 py-2 text-gray-500">' + fecha(r.fecha_venta) + '</td>' +
                    '<td class="px-4 py-2 text-gray-600">' + dest + '</td>' +
                    '<td class="px-4 py-2 text-right text-gray-700 font-semibold">' + valor + '</td>' +
                    '<td class="px-4 py-2 text-right text-gray-500">' + fmt(r.flete) + '</td>' +
                    '<td class="px-4 py-2">' + badgeEmpresa(r.company) + '</td>' +
                    '<td class="px-4 py-2">' + badgeEstado(r) + '</td>' +
                    '<td class="px-4 py-2 text-gray-400">' + r.fuentes.join('<br>') + '</td>' +
                '</tr>';
            }).join('');
            $('#tabla-guias').html(html || '<tr><td colspan="9" class="px-4 py-8 text-center text-gray-400">Nada que mostrar con este filtro</td></tr>');
            $('#conteo-filtro').text(visibles.length + ' de ' + datos.length + ' guías');
            // Totales en dinero de lo que se está viendo (según el filtro)
            var totCobrado = 0, totDeclarado = 0, totFlete = 0;
            visibles.forEach(function (r) {
                if (r.valor_cobrado) totCobrado += Number(r.valor_cobrado);
                else if (r.valor_declarado) totDeclarado += Number(r.valor_declarado);
                if (r.flete) totFlete += Number(r.flete);
            });
            $('#tot-etiqueta').text('TOTALES (' + visibles.length + ' guías)');
            $('#tot-valor').html(fmt(totCobrado + totDeclarado));
            $('#tot-flete').text(totFlete ? fmt(totFlete) : '—');
            $('#tot-detalle').text((totCobrado ? 'cobrado ' + fmt(totCobrado) : '') + (totCobrado && totDeclarado ? ' + ' : '') + (totDeclarado ? 'declarado ' + fmt(totDeclarado) : ''));
            actualizarKpis();
        }

        function actualizarKpis() {
            var pag = datos.filter(function (r) { return situacion(r) === 'pagadas'; });
            var dev = datos.filter(function (r) { return situacion(r) === 'devoluciones'; });
            var pen = datos.filter(function (r) { return situacion(r) === 'pendiente_pago'; });
            var sin = datos.filter(function (r) { return situacion(r) === 'sin_consultar'; }).length;
            var sumaPag = pag.reduce(function (a, r) { return a + (r.valor_cobrado || 0); }, 0);
            var sumaPen = pen.reduce(function (a, r) { return a + (r.valor_declarado || 0); }, 0);
            $('#kpi-total').text(datos.length.toLocaleString('es-CO'));
            $('#kpi-pagadas').text(pag.length.toLocaleString('es-CO'));
            $('#kpi-pagadas-valor').text(fmt(sumaPag) + ' cobrados');
            $('#kpi-devoluciones').text(dev.length.toLocaleString('es-CO'));
            var sumaDev = dev.reduce(function (a, r) { return a + (Number(r.valor_cobrado) || Number(r.valor_declarado) || 0); }, 0);
            $('#kpi-devoluciones-valor').text(sumaDev > 0 ? fmt(sumaDev) + ' en mercancía devuelta' : 'con su fecha de devolución');
            $('#kpi-pendiente-pago').text(pen.length.toLocaleString('es-CO'));
            $('#kpi-pendiente-pago-valor').text(sumaPen > 0 ? fmt(sumaPen) + ' declarados sin pago' : 'entregadas, sin pago de Interrapidísimo');
            $('#kpi-pendientes').text(sin.toLocaleString('es-CO'));
        }

        function cargar() {
            $.getJSON(BASE + 'listado', function (res) {
                if (!res.success) return;
                datos = res.data;
                render();
            });
        }

        function pendientesDeConsulta() {
            return datos.filter(function (r) { return !r.rec || !r.rec.consultada_at; }).map(function (r) { return r.guia; });
        }

        function consultarLote(guias, cb) {
            $.post(BASE + 'consultar', { guias: guias }, function (res) {
                if (!res.success) { alert(res.message || 'Error consultando'); cb(false); return; }
                res.data.forEach(function (g) {
                    var row = datos.find(function (r) { return r.guia === g.guia; });
                    if (row) row.rec = { estado: g.estado, fecha_primer: g.fecha_primer, fecha_ultimo: g.fecha_ultimo,
                        origen: g.origen, destino: g.destino, motivo: g.motivo, consultada_at: g.consultada_at };
                });
                render();
                cb(true);
            }, 'json').fail(function () { alert('Error de red consultando el API'); cb(false); });
        }

        function barrer(todo) {
            if (barriendo) return;
            var pend = pendientesDeConsulta();
            if (pend.length === 0) { alert('No quedan guías sin consultar.'); return; }
            var objetivo = todo ? pend.length : Math.min(10, pend.length);
            var hechas = 0;
            barriendo = true;
            $('#progreso').removeClass('hidden');

            function paso() {
                if (!barriendo || hechas >= objetivo) {
                    barriendo = false;
                    $('#progreso-texto').text('Listo: ' + hechas + ' guías consultadas.');
                    setTimeout(function () { $('#progreso').addClass('hidden'); }, 2500);
                    return;
                }
                var lote = pendientesDeConsulta().slice(0, Math.min(10, objetivo - hechas));
                if (lote.length === 0) { barriendo = false; return; }
                $('#progreso-texto').text('Consultando ' + (hechas + lote.length) + ' de ' + objetivo + '…');
                $('#progreso-barra').css('width', Math.round(hechas * 100 / objetivo) + '%');
                consultarLote(lote, function (ok) {
                    if (!ok) { barriendo = false; return; }
                    hechas += lote.length;
                    $('#progreso-barra').css('width', Math.round(hechas * 100 / objetivo) + '%');
                    setTimeout(paso, 3500); // el API de Interrapidisimo limita el ritmo
                });
            }
            paso();
        }

        $(document).on('click', '#btn-consultar-lote', function () { barrer(false); });
        $(document).on('click', '#btn-consultar-todas', function () { barrer(true); });
        $(document).on('click', '#btn-detener', function () { barriendo = false; });
        function activarFiltro(f) {
            filtro = f;
            $('.filtro').css({ background: '#fff', color: '#4B5563' });
            $('.filtro[data-f="' + f + '"]').css({ background: '#1B365D', color: '#fff' });
            render();
        }
        $(document).on('click', '.filtro', function () { activarFiltro($(this).data('f')); });
        $(document).on('click', '.kpi-card', function () { activarFiltro($(this).data('f')); });
        $(document).on('input', '#buscar', render);

        cargar();
    })();
    </script>
</body>
</html>
