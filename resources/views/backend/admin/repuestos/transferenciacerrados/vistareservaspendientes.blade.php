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
        #tablaReservas thead th {
            background: #6f42c1; color: #fff; font-size: 11px;
            font-weight: 700; text-transform: uppercase;
            border: none !important; padding: 10px 12px;
        }
        #tablaReservas tbody td { vertical-align: middle; font-size: 13px; padding: 8px 10px; }

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

        .destino-select { font-size: 12px; padding: 4px 6px; border-radius: 6px; border: 1px solid #dee2e6; }
        .proyecto-select { display: none; margin-top: 4px; font-size: 12px; padding: 4px 6px; border-radius: 6px; border: 1px solid #dee2e6; width: 100%; }

        tr.fila-liberar { background: #fff4f4 !important; }
        tr.fila-liberar td { color: #b32d2d; }

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
                            Defina el destino por grupo o por fila, luego procese las reservas seleccionadas
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

        // ── Cargar solo reservas pendientes ───────────────────────────────
        function cargarReservas() {
            axios.post(urlAdmin + '/admin/reservas/listar')
                .then((response) => {
                    if (response.data.success !== 1) {
                        toastr.error('Error al cargar reservas');
                        return;
                    }

                    // Solo pendientes (despachado = 0)
                    var lista = response.data.reservas.filter(function (r) {
                        return Number(r.despachado) === 0;
                    });

                    renderTabla(lista);
                })
                .catch(() => { toastr.error('Error al cargar reservas'); });
        }

        // ── Renderizar tabla ──────────────────────────────────────────────
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
                    "<div style='display:flex; align-items:center'>" +
                    "<button type='button' class='btn-toggle-grupo' " +
                    "onclick=\"toggleGrupo('" + gid + "', this)\">" +
                    "<i class='fas fa-chevron-down'></i></button>" +
                    "<input type='checkbox' class='chk-grupo' " +
                    "onclick=\"toggleSeleccionGrupo('" + gid + "', this)\" " +
                    "style='margin:0 8px'>" +
                    "<span class='grupo-titulo'><i class='fas fa-folder-open mr-1'></i>" +
                    nombreProyecto + "</span>" +
                    "<span class='grupo-contador'>" + reservasGrupo.length + "</span>" +
                    "<span style='margin-left:10px; font-size:12px; color:#6f42c1; font-weight:700'>" +
                    "Total: $" + totalGrupoFmt + "</span>" +
                    "</div>" +
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
                        "<input type='checkbox' class='chk-reserva' data-grupo='" + gid + "' data-id='" + r.id + "'>" +
                        "</td>" +
                        "<td style='font-size:12px'>" + (r.nombre_material ?? '—') + "</td>" +
                        "<td style='font-size:12px'>" + (r.nombre_proyecto_origen ?? '—') + "</td>" +
                        "<td style='text-align:center; font-weight:700'>" + r.cantidad + "</td>" +
                        "<td style='text-align:right; font-weight:700; font-size:12px'>$" + montoFmt + "</td>" +
                        "<td style='font-size:12px'>" + fechaFmt + "</td>" +
                        "<td style='font-size:12px'>" + (r.descripcion ?? '—') + "</td>" +
                        "<td>" +
                        "<select class='destino-select select-tipo' style='width:100%' " +
                        "onchange=\"cambiarTipoDestino(this, " + r.id + ")\">" +
                        "<option value=''>— Elegir destino —</option>" +
                        "<option value='proyecto'>Transferir a Proyecto</option>" +
                        "<option value='general'>Salida General</option>" +
                        "<option value='liberar'>Quitar de Reservas (cancelar)</option>" +
                        "</select>" +
                        "<select class='proyecto-select select-proyecto' id='proy-" + r.id + "'>" +
                        opcionesProyecto +
                        "</select>" +
                        "</td>" +
                        "</tr>";

                    $('#tbodyReservas').append(fila);
                });
            });
        }

        // ── Colapsar/expandir grupo ───────────────────────────────────────
        function toggleGrupo(gid, btn) {
            $(".fila-reserva[data-grupo='" + gid + "']").toggle();
            $(btn).find('i').toggleClass('fa-chevron-down fa-chevron-right');
        }

        function toggleSeleccionGrupo(gid, chk) {
            $(".chk-reserva[data-grupo='" + gid + "']").prop('checked', chk.checked);
        }

        function toggleTodos(chk) {
            $('.chk-reserva').prop('checked', chk.checked);
            $('.chk-grupo').prop('checked', chk.checked);
        }

        // ── Asignación masiva de destino por grupo ────────────────────────
        function aplicarDestinoGrupo(gid, selectEl) {
            var valor       = $(selectEl).val();
            var gproySelect = $('#gproy-' + gid);

            if (valor === 'proyecto') {
                gproySelect.show();
            } else {
                gproySelect.hide().val('0');
            }
            if (!valor) return;

            $(".fila-reserva[data-grupo='" + gid + "']").each(function () {
                var idReserva  = $(this).data('id');
                var filaSelect = $(this).find('.select-tipo');
                filaSelect.val(valor);
                cambiarTipoDestino(filaSelect[0], idReserva);
            });
            $(".chk-reserva[data-grupo='" + gid + "']").prop('checked', true);
        }

        function aplicarProyectoGrupo(gid, selectEl) {
            var idProyecto = $(selectEl).val();
            if (!idProyecto || idProyecto === '0') return;

            $(".fila-reserva[data-grupo='" + gid + "']").each(function () {
                if ($(this).find('.select-tipo').val() === 'proyecto') {
                    $(this).find('.select-proyecto').val(idProyecto).show();
                }
            });
        }

        function cambiarTipoDestino(selectEl, idReserva) {
            var val        = $(selectEl).val();
            var fila       = $(selectEl).closest('tr');
            var proySelect = $('#proy-' + idReserva);

            if (val === 'proyecto') {
                proySelect.show();
            } else {
                proySelect.hide().val('0');
            }
            fila.toggleClass('fila-liberar', val === 'liberar');
        }

        // ── Confirmar despacho ────────────────────────────────────────────
        function preguntaDespachar() {
            var seleccionados = $('.chk-reserva:checked');

            if (seleccionados.length === 0) {
                toastr.warning('Seleccione al menos una reserva');
                return;
            }

            var valido = true;
            seleccionados.each(function () {
                var fila     = $(this).closest('tr');
                var tipo     = fila.find('.select-tipo').val();
                var proyDest = fila.find('.select-proyecto').val();

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

            var hayLiberadas = false;
            seleccionados.each(function () {
                if ($(this).closest('tr').find('.select-tipo').val() === 'liberar') {
                    hayLiberadas = true;
                }
            });

            var textoConfirm = hayLiberadas
                ? 'Algunas reservas se cancelarán (no se despacharán ni generarán salida) y las demás generarán sus salidas correspondientes.'
                : 'Se generarán las salidas correspondientes y las reservas quedarán marcadas como despachadas.';

            Swal.fire({
                title: '¿Procesar reservas?',
                text:  textoConfirm,
                icon:  'question',
                showCancelButton:   true,
                confirmButtonColor: '#6f42c1',
                cancelButtonColor:  '#d33',
                cancelButtonText:   'Cancelar',
                confirmButtonText:  'Sí, procesar'
            }).then((result) => { if (result.isConfirmed) ejecutarDespacho(); });
        }

        function ejecutarDespacho() {
            var fecha       = document.getElementById('fecha-despacho').value;
            var descripcion = document.getElementById('descripcion-despacho').value;

            if (!fecha) { toastr.error('Fecha es requerida'); return; }

            var despachos = [];
            $('.chk-reserva:checked').each(function () {
                var idReserva = $(this).data('id');
                var fila      = $(this).closest('tr');
                var tipo      = fila.find('.select-tipo').val();
                var proyDest  = fila.find('.select-proyecto').val();

                despachos.push({
                    idReserva:   idReserva,
                    tipoDestino: tipo,
                    idDestino:   (tipo === 'proyecto') ? proyDest : null,
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
                    } else {
                        toastr.error('Error al despachar');
                    }
                })
                .catch(() => { toastr.error('Error al despachar'); closeLoading(); });
        }
    </script>
@endsection
