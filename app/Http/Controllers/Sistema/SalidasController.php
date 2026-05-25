<?php

namespace App\Http\Controllers\Sistema;

use App\Http\Controllers\Controller;
use App\Models\Departamentos;
use App\Models\Entradas;
use App\Models\EntradasDetalle;
use App\Models\InformacionGeneral;
use App\Models\Materiales;
use App\Models\Reserva;
use App\Models\Salidas;
use App\Models\SalidasDetalle;
use App\Models\TipoProyecto;
use App\Models\Transferencia;
use App\Models\TransferenciaDetalle;
use App\Models\UnidadMedida;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class SalidasController extends Controller
{

    public function indexRegistroSalida(){

        $arrayProyectos = Tipoproyecto::where('transferido', 0)->orderBy('nombre')->get();

        return view('backend.admin.repuestos.salidas.vistasalidaregistro', compact('arrayProyectos'));
    }


    public function buscadorMaterialDisponible(Request $request)
    {

        Log::info($request->all());

        if ($request->get('query')) {

            $query      = $request->get('query');
            $idProyecto = $request->get('id_proyecto');

            $materiales = Materiales::where('nombre', 'LIKE', "%{$query}%")->pluck('id');

            if ($materiales->isEmpty()) {
                return '';
            }

            $listado = DB::table('entradas_detalle as ed')
                ->join('entradas as e', 'e.id', '=', 'ed.id_entradas') // 👈 JOIN a entradas
                ->leftJoin(
                    DB::raw('(
                    SELECT id_entrada_detalle, SUM(cantidad_salida) as total_salido
                    FROM salidas_detalle
                    GROUP BY id_entrada_detalle
                ) as sd'),
                    'sd.id_entrada_detalle', '=', 'ed.id'
                )
                ->select(
                    'ed.id_material',
                    DB::raw('SUM(ed.cantidad_inicial) as total_inicial'),
                    DB::raw('COALESCE(SUM(sd.total_salido), 0) as total_salido'),
                    DB::raw('(SUM(ed.cantidad_inicial) - COALESCE(SUM(sd.total_salido), 0)) as disponible')
                )
                ->whereIn('ed.id_material', $materiales)
                ->when($idProyecto, fn($q) => $q->where('e.id_tipoproyecto', $idProyecto)) // 👈 filtro por proyecto
                ->groupBy('ed.id_material')
                ->havingRaw('disponible > 0')
                ->orderBy('ed.id_material')
                ->get();

            if ($listado->isEmpty()) {
                return '';
            }

            $output = '<ul class="dropdown-menu" style="display:block; position:relative; overflow:auto; max-height:300px; width:800px">';

            foreach ($listado as $row) {

                $infoMaterial = Materiales::with(['unidadMedida'])
                    ->find($row->id_material);

                if (!$infoMaterial) continue;

                $nombreCompleto = $infoMaterial->nombre .
                    " (" . optional($infoMaterial->unidadMedida)->nombre . ")";

                $output .= '
                <li class="cursor-pointer" onclick="modificarValor(this)"
                    id="' . $row->id_material . '"
                    data-tipo="material">
                    ' . $nombreCompleto . ' - Disponible: ' . $row->disponible . '
                </li>
                <hr>
            ';
            }

            $output .= '</ul>';

            return $output;
        }
    }



    public function infoBodegaMaterialDetalleFila(Request $request)
    {
        $regla = ['id' => 'required'];

        $validar = Validator::make($request->all(), $regla);
        if ($validar->fails()) {
            return ['success' => 0];
        }

        $infoMaterial = Materiales::find($request->id);
        if (!$infoMaterial) {
            return ['success' => 0];
        }

        $infoMedida   = UnidadMedida::find($infoMaterial->id_medida);
        $idProyecto   = $request->get('id_proyecto'); // 👈

        $listado = DB::table('entradas_detalle as ed')
            ->leftJoin(
                DB::raw('(
                SELECT id_entrada_detalle, SUM(cantidad_salida) as total_salido
                FROM salidas_detalle
                GROUP BY id_entrada_detalle
            ) as sd'),
                'sd.id_entrada_detalle', '=', 'ed.id'
            )
            ->join('entradas as e', 'e.id', '=', 'ed.id_entradas') // 👈 INNER JOIN (antes era left)
            ->select(
                'ed.id',
                'ed.id_entradas',
                'ed.cantidad_inicial',
                'ed.precio',
                'e.fecha',
                'ed.codigo',
                DB::raw('COALESCE(sd.total_salido, 0) as total_salido'),
                DB::raw('(ed.cantidad_inicial - COALESCE(sd.total_salido, 0)) as cantidadActual')
            )
            ->where('ed.id_material', $request->id)
            ->when($idProyecto, fn($q) => $q->where('e.id_tipoproyecto', $idProyecto)) // 👈
            ->havingRaw('cantidadActual > 0')
            ->orderBy('ed.id')
            ->get();

        foreach ($listado as $fila) {
            $fila->fechaIngreso = date("d-m-Y", strtotime($fila->fecha));
            $fila->precioFormat = '$' . number_format($fila->precio, 2, '.', ',');
        }

        $disponible = $listado->isEmpty() ? 1 : 0;

        return [
            'success'        => 1,
            'nombreMaterial' => $infoMaterial->nombre ?? '',
            'nombreMedida'   => $infoMedida->nombre   ?? '',
            'arrayIngreso'   => $listado,
            'disponible'     => $disponible,
        ];
    }













    public function guardarSalida(Request $request)
    {
        $rules = [
            'fecha'    => 'required',
            'proyecto' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return ['success' => 0];
        }

        $contenedor = json_decode($request->contenedorArray, true);

        if (empty($contenedor)) {
            return ['success' => 1];
        }

        // ── Validar que el proyecto no esté cerrado ──
        $proyecto = Tipoproyecto::find($request->proyecto);
        if (!$proyecto || $proyecto->transferido == 1) {
            return ['success' => 3]; // proyecto cerrado
        }

        // ✅ Agrupar por id_entrada_detalle y sumar cantidades del mismo lote
        $agrupado = [];
        foreach ($contenedor as $item) {
            $id = $item['infoIdEntradaDeta'];
            if (!isset($agrupado[$id])) {
                $agrupado[$id] = 0;
            }
            $agrupado[$id] += (int) $item['infoCantidad'];
        }

        DB::beginTransaction();

        try {

            // ✅ Validar con cantidades ya acumuladas
            $fila = 1;
            foreach ($agrupado as $idEntradaDetalle => $cantidadSalida) {

                $disponible = DB::table('entradas_detalle as ed')
                    ->leftJoin(
                        DB::raw('(
                        SELECT id_entrada_detalle, SUM(cantidad_salida) as total_salido
                        FROM salidas_detalle
                        GROUP BY id_entrada_detalle
                    ) as sd'),
                        'sd.id_entrada_detalle', '=', 'ed.id'
                    )
                    ->where('ed.id', $idEntradaDetalle)
                    ->selectRaw('(ed.cantidad_inicial - COALESCE(sd.total_salido, 0)) as disponible')
                    ->value('disponible');

                if (is_null($disponible) || $cantidadSalida > $disponible) {
                    DB::rollback();

                    $nombreMaterial = DB::table('entradas_detalle as ed')
                        ->join('materiales as m', 'm.id', '=', 'ed.id_material')
                        ->where('ed.id', $idEntradaDetalle)
                        ->value('m.nombre');

                    return [
                        'success'         => 2,
                        'fila'            => $fila,
                        'nombre_material' => $nombreMaterial ?? 'Material desconocido',
                        'cantidad_pedida' => $cantidadSalida,
                        'disponible'      => (int) $disponible,
                    ];
                }

                $fila++;
            }

            // Guardar cabecera
            $salida                  = new Salidas();
            $salida->fecha           = Carbon::parse($request->fecha);
            $salida->descripcion     = $request->descripcion;
            $salida->id_tipoproyecto = $request->proyecto;
            $salida->es_transferencia= 0;
            $salida->id_tipoproyecto_transferencia = null;
            $salida->save();

            // ✅ Guardar detalle con cantidades agrupadas
            foreach ($agrupado as $idEntradaDetalle => $cantidadSalida) {
                $detalle                      = new SalidasDetalle();
                $detalle->id_salida           = $salida->id;
                $detalle->id_entrada_detalle  = $idEntradaDetalle;
                $detalle->cantidad_salida     = $cantidadSalida;
                $detalle->save();
            }

            DB::commit();
            return ['success' => 10];

        } catch (\Throwable $e) {
            Log::error('guardarSalida: ' . $e);
            DB::rollback();
            return ['success' => 99];
        }
    }








    // *****************************

    public function indexTransferencias(){

        // LISTADO DE PROYECTOS (MENOS EL ID 1 YA QUE SERA EL INVENTARIO GENERAL)
        // Y QUE NO HAYAN SIDO TRANSFERIDOS

        $tipoproyecto = TipoProyecto::orderBy('nombre')
            ->where('id', '!=', 1)
            ->where('transferido', '!=', 1)
            ->get();

        return view('backend.admin.repuestos.registros.vistatransferidos', compact('tipoproyecto'));
    }


    public function generarSalidaTransferencia(Request $request)
    {
        $rules = ['fecha' => 'required'];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return ['success' => 0];
        }

        DB::beginTransaction();

        try {

            // Evitar cierre doble
            if (TipoProyecto::where('id', $request->idproyecto)->where('transferido', 1)->first()) {
                return ['success' => 1];
            }

            // Marcar como cerrado
            TipoProyecto::where('id', $request->idproyecto)->update(['transferido' => 1]);

            // Manejar documento opcional
            $nomDocumento = null;
            if ($request->hasFile('documento')) {
                $cadena       = Str::random(15);
                $tiempo       = microtime();
                $nombre       = str_replace(' ', '_', $cadena . $tiempo);
                $extension    = '.' . $request->documento->getClientOriginalExtension();
                $nomDocumento = $nombre . strtolower($extension);
                $avatar       = $request->file('documento');
                $guardado     = Storage::disk('archivos')->put($nomDocumento, \File::get($avatar));

                if (!$guardado) {
                    DB::rollback();
                    return ['success' => 99];
                }
            }

            // Guardar registro de cierre
            $transferencia                  = new Transferencia();
            $transferencia->id_tipoproyecto = $request->idproyecto;
            $transferencia->fecha           = Carbon::parse($request->fecha);
            $transferencia->descripcion     = $request->descripcion;
            $transferencia->documento       = $nomDocumento;
            $transferencia->save();

            // ── Snapshot del inventario al momento del cierre ─────────────
            $listado = DB::table('entradas_detalle as ed')
                ->leftJoin(DB::raw('(
                SELECT id_entrada_detalle, SUM(cantidad_salida) as total_salido
                FROM salidas_detalle GROUP BY id_entrada_detalle
            ) as sd'), 'sd.id_entrada_detalle', '=', 'ed.id')
                ->join('entradas as e', 'e.id', '=', 'ed.id_entradas')
                ->where('e.id_tipoproyecto', $request->idproyecto)
                ->selectRaw('
                ed.id,
                ed.precio,
                ed.nombre,
                (ed.cantidad_inicial - COALESCE(sd.total_salido, 0)) as disponible
            ')
                ->havingRaw('disponible > 0')
                ->get();

            foreach ($listado as $fila) {
                $det                     = new TransferenciaDetalle();
                $det->id_transferencia   = $transferencia->id;
                $det->id_entrada_detalle = $fila->id;
                $det->cantidad_sobrante  = $fila->disponible;
                $det->precio             = $fila->precio;
                $det->nombre_material    = $fila->nombre;
                $det->save();
            }

            DB::commit();
            return ['success' => 3];

        } catch (\Throwable $e) {
            Log::error('geenrarSalidaTransferencia: ' . $e);
            DB::rollback();
            return ['success' => 99];
        }
    }


    public function indexTransferenciasDeProyectosCerrados()
    {
        $proyectosCerrados = TipoProyecto::where('transferido', 1)->orderBy('nombre')->get();
        $proyectosActivos  = TipoProyecto::where('transferido', 0)->orderBy('nombre')->get();
        $departamentos     = Departamentos::orderBy('nombre')->get();

        return view('backend.admin.repuestos.transferenciacerrados.vistatransferenciamaterialcerrado', [
            'proyectosCerrados' => $proyectosCerrados,
            'proyectosActivos'  => $proyectosActivos,
            'departamentos'     => $departamentos,
        ]);
    }






    public function retirarMaterialDeProyectosCerrados(Request $request)
    {
        $rules = [
            'fecha'            => 'required',
            'proyecto_cerrado' => 'required',
            'tipo_destino'     => 'required',
            'contenedorArray'  => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return ['success' => 0];
        }

        DB::beginTransaction();

        try {

            $contenedor      = json_decode($request->contenedorArray, true);
            $tipodestino     = $request->tipo_destino;
            $proyectoCerrado = $request->proyecto_cerrado;
            $proyectoDestino = $request->proyecto_destino;

            if (empty($contenedor)) {
                return ['success' => 1];
            }

            // Datos acta
            $actaNumero        = $request->acta_numero ?? null;
            $actaReferencia    = $request->acta_referencia ?? null;
            $actaIdDepto       = $request->acta_id_departamento
                ? (int)$request->acta_id_departamento
                : null;

            $actaNombreSolic   = $request->acta_nombre_solic ?? null;
            $actaCargoSolic    = $request->acta_cargo_solic ?? null;
            $actaObservaciones = $request->acta_observaciones ?? null;
            $actaTipoDestino   = $request->acta_tipo_destino ?? null;

            // ==========================================================
            // TRANSFERENCIA A PROYECTO
            // ==========================================================
            if ($tipodestino === 'proyecto') {

                // SALIDA
                $salida = new Salidas();
                $salida->fecha                         = Carbon::parse($request->fecha);
                $salida->descripcion                   = $request->descripcion;
                $salida->id_tipoproyecto               = $proyectoCerrado;
                $salida->es_transferencia              = 1;
                $salida->id_tipoproyecto_transferencia = $proyectoDestino;
                $salida->acta_numero                   = $actaNumero;
                $salida->acta_referencia               = $actaReferencia;
                $salida->acta_id_departamento          = $actaIdDepto;
                $salida->acta_nombre_solic             = $actaNombreSolic;
                $salida->acta_cargo_solic              = $actaCargoSolic;
                $salida->acta_observaciones            = $actaObservaciones;
                $salida->acta_tipo_destino             = $actaTipoDestino;
                $salida->save();

                // ENTRADA
                $entrada = new Entradas();
                $entrada->id_tipoproyecto               = $proyectoDestino;
                $entrada->fecha                         = Carbon::parse($request->fecha);
                $entrada->descripcion                   = $request->descripcion;
                $entrada->es_transferencia              = 1;
                $entrada->id_tipoproyecto_transferencia = $proyectoCerrado;
                $entrada->save();

                // HISTORIAL
                $transferencia = new Transferencia();
                $transferencia->id_tipoproyecto        = $proyectoDestino;
                $transferencia->id_tipoproyecto_origen = $proyectoCerrado;
                $transferencia->id_salida              = $salida->id;
                $transferencia->id_entrada             = $entrada->id;
                $transferencia->fecha                  = Carbon::parse($request->fecha);
                $transferencia->descripcion            = $request->descripcion;
                $transferencia->documento              = null;
                $transferencia->tipo_salida            = 'proyecto';
                $transferencia->save();

                foreach ($contenedor as $item) {

                    $idEntradaDetalle = $item['infoIdEntradaDeta'];
                    $cantidad         = (int)$item['infoCantidad'];

                    $entradaDetalle = EntradasDetalle::find($idEntradaDetalle);

                    if (!$entradaDetalle) {
                        DB::rollback();
                        return ['success' => 2];
                    }

                    $totalSalido = SalidasDetalle::where(
                        'id_entrada_detalle',
                        $idEntradaDetalle
                    )->sum('cantidad_salida');

                    $totalReservado = Reserva::where(
                        'id_entrada_detalle',
                        $idEntradaDetalle
                    )->where('despachado', 0)->sum('cantidad');

                    $libre = $entradaDetalle->cantidad_inicial
                        - $totalSalido
                        - $totalReservado;

                    if ($cantidad > $libre) {

                        DB::rollback();

                        return [
                            'success'         => 3,
                            'nombre_material' => $entradaDetalle->material->nombre,
                            'cantidad_pedida' => $cantidad,
                            'disponible'      => $libre,
                        ];
                    }

                    // SALIDA DETALLE
                    $salidaDet = new SalidasDetalle();
                    $salidaDet->id_salida          = $salida->id;
                    $salidaDet->id_entrada_detalle = $idEntradaDetalle;
                    $salidaDet->cantidad_salida    = $cantidad;
                    $salidaDet->save();

                    $infoMaterial = Materiales::find(
                        $entradaDetalle->id_material
                    );

                    // ENTRADA DETALLE
                    $entradaDet = new EntradasDetalle();
                    $entradaDet->id_entradas      = $entrada->id;
                    $entradaDet->id_material      = $entradaDetalle->id_material;
                    $entradaDet->cantidad_inicial = $cantidad;
                    $entradaDet->precio           = $entradaDetalle->precio;
                    $entradaDet->nombre           =
                        $infoMaterial
                            ? $infoMaterial->nombre
                            : $entradaDetalle->nombre;

                    $entradaDet->save();

                    // HISTORIAL DETALLE
                    $transDet = new TransferenciaDetalle();
                    $transDet->id_transferencia   = $transferencia->id;
                    $transDet->id_entrada_detalle = $idEntradaDetalle;
                    $transDet->cantidad_sobrante  = $cantidad;
                    $transDet->precio             = $entradaDetalle->precio;
                    $transDet->nombre_material    =
                        $infoMaterial
                            ? $infoMaterial->nombre
                            : $entradaDetalle->nombre;

                    $transDet->save();
                }

            }
            // ==========================================================
            // SALIDA GENERAL
            // ==========================================================
            elseif ($tipodestino === 'general') {

                $salida = new Salidas();
                $salida->fecha                = Carbon::parse($request->fecha);
                $salida->descripcion          = $request->descripcion;
                $salida->id_tipoproyecto      = $proyectoCerrado;
                $salida->es_transferencia     = 0;
                $salida->acta_numero          = $actaNumero;
                $salida->acta_referencia      = $actaReferencia;
                $salida->acta_id_departamento = $actaIdDepto;
                $salida->acta_nombre_solic    = $actaNombreSolic;
                $salida->acta_cargo_solic     = $actaCargoSolic;
                $salida->acta_observaciones   = $actaObservaciones;
                $salida->acta_tipo_destino    = $actaTipoDestino;
                $salida->save();

                // HISTORIAL
                $transferencia = new Transferencia();
                $transferencia->id_tipoproyecto        = null;
                $transferencia->id_tipoproyecto_origen = $proyectoCerrado;
                $transferencia->id_salida              = $salida->id;
                $transferencia->id_entrada             = null;
                $transferencia->fecha                  = Carbon::parse($request->fecha);
                $transferencia->descripcion            = $request->descripcion;
                $transferencia->documento              = null;
                $transferencia->tipo_salida            = 'general';
                $transferencia->save();

                foreach ($contenedor as $item) {

                    $idEntradaDetalle = $item['infoIdEntradaDeta'];
                    $cantidad         = (int)$item['infoCantidad'];

                    $entradaDetalle = EntradasDetalle::find($idEntradaDetalle);

                    if (!$entradaDetalle) {
                        DB::rollback();
                        return ['success' => 2];
                    }

                    $salidaDet = new SalidasDetalle();
                    $salidaDet->id_salida          = $salida->id;
                    $salidaDet->id_entrada_detalle = $idEntradaDetalle;
                    $salidaDet->cantidad_salida    = $cantidad;
                    $salidaDet->save();

                    // HISTORIAL DETALLE
                    $transDet = new TransferenciaDetalle();
                    $transDet->id_transferencia   = $transferencia->id;
                    $transDet->id_entrada_detalle = $idEntradaDetalle;
                    $transDet->cantidad_sobrante  = $cantidad;
                    $transDet->precio             = $entradaDetalle->precio;
                    $transDet->nombre_material    =
                        $entradaDetalle->material->nombre
                        ?? $entradaDetalle->nombre;

                    $transDet->save();
                }
            }

            DB::commit();

            return [
                'success'  => 10,
                'id_salida'=> $salida->id ?? null
            ];

        } catch (\Throwable $e) {

            DB::rollback();

            Log::error(
                'retirarMaterialDeProyectosCerrados: '
                . $e->getMessage()
            );

            return ['success' => 99];
        }
    }



    public function materialesDisponiblesCerrado(Request $request)
    {
        $idProyecto = $request->id_proyecto;

        $materiales = DB::table('entradas_detalle as ed')
            ->join('entradas as e', 'e.id', '=', 'ed.id_entradas')
            ->join('materiales as m', 'm.id', '=', 'ed.id_material')
            ->leftJoin('unidadmedida as um', 'um.id', '=', 'm.id_medida') // leftJoin por si id_medida es nullable
            ->leftJoin(DB::raw('(
            SELECT id_entrada_detalle, SUM(cantidad_salida) as total_salido
            FROM salidas_detalle GROUP BY id_entrada_detalle
        ) as sd'), 'sd.id_entrada_detalle', '=', 'ed.id')
            ->leftJoin(DB::raw('(
            SELECT id_entrada_detalle, SUM(cantidad) as total_reservado
            FROM reservas WHERE despachado = 0 GROUP BY id_entrada_detalle
        ) as r'), 'r.id_entrada_detalle', '=', 'ed.id')
            ->where('e.id_tipoproyecto', $idProyecto)
            ->selectRaw('
            ed.id as id_entrada_detalle,
            m.nombre,
            COALESCE(um.nombre, "—") as medida,
            (ed.cantidad_inicial - COALESCE(sd.total_salido, 0)) as disponible,
            COALESCE(r.total_reservado, 0) as reservado,
            (ed.cantidad_inicial - COALESCE(sd.total_salido, 0) - COALESCE(r.total_reservado, 0)) as libre
        ')
            ->havingRaw('disponible > 0')
            ->get();

        return ['success' => 1, 'materiales' => $materiales];
    }












}
