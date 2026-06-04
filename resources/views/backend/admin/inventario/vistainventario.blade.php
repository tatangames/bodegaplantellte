@extends('adminlte::page')

@section('title', 'Inventario')

@section('content_header')
    <h1>Inventario</h1>
@stop

@section('plugins.Datatables', true)
@section('plugins.DatatablesPlugins', true)
@section('plugins.Sweetalert2', true)

@include('backend.urlglobal')

@section('content_top_nav_right')
    <link href="{{ asset('css/toastr.min.css') }}" type="text/css" rel="stylesheet"/>
    <link href="{{ asset('css/select2.min.css') }}" type="text/css" rel="stylesheet">
    <link href="{{ asset('css/select2-bootstrap-5-theme.min.css') }}" type="text/css" rel="stylesheet">
    <link href="{{ asset('css/estiloToggle.css') }}" type="text/css" rel="stylesheet"/>

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

        <section class="content-header">
            <div class="row align-items-center" style="margin-left: 0; margin-bottom: 10px;">
                <button type="button" onclick="modalAgregar()" class="btn btn-dark btn-sm mr-3">
                    <i class="fas fa-plus-square"></i> Registrar Material
                </button>
                <div class="btn-group" role="group">
                    <button type="button" id="btn-todos"
                            class="btn btn-sm btn-primary"
                            onclick="filtrarTabla('todos')">
                        <i class="fas fa-list mr-1"></i>Todos
                    </button>
                    <button type="button" id="btn-sin-objeto"
                            class="btn btn-sm btn-outline-info"
                            onclick="filtrarTabla('sin_objeto')">
                        <i class="fas fa-unlink mr-1"></i>Sin Objeto Específico
                    </button>
                </div>
            </div>
        </section>

        <section class="content">
            <div class="container-fluid">
                <div class="card card-blue">
                    <div class="card-header">
                        <h3 class="card-title">Listado Catálogo de Materiales</h3>
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

        {{-- ══ Modal Agregar ══ --}}
        <div class="modal fade" id="modalAgregar">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Nuevo Material</h4>
                        <button type="button" class="close" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form id="formulario-nuevo" onsubmit="event.preventDefault(); nuevo();">
                            <div class="card-body">

                                <div class="form-group">
                                    <label>Nombre: <span style="color: red">*</span></label>
                                    <input type="text" class="form-control" autocomplete="off"
                                           onpaste="contarcaracteresIngreso();" onkeyup="contarcaracteresIngreso();"
                                           maxlength="300" id="nombre-nuevo" placeholder="Nombre del material">
                                    <div id="res-caracter-nuevo" style="float: right">0/300</div>
                                </div>

                                <div class="form-group">
                                    <label>Marca Material: (Opcional)</label>
                                    <input type="text" class="form-control" autocomplete="off"
                                           maxlength="100" id="codigo-nuevo" placeholder="Puede ser Modelo del Material">
                                </div>


                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Unidad de Medida: <span style="color: red">*</span></label>
                                            <select class="form-control" id="select-unidad-nuevo" style="width:100%">
                                                <option value="">Seleccione una opción...</option>
                                                @foreach($lUnidad as $sel)
                                                    <option value="{{ $sel->id }}">{{ $sel->nombre }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Código Presupuestario: <span style="color: red">*</span></label>
                                            <select class="form-control" id="select-objeto-nuevo" style="width:100%">
                                                <option value="">— Sin asignar —</option>
                                                @foreach($lObjetoEspecifico as $obj)
                                                    <option value="{{ $obj->id }}">
                                                        {{ $obj->codigo }} — {{ $obj->nombre }}
                                                        @if($obj->cuenta) ({{ $obj->cuenta->nombre }}) @endif
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </form>
                    </div>
                    <div class="modal-footer justify-content-between">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
                        <button type="button" class="btn btn-primary" onclick="nuevo()">Guardar</button>
                    </div>
                </div>
            </div>
        </div>

        {{-- ══ Modal Editar ══ --}}
        <div class="modal fade" id="modalEditar">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Editar Material</h4>
                        <button type="button" class="close" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form id="formulario-editar" onsubmit="event.preventDefault(); editar();">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-12">

                                        <input type="hidden" id="id-editar">

                                        <div class="form-group">
                                            <label>Nombre: <span style="color: red">*</span></label>
                                            <input type="text" class="form-control" autocomplete="off"
                                                   onpaste="contarcaracteresEditar();" onkeyup="contarcaracteresEditar();"
                                                   maxlength="300" id="nombre-editar" placeholder="Nombre del material">
                                            <div id="res-caracter-editar" style="float: right">0/300</div>
                                        </div>

                                        <div class="form-group">
                                            <label>Marca Material: (Opcional)</label>
                                            <input type="text" class="form-control" autocomplete="off"
                                                   maxlength="100" id="codigo-editar" placeholder="Puede ser Modelo del Material">
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Unidad de Medida: <span style="color: red">*</span></label>
                                                    <select class="form-control" id="select-unidad-editar" style="width:100%">
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Código Presupuestario: <small class="text-muted">(opcional)</small></label>
                                                    <select class="form-control" id="select-objeto-editar" style="width:100%">
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer justify-content-between">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
                        <button type="button" class="btn btn-primary" onclick="editar()">Actualizar</button>
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- ══ Modal Proyectos ══ --}}
    <div class="modal fade" id="modalProyectos" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-md" role="document">
            <div class="modal-content">
                <div class="modal-header bg-info">
                    <h5 class="modal-title text-white">
                        <i class="fas fa-boxes mr-2"></i>
                        Distribución — <span id="proyectos-material"></span>
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="proyectos-loading" class="text-center py-4">
                        <i class="fas fa-spinner fa-spin fa-2x"></i>
                    </div>
                    <div id="proyectos-contenido" style="display:none;">
                        <table class="table table-bordered table-sm mb-0">
                            <thead class="thead-dark">
                            <tr>
                                <th class="text-center">Entradas</th>
                                <th class="text-center">Salidas</th>
                                <th class="text-center">Disponible</th>
                            </tr>
                            </thead>
                            <tbody id="proyectos-tbody"></tbody>
                            <tfoot id="proyectos-tfoot" class="font-weight-bold bg-light"></tfoot>
                        </table>
                    </div>
                    <div id="proyectos-vacio" class="text-center text-muted py-4" style="display:none;">
                        <i class="fas fa-inbox fa-2x mb-2"></i>
                        <p>Este material no tiene movimientos registrados.</p>
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
    <script src="{{ asset('js/theme.js') }}"></script>
    <script src="{{ asset('js/select2.min.js') }}" type="text/javascript"></script>

    <script>

        var filtroActual = 'todos';
        var rutaTabla    = "{{ url('/admin/inventario/tabla/index') }}";

        // ── DataTable ─────────────────────────────────────────────────
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
                lengthMenu: [[100, 150, -1], [100, 150, "Todo"]],
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
                    },
                    oAria: {
                        sSortAscending: ": Orden ascendente",
                        sSortDescending: ": Orden descendente"
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

        // ── Inicializar Select2 ───────────────────────────────────────
        function initSelect2() {
            $('#select-unidad-nuevo').select2({
                theme: "bootstrap-5",
                dropdownParent: $('#modalAgregar'),
                language: { noResults: function () { return "No encontrado"; } }
            });
            $('#select-objeto-nuevo').select2({
                theme: "bootstrap-5",
                dropdownParent: $('#modalAgregar'),
                language: { noResults: function () { return "No encontrado"; } }
            });
            $('#select-unidad-editar').select2({
                theme: "bootstrap-5",
                dropdownParent: $('#modalEditar'),
                language: { noResults: function () { return "No encontrado"; } }
            });
            $('#select-objeto-editar').select2({
                theme: "bootstrap-5",
                dropdownParent: $('#modalEditar'),
                language: { noResults: function () { return "No encontrado"; } }
            });
        }

        // ── Carga inicial ─────────────────────────────────────────────
        $(function () {
            initSelect2();
            cargarTabla('todos');
        });

        function cargarTabla(filtro) {
            var url = rutaTabla + '?filtro=' + filtro;
            if ($.fn.DataTable.isDataTable('#tabla')) {
                $('#tabla').DataTable().destroy();
            }
            $('#tablaDatatable').load(url, function () {
                initDataTable();
            });
        }

        window.recargar = function () { cargarTabla(filtroActual); };

        // ── Filtros ───────────────────────────────────────────────────
        function filtrarTabla(filtro) {
            filtroActual = filtro;

            if (filtro === 'todos') {
                $('#btn-todos').removeClass('btn-outline-primary').addClass('btn-primary');
                $('#btn-sin-objeto').removeClass('btn-info').addClass('btn-outline-info');
            } else {
                $('#btn-todos').removeClass('btn-primary').addClass('btn-outline-primary');
                $('#btn-sin-objeto').removeClass('btn-outline-info').addClass('btn-info');
            }

            cargarTabla(filtro);
        }

        // ── Modal Agregar ─────────────────────────────────────────────
        function modalAgregar() {
            document.getElementById("formulario-nuevo").reset();
            document.getElementById('res-caracter-nuevo').innerHTML = '0/300';
            $('#select-unidad-nuevo').val('').trigger('change');
            $('#select-objeto-nuevo').val('').trigger('change');
            $('#modalAgregar').modal({ backdrop: 'static', keyboard: false });
        }

        // ── Nuevo ─────────────────────────────────────────────────────
        function nuevo() {
            var nombre           = $('#nombre-nuevo').val().trim();
            var codigo           = $('#codigo-nuevo').val().trim();
            var unidad           = $('#select-unidad-nuevo').val();
            var id_objespecifico = $('#select-objeto-nuevo').val();

            if (!nombre) { toastr.error('Nombre es requerido'); return; }
            if (!unidad) { toastr.error('Unidad Medida es requerida'); return; }
            if (!id_objespecifico) { toastr.error('Código Presupuestario es requerido'); return; }

            openLoading();
            var formData = new FormData();
            formData.append('nombre',           nombre);
            formData.append('codigo',           codigo);
            formData.append('unidad',           unidad);
            formData.append('id_objespecifico', id_objespecifico);

            axios.post(urlAdmin + '/admin/inventario/nuevo', formData)
                .then((response) => {
                    closeLoading();
                    if (response.data.success === 1) {
                        toastr.success('Registrado correctamente');
                        $('#modalAgregar').modal('hide');
                        cargarTabla(filtroActual);
                    } else {
                        toastr.error('Error al registrar');
                    }
                })
                .catch(() => { closeLoading(); toastr.error('Error al registrar'); });
        }

        // ── Informacion ───────────────────────────────────────────────
        function informacion(id) {
            openLoading();
            document.getElementById("formulario-editar").reset();
            document.getElementById('res-caracter-editar').innerHTML = '0/300';

            axios.post(urlAdmin + '/admin/inventario/informacion', { id: id })
                .then((response) => {
                    closeLoading();
                    if (response.data.success === 1) {

                        $('#id-editar').val(id);
                        $('#nombre-editar').val(response.data.material.nombre);
                        $('#codigo-editar').val(response.data.material.codigo);
                        contarcaracteresEditar();

                        // ── Unidad de medida ──
                        $('#select-unidad-editar').empty().append('<option value="">Seleccione una opción...</option>');
                        $.each(response.data.unidad, function (key, val) {
                            var sel = response.data.material.id_medida == val.id ? ' selected' : '';
                            $('#select-unidad-editar').append(
                                '<option value="' + val.id + '"' + sel + '>' + val.nombre + '</option>'
                            );
                        });
                        $('#select-unidad-editar').trigger('change');

                        // ── Objeto Específico ──
                        $('#select-objeto-editar').empty().append('<option value="">— Sin asignar —</option>');
                        $.each(response.data.objeto_especifico, function (key, val) {
                            var label = val.codigo + ' — ' + val.nombre;
                            if (val.cuenta) { label += ' (' + val.cuenta.nombre + ')'; }
                            var sel = response.data.material.id_objespecifico == val.id ? ' selected' : '';
                            $('#select-objeto-editar').append(
                                '<option value="' + val.id + '"' + sel + '>' + label + '</option>'
                            );
                        });
                        $('#select-objeto-editar').trigger('change');

                        $('#modalEditar').modal({ backdrop: 'static', keyboard: false });

                    } else {
                        toastr.error('Información no encontrada');
                    }
                })
                .catch(() => { closeLoading(); toastr.error('Información no encontrada'); });
        }

        // ── Editar ────────────────────────────────────────────────────
        function editar() {
            var id               = $('#id-editar').val();
            var nombre           = $('#nombre-editar').val().trim();
            var codigo           = $('#codigo-editar').val().trim();
            var unidad           = $('#select-unidad-editar').val();
            var id_objespecifico = $('#select-objeto-editar').val();

            if (!nombre) { toastr.error('Nombre es requerido'); return; }
            if (!unidad) { toastr.error('Unidad Medida es requerida'); return; }
            if (!id_objespecifico) { toastr.error('Código Presupuestario es requerido'); return; }

            openLoading();
            var formData = new FormData();
            formData.append('id',               id);
            formData.append('nombre',           nombre);
            formData.append('codigo',           codigo);
            formData.append('unidad',           unidad);
            formData.append('id_objespecifico', id_objespecifico);

            axios.post(urlAdmin + '/admin/inventario/editar', formData)
                .then((response) => {
                    closeLoading();
                    if (response.data.success === 1) {
                        toastr.success('Actualizado correctamente');
                        $('#modalEditar').modal('hide');
                        cargarTabla(filtroActual);
                    } else {
                        toastr.error('Error al actualizar');
                    }
                })
                .catch(() => { closeLoading(); toastr.error('Error al actualizar'); });
        }

        // ── Contadores de caracteres ──────────────────────────────────
        function contarcaracteresIngreso() {
            setTimeout(function () {
                var cantidad = document.getElementById('nombre-nuevo').value.length;
                document.getElementById('res-caracter-nuevo').innerHTML = cantidad + '/300';
            }, 10);
        }

        function contarcaracteresEditar() {
            setTimeout(function () {
                var cantidad = document.getElementById('nombre-editar').value.length;
                document.getElementById('res-caracter-editar').innerHTML = cantidad + '/300';
            }, 10);
        }

        // ── Ver proyectos ─────────────────────────────────────────────
        function verInventario(id, nombre) {
            $('#proyectos-material').text(nombre);
            $('#proyectos-tbody').html('');
            $('#proyectos-tfoot').html('');
            $('#proyectos-contenido').hide();
            $('#proyectos-vacio').hide();
            $('#proyectos-loading').show();
            $('#modalProyectos').modal('show');

            axios.post(urlAdmin + '/admin/inventario/catalogo', { id: id })
                .then((response) => {
                    $('#proyectos-loading').hide();

                    if (response.data.success === 1) {
                        var t = response.data.totales;

                        // Si todo está en 0, mostrar vacío
                        if (t.entradas === 0 && t.salidas === 0) {
                            $('#proyectos-vacio').show();
                            return;
                        }

                        $('#proyectos-tbody').html(`
                    <tr>
                        <td class="text-center">${t.entradas}</td>
                        <td class="text-center">${t.salidas}</td>
                        <td class="text-center">
                            <strong class="${t.disponible > 0 ? 'text-success' : 'text-danger'}">
                                ${t.disponible}
                            </strong>
                        </td>
                    </tr>
                `);

                        $('#proyectos-contenido').show();
                    } else {
                        $('#proyectos-vacio').show();
                    }
                })
                .catch(() => {
                    $('#proyectos-loading').hide();
                    $('#proyectos-vacio').show();
                    toastr.error('Error al cargar la distribución');
                });
        }

    </script>
@endsection
