{{-- resources/views/backend/reportes/porperiodos.blade.php --}}
@extends('adminlte::page')
@section('title', 'Reporte de Movimientos por Proyecto y Período')
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
            background: linear-gradient(135deg, #0b3d2e, #1f9e6f);
        }
        .reporte-header i { font-size: 22px; color: #fff; }
        .reporte-header h5 {
            color: #fff;
            font-size: 14px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .05em;
            margin: 0;
        }
        .reporte-body { padding: 22px 24px; background: #fff; }
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
            background: linear-gradient(135deg, #0b3d2e, #1f9e6f);
            color: #fff;
            box-shadow: 0 4px 14px rgba(31,158,111,.35);
        }
        .btn-pdf:hover { transform: translateY(-1px); filter: brightness(1.08); color: #fff; }
        .divider { border: none; border-top: 2px dashed #e8eef8; margin: 10px 0 20px 0; }

        /* Tabs de estado */
        .estado-tabs {
            display: flex;
            gap: 8px;
            margin-bottom: 4px;
        }
        .estado-tab {
            flex: 1;
            text-align: center;
            padding: 10px 12px;
            border-radius: 8px;
            border: 2px solid #e8eef8;
            background: #f8fafc;
            cursor: pointer;
            font-size: 12px;
            font-weight: 700;
            color: #6b7a99;
            transition: all .15s;
            user-select: none;
        }
        .estado-tab i { display: block; font-size: 18px; margin-bottom: 4px; }
        .estado-tab.active {
            border-color: #1f9e6f;
            background: #e6f7ef;
            color: #0b7a4f;
        }
        .estado-tab.active.cerrado {
            border-color: #d97a3a;
            background: #fdeee6;
            color: #b35417;
        }
        .modo-hint {
            margin-top: 8px;
            font-size: 11px;
            font-weight: 600;
            padding: 6px 10px;
            border-radius: 6px;
            display: block;
        }
        .modo-hint.activo  { background: #e6f7ef; color: #0b7a4f; }
        .modo-hint.cerrado { background: #fdeee6; color: #b35417; }

        /* Toggle switch existencia cero */
        .toggle-wrap {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 14px;
            border-radius: 8px;
            border: 2px solid #e8eef8;
            background: #f8fafc;
            margin-top: 4px;
        }
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 44px;
            height: 24px;
            flex-shrink: 0;
        }
        .toggle-switch input { opacity: 0; width: 0; height: 0; }
        .toggle-slider {
            position: absolute;
            cursor: pointer;
            inset: 0;
            background: #ccc;
            border-radius: 24px;
            transition: .2s;
        }
        .toggle-slider:before {
            content: '';
            position: absolute;
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background: #fff;
            border-radius: 50%;
            transition: .2s;
        }
        .toggle-switch input:checked + .toggle-slider { background: #1f9e6f; }
        .toggle-switch input:checked + .toggle-slider:before { transform: translateX(20px); }
        .toggle-desc {
            font-size: 12px;
            font-weight: 600;
            color: #4a5568;
            line-height: 1.4;
        }
        .toggle-desc small {
            display: block;
            font-weight: 400;
            color: #9aa5b1;
            font-size: 10px;
        }
    </style>

    {{-- Formulario oculto para POST --}}
    <form id="form-pdf"
          action="{{ URL::to('admin/reporte/proyectos/periodos/pdf') }}"
          method="POST"
          target="_blank">
        @csrf
        <input type="hidden" name="idproy"          id="h-idproy">
        <input type="hidden" name="estado"          id="h-estado">
        <input type="hidden" name="desde"           id="h-desde">
        <input type="hidden" name="hasta"           id="h-hasta">
        <input type="hidden" name="mostrar_cero"    id="h-mostrar-cero" value="0">
    </form>

    <div id="divcontenedor">
        <section class="content">
            <div class="container-fluid">
                <div class="row justify-content-center">
                    <div class="col-md-10">
                        <div class="reporte-card">
                            <div class="reporte-header">
                                <i class="fas fa-exchange-alt"></i>
                                <h5>Reporte de Movimientos por Proyecto y Período</h5>
                            </div>
                            <div class="reporte-body">

                                <hr class="divider">

                                {{-- ── Selector de estado del proyecto ── --}}
                                <div class="form-group">
                                    <label class="field-label">
                                        <i class="fas fa-toggle-on mr-1"></i>Estado del Proyecto
                                    </label>
                                    <div class="estado-tabs">
                                        <div class="estado-tab active" data-estado="activo" id="tab-activo">
                                            <i class="fas fa-folder-open"></i>
                                            Activo
                                        </div>
                                        <div class="estado-tab" data-estado="cerrado" id="tab-cerrado">
                                            <i class="fas fa-folder"></i>
                                            Cerrado
                                        </div>
                                    </div>
                                    <span id="modo-hint" class="modo-hint activo">
                                        <i class="fas fa-info-circle mr-1"></i>
                                        <strong>Reporte de Saldos de Materiales.</strong>
                                        Incluye todos los movimientos del proyecto.
                                    </span>
                                </div>

                                <div class="form-group">
                                    <label class="field-label">
                                        <i class="fas fa-project-diagram mr-1"></i>Proyecto
                                    </label>
                                    <select class="form-control" id="sel-proyecto">
                                        @foreach($proyectos as $p)
                                            <option value="{{ $p->id }}"
                                                    data-cerrado="{{ $p->transferido ? '1' : '0' }}">{{ $p->nombre }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="field-label">
                                                <i class="fas fa-calendar-alt mr-1"></i>Fecha Desde
                                            </label>
                                            <input type="date" class="form-control" id="inp-desde">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="field-label">
                                                <i class="fas fa-calendar-check mr-1"></i>Fecha Hasta
                                            </label>
                                            <input type="date" class="form-control" id="inp-hasta">
                                        </div>
                                    </div>
                                </div>

                                {{-- ── Toggle: mostrar filas con existencia 0 ── --}}
                                <div class="form-group">
                                    <label class="field-label">
                                        <i class="fas fa-filter mr-1"></i>Opciones de visualización
                                    </label>
                                    <div class="toggle-wrap">
                                        <label class="toggle-switch" style="margin:0">
                                            <input type="checkbox" id="chk-mostrar-cero">
                                            <span class="toggle-slider"></span>
                                        </label>
                                        <div class="toggle-desc">
                                            Incluir materiales con existencia final en 0
                                            <small>Por defecto solo se muestran materiales con saldo actual mayor a 0</small>
                                        </div>
                                    </div>
                                </div>

                                <button type="button" onclick="generarPDF()" class="btn-pdf">
                                    <img src="{{ asset('images/logopdf.png') }}" width="22px" height="22px">
                                    Generar PDF
                                </button>

                                <div class="card mt-4">
                                    <div class="card-header bg-primary">
                                        <h5 class="mb-0">Firmas del Reporte</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-5">
                                                <div class="form-group">
                                                    <label>1 - Encargado de bodega de proyecto:</label>
                                                    <input type="text"
                                                           id="p_nombre1"
                                                           class="form-control"
                                                           maxlength="200"
                                                           value="{{ $infoGeneral->p_nombre1 ?? '' }}"
                                                           placeholder="Ingrese nombre">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>2 - Jefe Inmediato:</label>
                                                    <input type="text"
                                                           id="p_nombre2"
                                                           class="form-control"
                                                           maxlength="200"
                                                           value="{{ $infoGeneral->p_nombre2 ?? '' }}"
                                                           placeholder="Ingrese nombre">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <button class="btn btn-primary"
                                                    onclick="actualizarFirmasReporte()">
                                                Guardar
                                            </button>
                                        </div>
                                    </div>
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
    <script src="{{ asset('js/select2.min.js') }}"></script>
    <script src="{{ asset('js/axios.min.js') }}"></script>
    <script src="{{ asset('js/sweetalert2.all.min.js') }}"></script>
    <script src="{{ asset('js/alertaPersonalizada.js') }}"></script>

    <script>
        var estadoActual = 'activo';

        function formatProyecto(option) {
            if (!option.id) return option.text;
            var esCerrado = $(option.element).data('cerrado');
            if (parseInt(esCerrado) === 1) {
                return $(`
                    <span>
                        ${option.text}
                        <span style="background:#dc3545;color:#fff;font-size:10px;font-weight:700;
                                     padding:2px 7px;border-radius:4px;margin-left:6px;">CERRADO</span>
                    </span>
                `);
            }
            return $('<span>' + option.text + '</span>');
        }

        $(document).ready(function () {
            document.getElementById("divcontenedor").style.display = "block";

            $('#sel-proyecto').select2({
                theme: "bootstrap-5",
                templateResult: formatProyecto,
                templateSelection: formatProyecto,
                escapeMarkup: function(markup) { return markup; },
                language: {
                    noResults: function () { return "Búsqueda no encontrada"; }
                }
            });

            $('.estado-tab').on('click', function () {
                cambiarEstado($(this).data('estado'));
            });
        });

        function cambiarEstado(estado) {
            estadoActual = estado;
            $('.estado-tab').removeClass('active cerrado');
            if (estado === 'cerrado') {
                $('#tab-cerrado').addClass('active cerrado');
                $('#modo-hint').removeClass('activo').addClass('cerrado')
                    .html('<i class="fas fa-info-circle mr-1"></i> ' +
                        '<strong>Reporte de Saldos de Materiales Sobrantes.</strong> ' +
                        'Incluye únicamente los movimientos de transferencia.');
            } else {
                $('#tab-activo').addClass('active');
                $('#modo-hint').removeClass('cerrado').addClass('activo')
                    .html('<i class="fas fa-info-circle mr-1"></i> ' +
                        '<strong>Reporte de Saldos de Materiales.</strong> ' +
                        'Incluye todos los movimientos del proyecto.');
            }
        }

        function generarPDF() {
            var idproy     = $('#sel-proyecto').val();
            var desde      = $('#inp-desde').val();
            var hasta      = $('#inp-hasta').val();
            var mostrarCero = $('#chk-mostrar-cero').is(':checked') ? '1' : '0';

            if (!idproy) { toastr.error('Seleccione un proyecto'); return; }
            if (!desde)  { toastr.error('Seleccione la fecha "Desde"'); return; }
            if (!hasta)  { toastr.error('Seleccione la fecha "Hasta"'); return; }
            if (desde > hasta) {
                toastr.error('La fecha "Desde" no puede ser mayor que "Hasta"');
                return;
            }

            $('#h-idproy').val(idproy);
            $('#h-estado').val(estadoActual);
            $('#h-desde').val(desde);
            $('#h-hasta').val(hasta);
            $('#h-mostrar-cero').val(mostrarCero);

            $('#form-pdf').submit();
        }

        function actualizarFirmasReporte() {
            openLoading();
            axios.post(urlAdmin + '/admin/firmas/proyectos/periodos/actualizar', {
                p_nombre1: document.getElementById('p_nombre1').value.trim(),
                p_nombre2: document.getElementById('p_nombre2').value.trim(),
            })
                .then((response) => {
                    closeLoading();
                    switch (response.data.success) {
                        case 1:  toastr.success('Firmas actualizadas correctamente'); break;
                        case 0:  toastr.error('No se encontró la información'); break;
                        case 99: toastr.error('Ocurrió un error al actualizar'); break;
                        default: toastr.error('Error al actualizar');
                    }
                })
                .catch(() => { closeLoading(); toastr.error('Error al actualizar'); });
        }
    </script>
@endsection
