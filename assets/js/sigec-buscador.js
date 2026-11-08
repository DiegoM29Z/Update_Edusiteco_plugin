/**
 * SIGEC-WP · Buscador en tiempo real del catálogo de convocatorias.
 *
 * Depende del objeto global `sigec_ajax` (inyectado con wp_localize_script
 * en sigec-wp.php):
 *   sigec_ajax.url -> URL de admin-ajax.php
 *
 * Acción AJAX en el servidor: sigec_filtrar_convocatorias
 */
(function () {
    'use strict';

    function iniciarBuscador() {
        const inputTexto           = document.getElementById('sigec-input-texto');
        const selectEstado         = document.getElementById('sigec-select-estado');
        const contenedorResultados = document.getElementById('sigec-resultados-contenedor');

        // Si el catálogo no está en esta página, no hacemos nada
        if (!inputTexto || !selectEstado || !contenedorResultados) {
            return;
        }

        if (typeof sigec_ajax === 'undefined' || !sigec_ajax.url) {
            console.error('SIGEC: no se encontró la configuración AJAX (sigec_ajax).');
            return;
        }

        let timeoutBusqueda = null;

        function buscarConvocatorias() {
            // Efecto visual de carga
            contenedorResultados.classList.add('sigec-loading');

            const data = new URLSearchParams();
            data.append('action', 'sigec_filtrar_convocatorias');
            data.append('texto', inputTexto.value);
            data.append('estado', selectEstado.value);

            // Petición al servidor
            fetch(sigec_ajax.url, {
                method: 'POST',
                body: data
            })
                .then(response => response.text())
                .then(html => {
                    contenedorResultados.innerHTML = html;
                    contenedorResultados.classList.remove('sigec-loading');
                })
                .catch(error => {
                    console.error('Error al buscar:', error);
                    contenedorResultados.classList.remove('sigec-loading');
                });
        }

        // Cambio en el selector de estado (instantáneo)
        selectEstado.addEventListener('change', buscarConvocatorias);

        // Escritura en el buscador (con retraso de 400ms para no saturar el servidor)
        inputTexto.addEventListener('input', function () {
            clearTimeout(timeoutBusqueda);
            timeoutBusqueda = setTimeout(buscarConvocatorias, 400);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', iniciarBuscador);
    } else {
        iniciarBuscador();
    }
})();

