@extends('adminlte::page')

@section('title', 'Reportes de Entradas y Salidas')

@section('plugins.Sweetalert2', true)
@include('backend.urlglobal')

@section('content_top_nav_right')
    <link href="{{ asset('css/toastr.min.css') }}" type="text/css" rel="stylesheet"/>
    <link href="{{ asset('css/select2.min.css') }}" type="text/css" rel="stylesheet">
    <link href="{{ asset('css/select2-bootstrap-5-theme.min.css') }}" type="text/css" rel="stylesheet">
    <link href="{{ asset('css/estiloToggle.css') }}" type="text/css" rel="stylesheet" />
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
        *:focus { outline: none; }
        .reporte-card {
            border: none; border-radius: 12px;
            box-shadow: 0 2px 18px rgba(0,0,0,.10);
            margin-bottom: 24px; overflow: hidden;
        }
        .reporte-header { padding: 14px 20px; display: flex; align-items: center; gap: 12px; }
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
        .tipo-badge {
            display: inline-block; font-size: 10px; font-weight: 700;
            padding: 2px 8px; border-radius: 4px; margin-left: 6px;
            vertical-align: middle;
        }
        .tipo-badge.juntos   { background:#d4edda; color:#155724; }
        .tipo-badge.separado { background:#cce5ff; color:#004085; }
    </style>

    <section class="content">
        <div class="container-fluid">
            <div class="row">

                {{-- ══ ENTRADAS ══ --}}
                <div class="col-md-6">
                    <div class="reporte-card">
                        <div class="reporte-header entradas">
                            <i class="fas fa-arrow-circle-down"></i>
                            <h5>Reporte de Entradas de Materiales</h5>
                        </div>
                        <div class="reporte-body">
                            <p style="font-size:13px; color:#666; margin-bottom:14px;">
                                Materiales ingresados en el rango de fechas seleccionado.
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

                            <label class="field-label mt-2">Tipo de Reporte</label>
                            <select class="form-control" id="tipo-entrada" style="width:100%">
                                <option value="1">Juntos — materiales iguales del mismo precio se suman</option>
                                <option value="2">Separado — cada entrada por separado</option>
                            </select>

                            <button type="button" onclick="generarPdfEntrada()" class="btn-pdf verde">
                                <i class="fas fa-file-pdf"></i> Generar PDF
                            </button>
                        </div>
                    </div>
                </div>

                {{-- ══ SALIDAS ══ --}}
                <div class="col-md-6">
                    <div class="reporte-card">
                        <div class="reporte-header salidas">
                            <i class="fas fa-arrow-circle-up"></i>
                            <h5>Reporte de Salidas de Materiales</h5>
                        </div>
                        <div class="reporte-body">
                            <p style="font-size:13px; color:#666; margin-bottom:14px;">
                                Materiales entregados en el rango de fechas seleccionado.
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

                            <label class="field-label mt-2">Tipo de Reporte</label>
                            <select class="form-control" id="tipo-salida" style="width:100%">
                                <option value="1">Juntos — materiales iguales del mismo precio se suman</option>
                                <option value="2">Separado — cada salida por separado</option>
                            </select>

                            <button type="button" onclick="generarPdfSalida()" class="btn-pdf rojo">
                                <i class="fas fa-file-pdf"></i> Generar PDF
                            </button>
                        </div>
                    </div>
                </div>



                {{-- ══ INVENTARIO ACTUAL ══ --}}
                <div class="col-md-12">
                    <div class="reporte-card">
                        <div class="reporte-header" style="background: linear-gradient(135deg, #1a4a6b, #1a73e8);">
                            <i class="fas fa-boxes"></i>
                            <h5>Inventario Actual de Materiales</h5>
                        </div>
                        <div class="reporte-body">
                            <p style="font-size:13px; color:#666; margin-bottom:14px;">
                                Existencias actuales (entradas menos salidas). Solo muestra materiales con cantidad mayor a cero.
                            </p>
                            <hr class="divider">

                            <div class="row">
                                <div class="col-md-6">
                                    <label class="field-label">Material</label>
                                    <select class="form-control" id="inv-material" style="width:100%">
                                        <option value="0">— Todos los materiales —</option>
                                        @foreach($materiales as $mat)
                                            <option value="{{ $mat->id }}">{{ $mat->nombre }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6 d-flex align-items-end">
                                    <button type="button" onclick="generarPdfInventario()" class="btn-pdf"
                                            style="background: linear-gradient(135deg, #1a4a6b, #1a73e8); color:#fff;
                               box-shadow: 0 4px 14px rgba(26,115,232,.35); margin-top:0;">
                                        <i class="fas fa-file-pdf"></i> Generar PDF
                                    </button>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>



            </div>
        </div>
    </section>
@stop

@section('js')
    <script src="{{ asset('js/toastr.min.js') }}" type="text/javascript"></script>
    <script src="{{ asset('js/axios.min.js') }}" type="text/javascript"></script>
    <script src="{{ asset('js/sweetalert2.all.min.js') }}"></script>
    <script src="{{ asset('js/alertaPersonalizada.js') }}"></script>
    <script src="{{ asset('js/jquery.simpleaccordion.js') }}"></script>
    <script src="{{ asset('js/select2.min.js') }}" type="text/javascript"></script>
    <script>
        function generarPdfEntrada() {
            var desde = document.getElementById('entrada-desde').value || 'null';
            var hasta = document.getElementById('entrada-hasta').value || 'null';
            var tipo  = document.getElementById('tipo-entrada').value;
            window.open("{{ url('admin/reporte/quehaentrado/pdf') }}/" + desde + "/" + hasta + "/" + tipo, '_blank');
        }

        function generarPdfSalida() {
            var desde = document.getElementById('salida-desde').value || 'null';
            var hasta = document.getElementById('salida-hasta').value || 'null';
            var tipo  = document.getElementById('tipo-salida').value;
            window.open("{{ url('admin/reporte/quehasalido/pdf') }}/" + desde + "/" + hasta + "/" + tipo, '_blank');
        }

        function generarPdfInventario() {
            var idMaterial = document.getElementById('inv-material').value;
            window.open("{{ url('admin/reporte/inventario/pdf') }}/" + idMaterial, '_blank');
        }

        $('#inv-material').select2({
            theme: "bootstrap-5",
            "language": {
                "noResults": function(){
                    return "Búsqueda no encontrada";
                }
            },
        });
    </script>
@endsection
