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
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class HistorialController extends Controller
{

    public function indexHistorialEntradas()
    {
        $arrayProyectos = TipoProyecto::orderBy('nombre')->get(); // ajusta el modelo si es diferente

        return view('backend.admin.historial.entradas.vistahistorialentradas',
            compact('arrayProyectos'));
    }

    public function tablaHistorialEntradas(Request $request)
    {
        $arrayEntradas = Entradas::with([
            'tipoproyecto',
            'tipoproyectoTransferencia'
        ])
            ->when($request->proyecto, fn($q) =>
            $q->where('id_tipoproyecto', $request->proyecto)
            )
            ->when($request->fecha_desde, fn($q) =>
            $q->whereDate('fecha', '>=', $request->fecha_desde)
            )
            ->when($request->fecha_hasta, fn($q) =>
            $q->whereDate('fecha', '<=', $request->fecha_hasta)
            )
            ->orderBy('fecha', 'desc')
            ->get()
            ->map(function ($item) {
                $item->fecha_fmt = date('d/m/Y', strtotime($item->fecha));
                return $item;
            });

        return view('backend.admin.historial.entradas.tablahistorialentradas',
            compact('arrayEntradas'));
    }

    public function informacionEntrada(Request $request)
    {
        $entrada = Entradas::find($request->id);

        if (!$entrada) {
            return response()->json(['success' => 0]);
        }

        return response()->json([
            'success' => 1,
            'entrada' => [
                'id'          => $entrada->id,
                'fecha'       => $entrada->fecha,   // YYYY-MM-DD directo para el input type="date"
                'factura'     => $entrada->factura,
                'descripcion' => $entrada->descripcion,
            ]
        ]);
    }

    public function editarEntrada(Request $request)
    {
        $entrada = Entradas::find($request->id);

        if (!$entrada) {
            return response()->json(['success' => 0]);
        }

        $entrada->fecha       = $request->fecha;
        $entrada->factura     = $request->factura     ?: null;
        $entrada->descripcion = $request->descripcion ?: null;
        $entrada->save();

        return response()->json(['success' => 1]);
    }


    public function eliminarEntrada(Request $request)
    {
        $entrada = Entradas::find($request->id);

        if (!$entrada) {
            return response()->json(['success' => 0]);
        }

        $idsDetalle = $entrada->detalle()->pluck('id');

        if ($idsDetalle->isNotEmpty()) {

            // 4. IDs de salidas afectadas ANTES de borrar sus detalles
            $idsSalidas = SalidasDetalle::whereIn('id_entrada_detalle', $idsDetalle)
                ->pluck('id_salida')
                ->unique();

            // 5. Borrar salidas_detalle que apuntan a estos entradas_detalle
            SalidasDetalle::whereIn('id_entrada_detalle', $idsDetalle)->delete();

            // 6. Borrar salidas que quedaron sin ningún detalle
            if ($idsSalidas->isNotEmpty()) {
                $salidasHuerfanas = Salidas::whereIn('id', $idsSalidas)
                    ->whereDoesntHave('detalle')
                    ->pluck('id');

                if ($salidasHuerfanas->isNotEmpty()) {
                    Salidas::whereIn('id', $salidasHuerfanas)->delete();
                }
            }

            // 7. Borrar entradas_detalle
            $entrada->detalle()->delete();
        }

        // 8. Borrar la entrada
        $entrada->delete();

        return response()->json(['success' => 1]);
    }

    public function detalleEntrada(Request $request)
    {
        $entrada = Entradas::find($request->id);

        if (!$entrada) {
            return response()->json(['success' => 0]);
        }

        $detalle = $entrada->detalle()
            ->with('material')
            ->get()
            ->map(function ($item) {
                return [
                    'id'             => $item->id,
                    'codigo'         => $item->codigo ?? '',
                    'material'       => $item->material->nombre ?? '',
                    'cantidad_inicial'=> $item->cantidad_inicial,
                    'precio'         => number_format($item->precio, 4),
                    'precio_raw'     => $item->precio,  // sin formato para el input
                ];
            });

        return response()->json([
            'success' => 1,
            'detalle' => $detalle,
        ]);
    }

    public function editarDetalleEntrada(Request $request)
    {
        $detalle = EntradasDetalle::find($request->id);

        if (!$detalle) {
            return response()->json(['success' => 0]);
        }

        $detalle->codigo = $request->codigo ?: null;
        $detalle->precio = $request->precio;
        $detalle->save();

        return response()->json(['success' => 1]);
    }

    public function vistaExtrasEntrada($id)
    {
        $entrada = Entradas::with('tipoproyecto')->find($id);

        if (!$entrada || $entrada->tipoproyecto->transferido == 1) {
            return redirect()->route('admin.historial.entradas.index')
                ->with('error', 'El proyecto está cerrado, no se pueden agregar extras');
        }

        return view('backend.admin.historial.entradas.vistaextras', compact('entrada'));
    }

    public function guardarExtrasEntrada(Request $request)
    {
        $entrada = Entradas::find($request->id_entrada);

        if (!$entrada) {
            return response()->json(['success' => 0]);
        }

        // Verificar que el proyecto no esté cerrado
        if ($entrada->tipoproyecto->transferido == 1) {
            return response()->json(['success' => 1, 'mensaje' => 'El proyecto está cerrado']);
        }

        $contenedor = json_decode($request->contenedorArray, true);

        if (empty($contenedor)) {
            return response()->json(['success' => 0]);
        }

        foreach ($contenedor as $item) {
            EntradasDetalle::create([
                'id_entradas'      => $entrada->id,
                'id_material'      => $item['idMaterial'],
                'cantidad_inicial' => $item['infoCantidad'],
                'codigo'           => $item['infoCodigo'] ?: null,
                'precio'           => $item['infoPrecio'],
            ]);
        }

        return response()->json(['success' => 2]);
    }

    //***** ========================================================================================= **********


    public function indexHistorialSalidas()
    {
        $arrayProyectos = TipoProyecto::orderBy('nombre')->get();

        return view('backend.admin.historial.salidas.vistahistorialsalidas',
            compact('arrayProyectos'));
    }

    public function tablaHistorialSalidas(Request $request)
    {
        $arraySalidas = Salidas::with('tipoproyecto')
            ->when($request->proyecto, fn($q) =>
            $q->where('id_tipoproyecto', $request->proyecto)
            )
            ->when($request->fecha_desde, fn($q) =>
            $q->whereDate('fecha', '>=', $request->fecha_desde)
            )
            ->when($request->fecha_hasta, fn($q) =>
            $q->whereDate('fecha', '<=', $request->fecha_hasta)
            )
            // ── Filtro por material ──────────────────────────────
            ->when($request->material, function ($q) use ($request) {
                $busqueda = '%' . $request->material . '%';
                $q->whereHas('detalles.entradaDetalle.material', function ($q2) use ($busqueda) {
                    $q2->where('nombre', 'LIKE', $busqueda)
                        ->orWhere('codigo', 'LIKE', $busqueda);
                });
            })
            // ────────────────────────────────────────────────────
            ->orderBy('fecha', 'desc')
            ->get()
            ->map(function ($item) {
                $item->fecha_fmt = date('d/m/Y', strtotime($item->fecha));
                return $item;
            });

        return view('backend.admin.historial.salidas.tablahistorialsalidas',
            compact('arraySalidas'));
    }


    public function informacionSalida(Request $request)
    {
        $salida = Salidas::find($request->id);

        if (!$salida) {
            return response()->json(['success' => 0]);
        }

        return response()->json([
            'success' => 1,
            'salida'  => [
                'id'          => $salida->id,
                'fecha'       => $salida->fecha,
                'descripcion' => $salida->descripcion,
            ]
        ]);
    }

    public function editarSalida(Request $request)
    {
        $salida = Salidas::find($request->id);

        if (!$salida) {
            return response()->json(['success' => 0]);
        }

        $salida->fecha       = $request->fecha;
        $salida->descripcion = $request->descripcion ?: null;
        $salida->save();

        return response()->json(['success' => 1]);
    }

    public function eliminarSalida(Request $request)
    {
        $salida = Salidas::find($request->id);

        if (!$salida) {
            return response()->json(['success' => 0]);
        }

        // salidas_detalle apunta a salidas, hay que borrarla primero
        $salida->detalle()->delete();
        $salida->delete();

        return response()->json(['success' => 1]);
    }

    public function detalleSalida(Request $request)
    {
        $salida = Salidas::find($request->id);

        if (!$salida) {
            return response()->json(['success' => 0]);
        }

        $detalle = $salida->detalle()
            ->with('entradaDetalle.material')
            ->get()
            ->map(function ($item) {
                return [
                    'codigo'         => $item->entradaDetalle->id_material ?? '',
                    'material'       => $item->entradaDetalle->material->nombre ?? '',
                    'cantidad_salida'=> $item->cantidad_salida,
                    'precio'         => number_format($item->entradaDetalle->precio, 4),
                ];
            });

        return response()->json([
            'success' => 1,
            'detalle' => $detalle,
        ]);
    }


    public function vistaExtrasSalida($id)
    {
        $salida = Salidas::with('tipoproyecto')->find($id);

        if (!$salida || $salida->tipoproyecto->transferido == 1) {
            return redirect()->route('admin.historial.salidas.index')
                ->with('error', 'El proyecto está cerrado, no se pueden agregar extras');
        }



        return view('backend.admin.historial.salidas.vistaextrassalidas', compact('salida'));
    }

    public function guardarExtrasSalida(Request $request)
    {
        $salida = Salidas::find($request->id_salida);

        if (!$salida) {
            return response()->json(['success' => 0]);
        }

        if ($salida->tipoproyecto->transferido == 1) {
            return response()->json(['success' => 0, 'mensaje' => 'El proyecto está cerrado']);
        }

        $contenedor = json_decode($request->contenedorArray, true);

        if (empty($contenedor)) {
            return response()->json(['success' => 0]);
        }

        // Misma validación que el guardado original
        foreach ($contenedor as $index => $item) {
            $entradasDetalle = EntradasDetalle::find($item['infoIdEntradaDeta']);

            if (!$entradasDetalle) {
                return response()->json(['success' => 2, 'fila' => $index + 1]);
            }

            // Calcular cantidad disponible actual
            $totalSalido = SalidasDetalle::where('id_entrada_detalle', $entradasDetalle->id)
                ->sum('cantidad_salida');

            $disponible = $entradasDetalle->cantidad_inicial - $totalSalido;

            if ($item['infoCantidad'] > $disponible) {
                return response()->json(['success' => 2, 'fila' => $index + 1]);
            }
        }

        foreach ($contenedor as $item) {
            SalidasDetalle::create([
                'id_salida'          => $salida->id,
                'id_entrada_detalle' => $item['infoIdEntradaDeta'],
                'cantidad_salida'    => $item['infoCantidad'],
            ]);
        }

        return response()->json(['success' => 10]);
    }








    // ── Historial Transferencias ──────────────────────────────────────────────────

    public function indexHistorialTransferencias()
    {
        // Solo proyectos que tienen al menos una transferencia registrada
        $arrayProyectos = TipoProyecto::whereHas('transferencia')
            ->orderBy('nombre')
            ->get();

        return view('backend.admin.historial.transferencias.vistahistorialtransferencia',
            compact('arrayProyectos'));
    }


    public function tablaHistorialTransferencias(Request $request)
    {
        $arrayTransferencias = Transferencia::with([
            'tipoproyecto',         // destino
            'tipoproyectoOrigen',   // origen
        ])

            // Proyecto (filtra por ORIGEN)
            ->when($request->proyecto, function ($q) use ($request) {

                // especial: salida general
                if ($request->proyecto == 'general') {
                    $q->where('tipo_salida', 'general');
                } else {
                    $q->where(
                        'id_tipoproyecto_origen',
                        $request->proyecto
                    );
                }
            })

            // Tipo de salida (proyecto | general)
            ->when($request->tipo_salida, function ($q) use ($request) {
                $q->where(
                    'tipo_salida',
                    $request->tipo_salida
                );
            })

            // Fecha desde
            ->when($request->fecha_desde, function ($q) use ($request) {
                $q->whereDate(
                    'fecha',
                    '>=',
                    $request->fecha_desde
                );
            })

            // Fecha hasta
            ->when($request->fecha_hasta, function ($q) use ($request) {
                $q->whereDate(
                    'fecha',
                    '<=',
                    $request->fecha_hasta
                );
            })

            // Material
            ->when($request->material, function ($q) use ($request) {

                $busqueda = '%' . trim($request->material) . '%';

                $q->whereHas('detalle', function ($q2) use ($busqueda) {
                    $q2->where(
                        'nombre_material',
                        'LIKE',
                        $busqueda
                    );
                });
            })

            // Documento
            ->when($request->documento, function ($q) use ($request) {

                $q->where(
                    'documento',
                    'LIKE',
                    '%' . trim($request->documento) . '%'
                );
            })

            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->get()

            ->map(function ($item) {

                $item->fecha_fmt = date(
                    'd/m/Y',
                    strtotime($item->fecha)
                );

                // Proyecto de ORIGEN (de donde vino el material)
                $item->nombre_origen =
                    $item->tipoproyectoOrigen?->nombre
                    ?? '—';

                // Proyecto de DESTINO (a donde se mandó)
                $item->nombre_destino =
                    $item->tipo_salida === 'general'
                        ? 'Mantenimiento de instalaciones'
                        : ($item->tipoproyecto?->nombre ?? '—');

                return $item;
            });

        return view(
            'backend.admin.historial.transferencias.tablahistorialtransferencia',
            compact('arrayTransferencias')
        );
    }


    public function informacionTransferencia(Request $request)
    {
        $transferencia = Transferencia::find($request->id);

        if (!$transferencia) {
            return response()->json(['success' => 0]);
        }

        return response()->json([
            'success'       => 1,
            'transferencia' => [
                'id'          => $transferencia->id,
                'fecha'       => $transferencia->fecha,
                'descripcion' => $transferencia->descripcion,
                'documento'   => $transferencia->documento,
            ]
        ]);
    }

    public function eliminarTransferencia(Request $request)
    {
        $transferencia = Transferencia::find($request->id);

        if (!$transferencia) {
            return response()->json(['success' => 0]);
        }

        DB::beginTransaction();

        try {

            $idSalida  = $transferencia->id_salida;
            $idEntrada = $transferencia->id_entrada;

            // ==========================================================
            // 1) VALIDACION: el material que entro al proyecto destino
            //    NO debe haber sido usado todavia.
            //    Si ya tiene salidas o reservas, no se puede deshacer.
            // ==========================================================
            if ($idEntrada) {

                $detallesEntrada = EntradasDetalle::where(
                    'id_entradas',
                    $idEntrada
                )->get();

                foreach ($detallesEntrada as $entDet) {

                    $usado = SalidasDetalle::where(
                        'id_entrada_detalle',
                        $entDet->id
                    )->sum('cantidad_salida');

                    $reservado = Reserva::where(
                        'id_entrada_detalle',
                        $entDet->id
                    )->sum('cantidad');

                    if ($usado > 0 || $reservado > 0) {
                        DB::rollback();
                        return response()->json([
                            'success'         => 2,
                            'nombre_material' => $entDet->nombre,
                        ]);
                    }
                }
            }

            // ==========================================================
            // 2) BORRAR SALIDA (la del proyecto cerrado / origen)
            //    Primero los detalles, luego la cabecera.
            // ==========================================================
            if ($idSalida) {
                SalidasDetalle::where('id_salida', $idSalida)->delete();
                Salidas::where('id', $idSalida)->delete();
            }

            // ==========================================================
            // 3) BORRAR ENTRADA (la del proyecto destino)
            //    Solo existe en transferencia a proyecto.
            // ==========================================================
            if ($idEntrada) {
                EntradasDetalle::where('id_entradas', $idEntrada)->delete();
                Entradas::where('id', $idEntrada)->delete();
            }

            // ==========================================================
            // 4) BORRAR EL HISTORIAL (transferencia + detalle)
            // ==========================================================
            $transferencia->detalle()->delete();
            $transferencia->delete();

            DB::commit();

            return response()->json(['success' => 1]);

        } catch (\Throwable $e) {

            DB::rollback();

            Log::error(
                'eliminarTransferencia: ' . $e->getMessage()
            );

            return response()->json(['success' => 99]);
        }
    }

    public function detalleTransferencia(Request $request)
    {
        $transferencia = Transferencia::find($request->id);

        if (!$transferencia) {
            return response()->json(['success' => 0]);
        }

        $detalle = $transferencia->detalle()
            ->with([
                // Cargamos entradaDetalle → material → objetoEspecifico → cuenta → rubro
                'entradaDetalle.material.objetoEspecifico.cuenta'
            ])
            ->get()
            ->map(function ($item) {
                $ed       = $item->entradaDetalle;
                $material = $ed?->material;
                $objEsp   = $material?->objetoEspecifico;

                return [
                    // nombre_material guardado en transferencia_detalle como snapshot
                    // si está vacío caemos al nombre vivo del material
                    'nombre_material'   => $item->nombre_material
                        ?: ($material?->nombre ?? '—'),
                    'objeto_especifico' => $objEsp
                        ? $objEsp->codigo . ' — ' . $objEsp->nombre
                        : '—',
                    'cantidad_sobrante' => $item->cantidad_sobrante,
                    'precio'            => number_format($item->precio, 4),
                ];
            });

        return response()->json([
            'success' => 1,
            'detalle' => $detalle,
        ]);
    }













}
