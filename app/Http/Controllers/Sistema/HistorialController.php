<?php

namespace App\Http\Controllers\Sistema;

use App\Http\Controllers\Controller;
use App\Models\Entradas;
use App\Models\EntradasDetalle;
use App\Models\Equipos;
use App\Models\InformacionGeneral;
use App\Models\Materiales;
use App\Models\Proveedor;
use App\Models\Reserva;
use App\Models\Salidas;
use App\Models\SalidasDetalle;
use App\Models\TipoCompra;
use App\Models\TipoEntrada;
use App\Models\TipoProyecto;
use App\Models\Transferencia;
use App\Models\TransferenciaDetalle;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class HistorialController extends Controller
{

    public function indexHistorialEntradas()
    {
        $arrayTipoEntrada = TipoEntrada::orderBy('nombre')->get();
        $arrayTipoCompra  = TipoCompra::orderBy('nombre')->get();
        $arrayProveedor   = Proveedor::orderBy('nombre')->get();

        return view('backend.admin.historial.entradas.vistahistorialentradas',
            compact('arrayTipoEntrada', 'arrayTipoCompra', 'arrayProveedor'));
    }

    public function tablaHistorialEntradas(Request $request)
    {
        $arrayEntradas = Entradas::with(['tipoEntrada', 'tipoCompra', 'proveedor', 'detalle'])
            ->when($request->fecha_desde, fn($q) => $q->whereDate('fecha', '>=', $request->fecha_desde))
            ->when($request->fecha_hasta, fn($q) => $q->whereDate('fecha', '<=', $request->fecha_hasta))
            ->orderBy('fecha', 'desc')
            ->get()
            ->map(function ($item) {
                $item->fecha_fmt    = date('d/m/Y', strtotime($item->fecha));
                $item->totalEntrada = $item->detalle->sum(fn($d) => $d->cantidad_inicial * $d->precio);
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
                'id'            => $entrada->id,
                'fecha'         => $entrada->fecha,
                'factura'       => $entrada->factura,
                'descripcion'   => $entrada->descripcion,
                'id_tipoentrada'=> $entrada->id_tipoentrada,
                'id_tipocompra' => $entrada->id_tipocompra,
                'id_proveedor'  => $entrada->id_proveedor,
            ]
        ]);
    }


    public function editarEntrada(Request $request)
    {
        $entrada = Entradas::find($request->id);

        if (!$entrada) {
            return response()->json(['success' => 0]);
        }

        $entrada->fecha          = $request->fecha;
        $entrada->factura        = $request->factura        ?: null;
        $entrada->descripcion    = $request->descripcion    ?: null;
        $entrada->id_tipoentrada = $request->id_tipoentrada;
        $entrada->id_tipocompra  = $request->id_tipocompra;
        $entrada->id_proveedor   = $request->id_proveedor   ?: null;
        $entrada->save();

        return response()->json(['success' => 1]);
    }



    public function eliminarEntrada(Request $request)
    {
        $entrada = Entradas::find($request->id);

        if (!$entrada) {
            return response()->json(['success' => 0]);
        }

        DB::beginTransaction();

        try {
            $idsDetalle = $entrada->detalle()->pluck('id');

            if ($idsDetalle->isNotEmpty()) {

                // Verificar si algún detalle tiene salidas
                $tieneSalidas = SalidasDetalle::whereIn('id_entrada_detalle', $idsDetalle)->exists();

                if ($tieneSalidas) {
                    DB::rollback();
                    return response()->json([
                        'success' => 2,
                        'msg' => 'Esta entrada tiene salidas registradas y no puede eliminarse.',
                    ]);
                }

                // Borrar entradas_detalle
                $entrada->detalle()->delete();
            }

            $entrada->delete();

            DB::commit();
            return response()->json(['success' => 1]);

        } catch (\Throwable $e) {
            DB::rollback();
            Log::error('eliminarEntrada: ' . $e->getMessage());
            return response()->json(['success' => 99]);
        }
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
                $tieneSalidas = SalidasDetalle::where('id_entrada_detalle', $item->id)->exists();
                return [
                    'id'               => $item->id,
                    'codigo'           => $item->codigo ?? '',
                    'nombre'           => $item->nombre ?? '',
                    'material'         => $item->material->nombre ?? '',
                    'cantidad_inicial' => $item->cantidad_inicial,
                    'precio'           => number_format($item->precio, 4),
                    'precio_raw'       => $item->precio,
                    'tiene_salidas'    => $tieneSalidas ? 1 : 0,
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

        // Actualizar cantidad solo si no tiene salidas
        if ($request->filled('cantidad')) {
            $tieneSalidas = SalidasDetalle::where('id_entrada_detalle', $detalle->id)->exists();
            if ($tieneSalidas) {
                return response()->json([
                    'success' => 2,
                    'msg'     => 'No se puede modificar la cantidad porque este material ya tiene salidas registradas.',
                ]);
            }
            $detalle->cantidad_inicial = (int) $request->cantidad;
        }

        $detalle->save();

        return response()->json(['success' => 1]);
    }

    public function eliminarDetalleEntrada(Request $request)
    {
        $detalle = EntradasDetalle::find($request->id);

        if (!$detalle) {
            return response()->json(['success' => 0]);
        }

        // Bloquear si tiene salidas
        $tieneSalidas = SalidasDetalle::where('id_entrada_detalle', $detalle->id)->exists();
        if ($tieneSalidas) {
            return response()->json([
                'success' => 4,
                'msg' => 'Este material ya tiene salidas registradas y no puede eliminarse.',
            ]);
        }

        DB::beginTransaction();
        try {
            $entradaId = $detalle->id_entradas;
            $detalle->delete();

            // Si era el último detalle, eliminar también la cabecera
            $quedan = EntradasDetalle::where('id_entradas', $entradaId)->count();

            if ($quedan === 0) {
                Entradas::where('id', $entradaId)->delete();
                DB::commit();
                return response()->json(['success' => 1, 'entrada_borrada' => true]);
            }

            DB::commit();
            return response()->json(['success' => 1, 'entrada_borrada' => false]);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('eliminarDetalleEntrada: ' . $e->getMessage());
            return response()->json(['success' => 99, 'msg' => 'Error al eliminar.']);
        }
    }


    public function vistaExtrasEntrada($id)
    {
        $entrada = Entradas::with(['tipoEntrada', 'tipoCompra', 'proveedor'])->find($id);

        return view('backend.admin.historial.entradas.vistaextras', compact('entrada'));
    }

    public function guardarExtrasEntrada(Request $request)
    {
        $entrada = Entradas::find($request->id_entrada);

        if (!$entrada) {
            return response()->json(['success' => 0]);
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

        return response()->json(['success' => 1]);
    }

    //***** ========================================================================================= **********


    public function indexHistorialSalidas()
    {
        $arrayEquipos = Equipos::orderBy('nombre')->get();

        return view('backend.admin.historial.salidas.vistahistorialsalidas',
            compact('arrayEquipos'));
    }

    public function tablaHistorialSalidas(Request $request)
    {
        $arraySalidas = Salidas::with('equipo')
            ->when($request->equipo, fn($q) =>
            $q->where('id_equipo', $request->equipo)
            )
            ->when($request->fecha_desde, fn($q) =>
            $q->whereDate('fecha', '>=', $request->fecha_desde)
            )
            ->when($request->fecha_hasta, fn($q) =>
            $q->whereDate('fecha', '<=', $request->fecha_hasta)
            )
            ->when($request->material, function ($q) use ($request) {
                $busqueda = '%' . $request->material . '%';
                $q->whereHas('detalle.entradaDetalle.material', function ($q2) use ($busqueda) {
                    $q2->where('nombre', 'LIKE', $busqueda);
                });
            })
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
                'id'              => $salida->id,
                'fecha'           => $salida->fecha,
                'descripcion'     => $salida->descripcion,
                'id_equipo'       => $salida->id_equipo,
                'ficha_nombre'    => $salida->ficha_nombre,
                'ficha_talonario' => $salida->ficha_talonario,
            ]
        ]);
    }

    public function editarSalida(Request $request)
    {
        $salida = Salidas::find($request->id);

        if (!$salida) {
            return response()->json(['success' => 0]);
        }

        $salida->fecha           = $request->fecha;
        $salida->descripcion     = $request->descripcion     ?: null;
        $salida->id_equipo       = $request->id_equipo;
        $salida->ficha_nombre    = $request->ficha_nombre    ?: null;
        $salida->ficha_talonario = $request->ficha_talonario ?: null;
        $salida->save();

        return response()->json(['success' => 1]);
    }

    public function eliminarSalida(Request $request)
    {
        $salida = Salidas::find($request->id);

        if (!$salida) {
            return response()->json(['success' => 0]);
        }

        DB::beginTransaction();
        try {
            $salida->detalle()->delete();
            $salida->delete();
            DB::commit();
            return response()->json(['success' => 1]);
        } catch (\Throwable $e) {
            DB::rollback();
            Log::error('eliminarSalida: ' . $e->getMessage());
            return response()->json(['success' => 99]);
        }
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
                    'id'              => $item->id,
                    'material'        => $item->entradaDetalle->material->nombre ?? '',
                    'unidadMedida'    => $item->entradaDetalle->material->unidadMedida->nombre ?? '',
                    'cantidad_salida' => $item->cantidad_salida,
                    'precio'          => number_format($item->entradaDetalle->precio ?? 0, 4),
                ];
            });

        return response()->json([
            'success' => 1,
            'detalle' => $detalle,
        ]);
    }


    public function vistaExtrasSalida($id)
    {
        $salida = Salidas::with('equipo')->find($id);

        if (!$salida) {
            return redirect()->route('admin.historial.salidas.index');
        }

        return view('backend.admin.historial.salidas.vistaextrassalidas', compact('salida'));
    }

    public function guardarExtrasSalida(Request $request)
    {
        $salida = Salidas::find($request->id_salida);

        if (!$salida) {
            return response()->json(['success' => 0]);
        }

        $contenedor = json_decode($request->contenedorArray, true);

        if (empty($contenedor)) {
            return response()->json(['success' => 0]);
        }

        // ── Agrupar por id_entrada_detalle para sumar si viene el mismo lote dos veces ──
        $agrupado = [];
        foreach ($contenedor as $index => $item) {
            $id = $item['infoIdEntradaDeta'];
            if (!isset($agrupado[$id])) {
                $agrupado[$id] = ['cantidad' => 0, 'fila' => $index + 1];
            }
            $agrupado[$id]['cantidad'] += (int) $item['infoCantidad'];
        }

        // ── Validar disponibilidad ──
        foreach ($agrupado as $idEntradaDeta => $datos) {
            $entDetalle = EntradasDetalle::with('material')->find($idEntradaDeta);

            if (!$entDetalle) {
                return response()->json([
                    'success' => 2,
                    'fila'    => $datos['fila'],
                    'msg'     => 'Material no encontrado en el lote.',
                ]);
            }

            $totalSalido = SalidasDetalle::where('id_entrada_detalle', $entDetalle->id)
                ->sum('cantidad_salida');

            $disponible = $entDetalle->cantidad_inicial - $totalSalido;

            if ($datos['cantidad'] > $disponible) {
                return response()->json([
                    'success'         => 2,
                    'fila'            => $datos['fila'],
                    'msg'             => 'Cantidad insuficiente.',
                    'nombre_material' => $entDetalle->material->nombre ?? 'Material desconocido',
                    'cantidad_pedida' => $datos['cantidad'],
                    'disponible'      => (int) $disponible,
                ]);
            }
        }

        // ── Guardar ──
        foreach ($contenedor as $item) {
            SalidasDetalle::create([
                'id_salida'          => $salida->id,
                'id_entrada_detalle' => $item['infoIdEntradaDeta'],
                'cantidad_salida'    => (int) $item['infoCantidad'],
            ]);
        }

        return response()->json(['success' => 10]);
    }


    public function eliminarDetalleSalida(Request $request)
    {
        $detalle = SalidasDetalle::find($request->id);

        if (!$detalle) {
            return response()->json(['success' => 0]);
        }

        DB::beginTransaction();
        try {
            $salidaId = $detalle->id_salida;
            $detalle->delete();

            $quedan = SalidasDetalle::where('id_salida', $salidaId)->count();

            if ($quedan === 0) {
                Salidas::where('id', $salidaId)->delete();
                DB::commit();
                return response()->json(['success' => 1, 'salida_borrada' => true]);
            }

            DB::commit();
            return response()->json(['success' => 1, 'salida_borrada' => false]);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('eliminarDetalleSalida: ' . $e->getMessage());
            return response()->json(['success' => 99]);
        }
    }



    public function pdfReporteSalidaTalonario(Request $request)
    {
        $fecha          = $request->input('fecha', '');
        $idEquipo       = $request->input('equipo', '');
        $descripcion    = $request->input('descripcion', '');
        $nTalonario     = $request->input('ficha_talonario', '');
        $nombreRecibe   = $request->input('ficha_nombre', '');
        $contenedorJson = $request->input('contenedorArray', '[]');
        $contenedor     = json_decode($contenedorJson, true) ?? [];

        $infoEquipo = \App\Models\Equipos::find($idEquipo);

        $html = $this->construirHtmlTalonario($fecha, $infoEquipo, $descripcion, $nTalonario, $nombreRecibe, $contenedor);

        $this->generarPdfSalida($html);
    }

    public function pdfReporteSalidaTalonarioHistorial($id)
    {
        $salida = \App\Models\Salidas::with(['equipo', 'detalle.entradaDetalle.material'])->find($id);

        if (!$salida) {
            abort(404);
        }

        $contenedor = $salida->detalle->map(function ($det) {
            return [
                'infoIdEntradaDeta' => $det->id_entrada_detalle,
                'infoCantidad'      => $det->cantidad_salida,
                'nombreMaterial'    => optional(optional($det->entradaDetalle)->material)->nombre ?? '',
            ];
        })->toArray();

        $html = $this->construirHtmlTalonario(
            $salida->fecha,
            $salida->equipo,
            $salida->descripcion,
            $salida->ficha_talonario,
            $salida->ficha_nombre,
            $contenedor
        );

        $this->generarPdfSalida($html);
    }

    private function construirHtmlTalonario($fecha, $infoEquipo, $descripcion, $nTalonario, $nombreRecibe, array $contenedor)
    {
        $fechaFmt     = $fecha ? date('d/m/Y', strtotime($fecha)) : '';
        $logoalcaldia = 'images/logo.png';

        $html = "
<table width='100%' style='border-collapse:collapse; font-family:Arial, sans-serif;'>
    <tr>
        <td style='width:25%; border:0.8px solid #000; padding:6px 8px;'>
            <table width='100%'>
                <tr>
                    <td style='width:30%; text-align:left;'>
                        <img src='{$logoalcaldia}' style='height:38px'>
                    </td>
                    <td style='width:70%; text-align:left; color:#104e8c; font-size:13px; font-weight:bold; line-height:1.3;'>
                        SANTA ANA NORTE<br>EL SALVADOR
                    </td>
                </tr>
            </table>
        </td>
        <td style='width:50%; border-top:0.8px solid #000; border-bottom:0.8px solid #000;
                   padding:6px 8px; text-align:center; font-size:15px; font-weight:bold;'>
            FORMULARIO DE SALIDA DE BODEGA
        </td>
        <td style='width:25%; border:0.8px solid #000; padding:0; vertical-align:top;'>
            <table width='100%' style='font-size:10px;'>
                <tr>
                    <td width='40%' style='border-right:0.8px solid #000; border-bottom:0.8px solid #000; padding:4px 6px;'><strong>Código:</strong></td>
                    <td width='60%' style='border-bottom:0.8px solid #000; padding:4px 6px; text-align:center;'></td>
                </tr>
                <tr>
                    <td style='border-right:0.8px solid #000; border-bottom:0.8px solid #000; padding:4px 6px;'><strong>Versión:</strong></td>
                    <td style='border-bottom:0.8px solid #000; padding:4px 6px; text-align:center;'></td>
                </tr>
                <tr>
                    <td style='border-right:0.8px solid #000; padding:4px 6px;'><strong>Fecha de vigencia:</strong></td>
                    <td style='padding:4px 6px; text-align:center;'></td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<br>

<table width='100%' style='font-family:Arial, sans-serif; font-size:12px; border-collapse:collapse;'>
    <tr>
        <td width='35%'><strong>FECHA:</strong> &nbsp; {$fechaFmt}</td>
        <td width='35%'><strong>EQUIPO:</strong> &nbsp; " . e($infoEquipo->nombre ?? '') . "</td>
        <td width='30%' style='text-align:center;'><strong>N.</strong> &nbsp; " . e($nTalonario) . "</td>
    </tr>
    <tr>
        <td colspan='3' style='padding-top:6px;'>
            <strong>NOMBRE:</strong> &nbsp; " . e($nombreRecibe) . "
        </td>
    </tr>";

        if ($descripcion) {
            $html .= "
    <tr>
        <td colspan='3' style='padding-top:4px;'>
            <strong>DESCRIPCIÓN:</strong> &nbsp; " . e($descripcion) . "
        </td>
    </tr>";
        }

        $html .= "
</table>

<br>

<table width='100%' style='border-collapse:collapse; font-family:Arial, sans-serif; font-size:12px;'>
    <thead>
        <tr>
            <th style='width:20%; border:0.8px solid #000; padding:6px 8px; text-align:center; background:#f0f0f0;'>CANTIDAD</th>
            <th style='width:80%; border:0.8px solid #000; padding:6px 8px; text-align:center; background:#f0f0f0;'>DESCRIPCION</th>
        </tr>
    </thead>
    <tbody>";

        foreach ($contenedor as $item) {
            $cantidad  = htmlspecialchars($item['infoCantidad']   ?? '');
            $nombreMat = htmlspecialchars($item['nombreMaterial'] ?? '');

            if (!empty($item['infoIdEntradaDeta'])) {
                $entDet = \App\Models\EntradasDetalle::with('material')
                    ->find($item['infoIdEntradaDeta']);
                if ($entDet && $entDet->material) {
                    $nombreMat = htmlspecialchars($entDet->material->nombre);
                }
            }

            $html .= "
        <tr>
            <td style='border:0.8px solid #000; padding:5px 8px; text-align:center;'>{$cantidad}</td>
            <td style='border:0.8px solid #000; padding:5px 8px;'>{$nombreMat}</td>
        </tr>";
        }

        $html .= "
    </tbody>
</table>

<br><br><br><br>

<table width='100%' style='font-family:Arial, sans-serif; font-size:11px; border-collapse:collapse;'>
    <tr>
        <td width='40%' style='text-align:center; padding-bottom:4px;'>________________________________</td>
        <td width='20%'></td>
        <td width='40%' style='text-align:center; padding-bottom:4px;'>________________________________</td>
    </tr>
    <tr>
        <td width='40%' style='text-align:center;'><strong>RECIBE</strong></td>
        <td width='20%'></td>
        <td width='40%' style='text-align:center;'><strong>ENTREGA</strong></td>
    </tr>
</table>";

        return $html;
    }

    private function generarPdfSalida(string $html)
    {
        $mpdf = new \Mpdf\Mpdf([
            'tempDir'       => sys_get_temp_dir(),
            'format'        => 'LETTER',
            'margin_top'    => 15,
            'margin_bottom' => 15,
            'margin_left'   => 15,
            'margin_right'  => 15,
        ]);

        $mpdf->SetTitle('Formulario de Salida de Bodega');
        $mpdf->showImageErrors = false;

        $stylesheet = file_get_contents('css/cssregistro.css');
        $mpdf->WriteHTML($stylesheet, 1);
        $mpdf->WriteHTML($html, 2);
        $mpdf->Output('salida_bodega_' . date('Ymd_His') . '.pdf', 'I');
    }




}
