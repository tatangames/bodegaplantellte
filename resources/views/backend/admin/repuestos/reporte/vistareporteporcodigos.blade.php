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
            transition: all .2s;
        }
        .btn-pdf.verde { background: linear-gradient(135deg, #1a6b2a, #28a745); color: #fff; box-shadow: 0 4px 14px rgba(40,167,69,.35); }
        .btn-pdf.rojo  { background: linear-gradient(135deg, #6b1a1a, #dc3545); color: #fff; box-shadow: 0 4px 14px rgba(220,53,69,.35); }
        .btn-pdf:hover { transform: translateY(-1px); filter: brightness(1.08); color: #fff; }
    </style>

    <div id="divcontenedor" style="display:none">
        <section class="content">
            <div class="container-fluid">
                <div class="row">

                    {{-- ══ ENTRADAS ══ --}}
                    <div class="col-md-10">
                        <div class="reporte-card">
                            <div class="reporte-header entradas">
                                <i class="fas fa-arrow-circle-down"></i>
                                <h5>Reporte Mensual - Proyecto</h5>
                            </div>
                            <div class="reporte-body">
                                <p style="font-size:13px; color:#666; margin-bottom:14px;">
                                    Materiales que han ingresado a un proyecto en un rango de fechas.
                                </p>
                                <hr class="divider">

                                {{-- Fila fechas --}}
                                <div class="row mb-3">
                                    <div class="col-auto">
                                        <label class="field-label">Desde</label>
                                        <input type="date" class="form-control form-control-sm" id="entrada-desde" style="width:145px;">
                                    </div>
                                    <div class="col-auto">
                                        <label class="field-label">Hasta</label>
                                        <input type="date" class="form-control form-control-sm" id="entrada-hasta" style="width:145px;">
                                    </div>
                                </div>

                                {{-- Fila proyecto --}}
                                <div class="row mb-2">
                                    <div class="col">
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
                                    </div>
                                </div>

                                <button type="button" onclick="generarPdf()" class="btn-pdf verde">
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

        function formatProyecto(option) {
            if (!option.id) return option.text;
            var esCerrado = $(option.element).data('cerrado');
            if (esCerrado == '1') {
                return $('<span>' + option.text +
                    ' <span style="background:#dc3545; color:#fff; font-size:10px;' +
                    ' font-weight:700; padding:2px 7px; border-radius:4px;' +
                    ' vertical-align:middle;">CERRADO</span></span>');
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
        });

        function generarPdf() {
            var idproy = $('#select-proyecto-entrada').val();
            var desde  = document.getElementById('entrada-desde').value;
            var hasta  = document.getElementById('entrada-hasta').value;

            if (!idproy) { toastr.error('Proyecto es requerido'); return; }
            if (!desde)  { toastr.error('Fecha Desde es requerida'); return; }
            if (!hasta)  { toastr.error('Fecha Hasta es requerida'); return; }
            if (hasta < desde) { toastr.error('Fecha Hasta no puede ser menor a Fecha Desde'); return; }

            window.open("{{ URL::to('admin/reporte/proyectos/codigos/pdf') }}/"
                + idproy + "/" + desde + "/" + hasta);
        }

    </script>
@endsection
