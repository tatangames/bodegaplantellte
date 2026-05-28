@extends('adminlte::page')

@section('title', 'Reportes de Entradas y Salidas')

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

        .reporte-card {
            border: none; border-radius: 12px;
            box-shadow: 0 2px 18px rgba(0,0,0,.10);
            margin-bottom: 24px; overflow: hidden;
        }
        .reporte-header {
            padding: 14px 20px; display: flex;
            align-items: center; gap: 12px;
        }
        .reporte-header.entradas { background: linear-gradient(135deg, #1a6b2a, #28a745); }
        .reporte-header.salidas  { background: linear-gradient(135deg, #6b1a1a, #dc3545); }
        .reporte-header i  { font-size: 22px; color: #fff; }
        .reporte-header h5 {
            color: #fff; font-size: 14px; font-weight: 700;
            text-transform: uppercase; letter-spacing: .05em; margin: 0;
        }
        .reporte-body { padding: 22px 24px; background: #fff; }
        .field-label {
            font-size: 11px; font-weight: 700; color: #6b7a99;
            text-transform: uppercase; letter-spacing: .06em;
            margin-bottom: 6px; display: block;
        }
        .divider { border: none; border-top: 2px dashed #e8eef8; margin: 12px 0 18px 0; }
        .btn-pdf {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 8px 20px; border-radius: 8px; font-weight: 600;
            font-size: 13px; border: none; cursor: pointer;
            transition: all .2s; margin-top: 14px;
        }
        .btn-pdf.verde { background: linear-gradient(135deg, #1a6b2a, #28a745); color: #fff; box-shadow: 0 4px 14px rgba(40,167,69,.35); }
        .btn-pdf.rojo  { background: linear-gradient(135deg, #6b1a1a, #dc3545); color: #fff; box-shadow: 0 4px 14px rgba(220,53,69,.35); }
        .btn-pdf:hover { transform: translateY(-1px); filter: brightness(1.08); color: #fff; }

        .fecha-row { display: flex; gap: 14px; margin-bottom: 14px; }
        .fecha-box { flex: 1; }
        .fecha-box label {
            font-size: 11px; font-weight: 700; color: #6b7a99;
            text-transform: uppercase; margin-bottom: 4px; display: block;
        }
    </style>

    <div id="divcontenedor" style="display:none">
        <section class="content">
            <div class="container-fluid">
                <div class="row">

                    {{-- ══ ENTRADAS ══ --}}
                    <div class="col-md-6">
                        <div class="reporte-card">
                            <div class="reporte-header entradas">
                                <i class="fas fa-arrow-circle-down"></i>
                                <h5>Entrada de Materiales por Proyecto</h5>
                            </div>
                            <div class="reporte-body">
                                <p style="font-size:13px; color:#666; margin-bottom:14px;">
                                    Materiales que han ingresado a un proyecto en un rango de fechas.
                                </p>
                                <hr class="divider">

                                <div class="fecha-row">
                                    <div class="fecha-box">
                                        <label>Desde</label>
                                        <input type="date" class="form-control" id="entrada-desde">
                                    </div>
                                    <div class="fecha-box">
                                        <label>Hasta</label>
                                        <input type="date" class="form-control" id="entrada-hasta">
                                    </div>
                                </div>

                                <label class="field-label">
                                    <i class="fas fa-project-diagram mr-1"></i>Proyecto
                                </label>
                                <select class="form-control" id="select-proyecto-entrada">
                                    @foreach($proyectos as $dd)
                                        <option value="{{ $dd->id }}"
                                                data-cerrado="{{ $dd->transferido ? '1' : '0' }}">
                                            {{ $dd->nombre }}
                                        </option>
                                    @endforeach
                                </select>

                                <label class="field-label mt-3">Tipo de Reporte</label>
                                <select class="form-control" id="tipo-entrada" style="width:200px">
                                    <option value="1">Juntos</option>
                                    <option value="2">Separado</option>
                                </select>

                                <br>
                                <button type="button" onclick="generarPdfEntrada()" class="btn-pdf verde">
                                    <img src="{{ asset('images/logopdf.png') }}" width="22px" height="22px">
                                    Generar PDF
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- ══ SALIDAS ══ --}}
                    <div class="col-md-6">
                        <div class="reporte-card">
                            <div class="reporte-header salidas">
                                <i class="fas fa-arrow-circle-up"></i>
                                <h5>Salidas de Materiales por Proyecto</h5>
                            </div>
                            <div class="reporte-body">
                                <p style="font-size:13px; color:#666; margin-bottom:14px;">
                                    Materiales que han salido de un proyecto en un rango de fechas.
                                </p>
                                <p style="font-size:13px; color:#666; margin-bottom:14px; font-weight: bold">
                                    Marca el registro cuando fueron por Transferencia. Todas las salidas de los Materiales
                                </p>
                                <hr class="divider">

                                <div class="fecha-row">
                                    <div class="fecha-box">
                                        <label>Desde</label>
                                        <input type="date" class="form-control" id="salida-desde">
                                    </div>
                                    <div class="fecha-box">
                                        <label>Hasta</label>
                                        <input type="date" class="form-control" id="salida-hasta">
                                    </div>
                                </div>

                                <label class="field-label">
                                    <i class="fas fa-project-diagram mr-1"></i>Proyecto
                                </label>
                                <select class="form-control" id="select-proyecto-salida">
                                    @foreach($proyectos as $dd)
                                        <option value="{{ $dd->id }}"
                                                data-cerrado="{{ $dd->transferido ? '1' : '0' }}">
                                            {{ $dd->nombre }}
                                        </option>
                                    @endforeach
                                </select>

                                <label class="field-label mt-3">Tipo de Reporte</label>
                                <select class="form-control" id="tipo-salida" style="width:200px">
                                    <option value="1">Juntos</option>
                                    <option value="2">Separado</option>
                                </select>

                                <br>
                                <button type="button" onclick="generarPdfSalida()" class="btn-pdf rojo">
                                    <img src="{{ asset('images/logopdf.png') }}" width="22px" height="22px">
                                    Generar PDF
                                </button>
                            </div>
                        </div>
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
    <script src="{{ asset('js/select2.min.js') }}"></script>

    <script>

        // ── Formato con badge rojo si está cerrado ────────────────────────
        function formatProyecto(option) {
            if (!option.id) return option.text;
            var esCerrado = $(option.element).data('cerrado');
            if (esCerrado == '1') {
                return $('<span>' + option.text +
                    ' <span style="background:#dc3545; color:#fff; font-size:10px; ' +
                    'font-weight:700; padding:2px 7px; border-radius:4px; ' +
                    'vertical-align:middle;">CERRADO</span></span>');
            }
            return option.text;
        }

        $(document).ready(function () {
            document.getElementById("divcontenedor").style.display = "block";

            $('#select-proyecto-entrada').select2({
                theme:             "bootstrap-5",
                templateResult:    formatProyecto,
                templateSelection: formatProyecto,
                escapeMarkup:      function (m) { return m; },
                language: { noResults: function () { return "Búsqueda no encontrada"; } }
            });

            $('#select-proyecto-salida').select2({
                theme:             "bootstrap-5",
                templateResult:    formatProyecto,
                templateSelection: formatProyecto,
                escapeMarkup:      function (m) { return m; },
                language: { noResults: function () { return "Búsqueda no encontrada"; } }
            });
        });

        function generarPdfEntrada() {
            var idproy = $('#select-proyecto-entrada').val();
            var desde  = document.getElementById('entrada-desde').value || 'null';
            var hasta  = document.getElementById('entrada-hasta').value || 'null';
            var tipo   = document.getElementById('tipo-entrada').value;

            if (!idproy) { toastr.error('Proyecto es requerido'); return; }
            if (!tipo)   { toastr.error('Seleccionar Tipo');      return; }

            window.open("{{ URL::to('admin/reporte/quehaentrado/proyectos/pdf') }}/"
                + idproy + "/" + desde + "/" + hasta + "/" + tipo);
        }

        function generarPdfSalida() {
            var idproy = $('#select-proyecto-salida').val();
            var desde  = document.getElementById('salida-desde').value || 'null';
            var hasta  = document.getElementById('salida-hasta').value || 'null';
            var tipo   = document.getElementById('tipo-salida').value;

            if (!idproy) { toastr.error('Proyecto es requerido'); return; }
            if (!tipo)   { toastr.error('Seleccionar Tipo');      return; }

            window.open("{{ URL::to('admin/reporte/quehasalido/proyectos/pdf') }}/"
                + idproy + "/" + desde + "/" + hasta + "/" + tipo);
        }
    </script>
@endsection
