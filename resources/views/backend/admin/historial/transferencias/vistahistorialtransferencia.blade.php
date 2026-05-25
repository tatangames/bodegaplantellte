@extends('adminlte::page')

@section('title', 'Historial / Transferencias')

@section('content_header')
    <h1>Historial / Transferencias</h1>
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

                        {{-- Fila 1: Proyecto + Fechas + Botones --}}
                        <div class="row align-items-end">
                            <div class="col-md-4">
                                <label class="font-weight-bold">Proyecto</label>
                                <select class="form-control" id="filtro-proyecto">
                                    <option value="">— Todos —</option>
                                    @foreach($arrayProyectos as $p)
                                        <option value="{{ $p->id }}"
                                                data-cerrado="{{ $p->transferido ? '1' : '0' }}">
                                            {{ $p->nombre }}
                                        </option>
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
                            <div class="col-md-2">
                                <button class="btn btn-primary btn-block mb-1" onclick="recargar()">
                                    <i class="fas fa-search mr-1"></i> Filtrar
                                </button>
                                <button class="btn btn-secondary btn-block" onclick="limpiarFiltros()">
                                    <i class="fas fa-times mr-1"></i> Limpiar
                                </button>
                            </div>
                        </div>

                        {{-- Fila 2: Material + Documento --}}
                        <div class="row align-items-end mt-3">
                            <div class="col-md-5">
                                <label class="font-weight-bold">
                                    <i class="fas fa-box mr-1 text-muted"></i> Buscar por material
                                </label>
                                <input type="text"
                                       class="form-control"
                                       id="filtro-material"
                                       placeholder="Ej: cemento, varilla...">
                            </div>
                            <div class="col-md-5">
                                <label class="font-weight-bold">
                                    <i class="fas fa-file-alt mr-1 text-muted"></i> Buscar por documento
                                </label>
                                <input type="text"
                                       class="form-control"
                                       id="filtro-documento"
                                       placeholder="Ej: TRF-001...">
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <small class="text-muted">Filtra por material en el detalle o número de documento.</small>
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
                        <h3 class="card-title">Listado de Transferencias</h3>
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

    {{-- ══ Modal Detalle ══ --}}
    <div class="modal fade" id="modalDetalle" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-xl" role="document">   {{-- modal-xl para más columnas --}}
            <div class="modal-content">
                <div class="modal-header bg-info">
                    <h5 class="modal-title text-white">
                        <i class="fas fa-exchange-alt mr-2"></i>
                        Detalle de Transferencia —
                        <span id="detalle-proyecto"></span>
                        <small class="ml-2" id="detalle-fecha"></small>
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="detalle-desc-row" class="mb-3" style="display:none;">
                        <strong>Descripción:</strong> <span id="detalle-descripcion"></span>
                    </div>

                    <div id="detalle-loading" class="text-center py-4">
                        <i class="fas fa-spinner fa-spin fa-2x"></i>
                    </div>
                    <div id="detalle-contenido" style="display:none;">
                        <table class="table table-bordered table-striped table-sm">
                            <thead class="thead-dark">
                            <tr>
                                <th style="width:4%">#</th>
                                <th style="width:28%">Material</th>
                                <th style="width:28%">Objeto Específico</th>   {{-- ← nueva columna --}}
                                <th class="text-center" style="width:12%">Cantidad sobrante</th>
                                <th class="text-right" style="width:14%">Precio unitario</th>
                                <th class="text-right" style="width:14%">Subtotal</th>
                            </tr>
                            </thead>
                            <tbody id="detalle-tbody"></tbody>
                            <tfoot>
                            <tr class="table-dark">
                                <td colspan="5" class="text-right font-weight-bold">Total estimado:</td>
                                <td class="text-right font-weight-bold" id="detalle-total"></td>
                            </tr>
                            </tfoot>
                        </table>
                    </div>
                    <div id="detalle-vacio" class="text-center text-muted py-4" style="display:none;">
                        <i class="fas fa-inbox fa-2x mb-2"></i>
                        <p>Esta transferencia no tiene materiales registrados.</p>
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
        $(function () {
            const ruta = "{{ url('/admin/historial/transferencias/tabla') }}";

            // ── Select2 ───────────────────────────────────────────
            $('#filtro-proyecto').select2({
                theme: 'bootstrap-5',
                placeholder: '— Todos —',
                allowClear: true,
                language: { noResults: function () { return 'No encontrado'; } },
                templateResult: function (data) {
                    if (!data.id) return data.text;
                    var cerrado = $(data.element).data('cerrado') == '1';
                    return $('<span class="d-flex align-items-center justify-content-between">')
                        .append($('<span>').text(data.text))
                        .append($('<span>')
                            .addClass(cerrado ? 'badge badge-danger ml-2' : 'badge badge-success ml-2')
                            .text(cerrado ? 'Cerrado' : 'Activo'));
                },
                templateSelection: function (data) {
                    if (!data.id) return data.text;
                    var cerrado = $(data.element).data('cerrado') == '1';
                    return $('<span>')
                        .append($('<span>').text(data.text))
                        .append($('<span>')
                            .addClass(cerrado ? 'badge badge-danger ml-2' : 'badge badge-success ml-2')
                            .text(cerrado ? 'Cerrado' : 'Activo'));
                }
            });

            // ── DataTable ─────────────────────────────────────────
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

            // ── Cargar tabla ──────────────────────────────────────
            function cargarTabla() {
                const proyecto   = $('#filtro-proyecto').val();
                const fechaDesde = $('#filtro-fecha-desde').val();
                const fechaHasta = $('#filtro-fecha-hasta').val();
                const material   = $('#filtro-material').val().trim();
                const documento  = $('#filtro-documento').val().trim();

                const params = new URLSearchParams();
                if (proyecto)   params.append('proyecto',    proyecto);
                if (fechaDesde) params.append('fecha_desde', fechaDesde);
                if (fechaHasta) params.append('fecha_hasta', fechaHasta);
                if (material)   params.append('material',    material);
                if (documento)  params.append('documento',   documento);

                const url = params.toString() ? ruta + '?' + params.toString() : ruta;

                $('#tablaDatatable').load(url, function () {
                    initDataTable();
                });
            }

            window.recargar        = function () { cargarTabla(); };
            window.limpiarFiltros  = function () {
                $('#filtro-proyecto').val('').trigger('change');
                $('#filtro-fecha-desde').val('');
                $('#filtro-fecha-hasta').val('');
                $('#filtro-material').val('');
                $('#filtro-documento').val('');
                cargarTabla();
            };

            cargarTabla();
        });
    </script>

    <script>
        // ── Detalle ───────────────────────────────────────────────
        function verDetalle(id, proyecto, fecha, documento, descripcion) {
            $('#detalle-proyecto').text(proyecto);
            $('#detalle-fecha').text(fecha);
            $('#detalle-tbody').html('');
            $('#detalle-total').text('');
            $('#detalle-contenido').hide();
            $('#detalle-vacio').hide();
            $('#detalle-loading').show();

            // Documento
            if (documento) {
                $('#detalle-doc-row').show();
            } else {
                $('#detalle-doc-row').hide();
            }

            // Descripción
            if (descripcion) {
                $('#detalle-descripcion').text(descripcion);
                $('#detalle-desc-row').show();
            } else {
                $('#detalle-desc-row').hide();
            }

            $('#modalDetalle').modal('show');

            axios.post(urlAdmin + '/admin/historial/transferencias/detalle', {
                id: id
            })
                .then((response) => {
                    $('#detalle-loading').hide();

                    if (response.data.success === 1 &&
                        response.data.detalle.length > 0) {

                        let html = '';
                        let total = 0;

                        response.data.detalle.forEach((fila, index) => {
                            const precio = parseFloat(fila.precio);
                            const cantidad = parseInt(fila.cantidad_sobrante);
                            const subtotal = precio * cantidad;

                            total += subtotal;

                            html += `
                <tr>
                    <td>${index + 1}</td>
                    <td>${fila.nombre_material}</td>
                    <td>
                        <small class="text-muted">
                            ${fila.objeto_especifico}
                        </small>
                    </td>
                    <td class="text-center">${cantidad}</td>
                    <td class="text-right">$${fila.precio}</td>
                    <td class="text-right">$${subtotal.toFixed(4)}</td>
                </tr>`;
                        });

                        $('#detalle-tbody').html(html);
                        $('#detalle-total').text('$' + total.toFixed(4));
                        $('#detalle-contenido').show();

                    } else {
                        $('#detalle-vacio').show();
                    }
                })
                .catch((error) => {
                    $('#detalle-loading').hide();
                    $('#detalle-vacio').show();

                    console.error(error);
                    toastr.error('Error al cargar el detalle');
                });
        }



        // ── Eliminar ──────────────────────────────────────────────
        function eliminar(id) {
            Swal.fire({
                title: '¿Eliminar transferencia?',
                text: 'Se eliminarán también todos los materiales del detalle. Esta acción no se puede deshacer.',
                type: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.value) {
                    openLoading();
                    axios.post(urlAdmin + '/admin/historial/transferencias/eliminar', { id: id })
                        .then((response) => {
                            closeLoading();
                            if (response.data.success === 1) {
                                toastr.success('Transferencia eliminada correctamente');
                                recargar();
                            } else {
                                toastr.error('Error al eliminar');
                            }
                        })
                        .catch(() => { closeLoading(); toastr.error('Error al eliminar'); });
                }
            });
        }
    </script>
@endsection
