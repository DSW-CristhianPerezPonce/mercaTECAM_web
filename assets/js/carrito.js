// HU-04: carrito de compras guardado en el navegador (localStorage).
// Solo se guardan ids y cantidades; nombre, precio y disponibilidad se consultan
// siempre al servidor (/api/carrito) para que el total use los datos vigentes.
(function () {
    'use strict';

    var CLAVE = 'mercatecam.carrito.v1';
    var MAX_PRODUCTOS = 50;
    var formatoCop = new Intl.NumberFormat('es-CO', { maximumFractionDigits: 0 });

    // ---------- Estado ----------
    // Si el navegador bloquea localStorage (modo privado, datos de sitio bloqueados),
    // el carrito funciona en memoria mientras la página esté abierta.
    var memoria = '{}';

    function leer() {
        var crudo;
        try {
            crudo = localStorage.getItem(CLAVE) || '{}';
        } catch (e) {
            crudo = memoria;
        }
        try {
            var datos = JSON.parse(crudo);
            var limpio = {};
            Object.keys(datos).forEach(function (id) {
                var n = parseInt(datos[id], 10);
                if (/^[1-9]\d{0,9}$/.test(id) && n > 0) limpio[id] = n;
            });
            return limpio;
        } catch (e) {
            return {};
        }
    }

    function guardar(carrito) {
        memoria = JSON.stringify(carrito);
        try {
            localStorage.setItem(CLAVE, memoria);
        } catch (e) { /* almacenamiento lleno o bloqueado: el carrito sigue en memoria */ }
        actualizarContador(carrito);
    }

    function totalUnidades(carrito) {
        return Object.keys(carrito).reduce(function (s, id) { return s + carrito[id]; }, 0);
    }

    function precio(valor) {
        return '$ ' + formatoCop.format(Math.round(valor));
    }

    // ---------- Encabezado ----------
    function actualizarContador(carrito) {
        var n = totalUnidades(carrito || leer());
        document.querySelectorAll('[data-carrito-contador]').forEach(function (el) {
            el.textContent = n;
            el.setAttribute('aria-label', n === 1 ? '1 producto en el carrito' : n + ' productos en el carrito');
            el.classList.toggle('carrito-enlace__contador--lleno', n > 0);
        });
    }

    var temporizadorAviso;
    function avisar(texto) {
        var aviso = document.querySelector('[data-carrito-aviso]');
        if (!aviso) return;
        aviso.textContent = texto;
        aviso.hidden = false;
        clearTimeout(temporizadorAviso);
        temporizadorAviso = setTimeout(function () { aviso.hidden = true; }, 2600);
    }

    // ---------- Botones «Agregar al carrito» ----------
    function activarBotones() {
        document.querySelectorAll('[data-agregar]').forEach(function (boton) {
            if (boton.hasAttribute('data-agotado')) return;
            boton.disabled = false;
            boton.addEventListener('click', function () {
                var id = boton.getAttribute('data-agregar');
                var max = parseInt(boton.getAttribute('data-max'), 10) || 0;
                var nombre = boton.getAttribute('data-nombre');
                var cantidad = 1;
                var selector = boton.getAttribute('data-cantidad-desde');
                if (selector) {
                    var campo = document.querySelector(selector);
                    cantidad = Math.max(1, parseInt(campo && campo.value, 10) || 1);
                }

                var carrito = leer();
                if (!carrito[id] && Object.keys(carrito).length >= MAX_PRODUCTOS) {
                    avisar('El carrito admite hasta ' + MAX_PRODUCTOS + ' productos distintos.');
                    return;
                }
                var actual = carrito[id] || 0;
                var nueva = Math.min(actual + cantidad, max);
                if (nueva <= actual) {
                    avisar('Ya tienes en el carrito toda la disponibilidad de «' + nombre + '» (' + max + ').');
                    return;
                }
                carrito[id] = nueva;
                guardar(carrito);
                avisar(nueva - actual < cantidad
                    ? 'Agregamos solo ' + (nueva - actual) + ': es la disponibilidad declarada de «' + nombre + '».'
                    : '«' + nombre + '» agregado al carrito (' + nueva + ').');
            });
        });
    }

    // ---------- Página /carrito ----------
    function iniciarPaginaCarrito(raiz) {
        var productos = {}; // id → datos vigentes del servidor
        var lista = raiz.querySelector('[data-carrito-lista]');
        var plantilla = document.getElementById('plantilla-item-carrito');
        var estado = raiz.querySelector('[data-carrito-estado]');

        function mostrar(seccion) {
            estado.hidden = seccion !== 'estado';
            raiz.querySelector('[data-carrito-vacio]').hidden = seccion !== 'vacio';
            raiz.querySelector('[data-carrito-contenido]').hidden = seccion !== 'contenido';
        }

        function cambiarCantidad(id, cantidad) {
            var carrito = leer();
            var max = productos[id] ? productos[id].disponibilidad : 0;
            cantidad = Math.min(Math.max(1, cantidad || 1), max);
            if (cantidad > 0) carrito[id] = cantidad; else delete carrito[id];
            guardar(carrito);
            pintar();
        }

        function quitar(id) {
            var carrito = leer();
            delete carrito[id];
            guardar(carrito);
            pintar();
            avisar('Producto quitado del carrito.');
        }

        // Recalcula subtotales y total a partir del estado guardado y los precios vigentes.
        function pintar() {
            var carrito = leer();
            var ids = Object.keys(carrito).filter(function (id) { return productos[id]; });
            if (ids.length === 0) {
                mostrar('vacio');
                return;
            }

            var total = 0;
            var unidades = 0;
            lista.textContent = '';
            ids.sort(function (a, b) { return productos[a].nombre.localeCompare(productos[b].nombre, 'es'); });
            ids.forEach(function (id) {
                var p = productos[id];
                var cantidad = carrito[id];
                var subtotal = p.precio_referencia * cantidad;
                total += subtotal;
                unidades += cantidad;

                var item = plantilla.content.firstElementChild.cloneNode(true);
                item.dataset.id = id;
                var img = item.querySelector('.item-carrito__imagen');
                img.src = p.imagen;
                var enlace = item.querySelector('.item-carrito__nombre');
                enlace.textContent = p.nombre;
                enlace.href = '/producto/' + id;
                item.querySelector('.item-carrito__detalle').textContent =
                    precio(p.precio_referencia) + ' / ' + p.unidad + ' · ' + p.productor;

                var campo = item.querySelector('.cantidad__campo');
                campo.value = cantidad;
                campo.max = p.disponibilidad;
                campo.setAttribute('aria-label', 'Cantidad de ' + p.nombre);
                item.querySelector('[data-accion="bajar"]').disabled = cantidad <= 1;
                item.querySelector('[data-accion="subir"]').disabled = cantidad >= p.disponibilidad;
                item.querySelector('[data-accion="quitar"]').setAttribute('aria-label', 'Quitar ' + p.nombre + ' del carrito');
                if (cantidad >= p.disponibilidad) {
                    var aviso = item.querySelector('.item-carrito__aviso');
                    aviso.textContent = 'Máximo disponible: ' + p.disponibilidad;
                    aviso.hidden = false;
                }
                item.querySelector('.item-carrito__subtotal').textContent = precio(subtotal);
                lista.appendChild(item);
            });

            raiz.querySelector('[data-carrito-unidades]').textContent = unidades;
            raiz.querySelector('[data-carrito-total]').textContent = precio(total);
            mostrar('contenido');
        }

        lista.addEventListener('click', function (ev) {
            var boton = ev.target.closest('button[data-accion]');
            if (!boton) return;
            var id = boton.closest('.item-carrito').dataset.id;
            var actual = leer()[id] || 0;
            var accion = boton.getAttribute('data-accion');
            if (accion === 'subir') cambiarCantidad(id, actual + 1);
            if (accion === 'bajar') cambiarCantidad(id, actual - 1);
            if (accion === 'quitar') quitar(id);
        });
        lista.addEventListener('change', function (ev) {
            if (ev.target.getAttribute('data-accion') !== 'fijar') return;
            cambiarCantidad(ev.target.closest('.item-carrito').dataset.id, parseInt(ev.target.value, 10));
        });
        raiz.querySelector('[data-carrito-vaciar]').addEventListener('click', function () {
            guardar({});
            pintar();
            avisar('Carrito vaciado.');
        });

        // Carga inicial: trae datos vigentes y depura productos retirados o sin existencias.
        var carrito = leer();
        var ids = Object.keys(carrito);
        if (ids.length === 0) {
            mostrar('vacio');
            return;
        }
        fetch('/api/carrito?ids=' + ids.join(','), { headers: { Accept: 'application/json' } })
            .then(function (r) {
                if (!r.ok) throw new Error('HTTP ' + r.status);
                return r.json();
            })
            .then(function (json) {
                json.data.forEach(function (p) { productos[p.id] = p; });
                var cambios = [];
                ids.forEach(function (id) {
                    var p = productos[id];
                    if (!p || p.disponibilidad <= 0) {
                        cambios.push(p ? '«' + p.nombre + '» se agotó' : 'un producto ya no está publicado');
                        delete carrito[id];
                    } else if (carrito[id] > p.disponibilidad) {
                        cambios.push('ajustamos «' + p.nombre + '» a ' + p.disponibilidad + ' (disponibilidad actual)');
                        carrito[id] = p.disponibilidad;
                    }
                });
                guardar(carrito);
                pintar();
                if (cambios.length) avisar('Actualizamos tu carrito: ' + cambios.join('; ') + '.');
            })
            .catch(function () {
                estado.textContent = 'No pudimos cargar los precios del carrito. Intenta de nuevo en unos minutos.';
            });
    }

    // Sincroniza el contador si el carrito cambia en otra pestaña.
    window.addEventListener('storage', function (ev) {
        if (ev.key === CLAVE) actualizarContador();
    });

    actualizarContador();
    activarBotones();
    var raiz = document.querySelector('[data-carrito]');
    if (raiz) iniciarPaginaCarrito(raiz);
})();
