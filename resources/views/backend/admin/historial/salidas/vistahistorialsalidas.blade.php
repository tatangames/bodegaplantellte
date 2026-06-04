@extends('adminlte::page')

@section('title', 'Historial / Salidas')

@section('content_header')
    <h1>Historial / Salidas</h1>
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
                                <label class="font-weight-bold">Equipo</label>
                                <select class="form-control" id="filtro-equipo">
                                    <option value="">— Todos —</option>
                                    @foreach($arrayEquipos as $eq)
                                        <option value="{{ $eq->id }}">{{ $eq->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
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
                        <div class="row align-items-end mt-3">
                            <div class="col-md-6">
                                <label class="font-weight-bold">Buscar por material</label>
                                <input type="text" class="form-control" id="filtro-material"
                                       placeholder="Nombre del material...">
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
                        <h3 class="card-title">Listado de Salidas</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div id="tablaDatatable"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    {{-- ══ Modal Editar Salida ══ --}}
    <div class="modal fade" id="modalEditar" tabindex="-1">
        <div class="modal-dialog modal-md">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title text-white">
                        <i class="fas fa-edit mr-2"></i>Editar Salida
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
                            <label>Equipo <span class="text-danger">*</span></label>
                            <select id="select-equipo-editar" class="form-control" style="width:100%">
                                <option value="">Seleccione...</option>
                                @foreach($arrayEquipos as $eq)
                                    <option value="{{ $eq->id }}">{{ $eq->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>N. Talonario <small class="text-muted">(Opcional)</small></label>
                            <input type="text" id="talonario-editar" class="form-control" maxlength="100">
                        </div>
                        <div class="form-group">
                            <label>Nombre quien recibe <small class="text-muted">(Opcional)</small></label>
                            <input type="text" id="fichaNombre-editar" class="form-control" maxlength="100">
                        </div>
                        <div class="form-group">
                            <label>Descripción <small class="text-muted">(Opcional)</small></label>
                            <textarea id="descripcion-editar" class="form-control"
                                      rows="3" maxlength="800"></textarea>
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

    {{-- ══ Modal Detalle Salida ══ --}}
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
                        <p>Esta salida no tiene materiales registrados.</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
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
        var _salidaIdActual     = null;
        var _salidaTituloActual = '';

        $(function () {
            const ruta = "{{ url('/admin/historial/salidas/tabla') }}";

            function initDataTable() {
                if ($.fn.DataTable.isDataTable('#tabla')) {
                    $('#tabla').DataTable().destroy();
                }
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

            function cargarTabla() {
                const equipo     = $('#filtro-equipo').val();
                const fechaDesde = $('#filtro-fecha-desde').val();
                const fechaHasta = $('#filtro-fecha-hasta').val();
                const material   = $('#filtro-material').val().trim();

                const params = new URLSearchParams();
                if (equipo)     params.append('equipo',      equipo);
                if (fechaDesde) params.append('fecha_desde', fechaDesde);
                if (fechaHasta) params.append('fecha_hasta', fechaHasta);
                if (material)   params.append('material',    material);

                const url = params.toString() ? ruta + '?' + params.toString() : ruta;
                $('#tablaDatatable').load(url, function () { initDataTable(); });
            }

            window.recargar = function () { cargarTabla(); };

            window.limpiarFiltros = function () {
                $('#filtro-equipo').val('').trigger('change');
                $('#filtro-fecha-desde').val('');
                $('#filtro-fecha-hasta').val('');
                $('#filtro-material').val('');
                cargarTabla();
            };

            $('#filtro-equipo').select2({
                theme: 'bootstrap-5',
                placeholder: '— Todos —',
                allowClear: true,
                language: { noResults: function () { return 'No encontrado'; } }
            });

            $('#select-equipo-editar').select2({
                theme: 'bootstrap-5',
                dropdownParent: $('#modalEditar'),
                language: { noResults: function () { return 'No encontrado'; } }
            });

            // Delegación botones detalle
            $(document).on('click', '.btn-eliminar-detalle-salida', function () {
                eliminarDetalleItem($(this).data('id'), $(this).data('material'), $(this).data('salida-id'));
            });

            cargarTabla();
        });

        // ── Editar cabecera ───────────────────────────────────────
        function modalEditar(id) {
            openLoading();
            document.getElementById('formulario-editar').reset();

            axios.post(urlAdmin + '/admin/historial/salidas/informacion', { id: id })
                .then((response) => {
                    closeLoading();
                    if (response.data.success === 1) {
                        const s = response.data.salida;
                        $('#id-editar').val(s.id);
                        $('#fecha-editar').val(s.fecha ? s.fecha.substring(0, 10) : '');
                        $('#descripcion-editar').val(s.descripcion ?? '');
                        $('#talonario-editar').val(s.ficha_talonario ?? '');
                        $('#fichaNombre-editar').val(s.ficha_nombre ?? '');
                        $('#select-equipo-editar').val(s.id_equipo).trigger('change');
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
            const equipo      = $('#select-equipo-editar').val();
            const descripcion = $('#descripcion-editar').val().trim();
            const talonario   = $('#talonario-editar').val().trim();
            const fichaNombre = $('#fichaNombre-editar').val().trim();

            if (!fecha)  { toastr.error('La fecha es requerida');  return; }
            if (!equipo) { toastr.error('El equipo es requerido'); return; }

            openLoading();
            const formData = new FormData();
            formData.append('id',              id);
            formData.append('fecha',           fecha);
            formData.append('id_equipo',       equipo);
            formData.append('descripcion',     descripcion);
            formData.append('ficha_talonario', talonario);
            formData.append('ficha_nombre',    fichaNombre);

            axios.post(urlAdmin + '/admin/historial/salidas/editar', formData)
                .then((response) => {
                    closeLoading();
                    if (response.data.success === 1) {
                        toastr.success('Salida actualizada correctamente');
                        $('#modalEditar').modal('hide');
                        recargar();
                    } else {
                        toastr.error('Error al actualizar');
                    }
                })
                .catch(() => { closeLoading(); toastr.error('Error al actualizar'); });
        }

        // ── Eliminar salida completa ──────────────────────────────
        function eliminar(id) {
            Swal.fire({
                title: '¿Eliminar salida?',
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
                    axios.post(urlAdmin + '/admin/historial/salidas/eliminar', { id: id })
                        .then((response) => {
                            closeLoading();
                            if (response.data.success === 1) {
                                toastr.success('Salida eliminada correctamente');
                                recargar();
                            } else {
                                toastr.error('Error al eliminar');
                            }
                        })
                        .catch(() => { closeLoading(); toastr.error('Error al eliminar'); });
                }
            });
        }

        // ── Ver detalle ───────────────────────────────────────────
        function verDetalle(id, titulo) {
            _salidaIdActual     = id;
            _salidaTituloActual = titulo;

            $('#detalle-titulo').text(titulo);
            $('#detalle-tbody').html('');
            $('#detalle-contenido').hide();
            $('#detalle-vacio').hide();
            $('#detalle-loading').show();
            $('#modalDetalle').modal('show');

            axios.post(urlAdmin + '/admin/historial/salidas/detalle', { id: id })
                .then((response) => {
                    $('#detalle-loading').hide();
                    if (response.data.success === 1 && response.data.detalle.length > 0) {
                        let html = '';
                        response.data.detalle.forEach((fila, index) => {
                            html += `
                                <tr>
                                    <td>${index + 1}</td>
                                    <td>${fila.material}</td>
                                    <td class="text-center">${fila.cantidad_salida}</td>
                                    <td class="text-right">$${fila.precio}</td>
                                    <td class="text-center">
                                        <button type="button"
                                                class="btn btn-danger btn-xs btn-eliminar-detalle-salida"
                                                data-id="${fila.id}"
                                                data-material="${fila.material}"
                                                data-salida-id="${id}">
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
            if (_salidaIdActual) {
                verDetalle(_salidaIdActual, _salidaTituloActual);
            }
        }

        // ── Eliminar item de detalle ──────────────────────────────
        function eliminarDetalleItem(id, material, salidaId) {
            Swal.fire({
                title: '¿Eliminar material?',
                html: `Se eliminará: <b>${material}</b><br><small class="text-muted">Si es el último material, la salida también será eliminada.</small>`,
                type: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.value) {
                    openLoading();
                    axios.post(urlAdmin + '/admin/historial/salidas/detalle/eliminar', { id: id })
                        .then((response) => {
                            closeLoading();
                            switch (response.data.success) {
                                case 1:
                                    if (response.data.salida_borrada) {
                                        toastr.success('Material eliminado. La salida fue eliminada por quedar vacía.');
                                        $('#modalDetalle').modal('hide');
                                        recargar();
                                    } else {
                                        toastr.success('Material eliminado correctamente');
                                        recargarDetalle();
                                        recargar();
                                    }
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
