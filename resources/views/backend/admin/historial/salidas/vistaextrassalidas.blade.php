@extends('adminlte::page')

@section('title', 'Agregar Extras')

@section('content_header')
    <h1>Agregar Extras a Salida</h1>
@stop

@section('plugins.Sweetalert2', true)
@include('backend.urlglobal')

@section('content_top_nav_right')
    <link href="{{ asset('css/toastr.min.css') }}" type="text/css" rel="stylesheet"/>

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
    <style>
        table { table-layout: fixed; }
        #matriz-busqueda { table-layout: fixed; }
        #matrizM { table-layout: auto; }
        .cursor-pointer:hover { cursor: pointer; color: #401fd2; font-weight: bold; }
        *:focus { outline: none; }
    </style>

    <div id="divcontenedor">

        {{-- Info de la salida existente --}}
        <section class="content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-10">
                        <div class="card card-gray-dark">
                            <div class="card-header">
                                <h3 class="card-title">
                                    Salida #{{ $salida->id }} — {{ $salida->equipo->nombre ?? '' }}
                                </h3>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3">
                                        <label class="text-muted">Fecha</label>
                                        <p><strong>{{ date('d/m/Y', strtotime($salida->fecha)) }}</strong></p>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="text-muted">Talonario</label>
                                        <p><strong>{{ $salida->ficha_talonario ?? '—' }}</strong></p>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="text-muted">Descripción</label>
                                        <p><strong>{{ $salida->descripcion ?? '' }}</strong></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- Botones --}}
        <section class="content-header">
            <div class="row" style="margin-left: 0">
                <button type="button" onclick="abrirModal()" class="btn btn-primary btn-sm">
                    <i class="fas fa-search mr-1"></i> Buscar Material
                </button>
                <a href="{{ route('admin.historial.salidas.index') }}"
                   class="btn btn-secondary btn-sm ml-2">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
            </div>
        </section>

        {{-- Modal buscar material --}}
        <div class="modal fade" id="modalRepuesto">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header" style="background:#2156af">
                        <h4 class="modal-title text-white">
                            <i class="fas fa-search mr-2"></i>Buscar Material
                        </h4>
                        <button type="button" class="close text-white" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form id="formulario-repuesto">
                            <div class="card-body">
                                <div class="form-group">
                                    <label>Material — Solo con inventario disponible</label>
                                    <table class="table" id="matriz-busqueda">
                                        <tbody>
                                        <tr>
                                            <td>
                                                <input id="inputBuscador" autocomplete="off"
                                                       class="form-control" style="width:100%"
                                                       onkeyup="buscarMaterial(this)"
                                                       maxlength="300" type="text"
                                                       placeholder="Escribir nombre del material…">
                                                <div class="droplista"
                                                     style="position:absolute;z-index:9;width:95% !important;"></div>
                                            </td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div id="tablaRepuesto"></div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Modal cantidad --}}
        <div class="modal fade" id="modalCantidad">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header" style="background:#1a3a6b">
                        <h4 class="modal-title text-white">
                            <i class="fas fa-boxes mr-2"></i>Salida de Material
                        </h4>
                        <button type="button" class="close text-white" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form id="formulario-material">
                            <div class="card-body">
                                <input type="hidden" id="id-material-seleccionado">
                                <div class="form-row mb-3">
                                    <div class="col-md-9">
                                        <label>Material</label>
                                        <input type="text" disabled class="form-control" id="info-material">
                                    </div>
                                    <div class="col-md-3">
                                        <label>U/M</label>
                                        <input type="text" disabled class="form-control" id="info-medida">
                                    </div>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-sm" id="matrizM">
                                        <thead class="thead-dark">
                                        <tr>
                                            <th>Fecha Ingreso</th>
                                            <th>Valor</th>
                                            <th>Cant. Actual</th>
                                            <th>Cant. Salida</th>
                                        </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer justify-content-between">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                        <button type="button" class="btn btn-success" onclick="agregarAlDetalle()">
                            <i class="fas fa-plus mr-1"></i> Agregar al Detalle
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tabla detalle --}}
        <section class="content-header">
            <div class="row mb-2">
                <div class="col-sm-6" style="margin-left:15px">
                    <h2>Materiales a Sacar</h2>
                </div>
            </div>
        </section>

        <section class="content">
            <div class="container-fluid">
                <div class="card card-gray-dark">
                    <div class="card-header">
                        <h3 class="card-title">Detalle</h3>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover mb-0" id="matriz"
                                   style="table-layout:auto; width:100%; word-break:break-word;">
                                <thead class="thead-dark">
                                <tr>
                                    <th style="width:5%">#</th>
                                    <th>Material</th>
                                    <th style="width:12%">Salida</th>
                                    <th style="width:10%">Opciones</th>
                                </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- Botones finales --}}
        <div class="modal-footer justify-content-between" style="margin-top:25px;">
            <div></div>
            <button type="button" class="btn btn-success" onclick="preguntaGuardar()">
                <i class="fas fa-save mr-1"></i> Guardar Extras
            </button>
        </div>

    </div>
@stop

@section('js')
    <script src="{{ asset('js/toastr.min.js') }}"></script>
    <script src="{{ asset('js/axios.min.js') }}"></script>
    <script src="{{ asset('js/sweetalert2.all.min.js') }}"></script>
    <script src="{{ asset('js/alertaPersonalizada.js') }}"></script>

    <script>
        const ID_SALIDA = {{ $salida->id }};
        window.seguroBuscador = true;

        $(document).ready(function () {
            $(document).click(function () { $('.droplista').hide(); });
        });

        // ── Modal buscador ────────────────────────────────────────
        function abrirModal() {
            document.getElementById('tablaRepuesto').innerHTML = '';
            document.getElementById('formulario-repuesto').reset();
            $('#modalRepuesto').modal('show');
        }

        // ── Buscar material ───────────────────────────────────────
        function buscarMaterial(e) {
            if (seguroBuscador) {
                seguroBuscador = false;
                var row   = $(e).closest('tr');
                var texto = e.value;

                axios.post(urlAdmin + '/admin/buscar/material/disponible', { query: texto })
                    .then((response) => {
                        seguroBuscador = true;
                        $(row).find('.droplista').fadeIn().html(response.data);
                    })
                    .catch(() => { seguroBuscador = true; });
            }
        }

        // ── Seleccionar material → modal cantidades ───────────────
        function modificarValor(edrop) {
            openLoading();
            $('#matrizM tbody tr').remove();

            var formData = new FormData();
            formData.append('id', edrop.id);

            axios.post(urlAdmin + '/admin/buscar/material/disponibilidad', formData)
                .then((response) => {
                    closeLoading();
                    if (response.data.success !== 1) {
                        toastr.error('Error al cargar material'); return;
                    }
                    if (response.data.disponible === 1) {
                        toastr.info('NO HAY INVENTARIO DISPONIBLE'); return;
                    }

                    $('#id-material-seleccionado').val(edrop.id);
                    $('#info-material').val(response.data.nombreMaterial);
                    $('#info-medida').val(response.data.nombreMedida);

                    $.each(response.data.arrayIngreso, function (key, val) {
                        var markup =
                            '<tr>' +
                            '<td><input disabled value="' + val.fechaIngreso + '" class="form-control form-control-sm" type="text"></td>' +
                            '<td><input disabled value="' + val.precioFormat + '" class="form-control form-control-sm" type="text"></td>' +
                            '<td><input name="arrayCantidadActual[]" disabled ' +
                            'data-cantidadActualFila="' + val.cantidadActual + '" ' +
                            'value="' + val.cantidadActual + '" class="form-control form-control-sm" type="number"></td>' +
                            '<td><input class="form-control form-control-sm" ' +
                            'data-idfilaentradadetalle="' + val.id + '" ' +
                            'name="arrayCantidadSalida[]" min="0" max="' + val.cantidadActual + '" type="number" ' +
                            'onkeydown="return validateInput(event);" ' +
                            'oninput="validateCantidadSalida(this, ' + val.cantidadActual + ');"></td>' +
                            '</tr>';
                        $('#matrizM tbody').append(markup);
                    });

                    $('#modalCantidad').modal('show');
                })
                .catch(() => { closeLoading(); toastr.error('Error'); });
        }

        // ── Agregar al detalle local ───────────────────────────────
        function agregarAlDetalle() {
            var arrayIdEntradaDetalle = $("input[name='arrayCantidadSalida[]']")
                .map(function () { return $(this).attr('data-idfilaentradadetalle'); }).get();
            var arrayCantidadSalida = $("input[name='arrayCantidadSalida[]']")
                .map(function () { return $(this).val(); }).get();
            var arrayCantidadActual = $("input[name='arrayCantidadActual[]']")
                .map(function () { return $(this).attr('data-cantidadActualFila'); }).get();

            var habraSalida = true;
            for (var a = 0; a < arrayCantidadSalida.length; a++) {
                var fc = arrayCantidadSalida[a];
                if (fc !== '') {
                    if (parseInt(fc) <= 0) {
                        toastr.error('Fila #' + (a + 1) + ': No se permite cero'); return;
                    }
                    habraSalida = false;
                }
                if (fc !== '' && parseInt(fc) > parseInt(arrayCantidadActual[a])) {
                    toastr.error('Fila #' + (a + 1) + ': Supera cantidad actual'); return;
                }
            }
            if (habraSalida) { toastr.error('Registrar mínimo 1 salida'); return; }

            var nombreTexto = document.getElementById('info-material').value;
            var nFilas      = $('#matriz > tbody > tr').length;

            for (var z = 0; z < arrayCantidadSalida.length; z++) {
                var fc2 = arrayCantidadSalida[z];
                if (fc2 !== '' && parseInt(fc2) > 0) {
                    nFilas++;
                    var markup =
                        '<tr>' +
                        '<td><span class="num-fila">' + nFilas + '</span></td>' +
                        '<td>' +
                        '<input name="idmaterialArray[]" type="hidden" data-idmaterialArray="' + arrayIdEntradaDetalle[z] + '">' +
                        nombreTexto +
                        '</td>' +
                        '<td>' +
                        '<input name="salidaArray[]" type="hidden" data-cantidadSalida="' + fc2 + '">' +
                        fc2 +
                        '</td>' +
                        '<td>' +
                        '<button type="button" class="btn btn-danger btn-sm btn-block" onclick="borrarFila(this)">' +
                        '<i class="fas fa-trash"></i></button>' +
                        '</td>' +
                        '</tr>';
                    $('#matriz tbody').append(markup);
                }
            }

            $('#modalCantidad').modal('hide');
            document.getElementById('inputBuscador').value = '';
            toastr.success('Agregado al detalle');
        }

        // ── Guardar ───────────────────────────────────────────────
        function preguntaGuardar() {
            if ($('#matriz > tbody > tr').length === 0) {
                toastr.error('Agrega al menos un material'); return;
            }
            Swal.fire({
                title: '¿Guardar materiales extras?',
                text: 'Se agregarán a la salida #' + ID_SALIDA,
                type: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#d33',
                cancelButtonText: 'Cancelar',
                confirmButtonText: 'Sí, guardar'
            }).then((result) => {
                if (result.value) guardarExtras();
            });
        }

        function guardarExtras() {
            var idEntradaDetalle = $("input[name='idmaterialArray[]']")
                .map(function () { return $(this).attr('data-idmaterialArray'); }).get();
            var salidaCantidad   = $("input[name='salidaArray[]']")
                .map(function () { return $(this).attr('data-cantidadSalida'); }).get();

            const contenedorArray = [];
            for (var i = 0; i < salidaCantidad.length; i++) {
                contenedorArray.push({
                    infoIdEntradaDeta: idEntradaDetalle[i],
                    infoCantidad:      salidaCantidad[i],
                });
            }

            openLoading();
            var formData = new FormData();
            formData.append('id_salida',       ID_SALIDA);
            formData.append('contenedorArray', JSON.stringify(contenedorArray));

            axios.post(urlAdmin + '/admin/historial/salidas/extras/guardar', formData)
                .then((response) => {
                    closeLoading();
                    if (response.data.success === 10) {
                        Swal.fire({
                            title: 'Extras guardados',
                            type: 'success',
                            allowOutsideClick: false,
                            confirmButtonColor: '#28a745',
                            confirmButtonText: 'Aceptar'
                        }).then((r) => {
                            if (r.value) $('#matriz tbody tr').remove();
                        });
                    } else if (response.data.success === 2) {
                        Swal.fire({
                            title: 'Cantidad no disponible',
                            html:
                                'Fila #<b>' + response.data.fila + '</b> — ' +
                                '<b>' + (response.data.nombre_material ?? 'Material') + '</b><br><br>' +
                                'Solicitado: <b>' + response.data.cantidad_pedida + '</b><br>' +
                                'Disponible: <b>' + response.data.disponible + '</b>',
                            icon: 'warning',
                            confirmButtonColor: '#d33',
                            confirmButtonText: 'Entendido'
                        });
                    } else {
                        toastr.error('Error al guardar');
                    }
                })
                .catch(() => { closeLoading(); toastr.error('Error al guardar'); });
        }

        // ── Utilidades ────────────────────────────────────────────
        function borrarFila(el) {
            $(el).closest('tr').remove();
            renumerarFilas();
        }

        function renumerarFilas() {
            $('#matriz tbody tr').each(function (i) {
                $(this).find('.num-fila').text(i + 1);
            });
        }

        function validateInput(event) {
            const key = event.key;
            if (['Backspace','ArrowLeft','ArrowRight','Delete','Tab'].includes(key)) return true;
            if (key === 'e' || key === 'E' || key === '-' || isNaN(Number(key))) return false;
            return true;
        }

        function validateCantidadSalida(input, maxCantidad) {
            input.value = input.value.replace(/[^0-9]/g, '');
            if (Number(input.value) > maxCantidad) input.value = maxCantidad;
        }
    </script>
@endsection
