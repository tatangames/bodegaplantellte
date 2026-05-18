<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Sistema\LoginController;
use App\Http\Controllers\Sistema\ControlController;
use App\Http\Controllers\Sistema\RolesController;
use App\Http\Controllers\Sistema\PerfilController;
use App\Http\Controllers\Sistema\PermisoController;
use App\Http\Controllers\Sistema\ConfiguracionController;
use App\Http\Controllers\Sistema\RepuestosController;
use App\Http\Controllers\Sistema\TipoProyectoController;
use App\Http\Controllers\Sistema\SalidasController;
use App\Http\Controllers\Sistema\HistorialController;
use App\Http\Controllers\Sistema\ReportesController;
use App\Http\Controllers\Sistema\ReservasController;


Route::get('/', [LoginController::class,'vistaLoginForm'])->name('login.admin');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('admin.logout');

Route::middleware('auth:admin')->group(function () {

    // --- ROLES ---
    Route::get('/admin/roles/index', [RolesController::class,'index'])->name('admin.roles.index');
    Route::get('/admin/roles/tabla', [RolesController::class,'tablaRoles']);
    Route::get('/admin/roles/lista/permisos/{id}', [RolesController::class,'vistaPermisos']);
    Route::get('/admin/roles/permisos/tabla/{id}', [RolesController::class,'tablaRolesPermisos']);
    Route::post('/admin/roles/permiso/borrar', [RolesController::class, 'borrarPermiso']);
    Route::post('/admin/roles/permiso/agregar', [RolesController::class, 'agregarPermiso']);
    Route::get('/admin/roles/permisos/lista', [RolesController::class,'listaTodosPermisos']);
    Route::get('/admin/roles/permisos-todos/tabla', [RolesController::class,'tablaTodosPermisos']);
    Route::post('/admin/roles/borrar-global', [RolesController::class, 'borrarRolGlobal']);

    // --- PERMISOS ---
    Route::get('/admin/permisos/index', [PermisoController::class,'index'])->name('admin.permisos.index');
    Route::get('/admin/permisos/tabla', [PermisoController::class,'tablaUsuarios']);
    Route::post('/admin/permisos/nuevo-usuario', [PermisoController::class, 'nuevoUsuario']);
    Route::post('/admin/permisos/info-usuario', [PermisoController::class, 'infoUsuario']);
    Route::post('/admin/permisos/editar-usuario', [PermisoController::class, 'editarUsuario']);
    Route::post('/admin/permisos/nuevo-rol', [PermisoController::class, 'nuevoRol']);
    Route::post('/admin/permisos/extra-nuevo', [PermisoController::class, 'nuevoPermisoExtra']);
    Route::post('/admin/permisos/extra-borrar', [PermisoController::class, 'borrarPermisoGlobal']);

    // --- PERFIL ---
    Route::get('/admin/editar-perfil/index', [PerfilController::class,'indexEditarPerfil'])->name('admin.perfil');
    Route::post('/admin/editar-perfil/actualizar', [PerfilController::class, 'editarUsuario']);

    Route::get('sin-permisos', [ControlController::class,'indexSinPermiso'])->name('no.permisos.index');

    // --- CONTROL WEB ---
    Route::get('/panel', [ControlController::class,'indexRedireccionamiento'])->name('admin.panel');


    // --- UNIDAD DE MEDIDA ---
    Route::get('/admin/unidadmedida/index', [ConfiguracionController::class,'indexUnidadMedida'])->name('admin.unidadmedida.index');
    Route::get('/admin/unidadmedida/tabla/index', [ConfiguracionController::class,'tablaUnidadMedida']);
    Route::post('/admin/unidadmedida/nuevo', [ConfiguracionController::class, 'nuevaUnidadMedida']);
    Route::post('/admin/unidadmedida/informacion', [ConfiguracionController::class, 'informacionUnidadMedida']);
    Route::post('/admin/unidadmedida/editar', [ConfiguracionController::class, 'editarUnidadMedida']);

    // --- RUBRO ---
    Route::get('/admin/rubro/index', [ConfiguracionController::class,'indexRubro'])->name('admin.rubro.index');
    Route::get('/admin/rubro/tabla/index', [ConfiguracionController::class,'tablaRubro']);
    Route::post('/admin/rubro/nuevo', [ConfiguracionController::class, 'nuevaRubro']);
    Route::post('/admin/rubro/informacion', [ConfiguracionController::class, 'informacionRubro']);
    Route::post('/admin/rubro/editar', [ConfiguracionController::class, 'editarRubro']);

    // --- CUENTA ---
    Route::get('/admin/cuenta/index', [ConfiguracionController::class, 'indexCuenta'])->name('admin.cuenta.index');
    Route::get('/admin/cuenta/tabla/index', [ConfiguracionController::class, 'tablaCuenta']);
    Route::post('/admin/cuenta/nuevo', [ConfiguracionController::class, 'nuevaCuenta']);
    Route::post('/admin/cuenta/informacion', [ConfiguracionController::class, 'informacionCuenta']);
    Route::post('/admin/cuenta/editar', [ConfiguracionController::class, 'editarCuenta']);

    // --- OBJETO ESPECIFICO ---
    Route::get('/admin/objetoespecifico/index', [ConfiguracionController::class, 'indexObjetoEspecifico'])->name('admin.objetoespecifico.index');
    Route::get('/admin/objetoespecifico/tabla/index', [ConfiguracionController::class, 'tablaObjetoEspecifico']);
    Route::post('/admin/objetoespecifico/nuevo', [ConfiguracionController::class, 'nuevaObjetoEspecifico']);
    Route::post('/admin/objetoespecifico/informacion', [ConfiguracionController::class, 'informacionObjetoEspecifico']);
    Route::post('/admin/objetoespecifico/editar', [ConfiguracionController::class, 'editarObjetoEspecifico']);

    // --- INVENTARIO ---
    Route::get('/admin/inventario/index', [RepuestosController::class,'index'])->name('admin.materiales.index');
    Route::get('/admin/inventario/tabla/index', [RepuestosController::class,'tablaMateriales']);
    Route::post('/admin/inventario/nuevo', [RepuestosController::class, 'nuevoMaterial']);
    Route::post('/admin/inventario/informacion', [RepuestosController::class, 'informacionMaterial']);
    Route::post('/admin/inventario/editar', [RepuestosController::class, 'editarMaterial']);
    Route::post('/admin/informacion/herramienta/descartar', [RepuestosController::class, 'infoHerramientaDescartar']);
    Route::post('/admin/descartar/herramienta/inventario', [RepuestosController::class, 'descartarHerramientaInventario']);

    // --- REGISTRO DE UN PROYECTO ---
    Route::get('/admin/proyecto/index', [TipoProyectoController::class,'index'])->name('admin.tiposproyecto.index');
    Route::get('/admin/proyecto/tabla/index', [TipoProyectoController::class,'tablaProyectos']);
    Route::post('/admin/proyecto/nuevo', [TipoProyectoController::class, 'nuevoProyecto']);
    Route::post('/admin/proyecto/informacion', [TipoProyectoController::class, 'informacionProyecto']);
    Route::post('/admin/proyecto/editar', [TipoProyectoController::class, 'editarProyecto']);
    Route::post('/admin/proyecto/eliminar', [TipoProyectoController::class, 'borrarProyecto']);

    // --- REGISTRAR ENTRADA ---
    Route::get('/admin/registro/entrada', [RepuestosController::class,'indexRegistroEntrada'])->name('admin.entrada.registro.index');
    Route::post('/admin/buscar/material',  [RepuestosController::class,'buscadorMaterial']);
    Route::post('/admin/entradas/guardar',  [RepuestosController::class,'guardarEntrada']);
    Route::post('/admin/inventario/proyectos', [RepuestosController::class, 'proyectosPorMaterial']);

    // --- REGISTRAR SALIDA ---
    Route::get('/admin/registro/salida', [SalidasController::class,'indexRegistroSalida'])->name('admin.salida.registro.index');
    Route::post('/admin/salida/guardar',  [SalidasController::class,'guardarSalida']);
    Route::post('/admin/buscar/material/disponible',  [SalidasController::class,'buscadorMaterialDisponible']);
    Route::post('/admin/buscar/material/disponibilidad', [SalidasController::class, 'infoBodegaMaterialDetalleFila']);


    // --- CIERRE DE PROYECTOS ---
    Route::get('/admin/cierre/proyectos', [SalidasController::class,'indexTransferencias'])->name('admin.transferencias.index');
    Route::post('/admin/generar/salida/transferencia',  [SalidasController::class,'generarSalidaTransferencia']);


    // --- HISTORIAL / ENTRADAS ---
    Route::get('/admin/historial/entradas', [HistorialController::class,'indexHistorialEntradas'])->name('admin.historial.entradas.index');
    Route::get('/admin/historial/entradas/tabla',  [HistorialController::class,'tablaHistorialEntradas']);
    Route::post('/admin/historial/entradas/informacion', [HistorialController::class, 'informacionEntrada']);
    Route::post('/admin/historial/entradas/editar',      [HistorialController::class, 'editarEntrada']);
    Route::post('/admin/historial/entradas/eliminar',    [HistorialController::class, 'eliminarEntrada']);
    Route::post('/admin/historial/entradas/detalle',        [HistorialController::class, 'detalleEntrada']);
    Route::post('/admin/historial/entradas/detalle/editar', [HistorialController::class, 'editarDetalleEntrada']);

    Route::get('/admin/historial/entradas/extras/{id}', [HistorialController::class, 'vistaExtrasEntrada'])->name('admin.historial.entradas.extras');
    Route::post('/admin/historial/entradas/extras/guardar', [HistorialController::class, 'guardarExtrasEntrada']);

    // --- HISTORIAL / SALIDAS ---
    Route::get('/admin/historial/salidas', [HistorialController::class,'indexHistorialSalidas'])->name('admin.historial.salidas.index');
    Route::get('/admin/historial/salidas/tabla',  [HistorialController::class,'tablaHistorialSalidas']);
    Route::post('/admin/historial/salidas/informacion', [HistorialController::class, 'informacionSalida']);
    Route::post('/admin/historial/salidas/editar',      [HistorialController::class, 'editarSalida']);
    Route::post('/admin/historial/salidas/eliminar',    [HistorialController::class, 'eliminarSalida']);
    Route::post('/admin/historial/salidas/detalle', [HistorialController::class, 'detalleSalida']);
    Route::get('/admin/historial/salidas/extras/{id}',      [HistorialController::class, 'vistaExtrasSalida'])->name('admin.historial.salidas.extras');
    Route::post('/admin/historial/salidas/extras/guardar',  [HistorialController::class, 'guardarExtrasSalida']);

    // --- TRANSFERENCIA DE MATERIALES DE PROYECTOS CERRADOS ---
    Route::get('/admin/transferencia/material/proyectoscerrados', [SalidasController::class,'indexTransferenciasDeProyectosCerrados'])->name('admin.transferencias.materiales.index');
    Route::post('/admin/transferencia/material/xproyecto', [SalidasController::class,'retirarMaterialDeProyectosCerrados']);
    // Ruta nueva para cargar materiales del proyecto cerrado
    Route::post('/admin/transferencia/materiales/cerrado', [SalidasController::class, 'materialesDisponiblesCerrado']);

    // --- RESERVAS ---
    Route::get('/admin/reservas/index', [ReservasController::class,'indexReservasPendientes'])->name('admin.reservas.index');
    Route::post('/admin/reservas/listar', [ReservasController::class, 'listar']);
    Route::post('/admin/reservas/despachar', [ReservasController::class, 'despachar']);




    // --- REPORTE / ENTRADA POR PROYECTO
    Route::get('/admin/reporte/inventario/quehaentrado/proyecto', [ReportesController::class,'vistaQueHaEntradoProyecto'])->name('admin.reporte.inventario.entradaproyecto.index');
    Route::get('/admin/reporte/quehaentrado/proyectos/pdf/{idproy}/{desde}/{hasta}/{tipo}', [ReportesController::class,'pdfQueHaEntradoProyectos']);

    // --- REPORTE / SALIDA POR PROYECTO
    Route::get('/admin/reporte/quehasalido/proyectos/pdf/{idproy}/{desde}/{hasta}/{tipo}', [ReportesController::class,'pdfQueHaSalidoProyectos']);

    // --- REPORTE / INVENTARIO PROYECTO
    Route::get('/admin/reporte/inventario/quetengopor/proyecto', [ReportesController::class,'vistaQueTengoPorProyecto'])->name('admin.reporte.inventario.tengoporproyecto.index');
    Route::get('/admin/reporte/quetengopor/proyectos/pdf/{idproy}', [ReportesController::class,'reporteQueTengoPorProyecto']);

    // --- REPORTE / VER LOS MATERIALES QUE SOBRARON DE UN PROYECTO COMPLETADO
    Route::get('/admin/reporte/inventario/sobranteterminado/proyecto', [ReportesController::class,'vistaProyectoCompletado'])->name('admin.reporte.inventario.proyectocompletado.index');
    Route::get('/admin/reporte/inventario/sobranteterminado/proy/{idtrans}', [ReportesController::class,'reporteProyectoTerminado']);

    // --- REPORTE
    Route::get('/admin/reporte/proyectos/codigos', [ReportesController::class,'vistaReporteProyectoCodigos'])->name('admin.reporte.proyectos.codigos.index');
    Route::get('/admin/reporte/proyectos/codigos/pdf/{idproy}/{desde}/{hasta}', [ReportesController::class,'reportePDFProyectoCodigos']);








}); // end auth





