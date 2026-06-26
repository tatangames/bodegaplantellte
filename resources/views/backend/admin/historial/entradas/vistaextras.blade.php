@extends('adminlte::page')

@section('title', 'Agregar Extras — Entrada #{{ $entrada->id }}')

@section('content_header')
    <h1>Agregar Extras a Entrada</h1>
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

@section('css')
    <style>
        table { table-layout: fixed; }
        .cursor-pointer:hover { cursor: pointer; color: #401fd2; font-weight: bold; }
        *:focus { outline: none; }
        #fila-total td {
            font-weight: bold;
            background-color: #f4f6f9;
            font-size: 1.05rem;
        }
    </style>
@stop

@section('content')
    <div id="divcontenedor">

        {{-- Info de la entrada existente --}}
        <section class="content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-10">
                        <div class="card card-gray-dark">
                            <div class="card-header">
                                <h3 class="card-title">Entrada #{{ $entrada->id }}</h3>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3">
                                        <label class="text-muted">Fecha</label>
                                        <p><strong>{{ date('d/m/Y', strtotime($entrada->fecha)) }}</strong></p>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="text-muted">Factura</label>
                                        <p><strong>{{ $entrada->factura ?? '—' }}</strong></p>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="text-muted">Proveedor</label>
                                        <p><strong>{{ $entrada->proveedor->nombre ?? '—' }}</strong></p>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="text-muted">Descripción</label>
                                        <p><strong>{{ $entrada->descripcion ?? '—' }}</strong></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- Botón agregar material --}}
        <section class="content-header">
            <div class="row">
                <button type="button" style="margin-left:15px" onclick="abrirModal()" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i> Agregar Material
                </button>
                <a href="{{ route('admin.historial.entradas.index') }}"
                   style="margin-left:10px"
                   class="btn btn-secondary btn-sm">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
            </div>
        </section>

        {{-- Modal buscar material --}}
        <div class="modal fade" id="modalRepuesto">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Agregar Material</h4>
                        <button type="button" class="close" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form id="formulario-repuesto">
                            <div class="card-body">

                                <div class="form-group">
                                    <label>Material <span style="color:red">*</span></label>
                                    <table class="table" id="matriz-busqueda">
                                        <tbody>
                                        <tr>
                                            <td>
                                                <input id="repuesto" data-info="0" autocomplete="off"
                                                       class="form-control" style="width:100%"
                                                       onkeyup="buscarMaterial(this)" maxlength="400" type="text">
                                                <div class="droplista" style="position:absolute;z-index:9;width:75% !important;"></div>
                                            </td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Cantidad <span style="color:red">*</span></label>
                                            <input type="number" id="cantidad" min="1" max="1000000"
                                                   class="form-control" autocomplete="off" placeholder="0">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Precio (4 decimales) <span style="color:red">*</span></label>
                                            <input type="number" id="precio-producto" min="0" max="9000000"
                                                   step="0.0001" class="form-control" autocomplete="off" placeholder="0.00">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Marca / Detalle <small class="text-muted">(Opcional)</small></label>
                                            <input type="text" id="codigo" maxlength="100"
                                                   class="form-control" autocomplete="off">
                                        </div>
                                    </div>
                                </div>

                                {{-- Preview subtotal --}}
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="text-success font-weight-bold">
                                                <i class="fas fa-calculator"></i> Subtotal:
                                            </label>
                                            <input type="text" id="preview-subtotal"
                                                   class="form-control font-weight-bold text-success"
                                                   readonly placeholder="$0.0000"
                                                   style="background:#f4f9f4; font-size:1.1rem;">
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </form>
                    </div>
                    <div class="modal-footer justify-content-between">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
                        <button type="button" class="btn btn-primary" onclick="agregarFila()">Agregar</button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tabla de detalle a agregar --}}
        <section class="content-header">
            <div class="row mb-2">
                <div class="col-sm-6"><h2>Materiales a Agregar</h2></div>
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
                            <table class="table table-bordered table-hover mb-0" id="matriz">
                                <thead>
                                <tr>
                                    <th style="width:4%">#</th>
                                    <th style="width:30%">Material</th>
                                    <th style="width:10%">Cantidad</th>
                                    <th style="width:14%">Marca / Detalle</th>
                                    <th style="width:13%">Precio Unit.</th>
                                    <th style="width:13%">Subtotal</th>
                                    <th style="width:16%">Opciones</th>
                                </tr>
                                </thead>
                                <tbody></tbody>
                                <tfoot>
                                <tr id="fila-total">
                                    <td colspan="5" class="text-right">TOTAL GENERAL:</td>
                                    <td id="total-general" class="text-success">$0.0000</td>
                                    <td></td>
                                </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <div class="modal-footer justify-content-between" style="margin-top:25px;">
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
        const ID_ENTRADA = {{ $entrada->id }};
        window.seguroBuscador      = true;
        window.txtContenedorGlobal = null;

        $(document).ready(function () {
            $(document).click(function () { $(".droplista").hide(); });

            // Preview subtotal en tiempo real
            $('#cantidad, #precio-producto').on('input', function () {
                calcularPreviewSubtotal();
            });
        });

        document.getElementById('cantidad').addEventListener('keypress', function (e) {
            if (e.key < '0' || e.key > '9') e.preventDefault();
        });

        // ── Preview subtotal en el modal ──────────────────────────────
        function calcularPreviewSubtotal() {
            var cantidad = parseFloat(document.getElementById('cantidad').value) || 0;
            var precio   = parseFloat(document.getElementById('precio-producto').value) || 0;
            var subtotal = cantidad * precio;
            document.getElementById('preview-subtotal').value = '$' + subtotal.toFixed(4);
        }

        // ── Recalcular total general de la tabla ──────────────────────
        function recalcularTotal() {
            var total = 0;
            $("input[name='arraySubtotal[]']").each(function () {
                total += parseFloat($(this).attr('data-subtotal')) || 0;
            });
            document.getElementById('total-general').textContent = '$' + total.toFixed(4);
        }

        // ── Modal ─────────────────────────────────────────────────────
        function abrirModal() {
            document.getElementById('formulario-repuesto').reset();
            document.getElementById('preview-subtotal').value = '';
            $('#repuesto').attr('data-info', '0');
            $('#modalRepuesto').modal({ backdrop: 'static', keyboard: false });
        }

        // ── Agregar fila ──────────────────────────────────────────────
        function agregarFila() {
            var repuesto  = document.querySelector('#repuesto');
            var nomRepuesto  = repuesto.value.trim();
            var idMaterial   = repuesto.dataset.info;
            var cantidad     = document.getElementById('cantidad').value;
            var codigo       = document.getElementById('codigo').value;
            var precio       = document.getElementById('precio-producto').value;

            var reglaEntero  = /^[0-9]\d*$/;
            var reglaDecimal = /^([0-9]+\.?[0-9]{0,4})$/;

            if (idMaterial == 0 || idMaterial === '') {
                toastr.error('Seleccione un material de la lista'); return;
            }
            if (cantidad === '' || !cantidad.match(reglaEntero) || parseInt(cantidad) <= 0) {
                toastr.error('Cantidad debe ser un entero mayor a 0'); return;
            }
            if (parseInt(cantidad) > 1000000) {
                toastr.error('Cantidad máximo 1 millón'); return;
            }
            if (precio === '' || !precio.match(reglaDecimal) || parseFloat(precio) < 0) {
                toastr.error('Precio debe ser un número decimal no negativo'); return;
            }
            if (parseFloat(precio) > 9000000) {
                toastr.error('Precio máximo 9 millones'); return;
            }

            var subtotal = (parseFloat(cantidad) * parseFloat(precio)).toFixed(4);
            var nFilas   = $('#matriz tbody tr').length + 1;

            var fila = `
                <tr>
                    <td><span class="num-fila">${nFilas}</span></td>
                    <td>
                        <input name="descripcionArray[]" type="hidden"
                               data-info="${idMaterial}" value="${nomRepuesto}">
                        ${nomRepuesto}
                    </td>
                    <td>
                        <input name="cantidadArray[]" type="hidden" value="${cantidad}">
                        ${cantidad}
                    </td>
                    <td>
                        <input name="codigoArray[]" type="hidden" value="${codigo}">
                        ${codigo}
                    </td>
                    <td>
                        <input name="arrayPrecio[]" type="hidden" value="${precio}">
                        $${parseFloat(precio).toFixed(4)}
                    </td>
                    <td>
                        <input name="arraySubtotal[]" type="hidden"
                               data-subtotal="${subtotal}" value="${subtotal}">
                        <span class="font-weight-bold text-success">$${subtotal}</span>
                    </td>
                    <td>
                        <button type="button" class="btn btn-danger btn-sm btn-block"
                                onclick="borrarFila(this)">
                            <i class="fas fa-trash"></i> Borrar
                        </button>
                    </td>
                </tr>`;

            $('#matriz tbody').append(fila);
            recalcularTotal();
            toastr.success('Material agregado');

            document.getElementById('formulario-repuesto').reset();
            document.getElementById('preview-subtotal').value = '';
            $('#repuesto').attr('data-info', '0');
        }

        // ── Borrar fila ───────────────────────────────────────────────
        function borrarFila(btn) {
            $(btn).closest('tr').remove();
            renumerarFilas();
            recalcularTotal();
        }

        function renumerarFilas() {
            $('#matriz tbody tr').each(function (i) {
                $(this).find('.num-fila').text(i + 1);
            });
        }

        // ── Buscador material ─────────────────────────────────────────
        function buscarMaterial(e) {
            if (seguroBuscador) {
                seguroBuscador = false;
                var row = $(e).closest('tr');
                txtContenedorGlobal = e;
                let texto = e.value;
                if (texto === '') $(e).attr('data-info', 0);

                axios.post(urlAdmin + '/admin/buscar/material', { query: texto })
                    .then((response) => {
                        seguroBuscador = true;
                        $(row).find('.droplista').fadeIn().html(response.data);
                    })
                    .catch(() => { seguroBuscador = true; });
            }
        }

        function modificarValor(edrop) {
            let texto = $(edrop).text();
            $(txtContenedorGlobal).val(texto);
            $(txtContenedorGlobal).attr('data-info', edrop.id);
        }

        // ── Guardar ───────────────────────────────────────────────────
        function preguntaGuardar() {
            var nFilas = $('#matriz tbody tr').length;
            if (nFilas === 0) { toastr.error('Agrega al menos un material'); return; }

            Swal.fire({
                title: '¿Guardar materiales extras?',
                text: 'Se agregarán a la entrada #' + ID_ENTRADA,
                type: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#d33',
                cancelButtonText: 'Cancelar',
                confirmButtonText: 'Sí, guardar'
            }).then((result) => {
                if (result.value) { guardarExtras(); }
            });
        }

        function guardarExtras() {
            var descripcionAtributo = $("input[name='descripcionArray[]']").map(function () { return $(this).attr('data-info'); }).get();
            var cantidad            = $("input[name='cantidadArray[]']").map(function () { return $(this).val(); }).get();
            var codigo              = $("input[name='codigoArray[]']").map(function () { return $(this).val(); }).get();
            var arrayPrecio         = $("input[name='arrayPrecio[]']").map(function () { return $(this).val(); }).get();

            const contenedorArray = [];
            for (var i = 0; i < cantidad.length; i++) {
                contenedorArray.push({
                    idMaterial:   descripcionAtributo[i],
                    infoCantidad: cantidad[i],
                    infoCodigo:   codigo[i],
                    infoPrecio:   arrayPrecio[i],
                });
            }

            openLoading();
            const formData = new FormData();
            formData.append('id_entrada',      ID_ENTRADA);
            formData.append('contenedorArray', JSON.stringify(contenedorArray));

            axios.post(urlAdmin + '/admin/historial/entradas/extras/guardar', formData)
                .then((response) => {
                    closeLoading();
                    if (response.data.success === 1) {
                        toastr.success('Materiales agregados correctamente');
                        $('#matriz tbody tr').remove();
                        recalcularTotal();
                    } else {
                        toastr.error('Error al guardar');
                    }
                })
                .catch(() => { closeLoading(); toastr.error('Error al guardar'); });
        }
    </script>
@endsection
