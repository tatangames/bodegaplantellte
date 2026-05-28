@extends('adminlte::page')

@section('title', 'Reservas')

@section('content_header')
    <h1>Reservas</h1>
@stop

@section('plugins.Sweetalert2', true)
@include('backend.urlglobal')

@section('content_top_nav_right')
    <link href="{{ asset('css/toastr.min.css') }}" type="text/css" rel="stylesheet" />
    <link href="{{ asset('css/select2.min.css') }}" type="text/css" rel="stylesheet">
    <link href="{{ asset('css/select2-bootstrap-5-theme.min.css') }}" type="text/css" rel="stylesheet">

    <li class="nav-item dropdown">
        <a href="#" class="nav-link" data-toggle="dropdown">
            <i class="fas fa-cogs"></i>
            <span class="d-none d-md-inline">{{ Auth::guard('admin')->user()->nombre }}</span>
        </a>
        <div class="dropdown-menu dropdown-menu-right">
            <a href="{{ route('admin.perfil') }}" class="dropdown-item">
                <i class="fas fa-user mr-2"></i>Editar Perfil
            </a>
        </div>
    </li>
    <li class="nav-item">
        <form action="{{ route('admin.logout') }}" method="POST" class="d-inline">
            @csrf
            <button type="submit" class="nav-link btn btn-link border-0 bg-transparent">
                <i class="fas fa-sign-out-alt"></i>
                <span class="d-none d-md-inline">Cerrar Sesión</span>
            </button>
        </form>
    </li>
@endsection

@section('content')
    <style>
        *:focus { outline: none; }

        .seccion-header {
            background: linear-gradient(135deg, #1a3a6b 0%, #2156af 100%);
            border-radius: 10px 10px 0 0;
            padding: 12px 18px;
        }
        .seccion-header h3 {
            color: #fff; font-size: 14px; font-weight: 700;
            letter-spacing: .05em; text-transform: uppercase; margin: 0;
        }
        .card-info {
            border: none; border-radius: 10px;
            box-shadow: 0 2px 18px rgba(33,86,175,.13); margin-bottom: 20px;
        }
        .field-label {
            font-size: 11px; font-weight: 700; color: #6b7a99;
            text-transform: uppercase; letter-spacing: .06em;
            margin-bottom: 5px; display: block;
        }

        /* ── Tabla ── */
        #tablaReservas thead th {
            background: #6f42c1; color: #fff; font-size: 11px;
            font-weight: 700; text-transform: uppercase;
            border: none !important; padding: 10px 12px;
        }
        #tablaReservas tbody td { vertical-align: middle; font-size: 13px; padding: 8px 10px; }

        /* ── Botón principal ── */
        .btn-despachar {
            background: linear-gradient(135deg, #6f42c1, #5a2d91);
            color: #fff; border: none; border-radius: 8px;
            padding: 10px 28px; font-weight: 400; font-size: 14px;
            box-shadow: 0 4px 14px rgba(111,66,193,.35); transition: all .2s;
        }
        .btn-despachar:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(111,66,193,.45); color: #fff;
        }

        /* ── Selects fila ── */
        .destino-select { font-size: 12px; padding: 4px 6px; border-radius: 6px; border: 1px solid #dee2e6; }
        .proyecto-select { display: none; margin-top: 4px; font-size: 12px; padding: 4px 6px; border-radius: 6px; border: 1px solid #dee2e6; width: 100%; }

        /* ── Fila liberar ── */
        tr.fila-liberar { background: #fff4f4 !important; }
        tr.fila-liberar td { color: #b32d2d; }

        /* ── Cabecera de grupo ── */
        tr.grupo-header td {
            background: #eef1f8 !important;
            border-top: 2px solid #6f42c1 !important;
            padding: 10px 12px !important;
        }
        .grupo-titulo {
            font-size: 13px; font-weight: 700; color: #1a3a6b;
            text-transform: uppercase; letter-spacing: .03em;
        }
        .grupo-contador {
            background: #6f42c1; color: #fff; border-radius: 12px;
            padding: 1px 9px; font-size: 11px; font-weight: 700; margin-left: 8px;
        }
        .grupo-acciones { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
        .grupo-acciones label {
            margin: 0; font-size: 11px; font-weight: 700;
            color: #6b7a99; text-transform: uppercase;
        }
        .grupo-destino-select,
        .grupo-proyecto-select {
            font-size: 12px; padding: 4px 6px; border-radius: 6px;
            border: 1px solid #c3b3e0; min-width: 180px;
        }
        .grupo-proyecto-select { display: none; }
        .btn-toggle-grupo {
            background: none; border: none; color: #6f42c1;
            font-size: 13px; cursor: pointer; padding: 0 4px;
        }

        /* ── Badge modo grupo ── */
        .badge-grupo {
            background: #e8f0ff; color: #2156af;
            border: 1px solid #b3c8f5; border-radius: 10px;
            font-size: 10px; font-weight: 700; padding: 2px 8px;
            margin-left: 8px; text-transform: uppercase; letter-spacing: .04em;
        }
    </style>

    <div id="divcontenedor" style="display:none">

        {{-- Cabecera fecha + descripción --}}
        <section class="content" style="margin-bottom:0">
            <div class="container-fluid">
                <div class="card card-info">
                    <div class="seccion-header">
                        <h3><i class="fas fa-calendar-check mr-2"></i>Datos del Despacho</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <label class="field-label"><i class="fas fa-calendar-alt mr-1"></i>Fecha de Despacho</label>
                                <input type="date" class="form-control" id="fecha-despacho">
                            </div>
                            <div class="col-md-9">
                                <label class="field-label">
                                    <i class="fas fa-align-left mr-1"></i>Descripción
                                    <small style="text-transform:none; font-weight:400">(Opcional)</small>
                                </label>
                                <input type="text" class="form-control" id="descripcion-despacho"
                                       maxlength="800" placeholder="Descripción general del despacho…">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- Tabla reservas --}}
        <section class="content">
            <div class="container-fluid">
                <div class="card card-info">
                    <div class="seccion-header"
                         style="display:flex; justify-content:space-between; align-items:center">
                        <h3 style="margin:0;">
                            <i class="fas fa-lock mr-2"></i>
                            Reservas Pendientes de Despacho
                        </h3>
                        <span id="contador-reservas"
                              style="background:rgba(255,255,255,.2); color:#fff; border-radius:20px;
                                     padding:2px 12px; font-size:12px; font-weight:700"></span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped mb-0"
                                   id="tablaReservas" style="table-layout:fixed; width:100%">
                                <thead>
                                <tr>
                                    <th style="width:4%">
                                        <input type="checkbox" id="chkTodos" onclick="toggleTodos(this)">
                                    </th>
                                    <th style="width:18%">Material</th>
                                    <th style="width:13%">Proyecto Origen</th>
                                    <th style="width:7%">Cant.</th>
                                    <th style="width:11%">Monto</th>
                                    <th style="width:11%">Fecha Reserva</th>
                                    <th style="width:17%">Motivo</th>
                                    <th style="width:19%">Destino</th>
                                </tr>
                                </thead>
                                <tbody id="tbodyReservas"></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer d-flex justify-content-between align-items-center"
                         style="border-top:2px solid #e8eef8; background:#f8faff; border-radius:0 0 10px 10px">
                        <small class="text-muted">
                            Marque el <strong>checkbox del grupo</strong> para registrar todas sus reservas en
                            <strong>una sola salida</strong>. Marque filas individuales para generar una salida por cada una.
                        </small>
                        <button type="button" class="btn-despachar" onclick="preguntaDespachar()">
                            <i class="fas fa-paper-plane mr-1"></i> Procesar Seleccionados
                        </button>
                    </div>
                </div>
            </div>
        </section>

    </div>
@stop

@section('js')
    <script src="{{ asset('js/toastr.min.js') }}"></script>
    <script src="{{ asset('js/axios.min.js') }}"></script>
    <script src="{{ asset('js/sweetalert2.all.min.js') }}"></script>
    <script src="{{ asset('js/alertaPersonalizada.js') }}"></script>
    <script src="{{ asset('js/select2.min.js') }}"></script>

    <script>
        var proyectosActivos = @json($proyectosActivos);
        var opcionesProyecto = "";

        $(document).ready(function () {
            document.getElementById("divcontenedor").style.display = "block";

            var hoy = new Date();
            document.getElementById('fecha-despacho').value = hoy.toJSON().slice(0, 10);

            opcionesProyecto = "<option value='0' disabled selected>Seleccionar proyecto…</option>";
            $.each(proyectosActivos, function (i, p) {
                opcionesProyecto += "<option value='" + p.id + "'>" + p.nombre + "</option>";
            });

            cargarReservas();
        });

        // ─────────────────────────────────────────────────────────────────
        // CARGAR RESERVAS
        // ─────────────────────────────────────────────────────────────────
        function cargarReservas() {
            axios.post(urlAdmin + '/admin/reservas/listar')
                .then((response) => {
                    if (response.data.success !== 1) {
                        toastr.error('Error al cargar reservas');
                        return;
                    }
                    var lista = response.data.reservas.filter(function (r) {
                        return Number(r.despachado) === 0;
                    });
                    renderTabla(lista);
                })
                .catch(() => { toastr.error('Error al cargar reservas'); });
        }

        // ─────────────────────────────────────────────────────────────────
        // RENDERIZAR TABLA
        // ─────────────────────────────────────────────────────────────────
        function renderTabla(lista) {
            $('#tbodyReservas').empty();
            $('#chkTodos').prop('checked', false);
            $('#contador-reservas').text(lista.length + (lista.length === 1 ? ' reserva' : ' reservas'));

            if (lista.length === 0) {
                $('#tbodyReservas').append(
                    "<tr><td colspan='8' class='text-center text-muted py-4'>" +
                    "<i class='fas fa-check-circle mr-2' style='color:#28a745'></i>" +
                    "No hay reservas pendientes</td></tr>"
                );
                return;
            }

            // Agrupar por proyecto origen
            var grupos = {};
            $.each(lista, function (i, r) {
                var clave = r.nombre_proyecto_origen ?? 'Sin proyecto';
                if (!grupos[clave]) grupos[clave] = [];
                grupos[clave].push(r);
            });

            var indiceGrupo = 0;

            $.each(grupos, function (nombreProyecto, reservasGrupo) {
                indiceGrupo++;
                var gid = 'grupo-' + indiceGrupo;

                var totalGrupo = 0;
                $.each(reservasGrupo, function (j, r) {
                    totalGrupo += parseFloat(r.precio ?? 0) * parseFloat(r.cantidad ?? 0);
                });
                var totalGrupoFmt = totalGrupo.toLocaleString('es-SV', {
                    minimumFractionDigits: 2, maximumFractionDigits: 2
                });

                // ── Cabecera de grupo ────────────────────────────────────
                var headerRow =
                    "<tr class='grupo-header' data-grupo='" + gid + "'>" +
                    "<td colspan='8'>" +
                    "<div style='display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px'>" +

                    // Izquierda: toggle + checkbox padre + nombre + contador + total + badge
                    "<div style='display:flex; align-items:center; flex-wrap:wrap; gap:6px'>" +
                    "<button type='button' class='btn-toggle-grupo' " +
                    "onclick=\"toggleGrupo('" + gid + "', this)\">" +
                    "<i class='fas fa-chevron-down'></i></button>" +

                    // ── CHECKBOX PADRE: solo se controla manualmente ──────
                    "<input type='checkbox' class='chk-grupo' data-gid='" + gid + "' " +
                    "onclick=\"toggleSeleccionGrupo('" + gid + "', this)\" " +
                    "style='margin:0 4px'>" +

                    "<span class='grupo-titulo'><i class='fas fa-folder-open mr-1'></i>" +
                    nombreProyecto + "</span>" +
                    "<span class='grupo-contador'>" + reservasGrupo.length + "</span>" +
                    "<span style='margin-left:6px; font-size:12px; color:#6f42c1; font-weight:700'>" +
                    "Total: $" + totalGrupoFmt + "</span>" +
                    "<span class='badge-grupo' id='badge-" + gid + "' style='display:none'>" +
                    "<i class='fas fa-layer-group mr-1'></i>1 salida agrupada</span>" +
                    "</div>" +

                    // Derecha: selects del grupo
                    "<div class='grupo-acciones'>" +
                    "<label><i class='fas fa-magic mr-1'></i>Aplicar a todo el grupo:</label>" +
                    "<select class='grupo-destino-select' " +
                    "onchange=\"aplicarDestinoGrupo('" + gid + "', this)\">" +
                    "<option value=''>— Elegir destino —</option>" +
                    "<option value='proyecto'>Transferir a Proyecto</option>" +
                    "<option value='general'>Salida General</option>" +
                    "<option value='liberar'>Quitar de Reservas (cancelar)</option>" +
                    "</select>" +
                    "<select class='grupo-proyecto-select' id='gproy-" + gid + "' " +
                    "onchange=\"aplicarProyectoGrupo('" + gid + "', this)\">" +
                    opcionesProyecto +
                    "</select>" +
                    "</div>" +

                    "</div></td></tr>";

                $('#tbodyReservas').append(headerRow);

                // ── Filas de reservas ────────────────────────────────────
                $.each(reservasGrupo, function (j, r) {
                    var fechaFmt = r.fecha_reserva
                        ? new Date(r.fecha_reserva).toLocaleDateString('es-SV')
                        : '—';

                    var precio   = parseFloat(r.precio ?? 0);
                    var monto    = precio * parseFloat(r.cantidad ?? 0);
                    var montoFmt = monto.toLocaleString('es-SV', {
                        minimumFractionDigits: 2, maximumFractionDigits: 2
                    });

                    var fila =
                        "<tr data-id='" + r.id + "' class='fila-reserva' data-grupo='" + gid + "'>" +
                        "<td style='text-align:center'>" +
                        // ── CHECKBOX HIJO: NO sincroniza el padre, solo re-evalúa el badge ──
                        "<input type='checkbox' class='chk-reserva' " +
                        "data-grupo='" + gid + "' data-id='" + r.id + "' " +
                        "onchange=\"onCambioFilaCheckbox('" + gid + "')\">" +
                        "</td>" +
                        "<td style='font-size:12px'>" + (r.nombre_material ?? '—') + "</td>" +
                        "<td style='font-size:12px'>" + (r.nombre_proyecto_origen ?? '—') + "</td>" +
                        "<td style='text-align:center; font-weight:700'>" + r.cantidad + "</td>" +
                        "<td style='text-align:right; font-weight:700; font-size:12px'>$" + montoFmt + "</td>" +
                        "<td style='font-size:12px'>" + fechaFmt + "</td>" +
                        "<td style='font-size:12px'>" + (r.descripcion ?? '—') + "</td>" +
                        "<td>" +
                        "<select class='destino-select select-tipo' style='width:100%' " +
                        "onchange=\"cambiarTipoDestino(this, " + r.id + ", '" + gid + "')\">" +
                        "<option value=''>— Elegir destino —</option>" +
                        "<option value='proyecto'>Transferir a Proyecto</option>" +
                        "<option value='general'>Salida General</option>" +
                        "<option value='liberar'>Quitar de Reservas (cancelar)</option>" +
                        "</select>" +
                        "<select class='proyecto-select select-proyecto' id='proy-" + r.id + "' " +
                        "onchange=\"actualizarBadgeGrupo('" + gid + "')\">" +
                        opcionesProyecto +
                        "</select>" +
                        "</td>" +
                        "</tr>";

                    $('#tbodyReservas').append(fila);
                });
            });
        }

        // ─────────────────────────────────────────────────────────────────
        // TOGGLE COLAPSAR / EXPANDIR GRUPO
        // ─────────────────────────────────────────────────────────────────
        function toggleGrupo(gid, btn) {
            $(".fila-reserva[data-grupo='" + gid + "']").toggle();
            $(btn).find('i').toggleClass('fa-chevron-down fa-chevron-right');
        }

        // ─────────────────────────────────────────────────────────────────
        // CHECKBOX PADRE → marca/desmarca TODAS las filas hijas
        // ─────────────────────────────────────────────────────────────────
        function toggleSeleccionGrupo(gid, chk) {
            $(".chk-reserva[data-grupo='" + gid + "']").prop('checked', chk.checked);
            actualizarBadgeGrupo(gid);
        }

        // ─────────────────────────────────────────────────────────────────
        // CHECKBOX GLOBAL (thead)
        // ─────────────────────────────────────────────────────────────────
        function toggleTodos(chk) {
            // Marcar/desmarcar todos los padres e hijos
            $('.chk-grupo').prop('checked', chk.checked);
            $('.chk-reserva').prop('checked', chk.checked);
            // Re-evaluar badges de todos los grupos
            $('.chk-grupo').each(function () {
                actualizarBadgeGrupo($(this).data('gid'));
            });
        }

        // ─────────────────────────────────────────────────────────────────
        // CAMBIO EN FILA INDIVIDUAL
        // FIX: NO toca el checkbox padre (el padre solo se mueve manualmente
        //      o desde toggleTodos / toggleSeleccionGrupo).
        //      Solo re-evalúa el badge.
        // ─────────────────────────────────────────────────────────────────
        function onCambioFilaCheckbox(gid) {
            actualizarBadgeGrupo(gid);
        }

        // ─────────────────────────────────────────────────────────────────
        // BADGE "1 salida agrupada"
        // Condiciones para mostrarlo:
        //   1. Padre marcado
        //   2. TODAS las filas del grupo marcadas
        //   3. Todas tienen el mismo tipoDestino e idDestino
        //   4. El tipoDestino no está vacío
        // ─────────────────────────────────────────────────────────────────
        function actualizarBadgeGrupo(gid) {
            var totalHijos  = $(".chk-reserva[data-grupo='" + gid + "']").length;
            var marcados    = $(".chk-reserva[data-grupo='" + gid + "']:checked").length;
            var padreMarcado = $(".chk-grupo[data-gid='" + gid + "']").prop('checked');

            // Requisito 1 y 2: padre marcado y todos los hijos marcados
            if (!padreMarcado || marcados !== totalHijos || totalHijos === 0) {
                $('#badge-' + gid).hide();
                return;
            }

            // Requisito 3 y 4: mismo destino en todas las filas y no vacío
            var destinosUnicos = new Set();
            var tieneVacio     = false;

            $(".fila-reserva[data-grupo='" + gid + "']").each(function () {
                var tipo = $(this).find('.select-tipo').val() || '';
                if (!tipo) { tieneVacio = true; return false; } // break
                var proy = (tipo === 'proyecto') ? ($(this).find('.select-proyecto').val() || '') : '';
                destinosUnicos.add(tipo + '|' + proy);
            });

            if (!tieneVacio && destinosUnicos.size === 1) {
                $('#badge-' + gid).show();
            } else {
                $('#badge-' + gid).hide();
            }
        }

        // ─────────────────────────────────────────────────────────────────
        // APLICAR DESTINO A TODO EL GRUPO
        // Aplica tipo Y proyecto a TODAS las filas, luego marca padre e hijos
        // ─────────────────────────────────────────────────────────────────
        function aplicarDestinoGrupo(gid, selectEl) {
            var valor       = $(selectEl).val();
            var gproySelect = $('#gproy-' + gid);

            if (valor === 'proyecto') {
                gproySelect.show();
            } else {
                gproySelect.hide().val('0');
                // Si no es proyecto, limpiar proyecto en todas las filas
                $(".fila-reserva[data-grupo='" + gid + "']").each(function () {
                    var idReserva = $(this).data('id');
                    $(this).find('.select-tipo').val(valor);
                    cambiarTipoDestino($(this).find('.select-tipo')[0], idReserva, gid, false);
                });
                // Marcar padre e hijos
                $(".chk-reserva[data-grupo='" + gid + "']").prop('checked', true);
                $(".chk-grupo[data-gid='" + gid + "']").prop('checked', true);
                actualizarBadgeGrupo(gid);
            }

            if (!valor) return;

            if (valor === 'proyecto') {
                // Solo aplicar tipo por ahora; el proyecto se aplica en aplicarProyectoGrupo
                $(".fila-reserva[data-grupo='" + gid + "']").each(function () {
                    var idReserva = $(this).data('id');
                    $(this).find('.select-tipo').val('proyecto');
                    cambiarTipoDestino($(this).find('.select-tipo')[0], idReserva, gid, false);
                });
                // Marcar padre e hijos
                $(".chk-reserva[data-grupo='" + gid + "']").prop('checked', true);
                $(".chk-grupo[data-gid='" + gid + "']").prop('checked', true);
                actualizarBadgeGrupo(gid);
            }
        }

        // ─────────────────────────────────────────────────────────────────
        // APLICAR PROYECTO A TODO EL GRUPO
        // ─────────────────────────────────────────────────────────────────
        function aplicarProyectoGrupo(gid, selectEl) {
            var idProyecto = $(selectEl).val();
            if (!idProyecto || idProyecto === '0') return;

            // Aplica el proyecto a TODAS las filas con tipo=proyecto
            $(".fila-reserva[data-grupo='" + gid + "']").each(function () {
                if ($(this).find('.select-tipo').val() === 'proyecto') {
                    $(this).find('.select-proyecto').val(idProyecto).show();
                }
            });

            actualizarBadgeGrupo(gid);
        }

        // ─────────────────────────────────────────────────────────────────
        // CAMBIO DE TIPO EN FILA INDIVIDUAL
        // skipBadge=false por defecto; se pasa true cuando se llama en lote
        // para evitar N actualizaciones y hacer una sola al final
        // ─────────────────────────────────────────────────────────────────
        function cambiarTipoDestino(selectEl, idReserva, gid, skipBadge) {
            var val        = $(selectEl).val();
            var fila       = $(selectEl).closest('tr');
            var proySelect = $('#proy-' + idReserva);
            // Obtener gid desde el argumento o desde la fila
            var grupoId    = gid || fila.data('grupo');

            if (val === 'proyecto') {
                proySelect.show();
            } else {
                proySelect.hide().val('0');
            }
            fila.toggleClass('fila-liberar', val === 'liberar');

            if (!skipBadge) {
                actualizarBadgeGrupo(grupoId);
            }
        }

        // ─────────────────────────────────────────────────────────────────
        // VALIDAR Y CONFIRMAR
        // ─────────────────────────────────────────────────────────────────
        function preguntaDespachar() {
            var seleccionados = $('.chk-reserva:checked');

            if (seleccionados.length === 0) {
                toastr.warning('Seleccione al menos una reserva');
                return;
            }

            var valido = true;

            seleccionados.each(function () {
                var idReserva = $(this).data('id');
                var gid       = $(this).data('grupo');
                var fila      = $(this).closest('tr');
                var tipo      = fila.find('.select-tipo').val();
                var proyDest  = fila.find('.select-proyecto').val();

                // Si el padre está marcado y el tipo viene del grupo, el proyecto
                // también puede venir del select del grupo
                var padreMarcado = $(".chk-grupo[data-gid='" + gid + "']").prop('checked');
                if (padreMarcado && tipo === 'proyecto' && (!proyDest || proyDest === '0')) {
                    // Intentar tomar el proyecto del selector del grupo
                    var proyGrupo = $('#gproy-' + gid).val();
                    if (proyGrupo && proyGrupo !== '0') {
                        // Aplicar el proyecto del grupo a esta fila silenciosamente
                        $('#proy-' + idReserva).val(proyGrupo).show();
                        proyDest = proyGrupo;
                    }
                }

                if (!tipo) {
                    toastr.error('Defina el destino de todas las reservas seleccionadas');
                    valido = false;
                    return false;
                }
                if (tipo === 'proyecto' && (!proyDest || proyDest === '0')) {
                    toastr.error('Seleccione el proyecto destino para todas las marcadas como "Transferir a Proyecto"');
                    valido = false;
                    return false;
                }
            });

            if (!valido) return;

            // ── Contar grupos completos vs individuales para el mensaje ────
            var gruposCompletos = 0;
            var individuales    = 0;
            var hayLiberadas    = false;

            var infoGrupos = {};
            seleccionados.each(function () {
                var gid  = $(this).data('grupo');
                var tipo = $(this).closest('tr').find('.select-tipo').val();
                if (tipo === 'liberar') hayLiberadas = true;
                if (!infoGrupos[gid]) infoGrupos[gid] = { marcados: 0 };
                infoGrupos[gid].marcados++;
            });

            $.each(infoGrupos, function (gid, info) {
                var totalEnGrupo = $(".chk-reserva[data-grupo='" + gid + "']").length;
                var padreMarcado = $(".chk-grupo[data-gid='" + gid + "']").prop('checked');
                if (padreMarcado && info.marcados === totalEnGrupo) {
                    gruposCompletos++;
                } else {
                    individuales += info.marcados;
                }
            });

            var partes = [];
            if (gruposCompletos > 0)
                partes.push(gruposCompletos + (gruposCompletos === 1
                    ? ' grupo registrará <strong>1 salida agrupada</strong>'
                    : ' grupos registrarán <strong>1 salida agrupada cada uno</strong>'));
            if (individuales > 0)
                partes.push(individuales + (individuales === 1
                    ? ' reserva individual registrará <strong>su propia salida</strong>'
                    : ' reservas individuales registrarán <strong>su propia salida</strong>'));
            if (hayLiberadas)
                partes.push('algunas reservas serán <strong>canceladas</strong> sin generar salida');

            Swal.fire({
                title: '¿Procesar reservas?',
                html:  '• ' + partes.join('<br>• '),
                icon:  'question',
                showCancelButton:   true,
                confirmButtonColor: '#6f42c1',
                cancelButtonColor:  '#d33',
                cancelButtonText:   'Cancelar',
                confirmButtonText:  'Sí, procesar'
            }).then((result) => { if (result.isConfirmed) ejecutarDespacho(); });
        }

        // ─────────────────────────────────────────────────────────────────
        // EJECUTAR DESPACHO
        // ─────────────────────────────────────────────────────────────────
        function ejecutarDespacho() {
            var fecha       = document.getElementById('fecha-despacho').value;
            var descripcion = document.getElementById('descripcion-despacho').value;

            if (!fecha) { toastr.error('Fecha es requerida'); return; }

            // Agrupar checkboxes marcados por gid
            var mapaGrupos = {};

            $('.chk-reserva:checked').each(function () {
                var idReserva = $(this).data('id');
                var gid       = $(this).data('grupo');
                var fila      = $(this).closest('tr');
                var tipo      = fila.find('.select-tipo').val();
                var proyDest  = fila.find('.select-proyecto').val();

                // Si el proyecto de la fila está vacío pero el padre tiene proyecto, usarlo
                if (tipo === 'proyecto' && (!proyDest || proyDest === '0')) {
                    var proyGrupo = $('#gproy-' + gid).val();
                    if (proyGrupo && proyGrupo !== '0') proyDest = proyGrupo;
                }

                if (!mapaGrupos[gid]) mapaGrupos[gid] = [];
                mapaGrupos[gid].push({
                    idReserva:   idReserva,
                    tipoDestino: tipo,
                    idDestino:   (tipo === 'proyecto') ? proyDest : null,
                });
            });

            // Armar payload: { esGrupo, gid, items }
            var despachos = [];

            $.each(mapaGrupos, function (gid, filas) {
                var totalEnGrupo = $(".chk-reserva[data-grupo='" + gid + "']").length;
                var padreMarcado = $(".chk-grupo[data-gid='" + gid + "']").prop('checked');

                // Verificar destino uniforme
                var destinosUnicos = new Set();
                filas.forEach(function (f) {
                    var proy = (f.tipoDestino === 'proyecto') ? (f.idDestino || '') : '';
                    destinosUnicos.add(f.tipoDestino + '|' + proy);
                });

                var esGrupoCompleto = padreMarcado
                    && (filas.length === totalEnGrupo)
                    && (destinosUnicos.size === 1);

                despachos.push({
                    esGrupo: esGrupoCompleto,
                    gid:     gid,
                    items:   filas,
                });
            });

            openLoading();
            var formData = new FormData();
            formData.append('fecha',       fecha);
            formData.append('descripcion', descripcion);
            formData.append('despachos',   JSON.stringify(despachos));

            axios.post(urlAdmin + '/admin/reservas/despachar', formData)
                .then((response) => {
                    closeLoading();
                    if (response.data.success === 10) {
                        Swal.fire({
                            title: 'Proceso Exitoso',
                            text:  'Las reservas seleccionadas han sido procesadas correctamente.',
                            icon:  'success',
                            allowOutsideClick:  false,
                            confirmButtonColor: '#6f42c1',
                            confirmButtonText:  'Aceptar'
                        }).then((r) => { if (r.isConfirmed) location.reload(); });
                    } else if (response.data.success === 2) {
                        toastr.error(response.data.msg ?? 'Error en reserva');
                    } else if (response.data.success === 4) {
                        toastr.error(response.data.msg ?? 'Proyecto destino cerrado');
                    } else {
                        toastr.error('Error al despachar');
                    }
                })
                .catch(() => { toastr.error('Error al despachar'); closeLoading(); });
        }
    </script>
@endsection
