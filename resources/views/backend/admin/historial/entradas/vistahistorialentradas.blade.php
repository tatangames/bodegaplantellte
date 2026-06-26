@extends('adminlte::page')

@section('title', 'Historial / Entradas')

@section('content_header')
    <h1>Historial / Entradas</h1>
@stop

@section('plugins.Datatables', true)
@section('plugins.DatatablesPlugins', true)
@section('plugins.Sweetalert2', true)

@include('backend.urlglobal')

@section('content_top_nav_right')
    <link href="{{ asset('css/toastr.min.css') }}" type="text/css" rel="stylesheet"/>
    <link href="{{ asset('css/select2.min.css') }}" type="text/css" rel="stylesheet">
    <link href="{{ asset('css/select2-bootstrap-5-theme.min.css') }}" type="text/css" rel="stylesheet">

    <li class="nav-item dropdown">
        <a href="#" class="nav-link" data-toggle="dropdown">
            <i class="fas fa-cogs"></i>
            <span class="d-none d-md-inline">{{ Auth::guard('admin')->user()->nombre }}</span>
        </a>
        <div class="dropdown-menu dropdown-menu-right">
            <a href="{{ route('admin.perfil') }}" class="dropdown-item">
                <i class="fas fa-user mr-2"></i> Editar Perfil
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

@section('css')
    <style>
        /* ══ Fix Select2 + modal zoom ══════════════════════════════════════════ */
        .select2-container--open,
        .select2-dropdown,
        .select2-dropdown--below,
        .select2-dropdown--above { z-index: 99999 !important; }
        .select2-dropdown { box-sizing: border-box !important; }

        .modal .select2-container--bootstrap-5 .select2-selection { min-height: 38px !important; }
        .modal .select2-container--bootstrap-5 .select2-selection--single {
            height: 38px !important;
            padding: 0.375rem 2.25rem 0.375rem 0.75rem !important;
            display: flex !important; align-items: center !important;
        }
        .modal .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
            padding: 0 !important; line-height: 1.5 !important; color: #212529 !important;
        }
        .modal .select2-container--bootstrap-5 .select2-selection--single .select2-selection__placeholder {
            color: #6c757d !important;
        }
        .modal .select2-container--bootstrap-5 .select2-selection--single .select2-selection__arrow {
            height: 36px !important; top: 1px !important; right: 6px !important;
        }
        .select2-search--dropdown { padding: 8px !important; }
        .select2-search--dropdown .select2-search__field {
            width: 100% !important; padding: 6px 10px !important;
            border: 1px solid #ced4da !important; border-radius: 4px !important;
            font-size: 13px !important; box-sizing: border-box !important;
            pointer-events: auto !important; user-select: text !important;
            -webkit-user-select: text !important; cursor: text !important;
        }
        .select2-search--dropdown .select2-search__field:focus {
            border-color: #3b82f6 !important;
            box-shadow: 0 0 0 3px rgba(59,130,246,.15) !important;
            outline: none !important;
        }
        .select2-container--bootstrap-5 .select2-results__option {
            font-size: 13px !important; padding: 6px 12px !important;
        }
        .select2-container--bootstrap-5 .select2-results__option--highlighted {
            background-color: #3b82f6 !important; color: #fff !important;
        }
    </style>
@stop

@section('content')
    <div id="divcontenedor">

        {{-- ══ FILTROS ══ --}}
        <section class="content" style="margin-bottom:0">
            <div class="container-fluid">
                <div class="card card-blue">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-filter mr-1"></i> Filtros</h3>
                    </div>
                    <div class="card-body">
                        <div class="row align-items-end">
                            <div class="col-md-3">
                                <label class="font-weight-bold">Fecha desde</label>
                                <input type="date" class="form-control" id="filtro-fecha-desde">
                            </div>
                            <div class="col-md-3">
                                <label class="font-weight-bold">Fecha hasta</label>
                                <input type="date" class="form-control" id="filtro-fecha-hasta">
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <div style="width:100%">
                                    <button class="btn btn-primary btn-block mb-1" onclick="recargar()">
                                        <i class="fas fa-search mr-1"></i> Filtrar
                                    </button>
                                    <button class="btn btn-secondary btn-block" onclick="limpiarFiltros()">
                                        <i class="fas fa-times mr-1"></i> Limpiar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- ══ TABLA ══ --}}
        <section class="content">
            <div class="container-fluid">
                <div class="card card-blue">
                    <div class="card-header">
                        <h3 class="card-title">Listado de Entradas</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div id="tablaDatatable">
                                    {{-- Aviso inicial --}}
                                    <div class="text-center text-muted py-5" id="aviso-inicial">
                                        <i class="fas fa-filter fa-3x mb-3" style="color:#cbd5e1"></i>
                                        <p class="mb-0" style="font-size:15px">
                                            Selecciona un rango de fechas y presiona <strong>Filtrar</strong> para ver las entradas.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    {{-- ══ Modal Editar Entrada ══ --}}
    <div class="modal fade" id="modalEditar" tabindex="-1">
        <div class="modal-dialog modal-md">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title text-white">
                        <i class="fas fa-edit mr-2"></i>Editar Entrada
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="formulario-editar">
                        <input type="hidden" id="id-editar">
                        <div class="form-group">
                            <label>Fecha <span class="text-danger">*</span></label>
                            <input type="date" id="fecha-editar" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Tipo de Entrada <span class="text-danger">*</span></label>
                            <select id="select-tipoentrada-editar" class="form-control" style="width:100%">
                                <option value="">Seleccione...</option>
                                @foreach($arrayTipoEntrada as $te)
                                    <option value="{{ $te->id }}">{{ $te->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Tipo de Compra <span class="text-danger">*</span></label>
                            <select id="select-tipocompra-editar" class="form-control" style="width:100%">
                                <option value="">Seleccione...</option>
                                @foreach($arrayTipoCompra as $tc)
                                    <option value="{{ $tc->id }}">{{ $tc->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Proveedor <small class="text-muted">(Opcional)</small></label>
                            <select id="select-proveedor-editar" class="form-control" style="width:100%">
                                <option value="">— Sin asignar —</option>
                                @foreach($arrayProveedor as $prov)
                                    <option value="{{ $prov->id }}">{{ $prov->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Factura <small class="text-muted">(Opcional)</small></label>
                            <input type="text" id="factura-editar" class="form-control" maxlength="100">
                        </div>
                        <div class="form-group">
                            <label>Descripción <small class="text-muted">(Opcional)</small></label>
                            <textarea id="descripcion-editar" class="form-control" rows="3" maxlength="800"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-warning" onclick="editar()">
                        <i class="fas fa-save mr-1"></i> Guardar cambios
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ══ Modal Detalle Entrada ══ --}}
    <div class="modal fade" id="modalDetalle" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-info">
                    <h5 class="modal-title text-white">
                        <i class="fas fa-list mr-2"></i>
                        Detalle — <span id="detalle-titulo"></span>
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="detalle-loading" class="text-center py-4">
                        <i class="fas fa-spinner fa-spin fa-2x"></i>
                    </div>
                    <div id="detalle-contenido" style="display:none;">
                        <table class="table table-bordered table-striped table-sm">
                            <thead class="thead-dark">
                            <tr>
                                <th>#</th>
                                <th>Material</th>
                                <th>Detalle/Código</th>
                                <th class="text-center">Cantidad</th>
                                <th class="text-right">Precio unitario</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                            </thead>
                            <tbody id="detalle-tbody"></tbody>
                        </table>
                    </div>
                    <div id="detalle-vacio" class="text-center text-muted py-4" style="display:none;">
                        <i class="fas fa-inbox fa-2x mb-2"></i>
                        <p>Esta entrada no tiene materiales registrados.</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    {{-- ══ Modal Editar Detalle ══ --}}
    <div class="modal fade" id="modalEditarDetalle" tabindex="-1">
        <div class="modal-dialog modal-md">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title text-white">
                        <i class="fas fa-edit mr-2"></i>Editar Material
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="formulario-editar-detalle">
                        <input type="hidden" id="detalle-id-editar">
                        <div class="form-group">
                            <label>Material</label>
                            <input type="text" id="detalle-material-editar" class="form-control" disabled>
                        </div>
                        <div class="form-group">
                            <label>
                                Cantidad <span class="text-danger">*</span>
                                <small id="detalle-cantidad-aviso" class="text-danger ml-1" style="display:none;">
                                    (no editable — tiene salidas)
                                </small>
                            </label>
                            <input type="number" id="detalle-cantidad-editar" class="form-control"
                                   min="1" max="1000000" placeholder="0">
                        </div>
                        <div class="form-group">
                            <label>Detalle <small class="text-muted">(Opcional)</small></label>
                            <input type="text" id="detalle-codigo-editar" class="form-control" maxlength="100">
                        </div>
                        <div class="form-group">
                            <label>Precio unitario <span class="text-danger">*</span></label>
                            <input type="number" id="detalle-precio-editar" class="form-control"
                                   step="0.0001" min="0" placeholder="0.0000">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-warning" onclick="editarDetalle()">
                        <i class="fas fa-save mr-1"></i> Guardar
                    </button>
                </div>
            </div>
        </div>
    </div>
@stop

@section('js')
    <script src="{{ asset('js/toastr.min.js') }}" type="text/javascript"></script>
    <script src="{{ asset('js/axios.min.js') }}" type="text/javascript"></script>
    <script src="{{ asset('js/alertaPersonalizada.js') }}"></script>
    <script src="{{ asset('js/select2.min.js') }}" type="text/javascript"></script>

    <script>
        var _entradaIdActual     = null;
        var _entradaTituloActual = '';

        const avisoHtml = `
            <div class="text-center text-muted py-5">
                <i class="fas fa-filter fa-3x mb-3" style="color:#cbd5e1"></i>
                <p class="mb-0" style="font-size:15px">
                    Selecciona un rango de fechas y presiona <strong>Filtrar</strong> para ver las entradas.
                </p>
            </div>`;

        // ══ Fix Bootstrap _enforceFocus (Select2 zoom) ════════════════
        if (typeof $ !== 'undefined' && $.fn.modal && $.fn.modal.Constructor && $.fn.modal.Constructor.prototype) {
            var __modalProto = $.fn.modal.Constructor.prototype;
            if (__modalProto._enforceFocus) { __modalProto._enforceFocus = function () {}; }
            if (__modalProto.enforceFocus)  { __modalProto.enforceFocus  = function () {}; }
            if (__modalProto._focustrap)    { __modalProto._focustrap = { activate: function(){}, deactivate: function(){} }; }
        }

        $(function () {
            const ruta = "{{ url('/admin/historial/entradas/tabla') }}";

            // ── DataTable ─────────────────────────────────────────
            function initDataTable() {
                if ($.fn.DataTable.isDataTable('#tabla')) {
                    $('#tabla').DataTable().destroy();
                }
                if ($('#tabla').length === 0) return;

                $('#tabla').DataTable({
                    paging: true,
                    lengthChange: true,
                    searching: true,
                    ordering: true,
                    info: true,
                    autoWidth: false,
                    responsive: true,
                    pagingType: "full_numbers",
                    lengthMenu: [[50, 100, -1], [50, 100, "Todo"]],
                    language: {
                        sProcessing:   "Procesando...",
                        sLengthMenu:   "Mostrar _MENU_ registros",
                        sZeroRecords:  "No se encontraron resultados",
                        sEmptyTable:   "Ningún dato disponible en esta tabla",
                        sInfo:         "Mostrando _START_ a _END_ de _TOTAL_ registros",
                        sInfoEmpty:    "Mostrando 0 a 0 de 0 registros",
                        sInfoFiltered: "(filtrado de _MAX_ registros)",
                        sSearch:       "Buscar:",
                        oPaginate: {
                            sFirst: "Primero", sLast: "Último",
                            sNext: "Siguiente", sPrevious: "Anterior"
                        }
                    },
                    dom:
                        "<'row align-items-center'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6 text-md-right'f>>" +
                        "tr" +
                        "<'row align-items-center'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>"
                });
                $('#tabla_length select').addClass('form-control form-control-sm');
                $('#tabla_filter input').addClass('form-control form-control-sm').css('display', 'inline-block');
            }

            // ── Cargar tabla ──────────────────────────────────────
            function cargarTabla() {
                const fechaDesde = $('#filtro-fecha-desde').val();
                const fechaHasta = $('#filtro-fecha-hasta').val();

                const params = new URLSearchParams();
                if (fechaDesde) params.append('fecha_desde', fechaDesde);
                if (fechaHasta) params.append('fecha_hasta', fechaHasta);

                const url = ruta + (params.toString() ? '?' + params.toString() : '');
                $('#tablaDatatable').load(url, function () { initDataTable(); });
            }

            window.recargar = function () { cargarTabla(); };

            window.limpiarFiltros = function () {
                $('#filtro-fecha-desde').val('');
                $('#filtro-fecha-hasta').val('');
                cargarTabla();
            };

            // ── Carga inicial: solo mostrar aviso ─────────────────
            $('#tablaDatatable').html(avisoHtml);

            // ── Select2 modales con body como padre (fix zoom) ────
            ['select-tipoentrada-editar', 'select-tipocompra-editar', 'select-proveedor-editar'].forEach(function (id) {
                $('#' + id).select2({
                    theme: 'bootstrap-5',
                    dropdownParent: $('body'),
                    language: { noResults: function () { return 'No encontrado'; } },
                    width: '100%'
                });
            });

            // Auto-enfocar campo de búsqueda al abrir Select2
            $(document).on('select2:open', function () {
                var field = document.querySelector('.select2-container--open .select2-search__field');
                if (field) field.focus();
            });

            // ── Delegación botones detalle ────────────────────────
            $(document).on('click', '.btn-editar-detalle', function () {
                const btn = $(this);
                modalEditarDetalle(
                    btn.data('id'),
                    btn.data('material'),
                    btn.data('codigo'),
                    btn.data('precio'),
                    btn.data('cantidad'),
                    btn.data('tiene-salidas') == 1
                );
            });

            $(document).on('click', '.btn-eliminar-detalle', function () {
                eliminarDetalle($(this).data('id'), $(this).data('material'));
            });
        });

        // ── Editar cabecera ───────────────────────────────────────
        function modalEditar(id) {
            openLoading();
            document.getElementById('formulario-editar').reset();

            axios.post(urlAdmin + '/admin/historial/entradas/informacion', { id: id })
                .then((response) => {
                    closeLoading();
                    if (response.data.success === 1) {
                        const e = response.data.entrada;
                        $('#id-editar').val(e.id);
                        $('#fecha-editar').val(e.fecha);
                        $('#factura-editar').val(e.factura ?? '');
                        $('#descripcion-editar').val(e.descripcion ?? '');
                        $('#select-tipoentrada-editar').val(e.id_tipoentrada).trigger('change');
                        $('#select-tipocompra-editar').val(e.id_tipocompra).trigger('change');
                        $('#select-proveedor-editar').val(e.id_proveedor ?? '').trigger('change');
                        $('#modalEditar').modal('show');
                    } else {
                        toastr.error('No se pudo cargar la información');
                    }
                })
                .catch(() => { closeLoading(); toastr.error('Error al obtener información'); });
        }

        function editar() {
            const id          = $('#id-editar').val();
            const fecha       = $('#fecha-editar').val().trim();
            const tipoentrada = $('#select-tipoentrada-editar').val();
            const tipocompra  = $('#select-tipocompra-editar').val();
            const proveedor   = $('#select-proveedor-editar').val();
            const factura     = $('#factura-editar').val().trim();
            const descripcion = $('#descripcion-editar').val().trim();

            if (!fecha)       { toastr.error('La fecha es requerida');        return; }
            if (!tipoentrada) { toastr.error('Tipo de Entrada es requerido'); return; }
            if (!tipocompra)  { toastr.error('Tipo de Compra es requerido');  return; }

            openLoading();
            const formData = new FormData();
            formData.append('id',             id);
            formData.append('fecha',          fecha);
            formData.append('id_tipoentrada', tipoentrada);
            formData.append('id_tipocompra',  tipocompra);
            formData.append('id_proveedor',   proveedor);
            formData.append('factura',        factura);
            formData.append('descripcion',    descripcion);

            axios.post(urlAdmin + '/admin/historial/entradas/editar', formData)
                .then((response) => {
                    closeLoading();
                    if (response.data.success === 1) {
                        toastr.success('Entrada actualizada correctamente');
                        $('#modalEditar').modal('hide');
                        recargar();
                    } else {
                        toastr.error('Error al actualizar');
                    }
                })
                .catch(() => { closeLoading(); toastr.error('Error al actualizar'); });
        }

        // ── Eliminar entrada ──────────────────────────────────────
        function eliminar(id) {
            Swal.fire({
                title: '¿Eliminar entrada?',
                text: 'Se eliminarán también todos los materiales asociados. Esta acción no se puede deshacer.',
                type: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.value) {
                    openLoading();
                    axios.post(urlAdmin + '/admin/historial/entradas/eliminar', { id: id })
                        .then((response) => {
                            closeLoading();
                            switch (response.data.success) {
                                case 1:
                                    toastr.success('Entrada eliminada correctamente');
                                    recargar();
                                    break;
                                case 2:
                                    Swal.fire({
                                        title: 'No se puede eliminar',
                                        text: response.data.msg,
                                        icon: 'warning',
                                        confirmButtonColor: '#d33',
                                        confirmButtonText: 'Entendido'
                                    });
                                    break;
                                case 0:
                                    toastr.error('La entrada no existe');
                                    recargar();
                                    break;
                                default:
                                    toastr.error('Error al eliminar');
                            }
                        })
                        .catch(() => { closeLoading(); toastr.error('Error al eliminar'); });
                }
            });
        }

        // ── Ver detalle ───────────────────────────────────────────
        function verDetalle(id, titulo) {
            _entradaIdActual     = id;
            _entradaTituloActual = titulo;

            $('#detalle-titulo').text(titulo);
            $('#detalle-tbody').html('');
            $('#detalle-contenido').hide();
            $('#detalle-vacio').hide();
            $('#detalle-loading').show();
            $('#modalDetalle').modal('show');

            axios.post(urlAdmin + '/admin/historial/entradas/detalle', { id: id })
                .then((response) => {
                    $('#detalle-loading').hide();
                    if (response.data.success === 1 && response.data.detalle.length > 0) {
                        let html = '';
                        response.data.detalle.forEach((fila, index) => {
                            html += `
                                <tr>
                                    <td>${index + 1}</td>
                                    <td>${fila.material}</td>
                                    <td>${fila.codigo}</td>
                                    <td class="text-center">${fila.cantidad_inicial}</td>
                                    <td class="text-right">$${fila.precio}</td>
                                    <td class="text-center text-nowrap">
                                        <button type="button"
                                                class="btn btn-warning btn-xs btn-editar-detalle mr-1"
                                                data-id="${fila.id}"
                                                data-material="${fila.material}"
                                                data-codigo="${fila.codigo}"
                                                data-precio="${fila.precio_raw}"
                                                data-cantidad="${fila.cantidad_inicial}"
                                                data-tiene-salidas="${fila.tiene_salidas}">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button"
                                                class="btn btn-danger btn-xs btn-eliminar-detalle"
                                                data-id="${fila.id}"
                                                data-material="${fila.material}">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>`;
                        });
                        $('#detalle-tbody').html(html);
                        $('#detalle-contenido').show();
                    } else {
                        $('#detalle-vacio').show();
                    }
                })
                .catch(() => {
                    $('#detalle-loading').hide();
                    $('#detalle-vacio').show();
                    toastr.error('Error al cargar el detalle');
                });
        }

        function recargarDetalle() {
            if (_entradaIdActual) {
                verDetalle(_entradaIdActual, _entradaTituloActual);
            }
        }

        // ── Editar detalle ────────────────────────────────────────
        function modalEditarDetalle(id, material, codigo, precio, cantidad, tieneSalidas) {
            document.getElementById('formulario-editar-detalle').reset();
            $('#detalle-id-editar').val(id);
            $('#detalle-material-editar').val(material);
            $('#detalle-codigo-editar').val(codigo);
            $('#detalle-precio-editar').val(precio);
            $('#detalle-cantidad-editar').val(cantidad);

            if (tieneSalidas) {
                $('#detalle-cantidad-editar').prop('disabled', true);
                $('#detalle-cantidad-aviso').show();
            } else {
                $('#detalle-cantidad-editar').prop('disabled', false);
                $('#detalle-cantidad-aviso').hide();
            }

            $('#modalEditarDetalle').modal('show');
        }

        function editarDetalle() {
            const id       = $('#detalle-id-editar').val();
            const codigo   = $('#detalle-codigo-editar').val().trim();
            const precio   = $('#detalle-precio-editar').val().trim();
            const cantidad = $('#detalle-cantidad-editar').val();
            const disabled = $('#detalle-cantidad-editar').prop('disabled');

            if (precio === '' || isNaN(precio) || parseFloat(precio) < 0) {
                toastr.error('Precio inválido'); return;
            }
            if (!disabled && (cantidad === '' || parseInt(cantidad) <= 0)) {
                toastr.error('Cantidad debe ser mayor a 0'); return;
            }

            openLoading();
            const formData = new FormData();
            formData.append('id',     id);
            formData.append('codigo', codigo);
            formData.append('precio', precio);
            if (!disabled) {
                formData.append('cantidad', cantidad);
            }

            axios.post(urlAdmin + '/admin/historial/entradas/detalle/editar', formData)
                .then((response) => {
                    closeLoading();
                    if (response.data.success === 1) {
                        toastr.success('Actualizado correctamente');
                        $('#modalEditarDetalle').modal('hide');
                        recargarDetalle();
                    } else if (response.data.success === 2) {
                        Swal.fire({
                            title: 'No se puede modificar',
                            text: response.data.msg,
                            icon: 'warning',
                            confirmButtonColor: '#d33',
                            confirmButtonText: 'Entendido'
                        });
                    } else {
                        toastr.error('Error al actualizar');
                    }
                })
                .catch(() => { closeLoading(); toastr.error('Error al actualizar'); });
        }

        // ── Eliminar detalle ──────────────────────────────────────
        function eliminarDetalle(id, material) {
            Swal.fire({
                title: '¿Eliminar material?',
                html: `Se eliminará: <b>${material}</b><br><small class="text-muted">Si es el último material, la entrada también será eliminada.</small>`,
                type: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.value) {
                    openLoading();
                    axios.post(urlAdmin + '/admin/historial/entradas/detalle/eliminar', { id: id })
                        .then((response) => {
                            closeLoading();
                            switch (response.data.success) {
                                case 1:
                                    if (response.data.entrada_borrada) {
                                        toastr.success('Material eliminado. La entrada fue eliminada por quedar vacía.');
                                        $('#modalDetalle').modal('hide');
                                        recargar();
                                    } else {
                                        toastr.success('Material eliminado correctamente');
                                        recargarDetalle();
                                        recargar();
                                    }
                                    break;
                                case 4:
                                    Swal.fire({
                                        title: 'No se puede eliminar',
                                        text: response.data.msg,
                                        icon: 'warning',
                                        confirmButtonColor: '#d33',
                                        confirmButtonText: 'Entendido'
                                    });
                                    break;
                                case 0:
                                    toastr.error('El material no existe o ya fue eliminado');
                                    break;
                                default:
                                    toastr.error('Error al eliminar');
                            }
                        })
                        .catch(() => { closeLoading(); toastr.error('Error al eliminar'); });
                }
            });
        }
    </script>
@endsection
