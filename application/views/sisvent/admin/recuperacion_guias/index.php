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
                                Consultar 25 pendientes
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

                    <!-- KPIs -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                        <div class="bg-white rounded-lg p-4 border border-gray-200">
                            <p class="text-xs text-gray-400 font-semibold">GUÍAS HUÉRFANAS</p>
                            <p class="text-2xl font-bold text-gray-700 mt-1" id="kpi-total"><?= number_format($total_huerfanas, 0, ',', '.') ?></p>
                        </div>
                        <div class="bg-white rounded-lg p-4 border border-gray-200">
                            <p class="text-xs text-gray-400 font-semibold">CONSULTADAS</p>
                            <p class="text-2xl font-bold mt-1" style="color:#0F766E;" id="kpi-consultadas"><?= number_format($total_consultadas, 0, ',', '.') ?></p>
                        </div>
                        <div class="bg-white rounded-lg p-4 border border-gray-200">
                            <p class="text-xs text-gray-400 font-semibold">SIN CONSULTAR</p>
                            <p class="text-2xl font-bold mt-1" style="color:#B45309;" id="kpi-pendientes">—</p>
                        </div>
                        <div class="bg-white rounded-lg p-4 border border-gray-200">
                            <p class="text-xs text-gray-400 font-semibold">DEVOLUCIONES DETECTADAS</p>
                            <p class="text-2xl font-bold mt-1" style="color:#DC2626;" id="kpi-devoluciones">—</p>
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
                        <button type="button" class="filtro px-3 py-2 text-xs font-bold rounded-lg border bg-white text-gray-600" data-f="sin_consultar">Sin consultar</button>
                        <button type="button" class="filtro px-3 py-2 text-xs font-bold rounded-lg border bg-white text-gray-600" data-f="consultadas">Consultadas</button>
                        <button type="button" class="filtro px-3 py-2 text-xs font-bold rounded-lg border bg-white text-gray-600" data-f="devoluciones">Devoluciones</button>
                        <button type="button" class="filtro px-3 py-2 text-xs font-bold rounded-lg border bg-white text-gray-600" data-f="entregadas">Entregadas</button>
                        <span class="text-xs text-gray-400 ml-2" id="conteo-filtro"></span>
                    </div>

                    <!-- Tabla -->
                    <div class="bg-white rounded-lg border border-gray-200 overflow-x-auto">
                        <table class="w-full text-xs">
                            <thead>
                                <tr class="text-left text-gray-400 border-b border-gray-100">
                                    <th class="px-4 py-3 font-semibold">GUÍA</th>
                                    <th class="px-4 py-3 font-semibold">FUENTE</th>
                                    <th class="px-4 py-3 font-semibold">FECHA VENTA</th>
                                    <th class="px-4 py-3 font-semibold">DESTINATARIO / DESTINO</th>
                                    <th class="px-4 py-3 font-semibold text-right">COBRADO</th>
                                    <th class="px-4 py-3 font-semibold text-right">FLETE</th>
                                    <th class="px-4 py-3 font-semibold">EMPRESA</th>
                                    <th class="px-4 py-3 font-semibold">ESTADO (Interrapidísimo)</th>
                                    <th class="px-4 py-3 font-semibold">ÚLTIMO MOVIMIENTO</th>
                                </tr>
                            </thead>
                            <tbody id="tabla-guias">
                                <tr><td colspan="9" class="px-4 py-8 text-center text-gray-400">Cargando…</td></tr>
                            </tbody>
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

        function badgeEstado(r) {
            if (!r.rec || !r.rec.consultada_at) return '<span class="px-2 py-1 rounded-full font-bold" style="background:#F3F4F6; color:#9CA3AF;">sin consultar</span>';
            var e = r.rec.estado || 'SIN RESPUESTA';
            var bg = '#FEF3C7', col = '#B45309';
            if (esEntregada(r)) { bg = '#D1FAE5'; col = '#047857'; }
            if (esDevolucion(r)) { bg = '#FEE2E2'; col = '#DC2626'; }
            if (e === 'SIN RESPUESTA') { bg = '#F3F4F6'; col = '#6B7280'; }
            var extra = r.rec.motivo ? '<div class="text-gray-400 mt-0.5" style="max-width:200px; white-space:normal;">' + r.rec.motivo.substring(0, 60) + '</div>' : '';
            return '<span class="px-2 py-1 rounded-full font-bold" style="background:' + bg + '; color:' + col + ';">' + e + '</span>' + extra;
        }

        function badgeEmpresa(c) {
            var map = { ledxury: ['#DBEAFE', '#1D4ED8'], mam: ['#FCE7F3', '#BE185D'], mam_online: ['#EDE9FE', '#6D28D9'], sin_revisar: ['#F3F4F6', '#6B7280'] };
            var m = map[c] || map.sin_revisar;
            return '<span class="px-2 py-1 rounded-full font-bold" style="background:' + m[0] + '; color:' + m[1] + ';">' + (c || '—') + '</span>';
        }

        function pasaFiltro(r) {
            if (filtro === 'sin_consultar' && r.rec && r.rec.consultada_at) return false;
            if (filtro === 'consultadas' && (!r.rec || !r.rec.consultada_at)) return false;
            if (filtro === 'devoluciones' && !esDevolucion(r)) return false;
            if (filtro === 'entregadas' && !esEntregada(r)) return false;
            var q = $('#buscar').val().toLowerCase().trim();
            if (q && (r.guia + ' ' + (r.destinatario || '')).toLowerCase().indexOf(q) === -1) return false;
            return true;
        }

        function render() {
            var visibles = datos.filter(pasaFiltro);
            var html = visibles.map(function (r) {
                var dest = r.destinatario || '—';
                if (r.rec && r.rec.destino) dest += '<div class="text-gray-400">' + r.rec.destino + '</div>';
                return '<tr class="border-b border-gray-50 hover:bg-gray-50">' +
                    '<td class="px-4 py-2 font-mono font-bold text-gray-700">' + r.guia + '</td>' +
                    '<td class="px-4 py-2 text-gray-500">' + r.fuentes.join('<br>') + '</td>' +
                    '<td class="px-4 py-2 text-gray-500">' + fecha(r.fecha_venta) + '</td>' +
                    '<td class="px-4 py-2 text-gray-600">' + dest + '</td>' +
                    '<td class="px-4 py-2 text-right text-gray-700 font-semibold">' + fmt(r.valor_cobrado) + '</td>' +
                    '<td class="px-4 py-2 text-right text-gray-500">' + fmt(r.flete) + '</td>' +
                    '<td class="px-4 py-2">' + badgeEmpresa(r.company) + '</td>' +
                    '<td class="px-4 py-2">' + badgeEstado(r) + '</td>' +
                    '<td class="px-4 py-2 text-gray-500">' + (r.rec ? fecha(r.rec.fecha_ultimo) : '—') + '</td>' +
                '</tr>';
            }).join('');
            $('#tabla-guias').html(html || '<tr><td colspan="9" class="px-4 py-8 text-center text-gray-400">Nada que mostrar con este filtro</td></tr>');
            $('#conteo-filtro').text(visibles.length + ' de ' + datos.length + ' guías');
            actualizarKpis();
        }

        function actualizarKpis() {
            var cons = datos.filter(function (r) { return r.rec && r.rec.consultada_at; }).length;
            var dev = datos.filter(esDevolucion).length;
            $('#kpi-total').text(datos.length.toLocaleString('es-CO'));
            $('#kpi-consultadas').text(cons.toLocaleString('es-CO'));
            $('#kpi-pendientes').text((datos.length - cons).toLocaleString('es-CO'));
            $('#kpi-devoluciones').text(dev.toLocaleString('es-CO'));
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
            var objetivo = todo ? pend.length : Math.min(25, pend.length);
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
                var lote = pendientesDeConsulta().slice(0, Math.min(25, objetivo - hechas));
                if (lote.length === 0) { barriendo = false; return; }
                $('#progreso-texto').text('Consultando ' + (hechas + lote.length) + ' de ' + objetivo + '…');
                $('#progreso-barra').css('width', Math.round(hechas * 100 / objetivo) + '%');
                consultarLote(lote, function (ok) {
                    if (!ok) { barriendo = false; return; }
                    hechas += lote.length;
                    $('#progreso-barra').css('width', Math.round(hechas * 100 / objetivo) + '%');
                    setTimeout(paso, 500);
                });
            }
            paso();
        }

        $(document).on('click', '#btn-consultar-lote', function () { barrer(false); });
        $(document).on('click', '#btn-consultar-todas', function () { barrer(true); });
        $(document).on('click', '#btn-detener', function () { barriendo = false; });
        $(document).on('click', '.filtro', function () {
            filtro = $(this).data('f');
            $('.filtro').css({ background: '#fff', color: '#4B5563' });
            $(this).css({ background: '#1B365D', color: '#fff' });
            render();
        });
        $(document).on('input', '#buscar', render);

        cargar();
    })();
    </script>
</body>
</html>
