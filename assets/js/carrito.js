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
            el.classList.toggle('carrito-enlace__contador--lleno', n > 0);
        });
        // El nombre accesible va en el enlace: un aria-label en un <span> lo ignoran muchos lectores.
        document.querySelectorAll('[data-carrito-enlace]').forEach(function (el) {
            el.setAttribute('aria-label', 'Carrito: ' + (n === 1 ? '1 producto' : n + ' productos'));
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
        var items = {};     // id → <li> ya pintado; se actualiza en su lugar para no perder el foco
        var lista = raiz.querySelector('[data-carrito-lista]');
        var resumen = raiz.querySelector('[data-carrito-resumen-lista]');
        var plantilla = document.getElementById('plantilla-item-carrito');
        var estado = raiz.querySelector('[data-carrito-estado]');
        var titulo = raiz.querySelector('h1');
        var cargando = false;
        var pendiente = false;

        function mostrar(seccion) {
            estado.hidden = seccion !== 'estado';
            raiz.querySelector('[data-carrito-vacio]').hidden = seccion !== 'vacio';
            raiz.querySelector('[data-carrito-contenido]').hidden = seccion !== 'contenido';
        }

        function enfocar(el) {
            if (!el) return;
            if (!el.matches('a, button, input')) el.setAttribute('tabindex', '-1');
            el.focus();
        }

        function nombreDe(id) {
            return productos[id] ? '«' + productos[id].nombre + '»' : 'El producto';
        }

        // Fija la cantidad de un producto: 0 lo quita; por encima de la disponibilidad se ajusta y se avisa.
        function cambiarCantidad(id, cantidad) {
            var carrito = leer();
            if (!carrito[id] || !productos[id] || isNaN(cantidad)) {
                pintar(); // lo quitaron en otra pestaña o el campo quedó vacío: se muestra el estado real
                return;
            }
            if (cantidad <= 0) {
                quitar(id);
                return;
            }
            var max = productos[id].disponibilidad;
            if (cantidad > max) {
                cantidad = max;
                avisar('Solo hay ' + max + ' disponibles de ' + nombreDe(id) + '; ajustamos la cantidad.');
            }
            carrito[id] = cantidad;
            guardar(carrito);
            pintar();
        }

        function quitar(id) {
            var item = items[id];
            var teniaFoco = item && item.contains(document.activeElement);
            var vecino = item && (item.nextElementSibling || item.previousElementSibling);
            var nombre = nombreDe(id);
            var carrito = leer();
            delete carrito[id];
            guardar(carrito);
            pintar();
            // El foco pasa al producto vecino (o al título si el carrito quedó vacío) en vez de perderse.
            if (teniaFoco) enfocar(vecino && vecino.isConnected ? vecino.querySelector('[data-accion="quitar"]') : titulo);
            avisar(nombre + ' quitado del carrito.');
        }

        function crearItem(id) {
            var item = plantilla.content.firstElementChild.cloneNode(true);
            item.dataset.id = id;
            item.querySelector('.item-carrito__nombre').href = '/producto/' + id;
            return item;
        }

        function actualizarItem(item, p, cantidad, subtotal) {
            var img = item.querySelector('.item-carrito__imagen');
            if (img.getAttribute('src') !== p.imagen) img.src = p.imagen; // no recarga la foto en cada cambio
            item.querySelector('.item-carrito__nombre').textContent = p.nombre;
            item.querySelector('.item-carrito__detalle').textContent =
                precio(p.precio_referencia) + ' / ' + p.unidad + ' · ' + p.productor;

            var campo = item.querySelector('.cantidad__campo');
            campo.value = cantidad;
            campo.max = p.disponibilidad;
            campo.setAttribute('aria-label', 'Cantidad de ' + p.nombre);
            item.querySelector('[data-accion="bajar"]').setAttribute('aria-disabled', String(cantidad <= 1));
            item.querySelector('[data-accion="subir"]').setAttribute('aria-disabled', String(cantidad >= p.disponibilidad));
            item.querySelector('[data-accion="quitar"]').setAttribute('aria-label', 'Quitar ' + p.nombre + ' del carrito');

            var aviso = item.querySelector('.item-carrito__aviso');
            aviso.hidden = cantidad < p.disponibilidad;
            aviso.textContent = aviso.hidden ? '' : 'Máximo disponible: ' + p.disponibilidad;
            item.querySelector('.item-carrito__subtotal').textContent = precio(subtotal);
        }

        // Línea del resumen: nombre, cantidad × precio unitario y subtotal.
        function crearLineaResumen(p, cantidad, subtotal) {
            var li = document.createElement('li');
            li.className = 'resumen__item';
            var producto = document.createElement('span');
            producto.className = 'resumen__producto';
            producto.textContent = p.nombre;
            var detalle = document.createElement('span');
            detalle.className = 'resumen__cantidad';
            detalle.textContent = cantidad + ' × ' + precio(p.precio_referencia) + ' / ' + p.unidad;
            producto.appendChild(detalle);
            var valor = document.createElement('span');
            valor.className = 'resumen__subtotal';
            valor.textContent = precio(subtotal);
            li.appendChild(producto);
            li.appendChild(valor);
            return li;
        }

        // Recalcula subtotales y total a partir del estado guardado y los precios vigentes.
        // Los <li> existentes se actualizan en su lugar; solo se crean o quitan los que cambian.
        function pintar() {
            var carrito = leer();
            var ids = Object.keys(carrito).filter(function (id) { return productos[id]; });
            ids.sort(function (a, b) { return productos[a].nombre.localeCompare(productos[b].nombre, 'es'); });

            Object.keys(items).forEach(function (id) {
                if (ids.indexOf(id) === -1) {
                    items[id].remove();
                    delete items[id];
                }
            });
            if (ids.length === 0) {
                mostrar('vacio');
                return;
            }

            var total = 0;
            var unidades = 0;
            resumen.textContent = '';
            ids.forEach(function (id, i) {
                var p = productos[id];
                var cantidad = carrito[id];
                var subtotal = p.precio_referencia * cantidad;
                total += subtotal;
                unidades += cantidad;

                var item = items[id] || (items[id] = crearItem(id));
                if (lista.children[i] !== item) lista.insertBefore(item, lista.children[i] || null);
                actualizarItem(item, p, cantidad, subtotal);
                resumen.appendChild(crearLineaResumen(p, cantidad, subtotal));
            });

            raiz.querySelector('[data-carrito-unidades]').textContent = unidades;
            raiz.querySelector('[data-carrito-total]').textContent = precio(total);
            mostrar('contenido');
        }

        // Trae del servidor los productos del carrito (todos, o solo los que aún no conocemos)
        // y depura los retirados, agotados o con más cantidad que la disponible.
        function cargar(todos) {
            var ids = Object.keys(leer()).filter(function (id) { return todos || !productos[id]; });
            if (ids.length === 0) {
                pintar();
                return;
            }
            if (cargando) {
                pendiente = true;
                return;
            }
            cargando = true;
            if (Object.keys(items).length === 0) {
                estado.textContent = 'Cargando tu carrito…';
                mostrar('estado');
            }

            fetch('/api/carrito?ids=' + ids.join(','), { headers: { Accept: 'application/json' } })
                .then(function (r) {
                    if (!r.ok) throw new Error('HTTP ' + r.status);
                    return r.json();
                })
                .then(function (json) {
                    json.data.forEach(function (p) { productos[p.id] = p; });
                    // Se relee el carrito: pudo cambiar en otra pestaña mientras llegaba la respuesta.
                    var carrito = leer();
                    var cambios = [];
                    ids.forEach(function (id) {
                        var p = productos[id];
                        if (!carrito[id]) return;
                        if (!p || p.disponibilidad <= 0) {
                            cambios.push(p ? '«' + p.nombre + '» se agotó' : 'un producto ya no está publicado');
                            delete carrito[id];
                            delete productos[id]; // si vuelve a agregarse, se consulta de nuevo
                        } else if (carrito[id] > p.disponibilidad) {
                            cambios.push('ajustamos «' + p.nombre + '» a ' + p.disponibilidad + ' (disponibilidad actual)');
                            carrito[id] = p.disponibilidad;
                        }
                    });
                    if (cambios.length) guardar(carrito);
                    pintar();
                    if (cambios.length) avisar('Actualizamos tu carrito: ' + cambios.join('; ') + '.');
                })
                .catch(function () {
                    if (Object.keys(items).length) {
                        avisar('No pudimos actualizar los precios del carrito. Intenta de nuevo en unos minutos.');
                    } else {
                        estado.textContent = 'No pudimos cargar los precios del carrito. Intenta de nuevo en unos minutos.';
                        mostrar('estado');
                    }
                })
                .then(function () {
                    cargando = false;
                    if (pendiente) {
                        pendiente = false;
                        cargar(false);
                    }
                });
        }

        lista.addEventListener('click', function (ev) {
            var boton = ev.target.closest('button[data-accion]');
            if (!boton || boton.getAttribute('aria-disabled') === 'true') return;
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
            enfocar(titulo);
            avisar('Carrito vaciado.');
        });

        cargar(true);

        return {
            sincronizar: function () { cargar(false); }, // cambio en otra pestaña
            recargar: function () { cargar(true); }      // página restaurada desde la caché del navegador
        };
    }

    actualizarContador();
    activarBotones();
    var raiz = document.querySelector('[data-carrito]');
    var paginaCarrito = raiz ? iniciarPaginaCarrito(raiz) : null;

    // Otra pestaña cambió el carrito (key null = se borró todo el almacenamiento del sitio).
    window.addEventListener('storage', function (ev) {
        if (ev.key !== CLAVE && ev.key !== null) return;
        actualizarContador();
        if (paginaCarrito) paginaCarrito.sincronizar();
    });

    // Con «Atrás»/«Adelante» el navegador puede restaurar la página desde su caché (bfcache)
    // tal como estaba: se vuelven a leer el carrito y los datos vigentes.
    window.addEventListener('pageshow', function (ev) {
        if (!ev.persisted) return;
        actualizarContador();
        if (paginaCarrito) paginaCarrito.recargar();
    });
})();
