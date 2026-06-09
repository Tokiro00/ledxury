<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!-- Pulso footer: cierra Shell + scripts globales si los hay -->
<script>
// Sparkline renderer — convierte <div data-pulso-sparkline="3,5,4,7,8" data-color="#FF5A36"/>
(function() {
    function renderSparkline(el) {
        var data = (el.getAttribute('data-pulso-sparkline') || '').split(',').map(Number);
        if (data.length < 2) return;
        var color = el.getAttribute('data-color') || '#FF5A36';
        var fill = el.getAttribute('data-fill') !== '0';
        var w = parseInt(el.getAttribute('data-width') || '120', 10);
        var h = parseInt(el.getAttribute('data-height') || '36', 10);
        var max = Math.max.apply(null, data);
        var min = Math.min.apply(null, data);
        var rng = max - min || 1;
        var step = w / (data.length - 1);
        var pts = data.map(function(v, i) {
            return [i * step, h - ((v - min) / rng) * (h - 4) - 2];
        });
        var d = pts.map(function(p, i) {
            return (i === 0 ? 'M' : 'L') + p[0].toFixed(1) + ' ' + p[1].toFixed(1);
        }).join(' ');
        var fillD = d + ' L ' + w + ' ' + h + ' L 0 ' + h + ' Z';
        el.innerHTML = '<svg width="' + w + '" height="' + h + '" viewBox="0 0 ' + w + ' ' + h + '" style="display:block;">'
            + (fill ? '<path d="' + fillD + '" fill="' + color + '" opacity="0.08"/>' : '')
            + '<path d="' + d + '" fill="none" stroke="' + color + '" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>'
            + '</svg>';
    }
    document.querySelectorAll('[data-pulso-sparkline]').forEach(renderSparkline);
})();
</script>
