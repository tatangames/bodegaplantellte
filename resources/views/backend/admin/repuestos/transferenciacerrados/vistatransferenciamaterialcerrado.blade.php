@extends('adminlte::page')

@section('title', 'Retiro de Material — Proyectos Cerrados')

@section('content_header')
    <h1>Retiro de Material — Proyectos Cerrados</h1>
@stop

@section('plugins.Datatables', true)
@section('plugins.DatatablesPlugins', true)
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
        table { table-layout: fixed; }
        *:focus { outline: none; }
        .seccion-header {
            background: linear-gradient(135deg, #1a3a6b 0%, #2156af 100%);
            border-radius: 10px 10px 0 0; padding: 12px 18px;
        }
        .seccion-header h3 {
            color: #fff; font-size: 14px; font-weight: 700;
            letter-spacing: .05em; text-transform: uppercase; margin: 0;
        }
        .card-info {
            border: none; border-radius: 10px;
            box-shadow: 0 2px 18px rgba(33,86,175,.13); margin-bottom: 20px;
        }
        .field-label {
            font-size: 11px; font-weight: 700; color: #6b7a99;
            text-transform: uppercase; letter-spacing: .06em;
            margin-bottom: 5px; display: block;
        }
        .divider-azul { border: none; border-top: 2px solid #e8eef8; margin: 18px 0; }
        .destino-pills { display: flex; gap: 10px; flex-wrap: wrap; }
        .destino-pill {
            flex: 1; min-width: 140px; padding: 14px 10px;
            border: 2px solid #dee2e6; border-radius: 10px;
            text-align: center; cursor: pointer; transition: all .2s; background: #fff;
        }
        .destino-pill:hover { border-color: #2156af; background: #f0f4ff; }
        .destino-pill.activo-proyecto { border-color: #28a745; background: #f0fff4; }
        .destino-pill.activo-general  { border-color: #fd7e14; background: #fff8f0; }
        .destino-pill.activo-reserva  { border-color: #6f42c1; background: #f8f0ff; }
        .destino-pill i { font-size: 22px; display: block; margin-bottom: 6px; }
        .destino-pill.activo-proyecto i { color: #28a745; }
        .destino-pill.activo-general i  { color: #fd7e14; }
        .destino-pill.activo-reserva i  { color: #6f42c1; }
        .destino-pill span { font-size: 12px; font-weight: 700; color: #444; text-transform: uppercase; }
        #tablaMaterialesCerrado thead th {
            background: #495057; color: #fff; font-size: 11px;
            font-weight: 700; text-transform: uppercase; border: none !important; padding: 8px 10px;
        }
        #tablaMaterialesCerrado tbody td { vertical-align: middle; font-size: 13px; padding: 7px 10px; }
        #matriz thead tr th {
            background: #2156af; color: #fff; font-size: 11px;
            font-weight: 700; text-transform: uppercase; border: none !important; padding: 10px 12px;
        }
        #matriz tbody td { vertical-align: middle; font-size: 13px; padding: 8px 10px; }
        .btn-guardar-salida {
            background: linear-gradient(135deg, #28a745, #1e7e34);
            color: #fff; border: none; border-radius: 8px; padding: 10px 28px;
            font-weight: 400; font-size: 14px;
            box-shadow: 0 4px 14px rgba(40,167,69,.35); transition: all .2s;
        }
        .btn-guardar-salida:hover { transform: translateY(-1px); box-shadow: 0 6px 18px rgba(40,167,69,.45); color: #fff; }
        .btn-form-solicitud {
            background: linear-gradient(135deg, #7a4f1a, #fd7e14);
            color: #fff; border: none; border-radius: 8px; padding: 10px 20px;
            font-weight: 400; font-size: 14px;
            box-shadow: 0 4px 14px rgba(253,126,20,.35); transition: all .2s;
        }
        .btn-form-solicitud:hover { transform: translateY(-1px); box-shadow: 0 6px 18px rgba(253,126,20,.45); color: #fff; }
        .badge-reservado { background: #6f42c1; color: #fff; font-size: 10px; padding: 2px 6px; border-radius: 4px; }
        .tr-reservado { background: #faf5ff !important; }
    </style>

    <div id="divcontenedor" style="display:none">

        {{-- ══ PASO 1 ══ --}}
        <section class="content" style="margin-bottom:0">
            <div class="container-fluid">
                <div class="card card-info">
                    <div class="seccion-header">
                        <h3><i class="fas fa-lock mr-2"></i>Paso 1 — Seleccionar Proyecto Cerrado</h3>
                    </div>
                    <div class="card-body">
                        <div class="row align-items-end">
                            <div class="col-md-10">
                                <label class="field-label"><i class="fas fa-lock mr-1"></i>Proyecto Cerrado</label>
                                <select class="form-control" id="select-proyecto">
                                    <option value="0" selected disabled>Seleccionar Proyecto Cerrado…</option>
                                    @foreach($proyectosCerrados as $item)
                                        <option value="{{ $item->id }}">{{ $item->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="button" id="btnCargarMateriales"
                                        onclick="cargarMaterialesProyecto()"
                                        class="btn btn-primary btn-block" disabled>
                                    <i class="fas fa-search mr-1"></i> Buscar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- ══ PASO 2 ══ --}}
        <section class="content" id="seccionMateriales" style="margin-bottom:0; display:none">
            <div class="container-fluid">
                <div class="card card-info">
                    <div class="seccion-header" style="display:flex; justify-content:space-between; align-items:center">
                        <h3><i class="fas fa-boxes mr-2"></i>Paso 2 — Materiales Disponibles</h3>
                        <span id="lblProyectoCerrado"
                              style="background:rgba(255,255,255,.2); color:#fff; border-radius:20px;
                                     padding:2px 14px; font-size:12px; font-weight:700"></span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped mb-0"
                                   id="tablaMaterialesCerrado" style="width:100%">
                                <thead>
                                <tr>
                                    <th style="width:5%">#</th>
                                    <th style="width:33%">Material</th>
                                    <th style="width:10%">U/M</th>
                                    <th style="width:12%">Disponible</th>
                                    <th style="width:12%">Reservado</th>
                                    <th style="width:12%">Libre</th>
                                    <th style="width:16%">Acción</th>
                                </tr>
                                </thead>
                                <tbody id="tbodyMateriales"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- ══ PASO 3 ══ --}}
        <section class="content" id="seccionDestino" style="margin-bottom:0; display:none">
            <div class="container-fluid">
                <div class="card card-info">
                    <div class="seccion-header">
                        <h3><i class="fas fa-route mr-2"></i>Paso 3 — Tipo de Movimiento</h3>
                    </div>
                    <div class="card-body">
                        <div class="destino-pills mb-4">
                            <div class="destino-pill" id="pill-proyecto" onclick="seleccionarDestino('proyecto')">
                                <i class="fas fa-project-diagram"></i>
                                <span>Transferir a Proyecto</span>
                            </div>
                            <div class="destino-pill" id="pill-general" onclick="seleccionarDestino('general')">
                                <i class="fas fa-sign-out-alt"></i>
                                <span>Salida General</span>
                            </div>
                            <div class="destino-pill" id="pill-reserva" onclick="seleccionarDestino('reserva')">
                                <i class="fas fa-lock"></i>
                                <span>Reservar</span>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="field-label"><i class="fas fa-calendar-alt mr-1"></i>Fecha</label>
                                    <input type="date" class="form-control" id="fecha">
                                </div>
                            </div>
                            <div class="col-md-9">
                                <div class="form-group">
                                    <label class="field-label">
                                        <i class="fas fa-align-left mr-1"></i>Descripción / Motivo
                                        <small style="text-transform:none; font-weight:400">(Opcional)</small>
                                    </label>
                                    <input type="text" class="form-control" autocomplete="off"
                                           maxlength="800" id="descripcion" placeholder="Motivo…">
                                </div>
                            </div>
                        </div>
                        <div id="seccion-proyecto-destino" style="display:none">
                            <hr class="divider-azul">
                            <div class="row">
                                <div class="col-md-12">
                                    <label class="field-label">
                                        <i class="fas fa-project-diagram mr-1"></i>Proyecto Destino (Activo)
                                        <span style="color:red">*</span>
                                    </label>
                                    <select class="form-control" id="select-proyecto-destino">
                                        <option value="0" disabled selected>Seleccionar proyecto destino…</option>
                                        @foreach($proyectosActivos as $item)
                                            <option value="{{ $item->id }}">{{ $item->nombre }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- ══ PASO 4 ══ --}}
        <section class="content" id="seccionDetalle" style="display:none">
            <div class="container-fluid">
                <div class="card card-info">
                    <div class="seccion-header" style="display:flex; justify-content:space-between; align-items:center">
                        <h3><i class="fas fa-list mr-2"></i>Paso 4 — Detalle</h3>
                        <span id="contador-filas"
                              style="background:rgba(255,255,255,.2); color:#fff; border-radius:20px;
                                     padding:2px 12px; font-size:12px; font-weight:700">0 ítems</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped mb-0" id="matriz"
                                   style="table-layout:fixed; width:100%">
                                <thead>
                                <tr>
                                    <th style="width:5%">#</th>
                                    <th style="width:40%">Material</th>
                                    <th style="width:15%">Cantidad</th>
                                    <th style="width:20%">Tipo</th>
                                    <th style="width:10%">Opciones</th>
                                </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer d-flex justify-content-between align-items-center"
                         style="border-top:2px solid #e8eef8; background:#f8faff; border-radius:0 0 10px 10px">
                        <small class="text-muted" id="lblTipoMovimiento">—</small>
                        <div style="display:flex; gap:10px;">
                            <button type="button" class="btn-form-solicitud" id="btnAbrirForm"
                                    onclick="abrirModalFormDinamico()" style="display:none">
                                <i class="fas fa-file-alt mr-1"></i>
                                <span id="lblBtnForm">FORM</span>
                            </button>
                            <button type="button" class="btn-guardar-salida" onclick="preguntaGuardar()">
                                <i class="fas fa-save mr-1"></i> Guardar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- ══ MODAL: Cantidad ══ --}}
        <div class="modal fade" id="modalCantidad">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header" style="background:#1a3a6b">
                        <h4 class="modal-title" style="color:#fff">
                            <i class="fas fa-boxes mr-2"></i>Cantidad a Mover
                        </h4>
                        <button type="button" class="close" data-dismiss="modal" style="color:#fff">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="modal-id-entrada-detalle">
                        <input type="hidden" id="modal-max">
                        <div class="form-group">
                            <label class="field-label">Material</label>
                            <input type="text" disabled class="form-control" id="modal-nombre-material">
                        </div>
                        <div class="form-group">
                            <label class="field-label">
                                Disponible libre: <strong id="modal-disponible-libre"></strong>
                            </label>
                            <input type="number" class="form-control" id="modal-cantidad"
                                   min="1" placeholder="Cantidad a mover…"
                                   oninput="validateCantidadModal(this)">
                        </div>
                    </div>
                    <div class="modal-footer justify-content-between">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-success" onclick="agregarAlDetalle()">
                            <i class="fas fa-plus mr-1"></i> Agregar
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- ══ MODAL: GEAD-001-FORM (Reserva) ══ --}}
        <div class="modal fade" id="modalForm001" data-backdrop="static" data-keyboard="false">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header" style="background:linear-gradient(135deg,#3d1f6b,#6f42c1)">
                        <h4 class="modal-title" style="color:#fff">
                            <i class="fas fa-lock mr-2"></i>Formulario de Reserva — GEAD-001-FORM
                        </h4>
                        <button type="button" class="close" data-dismiss="modal" style="color:#fff">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="alert" style="font-size:12px; background:#f3eeff; border-left:4px solid #6f42c1;">
                            <i class="fas fa-info-circle mr-1" style="color:#6f42c1;"></i>
                            Formulario para Reserva de Materiales Sobrantes. La Unidad Solicitante es requerida.
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="field-label"><i class="fas fa-hashtag mr-1"></i>No. de Solicitud</label>
                                    <input type="text" class="form-control" id="form001-numero" placeholder="Ej: 001-2025">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="field-label"><i class="fas fa-lock mr-1"></i>Proyecto de Origen de los Materiales</label>
                                    <input type="text" class="form-control" id="form001-proyecto-origen" disabled>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="field-label">
                                <i class="fas fa-project-diagram mr-1"></i>Proyecto en Formulación
                            </label>
                            <input type="text" class="form-control" id="form001-proyecto-formul"
                                   placeholder="Nombre del proyecto al que se destinarán los materiales…">
                        </div>
                        <div class="form-group">
                            <label class="field-label"><i class="fas fa-align-left mr-1"></i>Justificación del Destino</label>
                            <textarea class="form-control" id="form001-justificacion" rows="2"
                                      placeholder="Justifique la reserva de los materiales…"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="field-label">
                                        <i class="fas fa-building mr-1"></i>Unidad Solicitante <span style="color:red">*</span>
                                    </label>
                                    <select class="form-control" id="form001-departamento">
                                        <option value="">— Seleccionar —</option>
                                        @foreach($departamentos as $d)
                                            <option value="{{ $d->id }}" data-nombre="{{ $d->nombre }}">{{ $d->nombre }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="field-label"><i class="fas fa-user mr-1"></i>Nombre del Solicitante</label>
                                    <input type="text" class="form-control" id="form001-nombre" placeholder="Nombre completo">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="field-label"><i class="fas fa-id-badge mr-1"></i>Cargo del Solicitante</label>
                                    <input type="text" class="form-control" id="form001-cargo" placeholder="Cargo o puesto">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="field-label"><i class="fas fa-sticky-note mr-1"></i>Observaciones</label>
                                    <textarea class="form-control" id="form001-observaciones" rows="2"
                                              placeholder="Observaciones adicionales (opcional)"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer justify-content-between">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            <i class="fas fa-times mr-1"></i>Cancelar
                        </button>
                        <button type="button" onclick="generarForm001PDF()"
                                style="background:linear-gradient(135deg,#3d1f6b,#6f42c1); border:none;
                                       color:#fff; font-weight:600; border-radius:6px; padding:8px 18px;">
                            <i class="fas fa-file-pdf mr-1"></i>Generar GEAD-001-FORM
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- ══ MODAL: GEAD-002-FORM (Transferir a Proyecto) ══ --}}
        <div class="modal fade" id="modalForm" data-backdrop="static" data-keyboard="false">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header" style="background:linear-gradient(135deg,#7a4f1a,#fd7e14)">
                        <h4 class="modal-title" style="color:#fff">
                            <i class="fas fa-file-alt mr-2"></i>Formulario de Solicitud — GEAD-002-FORM
                        </h4>
                        <button type="button" class="close" data-dismiss="modal" style="color:#fff">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-warning" style="font-size:12px;">
                            <i class="fas fa-info-circle mr-1"></i>
                            Complete los datos. La Unidad Solicitante es requerida.
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="field-label"><i class="fas fa-hashtag mr-1"></i>No. de Solicitud</label>
                                    <input type="text" class="form-control" id="form-numero" placeholder="Ej: 001-2025">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="field-label"><i class="fas fa-hashtag mr-1"></i>No. de Proyecto</label>
                                    <input type="text" class="form-control" id="form-noproyecto" placeholder="Ej: PROY-001-2025">
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="field-label">
                                <i class="fas fa-project-diagram mr-1"></i>Nombre del Proyecto
                                <small style="text-transform:none; font-weight:400; color:#888;">(puede editarlo)</small>
                            </label>
                            <input type="text" class="form-control" id="form-nombre-proyecto"
                                   placeholder="Nombre del proyecto destino o de origen…">
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="field-label"><i class="fas fa-file-alt mr-1"></i>Acuerdo de Aprobación</label>
                                    <input type="text" class="form-control" id="form-acuerdo" placeholder="Ej: Acuerdo No. 123-2025">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="field-label">
                                        <i class="fas fa-building mr-1"></i>Unidad Solicitante <span style="color:red">*</span>
                                    </label>
                                    <select class="form-control" id="form-departamento">
                                        <option value="">— Seleccionar —</option>
                                        @foreach($departamentos as $d)
                                            <option value="{{ $d->id }}" data-nombre="{{ $d->nombre }}">{{ $d->nombre }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="field-label"><i class="fas fa-user-tie mr-1"></i>Jefe o Encargado</label>
                                    <input type="text" class="form-control" id="form-jefe" placeholder="Nombre completo">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="field-label"><i class="fas fa-align-left mr-1"></i>Justificación del Destino</label>
                                    <textarea class="form-control" id="form-justificacion" rows="2"
                                              placeholder="Justifique el uso de los materiales…"></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="field-label"><i class="fas fa-sticky-note mr-1"></i>Observaciones</label>
                            <textarea class="form-control" id="form-observaciones" rows="2"
                                      placeholder="Observaciones adicionales (opcional)"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer justify-content-between">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            <i class="fas fa-times mr-1"></i>Cancelar
                        </button>
                        <button type="button" class="btn btn-warning" onclick="generarFormPDF()"
                                style="color:#fff; font-weight:600;">
                            <i class="fas fa-file-pdf mr-1"></i>Generar GEAD-002-FORM
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- ══ MODAL: GEAD-003-FORM (Salida General) ══ --}}
        <div class="modal fade" id="modalForm003" data-backdrop="static" data-keyboard="false">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header" style="background:linear-gradient(135deg,#1a6b3a,#28a745)">
                        <h4 class="modal-title" style="color:#fff">
                            <i class="fas fa-file-alt mr-2"></i>Formulario de Solicitud — GEAD-003-FORM
                        </h4>
                        <button type="button" class="close" data-dismiss="modal" style="color:#fff">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-success" style="font-size:12px;">
                            <i class="fas fa-info-circle mr-1"></i>
                            Formulario para Salida General — Mantenimiento de Instalaciones Municipales.
                            La Unidad Solicitante es requerida.
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="field-label"><i class="fas fa-hashtag mr-1"></i>No. de Solicitud</label>
                                    <input type="text" class="form-control" id="form003-numero" placeholder="Ej: 001-2025">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="field-label">
                                        <i class="fas fa-tag mr-1"></i>Tipo de Destino / Uso
                                        <small style="text-transform:none; font-weight:400; color:#888;">(puede editarlo)</small>
                                    </label>
                                    <input type="text" class="form-control" id="form003-tipodestino"
                                           placeholder="Ej: Mantenimiento edificio principal…">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="field-label">
                                        <i class="fas fa-building mr-1"></i>Unidad Solicitante <span style="color:red">*</span>
                                    </label>
                                    <select class="form-control" id="form003-departamento">
                                        <option value="">— Seleccionar —</option>
                                        @foreach($departamentos as $d)
                                            <option value="{{ $d->id }}" data-nombre="{{ $d->nombre }}">{{ $d->nombre }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="field-label"><i class="fas fa-user mr-1"></i>Nombre del Solicitante</label>
                                    <input type="text" class="form-control" id="form003-nombre" placeholder="Nombre completo">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="field-label"><i class="fas fa-id-badge mr-1"></i>Cargo del Solicitante</label>
                                    <input type="text" class="form-control" id="form003-cargo" placeholder="Cargo o puesto">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="field-label"><i class="fas fa-align-left mr-1"></i>Justificación del Destino</label>
                                    <textarea class="form-control" id="form003-justificacion" rows="2"
                                              placeholder="Justifique el uso de los materiales…"></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="field-label"><i class="fas fa-sticky-note mr-1"></i>Observaciones</label>
                            <textarea class="form-control" id="form003-observaciones" rows="2"
                                      placeholder="Observaciones adicionales (opcional)"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer justify-content-between">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            <i class="fas fa-times mr-1"></i>Cancelar
                        </button>
                        <button type="button" class="btn btn-success" onclick="generarForm003PDF()"
                                style="font-weight:600;">
                            <i class="fas fa-file-pdf mr-1"></i>Generar GEAD-003-FORM
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- ══ MODAL: Datos del Acta GEAD-002-ACTA ══ --}}
        <div class="modal fade" id="modalActa" data-backdrop="static" data-keyboard="false">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header" style="background:linear-gradient(135deg,#1a3a6b,#2156af)">
                        <h4 class="modal-title" style="color:#fff">
                            <i class="fas fa-file-alt mr-2"></i>Datos del Acta — GEAD-002-ACTA
                        </h4>
                        <button type="button" class="close" data-dismiss="modal" style="color:#fff">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info" style="font-size:12px;">
                            <i class="fas fa-info-circle mr-1"></i>
                            Complete los datos del acta. La Unidad Solicitante es requerida.
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="field-label"><i class="fas fa-hashtag mr-1"></i>No. de Acta de Recepción</label>
                                    <input type="text" class="form-control" id="acta-numero" placeholder="Ej: 001-2025">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="field-label"><i class="fas fa-file-invoice mr-1"></i>Referencia de la Solicitud</label>
                                    <input type="text" class="form-control" id="acta-referencia" placeholder="Ej: GEAD-002-FORM No. 001">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="field-label">
                                        <i class="fas fa-building mr-1"></i>Unidad Solicitante <span style="color:red">*</span>
                                    </label>
                                    <select class="form-control" id="acta-departamento">
                                        <option value="">— Seleccionar —</option>
                                        @foreach($departamentos as $d)
                                            <option value="{{ $d->id }}" data-nombre="{{ $d->nombre }}">{{ $d->nombre }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="field-label"><i class="fas fa-user mr-1"></i>Nombre del Solicitante</label>
                                    <input type="text" class="form-control" id="acta-nombre-solicitante" placeholder="Nombre completo">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="field-label"><i class="fas fa-id-badge mr-1"></i>Cargo del Solicitante</label>
                                    <input type="text" class="form-control" id="acta-cargo-solicitante" placeholder="Cargo o puesto">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="field-label">
                                        <i class="fas fa-tag mr-1"></i>Tipo de Destino / Uso
                                        <small style="text-transform:none; font-weight:400; color:#888;">(puede editarlo)</small>
                                    </label>
                                    <input type="text" class="form-control" id="acta-tipo-destino"
                                           placeholder="Ej: Mantenimiento edificio principal…">
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="field-label"><i class="fas fa-sticky-note mr-1"></i>Observaciones</label>
                            <textarea class="form-control" id="acta-observaciones" rows="2"
                                      placeholder="Observaciones adicionales (opcional)"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer justify-content-between">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            <i class="fas fa-times mr-1"></i>Cancelar
                        </button>
                        <div>
                            <button type="button" class="btn btn-info mr-2" onclick="guardar('pdf')">
                                <i class="fas fa-file-pdf mr-1"></i>Generar PDF
                            </button>
                            <button type="button" class="btn btn-success" onclick="guardar('guardar')">
                                <i class="fas fa-save mr-1"></i>Guardar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>{{-- fin #divcontenedor --}}
@stop

@section('js')
    <script src="{{ asset('js/jquery.dataTables.js') }}"></script>
    <script src="{{ asset('js/dataTables.bootstrap4.js') }}"></script>
    <script src="{{ asset('js/toastr.min.js') }}"></script>
    <script src="{{ asset('js/axios.min.js') }}"></script>
    <script src="{{ asset('js/sweetalert2.all.min.js') }}"></script>
    <script src="{{ asset('js/alertaPersonalizada.js') }}"></script>
    <script src="{{ asset('js/select2.min.js') }}"></script>

    <script>
        var tipoDestino = null;

        $(document).ready(function () {
            document.getElementById("divcontenedor").style.display = "block";

            var hoy = new Date();
            document.getElementById('fecha').value = hoy.toJSON().slice(0, 10);

            $('#select-proyecto').select2({
                theme: "bootstrap-5",
                language: { noResults: function () { return "Búsqueda no encontrada"; } }
            });
            $('#select-proyecto-destino').select2({
                theme: "bootstrap-5",
                language: { noResults: function () { return "Búsqueda no encontrada"; } }
            });
            $('#acta-departamento').select2({
                theme: "bootstrap-5", dropdownParent: $('#modalActa'),
                language: { noResults: function () { return "No encontrado"; } }
            });
            $('#form-departamento').select2({
                theme: "bootstrap-5", dropdownParent: $('#modalForm'),
                language: { noResults: function () { return "No encontrado"; } }
            });
            $('#form003-departamento').select2({
                theme: "bootstrap-5", dropdownParent: $('#modalForm003'),
                language: { noResults: function () { return "No encontrado"; } }
            });
            $('#form001-departamento').select2({
                theme: "bootstrap-5", dropdownParent: $('#modalForm001'),
                language: { noResults: function () { return "No encontrado"; } }
            });

            $('#select-proyecto').on('change', function () {
                var val = $(this).val();
                $('#btnCargarMateriales').prop('disabled', !val || val === '0');
                ocultarPasos();
                $('#select-proyecto').select2('close');
            });
        });

        function ocultarPasos() {
            $('#seccionMateriales, #seccionDestino, #seccionDetalle').hide();
            $('#tbodyMateriales').empty();
            $('#matriz tbody tr').remove();
            actualizarContador();
            tipoDestino = null;
            limpiarPills();
        }

        function cargarMaterialesProyecto() {
            var idProyecto = $('#select-proyecto').val();
            var nombreProy = $('#select-proyecto option:selected').text();

            if (!idProyecto || idProyecto === '0') {
                toastr.error('Seleccione un proyecto cerrado'); return;
            }

            openLoading();
            axios.post(urlAdmin + '/admin/transferencia/materiales/cerrado', { id_proyecto: idProyecto })
                .then((response) => {
                    closeLoading();
                    if (response.data.success !== 1) { toastr.error('Error al cargar materiales'); return; }

                    var lista = response.data.materiales;
                    if (!lista || lista.length === 0) {
                        toastr.warning('Este proyecto no tiene material disponible');
                        ocultarPasos(); return;
                    }

                    $('#lblProyectoCerrado').text(nombreProy);
                    $('#tbodyMateriales').empty();

                    $.each(lista, function (i, m) {
                        var badgeReservado = m.reservado > 0
                            ? ' <span class="badge-reservado">🔒 ' + m.reservado + ' reservado</span>' : '';
                        var trClass = m.reservado > 0 ? 'tr-reservado' : '';
                        var btnSeleccionar = m.libre > 0
                            ? "<button class='btn btn-primary btn-xs' " +
                            "data-id='" + m.id_entrada_detalle + "' " +
                            "data-nombre='" + m.nombre.replace(/'/g, "&#39;").replace(/\n/g, ' ').replace(/\r/g, '') + "' " +
                            "data-libre='" + m.libre + "' onclick=\"seleccionarMaterial(this)\">" +
                            "<i class='fas fa-plus'></i> Seleccionar</button>"
                            : "<span class='badge badge-secondary'>Sin stock libre</span>";

                        var celdaMaterial = m.nombre + badgeReservado +
                            "<br><small style='color:#888; font-size:10px'>" + (m.medida ?? '—') + "</small>";

                        $('#tbodyMateriales').append(
                            "<tr class='" + trClass + "'>" +
                            "<td>" + (i + 1) + "</td><td>" + celdaMaterial + "</td>" +
                            "<td>" + (m.medida ?? '—') + "</td><td>" + m.disponible + "</td>" +
                            "<td>" + m.reservado + "</td><td><strong>" + m.libre + "</strong></td>" +
                            "<td>" + btnSeleccionar + "</td></tr>"
                        );
                    });

                    $('#seccionMateriales, #seccionDestino, #seccionDetalle').show();
                    $('#matriz tbody tr').remove();
                    actualizarContador();
                    tipoDestino = null;
                    limpiarPills();
                })
                .catch(() => { closeLoading(); toastr.error('Error al cargar'); });
        }

        function seleccionarMaterial(btn) {
            abrirModalCantidad($(btn).data('id'), $(btn).data('nombre'), parseInt($(btn).data('libre')));
        }

        function seleccionarDestino(tipo) {
            tipoDestino = tipo;
            limpiarPills();

            if (tipo === 'proyecto') {
                $('#pill-proyecto').addClass('activo-proyecto');
                $('#seccion-proyecto-destino').show();
                $('#lblTipoMovimiento').html('<i class="fas fa-project-diagram mr-1" style="color:#28a745"></i> Transferir a Proyecto Activo');
                $('#btnAbrirForm').show();
                $('#lblBtnForm').text('GEAD-002-FORM');
            } else if (tipo === 'general') {
                $('#pill-general').addClass('activo-general');
                $('#seccion-proyecto-destino').hide();
                $('#lblTipoMovimiento').html('<i class="fas fa-sign-out-alt mr-1" style="color:#fd7e14"></i> Salida General');
                $('#btnAbrirForm').show();
                $('#lblBtnForm').text('GEAD-003-FORM');
            } else if (tipo === 'reserva') {
                $('#pill-reserva').addClass('activo-reserva');
                $('#seccion-proyecto-destino').hide();
                $('#lblTipoMovimiento').html('<i class="fas fa-lock mr-1" style="color:#6f42c1"></i> Reserva de Material');
                $('#btnAbrirForm').show();
                $('#lblBtnForm').text('GEAD-001-FORM');
            }

            $('#matriz tbody tr').remove();
            actualizarContador();
        }

        function limpiarPills() {
            $('#pill-proyecto').removeClass('activo-proyecto');
            $('#pill-general').removeClass('activo-general');
            $('#pill-reserva').removeClass('activo-reserva');
            $('#seccion-proyecto-destino').hide();
            $('#btnAbrirForm').hide();
        }

        function abrirModalFormDinamico() {
            if ($('#matriz > tbody > tr').length <= 0) {
                toastr.error('Agregue al menos un material al detalle'); return;
            }

            if (tipoDestino === 'proyecto') {
                var nombreProy = $('#select-proyecto option:selected').text();
                var nombreDest = $('#select-proyecto-destino option:selected').text();
                if (nombreDest && nombreDest !== 'Seleccionar proyecto destino…') nombreProy = nombreDest;
                $('#form-nombre-proyecto').val(nombreProy);
                $('#modalForm').modal('show');

            } else if (tipoDestino === 'general') {
                $('#form003-tipodestino').val('');
                $('#modalForm003').modal('show');

            } else if (tipoDestino === 'reserva') {
                $('#form001-proyecto-origen').val($('#select-proyecto option:selected').text());
                $('#modalForm001').modal('show');
            }
        }

        function abrirModalCantidad(idEntradaDetalle, nombre, libre) {
            if (!tipoDestino) { toastr.warning('Primero seleccione el tipo de movimiento en el Paso 3'); return; }
            if (libre <= 0)   { toastr.info('Sin stock libre disponible'); return; }
            $('#modal-id-entrada-detalle').val(idEntradaDetalle);
            $('#modal-nombre-material').val(nombre);
            $('#modal-max').val(libre);
            $('#modal-disponible-libre').text(libre);
            $('#modal-cantidad').val('');
            $('#modalCantidad').modal('show');
        }

        function validateCantidadModal(input) {
            var max = parseInt($('#modal-max').val());
            input.value = input.value.replace(/[^0-9]/g, '');
            if (parseInt(input.value) > max) input.value = max;
            if (parseInt(input.value) < 0)   input.value = '';
        }

        function agregarAlDetalle() {
            var idEntradaDetalle = $('#modal-id-entrada-detalle').val();
            var nombre           = $('#modal-nombre-material').val();
            var cantidad         = parseInt($('#modal-cantidad').val());
            var max              = parseInt($('#modal-max').val());

            if (!cantidad || cantidad <= 0) { toastr.error('Ingrese una cantidad válida'); return; }
            if (cantidad > max)             { toastr.error('Supera el stock libre');        return; }

            var labelDestino = '';
            if (tipoDestino === 'proyecto') labelDestino = '<span class="badge badge-success">Proyecto</span>';
            if (tipoDestino === 'general')  labelDestino = '<span class="badge badge-warning">General</span>';
            if (tipoDestino === 'reserva')  labelDestino = '<span class="badge" style="background:#6f42c1; color:#fff">Reserva</span>';

            var nFilas = $('#matriz > tbody > tr').length + 1;
            $('#matriz tbody').append(
                "<tr>" +
                "<td><span style='display:block; text-align:center'>" + nFilas + "</span></td>" +
                "<td><input name='idmaterialArray[]' type='hidden' data-idmaterialArray='" + idEntradaDetalle + "'>" +
                "<input disabled value='" + nombre.replace(/'/g, "&#39;") + "' class='form-control form-control-sm' type='text'></td>" +
                "<td><input name='salidaArray[]' disabled data-cantidadSalida='" + cantidad + "' value='" + cantidad + "' class='form-control form-control-sm' type='text'></td>" +
                "<td>" + labelDestino + "</td>" +
                "<td><button type='button' class='btn btn-danger btn-block btn-sm' onclick='borrarFila(this)'>Borrar</button></td>" +
                "</tr>"
            );
            actualizarContador();
            $('#modalCantidad').modal('hide');
            toastr.success('Agregado al detalle');
        }

        // ── GEAD-001-FORM PDF (Reserva) ───────────────────────────────────
        function generarForm001PDF() {
            var formDepto = $('#form001-departamento').val();
            if (!formDepto || formDepto === '') { toastr.error('La Unidad Solicitante es requerida'); return; }

            var materiales = [];
            $('#matriz > tbody > tr').each(function () {
                materiales.push({
                    nombre:             $(this).find("input[type='text']").val(),
                    cantidad:           $(this).find("input[name='salidaArray[]']").attr('data-cantidadSalida'),
                    id_entrada_detalle: $(this).find("input[name='idmaterialArray[]']").attr('data-idmaterialArray')
                });
            });

            var form = $('<form>', { method: 'POST', action: "{{ URL::to('admin/reporte/form001/reserva/preview') }}", target: '_blank' });
            form.append($('<input>', { type: 'hidden', name: '_token',          value: "{{ csrf_token() }}" }));
            form.append($('<input>', { type: 'hidden', name: 'idproy',          value: $('#select-proyecto').val() }));
            form.append($('<input>', { type: 'hidden', name: 'nombre_origen',   value: $('#select-proyecto option:selected').text() }));
            form.append($('<input>', { type: 'hidden', name: 'fecha',           value: document.getElementById('fecha').value }));
            form.append($('<input>', { type: 'hidden', name: 'numero',          value: $('#form001-numero').val().trim() }));
            form.append($('<input>', { type: 'hidden', name: 'proyecto_formul', value: $('#form001-proyecto-formul').val().trim() }));
            form.append($('<input>', { type: 'hidden', name: 'justificacion',   value: $('#form001-justificacion').val().trim() }));
            form.append($('<input>', { type: 'hidden', name: 'depto',           value: $('#form001-departamento option:selected').text() }));
            form.append($('<input>', { type: 'hidden', name: 'nombre',          value: $('#form001-nombre').val().trim() }));
            form.append($('<input>', { type: 'hidden', name: 'cargo',           value: $('#form001-cargo').val().trim() }));
            form.append($('<input>', { type: 'hidden', name: 'observaciones',   value: $('#form001-observaciones').val().trim() }));
            form.append($('<input>', { type: 'hidden', name: 'materiales',      value: JSON.stringify(materiales) }));
            $('body').append(form); form.submit(); form.remove();
        }

        // ── GEAD-002-FORM PDF ─────────────────────────────────────────────
        function generarFormPDF() {
            var formDepto = $('#form-departamento').val();
            if (!formDepto || formDepto === '') { toastr.error('La Unidad Solicitante es requerida'); return; }

            var materiales = [];
            $('#matriz > tbody > tr').each(function () {
                materiales.push({
                    nombre:             $(this).find("input[type='text']").val(),
                    cantidad:           $(this).find("input[name='salidaArray[]']").attr('data-cantidadSalida'),
                    id_entrada_detalle: $(this).find("input[name='idmaterialArray[]']").attr('data-idmaterialArray')
                });
            });

            var form = $('<form>', { method: 'POST', action: "{{ URL::to('admin/reporte/form/solicitud/preview') }}", target: '_blank' });
            form.append($('<input>', { type: 'hidden', name: '_token',          value: "{{ csrf_token() }}" }));
            form.append($('<input>', { type: 'hidden', name: 'idproy',          value: $('#select-proyecto').val() }));
            form.append($('<input>', { type: 'hidden', name: 'nombre_proyecto', value: $('#form-nombre-proyecto').val().trim() || $('#select-proyecto option:selected').text() }));
            form.append($('<input>', { type: 'hidden', name: 'nombre_origen',   value: $('#select-proyecto option:selected').text() }));
            form.append($('<input>', { type: 'hidden', name: 'proyecto_destino',value: $('#select-proyecto-destino option:selected').text() || '' }));
            form.append($('<input>', { type: 'hidden', name: 'fecha',           value: document.getElementById('fecha').value }));
            form.append($('<input>', { type: 'hidden', name: 'numero',          value: $('#form-numero').val().trim() }));
            form.append($('<input>', { type: 'hidden', name: 'noproyecto',      value: $('#form-noproyecto').val().trim() }));
            form.append($('<input>', { type: 'hidden', name: 'acuerdo',         value: $('#form-acuerdo').val().trim() }));
            form.append($('<input>', { type: 'hidden', name: 'depto',           value: $('#form-departamento option:selected').text() }));
            form.append($('<input>', { type: 'hidden', name: 'jefe',            value: $('#form-jefe').val().trim() }));
            form.append($('<input>', { type: 'hidden', name: 'justificacion',   value: $('#form-justificacion').val().trim() }));
            form.append($('<input>', { type: 'hidden', name: 'observaciones',   value: $('#form-observaciones').val().trim() }));
            form.append($('<input>', { type: 'hidden', name: 'tipo_destino',    value: tipoDestino }));
            form.append($('<input>', { type: 'hidden', name: 'materiales',      value: JSON.stringify(materiales) }));
            $('body').append(form); form.submit(); form.remove();
        }

        // ── GEAD-003-FORM PDF ─────────────────────────────────────────────
        function generarForm003PDF() {
            var formDepto = $('#form003-departamento').val();
            if (!formDepto || formDepto === '') { toastr.error('La Unidad Solicitante es requerida'); return; }

            var materiales = [];
            $('#matriz > tbody > tr').each(function () {
                materiales.push({
                    nombre:             $(this).find("input[type='text']").val(),
                    cantidad:           $(this).find("input[name='salidaArray[]']").attr('data-cantidadSalida'),
                    id_entrada_detalle: $(this).find("input[name='idmaterialArray[]']").attr('data-idmaterialArray')
                });
            });

            var form = $('<form>', { method: 'POST', action: "{{ URL::to('admin/reporte/form003/solicitud/preview') }}", target: '_blank' });
            form.append($('<input>', { type: 'hidden', name: '_token',        value: "{{ csrf_token() }}" }));
            form.append($('<input>', { type: 'hidden', name: 'idproy',        value: $('#select-proyecto').val() }));
            form.append($('<input>', { type: 'hidden', name: 'nombre_origen', value: $('#select-proyecto option:selected').text() }));
            form.append($('<input>', { type: 'hidden', name: 'fecha',         value: document.getElementById('fecha').value }));
            form.append($('<input>', { type: 'hidden', name: 'numero',        value: $('#form003-numero').val().trim() }));
            form.append($('<input>', { type: 'hidden', name: 'tipodestino',   value: $('#form003-tipodestino').val().trim() }));
            form.append($('<input>', { type: 'hidden', name: 'depto',         value: $('#form003-departamento option:selected').text() }));
            form.append($('<input>', { type: 'hidden', name: 'nombre',        value: $('#form003-nombre').val().trim() }));
            form.append($('<input>', { type: 'hidden', name: 'cargo',         value: $('#form003-cargo').val().trim() }));
            form.append($('<input>', { type: 'hidden', name: 'justificacion', value: $('#form003-justificacion').val().trim() }));
            form.append($('<input>', { type: 'hidden', name: 'observaciones', value: $('#form003-observaciones').val().trim() }));
            form.append($('<input>', { type: 'hidden', name: 'materiales',    value: JSON.stringify(materiales) }));
            $('body').append(form); form.submit(); form.remove();
        }

        function preguntaGuardar() {
            if (!tipoDestino) { toastr.warning('Seleccione el tipo de movimiento en el Paso 3'); return; }
            if ($('#matriz > tbody > tr').length <= 0) { toastr.error('Agregue al menos un material al detalle'); return; }

            var labelsTipo = {
                proyecto: 'Transferencia a Proyecto de Inversión Pública',
                general:  'Salida General — Mantenimiento de Instalaciones',
                reserva:  'Reserva de Material',
            };
            var tipoLabel = labelsTipo[tipoDestino] || tipoDestino;

            if (tipoDestino === 'proyecto') {
                var nombreProyDest = $('#select-proyecto-destino option:selected').text();
                if (nombreProyDest && nombreProyDest !== 'Seleccionar proyecto destino…') tipoLabel = nombreProyDest;
            }

            $('#acta-tipo-destino').val(tipoLabel);

            if (tipoDestino === 'reserva') {
                Swal.fire({
                    title: '¿Confirmar reserva?',
                    text:  '¿Reservar estos materiales? Quedarán bloqueados hasta su despacho.',
                    icon: 'question', showCancelButton: true,
                    confirmButtonColor: '#6f42c1', cancelButtonColor: '#d33',
                    cancelButtonText: 'Cancelar', confirmButtonText: 'Sí, reservar'
                }).then((result) => { if (result.isConfirmed) ejecutarGuardar('guardar'); });
            } else {
                $('#modalActa').modal('show');
            }
        }

        function guardar(accion) {
            var fecha           = document.getElementById('fecha').value;
            var proyectoCerrado = $('#select-proyecto').val();
            var proyectoDestino = $('#select-proyecto-destino').val();
            var actaIdDepto     = $('#acta-departamento').val();

            if (!fecha)                                      { toastr.error('Fecha es requerida'); return; }
            if (!proyectoCerrado || proyectoCerrado === '0') { toastr.error('Seleccione proyecto cerrado'); return; }
            if (tipoDestino === 'proyecto' && (!proyectoDestino || proyectoDestino === '0')) {
                toastr.error('Seleccione el proyecto destino'); return;
            }
            if (!actaIdDepto || actaIdDepto === '') { toastr.error('La Unidad Solicitante es requerida'); return; }

            if (accion === 'pdf') { abrirPDFSinGuardar(); return; }
            ejecutarGuardar(accion);
        }

        function abrirPDFSinGuardar() {
            var materiales = [];
            $('#matriz > tbody > tr').each(function () {
                materiales.push({
                    nombre:             $(this).find("input[type='text']").val(),
                    cantidad:           $(this).find("input[name='salidaArray[]']").attr('data-cantidadSalida'),
                    id_entrada_detalle: $(this).find("input[name='idmaterialArray[]']").attr('data-idmaterialArray')
                });
            });

            var form = $('<form>', { method: 'POST', action: "{{ URL::to('admin/reporte/acta/preview') }}", target: '_blank' });
            form.append($('<input>', { type: 'hidden', name: '_token',        value: "{{ csrf_token() }}" }));
            form.append($('<input>', { type: 'hidden', name: 'idproy',        value: $('#select-proyecto').val() }));
            form.append($('<input>', { type: 'hidden', name: 'fecha',         value: document.getElementById('fecha').value }));
            form.append($('<input>', { type: 'hidden', name: 'numero',        value: $('#acta-numero').val().trim() }));
            form.append($('<input>', { type: 'hidden', name: 'referencia',    value: $('#acta-referencia').val().trim() }));
            form.append($('<input>', { type: 'hidden', name: 'depto',         value: $('#acta-departamento option:selected').text() }));
            form.append($('<input>', { type: 'hidden', name: 'nombre',        value: $('#acta-nombre-solicitante').val().trim() }));
            form.append($('<input>', { type: 'hidden', name: 'cargo',         value: $('#acta-cargo-solicitante').val().trim() }));
            form.append($('<input>', { type: 'hidden', name: 'observaciones', value: $('#acta-observaciones').val().trim() }));
            form.append($('<input>', { type: 'hidden', name: 'tipodestino',   value: $('#acta-tipo-destino').val().trim() }));
            form.append($('<input>', { type: 'hidden', name: 'materiales',    value: JSON.stringify(materiales) }));
            $('body').append(form); form.submit(); form.remove();
        }

        function ejecutarGuardar(accion) {
            var idEntradaDetalle = $("input[name='idmaterialArray[]']").map(function () { return $(this).attr("data-idmaterialArray"); }).get();
            var salidaCantidad   = $("input[name='salidaArray[]']").map(function () { return $(this).attr("data-cantidadSalida"); }).get();

            var contenedorArray = [];
            for (var p = 0; p < salidaCantidad.length; p++) {
                contenedorArray.push({ infoIdEntradaDeta: idEntradaDetalle[p], infoCantidad: salidaCantidad[p] });
            }

            $('#modalActa').modal('hide');
            openLoading();

            var formData = new FormData();
            formData.append('fecha',                document.getElementById('fecha').value);
            formData.append('proyecto_cerrado',     $('#select-proyecto').val());
            formData.append('proyecto_destino',     $('#select-proyecto-destino').val() || '');
            formData.append('descripcion',          document.getElementById('descripcion').value);
            formData.append('tipo_destino',         tipoDestino);
            formData.append('contenedorArray',      JSON.stringify(contenedorArray));
            formData.append('acta_numero',          $('#acta-numero').val().trim());
            formData.append('acta_referencia',      $('#acta-referencia').val().trim());
            formData.append('acta_id_departamento', $('#acta-departamento').val() || '');
            formData.append('acta_nombre_solic',    $('#acta-nombre-solicitante').val().trim());
            formData.append('acta_cargo_solic',     $('#acta-cargo-solicitante').val().trim());
            formData.append('acta_observaciones',   $('#acta-observaciones').val().trim());
            formData.append('acta_tipo_destino',    $('#acta-tipo-destino').val().trim());

            axios.post(urlAdmin + '/admin/transferencia/material/xproyecto', formData)
                .then((response) => {
                    closeLoading();
                    if (response.data.success === 1) {
                        toastr.error('Sin ítems en el contenedor');
                    } else if (response.data.success === 3) {
                        Swal.fire({
                            title: 'Cantidad no disponible',
                            html: '<b>' + response.data.nombre_material + '</b><br><br>' +
                                'Solicitado: <b>' + response.data.cantidad_pedida + '</b><br>' +
                                'Disponible libre: <b>' + response.data.disponible + '</b>',
                            icon: 'warning', confirmButtonColor: '#d33', confirmButtonText: 'Entendido'
                        });
                    } else if (response.data.success === 10) {
                        var titulos = {
                            proyecto: 'Transferencia Registrada',
                            general:  'Salida General Registrada',
                            reserva:  'Materiales Reservados'
                        };
                        Swal.fire({
                            title: titulos[tipoDestino] || 'Guardado',
                            icon: 'success',
                            allowOutsideClick: false,
                            confirmButtonColor: '#28a745',
                            confirmButtonText: 'Aceptar'
                        }).then((r) => {
                            if (r.isConfirmed) location.reload();
                        });
                    } else {
                        toastr.error('Error al guardar');
                    }
                })
                .catch(() => { toastr.error('Error al guardar'); closeLoading(); });
        }

        function borrarFila(elemento) {
            elemento.closest('tr').remove();
            setearFila();
            actualizarContador();
        }

        function setearFila() {
            var table = document.getElementById('matriz'); var conteo = 0;
            for (var r = 1, n = table.rows.length; r < n; r++) {
                conteo++;
                table.rows[r].cells[0].children[0].innerHTML = conteo;
            }
        }

        function actualizarContador() {
            var n = $('#matriz > tbody > tr').length;
            $('#contador-filas').text(n + (n === 1 ? ' ítem' : ' ítems'));
        }
    </script>
@endsection
