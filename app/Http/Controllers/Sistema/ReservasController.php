<?php

namespace App\Http\Controllers\Sistema;

use App\Http\Controllers\Controller;
use App\Models\Entradas;
use App\Models\EntradasDetalle;
use App\Models\Materiales;
use App\Models\Reserva;
use App\Models\Salidas;
use App\Models\SalidasDetalle;
use App\Models\TipoProyecto;
use App\Models\Transferencia;
use App\Models\TransferenciaDetalle;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
class ReservasController extends Controller
{

    public function indexReservasPendientes()
    {
        $proyectosActivos = TipoProyecto::where('transferido', 0)
            ->orderBy('nombre')
            ->get();

        return view('backend.admin.repuestos.transferenciacerrados.vistareservaspendientes', [
            'proyectosActivos' => $proyectosActivos,
        ]);
    }


    public function crearReserva(Request $request)
    {
        $rules = [
            'fecha' => 'required',
            'proyecto_cerrado' => 'required',
            'contenedorArray' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return ['success' => 0];
        }

        DB::beginTransaction();

        try {
            $contenedor = json_decode($request->contenedorArray, true);
            $proyectoCerrado = $request->proyecto_cerrado;

            if (empty($contenedor)) {
                return ['success' => 1];
            }

            foreach ($contenedor as $item) {

                $idEntradaDetalle = $item['infoIdEntradaDeta'];
                $cantidad = (int)$item['infoCantidad'];

                $entradaDetalle = EntradasDetalle::find($idEntradaDetalle);

                if (!$entradaDetalle) {
                    DB::rollback();
                    return ['success' => 2];
                }

                // ── Calcular stock libre (sin reservas pendientes) ────────
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
                        'success' => 3,
                        'nombre_material' => $entradaDetalle->material->nombre
                            ?? $entradaDetalle->nombre,
                        'cantidad_pedida' => $cantidad,
                        'disponible' => $libre,
                    ];
                }

                // ── Crear reserva ─────────────────────────────────────────
                $reserva = new Reserva();
                $reserva->id_entrada_detalle = $idEntradaDetalle;
                $reserva->id_tipoproyecto = $proyectoCerrado;
                $reserva->cantidad = $cantidad;
                $reserva->descripcion = $request->descripcion ?? null;
                $reserva->fecha_reserva = Carbon::parse($request->fecha);
                $reserva->despachado = 0;
                $reserva->save();
            }

            DB::commit();

            return ['success' => 10];

        } catch (\Throwable $e) {

            DB::rollback();

            Log::error(
                'crearReserva: ' . $e->getMessage()
            );

            return ['success' => 99];
        }
    }









    // Cargar reservas pendientes (para tabla via axios)
    public function listar(Request $request)
    {
        $reservas = DB::table('reservas as r')
            ->join('entradas_detalle as ed', 'ed.id', '=', 'r.id_entrada_detalle')
            ->join('tipoproyecto as tp', 'tp.id', '=', 'r.id_tipoproyecto')
            ->leftJoin('tipoproyecto as tpd', 'tpd.id', '=', 'r.id_tipoproyecto_destino')
            ->leftJoin('materiales as m', 'm.id', '=', 'ed.id_material')
            ->orderBy('r.despachado', 'asc')
            ->orderBy('r.fecha_reserva', 'desc')
            ->selectRaw('
                r.id,
                r.id_entrada_detalle,
                r.cantidad,
                r.descripcion,
                r.fecha_reserva,
                r.fecha_despacho,
                r.despachado,
                r.tipo_destino,
                r.id_tipoproyecto_destino,
                r.id_salida,
                r.id_entrada,
                ed.precio                          as precio,
                COALESCE(ed.nombre, m.nombre)      as nombre_material,
                tp.nombre                          as nombre_proyecto_origen,
                tpd.nombre                         as nombre_proyecto_destino
            ')
            ->get();

        return response()->json([
            'success'  => 1,
            'reservas' => $reservas,
        ]);
    }



    public function despachar(Request $request)
    {
        $rules = [
            'fecha' => 'required',
            'despachos' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return ['success' => 0];
        }

        DB::beginTransaction();

        try {
            $despachos = json_decode($request->despachos, true);

            if (empty($despachos)) {
                return ['success' => 1];
            }

            foreach ($despachos as $d) {
                $idReserva = $d['idReserva'];
                $tipoDestino = $d['tipoDestino'];        // 'proyecto' | 'general' | 'liberar'
                $idDestino = $d['idDestino'] ?? null;  // id proyecto si es 'proyecto'

                $reserva = Reserva::find($idReserva);
                if (!$reserva || $reserva->despachado) {
                    DB::rollback();
                    return ['success' => 2, 'msg' => 'Reserva no encontrada o ya despachada'];
                }

                // ── Liberar reserva (cancelar) ────────────────────────────
                if ($tipoDestino === 'liberar') {
                    $reserva->despachado = 1;
                    $reserva->tipo_destino = 'liberada';
                    $reserva->fecha_despacho = Carbon::parse($request->fecha);
                    $reserva->id_tipoproyecto_destino = null;
                    $reserva->id_salida = null;
                    $reserva->id_entrada = null;
                    $reserva->save();
                    continue;
                }

                $entradaDetalle = EntradasDetalle::find($reserva->id_entrada_detalle);
                if (!$entradaDetalle) {
                    DB::rollback();
                    return ['success' => 2, 'msg' => 'Material no encontrado'];
                }

                $infoMaterial = Materiales::find($entradaDetalle->id_material);

                // ── Nombre del material (igual que retirar) ───────────────
                $nombreMaterial = $infoMaterial
                    ? $infoMaterial->nombre
                    : ($entradaDetalle->nombre ?? '—');

                // ==========================================================
                // TRANSFERENCIA A PROYECTO
                // ==========================================================
                if ($tipoDestino === 'proyecto' && $idDestino) {

                    // SALIDA del proyecto cerrado origen
                    $salida = new Salidas();
                    $salida->fecha = Carbon::parse($request->fecha);
                    $salida->descripcion = $request->descripcion ?? $reserva->descripcion;
                    $salida->id_tipoproyecto = $reserva->id_tipoproyecto;
                    $salida->es_transferencia = 1;
                    $salida->id_tipoproyecto_transferencia = $idDestino;
                    $salida->save();

                    // DETALLE SALIDA
                    $salidaDet = new SalidasDetalle();
                    $salidaDet->id_salida = $salida->id;
                    $salidaDet->id_entrada_detalle = $reserva->id_entrada_detalle;
                    $salidaDet->cantidad_salida = $reserva->cantidad;
                    $salidaDet->save();

                    // ENTRADA al proyecto destino
                    $entrada = new Entradas();
                    $entrada->id_tipoproyecto = $idDestino;
                    $entrada->fecha = Carbon::parse($request->fecha);
                    $entrada->descripcion = $request->descripcion ?? $reserva->descripcion;
                    $entrada->es_transferencia = 1;
                    $entrada->id_tipoproyecto_transferencia = $reserva->id_tipoproyecto;
                    $entrada->save();

                    // DETALLE ENTRADA
                    $entradaDet = new EntradasDetalle();
                    $entradaDet->id_entradas = $entrada->id;
                    $entradaDet->id_material = $entradaDetalle->id_material;
                    $entradaDet->cantidad_inicial = $reserva->cantidad;
                    $entradaDet->precio = $entradaDetalle->precio;
                    $entradaDet->codigo = $entradaDetalle->codigo;
                    $entradaDet->nombre = $nombreMaterial;
                    $entradaDet->save();

                    // HISTORIAL TRANSFERENCIA
                    $transferencia = new Transferencia();
                    $transferencia->id_tipoproyecto = $idDestino;
                    $transferencia->id_tipoproyecto_origen = $reserva->id_tipoproyecto;
                    $transferencia->id_salida = $salida->id;
                    $transferencia->id_entrada = $entrada->id;
                    $transferencia->fecha = Carbon::parse($request->fecha);
                    $transferencia->descripcion = $request->descripcion ?? $reserva->descripcion;
                    $transferencia->documento = null;
                    $transferencia->tipo_salida = 'proyecto';
                    $transferencia->save();

                    // HISTORIAL DETALLE
                    $transDet = new TransferenciaDetalle();
                    $transDet->id_transferencia = $transferencia->id;
                    $transDet->id_entrada_detalle = $reserva->id_entrada_detalle;
                    $transDet->cantidad_sobrante = $reserva->cantidad;
                    $transDet->precio = $entradaDetalle->precio;
                    $transDet->nombre_material = $nombreMaterial;
                    $transDet->save();

                    // MARCAR RESERVA DESPACHADA
                    $reserva->despachado = 1;
                    $reserva->tipo_destino = 'proyecto';
                    $reserva->fecha_despacho = Carbon::parse($request->fecha);
                    $reserva->id_tipoproyecto_destino = $idDestino;
                    $reserva->id_salida = $salida->id;
                    $reserva->id_entrada = $entrada->id;
                    $reserva->save();

                }
                // ==========================================================
                // SALIDA GENERAL
                // ==========================================================
                elseif ($tipoDestino === 'general') {

                    // SALIDA del proyecto cerrado origen
                    $salida = new Salidas();
                    $salida->fecha = Carbon::parse($request->fecha);
                    $salida->descripcion = $request->descripcion ?? $reserva->descripcion;
                    $salida->id_tipoproyecto = $reserva->id_tipoproyecto;
                    $salida->es_transferencia = 0;
                    $salida->save();

                    // DETALLE SALIDA
                    $salidaDet = new SalidasDetalle();
                    $salidaDet->id_salida = $salida->id;
                    $salidaDet->id_entrada_detalle = $reserva->id_entrada_detalle;
                    $salidaDet->cantidad_salida = $reserva->cantidad;
                    $salidaDet->save();

                    // HISTORIAL TRANSFERENCIA
                    $transferencia = new Transferencia();
                    $transferencia->id_tipoproyecto = null;
                    $transferencia->id_tipoproyecto_origen = $reserva->id_tipoproyecto;
                    $transferencia->id_salida = $salida->id;
                    $transferencia->id_entrada = null;
                    $transferencia->fecha = Carbon::parse($request->fecha);
                    $transferencia->descripcion = $request->descripcion ?? $reserva->descripcion;
                    $transferencia->documento = null;
                    $transferencia->tipo_salida = 'general';
                    $transferencia->save();

                    // HISTORIAL DETALLE
                    $transDet = new TransferenciaDetalle();
                    $transDet->id_transferencia = $transferencia->id;
                    $transDet->id_entrada_detalle = $reserva->id_entrada_detalle;
                    $transDet->cantidad_sobrante = $reserva->cantidad;
                    $transDet->precio = $entradaDetalle->precio;
                    $transDet->nombre_material = $nombreMaterial;
                    $transDet->save();

                    // MARCAR RESERVA DESPACHADA
                    $reserva->despachado = 1;
                    $reserva->tipo_destino = 'general';
                    $reserva->fecha_despacho = Carbon::parse($request->fecha);
                    $reserva->id_tipoproyecto_destino = null;
                    $reserva->id_salida = $salida->id;
                    $reserva->id_entrada = null;
                    $reserva->save();
                }
            }

            DB::commit();
            return ['success' => 10];

        } catch (\Throwable $e) {
            Log::error('despachar reserva: ' . $e);
            DB::rollback();
            return ['success' => 99];
        }
    }










}
