@extends('adminlte::page')

@section('title', 'Reportes de Proyectos')

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
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 18px rgba(0,0,0,.10);
            margin-bottom: 24px;
            overflow: hidden;
        }
        .reporte-header {
            padding: 14px 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .reporte-header.activo {
            background: linear-gradient(135deg, #1a3a6b, #2156af);
        }
        .reporte-header.completado {
            background: linear-gradient(135deg, #3d1f6b, #6f42c1);
        }
        .reporte-header.destino {
            background: linear-gradient(135deg, #1a5c3a, #28a745);
        }
        .reporte-header i {
            font-size: 22px;
            color: #fff;
        }
        .reporte-header h5 {
            color: #fff;
            font-size: 14px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .05em;
            margin: 0;
        }
        .reporte-body {
            padding: 22px 24px;
            background: #fff;
        }
        .field-label {
            font-size: 11px;
            font-weight: 700;
            color: #6b7a99;
            text-transform: uppercase;
            letter-spacing: .06em;
            margin-bottom: 6px;
            display: block;
        }
        .btn-pdf {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 20px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 13px;
            border: none;
            cursor: pointer;
            transition: all .2s;
            margin-top: 14px;
        }
        .btn-pdf.azul {
            background: linear-gradient(135deg, #1a3a6b, #2156af);
            color: #fff;
            box-shadow: 0 4px 14px rgba(33,86,175,.35);
        }
        .btn-pdf.morado {
            background: linear-gradient(135deg, #3d1f6b, #6f42c1);
            color: #fff;
            box-shadow: 0 4px 14px rgba(111,66,193,.35);
        }
        .btn-pdf.verde {
            background: linear-gradient(135deg, #1a5c3a, #28a745);
            color: #fff;
            box-shadow: 0 4px 14px rgba(40,167,69,.35);
        }
        .btn-pdf:hover { transform: translateY(-1px); filter: brightness(1.08); color: #fff; }
        .divider {
            border: none;
            border-top: 2px dashed #e8eef8;
            margin: 10px 0 20px 0;
        }
    </style>

    <div id="divcontenedor" style="display:none">
        <section class="content">
            <div class="container-fluid">
                <div class="row">

                    {{-- ══ REPORTE 1: Inventario Actual de Proyecto Activo ══ --}}
                    <div class="col-md-6">
                        <div class="reporte-card">
                            <div class="reporte-header activo">
                                <i class="fas fa-boxes"></i>
                                <h5>Inventario Actual de Proyecto</h5>
                            </div>
                            <div class="reporte-body">
                                <p style="font-size:13px; color:#666; margin-bottom:14px;">
                                    Muestra el stock disponible actual de un proyecto activo,
                                    incluyendo entradas, salidas y saldo.
                                </p>
                                <hr class="divider">
                                <label class="field-label">
                                    <i class="fas fa-project-diagram mr-1"></i>Proyecto
                                </label>
                                <select class="form-control" id="select-proyecto-activo">
                                    @foreach($proyectos as $dd)
                                        <option value="{{ $dd->id }}">{{ $dd->nombre }}</option>
                                    @endforeach
                                </select>
                                <br>
                                <button type="button" onclick="generarPdfActivo()" class="btn-pdf azul">
                                    <img src="{{ asset('images/logopdf.png') }}" width="22px" height="22px">
                                    Generar PDF
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- ══ REPORTE 2: Sobrantes de Proyecto Completado ══ --}}
                    <div class="col-md-6">
                        <div class="reporte-card">
                            <div class="reporte-header completado">
                                <i class="fas fa-flag-checkered"></i>
                                <h5>Sobrantes de Proyecto Completado</h5>
                                <h5>REPORTE DE SALDOS DE MATERIALES SOBRANTES</h5>
                            </div>
                            <div class="reporte-body">
                                <p style="font-size:13px; color:#666; margin-bottom:14px;">
                                    Muestra el inventario sobrante registrado al momento del cierre
                                    del proyecto. Los movimientos posteriores no afectan este reporte.
                                </p>
                                <hr class="divider">
                                <label class="field-label">
                                    <i class="fas fa-lock mr-1"></i>Proyecto Cerrado
                                </label>
                                <select class="form-control" id="select-proyecto-completado">
                                    @foreach($transferido as $dd)
                                        <option value="{{ $dd->id }}">{{ $dd->nombre }}</option>
                                    @endforeach
                                </select>
                                <br>
                                <button type="button" onclick="generarPdfCompletado()" class="btn-pdf morado">
                                    <img src="{{ asset('images/logopdf.png') }}" width="22px" height="22px">
                                    Generar PDF
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- ══ REPORTE 3: Destino de Sobrantes ══ --}}
                    <div class="col-md-6">
                        <div class="reporte-card">
                            <div class="reporte-header destino">
                                <i class="fas fa-share-alt"></i>
                                <h5>Destino de Sobrantes por Proyecto</h5>
                            </div>
                            <div class="reporte-body">
                                <p style="font-size:13px; color:#666; margin-bottom:14px;">
                                    Muestra qué pasó con los materiales sobrantes de un proyecto cerrado
                                    según el tipo de destino seleccionado.
                                </p>
                                <hr class="divider">
                                <label class="field-label">
                                    <i class="fas fa-lock mr-1"></i>Proyecto Cerrado
                                </label>
                                <select class="form-control" id="select-proyecto-destino">
                                    @foreach($transferido as $dd)
                                        <option value="{{ $dd->id }}">{{ $dd->nombre }}</option>
                                    @endforeach
                                </select>

                                <div class="mt-3" style="display:flex; gap:10px; flex-wrap:wrap;">
                                    <button type="button" onclick="generarPdfDestino('proyecto')" class="btn-pdf verde">
                                        <img src="{{ asset('images/logopdf.png') }}" width="22px" height="22px">
                                        A Otro Proyecto
                                    </button>
                                    <button type="button" onclick="generarPdfDestino('general')" class="btn-pdf"
                                            style="background:linear-gradient(135deg,#7a4f1a,#fd7e14);
                               color:#fff; box-shadow:0 4px 14px rgba(253,126,20,.35);">
                                        <img src="{{ asset('images/logopdf.png') }}" width="22px" height="22px">
                                        Salida General
                                    </button>
                                </div>
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
        $(document).ready(function () {
            document.getElementById("divcontenedor").style.display = "block";

            $('#select-proyecto-activo').select2({
                theme: "bootstrap-5",
                language: { noResults: function () { return "Búsqueda no encontrada"; } }
            });

            $('#select-proyecto-completado').select2({
                theme: "bootstrap-5",
                language: { noResults: function () { return "Búsqueda no encontrada"; } }
            });

            $('#select-proyecto-destino').select2({
                theme: "bootstrap-5",
                language: { noResults: function () { return "Búsqueda no encontrada"; } }
            });
        });

        function generarPdfActivo() {
            var idproy = $('#select-proyecto-activo').val();
            if (!idproy) { toastr.error('Proyecto es requerido'); return; }
            window.open("{{ URL::to('admin/reporte/quetengopor/proyectos/pdf') }}/" + idproy);
        }

        function generarPdfCompletado() {
            var idtrans = $('#select-proyecto-completado').val();
            if (!idtrans) { toastr.error('Proyecto es requerido'); return; }
            window.open("{{ URL::to('admin/reporte/inventario/sobranteterminado/proy') }}/" + idtrans);
        }

        function generarPdfDestino(tipo) {
            var idtrans = $('#select-proyecto-destino').val();
            if (!idtrans) { toastr.error('Proyecto es requerido'); return; }
            window.open("{{ URL::to('admin/reporte/inventario/destino/sobrantes') }}/" + idtrans + "/" + tipo);
        }
    </script>
@endsection
