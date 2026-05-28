<?php

namespace App\Http\Controllers\Sistema;

use App\Http\Controllers\Controller;
use App\Models\Departamentos;
use App\Models\Entradas;
use App\Models\EntradasDetalle;
use App\Models\InformacionGeneral;
use App\Models\Materiales;
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

class ReportesController extends Controller
{


    public function pdfQueHaSalidoProyectos($idproy, $desde, $hasta, $tipo)
    {
        $infoProyecto = Tipoproyecto::find($idproy);
        $fechaHoy = Carbon::now('America/El_Salvador')->format('d-m-Y');

        $sinFecha = ($desde === 'null' || $desde === '' || $hasta === 'null' || $hasta === '');

        $logoalcaldia = 'images/logo.png';

        if (!$sinFecha) {
            $start = date('Y-m-d 00:00:00', strtotime($desde));
            $end = date('Y-m-d 23:59:59', strtotime($hasta));
            $fechaLabel = date("d-m-Y", strtotime($desde)) . "  -  " . date("d-m-Y", strtotime($hasta));
        } else {
            $fechaLabel = "Todas las fechas";
        }

        $encabezado = "
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
        <td style='width:50%; border-top:0.8px solid #000; border-bottom:0.8px solid #000; padding:6px 8px; text-align:center; font-size:15px; font-weight:bold;'>
            REPORTE DE MATERIALES ENTREGADOS
        </td>
        <td style='width:25%; border:0.8px solid #000; padding:0; vertical-align:top;'>
            <table width='100%' style='font-size:10px;'>
                <tr>
                    <td width='40%' style='border-right:0.8px solid #000; border-bottom:0.8px solid #000; padding:4px 6px;'><strong>Código:</strong></td>
                    <td width='60%' style='border-bottom:0.8px solid #000; padding:4px 6px; text-align:center;'></td>
                </tr>
                <tr>
                    <td style='border-right:0.8px solid #000; border-bottom:0.8px solid #000; padding:4px 6px;'><strong>Versión:</strong></td>
                    <td style='border-bottom:0.8px solid #000; padding:4px 6px; text-align:center;'>000</td>
                </tr>
                <tr>
                    <td style='border-right:0.8px solid #000; padding:4px 6px;'><strong>Fecha de vigencia:</strong></td>
                    <td style='padding:4px 6px; text-align:center;'></td>
                </tr>
            </table>
        </td>
    </tr>
</table><br>

<table width='100%' style='border-collapse:collapse; font-family:Arial, sans-serif; margin-bottom:4px;'>
    <tr>
        <td style='width:22%; border:0.8px solid #ccc; padding:6px 8px; font-size:11px;
                   font-weight:bold; background:#f5f5f5; vertical-align:top;'>
            PROYECTO DE ORIGEN DE LOS MATERIALES
        </td>
        <td style='border:0.8px solid #ccc; padding:6px 8px; font-size:11px;'>
            " . e($infoProyecto->nombre ?? '') . "
        </td>
    </tr>
</table>

<table width='100%' style='border-collapse:collapse; font-family:Arial, sans-serif; margin-bottom:8px;'>
    <tr>
        <td style='width:22%; border:0.8px solid #ccc; padding:6px 8px; font-size:11px;
                   font-weight:bold; background:#f5f5f5;'>
            PERIODO
        </td>
        <td style='width:43%; border:0.8px solid #ccc; padding:6px 8px; font-size:11px;'>
            $fechaLabel
        </td>
        <td style='width:20%;'></td>
        <td style='width:7%; border:0.8px solid #ccc; padding:6px 8px; font-size:11px;
                   font-weight:bold; background:#f5f5f5; text-align:center;'>
            FECHA
        </td>
        <td style='width:8%; border:0.8px solid #ccc; padding:6px 8px; font-size:11px; text-align:center;'>
           $fechaHoy
        </td>
    </tr>
</table>
";

        // ─── TIPO 1: JUNTOS ───────────────────────────────────────────
        if ($tipo == 1) {

            $query = Salidas::where('id_tipoproyecto', $idproy);
            if (!$sinFecha) {
                $query->whereBetween('fecha', [$start, $end]);
            }
            $idsSalidas = $query->orderBy('fecha', 'ASC')->pluck('id');

            $detalles = SalidasDetalle::with([
                'entradaDetalle.material.unidadMedida',
                'entradaDetalle.material.objetoEspecifico',
            ])
                ->whereIn('id_salida', $idsSalidas)
                ->get();

            $dataArray = [];
            $sumaTotalCantidad = 0;

            foreach ($detalles as $det) {
                $entDet = $det->entradaDetalle;
                if (!$entDet || !$entDet->material) continue;

                $idMat  = $entDet->id_material;
                $precio = (float) ($entDet->precio ?? 0);

                $clave = $idMat . '|' . number_format($precio, 4, '.', '');

                if (!isset($dataArray[$clave])) {
                    $dataArray[$clave] = [
                        'nombre'   => $entDet->material->nombre ?? '',
                        'medida'   => $entDet->material->unidadMedida->nombre ?? '',
                        'codigo'   => $entDet->codigo ?? '',
                        'objespec' => $entDet->material->objetoEspecifico->codigo ?? 'SIN-CODIGO',
                        'cantidad' => 0,
                        'total'    => 0,
                        'precio'   => $precio,
                    ];
                }

                $dataArray[$clave]['cantidad'] += $det->cantidad_salida;
                $dataArray[$clave]['total']    += ($det->cantidad_salida * $precio);
                $sumaTotalCantidad             += $det->cantidad_salida;
            }

            usort($dataArray, function ($a, $b) {
                $cmp = strcmp($a['objespec'], $b['objespec']);
                return $cmp !== 0 ? $cmp : strcmp($a['nombre'], $b['nombre']);
            });

            $granTotal = array_sum(array_column($dataArray, 'total'));
            $granTotalFmt = number_format($granTotal, 4);
            $sumaTotalCantidadFmt = number_format($sumaTotalCantidad, 2, '.', ',');

            $mpdf = new \Mpdf\Mpdf(['tempDir' => sys_get_temp_dir(), 'format' => 'LETTER']);
            $mpdf->SetTitle('Reporte de Materiales Entregados');
            $mpdf->showImageErrors = false;

            $tabla = $encabezado;

            $tabla .= "
<table width='100%' id='tablaFor'>
    <tbody>
        <tr>
            <td style='font-weight:bold; width:11%; font-size:13px;'>Obj. Espec.</td>
            <td style='font-weight:bold; width:31%; font-size:13px;'>Material</td>
            <td style='font-weight:bold; width:11%; font-size:13px;'>Medida</td>
            <td style='font-weight:bold; width:11%; font-size:13px;'>Cantidad</td>
            <td style='font-weight:bold; width:13%; font-size:13px;'>Precio Unit.</td>
            <td style='font-weight:bold; width:13%; font-size:13px;'>Total ($)</td>
        </tr>";

            $codigoActual    = null;
            $subtotalCodigo  = 0;
            $subtotalCantCod = 0;

            $imprimirSubtotal = function ($codigo, $cantidad, $monto) {
                $cantFmt  = number_format($cantidad, 2, '.', ',');
                $montoFmt = number_format($monto, 4);
                return "
        <tr>
            <td colspan='3' style='font-weight:bold; font-size:12px; text-align:right;
                                    background:#f2f4f8; padding:4px;'>
                SUBTOTAL [" . e($codigo) . "]
            </td>
            <td style='font-weight:bold; font-size:12px; background:#f2f4f8; padding:4px;'>
                $cantFmt
            </td>
            <td style='background:#f2f4f8;'></td>
            <td style='font-weight:bold; font-size:12px; background:#f2f4f8; padding:4px;'>
                $ $montoFmt
            </td>
        </tr>";
            };

            foreach ($dataArray as $info) {

                if ($codigoActual !== null && $info['objespec'] !== $codigoActual) {
                    $tabla .= $imprimirSubtotal($codigoActual, $subtotalCantCod, $subtotalCodigo);
                    $subtotalCodigo  = 0;
                    $subtotalCantCod = 0;
                }
                $codigoActual = $info['objespec'];

                $subtotalCodigo  += $info['total'];
                $subtotalCantCod += $info['cantidad'];

                $precioFmt = number_format($info['precio'], 4);
                $totalFmt  = number_format($info['total'], 4);

                $tabla .= "
        <tr>
            <td style='font-size:12px;'>{$info['objespec']}</td>
            <td style='text-align:left; font-size:12px;'>{$info['nombre']}</td>
            <td style='font-size:12px;'>{$info['medida']}</td>
            <td style='font-size:12px;'>{$info['cantidad']}</td>
            <td style='font-size:12px;'>$ $precioFmt</td>
            <td style='font-size:12px;'>$ $totalFmt</td>
        </tr>";
            }

            if ($codigoActual !== null) {
                $tabla .= $imprimirSubtotal($codigoActual, $subtotalCantCod, $subtotalCodigo);
            }

            $tabla .= "
        <tr>
            <td colspan='3' style='font-weight:bold; font-size:13px; text-align:right;
                                    border-top:1.5px solid #000; padding-top:4px;'>
                TOTAL CANTIDAD:
            </td>
            <td style='font-weight:bold; font-size:13px; border-top:1.5px solid #000; padding-top:4px;'>
                $sumaTotalCantidadFmt
            </td>
            <td style='font-weight:bold; font-size:13px; text-align:right;
                        border-top:1.5px solid #000; padding-top:4px;'>
                TOTAL GENERAL:
            </td>
            <td style='font-weight:bold; font-size:13px; border-top:1.5px solid #000; padding-top:4px;'>
                $ $granTotalFmt
            </td>
        </tr>
    </tbody>
</table>";

            // ─── TIPO 2: SEPARADOS ────────────────────────────────────────
        } else {

            $query = Salidas::with([
                'detalle.entradaDetalle.material.unidadMedida',
                'proyectoTransferencia',
                'detalle.entradaDetalle.material.objetoEspecifico',
            ])->where('id_tipoproyecto', $idproy);

            if (!$sinFecha) {
                $query->whereBetween('fecha', [$start, $end]);
            }

            $arraySalidas = $query->orderBy('fecha', 'ASC')->get();

            // ════════════════════════════════════════════════════════════════
            // Precargar todas las reservas despachadas vinculadas a estas salidas
            // (id_salida → reserva). Esto evita hacer 1 query por salida.
            // ════════════════════════════════════════════════════════════════
            $idsSalidas = $arraySalidas->pluck('id')->toArray();

            $reservasPorSalida = [];
            if (!empty($idsSalidas)) {
                $reservas = \DB::table('reservas')
                    ->whereIn('id_salida', $idsSalidas)
                    ->where('despachado', true)
                    ->get();

                foreach ($reservas as $r) {
                    // Indexamos por id_salida (se usa la primera reserva encontrada
                    // para determinar tipo_destino y proyecto destino)
                    if (!isset($reservasPorSalida[$r->id_salida])) {
                        $reservasPorSalida[$r->id_salida] = $r;
                    }
                }
            }

            $granTotal = 0;
            $sumaTotalCantidad = 0;

            $mpdf = new \Mpdf\Mpdf(['tempDir' => sys_get_temp_dir(), 'format' => 'LETTER']);
            $mpdf->SetTitle('Reporte de Materiales Entregados');
            $mpdf->showImageErrors = false;

            $tabla = $encabezado;

            foreach ($arraySalidas as $salida) {

                $fechaFmt        = date("d-m-Y", strtotime($salida->fecha));
                $descripcion     = $salida->descripcion ?? '';
                $esTransferencia = (int)$salida->es_transferencia === 1;

                // ── ¿Esta salida proviene de una reserva despachada? ──────
                $reservaInfo  = null;
                $esPorReserva = isset($reservasPorSalida[$salida->id]);

                if ($esPorReserva) {
                    $r           = $reservasPorSalida[$salida->id];
                    $tipoDestino = $r->tipo_destino ?? null;
                    $idDestino   = $r->id_tipoproyecto_destino ?? null;

                    if ($tipoDestino === 'proyecto' && $idDestino) {
                        $proyDestino = Tipoproyecto::find($idDestino);
                        $nombreDest  = $proyDestino ? $proyDestino->nombre : 'Proyecto #' . $idDestino;
                        $reservaInfo = "RESERVA DESPACHADA &#8594; $nombreDest";
                    } elseif ($tipoDestino === 'general') {
                        $reservaInfo = "RESERVA DESPACHADA (Salida general)";
                    } else {
                        $reservaInfo = "RESERVA DESPACHADA";
                    }
                }

                // ── Renderizar viñeta según el origen de la salida ────────
                if ($esPorReserva) {
                    // RESERVA → amarillo / dorado
                    $tabla .= "
        <table width='100%' style='margin-bottom:3px;'>
            <tbody>
                <tr>
                    <td style='
                        background-color:#e9e9e9;
                        border:1px solid #aaaaaa;
                        color:#444444;
                        font-weight:bold;
                        font-size:12px;
                        padding:4px 8px;
                        text-align:center;
                    '>
                        $reservaInfo
                    </td>
                </tr>
            </tbody>
        </table>";
                } elseif ($esTransferencia) {
                    // TRANSFERENCIA / SALIDA GENERAL (no de reserva) → gris
                    if ($salida->id_tipoproyecto_transferencia) {
                        $nombreDestino = $salida->proyectoTransferencia
                            ? $salida->proyectoTransferencia->nombre
                            : 'Proyecto #' . $salida->id_tipoproyecto_transferencia;
                        $textoLabel = "TRANSFERENCIA &#8594; $nombreDestino";
                    } else {
                        $textoLabel = "SALIDA GENERAL (Sin proyecto destino)";
                    }

                    $tabla .= "
        <table width='100%' style='margin-bottom:3px;'>
            <tbody>
                <tr>
                    <td style='
                        background-color:#e9e9e9;
                        border:1px solid #aaaaaa;
                        color:#444444;
                        font-weight:bold;
                        font-size:12px;
                        padding:4px 8px;
                        text-align:center;
                    '>
                        $textoLabel
                    </td>
                </tr>
            </tbody>
        </table>";
                }

                $tabla .= "
<table width='100%' id='tablaFor'>
    <tbody>
        <tr>
            <td style='font-weight:bold; width:15%; font-size:13px;'>Fecha</td>
            <td style='font-weight:bold; width:85%; font-size:13px;'>Descripción</td>
        </tr>
        <tr>
            <td style='font-size:12px;'>$fechaFmt</td>
            <td style='font-size:12px;'>$descripcion</td>
        </tr>
    </tbody>
</table>";

                $tabla .= "
<table width='100%' id='tablaFor'>
    <tbody>
        <tr>
            <td style='font-weight:bold; width:12%; font-size:13px;'>Código</td>
            <td style='font-weight:bold; width:12%; font-size:13px;'>Medida</td>
            <td style='font-weight:bold; width:30%; font-size:13px;'>Material</td>
            <td style='font-weight:bold; width:11%; font-size:13px;'>Cantidad</td>
            <td style='font-weight:bold; width:15%; font-size:13px;'>Precio Unit.</td>
            <td style='font-weight:bold; width:15%; font-size:13px;'>Total ($)</td>
        </tr>";

                $subtotal = 0;
                $subtotalCantidad = 0;

                foreach ($salida->detalle as $det) {

                    $entDet = $det->entradaDetalle;
                    if (!$entDet || !$entDet->material) continue;

                    $codigo    = $entDet->material->objetoEspecifico->codigo ?? '';
                    $medida    = $entDet->material->unidadMedida->nombre ?? '';
                    $nombreMat = $entDet->material->nombre ?? '';
                    $cantidad  = $det->cantidad_salida;
                    $precio    = $entDet->precio ?? 0;
                    $total     = $cantidad * $precio;

                    $granTotal         += $total;
                    $subtotal          += $total;
                    $sumaTotalCantidad += $cantidad;
                    $subtotalCantidad  += $cantidad;

                    $precioFmt = number_format($precio, 4);
                    $totalFmt  = number_format($total, 4);

                    $tabla .= "
        <tr>
            <td style='font-size:12px;'>$codigo</td>
            <td style='font-size:12px;'>$medida</td>
            <td style='font-size:12px;'>$nombreMat</td>
            <td style='font-size:12px;'>$cantidad</td>
            <td style='font-size:12px;'>$ $precioFmt</td>
            <td style='font-size:12px;'>$ $totalFmt</td>
        </tr>";
                }

                $subtotalFmt = number_format($subtotal, 4);
                $subtotalCantidadFmt = number_format($subtotalCantidad, 2, '.', ',');

                $tabla .= "
        <tr>
            <td colspan='2' style='border-top:1px solid #000;'></td>
            <td style='font-weight:bold; font-size:12px; text-align:right;
                       border-top:1px solid #000; padding-top:3px;'>
                Subtotal cantidad:
            </td>
            <td style='font-weight:bold; font-size:12px;
                       border-top:1px solid #000; padding-top:3px;'>
                $subtotalCantidadFmt
            </td>
            <td style='font-weight:bold; font-size:12px; text-align:right;
                       border-top:1px solid #000; padding-top:3px;'>
                Subtotal:
            </td>
            <td style='font-weight:bold; font-size:12px;
                       border-top:1px solid #000; padding-top:3px;'>
                $ $subtotalFmt
            </td>
        </tr>
    </tbody>
</table><br>";
            }

            $granTotalFmt = number_format($granTotal, 4);
            $sumaTotalCantidadFmt = number_format($sumaTotalCantidad, 2, '.', ',');

            $tabla .= "
<table width='100%' style='margin-top:10px;'>
    <tbody>
        <tr>
            <td style='font-weight:bold; font-size:14px; text-align:right;
                        border-top:2px solid #000; padding-top:6px;'>
                TOTAL CANTIDAD:&nbsp;&nbsp;
            </td>
            <td style='font-weight:bold; font-size:14px; width:15%;
                        border-top:2px solid #000; padding-top:6px;'>
                $sumaTotalCantidadFmt
            </td>
            <td style='font-weight:bold; font-size:14px; text-align:right;
                        border-top:2px solid #000; padding-top:6px;'>
                TOTAL GENERAL:&nbsp;&nbsp;
            </td>
            <td style='font-weight:bold; font-size:14px; width:18%;
                        border-top:2px solid #000; padding-top:6px;'>
                $ $granTotalFmt
            </td>
        </tr>
    </tbody>
</table>";
        }

        $stylesheet = file_get_contents('css/cssregistro.css');
        $mpdf->WriteHTML($stylesheet, 1);
        $mpdf->setFooter("Página: " . '{PAGENO}' . "/" . '{nb}');
        $mpdf->WriteHTML($tabla, 2);
        $mpdf->Output();
    }



    public function vistaQueTengoPorProyecto()
    {
        $proyectos = Tipoproyecto::where('transferido', 0)->orderBy('nombre', 'ASC')->get();
        $transferido = Tipoproyecto::where('transferido', 1)->orderBy('nombre', 'ASC')->get();
        $infoGeneral = InformacionGeneral::where('id', 1)->first();

        return view('backend.admin.repuestos.reporte.vistaquetengoporproyecto', compact('proyectos', 'transferido', 'infoGeneral'));
    }

    public function actualizarFirmasSobrantes(Request $request)
    {
        try {

            InformacionGeneral::where('id', 1)->update([
                's_nombre1' => $request->s_nombre1,
                's_nombre2' => $request->s_nombre2,
            ]);

            return response()->json([
                'success' => 1
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'success' => 99
            ]);
        }
    }

    public function actualizarFirmasTraspaso(Request $request)
    {
        try {

            InformacionGeneral::where('id', 1)->update([
                'd_nombre1' => $request->d_nombre1,
                'd_nombre2' => $request->d_nombre2,
            ]);

            return response()->json([
                'success' => 1
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'success' => 99
            ]);
        }
    }


    public function reporteQueTengoPorProyecto($idproy)
    {
        $infoProyecto = Tipoproyecto::find($idproy);
        $fechaFormat  = date("d-m-Y");
        $logoalcaldia = 'images/logo.png';

        $detalles = EntradasDetalle::with('material.unidadMedida', 'material.objetoEspecifico')
            ->whereHas('entrada', fn($q) => $q->where('id_tipoproyecto', $idproy))
            ->get();

        // ── Agrupar por código objeto específico ──────────────────────────
        // Dentro de cada código, unir filas con mismo material y mismo precio.
        $porCodigo = [];

        foreach ($detalles as $det) {
            if (!$det->material) continue;

            $codigo     = $det->material->objetoEspecifico->codigo ?? 'SIN-CODIGO';
            $idMaterial = $det->id_material;
            $nombre     = $det->material->nombre ?? '';
            $medida     = $det->material->unidadMedida->nombre ?? '';
            $precio     = (float) $det->precio;

            if (!isset($porCodigo[$codigo])) {
                $porCodigo[$codigo] = [
                    'codigo'     => $codigo,
                    'materiales' => [],
                    'subtotal'   => 0,
                ];
            }

            // Clave de unión: mismo material + mismo precio unitario.
            $clave = $idMaterial . '|' . number_format($precio, 4, '.', '');

            if (!isset($porCodigo[$codigo]['materiales'][$clave])) {
                $porCodigo[$codigo]['materiales'][$clave] = [
                    'nombre'   => $nombre,
                    'medida'   => $medida,
                    'entradas' => 0,
                    'salidas'  => 0,
                    'precio'   => $precio,
                ];
            }

            $porCodigo[$codigo]['materiales'][$clave]['entradas'] += $det->cantidad_inicial;

            $salidas = SalidasDetalle::where('id_entrada_detalle', $det->id)
                ->sum('cantidad_salida');
            $porCodigo[$codigo]['materiales'][$clave]['salidas'] += $salidas;
        }

        // Calcular stock, subtotales y gran total una vez consolidadas las filas.
        $granTotal = 0;

        foreach ($porCodigo as $codigo => &$grupo) {
            $grupo['subtotal'] = 0;

            foreach ($grupo['materiales'] as $clave => &$mat) {
                $mat['stock']      = $mat['entradas'] - $mat['salidas'];
                $mat['subtotal']   = $mat['stock'] * $mat['precio'];
                $grupo['subtotal'] += $mat['subtotal'];
            }
            unset($mat);

            // Filtrar materiales sin stock.
            $grupo['materiales'] = array_filter(
                $grupo['materiales'],
                fn($m) => $m['stock'] > 0
            );

            // Ordenar materiales del grupo alfabéticamente.
            uasort($grupo['materiales'], fn($a, $b) => strcmp($a['nombre'], $b['nombre']));

            $granTotal += $grupo['subtotal'];
        }
        unset($grupo);

        // Eliminar grupos que quedaron sin materiales con stock.
        $porCodigo = array_filter($porCodigo, fn($g) => count($g['materiales']) > 0);

        // Ordenar grupos por código.
        ksort($porCodigo);

        // ── Inicializar mPDF ──────────────────────────────────────────────
        $mpdf = new \Mpdf\Mpdf([
            'tempDir'     => sys_get_temp_dir(),
            'format'      => 'LETTER',
            'orientation' => 'P',
        ]);
        $mpdf->SetTitle('Inventario Actual');
        $mpdf->showImageErrors = false;

        // ── Encabezado ────────────────────────────────────────────────────
        $tabla = "
<table width='100%' style='border-collapse:collapse; font-family:Arial, sans-serif;'>
    <tr>
        <td style='width:25%; border:0.8px solid #000; padding:6px 8px;'>
            <table width='100%'>
                <tr>
                    <td style='width:30%; text-align:left;'>
                        <img src='{$logoalcaldia}' style='height:38px'>
                    </td>
                    <td style='width:70%; text-align:left; color:#104e8c;
                                font-size:13px; font-weight:bold; line-height:1.3;'>
                        SANTA ANA NORTE<br>EL SALVADOR
                    </td>
                </tr>
            </table>
        </td>
        <td style='width:50%; border-top:0.8px solid #000; border-bottom:0.8px solid #000;
                   padding:6px 8px; text-align:center; font-size:15px; font-weight:bold;'>
            REPORTE INVENTARIO DE PROYECTO
        </td>
        <td style='width:25%; border:0.8px solid #000; padding:0; vertical-align:top;'>
            <table width='100%' style='font-size:10px;'>
                <tr>
                    <td width='40%' style='border-right:0.8px solid #000;
                                           border-bottom:0.8px solid #000; padding:4px 6px;'>
                        <strong>Código:</strong>
                    </td>
                    <td width='60%' style='border-bottom:0.8px solid #000;
                                           padding:4px 6px; text-align:center;'></td>
                </tr>
                <tr>
                    <td style='border-right:0.8px solid #000;
                               border-bottom:0.8px solid #000; padding:4px 6px;'>
                        <strong>Versión:</strong>
                    </td>
                    <td style='border-bottom:0.8px solid #000;
                               padding:4px 6px; text-align:center;'>000</td>
                </tr>
                <tr>
                    <td style='border-right:0.8px solid #000; padding:4px 6px;'>
                        <strong>Fecha de vigencia:</strong>
                    </td>
                    <td style='padding:4px 6px; text-align:center;'></td>
                </tr>
            </table>
        </td>
    </tr>
</table>
<br>";

        // ── Info proyecto ─────────────────────────────────────────────────
        $tabla .= "
<table width='100%' style='margin-bottom:4px; border-collapse:collapse;'>
    <tr>
        <td style='font-size:13px; padding:4px 0;'>
            <span style='font-weight:bold;'>Proyecto:</span> {$infoProyecto->nombre}<br>
            <span style='font-weight:bold;'>Fecha de generación:</span> {$fechaFormat}
        </td>
    </tr>
</table>";

        // ── Estilos inline reutilizables ──────────────────────────────────
        $thStyle = "font-weight:bold; font-size:11px; border:0.8px solid #000;
            padding:5px 4px; background:#d9e1f2; text-align:center;";
        $tdStyle = "font-size:11px; border:0.8px solid #000; padding:4px;";
        $tdC     = $tdStyle . " text-align:center;";
        $tdR     = $tdStyle . " text-align:right;";

        // ── Tabla de materiales agrupados por código ──────────────────────
        $tabla .= "
<table width='100%' style='border-collapse:collapse;'>
    <thead>
        <tr>
            <th style='{$thStyle} width:10%;'>Obj.<br>Espec.</th>
            <th style='{$thStyle} width:42%;'>Material</th>
            <th style='{$thStyle} width:12%;'>Medida</th>
            <th style='{$thStyle} width:10%;'>Stock</th>
            <th style='{$thStyle} width:13%;'>Precio Unit.</th>
            <th style='{$thStyle} width:13%;'>Total ($)</th>
        </tr>
    </thead>
    <tbody>";

        foreach ($porCodigo as $grupo) {
            foreach ($grupo['materiales'] as $mat) {
                $precioFmt = '$ ' . number_format($mat['precio'], 4);
                $totalFmt  = '$ ' . number_format($mat['subtotal'], 4);

                $tabla .= "
        <tr>
            <td style='{$tdC}'>" . e($grupo['codigo']) . "</td>
            <td style='{$tdStyle}'>" . e($mat['nombre']) . "</td>
            <td style='{$tdC}'>" . e($mat['medida']) . "</td>
            <td style='{$tdC} font-weight:bold;'>{$mat['stock']}</td>
            <td style='{$tdR}'>{$precioFmt}</td>
            <td style='{$tdR}'>{$totalFmt}</td>
        </tr>";
            }

            // ── Subtotal por código objeto específico ─────────────────────
            $subtotalFmt = '$ ' . number_format($grupo['subtotal'], 4);
            $tabla .= "
        <tr>
            <td colspan='5' style='font-weight:bold; font-size:11px; text-align:center;
                                    border:0.8px solid #000; padding:5px 4px; background:#f2f4f8;'>
                SUBTOTAL [" . e($grupo['codigo']) . "]
            </td>
            <td style='font-weight:bold; font-size:11px; text-align:right;
                        border:0.8px solid #000; padding:5px 4px; background:#f2f4f8;'>
                {$subtotalFmt}
            </td>
        </tr>";
        }

        // ── Total general ─────────────────────────────────────────────────
        $granTotalFmt = '$ ' . number_format($granTotal, 4);
        $tabla .= "
        <tr>
            <td colspan='5' style='font-weight:bold; font-size:12px; text-align:center;
                                    border:0.8px solid #000; padding:5px 4px; background:#d9e1f2;'>
                TOTAL GENERAL
            </td>
            <td style='font-weight:bold; font-size:12px; text-align:right;
                        border:0.8px solid #000; padding:5px 4px; background:#d9e1f2;'>
                {$granTotalFmt}
            </td>
        </tr>
    </tbody>
</table>";

        $stylesheet = file_get_contents('css/cssregistro.css');
        $mpdf->WriteHTML($stylesheet, 1);
        $mpdf->setFooter("Página: " . '{PAGENO}' . "/" . '{nb}');
        $mpdf->WriteHTML($tabla, 2);
        $mpdf->Output();
    }

    public function reporteProyectoTerminado($idtrans)
    {
        $infoProyecto = Tipoproyecto::find($idtrans);
        $fechaGenerado = date("d-m-Y");
        $logoalcaldia = 'images/logo.png';

        $transferencia = Transferencia::where('id_tipoproyecto', $idtrans)
            ->orderBy('id', 'desc')
            ->first();

        if (!$transferencia) {
            $mpdf = new \Mpdf\Mpdf(['tempDir' => sys_get_temp_dir(), 'format' => 'LETTER-P']);
            $mpdf->WriteHTML("<p style='font-family:Arial; font-size:14px; color:red;'>
    Este proyecto no tiene registro de cierre generado.</p>", 2);
            $mpdf->Output();
            return;
        }

        $fechaCierre = date("d-m-Y", strtotime($transferencia->fecha));

        $detallesSnapshot = TransferenciaDetalle::where('id_transferencia', $transferencia->id)
            ->get();

        // ── Agrupar por código objeto específico ──────────────────────────
        // Dentro de cada código, unir filas con mismo material y mismo precio.
        $porCodigo = [];

        foreach ($detallesSnapshot as $det) {
            $entradaDet = EntradasDetalle::with('material.unidadMedida', 'material.objetoEspecifico')
                ->find($det->id_entrada_detalle);

            $codigo     = $entradaDet?->material?->objetoEspecifico?->codigo ?? 'SIN-CODIGO';
            $nombre     = $entradaDet?->material?->nombre ?? $det->nombre_material ?? '—';
            $medida     = $entradaDet?->material?->unidadMedida?->nombre ?? '—';
            $idMaterial = $entradaDet?->material?->id ?? ('X' . md5($nombre));
            $precio     = (float) $det->precio;

            // Cantidad adquirida es fija del lote (no se acumula por material,
            // viene del lote concreto de entrada).
            $cantAdquirida = $entradaDet?->cantidad_inicial ?? 0;
            $cantSobrante  = (float) $det->cantidad_sobrante;

            if (!isset($porCodigo[$codigo])) {
                $porCodigo[$codigo] = [
                    'codigo'     => $codigo,
                    'materiales' => [],
                    'subtotal'   => 0,
                ];
            }

            // Clave de unión: mismo material + mismo precio unitario.
            $clave = $idMaterial . '|' . number_format($precio, 4, '.', '');

            if (!isset($porCodigo[$codigo]['materiales'][$clave])) {
                $porCodigo[$codigo]['materiales'][$clave] = [
                    'nombre'          => $nombre,
                    'medida'          => $medida,
                    'cant_adquirida'  => 0,
                    'cant_utilizada'  => 0,
                    'cantidad_cierre' => 0,
                    'precio'          => $precio,
                ];
            }

            // Acumular cantidades cuando hay varios renglones del mismo lote/material.
            $porCodigo[$codigo]['materiales'][$clave]['cant_adquirida']  += $cantAdquirida;
            $porCodigo[$codigo]['materiales'][$clave]['cantidad_cierre'] += $cantSobrante;
        }

        // Calcular cant_utilizada y subtotales una vez consolidadas las filas.
        $granTotal = 0;

        foreach ($porCodigo as $codigo => &$grupo) {
            $grupo['subtotal'] = 0;

            foreach ($grupo['materiales'] as $clave => &$mat) {
                $mat['cant_utilizada'] = $mat['cant_adquirida'] - $mat['cantidad_cierre'];
                $subtotalFila          = $mat['cantidad_cierre'] * $mat['precio'];
                $mat['subtotal']       = $subtotalFila;
                $grupo['subtotal']    += $subtotalFila;
            }
            unset($mat);

            // Filtrar materiales sin sobrante dentro del grupo.
            $grupo['materiales'] = array_filter(
                $grupo['materiales'],
                fn($m) => $m['cantidad_cierre'] > 0
            );

            // Ordenar materiales del grupo alfabéticamente.
            uasort($grupo['materiales'], fn($a, $b) => strcmp($a['nombre'], $b['nombre']));

            $granTotal += $grupo['subtotal'];
        }
        unset($grupo);

        // Eliminar grupos que quedaron sin materiales sobrantes.
        $porCodigo = array_filter($porCodigo, fn($g) => count($g['materiales']) > 0);

        // Ordenar grupos por código.
        ksort($porCodigo);

        // ── Inicializar mPDF ──────────────────────────────────────────────
        $mpdf = new \Mpdf\Mpdf(['tempDir' => sys_get_temp_dir(), 'format' => 'LETTER-P']);
        $mpdf->SetTitle('Reporte GEAD-002-REPO');
        $mpdf->showImageErrors = false;

        // ── Encabezado ────────────────────────────────────────────────────
        $tabla = "
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
            REPORTE DE SALDOS DE MATERIALES SOBRANTES
        </td>
        <td style='width:25%; border:0.8px solid #000; padding:0; vertical-align:top;'>
            <table width='100%' style='font-size:10px;'>
                <tr>
                    <td width='40%' style='border-right:0.8px solid #000; border-bottom:0.8px solid #000; padding:4px 6px;'><strong>Código:</strong></td>
                    <td width='60%' style='border-bottom:0.8px solid #000; padding:4px 6px; text-align:center;'>GEAD-002-REPO</td>
                </tr>
                <tr>
                    <td style='border-right:0.8px solid #000; border-bottom:0.8px solid #000; padding:4px 6px;'><strong>Versión:</strong></td>
                    <td style='border-bottom:0.8px solid #000; padding:4px 6px; text-align:center;'>000</td>
                </tr>
                <tr>
                    <td style='border-right:0.8px solid #000; padding:4px 6px;'><strong>Fecha de vigencia:</strong></td>
                    <td style='padding:4px 6px; text-align:center;'>22/05/2026</td>
                </tr>
            </table>
        </td>
    </tr>
</table>
<br>";

        // ── Info proyecto ─────────────────────────────────────────────────
        $tabla .= "
<table width='100%' style='margin-bottom:4px; border-collapse:collapse;'>
    <tr>
        <td style='font-size:13px; padding:4px 0;'>
            <span style='font-weight:bold;'>Proyecto de origen de los materiales:</span> {$infoProyecto->nombre}<br>
            <span style='font-weight:bold;'>Fecha de cierre:</span> $fechaCierre<br>
            <span style='font-weight:bold;'>Fecha de generación:</span> $fechaGenerado
        </td>
    </tr>
</table>";

        // ── Estilos inline reutilizables ──────────────────────────────────
        $thStyle = "font-weight:bold; font-size:11px; border:0.8px solid #000;
            padding:5px 4px; background:#d9e1f2; text-align:center;";
        $tdStyle = "font-size:11px; border:0.8px solid #000; padding:4px;";
        $tdC     = $tdStyle . " text-align:center;";
        $tdR     = $tdStyle . " text-align:right;";

        // ── Tabla de materiales agrupados por código ──────────────────────
        $tabla .= "
<table width='100%' style='border-collapse:collapse;'>
    <thead>
        <tr>
            <th style='{$thStyle} width:8%;'>Obj.<br>Espec.</th>
            <th style='{$thStyle} width:28%;'>Material</th>
            <th style='{$thStyle} width:8%;'>Medida</th>
            <th style='{$thStyle} width:9%;'>Cant.<br>Adquirida</th>
            <th style='{$thStyle} width:9%;'>Cant.<br>Utilizada</th>
            <th style='{$thStyle} width:9%;'>Cant.<br>Sobrante</th>
            <th style='{$thStyle} width:10%;'>Precio<br>Unit.</th>
            <th style='{$thStyle} width:10%;'>Total ($)</th>
        </tr>
    </thead>
    <tbody>";

        foreach ($porCodigo as $grupo) {
            foreach ($grupo['materiales'] as $mat) {
                $totalLinea  = $mat['subtotal'];
                $precioFmt   = '$ ' . number_format($mat['precio'], 4);
                $totalFmt    = '$ ' . number_format($totalLinea, 4);

                $tabla .= "
        <tr>
            <td style='{$tdC}'>" . e($grupo['codigo']) . "</td>
            <td style='{$tdStyle}'>" . e($mat['nombre']) . "</td>
            <td style='{$tdC}'>" . e($mat['medida']) . "</td>
            <td style='{$tdC}'>{$mat['cant_adquirida']}</td>
            <td style='{$tdC}'>{$mat['cant_utilizada']}</td>
            <td style='{$tdC} font-weight:bold;'>{$mat['cantidad_cierre']}</td>
            <td style='{$tdR}'>{$precioFmt}</td>
            <td style='{$tdR}'>{$totalFmt}</td>
        </tr>";
            }

            // ── Subtotal por código objeto específico ─────────────────────
            $subtotalFmt = '$ ' . number_format($grupo['subtotal'], 4);
            $tabla .= "
        <tr>
            <td colspan='7' style='font-weight:bold; font-size:11px; text-align:center;
                                    border:0.8px solid #000; padding:5px 4px; background:#f2f4f8;'>
                SUBTOTAL [" . e($grupo['codigo']) . "]
            </td>
            <td style='font-weight:bold; font-size:11px; text-align:right;
                        border:0.8px solid #000; padding:5px 4px; background:#f2f4f8;'>
                {$subtotalFmt}
            </td>
        </tr>";
        }

        // ── Total general ─────────────────────────────────────────────────
        $granTotalFmt = '$ ' . number_format($granTotal, 4);
        $tabla .= "
        <tr>
            <td colspan='7' style='font-weight:bold; font-size:12px; text-align:right;
                                    border:0.8px solid #000; padding:5px 4px; background:#d9e1f2;'>
                TOTAL GENERAL:
            </td>
            <td style='font-weight:bold; font-size:12px; text-align:right;
                        border:0.8px solid #000; padding:5px 4px; background:#d9e1f2;'>
                {$granTotalFmt}
            </td>
        </tr>
    </tbody>
</table>";

        // ── Sección de firmas ─────────────────────────────────────────────
        $informacionGeneral = InformacionGeneral::where('id', 1)->first();

        $tabla .= "
<table width='100%' style='margin-top:30px; border-collapse:collapse; font-family:Arial, sans-serif;'>
    <tr>
        <td style='width:50%; padding-right:40px; vertical-align:top;'>
            <div style='font-weight:bold; font-size:12px; margin-bottom:8px;'>ELABORADO POR:</div>
            <table width='100%' style='border-collapse:collapse;'>
                <tr><td style='height:{$informacionGeneral->px_firmas}px;'></td></tr>
                <tr>
                    <td style='font-size:12px; padding-bottom:8px;'>
                        FIRMA:
                        <table width='90%' style='border-collapse:collapse; display:inline-table;'>
                            <tr><td style='border-bottom:0.8px solid #000; height:16px;'></td></tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td style='font-size:12px; padding-bottom:8px;'>
                        NOMBRE:
                        <table width='85%' style='border-collapse:collapse; display:inline-table;'>
                            <tr><td style='border-bottom:0.8px solid #000; height:16px;'></td></tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td style='font-size:12px; padding-bottom:8px;'>
                        CARGO:
                        <table width='87%' style='border-collapse:collapse; display:inline-table;'>
                            <tr><td style='border-bottom:0.8px solid #000; height:16px;'></td></tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td style='font-size:13px; color:#333; text-align:center;'>
                        {$informacionGeneral->s_nombre1}
                    </td>
                </tr>
            </table>
        </td>
        <td style='width:50%; padding-left:40px; vertical-align:top;'>
            <div style='font-weight:bold; font-size:12px; margin-bottom:8px;'>REVISADO POR:</div>
            <table width='100%' style='border-collapse:collapse;'>
                <tr><td style='height:{$informacionGeneral->px_firmas}px;'></td></tr>
                <tr>
                    <td style='font-size:12px; padding-bottom:8px;'>
                        FIRMA:
                        <table width='90%' style='border-collapse:collapse; display:inline-table;'>
                            <tr><td style='border-bottom:0.8px solid #000; height:16px;'></td></tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td style='font-size:12px; padding-bottom:8px;'>
                        NOMBRE:
                        <table width='85%' style='border-collapse:collapse; display:inline-table;'>
                            <tr><td style='border-bottom:0.8px solid #000; height:16px;'></td></tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td style='font-size:12px; padding-bottom:8px;'>
                        CARGO:
                        <table width='87%' style='border-collapse:collapse; display:inline-table;'>
                            <tr><td style='border-bottom:0.8px solid #000; height:16px;'></td></tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td style='font-size:13px; color:#333; text-align:center;'>
                        {$informacionGeneral->s_nombre2}
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>";

        $stylesheet = file_get_contents('css/cssregistro.css');
        $mpdf->WriteHTML($stylesheet, 1);
        $mpdf->setFooter("Página: " . '{PAGENO}' . "/" . '{nb}');
        $mpdf->WriteHTML($tabla, 2);
        $mpdf->Output();
    }


    public function vistaQueHaEntradoProyecto()
    {
        $proyectos = TipoProyecto::orderBy('nombre', 'ASC')->get();

        return view('backend.admin.repuestos.reporte.vistaquehaentradoproyecto', compact('proyectos'));
    }


    public function pdfQueHaEntradoProyectos($idproy, $desde, $hasta, $tipo)
    {
        $infoProyecto = Tipoproyecto::find($idproy);

        $sinFecha = ($desde === 'null' || $desde === '' || $hasta === 'null' || $hasta === '');

        $logoalcaldia = 'images/logo.png';

        if (!$sinFecha) {
            $start = date('Y-m-d 00:00:00', strtotime($desde));
            $end = date('Y-m-d 23:59:59', strtotime($hasta));
            $fechaLabel = date("d-m-Y", strtotime($desde)) . "  -  " . date("d-m-Y", strtotime($hasta));
        } else {
            $fechaLabel = "Todas las fechas";
        }

        $fechaHoy = Carbon::now('America/El_Salvador')->format('d-m-Y');

        $encabezado = "
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
        <td style='width:50%; border-top:0.8px solid #000; border-bottom:0.8px solid #000; padding:6px 8px; text-align:center; font-size:15px; font-weight:bold;'>
            REPORTE DE MATERIALES RECIBIDOS
        </td>
        <td style='width:25%; border:0.8px solid #000; padding:0; vertical-align:top;'>
            <table width='100%' style='font-size:10px;'>
                <tr>
                    <td width='40%' style='border-right:0.8px solid #000; border-bottom:0.8px solid #000; padding:4px 6px;'><strong>Código:</strong></td>
                    <td width='60%' style='border-bottom:0.8px solid #000; padding:4px 6px; text-align:center;'></td>
                </tr>
                <tr>
                    <td style='border-right:0.8px solid #000; border-bottom:0.8px solid #000; padding:4px 6px;'><strong>Versión:</strong></td>
                    <td style='border-bottom:0.8px solid #000; padding:4px 6px; text-align:center;'>000</td>
                </tr>
                <tr>
                    <td style='border-right:0.8px solid #000; padding:4px 6px;'><strong>Fecha de vigencia:</strong></td>
                    <td style='padding:4px 6px; text-align:center;'></td>
                </tr>
            </table>
        </td>
    </tr>
</table><br>

<table width='100%' style='border-collapse:collapse; font-family:Arial, sans-serif; margin-bottom:4px;'>
    <tr>
        <td style='width:22%; border:0.8px solid #ccc; padding:6px 8px; font-size:11px;
                   font-weight:bold; background:#f5f5f5; vertical-align:top;'>
            PROYECTO DE ORIGEN DE LOS MATERIALES
        </td>
        <td style='border:0.8px solid #ccc; padding:6px 8px; font-size:11px;'>
            " . e($infoProyecto->nombre ?? '') . "
        </td>
    </tr>
</table>

<table width='100%' style='border-collapse:collapse; font-family:Arial, sans-serif; margin-bottom:8px;'>
    <tr>
        <td style='width:22%; border:0.8px solid #ccc; padding:6px 8px; font-size:11px;
                   font-weight:bold; background:#f5f5f5;'>
            PERIODO
        </td>
        <td style='width:43%; border:0.8px solid #ccc; padding:6px 8px; font-size:11px;'>
            $fechaLabel
        </td>
        <td style='width:20%;'></td>
        <td style='width:7%; border:0.8px solid #ccc; padding:6px 8px; font-size:11px;
                   font-weight:bold; background:#f5f5f5; text-align:center;'>
            FECHA
        </td>
        <td style='width:8%; border:0.8px solid #ccc; padding:6px 8px; font-size:11px; text-align:center;'>
           $fechaHoy
        </td>
    </tr>
</table>
";

        $totalCantidad = 0;

        // ─── TIPO 1: JUNTOS ───────────────────────────────────────────
        if ($tipo == 1) {

            $query = Entradas::where('id_tipoproyecto', $idproy);
            if (!$sinFecha) {
                $query->whereBetween('fecha', [$start, $end]);
            }
            $idsEntradas = $query->orderBy('fecha', 'ASC')->pluck('id');

            $detalles = EntradasDetalle::with([
                'material.unidadMedida',
                'material.objetoEspecifico',
            ])
                ->whereIn('id_entradas', $idsEntradas)
                ->get();

            $dataArray = [];
            $granTotal = 0;

            foreach ($detalles as $det) {
                $idMat  = $det->id_material;
                $precio = (float) $det->precio;
                $totalCantidad += $det->cantidad_inicial;

                $clave = $idMat . '|' . number_format($precio, 4, '.', '');

                if (!isset($dataArray[$clave])) {
                    $dataArray[$clave] = [
                        'nombre'         => $det->material->nombre ?? '',
                        'medida'         => $det->material->unidadMedida->nombre ?? '',
                        'codigo'         => $det->material->codigo ?? '',
                        'objespec'       => $det->material->objetoEspecifico->codigo ?? 'SIN-CODIGO',
                        'cantidad'       => 0,
                        'totalMaterial'  => 0,
                        'precioUnitario' => $precio,
                    ];
                }

                $dataArray[$clave]['cantidad']      += $det->cantidad_inicial;
                $dataArray[$clave]['totalMaterial'] += ($precio * $det->cantidad_inicial);
            }

            usort($dataArray, function ($a, $b) {
                $cmp = strcmp($a['objespec'], $b['objespec']);
                return $cmp !== 0 ? $cmp : strcmp($a['nombre'], $b['nombre']);
            });

            foreach ($dataArray as $item) {
                $granTotal += $item['totalMaterial'];
            }

            $granTotalFmt     = number_format($granTotal, 2);
            $totalCantidadFmt = number_format($totalCantidad, 2);

            $mpdf = new \Mpdf\Mpdf(['tempDir' => sys_get_temp_dir(), 'format' => 'LETTER']);
            $mpdf->SetTitle('Reporte de Materiales Recibidos');
            $mpdf->showImageErrors = false;

            $tabla = $encabezado;

            $tabla .= "
<table width='100%' id='tablaFor'>
    <tbody>
        <tr>
            <td style='font-weight:bold; width:11%; font-size:13px;'>Obj. Espec.</td>
            <td style='font-weight:bold; width:31%; font-size:13px;'>Material</td>
            <td style='font-weight:bold; width:11%; font-size:13px;'>Medida</td>
            <td style='font-weight:bold; width:11%; font-size:13px;'>Cantidad</td>
            <td style='font-weight:bold; width:13%; font-size:13px;'>Precio Unit.</td>
            <td style='font-weight:bold; width:13%; font-size:13px;'>Total ($)</td>
        </tr>";

            $codigoActual    = null;
            $subtotalCodigo  = 0;
            $subtotalCantCod = 0;

            $imprimirSubtotal = function ($codigo, $cantidad, $monto) {
                $cantFmt  = number_format($cantidad, 2);
                $montoFmt = number_format($monto, 4);
                return "
<tr>
    <td colspan='3' style='font-weight:bold; font-size:12px; text-align:right;
                            background:#f2f4f8; padding:4px;'>
        SUBTOTAL [" . e($codigo) . "]
    </td>
    <td style='font-weight:bold; font-size:12px; background:#f2f4f8; padding:4px;'>
        $cantFmt
    </td>
    <td style='background:#f2f4f8;'></td>
    <td style='font-weight:bold; font-size:12px; background:#f2f4f8; padding:4px;'>
        $ $montoFmt
    </td>
</tr>";
            };

            foreach ($dataArray as $info) {

                if ($codigoActual !== null && $info['objespec'] !== $codigoActual) {
                    $tabla .= $imprimirSubtotal($codigoActual, $subtotalCantCod, $subtotalCodigo);
                    $subtotalCodigo  = 0;
                    $subtotalCantCod = 0;
                }
                $codigoActual = $info['objespec'];

                $subtotalCodigo  += $info['totalMaterial'];
                $subtotalCantCod += $info['cantidad'];

                $precioFmt = number_format($info['precioUnitario'], 4);
                $totalFmt  = number_format($info['totalMaterial'], 4);

                $tabla .= "
<tr>
    <td style='font-size:12px;'>{$info['objespec']}</td>
    <td style='text-align:left; font-size:12px;'>{$info['nombre']}</td>
    <td style='font-size:12px;'>{$info['medida']}</td>
    <td style='font-size:12px;'>{$info['cantidad']}</td>
    <td style='font-size:12px;'>$ $precioFmt</td>
    <td style='font-size:12px;'>$ $totalFmt</td>
</tr>";
            }

            if ($codigoActual !== null) {
                $tabla .= $imprimirSubtotal($codigoActual, $subtotalCantCod, $subtotalCodigo);
            }

            $tabla .= "
<tr>
    <td colspan='3' style='font-weight:bold; font-size:13px; text-align:right;
                            border-top:1.5px solid #000; padding-top:4px;'>
        TOTAL CANTIDAD:
    </td>
    <td style='font-weight:bold; font-size:13px; border-top:1.5px solid #000; padding-top:4px;'>
        $totalCantidadFmt
    </td>
    <td style='font-weight:bold; font-size:13px; text-align:right;
                border-top:1.5px solid #000; padding-top:4px;'>
        TOTAL GENERAL:
    </td>
    <td style='font-weight:bold; font-size:13px; border-top:1.5px solid #000; padding-top:4px;'>
        $ $granTotalFmt
    </td>
</tr>
</tbody>
</table>";

            // ─── TIPO 2: SEPARADOS ────────────────────────────────────────
        } else {

            // ── IDs de entradas que vienen de despacho de reserva ────────
            $idsEntradasDeReserva = DB::table('transferencia')
                ->where('id_tipoproyecto', $idproy)
                ->where('origen_registro', 'reserva')
                ->whereNotNull('id_entrada')
                ->pluck('id_entrada')
                ->toArray();

            $query = Entradas::with([
                'detalle.material.unidadMedida',
                'detalle.material.objetoEspecifico',
            ])
                ->where('id_tipoproyecto', $idproy);

            if (!$sinFecha) {
                $query->whereBetween('fecha', [$start, $end]);
            }

            $arrayEntradas = $query->orderBy('fecha', 'ASC')->get();

            $granTotal = 0;

            $mpdf = new \Mpdf\Mpdf(['tempDir' => sys_get_temp_dir(), 'format' => 'LETTER']);
            $mpdf->SetTitle('Reporte de Materiales Recibidos');
            $mpdf->showImageErrors = false;

            $tabla = $encabezado;

            foreach ($arrayEntradas as $entrada) {

                $fechaFmt    = date("d-m-Y", strtotime($entrada->fecha));
                $descripcion = $entrada->descripcion ?? '';
                $factura     = $entrada->factura ?? '';

                $esReserva       = in_array($entrada->id, $idsEntradasDeReserva);
                $esTransferencia = (int) $entrada->es_transferencia === 1 && !$esReserva;

                // ── Banner según origen ───────────────────────────────────
                if ($esReserva) {
                    $tabla .= "
<table width='100%' style='margin-bottom:3px;'>
    <tbody>
        <tr>
            <td style='
                background-color:#e9e9e9;
                border:1px solid #aaaaaa;
                color:#444444;
                font-weight:bold;
                font-size:12px;
                padding:4px 8px;
                text-align:center;
            '>
                ENTRADA POR DESPACHO DE RESERVA
            </td>
        </tr>
    </tbody>
</table>";

                } elseif ($esTransferencia) {

                    $proyectoOrigen = null;
                    if ($entrada->id_tipoproyecto_transferencia) {
                        $proyectoOrigen = Tipoproyecto::find($entrada->id_tipoproyecto_transferencia);
                    }
                    $nombreOrigen = $proyectoOrigen
                        ? $proyectoOrigen->nombre
                        : 'Proyecto #' . $entrada->id_tipoproyecto_transferencia;

                    $tabla .= "
<table width='100%' style='margin-bottom:3px;'>
    <tbody>
        <tr>
            <td style='
                background-color:#e9e9e9;
                border:1px solid #aaaaaa;
                color:#444444;
                font-weight:bold;
                font-size:12px;
                padding:4px 8px;
                text-align:center;
            '>
                ENTRADA POR CIERRE DE PROYECTO: $nombreOrigen
            </td>
        </tr>
    </tbody>
</table>";
                }

                $tabla .= "
<table width='100%' id='tablaFor'>
    <tbody>
        <tr>
            <td style='font-weight:bold; width:15%; font-size:13px;'>Fecha</td>
            <td style='font-weight:bold; width:20%; font-size:13px;'>Factura</td>
            <td style='font-weight:bold; width:65%; font-size:13px;'>Descripción</td>
        </tr>
        <tr>
            <td style='font-size:12px;'>$fechaFmt</td>
            <td style='font-size:12px;'>$factura</td>
            <td style='font-size:12px;'>$descripcion</td>
        </tr>
    </tbody>
</table>";

                $tabla .= "
<table width='100%' id='tablaFor'>
    <tbody>
        <tr>
            <td style='font-weight:bold; width:13%; font-size:13px;'>Código</td>
            <td style='font-weight:bold; width:12%; font-size:13px;'>Medida</td>
            <td style='font-weight:bold; width:30%; font-size:13px;'>Material</td>
            <td style='font-weight:bold; width:12%; font-size:13px;'>Cantidad</td>
            <td style='font-weight:bold; width:15%; font-size:13px;'>Precio Unit.</td>
            <td style='font-weight:bold; width:15%; font-size:13px;'>Total ($)</td>
        </tr>";

                $subtotal         = 0;
                $subtotalCantidad = 0;

                foreach ($entrada->detalle as $det) {
                    $totalCantidad    += $det->cantidad_inicial;
                    $subtotalCantidad += $det->cantidad_inicial;

                    $totalLinea  = $det->precio * $det->cantidad_inicial;
                    $granTotal  += $totalLinea;
                    $subtotal   += $totalLinea;

                    $codigo    = $det->material->objetoEspecifico->codigo ?? '';
                    $nombreMat = $det->material->nombre ?? '';
                    $medida    = $det->material->unidadMedida->nombre ?? '';
                    $precioFmt = number_format($det->precio, 4);
                    $totalFmt  = number_format($totalLinea, 4);

                    $tabla .= "
        <tr>
            <td style='font-size:12px;'>$codigo</td>
            <td style='font-size:12px;'>$medida</td>
            <td style='font-size:12px;'>$nombreMat</td>
            <td style='font-size:12px;'>{$det->cantidad_inicial}</td>
            <td style='font-size:12px;'>$ $precioFmt</td>
            <td style='font-size:12px;'>$ $totalFmt</td>
        </tr>";
                }

                $subtotalFmt         = number_format($subtotal, 4);
                $subtotalCantidadFmt = number_format($subtotalCantidad, 2);

                $tabla .= "
        <tr>
            <td colspan='3' style='font-weight:bold; font-size:12px; text-align:right;
                                   border-top:1px solid #000; padding-top:3px;'>
                Subtotal Cantidad:
            </td>
            <td style='font-weight:bold; font-size:12px; border-top:1px solid #000; padding-top:3px;'>
                $subtotalCantidadFmt
            </td>
            <td style='font-weight:bold; font-size:12px; text-align:right;
                        border-top:1px solid #000; padding-top:3px;'>
                Subtotal:
            </td>
            <td style='font-weight:bold; font-size:12px; border-top:1px solid #000; padding-top:3px;'>
                $ $subtotalFmt
            </td>
        </tr>
    </tbody>
</table><br>";
            }

            $granTotalFmt     = number_format($granTotal, 4);
            $totalCantidadFmt = number_format($totalCantidad, 2);

            $tabla .= "
<table width='100%' style='margin-top:10px;'>
    <tbody>
        <tr>
            <td style='font-weight:bold; font-size:14px; text-align:right;
                        border-top:2px solid #000; padding-top:6px;'>
                TOTAL CANTIDAD:&nbsp;&nbsp;
            </td>
            <td style='font-weight:bold; font-size:14px; width:12%;
                        border-top:2px solid #000; padding-top:6px;'>
                $totalCantidadFmt
            </td>
            <td style='font-weight:bold; font-size:14px; text-align:right;
                        border-top:2px solid #000; padding-top:6px;'>
                TOTAL GENERAL:&nbsp;&nbsp;
            </td>
            <td style='font-weight:bold; font-size:14px; width:18%;
                        border-top:2px solid #000; padding-top:6px;'>
                $ $granTotalFmt
            </td>
        </tr>
    </tbody>
</table>";
        }

        $stylesheet = file_get_contents('css/cssregistro.css');
        $mpdf->WriteHTML($stylesheet, 1);
        $mpdf->setFooter("Página: " . '{PAGENO}' . "/" . '{nb}');
        $mpdf->WriteHTML($tabla, 2);
        $mpdf->Output();
    }




    public function vistaReporteProyectoCodigos()
    {
        $proyectos = TipoProyecto::orderBy('nombre', 'ASC')->get();

        return view('backend.admin.repuestos.reporte.vistareporteporcodigos', compact('proyectos'));
    }


    public function reportePDFProyectoCodigos($idproy, $desde, $hasta, $descripcion = '')
    {
        $start = Carbon::parse($desde)->startOfDay();
        $end = Carbon::parse($hasta)->endOfDay();

        $desdeFormat = Carbon::parse($desde)->format('d/m/Y');
        $hastaFormat = Carbon::parse($hasta)->format('d/m/Y');
        $descripcion = urldecode($descripcion);

        $proyecto = DB::table('tipoproyecto')->where('id', $idproy)->first();
        $informacionGeneral = InformacionGeneral::where('id', 1)->first();

        $rows = DB::select("
        WITH entradas AS (
            SELECT
                ed.id               AS id_entradadetalle,
                ed.id_material,
                ed.precio,
                ed.codigo           AS codigo_detalle,
                ed.nombre           AS nombre_copia,
                ed.cantidad_inicial AS cantidad_entrada,
                e.fecha             AS fecha_entrada
            FROM entradas_detalle ed
            JOIN entradas e ON e.id = ed.id_entradas
            WHERE e.id_tipoproyecto = ?
        ),
        salidas AS (
            SELECT
                sd.id_entrada_detalle,
                sd.cantidad_salida,
                s.fecha AS fecha_salida
            FROM salidas_detalle sd
            JOIN salidas s ON s.id = sd.id_salida
            WHERE s.id_tipoproyecto = ?
        ),
        in_before AS (
            SELECT id_entradadetalle, SUM(cantidad_entrada) AS qty_in_before
            FROM entradas WHERE fecha_entrada < ?
            GROUP BY id_entradadetalle
        ),
        out_before AS (
            SELECT id_entrada_detalle, SUM(cantidad_salida) AS qty_out_before
            FROM salidas WHERE fecha_salida < ?
            GROUP BY id_entrada_detalle
        ),
        in_period AS (
            SELECT id_entradadetalle, SUM(cantidad_entrada) AS qty_in_period
            FROM entradas WHERE fecha_entrada >= ? AND fecha_entrada <= ?
            GROUP BY id_entradadetalle
        ),
        out_period AS (
            SELECT id_entrada_detalle, SUM(cantidad_salida) AS qty_out_period
            FROM salidas WHERE fecha_salida >= ? AND fecha_salida <= ?
            GROUP BY id_entrada_detalle
        )
        SELECT
            en.id_entradadetalle,
            en.id_material,
            obj.codigo                          AS codigo_obj,
            COALESCE(m.nombre, en.nombre_copia) AS descripcion,
            um.nombre                           AS unidad_medida,
            en.precio,
            COALESCE(ib.qty_in_before, 0) - COALESCE(ob.qty_out_before, 0)  AS saldo_inicial_cant,
            COALESCE(ip.qty_in_period,  0)                                   AS entradas_cant,
            COALESCE(op.qty_out_period, 0)                                   AS salidas_cant,
            (COALESCE(ib.qty_in_before, 0) - COALESCE(ob.qty_out_before, 0)
             + COALESCE(ip.qty_in_period, 0)
             - COALESCE(op.qty_out_period, 0))                               AS saldo_final_cant,
            ((COALESCE(ib.qty_in_before, 0) - COALESCE(ob.qty_out_before, 0)) * en.precio) AS saldo_inicial_money,
            (COALESCE(ip.qty_in_period,  0) * en.precio)                                   AS entradas_money,
            (COALESCE(op.qty_out_period, 0) * en.precio)                                   AS salidas_money,
            ((COALESCE(ib.qty_in_before, 0) - COALESCE(ob.qty_out_before, 0)
              + COALESCE(ip.qty_in_period, 0)
              - COALESCE(op.qty_out_period, 0)) * en.precio)                               AS saldo_final_money
        FROM entradas en
        LEFT JOIN materiales m          ON m.id  = en.id_material
        LEFT JOIN objeto_especifico obj ON obj.id = m.id_objespecifico
        LEFT JOIN unidadmedida um       ON um.id  = m.id_medida
        LEFT JOIN in_before  ib ON ib.id_entradadetalle  = en.id_entradadetalle
        LEFT JOIN out_before ob ON ob.id_entrada_detalle = en.id_entradadetalle
        LEFT JOIN in_period  ip ON ip.id_entradadetalle  = en.id_entradadetalle
        LEFT JOIN out_period op ON op.id_entrada_detalle = en.id_entradadetalle
        ORDER BY obj.codigo, descripcion, en.precio
        ", [
            $idproy, $idproy,
            $start->toDateString(), $start->toDateString(),
            $start->toDateString(), $end->toDateString(),
            $start->toDateString(), $end->toDateString(),
        ]);

        $totales = [
            'inicial_cant' => 0, 'entradas_cant' => 0,
            'salidas_cant' => 0, 'final_cant' => 0,
            'inicial_money' => 0.0, 'entradas_money' => 0.0,
            'salidas_money' => 0.0, 'final_money' => 0.0,
        ];

        $sumPorCodigo = [];

        foreach ($rows as $r) {
            $totales['inicial_cant'] += (int)($r->saldo_inicial_cant ?? 0);
            $totales['entradas_cant'] += (int)($r->entradas_cant ?? 0);
            $totales['salidas_cant'] += (int)($r->salidas_cant ?? 0);
            $totales['final_cant'] += (int)($r->saldo_final_cant ?? 0);
            $totales['inicial_money'] += (float)($r->saldo_inicial_money ?? 0);
            $totales['entradas_money'] += (float)($r->entradas_money ?? 0);
            $totales['salidas_money'] += (float)($r->salidas_money ?? 0);
            $totales['final_money'] += (float)($r->saldo_final_money ?? 0);

            $codigo = $r->codigo_obj ?? 'SIN-CODIGO';

            if (!isset($sumPorCodigo[$codigo])) {
                $sumPorCodigo[$codigo] = [
                    'codigo' => $codigo,
                    'inicial_cant' => 0, 'entradas_cant' => 0,
                    'salidas_cant' => 0, 'final_cant' => 0,
                    'inicial_money' => 0.0, 'entradas_money' => 0.0,
                    'salidas_money' => 0.0, 'final_money' => 0.0,
                ];
            }

            $sumPorCodigo[$codigo]['inicial_cant'] += (int)($r->saldo_inicial_cant ?? 0);
            $sumPorCodigo[$codigo]['entradas_cant'] += (int)($r->entradas_cant ?? 0);
            $sumPorCodigo[$codigo]['salidas_cant'] += (int)($r->salidas_cant ?? 0);
            $sumPorCodigo[$codigo]['final_cant'] += (int)($r->saldo_final_cant ?? 0);
            $sumPorCodigo[$codigo]['inicial_money'] += (float)($r->saldo_inicial_money ?? 0);
            $sumPorCodigo[$codigo]['entradas_money'] += (float)($r->entradas_money ?? 0);
            $sumPorCodigo[$codigo]['salidas_money'] += (float)($r->salidas_money ?? 0);
            $sumPorCodigo[$codigo]['final_money'] += (float)($r->saldo_final_money ?? 0);
        }

        $mpdf = new \Mpdf\Mpdf([
            'tempDir' => sys_get_temp_dir(),
            'format' => 'LETTER',
            'orientation' => 'L',
        ]);

        $mpdf->SetTitle('Reporte por Proyecto');
        $mpdf->showImageErrors = false;

        $logoalcaldia = 'images/gobiernologo.jpg';
        $nombreProyecto = $proyecto->nombre ?? 'Proyecto';

        // ── Encabezado ────────────────────────────────────────────────────
        $encabezado = "
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
            REPORTE DE SALDOS DE MATERIALES SOBRANTES
        </td>
        <td style='width:25%; border:0.8px solid #000; padding:0; vertical-align:top;'>
            <table width='100%' style='font-size:10px;'>
                <tr>
                    <td width='40%' style='border-right:0.8px solid #000; border-bottom:0.8px solid #000; padding:4px 6px;'><strong>Código:</strong></td>
                    <td width='60%' style='border-bottom:0.8px solid #000; padding:4px 6px; text-align:center;'>GEAD-002-REPO</td>
                </tr>
                <tr>
                    <td style='border-right:0.8px solid #000; border-bottom:0.8px solid #000; padding:4px 6px;'><strong>Versión:</strong></td>
                    <td style='border-bottom:0.8px solid #000; padding:4px 6px; text-align:center;'>000</td>
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
<strong>PROYECTO DE ORIGEN DE LOS MATERIALES:</strong> {$nombreProyecto}<br>
<strong>PERIODO: {$desdeFormat} AL {$hastaFormat}</strong><br><br>
";


        if (file_exists(public_path('css/cssbodega.css'))) {
            $stylesheet = file_get_contents(public_path('css/cssbodega.css'));
            $mpdf->WriteHTML($stylesheet, \Mpdf\HTMLParserMode::HEADER_CSS);
        }

        // ── Tabla detalle ─────────────────────────────────────────────────
        $html = $encabezado;
        $html .= "
<table width='100%' border='1' cellspacing='0' cellpadding='4'
       style='border-collapse:collapse; font-size:10px; margin-top:8px;'>
    <thead style='background:#f2f4f8;'>
        <tr>
            <th style='width:3%;'>#</th>
            <th style='width:8%; text-align: center'>COD PRESUP.</th>
             <th style='width:8%; text-align: center'>U. MEDIDA</th>
            <th style='text-align: center'>DESCRIPCIÓN</th>
            <th style='text-align:center; width:7%;'>PRECIO UNITARIO</th>
            <th style='text-align:center; width:8%;'>EXISTENCIA INICIAL</th>
            <th style='text-align:center; width:8%;'>\$ SALDO INICIAL</th>
            <th style='text-align:center; width:8%;'>ENTRADAS</th>
            <th style='text-align:center; width:9%;'>\$ ENTRADAS</th>
            <th style='text-align:center; width:8%;'>SALIDAS TOTALES</th>
            <th style='text-align:center; width:8%;'>SALDO POR SALIDAS</th>
            <th style='text-align:center; width:8%;'>EXISTENCIA ACTUAL</th>
            <th style='text-align:center; width:8%;'>SALDO EXISTENCIA ACTUAL</th>
        </tr>
    </thead>
    <tbody>
";

        $i = 1;
        foreach ($rows as $r) {
            $tieneCodigo = !empty($r->codigo_obj);
            $codigoHtml = $tieneCodigo
                ? e($r->codigo_obj)
                : "<span style='color:#dc3545; font-weight:bold;'>S/C</span>";

            $html .= "
<tr>
    <td>{$i}</td>
    <td style='text-align:center;'>{$codigoHtml}</td>
    <td style='text-align:center;'>" . e($r->unidad_medida ?? 'N/A') . "</td>
    <td>" . e($r->descripcion) . "</td>
    <td style='text-align:right;'>$" . number_format($r->precio ?? 0, 4) . "</td>
    <td style='text-align:right;'>" . number_format($r->saldo_inicial_cant ?? 0) . "</td>
    <td style='text-align:right;'>$" . number_format($r->saldo_inicial_money ?? 0, 2) . "</td>
    <td style='text-align:right;'>" . number_format($r->entradas_cant ?? 0) . "</td>
    <td style='text-align:right;'>$" . number_format($r->entradas_money ?? 0, 2) . "</td>
    <td style='text-align:right;'>" . number_format($r->salidas_cant ?? 0) . "</td>
    <td style='text-align:right;'>$" . number_format($r->salidas_money ?? 0, 2) . "</td>
    <td style='text-align:right;'>" . number_format($r->saldo_final_cant ?? 0) . "</td>
    <td style='text-align:right;'>$" . number_format($r->saldo_final_money ?? 0, 2) . "</td>
</tr>
";
            $i++;
        }

        if (!$rows) {
            $html .= "<tr><td colspan='12' style='text-align:center; color:#888;'>Sin registros en el rango seleccionado.</td></tr>";
        }

        $html .= "
    </tbody>
    <tfoot>
        <tr style='font-weight:bold; background:#f9fafb;'>
            <td colspan='5' style='text-align:right;'>TOTALES:</td>
            <td style='text-align:right;'>" . number_format($totales['inicial_cant']) . "</td>
            <td style='text-align:right;'>$" . number_format($totales['inicial_money'], 2) . "</td>
            <td style='text-align:right;'>" . number_format($totales['entradas_cant']) . "</td>
            <td style='text-align:right;'>$" . number_format($totales['entradas_money'], 2) . "</td>
            <td style='text-align:right;'>" . number_format($totales['salidas_cant']) . "</td>
            <td style='text-align:right;'>$" . number_format($totales['salidas_money'], 2) . "</td>
            <td style='text-align:right;'>" . number_format($totales['final_cant']) . "</td>
            <td style='text-align:right;'>$" . number_format($totales['final_money'], 2) . "</td>
        </tr>
    </tfoot>
</table>
";

        // ── Resumen del período ───────────────────────────────────────────
        $html .= "
<br>
<table width='55%' border='1' cellspacing='0' cellpadding='6'
       style='border-collapse:collapse; font-size:12px;'>
    <tr style='background:#eef3ff; font-weight:bold; text-align:center;'>
        <td colspan='3'>Resumen del período {$desdeFormat} - {$hastaFormat}</td>
    </tr>
    <tr style='font-weight:bold; background:#f9fafb;'>
        <td></td>
        <td style='text-align:right;'>Cantidad</td>
        <td style='text-align:right;'>Dinero ($)</td>
    </tr>
    <tr>
        <td>Saldo inicial</td>
        <td style='text-align:right;'>" . number_format($totales['inicial_cant']) . "</td>
        <td style='text-align:right;'>$" . number_format($totales['inicial_money'], 2) . "</td>
    </tr>
    <tr>
        <td>Ingresó (Entradas del período)</td>
        <td style='text-align:right;'>" . number_format($totales['entradas_cant']) . "</td>
        <td style='text-align:right;'>$" . number_format($totales['entradas_money'], 2) . "</td>
    </tr>
    <tr>
        <td>Salió (Salidas del período)</td>
        <td style='text-align:right;'>" . number_format($totales['salidas_cant']) . "</td>
        <td style='text-align:right;'>$" . number_format($totales['salidas_money'], 2) . "</td>
    </tr>
    <tr style='font-weight:bold;'>
        <td>Disponible al cierre (Saldo final)</td>
        <td style='text-align:right;'>" . number_format($totales['final_cant']) . "</td>
        <td style='text-align:right;'>$" . number_format($totales['final_money'], 2) . "</td>
    </tr>
</table>
";

        // ── Resumen por código objeto específico ──────────────────────────
        if (!empty($sumPorCodigo)) {

            $totalSaldoFinalCodigos = 0;

            $html .= "
<br><br>
<table width='100%' border='1' cellspacing='0' cellpadding='4'
       style='border-collapse:collapse; font-size:11px;'>
    <thead style='background:#f2f4f8;'>
        <tr>
            <th style='width:4%;'>#</th>
            <th style='width:10%;'>COD. PRESUP.</th>
            <th style='text-align:right; width:6%;'>INICIAL</th>
            <th style='text-align:right; width:10%;'>\$ INICIAL</th>
            <th style='text-align:right; width:6%;'>ENTRADAS</th>
            <th style='text-align:right; width:10%;'>\$ ENTRADAS</th>
            <th style='text-align:right; width:6%;'>SALIDAS</th>
            <th style='text-align:right; width:10%;'>\$ SALIDAS</th>
            <th style='text-align:right; width:6%;'>SALDO</th>
            <th style='text-align:right; width:10%;'>\$ SALDO</th>
        </tr>
    </thead>
    <tbody>
";

            $j = 1;
            foreach ($sumPorCodigo as $s) {

                $totalSaldoFinalCodigos += (float)$s['final_money'];

                $html .= "
<tr>
    <td>{$j}</td>
    <td>" . e($s['codigo']) . "</td>
    <td style='text-align:right;'>" . number_format($s['inicial_cant']) . "</td>
    <td style='text-align:right;'>$" . number_format($s['inicial_money'], 2) . "</td>
    <td style='text-align:right;'>" . number_format($s['entradas_cant']) . "</td>
    <td style='text-align:right;'>$" . number_format($s['entradas_money'], 2) . "</td>
    <td style='text-align:right;'>" . number_format($s['salidas_cant']) . "</td>
    <td style='text-align:right;'>$" . number_format($s['salidas_money'], 2) . "</td>
    <td style='text-align:right;'>" . number_format($s['final_cant']) . "</td>
    <td style='text-align:right;'>$" . number_format($s['final_money'], 2) . "</td>
</tr>
";
                $j++;
            }

            $html .= "
    <tr style='font-weight:bold; background:#f9fafb;'>
        <td colspan='9' style='text-align:right;'>TOTAL</td>
        <td style='text-align:right;'>$" . number_format($totalSaldoFinalCodigos, 2) . "</td>
    </tr>
    </tbody>
</table>
";
        }


        // ── Observaciones ─────────────────────────────────────────────────
        $html .= "
<br>
<table width='100%' border='1' cellspacing='0' cellpadding='6'
       style='border-collapse:collapse; font-size:11px; margin-top:{$informacionGeneral->px_observaciones}px;'>
    <tr style='background:#f2f4f8;'>
        <td style='font-weight:bold; font-size: 14px'>Observaciones: </td>
    </tr>
    <tr>
        <td style='height:50px; font-size: 14px; vertical-align:top;'>$descripcion</td>
    </tr>
</table>
";


        // ── Firmas ────────────────────────────────────────────────────────
        $html .= "
<table width='100%' style='border-collapse:collapse; margin-top:{$informacionGeneral->px_firmas}px; font-size:11px;'>
    <tr>
        <td style='width:50%; padding-right:40px; vertical-align:top;'>
            <strong>ELABORADO POR:</strong><br><br><br>
            <table width='100%' style='border-collapse:collapse;'>
                <tr>
                    <td style='width:20%;'>FIRMA:</td>
                    <td style='border-bottom:0.8px solid #000; width:75%;'>&nbsp;</td>
                </tr>
                <tr><td colspan='2' style='height:20px;'></td></tr>
                <tr>
                    <td>NOMBRE:</td>
                    <td style='border-bottom:0.8px solid #000;'>&nbsp;</td>
                </tr>
                <tr><td colspan='2' style='height:20px;'></td></tr>
                <tr>
                    <td>CARGO:</td>
                    <td style='border-bottom:0.8px solid #000;'>&nbsp;</td>
                </tr>
                <tr><td colspan='2' style='height:20px;'></td></tr>
                <tr>
                    <td colspan='2' style='text-align:center;'>[ENCARGADO DE BODEGA DE PROYECTO O RESPONSABLE ASIGNADO]</td>
                </tr>
            </table>
        </td>
       <td style='width:50%; padding-left:40px; vertical-align:top;'>
            <strong>REVISADO POR:</strong><br><br><br>
            <table width='100%' style='border-collapse:collapse;'>
                <tr>
                    <td style='width:20%;'>FIRMA:</td>
                    <td style='border-bottom:0.8px solid #000; width:85%;'>&nbsp;</td>
                </tr>
                <tr><td colspan='2' style='height:20px;'></td></tr>
                <tr>
                    <td>NOMBRE:</td>
                    <td style='border-bottom:0.8px solid #000;'>&nbsp;</td>
                </tr>
                <tr><td colspan='2' style='height:20px;'></td></tr>
                <tr>
                    <td>CARGO:</td>
                    <td style='border-bottom:0.8px solid #000;'>&nbsp;</td>
                </tr>
                <tr><td colspan='2' style='height:20px;'></td></tr>
                <tr>
                    <td colspan='2' style='text-align:center;'>JEFE INMEDIATO</td>
                </tr>
            </table>
        </td>
    </tr>
</table>
";

        $mpdf->setFooter("Página {PAGENO} de {nb}");
        $mpdf->WriteHTML($html, \Mpdf\HTMLParserMode::HTML_BODY);
        $mpdf->Output();
    }




    public function reporteDestinoSobrantes($idtrans, $tipo, Request $request)
    {
        $tipo = strtolower(trim($tipo));

        $infoProyecto  = Tipoproyecto::find($idtrans);
        $fechaGenerado = date("d-m-Y");
        $logoalcaldia  = 'images/logo.png';

        $desde = $request->query('desde');
        $hasta = $request->query('hasta');


        // Validar si fecha desde es menor a fecha de cierre
        if ($desde && $infoProyecto->fecha_cierre) {

            $fechaDesde = Carbon::parse($desde);
            $fechaCierre = Carbon::parse($infoProyecto->fecha_cierre);
            $fechaCierreFormat = Carbon::parse($infoProyecto->fecha_cierre)->format('d-m-Y');

            if ($fechaDesde->lt($fechaCierre)) {
                return response()->json([
                    'mensaje' => 'La fecha desde no puede ser menor a la fecha de cierre del proyecto: ' . $fechaCierreFormat
                ]);
            }
        }

        $transferencia = Transferencia::where('id_tipoproyecto_origen', $idtrans)
            ->orderBy('id', 'desc')
            ->first();

        if (!$transferencia) {
            $mpdf = new \Mpdf\Mpdf(['tempDir' => sys_get_temp_dir(), 'format' => 'LETTER-P']);
            $mpdf->WriteHTML("<p style='font-family:Arial; font-size:14px; color:red;'>
Este proyecto no tiene registro de cierre generado.</p>", 2);
            $mpdf->Output();
            return;
        }

        $fechaCierre = date("d-m-Y", strtotime($transferencia->fecha));

        $periodoTexto = ($desde && $hasta)
            ? date('d/m/Y', strtotime($desde)) . ' AL ' . date('d/m/Y', strtotime($hasta))
            : 'Todas las fechas';

        $tituloTipo = $tipo === 'proyecto'
            ? "REPORTE DE MATERIALES SOBRANTES<br>TRANSFERIDOS A PROYECTO DE INVERSIÓN PÚBLICA"
            : "REPORTE DE SALIDAS DE MATERIALES SOBRANTES<br>PARA MANTENIMIENTO DE INSTALACIONES MUNICIPALES";

        $codigoPDF = 'GEAD-001-REPO';

        $colorTipo = '#000000';
        $textoTipo = $tipo === 'proyecto'
            ? 'TRANSFERENCIA A PROYECTO DE INVERSIÓN PÚBLICA'
            : 'SALIDA GENERAL — MANTENIMIENTO DE INSTALACIONES MUNICIPALES';

        // ── Obtener los id_salida que corresponden a despachos de sobrantes ───
        $tipoSalidaBuscado = $tipo === 'proyecto' ? 'proyecto' : 'general';

        $idsSalidaValidos = Transferencia::where('id_tipoproyecto_origen', $idtrans)
            ->where('tipo_salida', $tipoSalidaBuscado)
            ->whereNotNull('id_salida')
            ->pluck('id_salida')
            ->toArray();

        if (empty($idsSalidaValidos)) {
            $mpdf = new \Mpdf\Mpdf(['tempDir' => sys_get_temp_dir(), 'format' => 'LETTER-P']);
            $mpdf->WriteHTML("<p style='font-family:Arial; font-size:14px; color:#888; padding:20px;'>
No hay registros para este proyecto en el rango de fechas seleccionado.</p>", 2);
            $mpdf->Output();
            return;
        }

        // ── Salidas reales, limitadas a los despachos de sobrantes ────────────
        $salidasQuery = SalidasDetalle::whereHas('salida', function ($q) use ($idsSalidaValidos, $idtrans, $desde, $hasta) {
            $q->whereIn('id', $idsSalidaValidos)
                ->where('id_tipoproyecto', $idtrans);

            if ($desde) $q->whereDate('fecha', '>=', $desde);
            if ($hasta) $q->whereDate('fecha', '<=', $hasta);
        })
            ->with([
                'salida',
                'entradaDetalle.material.unidadMedida',
                'entradaDetalle.material.objetoEspecifico',
            ])
            ->get();

        // ── Agrupar por PROYECTO DESTINO (o "general") ─────────────────────────
        // Para tipo 'proyecto': se agrupa por id_tipoproyecto_transferencia.
        // Para tipo 'general':  todo cae en una sola agrupación con id 0.
        //
        // Dentro de cada agrupación, los materiales con el mismo id_material y
        // mismo precio unitario se fusionan acumulando la cantidad.
        $porDestino = [];

        foreach ($salidasQuery as $sd) {

            if ($sd->cantidad_salida <= 0) continue;

            $entradaDet = $sd->entradaDetalle;
            $material   = $entradaDet?->material;

            // Código del objeto específico
            $codigoObjEsp = '—';
            if ($material) {
                if ($material->relationLoaded('objetoEspecifico') && $material->objetoEspecifico) {
                    $codigoObjEsp = $material->objetoEspecifico->codigo ?? '—';
                } elseif (!empty($material->id_objespecifico)) {
                    $objEsp = DB::table('objeto_especifico')
                        ->where('id', $material->id_objespecifico)
                        ->first();
                    $codigoObjEsp = $objEsp->codigo ?? '—';
                }
            }

            // Determinar la clave de agrupación según el tipo
            if ($tipo === 'proyecto') {
                $idDestino = (int) ($sd->salida->id_tipoproyecto_transferencia ?? 0);
            } else {
                $idDestino = 0;   // todas las salidas generales en un solo grupo
            }

            // Cabecera del grupo (una sola vez)
            if (!isset($porDestino[$idDestino])) {
                if ($tipo === 'proyecto') {
                    $proyectoDestNombre = $idDestino
                        ? (Tipoproyecto::find($idDestino)?->nombre ?? '—')
                        : '—';
                } else {
                    $proyectoDestNombre = 'MANTENIMIENTO DE INSTALACIONES MUNICIPALES';
                }

                $porDestino[$idDestino] = [
                    'proyecto_destino' => $proyectoDestNombre,
                    'materiales'       => [],
                ];
            }

            $precio     = (float) ($entradaDet?->precio ?? 0);
            $cantidad   = (float) $sd->cantidad_salida;
            $nombre     = $material?->nombre ?? $entradaDet?->nombre ?? '—';
            $idMaterial = $material?->id ?? ('X' . md5($nombre));

            // Clave de unión: mismo material + mismo precio unitario.
            $clave = $idMaterial . '|' . number_format($precio, 4, '.', '');

            if (!isset($porDestino[$idDestino]['materiales'][$clave])) {
                $porDestino[$idDestino]['materiales'][$clave] = [
                    'nombre'          => $nombre,
                    'medida'          => $material?->unidadMedida?->nombre ?? '—',
                    'codigo'          => $codigoObjEsp,
                    'cant_despachada' => 0,
                    'precio'          => $precio,
                ];
            }

            // Acumular la cantidad en la fila unificada
            $porDestino[$idDestino]['materiales'][$clave]['cant_despachada'] += $cantidad;
        }

        if (empty($porDestino)) {
            $mpdf = new \Mpdf\Mpdf(['tempDir' => sys_get_temp_dir(), 'format' => 'LETTER-P']);
            $mpdf->WriteHTML("<p style='font-family:Arial; font-size:14px; color:#888; padding:20px;'>
No hay registros para este proyecto en el rango de fechas seleccionado.</p>", 2);
            $mpdf->Output();
            return;
        }

        $granTotal = 0;

        // Acumulador de totales por código objeto específico (cruza todos los destinos)
        $totalPorCodigo = [];

        $mpdf = new \Mpdf\Mpdf(['tempDir' => sys_get_temp_dir(), 'format' => 'LETTER-P']);
        $mpdf->SetTitle('Destino de Sobrantes');
        $mpdf->showImageErrors = false;

        // ── Encabezado institucional ──────────────────────────────────────────
        $tabla = "
<table width='100%' style='border-collapse:collapse; font-family:Arial, sans-serif;'>
<tr>
    <td style='width:25%; border:0.8px solid #000; padding:6px 8px;'>
        <table width='100%'>
            <tr>
                <td style='width:30%; text-align:left;'>
                    <img src='{$logoalcaldia}' style='height:38px'>
                </td>
                <td style='width:70%; text-align:left; color:#104e8c;
                            font-size:13px; font-weight:bold; line-height:1.3;'>
                    SANTA ANA NORTE<br>EL SALVADOR
                </td>
            </tr>
        </table>
    </td>
    <td style='width:50%; border-top:0.8px solid #000; border-bottom:0.8px solid #000;
               padding:6px 8px; text-align:center; font-size:14px; font-weight:bold;'>
        {$tituloTipo}
    </td>
    <td style='width:25%; border:0.8px solid #000; padding:0; vertical-align:top;'>
        <table width='100%' style='font-size:10px;'>
            <tr>
                <td width='40%' style='border-right:0.8px solid #000;
                                       border-bottom:0.8px solid #000; padding:4px 6px;'>
                    <strong>Código:</strong>
                </td>
                <td width='60%' style='border-bottom:0.8px solid #000;
                                       padding:4px 6px; text-align:center;'>
                    {$codigoPDF}
                </td>
            </tr>
            <tr>
                <td style='border-right:0.8px solid #000;
                           border-bottom:0.8px solid #000; padding:4px 6px;'>
                    <strong>Versión:</strong>
                </td>
                <td style='border-bottom:0.8px solid #000;
                           padding:4px 6px; text-align:center;'>000</td>
            </tr>
            <tr>
                <td style='border-right:0.8px solid #000; padding:4px 6px;'>
                    <strong>Fecha de vigencia:</strong>
                </td>
                <td style='padding:4px 6px; text-align:center;'>22/05/2026</td>
            </tr>
        </table>
    </td>
</tr>
</table><br>";

        // ── Datos generales ───────────────────────────────────────────────────
        $tabla .= "
<table width='100%' style='margin-bottom:8px; border-collapse:collapse;'>
<tr>
    <td style='font-size:13px; padding:4px 0;'>
        <span style='font-weight:bold;'>Proyecto de origen:</span> {$infoProyecto->nombre}
    </td>
    <td style='font-size:13px; padding:4px 0;'>
        <span style='font-weight:bold;'>Fecha de cierre:</span> {$fechaCierre}
    </td>
</tr>

<tr>
    <td style='font-size:13px; padding:4px 0;'>
        <span style='font-weight:bold;'>Período:</span> {$periodoTexto}
    </td>
    <td style='font-size:13px; padding:4px 0;'>
        <span style='font-weight:bold;'>Generado:</span> {$fechaGenerado}
    </td>
</tr>
</table>";

        $thStyle = "font-weight:bold; font-size:11px; border:0.8px solid #000;
padding:5px 4px; background:#d9e1f2; text-align:center;";
        $tdStyle = "font-size:11px; border:0.8px solid #000; padding:4px;";
        $tdC     = $tdStyle . " text-align:center;";
        $tdR     = $tdStyle . " text-align:right;";

        // Función auxiliar para imprimir la fila de subtotal por objeto específico
        $filaSubtotalCodigo = function ($codigo, $cantidad, $monto) {
            return "
    <tr>
        <td colspan='3' style='font-weight:bold; font-size:11px; text-align:center;
                                border:0.8px solid #000; padding:5px 4px; background:#e8eef7;'>
            SUBTOTAL [" . e($codigo) . "]
        </td>
        <td style='font-weight:bold; font-size:11px; text-align:center;
                    border:0.8px solid #000; padding:5px 4px; background:#e8eef7;'>
            " . number_format($cantidad, 2) . "
        </td>
        <td style='border:0.8px solid #000; padding:5px 4px; background:#e8eef7;'></td>
        <td style='font-weight:bold; font-size:11px; text-align:right;
                    border:0.8px solid #000; padding:5px 4px; background:#e8eef7;'>
            $ " . number_format($monto, 4) . "
        </td>
    </tr>";
        };

        // ── Una sección por cada destino unificado ────────────────────────────
        foreach ($porDestino as $idDestino => $grupo) {

            $subtotalDestino = 0;

            // Ordenar los materiales por código objeto específico para agrupar
            $materialesOrdenados = $grupo['materiales'];
            uasort($materialesOrdenados, function ($a, $b) {
                return strcmp($a['codigo'], $b['codigo']);
            });

            // Cabecera del bloque: solo el proyecto destino (tipo proyecto)
            // o "MANTENIMIENTO DE INSTALACIONES MUNICIPALES" (tipo general)
            $etiquetaCabecera = $tipo === 'proyecto' ? 'Proyecto destino:' : 'Destino:';

            $tabla .= "
<table width='100%' style='border-collapse:collapse; margin-bottom:4px; margin-top:10px;'>
<tr>
    <td style='font-size:12px; padding:4px 6px;
               border:0.8px solid #000; background:#f2f4f8;'>
        <span style='font-weight:bold;'>{$etiquetaCabecera}</span>
        {$grupo['proyecto_destino']}
    </td>
</tr>
</table>";

            $tabla .= "
<table width='100%' style='border-collapse:collapse; margin-bottom:14px;'>
<thead>
    <tr>
        <th style='{$thStyle} width:10%;'>Obj.<br>Espec.</th>
        <th style='{$thStyle} width:45%;'>Material</th>
        <th style='{$thStyle} width:10%;'>Medida</th>
        <th style='{$thStyle} width:11%;'>Cant.<br>Despachada</th>
        <th style='{$thStyle} width:12%;'>Precio<br>Unit.</th>
        <th style='{$thStyle} width:12%;'>Total ($)</th>
    </tr>
</thead>
<tbody>";

            // Variables para el subtotal por objeto específico dentro del destino
            $codigoActual = null;
            $cantGrupo    = 0;
            $montoGrupo   = 0;

            foreach ($materialesOrdenados as $mat) {

                $totalLinea       = $mat['cant_despachada'] * $mat['precio'];
                $subtotalDestino += $totalLinea;
                $granTotal       += $totalLinea;

                // Acumular en el resumen global por código objeto específico
                $codObj = $mat['codigo'];
                if (!isset($totalPorCodigo[$codObj])) {
                    $totalPorCodigo[$codObj] = [
                        'codigo'   => $codObj,
                        'cantidad' => 0,
                        'total'    => 0,
                    ];
                }
                $totalPorCodigo[$codObj]['cantidad'] += $mat['cant_despachada'];
                $totalPorCodigo[$codObj]['total']    += $totalLinea;

                // Si cambió el código, cerrar el grupo anterior
                if ($codigoActual !== null && $codObj !== $codigoActual) {
                    $tabla .= $filaSubtotalCodigo($codigoActual, $cantGrupo, $montoGrupo);
                    $cantGrupo  = 0;
                    $montoGrupo = 0;
                }

                $codigoActual = $codObj;
                $cantGrupo   += $mat['cant_despachada'];
                $montoGrupo  += $totalLinea;

                $precioFmt = '$ ' . number_format($mat['precio'], 4);
                $totalFmt  = '$ ' . number_format($totalLinea, 4);

                $tabla .= "
<tr>
    <td style='{$tdC}'>{$mat['codigo']}</td>
    <td style='{$tdStyle}'>{$mat['nombre']}</td>
    <td style='{$tdC}'>{$mat['medida']}</td>
    <td style='{$tdC} font-weight:bold;'>" . number_format($mat['cant_despachada']) . "</td>
    <td style='{$tdR}'>{$precioFmt}</td>
    <td style='{$tdR}'>{$totalFmt}</td>
</tr>";
            }

            // Cerrar el subtotal del último grupo de este destino
            if ($codigoActual !== null) {
                $tabla .= $filaSubtotalCodigo($codigoActual, $cantGrupo, $montoGrupo);
            }

            $subtotalFmt = '$ ' . number_format($subtotalDestino, 4);

            $etiquetaSubtotal = $tipo === 'proyecto'
                ? 'Subtotal del proyecto destino:'
                : 'Subtotal:';

            $tabla .= "
    <tr>
        <td colspan='5' style='font-weight:bold; font-size:11px; text-align:right;
                                border:0.8px solid #000; padding:5px 4px; background:#d9e1f2;'>
            {$etiquetaSubtotal}
        </td>
        <td style='font-weight:bold; font-size:11px; text-align:right;
                    border:0.8px solid #000; padding:5px 4px; background:#d9e1f2;'>
            {$subtotalFmt}
        </td>
    </tr>
</tbody>
</table>";
        }

        // ── Total general ─────────────────────────────────────────────────────
        $granTotalFmt = '$ ' . number_format($granTotal, 4);

        $tabla .= "
<table width='100%' style='border-collapse:collapse; margin-top:4px;'>
<tr>
    <td style='font-weight:bold; font-size:12px; text-align:right;
               border:0.8px solid #000; padding:5px 4px;'>
        TOTAL GENERAL:
    </td>
    <td style='font-weight:bold; font-size:12px; width:12%;
               border:0.8px solid #000; padding:5px 4px;'>
        {$granTotalFmt}
    </td>
</tr>
</table>";

        // ── Resumen: totales por código objeto específico ─────────────────────
        if (!empty($totalPorCodigo)) {

            ksort($totalPorCodigo);   // ordena por código

            $tabla .= "
<br>
<table width='60%' style='border-collapse:collapse; margin-top:8px;'>
<thead>
    <tr>
        <td colspan='3' style='{$thStyle} font-size:12px;'>
            RESUMEN POR CÓDIGO OBJETO ESPECÍFICO
        </td>
    </tr>
    <tr>
        <th style='{$thStyle} width:30%;'>Obj. Espec.</th>
        <th style='{$thStyle} width:30%;'>Cant. Total</th>
        <th style='{$thStyle} width:40%;'>Total ($)</th>
    </tr>
</thead>
<tbody>";

            foreach ($totalPorCodigo as $tc) {
                $tabla .= "
    <tr>
        <td style='{$tdC}'>" . e($tc['codigo']) . "</td>
        <td style='{$tdC} font-weight:bold;'>" . number_format($tc['cantidad']) . "</td>
        <td style='{$tdR}'>$ " . number_format($tc['total'], 4) . "</td>
    </tr>";
            }

            $tabla .= "
    <tr>
        <td style='font-weight:bold; font-size:11px; text-align:right;
                    border:0.8px solid #000; padding:5px 4px; background:#d9e1f2;'>
            TOTAL:
        </td>
        <td style='border:0.8px solid #000; padding:5px 4px; background:#d9e1f2;'></td>
        <td style='font-weight:bold; font-size:11px; text-align:right;
                    border:0.8px solid #000; padding:5px 4px; background:#d9e1f2;'>
            $ " . number_format($granTotal, 4) . "
        </td>
    </tr>
</tbody>
</table>";
        }

        // ── Firmas ────────────────────────────────────────────────────────────
        $informacionGeneral = InformacionGeneral::where('id', 1)->first();
        $px2 = 60;

        $tabla .= "
<table width='100%' style='border-collapse:collapse; font-family:Arial, sans-serif;'>
<tr>
    <td colspan='2' style='height:{$informacionGeneral->px_firmas}px;'></td>
</tr>
<tr>
    <td style='width:50%; padding-right:40px; vertical-align:top;'>
        <div style='font-weight:bold; font-size:13px; margin-bottom:8px;'>ELABORADO POR:</div>
        <table width='100%' style='border-collapse:collapse;'>
            <tr><td style='height:{$px2}px;'></td></tr>
            <tr>
                <td style='font-size:12px; padding-bottom:8px;'>
                    FIRMA:
                    <table width='90%' style='border-collapse:collapse; display:inline-table;'>
                        <tr><td style='border-bottom:0.8px solid #000; height:16px;'></td></tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td style='font-size:12px; padding-bottom:8px;'>
                    NOMBRE:
                    <table width='85%' style='border-collapse:collapse; display:inline-table;'>
                        <tr><td style='border-bottom:0.8px solid #000; height:16px;'></td></tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td style='font-size:12px; padding-bottom:8px;'>
                    CARGO:
                    <table width='87%' style='border-collapse:collapse; display:inline-table;'>
                        <tr><td style='border-bottom:0.8px solid #000; height:16px;'></td></tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td style='font-size:10px; color:#333; text-align:center;'>
                    " . e($informacionGeneral->d_nombre1 ?? '') . "
                </td>
            </tr>
        </table>
    </td>
    <td style='width:50%; padding-left:40px; vertical-align:top;'>
        <div style='font-weight:bold; font-size:13px; margin-bottom:8px;'>REVISADO POR:</div>
        <table width='100%' style='border-collapse:collapse;'>
            <tr><td style='height:{$px2}px;'></td></tr>
            <tr>
                <td style='font-size:12px; padding-bottom:8px;'>
                    FIRMA:
                    <table width='90%' style='border-collapse:collapse; display:inline-table;'>
                        <tr><td style='border-bottom:0.8px solid #000; height:16px;'></td></tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td style='font-size:12px; padding-bottom:8px;'>
                    NOMBRE:
                    <table width='85%' style='border-collapse:collapse; display:inline-table;'>
                        <tr><td style='border-bottom:0.8px solid #000; height:16px;'></td></tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td style='font-size:12px; padding-bottom:8px;'>
                    CARGO:
                    <table width='87%' style='border-collapse:collapse; display:inline-table;'>
                        <tr><td style='border-bottom:0.8px solid #000; height:16px;'></td></tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td style='font-size:10px; color:#333; text-align:center;'>
                    " . e($informacionGeneral->d_nombre2 ?? '') . "
                </td>
            </tr>
        </table>
    </td>
</tr>
</table>";

        $stylesheet = file_get_contents('css/cssregistro.css');
        $mpdf->WriteHTML($stylesheet, 1);
        $mpdf->setFooter("Página: " . '{PAGENO}' . "/" . '{nb}');
        $mpdf->WriteHTML($tabla, 2);
        $mpdf->Output();
    }





    public function reporteDestinoSobrantesDescriptivo($idtrans, Request $request)
    {

        $infoProyecto  = Tipoproyecto::find($idtrans);
        $fechaGenerado = date("d-m-Y");
        $logoalcaldia  = 'images/logo.png';

        $desde = $request->query('desde');
        $hasta = $request->query('hasta');

        // ── Validar fecha desde vs fecha de cierre (solo si tiene fecha_cierre) ──
        if ($desde && !empty($infoProyecto->fecha_cierre)) {
            $fechaDesde        = Carbon::parse($desde);
            $fechaCierre       = Carbon::parse($infoProyecto->fecha_cierre);
            $fechaCierreFormat = $fechaCierre->format('d-m-Y');

            if ($fechaDesde->lt($fechaCierre)) {
                $mpdf = new \Mpdf\Mpdf(['tempDir' => sys_get_temp_dir(), 'format' => 'LETTER-P']);
                $mpdf->WriteHTML("<p style='font-family:Arial; font-size:14px; color:red; padding:20px;'>
            La fecha desde no puede ser menor a la fecha de cierre del proyecto: {$fechaCierreFormat}
        </p>", 2);
                $mpdf->Output();
                return;
            }
        }

        // ── Verificar que el proyecto tenga cierre ────────────────────────────
        $transferenciaCierre = Transferencia::where('id_tipoproyecto_origen', $idtrans)
            ->orderBy('id', 'desc')
            ->first();

        if (!$transferenciaCierre) {
            $mpdf = new \Mpdf\Mpdf(['tempDir' => sys_get_temp_dir(), 'format' => 'LETTER-P']);
            $mpdf->WriteHTML("<p style='font-family:Arial; font-size:14px; color:red;'>
Este proyecto no tiene registro de cierre generado.</p>", 2);
            $mpdf->Output();
            return;
        }

        $fechaCierre = date("d-m-Y", strtotime($transferenciaCierre->fecha));

        $periodoTexto = ($desde && $hasta)
            ? date('d/m/Y', strtotime($desde)) . ' AL ' . date('d/m/Y', strtotime($hasta))
            : 'Todas las fechas';

        $codigoPDF = 'GEAD-003-REPO';

        // ══════════════════════════════════════════════════════════════════════
        // SECCIÓN A: TRANSFERENCIAS A OTRO PROYECTO
        // ══════════════════════════════════════════════════════════════════════
        $idsSalidaProyecto = Transferencia::where('id_tipoproyecto_origen', $idtrans)
            ->where('tipo_salida', 'proyecto')
            ->whereNotNull('id_salida')
            ->pluck('id_salida')
            ->toArray();

        $salidasProyecto = collect();
        if (!empty($idsSalidaProyecto)) {
            $salidasProyecto = SalidasDetalle::whereHas('salida', function ($q) use ($idsSalidaProyecto, $idtrans, $desde, $hasta) {
                $q->whereIn('id', $idsSalidaProyecto)
                    ->where('id_tipoproyecto', $idtrans);
                if ($desde) $q->whereDate('fecha', '>=', $desde);
                if ($hasta) $q->whereDate('fecha', '<=', $hasta);
            })
                ->with([
                    'salida.proyectoTransferencia',
                    'entradaDetalle.material.unidadMedida',
                    'entradaDetalle.material.objetoEspecifico',
                ])
                ->get();
        }

        // ══════════════════════════════════════════════════════════════════════
        // SECCIÓN B: SALIDAS GENERALES
        // ══════════════════════════════════════════════════════════════════════
        $idsSalidaGeneral = Transferencia::where('id_tipoproyecto_origen', $idtrans)
            ->where('tipo_salida', 'general')
            ->whereNotNull('id_salida')
            ->pluck('id_salida')
            ->toArray();

        $salidasGeneral = collect();
        if (!empty($idsSalidaGeneral)) {
            $salidasGeneral = SalidasDetalle::whereHas('salida', function ($q) use ($idsSalidaGeneral, $idtrans, $desde, $hasta) {
                $q->whereIn('id', $idsSalidaGeneral)
                    ->where('id_tipoproyecto', $idtrans);
                if ($desde) $q->whereDate('fecha', '>=', $desde);
                if ($hasta) $q->whereDate('fecha', '<=', $hasta);
            })
                ->with([
                    'salida',
                    'entradaDetalle.material.unidadMedida',
                    'entradaDetalle.material.objetoEspecifico',
                ])
                ->get();
        }

        // ══════════════════════════════════════════════════════════════════════
        // SECCIÓN C: RESERVAS
        // ══════════════════════════════════════════════════════════════════════
        $reservasQuery = DB::table('reservas')
            ->where('id_tipoproyecto', $idtrans);

        if ($desde) $reservasQuery->whereDate('fecha_reserva', '>=', $desde);
        if ($hasta) $reservasQuery->whereDate('fecha_reserva', '<=', $hasta);

        $reservas = $reservasQuery->orderBy('fecha_reserva', 'asc')->get();

        $idsEntDet = $reservas->pluck('id_entrada_detalle')->unique()->filter()->toArray();
        $detallesReservas = collect();
        if (!empty($idsEntDet)) {
            $detallesReservas = EntradasDetalle::with([
                'material.unidadMedida',
                'material.objetoEspecifico',
            ])->whereIn('id', $idsEntDet)->get()->keyBy('id');
        }

        // ── Si TODO está vacío, mostrar mensaje ───────────────────────────────
        if ($salidasProyecto->isEmpty() && $salidasGeneral->isEmpty() && $reservas->isEmpty()) {
            $mpdf = new \Mpdf\Mpdf(['tempDir' => sys_get_temp_dir(), 'format' => 'LETTER-P']);
            $mpdf->WriteHTML("<p style='font-family:Arial; font-size:14px; color:#888; padding:20px;'>
No hay registros para este proyecto en el rango de fechas seleccionado.</p>", 2);
            $mpdf->Output();
            return;
        }

        $mpdf = new \Mpdf\Mpdf(['tempDir' => sys_get_temp_dir(), 'format' => 'LETTER-P']);
        $mpdf->SetTitle('Descriptivo de Destinos');
        $mpdf->showImageErrors = false;

        // ══════════════════════════════════════════════════════════════════════
        // Encabezado institucional
        // ══════════════════════════════════════════════════════════════════════
        $tabla = "
<table width='100%' style='border-collapse:collapse; font-family:Arial, sans-serif;'>
<tr>
    <td style='width:25%; border:0.8px solid #000; padding:6px 8px;'>
        <table width='100%'>
            <tr>
                <td style='width:30%; text-align:left;'>
                    <img src='{$logoalcaldia}' style='height:38px'>
                </td>
                <td style='width:70%; text-align:left; color:#104e8c;
                            font-size:13px; font-weight:bold; line-height:1.3;'>
                    SANTA ANA NORTE<br>EL SALVADOR
                </td>
            </tr>
        </table>
    </td>
    <td style='width:50%; border-top:0.8px solid #000; border-bottom:0.8px solid #000;
               padding:6px 8px; text-align:center; font-size:14px; font-weight:bold;'>
        REPORTE DESCRIPTIVO DE DESTINO<br>
        DE MATERIALES SOBRANTES
    </td>
    <td style='width:25%; border:0.8px solid #000; padding:0; vertical-align:top;'>
        <table width='100%' style='font-size:10px;'>
            <tr>
                <td width='40%' style='border-right:0.8px solid #000;
                                       border-bottom:0.8px solid #000; padding:4px 6px;'>
                    <strong>Código:</strong>
                </td>
                <td width='60%' style='border-bottom:0.8px solid #000;
                                       padding:4px 6px; text-align:center;'>

                </td>
            </tr>
            <tr>
                <td style='border-right:0.8px solid #000;
                           border-bottom:0.8px solid #000; padding:4px 6px;'>
                    <strong>Versión:</strong>
                </td>
                <td style='border-bottom:0.8px solid #000;
                           padding:4px 6px; text-align:center;'>000</td>
            </tr>
            <tr>
                <td style='border-right:0.8px solid #000; padding:4px 6px;'>
                    <strong>Fecha de vigencia:</strong>
                </td>
                <td style='padding:4px 6px; text-align:center;'></td>
            </tr>
        </table>
    </td>
</tr>
</table><br>";

        // ── Datos generales ───────────────────────────────────────────────────
        $tabla .= "
<table width='100%' style='margin-bottom:8px; border-collapse:collapse;'>
<tr>
    <td style='font-size:13px; padding:4px 0;'>
        <span style='font-weight:bold;'>Proyecto de origen:</span> {$infoProyecto->nombre}
    </td>
    <td style='font-size:13px; padding:4px 0;'>
        <span style='font-weight:bold;'>Fecha de cierre:</span> {$fechaCierre}
    </td>
</tr>
<tr>
    <td style='font-size:13px; padding:4px 0;'>
        <span style='font-weight:bold;'>Período:</span> {$periodoTexto}
    </td>
    <td style='font-size:13px; padding:4px 0;'>
        <span style='font-weight:bold;'>Generado:</span> {$fechaGenerado}
    </td>
</tr>
</table>";

        // ══════════════════════════════════════════════════════════════════════
        // Estilos comunes
        // ══════════════════════════════════════════════════════════════════════
        $thStyle = "font-weight:bold; font-size:11px; border:0.8px solid #000;
padding:5px 4px; background:#d9e1f2; text-align:center;";
        $tdStyle = "font-size:11px; border:0.8px solid #000; padding:4px;";
        $tdC     = $tdStyle . " text-align:center;";
        $tdR     = $tdStyle . " text-align:right;";

        // Acumuladores globales
        $granTotalProy    = 0;
        $granTotalGral    = 0;
        $granTotalReserva = 0;

        // ═══ Consolidado por objeto específico (cruza las 3 secciones) ════════
        // ['codigo' => ['cantidad' => x, 'total' => y, 'nombre_obj' => z]]
        $consolidadoObjEsp = [];

        // Helper para acumular en el consolidado
        $acumularConsolidado = function ($codigo, $nombreObj, $cantidad, $total) use (&$consolidadoObjEsp) {
            $codigo = $codigo ?: '—';
            if (!isset($consolidadoObjEsp[$codigo])) {
                $consolidadoObjEsp[$codigo] = [
                    'codigo'     => $codigo,
                    'nombre_obj' => $nombreObj ?? '',
                    'cantidad'   => 0,
                    'total'      => 0,
                ];
            }
            $consolidadoObjEsp[$codigo]['cantidad'] += $cantidad;
            $consolidadoObjEsp[$codigo]['total']    += $total;
        };

        // Helper para fila de subtotal por objeto específico dentro de cada sección
        $filaSubtotalObjEsp = function ($codigo, $cantidad, $monto, $colspanLabel, $colspanEnd, $bg = '#e8eef7') use ($tdStyle) {
            $cantFmt  = number_format($cantidad, 2, '.', ',');
            $montoFmt = number_format($monto, 4);
            $extraCols = '';
            for ($i = 0; $i < $colspanEnd; $i++) {
                $extraCols .= "<td style='border:0.8px solid #000; background:{$bg};'></td>";
            }
            return "
    <tr>
        <td colspan='{$colspanLabel}' style='font-weight:bold; font-size:11px; text-align:right;
                                background:{$bg}; padding:4px; border:0.8px solid #000;'>
            SUBTOTAL OBJ. ESPEC. [" . e($codigo) . "]
        </td>
        <td style='font-weight:bold; font-size:11px; text-align:center;
                    background:{$bg}; padding:4px; border:0.8px solid #000;'>
            {$cantFmt}
        </td>
        <td style='background:{$bg}; border:0.8px solid #000;'></td>
        <td style='font-weight:bold; font-size:11px; text-align:right;
                    background:{$bg}; padding:4px; border:0.8px solid #000;'>
            \$ {$montoFmt}
        </td>
        {$extraCols}
    </tr>";
        };

        // ══════════════════════════════════════════════════════════════════════
        // SECCIÓN A — TRANSFERENCIAS A OTRO PROYECTO
        // ══════════════════════════════════════════════════════════════════════
        $tabla .= "
<table width='100%' style='border-collapse:collapse; margin-top:14px; margin-bottom:4px;'>
<tr>
    <td style='font-size:13px; padding:6px 8px; font-weight:bold;
               border:0.8px solid #1a5c3a; background:#d4edda; color:#155724;'>
        SECCIÓN A — TRANSFERENCIAS A OTRO PROYECTO
    </td>
</tr>
</table>";

        if ($salidasProyecto->isEmpty()) {
            $tabla .= "<p style='font-size:11px; color:#888; padding:6px 0;'>
            Sin movimientos en este rango.
        </p>";
        } else {
            // Agrupar por proyecto destino
            $porProyecto = [];
            foreach ($salidasProyecto as $sd) {
                if ($sd->cantidad_salida <= 0) continue;

                $idDest = (int) ($sd->salida->id_tipoproyecto_transferencia ?? 0);
                $nombreDest = $sd->salida->proyectoTransferencia->nombre ?? '—';

                if (!isset($porProyecto[$idDest])) {
                    $porProyecto[$idDest] = [
                        'nombre' => $nombreDest,
                        'items'  => [],
                    ];
                }

                $material = $sd->entradaDetalle?->material;
                $precio   = (float) ($sd->entradaDetalle?->precio ?? 0);
                $idMat    = $material?->id ?? ('X' . md5($material?->nombre ?? ''));
                $clave    = $idMat . '|' . number_format($precio, 4, '.', '');

                if (!isset($porProyecto[$idDest]['items'][$clave])) {
                    $porProyecto[$idDest]['items'][$clave] = [
                        'codigo'     => $material?->objetoEspecifico?->codigo ?? '—',
                        'nombre_obj' => $material?->objetoEspecifico?->nombre ?? '',
                        'nombre'     => $material?->nombre ?? $sd->entradaDetalle?->nombre ?? '—',
                        'medida'     => $material?->unidadMedida?->nombre ?? '—',
                        'precio'     => $precio,
                        'cantidad'   => 0,
                        'fecha'      => date('d-m-Y', strtotime($sd->salida->fecha)),
                    ];
                }
                $porProyecto[$idDest]['items'][$clave]['cantidad'] += $sd->cantidad_salida;
            }

            foreach ($porProyecto as $grupo) {

                // Ordenar items por código objeto específico
                uasort($grupo['items'], function ($a, $b) {
                    return strcmp($a['codigo'], $b['codigo']);
                });

                $tabla .= "
<table width='100%' style='border-collapse:collapse; margin-bottom:4px; margin-top:10px;'>
<tr>
    <td style='font-size:12px; padding:4px 6px;
               border:0.8px solid #000; background:#f2f4f8;'>
        <span style='font-weight:bold;'>Proyecto destino:</span> {$grupo['nombre']}
    </td>
</tr>
</table>";

                $tabla .= "
<table width='100%' style='border-collapse:collapse; margin-bottom:10px;'>
<thead>
    <tr>
        <th style='{$thStyle} width:10%;'>Fecha Salida</th>
        <th style='{$thStyle} width:10%;'>Obj. Espec.</th>
        <th style='{$thStyle} width:36%;'>Material</th>
        <th style='{$thStyle} width:10%;'>Medida</th>
        <th style='{$thStyle} width:10%;'>Cantidad</th>
        <th style='{$thStyle} width:12%;'>Precio Unit.</th>
        <th style='{$thStyle} width:12%;'>Total ($)</th>
    </tr>
</thead>
<tbody>";

                $subtotal       = 0;
                $codigoActual   = null;
                $cantGrupo      = 0;
                $montoGrupo     = 0;
                $nombreObjGrupo = '';

                foreach ($grupo['items'] as $it) {
                    $totalLinea     = $it['cantidad'] * $it['precio'];
                    $subtotal       += $totalLinea;
                    $granTotalProy  += $totalLinea;

                    // Acumular en el consolidado global
                    $acumularConsolidado($it['codigo'], $it['nombre_obj'], $it['cantidad'], $totalLinea);

                    // Cierre del grupo anterior si cambió el código
                    if ($codigoActual !== null && $it['codigo'] !== $codigoActual) {
                        $tabla .= $filaSubtotalObjEsp($codigoActual, $cantGrupo, $montoGrupo, 4, 0, '#e8eef7');
                        $cantGrupo  = 0;
                        $montoGrupo = 0;
                    }

                    $codigoActual    = $it['codigo'];
                    $nombreObjGrupo  = $it['nombre_obj'];
                    $cantGrupo      += $it['cantidad'];
                    $montoGrupo     += $totalLinea;

                    $tabla .= "
    <tr>
        <td style='{$tdC}'>{$it['fecha']}</td>
        <td style='{$tdC}'>" . e($it['codigo']) . "</td>
        <td style='{$tdStyle}'>" . e($it['nombre']) . "</td>
        <td style='{$tdC}'>" . e($it['medida']) . "</td>
        <td style='{$tdC} font-weight:bold;'>" . number_format($it['cantidad']) . "</td>
        <td style='{$tdR}'>$ " . number_format($it['precio'], 4) . "</td>
        <td style='{$tdR}'>$ " . number_format($totalLinea, 4) . "</td>
    </tr>";
                }

                // Cierre del último grupo de obj. específico
                if ($codigoActual !== null) {
                    $tabla .= $filaSubtotalObjEsp($codigoActual, $cantGrupo, $montoGrupo, 4, 0, '#e8eef7');
                }

                $tabla .= "
    <tr>
        <td colspan='6' style='font-weight:bold; font-size:11px; text-align:right;
                                border:0.8px solid #000; padding:5px; background:#d4edda;'>
            Subtotal proyecto destino:
        </td>
        <td style='font-weight:bold; font-size:11px; text-align:right;
                    border:0.8px solid #000; padding:5px; background:#d4edda;'>
            $ " . number_format($subtotal, 4) . "
        </td>
    </tr>
</tbody>
</table>";
            }

            $tabla .= "
<table width='100%' style='border-collapse:collapse; margin-bottom:6px;'>
<tr>
    <td style='font-weight:bold; font-size:12px; text-align:right;
               border:0.8px solid #1a5c3a; padding:5px; background:#c3e6cb;'>
        TOTAL SECCIÓN A (Transferencias a proyecto):
    </td>
    <td style='font-weight:bold; font-size:12px; width:14%; text-align:right;
               border:0.8px solid #1a5c3a; padding:5px; background:#c3e6cb;'>
        $ " . number_format($granTotalProy, 4) . "
    </td>
</tr>
</table>";
        }

        // ══════════════════════════════════════════════════════════════════════
        // SECCIÓN B — SALIDAS GENERALES
        // ══════════════════════════════════════════════════════════════════════
        $tabla .= "
<table width='100%' style='border-collapse:collapse; margin-top:16px; margin-bottom:4px;'>
<tr>
    <td style='font-size:13px; padding:6px 8px; font-weight:bold;
               border:0.8px solid #7a4f1a; background:#ffe5cc; color:#7a4f1a;'>
        SECCIÓN B — SALIDAS GENERALES (Mantenimiento de Instalaciones Municipales)
    </td>
</tr>
</table>";

        if ($salidasGeneral->isEmpty()) {
            $tabla .= "<p style='font-size:11px; color:#888; padding:6px 0;'>
            Sin movimientos en este rango.
        </p>";
        } else {
            $itemsGral = [];
            foreach ($salidasGeneral as $sd) {
                if ($sd->cantidad_salida <= 0) continue;

                $material = $sd->entradaDetalle?->material;
                $precio   = (float) ($sd->entradaDetalle?->precio ?? 0);
                $idMat    = $material?->id ?? ('X' . md5($material?->nombre ?? ''));
                $clave    = $idMat . '|' . number_format($precio, 4, '.', '');

                if (!isset($itemsGral[$clave])) {
                    $itemsGral[$clave] = [
                        'codigo'     => $material?->objetoEspecifico?->codigo ?? '—',
                        'nombre_obj' => $material?->objetoEspecifico?->nombre ?? '',
                        'nombre'     => $material?->nombre ?? $sd->entradaDetalle?->nombre ?? '—',
                        'medida'     => $material?->unidadMedida?->nombre ?? '—',
                        'precio'     => $precio,
                        'cantidad'   => 0,
                        'fecha'      => date('d-m-Y', strtotime($sd->salida->fecha)),
                    ];
                }
                $itemsGral[$clave]['cantidad'] += $sd->cantidad_salida;
            }

            // Ordenar por código objeto específico
            uasort($itemsGral, function ($a, $b) {
                return strcmp($a['codigo'], $b['codigo']);
            });

            $tabla .= "
<table width='100%' style='border-collapse:collapse; margin-bottom:10px;'>
<thead>
    <tr>
        <th style='{$thStyle} width:10%;'>Fecha Salida</th>
        <th style='{$thStyle} width:10%;'>Obj. Espec.</th>
        <th style='{$thStyle} width:36%;'>Material</th>
        <th style='{$thStyle} width:10%;'>Medida</th>
        <th style='{$thStyle} width:10%;'>Cantidad</th>
        <th style='{$thStyle} width:12%;'>Precio Unit.</th>
        <th style='{$thStyle} width:12%;'>Total ($)</th>
    </tr>
</thead>
<tbody>";

            $codigoActual = null;
            $cantGrupo    = 0;
            $montoGrupo   = 0;

            foreach ($itemsGral as $it) {
                $totalLinea     = $it['cantidad'] * $it['precio'];
                $granTotalGral  += $totalLinea;

                // Acumular en el consolidado global
                $acumularConsolidado($it['codigo'], $it['nombre_obj'], $it['cantidad'], $totalLinea);

                // Cierre del grupo anterior si cambió el código
                if ($codigoActual !== null && $it['codigo'] !== $codigoActual) {
                    $tabla .= $filaSubtotalObjEsp($codigoActual, $cantGrupo, $montoGrupo, 4, 0, '#ffeed9');
                    $cantGrupo  = 0;
                    $montoGrupo = 0;
                }

                $codigoActual = $it['codigo'];
                $cantGrupo   += $it['cantidad'];
                $montoGrupo  += $totalLinea;

                $tabla .= "
    <tr>
        <td style='{$tdC}'>{$it['fecha']}</td>
        <td style='{$tdC}'>" . e($it['codigo']) . "</td>
        <td style='{$tdStyle}'>" . e($it['nombre']) . "</td>
        <td style='{$tdC}'>" . e($it['medida']) . "</td>
        <td style='{$tdC} font-weight:bold;'>" . number_format($it['cantidad']) . "</td>
        <td style='{$tdR}'>$ " . number_format($it['precio'], 4) . "</td>
        <td style='{$tdR}'>$ " . number_format($totalLinea, 4) . "</td>
    </tr>";
            }

            // Cierre del último grupo
            if ($codigoActual !== null) {
                $tabla .= $filaSubtotalObjEsp($codigoActual, $cantGrupo, $montoGrupo, 4, 0, '#ffeed9');
            }

            $tabla .= "
    <tr>
        <td colspan='6' style='font-weight:bold; font-size:12px; text-align:right;
                                border:0.8px solid #7a4f1a; padding:5px; background:#ffe5cc;'>
            TOTAL SECCIÓN B (Salidas Generales):
        </td>
        <td style='font-weight:bold; font-size:12px; text-align:right;
                    border:0.8px solid #7a4f1a; padding:5px; background:#ffe5cc;'>
            $ " . number_format($granTotalGral, 4) . "
        </td>
    </tr>
</tbody>
</table>";
        }

        // ══════════════════════════════════════════════════════════════════════
        // SECCIÓN C — RESERVAS
        // ══════════════════════════════════════════════════════════════════════
        $tabla .= "
<table width='100%' style='border-collapse:collapse; margin-top:16px; margin-bottom:4px;'>
<tr>
    <td style='font-size:13px; padding:6px 8px; font-weight:bold;
               border:0.8px solid #b8860b; background:#fff3cd; color:#856404;'>
        SECCIÓN C — RESERVAS DE MATERIALES
    </td>
</tr>
</table>";

        if ($reservas->isEmpty()) {
            $tabla .= "<p style='font-size:11px; color:#888; padding:6px 0;'>
            Sin reservas en este rango.
        </p>";
        } else {

            // Pre-procesar reservas para ordenarlas por código de obj. específico
            $reservasProc = [];
            foreach ($reservas as $r) {
                $entDet = $detallesReservas->get($r->id_entrada_detalle);
                if (!$entDet) continue;

                $material = $entDet->material;
                $reservasProc[] = [
                    'fecha_reserva'   => $r->fecha_reserva,
                    'fecha_despacho'  => $r->fecha_despacho,
                    'despachado'      => $r->despachado,
                    'tipo_destino'    => $r->tipo_destino,
                    'id_tipoproy_dst' => $r->id_tipoproyecto_destino,
                    'codigo'          => $material?->objetoEspecifico?->codigo ?? '—',
                    'nombre_obj'      => $material?->objetoEspecifico?->nombre ?? '',
                    'nombre'          => $material?->nombre ?? $entDet->nombre ?? '—',
                    'medida'          => $material?->unidadMedida?->nombre ?? '—',
                    'precio'          => (float) ($entDet->precio ?? 0),
                    'cantidad'        => (int) $r->cantidad,
                ];
            }

            // Ordenar por código obj. específico, luego por fecha
            usort($reservasProc, function ($a, $b) {
                $cmp = strcmp($a['codigo'], $b['codigo']);
                return $cmp !== 0 ? $cmp : strcmp($a['fecha_reserva'], $b['fecha_reserva']);
            });

            $tabla .= "
<table width='100%' style='border-collapse:collapse; margin-bottom:10px;'>
<thead>
    <tr>
        <th style='{$thStyle} width:9%;'>Fecha<br>Reserva</th>
        <th style='{$thStyle} width:9%;'>Obj. Espec.</th>
        <th style='{$thStyle} width:25%;'>Material</th>
        <th style='{$thStyle} width:8%;'>Medida</th>
        <th style='{$thStyle} width:8%;'>Cantidad</th>
        <th style='{$thStyle} width:10%;'>Precio Unit.</th>
        <th style='{$thStyle} width:11%;'>Total ($)</th>
        <th style='{$thStyle} width:20%;'>Estado / Destino</th>
    </tr>
</thead>
<tbody>";

            $codigoActual = null;
            $cantGrupo    = 0;
            $montoGrupo   = 0;

            foreach ($reservasProc as $r) {

                $cantidad = $r['cantidad'];
                $precio   = $r['precio'];
                $total    = $cantidad * $precio;

                $granTotalReserva += $total;

                // Acumular en consolidado global
                $acumularConsolidado($r['codigo'], $r['nombre_obj'], $cantidad, $total);

                // Cambio de objeto específico → subtotal del anterior
                if ($codigoActual !== null && $r['codigo'] !== $codigoActual) {
                    // Subtotal de reservas usa colspan=4 para label + 1 cant + 1 vacío + 1 total + 1 extra (estado)
                    $tabla .= $filaSubtotalObjEsp($codigoActual, $cantGrupo, $montoGrupo, 4, 1, '#fff8e1');
                    $cantGrupo  = 0;
                    $montoGrupo = 0;
                }

                $codigoActual = $r['codigo'];
                $cantGrupo   += $cantidad;
                $montoGrupo  += $total;

                $fechaReserva = date('d-m-Y', strtotime($r['fecha_reserva']));

                // Determinar estado / destino
                if (!$r['despachado']) {
                    $estado     = "PENDIENTE";
                    $estadoBg   = '#fff3cd';
                    $estadoFg   = '#856404';
                    $estadoExtra = '';
                } else {
                    $tipoDest = $r['tipo_destino'] ?? '';
                    $fechaDesp = $r['fecha_despacho'] ? date('d-m-Y', strtotime($r['fecha_despacho'])) : '';

                    if ($tipoDest === 'proyecto') {
                        $nombreDest = Tipoproyecto::find($r['id_tipoproy_dst'])?->nombre ?? '—';
                        $estado     = "DESPACHADA → " . $nombreDest;
                        $estadoBg   = '#d4edda';
                        $estadoFg   = '#155724';
                        $estadoExtra = "<br><small>Despachada: $fechaDesp</small>";
                    } elseif ($tipoDest === 'general') {
                        $estado     = "DESPACHADA (Gral.)";
                        $estadoBg   = '#ffe5cc';
                        $estadoFg   = '#7a4f1a';
                        $estadoExtra = "<br><small>Despachada: $fechaDesp</small>";
                    } elseif ($tipoDest === 'liberada') {
                        $estado     = "LIBERADA";
                        $estadoBg   = '#f8d7da';
                        $estadoFg   = '#721c24';
                        $estadoExtra = "<br><small>Liberada: $fechaDesp</small>";
                    } else {
                        $estado     = "DESPACHADA";
                        $estadoBg   = '#e2e3e5';
                        $estadoFg   = '#383d41';
                        $estadoExtra = '';
                    }
                }

                $tabla .= "
    <tr>
        <td style='{$tdC}'>{$fechaReserva}</td>
        <td style='{$tdC}'>" . e($r['codigo']) . "</td>
        <td style='{$tdStyle}'>" . e($r['nombre']) . "</td>
        <td style='{$tdC}'>" . e($r['medida']) . "</td>
        <td style='{$tdC} font-weight:bold;'>" . number_format($cantidad) . "</td>
        <td style='{$tdR}'>$ " . number_format($precio, 4) . "</td>
        <td style='{$tdR}'>$ " . number_format($total, 4) . "</td>
        <td style='{$tdStyle} text-align:center; font-weight:bold;
                  background:{$estadoBg}; color:{$estadoFg};'>
            {$estado}{$estadoExtra}
        </td>
    </tr>";
            }

            // Cierre del último grupo
            if ($codigoActual !== null) {
                $tabla .= $filaSubtotalObjEsp($codigoActual, $cantGrupo, $montoGrupo, 4, 1, '#fff8e1');
            }

            $tabla .= "
    <tr>
        <td colspan='6' style='font-weight:bold; font-size:12px; text-align:right;
                                border:0.8px solid #b8860b; padding:5px; background:#fff3cd;'>
            TOTAL SECCIÓN C (Reservas):
        </td>
        <td style='font-weight:bold; font-size:12px; text-align:right;
                    border:0.8px solid #b8860b; padding:5px; background:#fff3cd;'>
            $ " . number_format($granTotalReserva, 4) . "
        </td>
        <td style='border:0.8px solid #b8860b; background:#fff3cd;'></td>
    </tr>
</tbody>
</table>";
        }

        // ══════════════════════════════════════════════════════════════════════
        // RESUMEN GLOBAL DE SECCIONES
        // ══════════════════════════════════════════════════════════════════════
        $granTotalGlobal = $granTotalProy + $granTotalGral + $granTotalReserva;

        $tabla .= "
<br>
<table width='70%' style='border-collapse:collapse; margin-top:14px;'>
<thead>
    <tr>
        <td colspan='2' style='{$thStyle} font-size:12px;'>
            RESUMEN POR SECCIÓN
        </td>
    </tr>
</thead>
<tbody>
    <tr>
        <td style='{$tdStyle} background:#d4edda;'>Total Transferencias a Proyecto</td>
        <td style='{$tdR} font-weight:bold; background:#d4edda;'>
            $ " . number_format($granTotalProy, 4) . "
        </td>
    </tr>
    <tr>
        <td style='{$tdStyle} background:#ffe5cc;'>Total Salidas Generales</td>
        <td style='{$tdR} font-weight:bold; background:#ffe5cc;'>
            $ " . number_format($granTotalGral, 4) . "
        </td>
    </tr>
    <tr>
        <td style='{$tdStyle} background:#fff3cd;'>Total Reservas</td>
        <td style='{$tdR} font-weight:bold; background:#fff3cd;'>
            $ " . number_format($granTotalReserva, 4) . "
        </td>
    </tr>
    <tr>
        <td style='{$tdStyle} background:#d9e1f2; font-weight:bold; font-size:12px;'>
            TOTAL GENERAL
        </td>
        <td style='{$tdR} background:#d9e1f2; font-weight:bold; font-size:12px;'>
            $ " . number_format($granTotalGlobal, 4) . "
        </td>
    </tr>
</tbody>
</table>";

        // ══════════════════════════════════════════════════════════════════════
        // CONSOLIDADO POR OBJETO ESPECÍFICO (cruzando las 3 secciones)
        // ══════════════════════════════════════════════════════════════════════
        if (!empty($consolidadoObjEsp)) {

            ksort($consolidadoObjEsp); // ordenar alfabéticamente por código

            $tabla .= "
<br>
<table width='100%' style='border-collapse:collapse; margin-top:14px;'>
<thead>
    <tr>
        <td colspan='4' style='{$thStyle} font-size:12px;'>
            CONSOLIDADO POR OBJETO ESPECÍFICO (Secciones A + B + C)
        </td>
    </tr>
    <tr>
        <th style='{$thStyle} width:15%;'>Código</th>
        <th style='{$thStyle} width:50%;'>Nombre</th>
        <th style='{$thStyle} width:15%;'>Cant. Total</th>
        <th style='{$thStyle} width:20%;'>Total ($)</th>
    </tr>
</thead>
<tbody>";

            $totalConsolidado = 0;
            foreach ($consolidadoObjEsp as $obj) {
                $totalConsolidado += $obj['total'];
                $tabla .= "
    <tr>
        <td style='{$tdC} font-weight:bold;'>" . e($obj['codigo']) . "</td>
        <td style='{$tdStyle}'>" . e($obj['nombre_obj']) . "</td>
        <td style='{$tdC} font-weight:bold;'>" . number_format($obj['cantidad'], 2, '.', ',') . "</td>
        <td style='{$tdR}'>$ " . number_format($obj['total'], 4) . "</td>
    </tr>";
            }

            $tabla .= "
    <tr>
        <td colspan='3' style='font-weight:bold; font-size:12px; text-align:right;
                                border:0.8px solid #000; padding:6px; background:#d9e1f2;'>
            TOTAL CONSOLIDADO:
        </td>
        <td style='font-weight:bold; font-size:12px; text-align:right;
                    border:0.8px solid #000; padding:6px; background:#d9e1f2;'>
            $ " . number_format($totalConsolidado, 4) . "
        </td>
    </tr>
</tbody>
</table>";
        }

        // ══════════════════════════════════════════════════════════════════════
        // Firmas
        // ══════════════════════════════════════════════════════════════════════
        $informacionGeneral = InformacionGeneral::where('id', 1)->first();
        $px2 = 60;

        $tabla .= "
<table width='100%' style='border-collapse:collapse; font-family:Arial, sans-serif;'>
<tr>
    <td colspan='2' style='height:" . ($informacionGeneral->px_firmas ?? 60) . "px;'></td>
</tr>
<tr>
    <td style='width:50%; padding-right:40px; vertical-align:top;'>
        <div style='font-weight:bold; font-size:13px; margin-bottom:8px;'>ELABORADO POR:</div>
        <table width='100%' style='border-collapse:collapse;'>
            <tr><td style='height:{$px2}px;'></td></tr>
            <tr>
                <td style='font-size:12px; padding-bottom:8px;'>
                    FIRMA:
                    <table width='90%' style='border-collapse:collapse; display:inline-table;'>
                        <tr><td style='border-bottom:0.8px solid #000; height:16px;'></td></tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td style='font-size:12px; padding-bottom:8px;'>
                    NOMBRE:
                    <table width='85%' style='border-collapse:collapse; display:inline-table;'>
                        <tr><td style='border-bottom:0.8px solid #000; height:16px;'></td></tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td style='font-size:12px; padding-bottom:8px;'>
                    CARGO:
                    <table width='87%' style='border-collapse:collapse; display:inline-table;'>
                        <tr><td style='border-bottom:0.8px solid #000; height:16px;'></td></tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td style='font-size:10px; color:#333; text-align:center;'>
                    " . e($informacionGeneral->d_nombre1 ?? '') . "
                </td>
            </tr>
        </table>
    </td>
    <td style='width:50%; padding-left:40px; vertical-align:top;'>
        <div style='font-weight:bold; font-size:13px; margin-bottom:8px;'>REVISADO POR:</div>
        <table width='100%' style='border-collapse:collapse;'>
            <tr><td style='height:{$px2}px;'></td></tr>
            <tr>
                <td style='font-size:12px; padding-bottom:8px;'>
                    FIRMA:
                    <table width='90%' style='border-collapse:collapse; display:inline-table;'>
                        <tr><td style='border-bottom:0.8px solid #000; height:16px;'></td></tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td style='font-size:12px; padding-bottom:8px;'>
                    NOMBRE:
                    <table width='85%' style='border-collapse:collapse; display:inline-table;'>
                        <tr><td style='border-bottom:0.8px solid #000; height:16px;'></td></tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td style='font-size:12px; padding-bottom:8px;'>
                    CARGO:
                    <table width='87%' style='border-collapse:collapse; display:inline-table;'>
                        <tr><td style='border-bottom:0.8px solid #000; height:16px;'></td></tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td style='font-size:10px; color:#333; text-align:center;'>
                    " . e($informacionGeneral->d_nombre2 ?? '') . "
                </td>
            </tr>
        </table>
    </td>
</tr>
</table>";

        $stylesheet = file_get_contents('css/cssregistro.css');
        $mpdf->WriteHTML($stylesheet, 1);
        $mpdf->setFooter("Página: " . '{PAGENO}' . "/" . '{nb}');
        $mpdf->WriteHTML($tabla, 2);
        $mpdf->Output();
    }



    public function vistaReporteSobranteProyectoCerrado()
    {
        $proyectosCerrados = Tipoproyecto::whereHas('transferencia')->orderBy('nombre')->get();
        $departamentos = Departamentos::orderBy('nombre')->get();
        $infoGeneral = InformacionGeneral::where('id', 1)->first();

        return view('backend.reportes.vistareporteproyectocerrado', compact('proyectosCerrados', 'departamentos',
        'infoGeneral'));
    }

    public function actualizarFirmasReporteCerrado(Request $request)
    {
        try {

            InformacionGeneral::where('id', 1)->update([
                'c_nombre1' => $request->c_nombre1,
                'c_nombre2' => $request->c_nombre2,
                'c_nombre3' => $request->c_nombre3,
            ]);

            return response()->json([
                'success' => 1
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'success' => 99
            ]);
        }
    }


    public function vistaPDFReporteSobranteProyectoCerrado(Request $request)
    {
        $idproy        = $request->input('idproy');
        $noproyecto    = $request->input('noproyecto', '');
        $acuerdo       = $request->input('acuerdo', '');
        $iddepto       = $request->input('iddepto', 0);
        $jefe          = $request->input('jefe', '');
        $justificacion = $request->input('justificacion', '');
        $observaciones = $request->input('observaciones', '');

        $proyecto         = Tipoproyecto::find($idproy);
        $departamento     = Departamentos::find($iddepto);
        $logoalcaldia     = 'images/logo.png';
        $fechaHoy         = date('d/m/Y');
        $informacionGeneral = InformacionGeneral::where('id', 1)->first();

        // ── Obtener transferencia (cierre del proyecto) ───────────────────
        $transferencia = Transferencia::where('id_tipoproyecto', $idproy)
            ->orderBy('id', 'desc')
            ->first();

        if (!$transferencia) {
            $mpdf = new \Mpdf\Mpdf([
                'tempDir'     => sys_get_temp_dir(),
                'format'      => 'LETTER',
                'orientation' => 'L',
            ]);
            $mpdf->WriteHTML("<p style='font-family:Arial; font-size:14px; color:red; padding:20px;'>
        Este proyecto no tiene registro de cierre generado.</p>",
                \Mpdf\HTMLParserMode::HTML_BODY
            );
            $mpdf->Output();
            return;
        }

        // ── Obtener detalles del snapshot de cierre ───────────────────────
        $detalles = TransferenciaDetalle::where('id_transferencia', $transferencia->id)
            ->with('entradaDetalle.material.unidadMedida', 'entradaDetalle.material.objetoEspecifico')
            ->get();

        if ($detalles->isEmpty()) {
            $mpdf = new \Mpdf\Mpdf([
                'tempDir'     => sys_get_temp_dir(),
                'format'      => 'LETTER',
                'orientation' => 'L',
            ]);
            $mpdf->WriteHTML("<p style='font-family:Arial; font-size:14px; color:#888; padding:20px;'>
        No hay materiales sobrantes registrados para este proyecto.</p>",
                \Mpdf\HTMLParserMode::HTML_BODY
            );
            $mpdf->Output();
            return;
        }

        // ── Agrupar por código objeto específico ──────────────────────────
        // Dentro de cada código, se unen las filas que tengan el mismo
        // material y el mismo precio unitario (clave: id_material|precio).
        $porCodigo = [];
        $granTotal = 0;

        foreach ($detalles as $det) {
            $codigo     = $det->entradaDetalle?->material?->objetoEspecifico?->codigo ?? 'SIN-CODIGO';
            $nombre     = $det->entradaDetalle?->material?->nombre ?? $det->nombre_material ?? '—';
            $medida     = $det->entradaDetalle?->material?->unidadMedida?->nombre ?? '—';
            $idMaterial = $det->entradaDetalle?->material?->id ?? ('X' . md5($nombre));
            $cantidad   = (float) $det->cantidad_sobrante;
            $precio     = (float) $det->precio;

            if (!isset($porCodigo[$codigo])) {
                $porCodigo[$codigo] = [
                    'codigo'     => $codigo,
                    'materiales' => [],
                    'subtotal'   => 0,
                ];
            }

            // Clave de unión: mismo material + mismo precio unitario.
            // El precio se normaliza a 4 decimales (igual que en la BD).
            $clave = $idMaterial . '|' . number_format($precio, 4, '.', '');

            if (!isset($porCodigo[$codigo]['materiales'][$clave])) {
                $porCodigo[$codigo]['materiales'][$clave] = [
                    'nombre'   => $nombre,
                    'medida'   => $medida,
                    'cantidad' => 0,
                    'precio'   => $precio,
                    'subtotal' => 0,
                ];
            }

            // Acumular cantidad y subtotal en la fila unificada
            $porCodigo[$codigo]['materiales'][$clave]['cantidad'] += $cantidad;

            $subtotalFila = $porCodigo[$codigo]['materiales'][$clave]['cantidad'] * $precio;
            $porCodigo[$codigo]['materiales'][$clave]['subtotal'] = $subtotalFila;
        }

        // Recalcular subtotales por código y gran total a partir de las filas unificadas
        foreach ($porCodigo as $codigo => &$grupo) {
            $grupo['subtotal'] = 0;
            foreach ($grupo['materiales'] as $mat) {
                $grupo['subtotal'] += $mat['subtotal'];
            }
            $granTotal += $grupo['subtotal'];
        }
        unset($grupo);

        // ── Inicializar mPDF ──────────────────────────────────────────────
        $mpdf = new \Mpdf\Mpdf([
            'tempDir'     => sys_get_temp_dir(),
            'format'      => 'LETTER',
            'orientation' => 'P',
        ]);
        $mpdf->SetTitle('GEAD-001-INFO');
        $mpdf->showImageErrors = false;

        if (file_exists(public_path('css/cssbodega.css'))) {
            $stylesheet = file_get_contents(public_path('css/cssbodega.css'));
            $mpdf->WriteHTML($stylesheet, \Mpdf\HTMLParserMode::HEADER_CSS);
        }

        // ── Estilos inline reutilizables ──────────────────────────────────
        $thStyle = "font-weight:bold; font-size:11px; border:0.8px solid #000;
            padding:5px 4px; background:#d9e1f2; text-align:center;";
        $tdStyle = "font-size:11px; border:0.8px solid #000; padding:4px;";
        $tdC     = $tdStyle . " text-align:center;";
        $tdR     = $tdStyle . " text-align:right;";
        $tdL     = $tdStyle . " text-align:left;";

        // ── Encabezado ────────────────────────────────────────────────────
        $html = "
<table width='100%' style='border-collapse:collapse; font-family:Arial, sans-serif;'>
    <tr>
        <td style='width:25%; border:0.8px solid #000; padding:6px 8px;'>
            <table width='100%'>
                <tr>
                    <td style='width:30%; text-align:left;'>
                        <img src='{$logoalcaldia}' style='height:38px'>
                    </td>
                    <td style='width:70%; text-align:left; color:#104e8c;
                               font-size:13px; font-weight:bold; line-height:1.3;'>
                        SANTA ANA NORTE<br>EL SALVADOR
                    </td>
                </tr>
            </table>
        </td>
        <td style='width:50%; border-top:0.8px solid #000; border-bottom:0.8px solid #000;
                   padding:6px 8px; text-align:center; font-size:15px; font-weight:bold;'>
            INFORME DE INVENTARIO FÍSICO<br>DE MATERIALES SOBRANTES
        </td>
        <td style='width:25%; border:0.8px solid #000; padding:0; vertical-align:top;'>
            <table width='100%' style='font-size:10px;'>
                <tr>
                    <td width='40%' style='border-right:0.8px solid #000;
                                           border-bottom:0.8px solid #000; padding:4px 6px;'>
                        <strong>Código:</strong>
                    </td>
                    <td width='60%' style='border-bottom:0.8px solid #000;
                                           padding:4px 6px; text-align:center;'>
                        GEAD-001-INFO
                    </td>
                </tr>
                <tr>
                    <td style='border-right:0.8px solid #000;
                               border-bottom:0.8px solid #000; padding:4px 6px;'>
                        <strong>Versión:</strong>
                    </td>
                    <td style='border-bottom:0.8px solid #000;
                               padding:4px 6px; text-align:center;'>000</td>
                </tr>
                <tr>
                    <td style='border-right:0.8px solid #000; padding:4px 6px;'>
                        <strong>Fecha de vigencia:</strong>
                    </td>
                    <td style='padding:4px 6px; text-align:center;'>22/05/2026</td>
                </tr>
            </table>
        </td>
    </tr>
</table><br>";

        // ── Fecha ─────────────────────────────────────────────────────────
        $html .= "
<table width='100%' style='border-collapse:collapse; margin-bottom:6px;'>
    <tr>
        <td style='width:70%;'></td>
        <td style='width:15%; border:0.8px solid #000; padding:5px 8px;
                   font-weight:bold; font-size:11px; text-align:center;'>FECHA</td>
        <td style='width:15%; border:0.8px solid #000; padding:5px 8px;
                   font-size:11px; text-align:center;'>{$fechaHoy}</td>
    </tr>
</table>";

        // ── Datos del proyecto ────────────────────────────────────────────
        $campos = [
            'No. DE PROYECTO'                        => e($noproyecto)    ?: '',
            'NOMBRE DEL PROYECTO'                    => e($proyecto->nombre ?? ''),
            'ACUERDO DE APROBACIÓN DEL PROYECTO'     => e($acuerdo)       ?: '',
            'UNIDAD SOLICITANTE'                     => e($departamento->nombre ?? ''),
            'JEFE O ENCARGADO DE UNIDAD SOLICITANTE' => e($jefe)          ?: '',
            'JUSTIFICACIÓN DEL SOBRANTE'             => e($justificacion) ?: '',
        ];

        $html .= "<table width='100%' style='border-collapse:collapse; margin-bottom:6px;'>";
        foreach ($campos as $label => $valor) {
            $html .= "
    <tr>
        <td style='width:25%; border:0.8px solid #ccc; padding:5px 8px;
                   font-size:11px; font-weight:bold; background:#f5f5f5;'>
            {$label}:
        </td>
        <td style='border:0.8px solid #ccc; padding:5px 8px; font-size:11px;'>
            {$valor}
        </td>
    </tr>";
        }
        $html .= "</table><br>";

        // ── Texto declaración ─────────────────────────────────────────────
        $html .= "
<table width='100%' style='border-collapse:collapse; margin-bottom:10px;'>
    <tr>
        <td style='border:0.8px solid #000; padding:8px 10px; font-size:10px;
                   text-align:justify; line-height:1.6;'>
            POR MEDIO DEL PRESENTE LOS SUSCRITOS RESPONSABLES DE LA EJECUCIÓN Y SUPERVISIÓN DEL PROYECTO,
            DECLARAMOS BAJO FE DE JURAMENTO QUE EL INVENTARIO FÍSICO DETALLADO HA SIDO VERIFICADO Y
            CONFRONTADO CON LOS REGISTROS Y LA LIQUIDACIÓN FINAL DEL PROYECTO. CERTIFICAMOS QUE LAS
            CANTIDADES AQUÍ EXPRESADAS SON LAS SOBRANTES REALES DEL PROYECTO Y QUE LA VALORACIÓN MONETARIA
            SE HA DETERMINADO CON BASE EN LAS ORDENES DE COMPRA Y/O CONTRATOS. AUTORIZAMOS EL USO DE ESTE
            DOCUMENTO COMO SOPORTE PARA EL INGRESO DE ESTOS MATERIALES SOBRANTES A LA BODEGA DE PROYECTOS
            O A LA QUE DESIGNE EL CONCEJO MUNICIPAL Y SU CORRESPONDIENTES REGISTROS CONTABLES.
        </td>
    </tr>
</table>";

        // ── Tabla de materiales agrupados por código ──────────────────────
        $html .= "
<table width='100%' style='border-collapse:collapse;'>
    <thead>
        <tr>
            <th style='{$thStyle} width:5%;'>No.</th>
            <th style='{$thStyle} width:10%;'>COD PRESUP.</th>
            <th style='{$thStyle} width:38%;'>DESCRIPCIÓN</th>
            <th style='{$thStyle} width:12%;'>U. DE MEDIDA</th>
            <th style='{$thStyle} width:10%;'>CANTIDAD</th>
            <th style='{$thStyle} width:12%;'>PRECIO UNITARIO</th>
            <th style='{$thStyle} width:13%;'>SUBTOTAL</th>
        </tr>
    </thead>
    <tbody>";

        $i = 1;
        foreach ($porCodigo as $grupo) {
            foreach ($grupo['materiales'] as $mat) {
                $html .= "
        <tr>
            <td style='{$tdC}'>{$i}</td>
            <td style='{$tdC}'>" . e($grupo['codigo']) . "</td>
            <td style='{$tdL}'>" . e($mat['nombre']) . "</td>
            <td style='{$tdC}'>" . e($mat['medida']) . "</td>
            <td style='{$tdC} font-weight:bold;'>" . number_format($mat['cantidad']) . "</td>
            <td style='{$tdR}'>$ " . number_format($mat['precio'], 4) . "</td>
            <td style='{$tdR}'>$ " . number_format($mat['subtotal'], 4) . "</td>
        </tr>";
                $i++;
            }

            // Subtotal por código
            $html .= "
        <tr>
            <td colspan='6' style='font-weight:bold; font-size:11px; text-align:center;
                                    border:0.8px solid #000; padding:5px 4px; background:#f2f4f8;'>
                SUBTOTAL [" . e($grupo['codigo']) . "]
            </td>
            <td style='font-weight:bold; font-size:11px; text-align:right;
                        border:0.8px solid #000; padding:5px 4px; background:#f2f4f8;'>
                $ " . number_format($grupo['subtotal'], 4) . "
            </td>
        </tr>";
        }

        // Total general
        $html .= "
        <tr>
            <td colspan='6' style='font-weight:bold; font-size:12px; text-align:center;
                                    border:0.8px solid #000; padding:6px 4px; background:#d9e1f2;'>
                TOTAL GENERAL
            </td>
            <td style='font-weight:bold; font-size:12px; text-align:right;
                        border:0.8px solid #000; padding:6px 4px; background:#d9e1f2;'>
                $ " . number_format($granTotal, 4) . "
            </td>
        </tr>
    </tbody>
</table>";

        // ── Observaciones ─────────────────────────────────────────────────
        $html .= "
<br>
<table width='100%' border='1' cellspacing='0' cellpadding='6'
       style='border-collapse:collapse; font-size:11px;
              margin-top:" . ($informacionGeneral->px_observaciones ?? 0) . "px;'>
    <tr style='background:#f2f4f8;'>
        <td style='font-weight:bold; font-size:12px;'>Observaciones:</td>
    </tr>
    <tr>
        <td style='height:50px; font-size:11px; vertical-align:top;'>
            " . e($observaciones) . "
        </td>
    </tr>
</table>";

        // ── Firmas ────────────────────────────────────────────────────────
        $html .= "
<table width='100%' style='border-collapse:collapse; font-family:Arial,sans-serif;
                            margin-top:" . ($informacionGeneral->px_firmas ?? 0) . "px;
                            font-size:23px; line-height:1.6;'>
    <tr>
        <td style='width:50%; padding-right:40px; vertical-align:top;'>
            <strong style='font-size:24px;'>ELABORADO POR:</strong><br><br>
            <table width='100%' style='border-collapse:collapse;'>
                <tr>
                    <td style='width:15%; padding-bottom:12px;'>FIRMA:</td>
                    <td style='border-bottom:0.8px solid #000; width:85%;'>&nbsp;</td>
                </tr>
                <tr><td colspan='2' style='height:40px;'></td></tr>
                <tr>
                    <td style='padding-bottom:12px;'>NOMBRE:</td>
                    <td style='border-bottom:0.8px solid #000;'>&nbsp;</td>
                </tr>
                <tr><td colspan='2' style='height:40px;'></td></tr>
                <tr>
                    <td style='padding-bottom:12px;'>CARGO:</td>
                    <td style='border-bottom:0.8px solid #000;'>&nbsp;</td>
                </tr>
                <tr><td colspan='2' style='height:40px;'></td></tr>
                <tr>
                    <td colspan='2' style='text-align:center; font-size:20px; line-height:1.5;'>
                        $informacionGeneral->c_nombre1
                    </td>
                </tr>
            </table>
        </td>
        <td style='width:50%; padding-left:40px; vertical-align:top;'>
            <strong style='font-size:24px;'>REVISADO POR:</strong><br><br>
            <table width='100%' style='border-collapse:collapse;'>
                <tr>
                    <td style='width:15%; padding-bottom:12px;'>FIRMA:</td>
                    <td style='border-bottom:0.8px solid #000; width:85%;'>&nbsp;</td>
                </tr>
                <tr><td colspan='2' style='height:40px;'></td></tr>
                <tr>
                    <td style='padding-bottom:12px;'>NOMBRE:</td>
                    <td style='border-bottom:0.8px solid #000;'>&nbsp;</td>
                </tr>
                <tr><td colspan='2' style='height:40px;'></td></tr>
                <tr>
                    <td style='padding-bottom:12px;'>CARGO:</td>
                    <td style='border-bottom:0.8px solid #000;'>&nbsp;</td>
                </tr>
                <tr><td colspan='2' style='height:40px;'></td></tr>
                <tr>
                    <td colspan='2' style='text-align:center; font-size:20px; line-height:1.5;'>
                        $informacionGeneral->c_nombre2
                    </td>
                </tr>
            </table>
        </td>
    </tr>

    <tr><td colspan='2' style='height:70px;'></td></tr>

    <tr>
        <td colspan='2' style='vertical-align:top;'>
            <strong style='font-size:24px;'>ES CONFORME:</strong><br><br>
            <table width='50%' style='border-collapse:collapse; margin:0 auto;'>
                <tr>
                    <td style='width:15%; padding-bottom:12px;'>FIRMA:</td>
                    <td style='border-bottom:0.8px solid #000; width:85%;'>&nbsp;</td>
                </tr>
                <tr><td colspan='2' style='height:40px;'></td></tr>
                <tr>
                    <td style='padding-bottom:12px;'>NOMBRE:</td>
                    <td style='border-bottom:0.8px solid #000;'>&nbsp;</td>
                </tr>
                <tr><td colspan='2' style='height:40px;'></td></tr>
                <tr>
                    <td style='padding-bottom:12px;'>CARGO:</td>
                    <td style='border-bottom:0.8px solid #000;'>&nbsp;</td>
                </tr>
                <tr><td colspan='2' style='height:40px;'></td></tr>
                <tr>
                    <td colspan='2' style='text-align:center; font-size:20px; line-height:1.5;'>
                        $informacionGeneral->c_nombre3
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>";

        $mpdf->setFooter("Página {PAGENO} de {nb}");
        $mpdf->WriteHTML($html, \Mpdf\HTMLParserMode::HTML_BODY);
        $mpdf->Output();
    }

    public function formSolicitudPreview(Request $request)
    {
        $logoalcaldia       = 'images/logo.png';
        $informacionGeneral = InformacionGeneral::where('id', 1)->first();
        $fechaFormat        = Carbon::parse($request->fecha)->format('d/m/Y');

        $numero          = $request->numero          ?? '';
        $noproyecto      = $request->noproyecto      ?? '';
        $nombreOrigen    = $request->nombre_origen   ?? '';
        $nombreProyecto  = $request->nombre_proyecto ?? '';
        $proyectoDestino = $request->proyecto_destino ?? '';
        $acuerdo         = $request->acuerdo         ?? '';
        $depto           = $request->depto           ?? '';
        $jefe            = $request->jefe            ?? '';
        $justificacion   = $request->justificacion   ?? '';
        $observaciones   = $request->observaciones   ?? '';
        $tipodestino     = $request->tipo_destino    ?? '';
        $materiales      = json_decode($request->materiales, true) ?? [];

        // ── Obtener datos de BD por id_entrada_detalle ────────────────
        $rows = [];

        foreach ($materiales as $mat) {
            $idEntDet = $mat['id_entrada_detalle'] ?? null;
            $codigo   = '—';
            $medida   = '—';
            $precio   = 0;

            if ($idEntDet) {
                $entDet = EntradasDetalle::with([
                    'material.unidadMedida',
                    'material.objetoEspecifico',
                ])->find($idEntDet);

                if ($entDet) {
                    $codigo = $entDet->material?->objetoEspecifico?->codigo
                        ?? $entDet->codigo
                        ?? '—';
                    $medida = $entDet->material?->unidadMedida?->nombre ?? '—';
                    $precio = $entDet->precio ?? 0;
                }
            }

            $cantidad = (int) ($mat['cantidad'] ?? 0);
            $subtotal = $cantidad * $precio;

            $rows[] = [
                'codigo'   => $codigo,
                'nombre'   => $mat['nombre'] ?? '—',
                'medida'   => $medida,
                'cantidad' => $cantidad,
                'precio'   => $precio,
                'subtotal' => $subtotal,
            ];
        }

        // ── Ordenar por código de objeto específico ───────────────────
        usort($rows, fn($a, $b) => strcmp($a['codigo'], $b['codigo']));

        $granTotal = array_sum(array_column($rows, 'subtotal'));

        // ── Estilos ───────────────────────────────────────────────────
        $thStyle  = "font-weight:bold; font-size:10px; border:0.8px solid #000; padding:4px; background:#d9e1f2; text-align:center;";
        $tdStyle  = "font-size:10px; border:0.8px solid #000; padding:4px;";
        $tdC      = $tdStyle . " text-align:center;";
        $tdR      = $tdStyle . " text-align:right;";
        $subStyle = "font-weight:bold; font-size:10px; text-align:right; border:0.8px solid #000; padding:4px; background:#f2f4f8;";
        $subLabel = "font-weight:bold; font-size:10px; text-align:center; border:0.8px solid #000; padding:4px; background:#f2f4f8;";

        // ── Encabezado ────────────────────────────────────────────────
        $html = "
<table width='100%' style='border-collapse:collapse; font-family:Arial,sans-serif;'>
    <tr>
        <td style='width:25%; border:0.8px solid #000; padding:6px 8px;'>
            <table width='100%'>
                <tr>
                    <td style='width:35%; text-align:left;'>
                        <img src='{$logoalcaldia}' style='height:38px'>
                    </td>
                    <td style='width:65%; text-align:left; color:#104e8c;
                                font-size:12px; font-weight:bold; line-height:1.3;'>
                        SANTA ANA NORTE<br>EL SALVADOR
                    </td>
                </tr>
            </table>
        </td>
        <td style='width:50%; border-top:0.8px solid #000; border-bottom:0.8px solid #000;
                   padding:6px 8px; text-align:center; font-size:13px; font-weight:bold;'>
            FORMULARIO DE SOLICITUD DE MATERIALES SOBRANTES<br>
            PARA PROYECTO DE INVERSIÓN PÚBLICA<br>
            POR IMPREVISTOS MENORES
        </td>
        <td style='width:25%; border:0.8px solid #000; padding:0; vertical-align:top;'>
            <table width='100%' style='font-size:10px;'>
                <tr>
                    <td width='40%' style='border-right:0.8px solid #000;
                                           border-bottom:0.8px solid #000; padding:4px 6px;'>
                        <strong>Código:</strong>
                    </td>
                    <td width='60%' style='border-bottom:0.8px solid #000;
                                           padding:4px 6px; text-align:center;'>
                        GEAD-002-FORM
                    </td>
                </tr>
                <tr>
                    <td style='border-right:0.8px solid #000;
                               border-bottom:0.8px solid #000; padding:4px 6px;'>
                        <strong>Versión:</strong>
                    </td>
                    <td style='border-bottom:0.8px solid #000;
                               padding:4px 6px; text-align:center;'>000</td>
                </tr>
                <tr>
                    <td style='border-right:0.8px solid #000; padding:4px 6px;'>
                        <strong>Fecha de vigencia:</strong>
                    </td>
                    <td style='padding:4px 6px; text-align:center;'>22/05/2026</td>
                </tr>
            </table>
        </td>
    </tr>
</table><br>";

        // ── No. Solicitud y Fecha ─────────────────────────────────────
        $html .= "
<table width='100%' style='border-collapse:collapse; margin-bottom:4px; margin-top:6px;'>
    <tr>
        <td style='width:20%; border:0.8px solid #ccc; padding:5px 8px;
                   font-size:11px; font-weight:bold; background:#f5f5f5;'>
            NO. DE SOLICITUD:
        </td>
        <td style='width:44%; border:0.8px solid #ccc; padding:5px 8px; font-size:11px;'>
            " . e($numero) . "
        </td>
        <td style='width:5%; border:none;'></td>
        <td style='width:13%; border:0.8px solid #000; padding:5px 8px;
                   font-size:11px; font-weight:bold; text-align:center; background:#f5f5f5;'>
            FECHA:
        </td>
        <td style='width:18%; border:0.8px solid #000; padding:5px 8px;
                   font-size:11px; text-align:center;'>
            {$fechaFormat}
        </td>
    </tr>
</table>";

        // ── Campos del formulario ─────────────────────────────────────
        $campos = [
            'PROYECTO DE ORIGEN DE LOS MATERIALES'  => $nombreOrigen,
            'No. DE PROYECTO'                        => $noproyecto,
            'NOMBRE DEL PROYECTO'                    => $nombreProyecto,
            'ACUERDO DE APROBACIÓN DEL PROYECTO'     => $acuerdo,
            'JUSTIFICACIÓN DEL DESTINO'              => $justificacion,
            'UNIDAD SOLICITANTE'                     => $depto,
            'JEFE O ENCARGADO DE UNIDAD SOLICITANTE' => $jefe,
        ];

        $html .= "<table width='100%' style='border-collapse:collapse; margin-bottom:4px;'>";
        foreach ($campos as $label => $valor) {
            $html .= "
    <tr>
        <td style='width:25%; border:0.8px solid #ccc; padding:5px 8px;
                   font-size:11px; font-weight:bold; background:#f5f5f5;'>
            {$label}:
        </td>
        <td style='border:0.8px solid #ccc; padding:5px 8px; font-size:11px;'>
            " . e($valor) . "
        </td>
    </tr>";
        }
        $html .= "</table>";

        // ── Texto declaración ─────────────────────────────────────────
        $html .= "
<table width='100%' style='border-collapse:collapse; margin-bottom:8px; margin-top:4px;'>
    <tr>
        <td style='border:0.8px solid #000; padding:8px 10px; font-size:10px;
                   text-align:justify; line-height:1.6;'>
            POR MEDIO DEL PRESENTE DOCUMENTO, EL SUSCRITO DECLARA FORMALMENTE LA SOLICITUD Y
            JUSTIFICACIÓN DE LOS MATERIALES SOBRANTES DETALLADOS A CONTINUACIÓN, LOS CUALES SE
            REQUIEREN PARA LA CONTINUIDAD DE LA EJECUCIÓN DEL PROYECTO ESPECIFICADO EN LA PRESENTE
            SOLICITUD, CUMPLIENDO CON LO ESTABLECIDO EN EL MANUAL DE PROCEDIMIENTOS PARA CONTROL DE
            EXISTENCIAS DE MATERIALES SOBRANTES DE PROYECTOS, SE SOLICITA LA AUTORIZACIÓN PARA SU
            DEBIDA ENTREGA Y SALIDA DE INVENTARIO DE BODEGA SEGÚN EL SIGUIENTE DETALLE:
        </td>
    </tr>
</table>";

        // ── Tabla materiales con subtotales por objeto específico ─────
        $html .= "
<table width='100%' style='border-collapse:collapse;'>
    <thead>
        <tr>
            <th style='{$thStyle} width:5%;'>No.</th>
            <th style='{$thStyle} width:10%;'>COD PRESUP.</th>
            <th style='{$thStyle} width:35%;'>DESCRIPCIÓN</th>
            <th style='{$thStyle} width:12%;'>U. DE MEDIDA</th>
            <th style='{$thStyle} width:10%;'>CANTIDAD</th>
            <th style='{$thStyle} width:13%;'>PRECIO UNITARIO</th>
            <th style='{$thStyle} width:15%;'>SUBTOTAL</th>
        </tr>
    </thead>
    <tbody>";

        $i              = 1;
        $codigoActual   = null;
        $subtotalGrupo  = 0;

        foreach ($rows as $index => $r) {

            // ── Detectar cambio de grupo: emitir subtotal del grupo anterior ──
            if ($codigoActual !== null && $r['codigo'] !== $codigoActual) {
                $html .= "
        <tr>
            <td colspan='6' style='{$subLabel}'>
                SUBTOTAL [" . e($codigoActual) . "]
            </td>
            <td style='{$subStyle}'>
                $ " . number_format($subtotalGrupo, 4) . "
            </td>
        </tr>";
                $subtotalGrupo = 0;
            }

            $codigoActual   = $r['codigo'];
            $subtotalGrupo += $r['subtotal'];

            $html .= "
        <tr>
            <td style='{$tdC}'>{$i}</td>
            <td style='{$tdC}'>" . e($r['codigo']) . "</td>
            <td style='{$tdStyle}'>" . e($r['nombre']) . "</td>
            <td style='{$tdC}'>" . e($r['medida']) . "</td>
            <td style='{$tdC} font-weight:bold;'>" . number_format($r['cantidad']) . "</td>
            <td style='{$tdR}'>$ " . number_format($r['precio'], 4) . "</td>
            <td style='{$tdR}'>$ " . number_format($r['subtotal'], 4) . "</td>
        </tr>";
            $i++;
        }

        // ── Subtotal del último grupo ─────────────────────────────────
        if ($codigoActual !== null) {
            $html .= "
        <tr>
            <td colspan='6' style='{$subLabel}'>
                SUBTOTAL [" . e($codigoActual) . "]
            </td>
            <td style='{$subStyle}'>
                $ " . number_format($subtotalGrupo, 4) . "
            </td>
        </tr>";
        }

        // ── Total general ─────────────────────────────────────────────
        $html .= "
        <tr>
            <td colspan='6' style='font-weight:bold; font-size:11px; text-align:center;
                                    border:0.8px solid #000; padding:5px; background:#d9e1f2;'>
                TOTAL GENERAL
            </td>
            <td style='font-weight:bold; font-size:11px; text-align:right;
                        border:0.8px solid #000; padding:5px; background:#d9e1f2;'>
                $ " . number_format($granTotal, 4) . "
            </td>
        </tr>
    </tbody>
</table>";

        // ── Observaciones ─────────────────────────────────────────────
        $html .= "
<br>
<table width='100%' border='1' cellspacing='0' cellpadding='6'
       style='border-collapse:collapse; font-size:11px;'>
    <tr style='background:#f2f4f8;'>
        <td style='font-weight:bold;'>OBSERVACIONES:</td>
    </tr>
    <tr>
        <td style='height:40px; vertical-align:top;'>" . e($observaciones) . "</td>
    </tr>
</table>";

        // ── Espaciador antes de las firmas ────────────────────────────
        $html .= "<div style='height:{$informacionGeneral->px_firmas}px;
                   line-height:{$informacionGeneral->px_firmas}px;
                   font-size:1px;'>&nbsp;</div>";

        // ── Firmas 2x2 ────────────────────────────────────────────────
        $html .= "
<table width='100%' style='border-collapse:collapse; font-family:Arial,sans-serif; font-size:20px;'>

    <tr>
        <td style='width:50%; padding-right:40px; vertical-align:top;'>
            <strong>ELABORADO POR:</strong><br><br>
            <table width='100%' style='border-collapse:collapse;'>
                <tr>
                    <td style='width:15%;'>FIRMA:</td>
                    <td style='border-bottom:0.8px solid #000; width:85%;'>&nbsp;</td>
                </tr>
                <tr><td colspan='2' style='height:34px;'></td></tr>
                <tr>
                    <td>NOMBRE:</td>
                    <td style='border-bottom:0.8px solid #000;'>&nbsp;</td>
                </tr>
                <tr><td colspan='2' style='height:34px;'></td></tr>
                <tr>
                    <td>CARGO:</td>
                    <td style='border-bottom:0.8px solid #000;'>&nbsp;</td>
                </tr>
                <tr><td colspan='2' style='height:34px;'></td></tr>
                <tr>
                    <td colspan='2' style='text-align:center; font-size:18px;'>
                        RESPONSABLE DEL PROYECTO
                    </td>
                </tr>
            </table>
        </td>
        <td style='width:50%; padding-left:40px; vertical-align:top;'>
            <strong>REVISADO POR:</strong><br><br>
            <table width='100%' style='border-collapse:collapse;'>
                <tr>
                    <td style='width:15%;'>FIRMA:</td>
                    <td style='border-bottom:0.8px solid #000; width:85%;'>&nbsp;</td>
                </tr>
                <tr><td colspan='2' style='height:34px;'></td></tr>
                <tr>
                    <td>NOMBRE:</td>
                    <td style='border-bottom:0.8px solid #000;'>&nbsp;</td>
                </tr>
                <tr><td colspan='2' style='height:34px;'></td></tr>
                <tr>
                    <td>CARGO:</td>
                    <td style='border-bottom:0.8px solid #000;'>&nbsp;</td>
                </tr>
                <tr><td colspan='2' style='height:34px;'></td></tr>
                <tr>
                    <td colspan='2' style='text-align:center; font-size:18px;'>
                        [SUPERVISOR DEL PROYECTO O JEFE O ENCARGADO SOLICITANTE]
                    </td>
                </tr>
            </table>
        </td>
    </tr>

    <tr><td colspan='2' style='height:180px;'></td></tr>

    <tr>
        <td style='width:50%; padding-right:40px; padding-top:50px; vertical-align:top;'>
            <strong>AUTORIZADO POR:</strong><br><br>
            <table width='100%' style='border-collapse:collapse;'>
                <tr>
                    <td style='width:15%;'>FIRMA:</td>
                    <td style='border-bottom:0.8px solid #000; width:85%;'>&nbsp;</td>
                </tr>
                <tr><td colspan='2' style='height:34px;'></td></tr>
                <tr>
                    <td>NOMBRE:</td>
                    <td style='border-bottom:0.8px solid #000;'>&nbsp;</td>
                </tr>
                <tr><td colspan='2' style='height:34px;'></td></tr>
                <tr>
                    <td colspan='2' style='text-align:center; font-size:18px;'>
                        ALCALDE MUNICIPAL
                    </td>
                </tr>
            </table>
        </td>
        <td style='width:50%; padding-left:40px; padding-top:50px; vertical-align:top;'>
            <strong>ES CONFORME:</strong><br><br>
            <table width='100%' style='border-collapse:collapse;'>
                <tr>
                    <td style='width:15%;'>FIRMA:</td>
                    <td style='border-bottom:0.8px solid #000; width:85%;'>&nbsp;</td>
                </tr>
                <tr><td colspan='2' style='height:34px;'></td></tr>
                <tr>
                    <td>NOMBRE:</td>
                    <td style='border-bottom:0.8px solid #000;'>&nbsp;</td>
                </tr>
                <tr><td colspan='2' style='height:34px;'></td></tr>
                <tr>
                    <td>CARGO:</td>
                    <td style='border-bottom:0.8px solid #000;'>&nbsp;</td>
                </tr>
                <tr><td colspan='2' style='height:34px;'></td></tr>
                <tr>
                    <td colspan='2' style='text-align:center; font-size:18px;'>
                        [ENCARGADO DE BODEGA DE PROYECTO O RESPONSABLE ASIGNADO]
                    </td>
                </tr>
            </table>
        </td>
    </tr>

</table>";

        $mpdf = new \Mpdf\Mpdf([
            'tempDir'     => sys_get_temp_dir(),
            'format'      => 'LETTER',
            'orientation' => 'P',
        ]);
        $mpdf->SetTitle('GEAD-002-FORM');
        $mpdf->showImageErrors = false;
        $mpdf->setFooter("Página {PAGENO} de {nb}");
        $mpdf->WriteHTML($html, \Mpdf\HTMLParserMode::HTML_BODY);
        $mpdf->Output();
    }


    public function form003SolicitudPreview(Request $request)
    {
        $logoalcaldia       = 'images/logo.png';
        $informacionGeneral = InformacionGeneral::where('id', 1)->first();
        $fechaFormat        = Carbon::parse($request->fecha)->format('d/m/Y');

        $numero        = $request->numero        ?? '';
        $nombreOrigen  = $request->nombre_origen ?? '';
        $tipodestino   = $request->tipodestino   ?? '';
        $justificacion = $request->justificacion ?? '';
        $depto         = $request->depto         ?? '';
        $nombreSolic   = $request->nombre        ?? '';
        $cargoSolic    = $request->cargo         ?? '';
        $observaciones = $request->observaciones ?? '';
        $materiales    = json_decode($request->materiales, true) ?? [];

        $firma1 = $request->firma1 ?? '';
        $firma2 = $request->firma2 ?? '';
        $firma3 = $request->firma3 ?? '';

        $rows = [];

        foreach ($materiales as $mat) {
            $idEntDet = $mat['id_entrada_detalle'] ?? null;
            $codigo   = '—';
            $medida   = '—';
            $precio   = 0;

            if ($idEntDet) {
                $entDet = EntradasDetalle::with([
                    'material.unidadMedida',
                    'material.objetoEspecifico',
                ])->find($idEntDet);

                if ($entDet) {
                    $codigo = $entDet->material?->objetoEspecifico?->codigo
                        ?? $entDet->codigo ?? '—';
                    $medida = $entDet->material?->unidadMedida?->nombre ?? '—';
                    $precio = $entDet->precio ?? 0;
                }
            }

            $cantidad = (int) ($mat['cantidad'] ?? 0);
            $subtotal = $cantidad * $precio;

            $rows[] = [
                'codigo'   => $codigo,
                'nombre'   => $mat['nombre'] ?? '—',
                'medida'   => $medida,
                'cantidad' => $cantidad,
                'precio'   => $precio,
                'subtotal' => $subtotal,
            ];
        }

        // ── Ordenar por código de objeto específico ───────────────────
        usort($rows, fn($a, $b) => strcmp($a['codigo'], $b['codigo']));

        $granTotal = array_sum(array_column($rows, 'subtotal'));

        $thStyle  = "font-weight:bold; font-size:10px; border:0.8px solid #000; padding:4px; background:#d9e1f2; text-align:center;";
        $tdStyle  = "font-size:10px; border:0.8px solid #000; padding:4px;";
        $tdC      = $tdStyle . " text-align:center;";
        $tdR      = $tdStyle . " text-align:right;";
        $subStyle = "font-weight:bold; font-size:10px; text-align:right; border:0.8px solid #000; padding:4px; background:#f2f4f8;";
        $subLabel = "font-weight:bold; font-size:10px; text-align:center; border:0.8px solid #000; padding:4px; background:#f2f4f8;";

        // ── Encabezado ────────────────────────────────────────────────
        $html = "
<table width='100%' style='border-collapse:collapse; font-family:Arial,sans-serif;'>
    <tr>
        <td style='width:25%; border:0.8px solid #000; padding:6px 8px;'>
            <table width='100%'>
                <tr>
                    <td style='width:35%; text-align:left;'>
                        <img src='{$logoalcaldia}' style='height:38px'>
                    </td>
                    <td style='width:65%; text-align:left; color:#104e8c;
                                font-size:12px; font-weight:bold; line-height:1.3;'>
                        SANTA ANA NORTE<br>EL SALVADOR
                    </td>
                </tr>
            </table>
        </td>
        <td style='width:50%; border-top:0.8px solid #000; border-bottom:0.8px solid #000;
                   padding:6px 8px; text-align:center; font-size:13px; font-weight:bold;'>
            FORMULARIO DE SOLICITUD DE MATERIALES SOBRANTES<br>
            PARA MANTENIMIENTO DE INSTALACIONES MUNICIPALES
        </td>
        <td style='width:25%; border:0.8px solid #000; padding:0; vertical-align:top;'>
            <table width='100%' style='font-size:10px;'>
                <tr>
                    <td width='40%' style='border-right:0.8px solid #000;
                                           border-bottom:0.8px solid #000; padding:4px 6px;'>
                        <strong>Código:</strong>
                    </td>
                    <td width='60%' style='border-bottom:0.8px solid #000;
                                           padding:4px 6px; text-align:center;'>
                        GEAD-003-FORM
                    </td>
                </tr>
                <tr>
                    <td style='border-right:0.8px solid #000;
                               border-bottom:0.8px solid #000; padding:4px 6px;'>
                        <strong>Versión:</strong>
                    </td>
                    <td style='border-bottom:0.8px solid #000;
                               padding:4px 6px; text-align:center;'>000</td>
                </tr>
                <tr>
                    <td style='border-right:0.8px solid #000; padding:4px 6px;'>
                        <strong>Fecha de vigencia:</strong>
                    </td>
                    <td style='padding:4px 6px; text-align:center;'>22/05/2026</td>
                </tr>
            </table>
        </td>
    </tr>
</table><br>";

        // ── No. Solicitud y Fecha ─────────────────────────────────────
        $html .= "
<table width='100%' style='border-collapse:collapse; margin-bottom:4px; margin-top:6px;'>
    <tr>
        <td style='width:20%; border:0.8px solid #ccc; padding:5px 8px;
                   font-size:11px; font-weight:bold; background:#f5f5f5;'>
            NO. DE SOLICITUD:
        </td>
        <td style='width:44%; border:0.8px solid #ccc; padding:5px 8px; font-size:11px;'>
            " . e($numero) . "
        </td>
        <td style='width:5%; border:none;'></td>
        <td style='width:13%; border:0.8px solid #000; padding:5px 8px;
                   font-size:11px; font-weight:bold; text-align:center; background:#f5f5f5;'>
            FECHA:
        </td>
        <td style='width:18%; border:0.8px solid #000; padding:5px 8px;
                   font-size:11px; text-align:center;'>
            {$fechaFormat}
        </td>
    </tr>
</table>";

        // ── Campos ────────────────────────────────────────────────────
        $campos = [
            'PROYECTO DE ORIGEN DE LOS MATERIALES' => $nombreOrigen,
            'TIPO DE DESTINO / USO'                => $tipodestino,
            'JUSTIFICACIÓN DEL DESTINO'            => $justificacion,
            'UNIDAD SOLICITANTE'                   => $depto,
            'NOMBRE DE SOLICITANTE'                => $nombreSolic,
            'CARGO DE SOLICITANTE'                 => $cargoSolic,
        ];

        $html .= "<table width='100%' style='border-collapse:collapse; margin-bottom:4px;'>";
        foreach ($campos as $label => $valor) {
            $html .= "
        <tr>
            <td style='width:25%; border:0.8px solid #ccc; padding:5px 8px;
                       font-size:11px; font-weight:bold; background:#f5f5f5;'>
                {$label}:
            </td>
            <td style='border:0.8px solid #ccc; padding:5px 8px; font-size:11px;'>
                " . e($valor) . "
            </td>
        </tr>";
        }
        $html .= "</table>";

        // ── Texto declaración ─────────────────────────────────────────
        $html .= "
<table width='100%' style='border-collapse:collapse; margin-bottom:8px; margin-top:4px;'>
    <tr>
        <td style='border:0.8px solid #000; padding:8px 10px; font-size:10px;
                   text-align:justify; line-height:1.6;'>
            POR MEDIO DEL PRESENTE DOCUMENTO, EL SUSCRITO DECLARA FORMALMENTE LA SOLICITUD Y
            JUSTIFICACIÓN DE LOS MATERIALES SOBRANTES DETALLADOS A CONTINUACIÓN, LOS CUALES SE
            REQUIEREN PARA LA EJECUCIÓN DEL MANTENIMIENTO DE INFRAESTRUCTURA DE LA MUNICIPALIDAD
            ESPECIFICADO EN LA PRESENTE SOLICITUD, CUMPLIENDO CON LO ESTABLECIDO EN EL MANUAL DE
            PROCEDIMIENTOS PARA CONTROL DE EXISTENCIAS DE MATERIALES SOBRANTES DE PROYECTOS,
            SE SOLICITA LA AUTORIZACIÓN PARA SU DEBIDA ENTREGA Y SALIDA DE INVENTARIO DE BODEGA
            SEGÚN EL SIGUIENTE DETALLE:
        </td>
    </tr>
</table>";

        // ── Tabla materiales con subtotales por objeto específico ─────
        $html .= "
<table width='100%' style='border-collapse:collapse;'>
    <thead>
        <tr>
            <th style='{$thStyle} width:5%;'>No.</th>
            <th style='{$thStyle} width:10%;'>COD PRESUP.</th>
            <th style='{$thStyle} width:35%;'>DESCRIPCIÓN</th>
            <th style='{$thStyle} width:12%;'>U. DE MEDIDA</th>
            <th style='{$thStyle} width:10%;'>CANTIDAD</th>
            <th style='{$thStyle} width:13%;'>PRECIO UNITARIO</th>
            <th style='{$thStyle} width:15%;'>SUBTOTAL</th>
        </tr>
    </thead>
    <tbody>";

        $i             = 1;
        $codigoActual  = null;
        $subtotalGrupo = 0;

        foreach ($rows as $r) {

            // ── Detectar cambio de grupo: emitir subtotal del anterior ──
            if ($codigoActual !== null && $r['codigo'] !== $codigoActual) {
                $html .= "
        <tr>
            <td colspan='6' style='{$subLabel}'>
                SUBTOTAL [" . e($codigoActual) . "]
            </td>
            <td style='{$subStyle}'>
                $ " . number_format($subtotalGrupo, 4) . "
            </td>
        </tr>";
                $subtotalGrupo = 0;
            }

            $codigoActual   = $r['codigo'];
            $subtotalGrupo += $r['subtotal'];

            $html .= "
        <tr>
            <td style='{$tdC}'>{$i}</td>
            <td style='{$tdC}'>" . e($r['codigo']) . "</td>
            <td style='{$tdStyle}'>" . e($r['nombre']) . "</td>
            <td style='{$tdC}'>" . e($r['medida']) . "</td>
            <td style='{$tdC} font-weight:bold;'>" . number_format($r['cantidad']) . "</td>
            <td style='{$tdR}'>$ " . number_format($r['precio'], 4) . "</td>
            <td style='{$tdR}'>$ " . number_format($r['subtotal'], 4) . "</td>
        </tr>";
            $i++;
        }

        // ── Subtotal del último grupo ─────────────────────────────────
        if ($codigoActual !== null) {
            $html .= "
        <tr>
            <td colspan='6' style='{$subLabel}'>
                SUBTOTAL [" . e($codigoActual) . "]
            </td>
            <td style='{$subStyle}'>
                $ " . number_format($subtotalGrupo, 4) . "
            </td>
        </tr>";
        }

        // ── Total general ─────────────────────────────────────────────
        $html .= "
        <tr>
            <td colspan='6' style='font-weight:bold; font-size:11px; text-align:center;
                                    border:0.8px solid #000; padding:5px; background:#d9e1f2;'>
                TOTAL GENERAL
            </td>
            <td style='font-weight:bold; font-size:11px; text-align:right;
                        border:0.8px solid #000; padding:5px; background:#d9e1f2;'>
                $ " . number_format($granTotal, 4) . "
            </td>
        </tr>
    </tbody>
</table>";

        // ── Observaciones ─────────────────────────────────────────────
        $html .= "
<br>
<table width='100%' border='1' cellspacing='0' cellpadding='6'
       style='border-collapse:collapse; font-size:11px;'>
    <tr style='background:#f2f4f8;'>
        <td style='font-weight:bold;'>OBSERVACIONES:</td>
    </tr>
    <tr>
        <td style='height:40px; vertical-align:top;'>" . e($observaciones) . "</td>
    </tr>
</table>";

        // ── Firmas ────────────────────────────────────────────────────
        $px = $informacionGeneral->px_firmas ?? 40;

        $html .= "
<table width='100%' style='border-collapse:collapse; font-family:Arial,sans-serif;
                            margin-top:{$px}px; font-size:20px; line-height:1.6;'>
    <tr>
        <td style='width:50%; padding-right:40px; vertical-align:top;'>
            <strong>ELABORADO POR:</strong><br><br>
            <table width='100%' style='border-collapse:collapse;'>
                <tr>
                    <td style='width:15%;'>FIRMA:</td>
                    <td style='border-bottom:0.8px solid #000; width:85%;'>&nbsp;</td>
                </tr>
                <tr><td colspan='2' style='height:28px;'></td></tr>
                <tr>
                    <td>NOMBRE:</td>
                    <td style='border-bottom:0.8px solid #000;'>&nbsp;</td>
                </tr>
                <tr><td colspan='2' style='height:28px;'></td></tr>
                <tr>
                    <td>CARGO:</td>
                    <td style='border-bottom:0.8px solid #000;'>&nbsp;</td>
                </tr>
                <tr><td colspan='2' style='height:28px;'></td></tr>
                <tr>
                    <td colspan='2' style='text-align:center; font-size:19px;'>$firma1</td>
                </tr>
            </table>
        </td>
        <td style='width:50%; padding-left:40px; vertical-align:top;'>
            <strong>AUTORIZADO POR:</strong><br><br>
            <table width='100%' style='border-collapse:collapse;'>
                <tr>
                    <td style='width:15%;'>FIRMA:</td>
                    <td style='border-bottom:0.8px solid #000; width:85%;'>&nbsp;</td>
                </tr>
                <tr><td colspan='2' style='height:28px;'></td></tr>
                <tr>
                    <td>NOMBRE:</td>
                    <td style='border-bottom:0.8px solid #000;'>&nbsp;</td>
                </tr>
                <tr><td colspan='2' style='height:28px;'></td></tr>
                <tr>
                    <td>CARGO:</td>
                    <td style='border-bottom:0.8px solid #000;'>&nbsp;</td>
                </tr>
                <tr><td colspan='2' style='height:28px;'></td></tr>
                <tr>
                    <td colspan='2' style='text-align:center; font-size:19px;'>$firma2</td>
                </tr>
            </table>
        </td>
    </tr>

    <tr><td colspan='2' style='height:50px;'></td></tr>

    <tr>
        <td colspan='2' style='vertical-align:top; text-align:center;'>
            <strong>ES CONFORME:</strong><br><br>
            <table width='50%' style='border-collapse:collapse; margin:0 auto;'>
                <tr>
                    <td style='width:15%;'>FIRMA:</td>
                    <td style='border-bottom:0.8px solid #000; width:85%;'>&nbsp;</td>
                </tr>
                <tr><td colspan='2' style='height:28px;'></td></tr>
                <tr>
                    <td>NOMBRE:</td>
                    <td style='border-bottom:0.8px solid #000;'>&nbsp;</td>
                </tr>
                <tr><td colspan='2' style='height:28px;'></td></tr>
                <tr>
                    <td>CARGO:</td>
                    <td style='border-bottom:0.8px solid #000;'>&nbsp;</td>
                </tr>
                <tr><td colspan='2' style='height:28px;'></td></tr>
                <tr>
                    <td colspan='2' style='text-align:center; font-size:19px;'>
                        $firma3
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>";

        $mpdf = new \Mpdf\Mpdf([
            'tempDir'     => sys_get_temp_dir(),
            'format'      => 'LETTER',
            'orientation' => 'P',
        ]);
        $mpdf->SetTitle('GEAD-003-FORM');
        $mpdf->showImageErrors = false;
        $mpdf->setFooter("Página {PAGENO} de {nb}");
        $mpdf->WriteHTML($html, \Mpdf\HTMLParserMode::HTML_BODY);
        $mpdf->Output();
    }



    public function actaRecepcionPreview(Request $request)
    {
        $proyecto           = Tipoproyecto::find($request->idproy);
        $logoalcaldia       = 'images/logo.png';
        $fechaFormat        = Carbon::parse($request->fecha)->format('d/m/Y');
        $informacionGeneral = InformacionGeneral::where('id', 1)->first();

        $numero        = $request->numero        ?? '';
        $referencia    = $request->referencia    ?? '';
        $depto         = $request->depto         ?? '';
        $nombre        = $request->nombre        ?? '';
        $cargo         = $request->cargo         ?? '';
        $observaciones = $request->observaciones ?? '';
        $tipodestino   = $request->tipodestino   ?? '';

        $nombreFirma1  = $request->nombrefirma1  ?? '';
        $nombreFirma2  = $request->nombrefirma2  ?? '';

        $materiales = json_decode($request->materiales, true) ?? [];

        $rows = [];
        foreach ($materiales as $mat) {
            $idEntDet = $mat['id_entrada_detalle'] ?? null;
            $codigo   = '—';
            $medida   = '—';
            $precio   = 0;

            if ($idEntDet) {
                $entDet = EntradasDetalle::with([
                    'material.unidadMedida',
                    'material.objetoEspecifico',
                ])->find($idEntDet);

                if ($entDet) {
                    $codigo = $entDet->material?->objetoEspecifico?->codigo
                        ?? $entDet->codigo
                        ?? '—';
                    $medida = $entDet->material?->unidadMedida?->nombre ?? '—';
                    $precio = $entDet->precio ?? 0;
                }
            }

            $cantidad = (int) ($mat['cantidad'] ?? 0);
            $subtotal = $cantidad * $precio;

            $rows[] = [
                'codigo'   => $codigo,
                'nombre'   => $mat['nombre'] ?? '—',
                'medida'   => $medida,
                'cantidad' => $cantidad,
                'precio'   => $precio,
                'subtotal' => $subtotal,
            ];
        }

        $html = $this->buildActaHTML(
            $logoalcaldia,
            $proyecto->nombre ?? '—',
            $fechaFormat,
            $numero,
            $referencia,
            $tipodestino,
            $depto,
            $nombre,
            $cargo,
            $observaciones,
            $rows,
            $informacionGeneral,
            $nombreFirma1,
            $nombreFirma2
        );

        $mpdf = new \Mpdf\Mpdf([
            'tempDir'     => sys_get_temp_dir(),
            'format'      => 'LETTER',
            'orientation' => 'P',
        ]);
        $mpdf->SetTitle('GEAD-002-ACTA Preview');
        $mpdf->showImageErrors = false;
        $mpdf->setFooter("Página {PAGENO} de {nb}");
        $mpdf->WriteHTML($html, \Mpdf\HTMLParserMode::HTML_BODY);
        $mpdf->Output();
    }

    private function buildActaHTML(
        $logo, $nombreProyecto, $fecha,
        $numero, $referencia, $tipodestino,
        $depto, $nombreSolic, $cargoSolic,
        $observaciones, $rows, $informacionGeneral,
        $nombreFirma1, $nombreFirma2
    ) {
        $thStyle  = "font-weight:bold; font-size:10px; border:0.8px solid #000; padding:4px; background:#d9e1f2; text-align:center;";
        $tdStyle  = "font-size:10px; border:0.8px solid #000; padding:4px;";
        $tdC      = $tdStyle . " text-align:center;";
        $tdR      = $tdStyle . " text-align:right;";
        $subStyle = "font-weight:bold; font-size:10px; text-align:right; border:0.8px solid #000; padding:4px; background:#f2f4f8;";
        $subLabel = "font-weight:bold; font-size:10px; text-align:center; border:0.8px solid #000; padding:4px; background:#f2f4f8;";

        // ── Ordenar por código de objeto específico ───────────────────
        usort($rows, fn($a, $b) => strcmp($a['codigo'], $b['codigo']));

        $granTotal = array_sum(array_column($rows, 'subtotal'));

        // ── Encabezado ────────────────────────────────────────────────
        $html = "
<table width='100%' style='border-collapse:collapse; font-family:Arial,sans-serif;'>
    <tr>
        <td style='width:25%; border:0.8px solid #000; padding:6px 8px;'>
            <table width='100%'>
                <tr>
                    <td style='width:35%; text-align:left;'>
                        <img src='{$logo}' style='height:38px'>
                    </td>
                    <td style='width:65%; text-align:left; color:#104e8c;
                                font-size:12px; font-weight:bold; line-height:1.3;'>
                        SANTA ANA NORTE<br>EL SALVADOR
                    </td>
                </tr>
            </table>
        </td>
        <td style='width:50%; border-top:0.8px solid #000; border-bottom:0.8px solid #000;
                   padding:6px 8px; text-align:center; font-size:15px; font-weight:bold;'>
            ACTA DE RECEPCIÓN DE<br>MATERIALES SOBRANTES
        </td>
        <td style='width:25%; border:0.8px solid #000; padding:0; vertical-align:top;'>
            <table width='100%' style='font-size:10px;'>
                <tr>
                    <td width='40%' style='border-right:0.8px solid #000;
                                           border-bottom:0.8px solid #000; padding:4px 6px;'>
                        <strong>Código:</strong>
                    </td>
                    <td width='60%' style='border-bottom:0.8px solid #000;
                                           padding:4px 6px; text-align:center;'>GEAD-002-ACTA</td>
                </tr>
                <tr>
                    <td style='border-right:0.8px solid #000;
                               border-bottom:0.8px solid #000; padding:4px 6px;'>
                        <strong>Versión:</strong>
                    </td>
                    <td style='border-bottom:0.8px solid #000;
                               padding:4px 6px; text-align:center;'>000</td>
                </tr>
                <tr>
                    <td style='border-right:0.8px solid #000; padding:4px 6px;'>
                        <strong>Fecha de vigencia:</strong>
                    </td>
                    <td style='padding:4px 6px; text-align:center;'>22/05/2026</td>
                </tr>
            </table>
        </td>
    </tr>
</table><br>";

        // ── No. Acta y Fecha ──────────────────────────────────────────
        $html .= "
<table width='100%' style='border-collapse:collapse; margin-bottom:4px; margin-top:6px;'>
    <tr>
        <td style='width:20%; border:0.8px solid #ccc; padding:5px 8px;
                   font-size:11px; font-weight:bold; background:#f5f5f5;'>
            NO. DE ACTA DE RECEPCIÓN:
        </td>
        <td style='width:44%; border:0.8px solid #ccc; padding:5px 8px; font-size:11px;'>
            " . e($numero) . "
        </td>
        <td style='width:5%; border:none;'></td>
        <td style='width:13%; border:0.8px solid #000; padding:5px 8px;
                   font-size:11px; font-weight:bold; text-align:center; background:#f5f5f5;'>
            FECHA:
        </td>
        <td style='width:18%; border:0.8px solid #000; padding:5px 8px;
                   font-size:11px; text-align:center;'>
            {$fecha}
        </td>
    </tr>
</table>";

        // ── Campos del acta ───────────────────────────────────────────
        $campos = [
            'PROYECTO DE ORIGEN DE LOS MATERIALES' => $nombreProyecto,
            'REFERENCIA DE LA SOLICITUD'            => $referencia,
            'TIPO DE DESTINO / USO'                 => $tipodestino,
            'UNIDAD SOLICITANTE'                    => $depto,
            'NOMBRE DE SOLICITANTE'                 => $nombreSolic,
            'CARGO DE SOLICITANTE'                  => $cargoSolic,
        ];

        $html .= "<table width='100%' style='border-collapse:collapse; margin-bottom:4px;'>";
        foreach ($campos as $label => $valor) {
            $html .= "
    <tr>
        <td style='width:25%; border:0.8px solid #ccc; padding:5px 8px;
                   font-size:11px; font-weight:bold; background:#f5f5f5;'>
            {$label}:
        </td>
        <td style='border:0.8px solid #ccc; padding:5px 8px; font-size:11px;'>
            " . e($valor) . "
        </td>
    </tr>";
        }
        $html .= "</table>";

        // ── Texto declaración ─────────────────────────────────────────
        $html .= "
<table width='100%' style='border-collapse:collapse; margin-bottom:8px; margin-top:4px;'>
    <tr>
        <td style='border:0.8px solid #000; padding:8px 10px; font-size:10px;
                   text-align:justify; line-height:1.6;'>
            POR MEDIO DEL PRESENTE, EL RESPONSABLE DE LA BODEGA DE PROYECTOS O RESPONSABLE ASIGNADO
            HACE ENTREGA FORMAL DE LOS MATERIALES DETALLADOS EN EL FORMULARIO DE SOLICITUD. POR SU PARTE,
            EL RESPONSABLE QUE RECIBE DECLARA LA RECEPCIÓN CONFORME DE LOS MISMOS, ASUMIENDO LA CUSTODIA
            Y RESPONSABILIDAD PARA SU USO EXCLUSIVO EN EL DESTINO ESPECIFICADO Y SE COMPROMETE A REALIZAR
            LOS REGISTROS DE CONSUMO CORRESPONDIENTES.
        </td>
    </tr>
</table>";

        // ── Tabla materiales con subtotales por objeto específico ─────
        $html .= "
<table width='100%' style='border-collapse:collapse;'>
    <thead>
        <tr>
            <th style='{$thStyle} width:5%;'>No.</th>
            <th style='{$thStyle} width:10%;'>COD PRESUP.</th>
            <th style='{$thStyle} width:35%;'>DESCRIPCIÓN</th>
            <th style='{$thStyle} width:12%;'>U. DE MEDIDA</th>
            <th style='{$thStyle} width:10%;'>CANTIDAD</th>
            <th style='{$thStyle} width:13%;'>PRECIO UNITARIO</th>
            <th style='{$thStyle} width:15%;'>SUBTOTAL</th>
        </tr>
    </thead>
    <tbody>";

        $i             = 1;
        $codigoActual  = null;
        $subtotalGrupo = 0;

        foreach ($rows as $r) {

            // ── Detectar cambio de grupo: emitir subtotal del anterior ──
            if ($codigoActual !== null && $r['codigo'] !== $codigoActual) {
                $html .= "
        <tr>
            <td colspan='6' style='{$subLabel}'>
                SUBTOTAL [" . e($codigoActual) . "]
            </td>
            <td style='{$subStyle}'>
                $ " . number_format($subtotalGrupo, 4) . "
            </td>
        </tr>";
                $subtotalGrupo = 0;
            }

            $codigoActual   = $r['codigo'];
            $subtotalGrupo += $r['subtotal'];

            $html .= "
        <tr>
            <td style='{$tdC}'>{$i}</td>
            <td style='{$tdC}'>" . e($r['codigo']) . "</td>
            <td style='{$tdStyle}'>" . e($r['nombre']) . "</td>
            <td style='{$tdC}'>" . e($r['medida']) . "</td>
            <td style='{$tdC} font-weight:bold;'>" . number_format($r['cantidad']) . "</td>
            <td style='{$tdR}'>$ " . number_format($r['precio'], 4) . "</td>
            <td style='{$tdR}'>$ " . number_format($r['subtotal'], 4) . "</td>
        </tr>";
            $i++;
        }

        // ── Subtotal del último grupo ─────────────────────────────────
        if ($codigoActual !== null) {
            $html .= "
        <tr>
            <td colspan='6' style='{$subLabel}'>
                SUBTOTAL [" . e($codigoActual) . "]
            </td>
            <td style='{$subStyle}'>
                $ " . number_format($subtotalGrupo, 4) . "
            </td>
        </tr>";
        }

        // ── Total general ─────────────────────────────────────────────
        $html .= "
        <tr>
            <td colspan='6' style='font-weight:bold; font-size:11px; text-align:center;
                                    border:0.8px solid #000; padding:5px; background:#d9e1f2;'>
                TOTAL GENERAL
            </td>
            <td style='font-weight:bold; font-size:11px; text-align:right;
                        border:0.8px solid #000; padding:5px; background:#d9e1f2;'>
                $ " . number_format($granTotal, 4) . "
            </td>
        </tr>
    </tbody>
</table>";

        // ── Observaciones ─────────────────────────────────────────────
        $html .= "
<br>
<table width='100%' border='1' cellspacing='0' cellpadding='6'
       style='border-collapse:collapse; font-size:11px;'>
    <tr style='background:#f2f4f8;'>
        <td style='font-weight:bold;'>OBSERVACIONES:</td>
    </tr>
    <tr>
        <td style='height:40px; vertical-align:top;'>" . e($observaciones) . "</td>
    </tr>
</table>";

        // ── Espaciador antes de las firmas ────────────────────────────
        $px = $informacionGeneral->px_firmas ?? 40;
        $html .= "<div style='height:{$px}px; line-height:{$px}px; font-size:1px;'>&nbsp;</div>";

        // ── Firmas ────────────────────────────────────────────────────
        $html .= "
<table width='100%' style='border-collapse:collapse; font-family:Arial,sans-serif;
                            font-size:19px; line-height:1.6;'>
    <tr>
        <td style='width:50%; padding-right:40px; vertical-align:top;'>
            <strong style='font-size:21px;'>ENTREGADO POR:</strong><br><br>
            <table width='100%' style='border-collapse:collapse;'>
                <tr>
                    <td style='width:18%; padding-bottom:12px;'>FIRMA:</td>
                    <td style='border-bottom:0.8px solid #000; width:82%;'>&nbsp;</td>
                </tr>
                <tr><td colspan='2' style='height:42px;'></td></tr>
                <tr>
                    <td style='padding-bottom:12px;'>NOMBRE:</td>
                    <td style='border-bottom:0.8px solid #000;'>&nbsp;</td>
                </tr>
                <tr><td colspan='2' style='height:42px;'></td></tr>
                <tr>
                    <td style='padding-bottom:12px;'>CARGO:</td>
                    <td style='border-bottom:0.8px solid #000;'>&nbsp;</td>
                </tr>
                <tr><td colspan='2' style='height:42px;'></td></tr>
                <tr>
                    <td colspan='2' style='text-align:center; font-size:19px; line-height:1.5;'>
                        $nombreFirma1
                    </td>
                </tr>
            </table>
        </td>
        <td style='width:50%; padding-left:40px; vertical-align:top;'>
            <strong style='font-size:21px;'>RECIBIDO POR:</strong><br><br>
            <table width='100%' style='border-collapse:collapse;'>
                <tr>
                    <td style='width:18%; padding-bottom:12px;'>FIRMA:</td>
                    <td style='border-bottom:0.8px solid #000; width:82%;'>&nbsp;</td>
                </tr>
                <tr><td colspan='2' style='height:42px;'></td></tr>
                <tr>
                    <td style='padding-bottom:12px;'>NOMBRE:</td>
                    <td style='border-bottom:0.8px solid #000;'>&nbsp;</td>
                </tr>
                <tr><td colspan='2' style='height:42px;'></td></tr>
                <tr>
                    <td style='padding-bottom:12px;'>CARGO:</td>
                    <td style='border-bottom:0.8px solid #000;'>&nbsp;</td>
                </tr>
                <tr><td colspan='2' style='height:42px;'></td></tr>
                <tr>
                    <td colspan='2' style='text-align:center; font-size:19px; line-height:1.5;'>
                        $nombreFirma2
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>";

        return $html;
    }






    public function form001ReservaPreview(Request $request)
    {
        $logoalcaldia       = 'images/logo.png';
        $informacionGeneral = InformacionGeneral::where('id', 1)->first();
        $fechaFormat        = Carbon::parse($request->fecha)->format('d/m/Y');

        $numero         = $request->numero          ?? '';
        $nombreOrigen   = $request->nombre_origen   ?? '';
        $proyectoFormul = $request->proyecto_formul ?? '';
        $justificacion  = $request->justificacion   ?? '';
        $depto          = $request->depto           ?? '';
        $nombreSolic    = $request->nombre          ?? '';
        $cargoSolic     = $request->cargo           ?? '';
        $observaciones  = $request->observaciones   ?? '';
        $materiales     = json_decode($request->materiales, true) ?? [];

        // ════════════════════════════════════════════════════════════════
        // PASO 1 — Agrupar items iguales (mismo id_entrada_detalle + precio)
        // ════════════════════════════════════════════════════════════════
        $agrupado = [];
        foreach ($materiales as $mat) {
            $idEntDet = $mat['id_entrada_detalle'] ?? null;
            if (!$idEntDet) continue;

            $cantidad = (int) ($mat['cantidad'] ?? 0);
            if ($cantidad <= 0) continue;

            $entDet = EntradasDetalle::with([
                'material.unidadMedida',
                'material.objetoEspecifico',
            ])->find($idEntDet);

            if (!$entDet) continue;

            $precio = (float) ($entDet->precio ?? 0);
            $key    = $idEntDet . '|' . number_format($precio, 4, '.', '');

            if (!isset($agrupado[$key])) {
                $agrupado[$key] = [
                    'id_entrada_detalle' => $idEntDet,
                    'entDet'             => $entDet,
                    'nombre'             => $mat['nombre'] ?? ($entDet->nombre ?? '—'),
                    'precio'             => $precio,
                    'cantidad'           => 0,
                ];
            }
            $agrupado[$key]['cantidad'] += $cantidad;
        }

        // ════════════════════════════════════════════════════════════════
        // PASO 2 — Validar stock disponible
        // ════════════════════════════════════════════════════════════════
        $errores = [];
        foreach ($agrupado as $g) {
            $idEntDet = $g['id_entrada_detalle'];
            $entDet   = $g['entDet'];

            $cantidadInicial = (int) $entDet->cantidad_inicial;

            $totalSalidas = (int) \DB::table('salidas_detalle')
                ->where('id_entrada_detalle', $idEntDet)
                ->sum('cantidad_salida');

            $totalReservas = (int) \DB::table('reservas')
                ->where('id_entrada_detalle', $idEntDet)
                ->where('despachado', false)
                ->sum('cantidad');

            $disponible = $cantidadInicial - $totalSalidas - $totalReservas;

            if ($g['cantidad'] > $disponible) {
                $errores[] = [
                    'nombre'     => $g['nombre'],
                    'solicitado' => $g['cantidad'],
                    'disponible' => max(0, $disponible),
                    'inicial'    => $cantidadInicial,
                    'salidas'    => $totalSalidas,
                    'reservas'   => $totalReservas,
                ];
            }
        }

        if (!empty($errores)) {
            $this->renderErrorStockPdf($errores);
            return;
        }

        // ════════════════════════════════════════════════════════════════
        // PASO 3 — Construir filas ordenadas por objeto específico
        // ════════════════════════════════════════════════════════════════
        $rows = [];

        foreach ($agrupado as $g) {
            $entDet = $g['entDet'];

            $codigoObjEspec = $entDet->material?->objetoEspecifico?->codigo ?? '—';
            $nombreObjEspec = $entDet->material?->objetoEspecifico?->nombre ?? '';
            $medida         = $entDet->material?->unidadMedida?->nombre ?? '—';
            $subtotal       = $g['cantidad'] * $g['precio'];

            $rows[] = [
                'codigo'          => $codigoObjEspec,
                'nombre_obj_spec' => $nombreObjEspec,
                'nombre'          => $g['nombre'],
                'medida'          => $medida,
                'cantidad'        => $g['cantidad'],
                'precio'          => $g['precio'],
                'subtotal'        => $subtotal,
            ];
        }

        // ── Ordenar por código de objeto específico ───────────────────
        usort($rows, fn($a, $b) => strcmp($a['codigo'], $b['codigo']));

        $granTotal = array_sum(array_column($rows, 'subtotal'));

        $thStyle  = "font-weight:bold; font-size:10px; border:0.8px solid #000; padding:4px; background:#d9e1f2; text-align:center;";
        $tdStyle  = "font-size:10px; border:0.8px solid #000; padding:4px;";
        $tdC      = $tdStyle . " text-align:center;";
        $tdR      = $tdStyle . " text-align:right;";
        $subStyle = "font-weight:bold; font-size:10px; text-align:right; border:0.8px solid #000; padding:4px; background:#f2f4f8;";
        $subLabel = "font-weight:bold; font-size:10px; text-align:center; border:0.8px solid #000; padding:4px; background:#f2f4f8;";

        // ── Encabezado ────────────────────────────────────────────────
        $html = "
<table width='100%' style='border-collapse:collapse; font-family:Arial,sans-serif;'>
<tr>
    <td style='width:25%; border:0.8px solid #000; padding:6px 8px;'>
        <table width='100%'>
            <tr>
                <td style='width:35%; text-align:left;'>
                    <img src='{$logoalcaldia}' style='height:38px'>
                </td>
                <td style='width:65%; text-align:left; color:#104e8c;
                            font-size:12px; font-weight:bold; line-height:1.3;'>
                    SANTA ANA NORTE<br>EL SALVADOR
                </td>
            </tr>
        </table>
    </td>
    <td style='width:50%; border-top:0.8px solid #000; border-bottom:0.8px solid #000;
               padding:6px 8px; text-align:center; font-size:13px; font-weight:bold;'>
        FORMULARIO DE RESERVA DE MATERIALES SOBRANTES<br>
        PARA PROYECTO DE INVERSIÓN PÚBLICA
    </td>
    <td style='width:25%; border:0.8px solid #000; padding:0; vertical-align:top;'>
        <table width='100%' style='font-size:10px;'>
            <tr>
                <td width='40%' style='border-right:0.8px solid #000;
                                       border-bottom:0.8px solid #000; padding:4px 6px;'>
                    <strong>Código:</strong>
                </td>
                <td width='60%' style='border-bottom:0.8px solid #000;
                                       padding:4px 6px; text-align:center;'>
                    GEAD-001-FORM
                </td>
            </tr>
            <tr>
                <td style='border-right:0.8px solid #000;
                           border-bottom:0.8px solid #000; padding:4px 6px;'>
                    <strong>Versión:</strong>
                </td>
                <td style='border-bottom:0.8px solid #000;
                           padding:4px 6px; text-align:center;'>000</td>
            </tr>
            <tr>
                <td style='border-right:0.8px solid #000; padding:4px 6px;'>
                    <strong>Fecha de vigencia:</strong>
                </td>
                <td style='padding:4px 6px; text-align:center;'>22/05/2026</td>
            </tr>
        </table>
    </td>
</tr>
</table><br>";

        // ── No. Solicitud y Fecha ─────────────────────────────────────
        $html .= "
<table width='100%' style='border-collapse:collapse; margin-bottom:4px; margin-top:6px;'>
<tr>
    <td style='width:20%; border:0.8px solid #ccc; padding:5px 8px;
               font-size:11px; font-weight:bold; background:#f5f5f5;'>
        NO. DE SOLICITUD:
    </td>
    <td style='width:44%; border:0.8px solid #ccc; padding:5px 8px; font-size:11px;'>
        " . e($numero) . "
    </td>
    <td style='width:5%; border:none;'></td>
    <td style='width:13%; border:0.8px solid #000; padding:5px 8px;
               font-size:11px; font-weight:bold; text-align:center; background:#f5f5f5;'>
        FECHA:
    </td>
    <td style='width:18%; border:0.8px solid #000; padding:5px 8px;
               font-size:11px; text-align:center;'>
        {$fechaFormat}
    </td>
</tr>
</table>";

        // ── Campos ────────────────────────────────────────────────────
        $campos = [
            'PROYECTO DE ORIGEN DE LOS MATERIALES' => $nombreOrigen,
            'PROYECTO EN FORMULACIÓN'              => $proyectoFormul,
            'JUSTIFICACIÓN DEL DESTINO'            => $justificacion,
            'UNIDAD SOLICITANTE'                   => $depto,
            'NOMBRE DE SOLICITANTE'                => $nombreSolic,
            'CARGO DE SOLICITANTE'                 => $cargoSolic,
        ];

        $html .= "<table width='100%' style='border-collapse:collapse; margin-bottom:4px;'>";
        foreach ($campos as $label => $valor) {
            $html .= "
<tr>
    <td style='width:25%; border:0.8px solid #ccc; padding:5px 8px;
               font-size:11px; font-weight:bold; background:#f5f5f5;'>
        {$label}:
    </td>
    <td style='border:0.8px solid #ccc; padding:5px 8px; font-size:11px;'>
        " . e($valor) . "
    </td>
</tr>";
        }
        $html .= "</table>";

        // ── Texto declaración ─────────────────────────────────────────
        $html .= "
<table width='100%' style='border-collapse:collapse; margin-bottom:8px; margin-top:4px;'>
<tr>
    <td style='border:0.8px solid #000; padding:8px 10px; font-size:10px;
               text-align:justify; line-height:1.6;'>
        POR MEDIO DEL PRESENTE DOCUMENTO, EL SUSCRITO DECLARA FORMALMENTE LA SOLICITUD PARA
        LA RESERVA Y JUSTIFICACIÓN DE LOS MATERIALES SOBRANTES DETALLADOS A CONTINUACIÓN,
        LOS CUALES SE REQUIEREN PARA LA EJECUCIÓN DEL PROYECTO DE INVERSIÓN PÚBLICA QUE SE
        EJECUTARÁ POR LA MUNICIPALIDAD ESPECIFICADO EN LA PRESENTE SOLICITUD, CUMPLIENDO CON
        LO ESTABLECIDO EN EL MANUAL DE PROCEDIMIENTOS PARA CONTROL DE EXISTENCIAS DE MATERIALES
        SOBRANTES DE PROYECTOS, CON LA CERTIFICACIÓN DE LAS EXISTENCIAS DEL INVENTARIO EN BODEGA
        SEGÚN EL SIGUIENTE DETALLE:
    </td>
</tr>
</table>";

        // ── Tabla materiales con subtotales por objeto específico ─────
        $html .= "
<table width='100%' style='border-collapse:collapse;'>
<thead>
    <tr>
        <th style='{$thStyle} width:5%;'>No.</th>
        <th style='{$thStyle} width:10%;'>COD PRESUP.</th>
        <th style='{$thStyle} width:35%;'>DESCRIPCIÓN</th>
        <th style='{$thStyle} width:12%;'>U. DE MEDIDA</th>
        <th style='{$thStyle} width:10%;'>CANTIDAD</th>
        <th style='{$thStyle} width:13%;'>PRECIO UNITARIO</th>
        <th style='{$thStyle} width:15%;'>SUBTOTAL</th>
    </tr>
</thead>
<tbody>";

        $i             = 1;
        $codigoActual  = null;
        $nombreActual  = '';
        $subtotalGrupo = 0;

        foreach ($rows as $r) {

            // ── Detectar cambio de grupo: emitir subtotal del anterior ──
            if ($codigoActual !== null && $r['codigo'] !== $codigoActual) {
                $labelSub = "SUBTOTAL OBJETO ESPECÍFICO [" . e($codigoActual) . "]";
                if (!empty($nombreActual)) {
                    $labelSub .= " — " . e($nombreActual);
                }
                $html .= "
    <tr>
        <td colspan='6' style='{$subLabel}'>{$labelSub}</td>
        <td style='{$subStyle}'>$ " . number_format($subtotalGrupo, 4) . "</td>
    </tr>";
                $subtotalGrupo = 0;
            }

            $codigoActual   = $r['codigo'];
            $nombreActual   = $r['nombre_obj_spec'];
            $subtotalGrupo += $r['subtotal'];

            $html .= "
    <tr>
        <td style='{$tdC}'>{$i}</td>
        <td style='{$tdC}'>" . e($r['codigo']) . "</td>
        <td style='{$tdStyle}'>" . e($r['nombre']) . "</td>
        <td style='{$tdC}'>" . e($r['medida']) . "</td>
        <td style='{$tdC} font-weight:bold;'>" . number_format($r['cantidad']) . "</td>
        <td style='{$tdR}'>$ " . number_format($r['precio'], 4) . "</td>
        <td style='{$tdR}'>$ " . number_format($r['subtotal'], 4) . "</td>
    </tr>";
            $i++;
        }

        // ── Subtotal del último grupo ─────────────────────────────────
        if ($codigoActual !== null) {
            $labelSub = "SUBTOTAL OBJETO ESPECÍFICO [" . e($codigoActual) . "]";
            if (!empty($nombreActual)) {
                $labelSub .= " — " . e($nombreActual);
            }
            $html .= "
    <tr>
        <td colspan='6' style='{$subLabel}'>{$labelSub}</td>
        <td style='{$subStyle}'>$ " . number_format($subtotalGrupo, 4) . "</td>
    </tr>";
        }

        // ── Total general ─────────────────────────────────────────────
        $html .= "
    <tr>
        <td colspan='6' style='font-weight:bold; font-size:11px; text-align:center;
                                border:0.8px solid #000; padding:5px; background:#d9e1f2;'>
            TOTAL GENERAL
        </td>
        <td style='font-weight:bold; font-size:11px; text-align:right;
                    border:0.8px solid #000; padding:5px; background:#d9e1f2;'>
            $ " . number_format($granTotal, 4) . "
        </td>
    </tr>
</tbody>
</table>";

        // ── Observaciones ─────────────────────────────────────────────
        $html .= "
<br>
<table width='100%' border='1' cellspacing='0' cellpadding='6'
   style='border-collapse:collapse; font-size:11px;'>
<tr style='background:#f2f4f8;'>
    <td style='font-weight:bold;'>OBSERVACIONES:</td>
</tr>
<tr>
    <td style='height:40px; vertical-align:top;'>" . e($observaciones) . "</td>
</tr>
</table>";

        // ── Firmas ────────────────────────────────────────────────────
        $px = $informacionGeneral->px_firmas ?? 40;

        $html .= "
<table width='100%' style='border-collapse:collapse; font-family:Arial,sans-serif;
                    margin-top:{$px}px; font-size:13px; line-height:1.8;'>
<tr>
    <td style='width:50%; padding-right:40px; vertical-align:top;'>
        <strong style='font-size:14px;'>ELABORADO POR:</strong><br><br><br>
        <table width='100%' style='border-collapse:collapse; line-height:1.8;'>
            <tr>
                <td style='width:18%; padding:6px 0; font-size:13px;'>FIRMA:</td>
                <td style='border-bottom:0.8px solid #000; width:82%; padding:6px 0;'>&nbsp;</td>
            </tr>
            <tr><td colspan='2' style='height:35px;'></td></tr>
            <tr>
                <td style='padding:6px 0; font-size:13px;'>NOMBRE:</td>
                <td style='border-bottom:0.8px solid #000; padding:6px 0;'>&nbsp;</td>
            </tr>
            <tr><td colspan='2' style='height:35px;'></td></tr>
            <tr>
                <td style='padding:6px 0; font-size:13px;'>CARGO:</td>
                <td style='border-bottom:0.8px solid #000; padding:6px 0;'>&nbsp;</td>
            </tr>
            <tr><td colspan='2' style='height:35px;'></td></tr>
            <tr>
                <td colspan='2' style='text-align:center; font-size:13px; font-weight:bold;
                                       padding-top:10px;'>
                    SOLICITANTE
                </td>
            </tr>
        </table>
    </td>
    <td style='width:50%; padding-left:40px; vertical-align:top;'>
        <strong style='font-size:14px;'>ES CONFORME:</strong><br><br><br>
        <table width='100%' style='border-collapse:collapse; line-height:1.8;'>
            <tr>
                <td style='width:18%; padding:6px 0; font-size:13px;'>FIRMA:</td>
                <td style='border-bottom:0.8px solid #000; width:82%; padding:6px 0;'>&nbsp;</td>
            </tr>
            <tr><td colspan='2' style='height:35px;'></td></tr>
            <tr>
                <td style='padding:6px 0; font-size:13px;'>NOMBRE:</td>
                <td style='border-bottom:0.8px solid #000; padding:6px 0;'>&nbsp;</td>
            </tr>
            <tr><td colspan='2' style='height:35px;'></td></tr>
            <tr>
                <td style='padding:6px 0; font-size:13px;'>CARGO:</td>
                <td style='border-bottom:0.8px solid #000; padding:6px 0;'>&nbsp;</td>
            </tr>
            <tr><td colspan='2' style='height:35px;'></td></tr>
            <tr>
                <td colspan='2' style='text-align:center; font-size:12px; font-weight:bold;
                                       padding-top:10px; line-height:1.5;'>
                    [ENCARGADO DE BODEGA DE PROYECTO<br>O RESPONSABLE ASIGNADO]
                </td>
            </tr>
        </table>
    </td>
</tr>

<tr><td colspan='2' style='height:100px;'></td></tr>

<tr>
<td colspan='2'
    style='text-align:center; vertical-align:top;
           padding: {$informacionGeneral->px_autorizado}px 0 20px 0;'>
    <table width='95%'
           style='border-collapse:collapse; margin:0 auto; font-size:13px; line-height:1.8;'>
        <tr>
            <td style='width:14%; text-align:left; padding:8px 6px 8px 0; white-space:nowrap;
                       font-weight:bold;'>
                AUTORIZADO:
            </td>
            <td style='width:20%; padding:8px 0;'>&nbsp;</td>

            <td style='width:4%; padding:8px 0;'>&nbsp;</td>

            <td style='width:10%; text-align:left; padding:8px 6px 8px 0; white-space:nowrap;
                       font-weight:bold;'>
                ACUERDO:
            </td>
            <td style='width:14%; border-bottom:0.8px solid #000; padding:8px 10px;
                       min-width:90px;'>
                &nbsp;
            </td>

            <td style='width:4%; padding:8px 0;'>&nbsp;</td>

            <td style='width:7%; text-align:left; padding:8px 6px 8px 0; white-space:nowrap;
                       font-weight:bold;'>
                ACTA:
            </td>
            <td style='width:14%; border-bottom:0.8px solid #000; padding:8px 10px;
                       min-width:90px;'>
                &nbsp;
            </td>

            <td style='width:4%; padding:8px 0;'>&nbsp;</td>

            <td style='width:7%; text-align:left; padding:8px 6px 8px 0; white-space:nowrap;
                       font-weight:bold;'>
                FECHA:
            </td>
            <td style='width:18%; border-bottom:0.8px solid #000; padding:8px 10px;
                       min-width:110px;'>
                &nbsp;
            </td>
        </tr>
    </table>
</td>
</tr>
</table>";

        $mpdf = new \Mpdf\Mpdf([
            'tempDir'     => sys_get_temp_dir(),
            'format'      => 'LETTER',
            'orientation' => 'P',
        ]);
        $mpdf->SetTitle('GEAD-001-FORM');
        $mpdf->showImageErrors = false;
        $mpdf->setFooter("Página {PAGENO} de {nb}");
        $mpdf->WriteHTML($html, \Mpdf\HTMLParserMode::HTML_BODY);
        $mpdf->Output();
    }



    /**
     * Genera un PDF con el detalle de los errores de stock encontrados.
     */
    private function renderErrorStockPdf(array $errores)
    {
        $thStyle = "font-weight:bold; font-size:11px; border:0.8px solid #000;
                padding:6px; background:#f8d7da; text-align:center; color:#721c24;";
        $tdStyle = "font-size:10px; border:0.8px solid #000; padding:6px;";
        $tdC     = $tdStyle . " text-align:center;";

        $html = "
<table width='100%' style='border-collapse:collapse; font-family:Arial,sans-serif; margin-bottom:20px;'>
    <tr>
        <td style='border:2px solid #721c24; background:#f8d7da; padding:20px;
                   text-align:center; color:#721c24;'>
            <h2 style='margin:0; font-size:18px;'>
                ⚠ ERROR DE STOCK INSUFICIENTE
            </h2>
            <p style='margin:8px 0 0 0; font-size:12px;'>
                No es posible generar el formulario de reserva. Las siguientes cantidades
                solicitadas exceden el stock disponible en bodega.
            </p>
        </td>
    </tr>
</table>

<table width='100%' style='border-collapse:collapse;'>
    <thead>
        <tr>
            <th style='{$thStyle} width:5%;'>No.</th>
            <th style='{$thStyle} width:35%;'>MATERIAL</th>
            <th style='{$thStyle} width:12%;'>CANT. INICIAL</th>
            <th style='{$thStyle} width:11%;'>SALIDAS</th>
            <th style='{$thStyle} width:11%;'>RESERVADO</th>
            <th style='{$thStyle} width:12%;'>DISPONIBLE</th>
            <th style='{$thStyle} width:14%;'>SOLICITADO</th>
        </tr>
    </thead>
    <tbody>";

        $i = 1;
        foreach ($errores as $e) {
            $exceso = $e['solicitado'] - $e['disponible'];
            $html .= "
        <tr>
            <td style='{$tdC}'>{$i}</td>
            <td style='{$tdStyle}'>" . e($e['nombre']) . "</td>
            <td style='{$tdC}'>" . number_format($e['inicial']) . "</td>
            <td style='{$tdC}'>" . number_format($e['salidas']) . "</td>
            <td style='{$tdC}'>" . number_format($e['reservas']) . "</td>
            <td style='{$tdC} font-weight:bold; color:#155724; background:#d4edda;'>
                " . number_format($e['disponible']) . "
            </td>
            <td style='{$tdC} font-weight:bold; color:#721c24; background:#f8d7da;'>
                " . number_format($e['solicitado']) . "
                <br><small>(excede en " . number_format($exceso) . ")</small>
            </td>
        </tr>";
            $i++;
        }

        $html .= "
    </tbody>
</table>

<br><br>
<table width='100%' style='border-collapse:collapse; font-family:Arial,sans-serif;'>
    <tr>
        <td style='border:0.8px solid #999; padding:12px; font-size:10px;
                   background:#fff3cd; color:#856404;'>
            <strong>Nota:</strong> La cantidad disponible se calcula como:
            <em>Cantidad inicial − Salidas registradas − Reservas pendientes de despacho</em>.
            Verifique las cantidades solicitadas o consulte el estado actual del inventario
            antes de continuar.
        </td>
    </tr>
</table>";

        $mpdf = new \Mpdf\Mpdf([
            'tempDir'     => sys_get_temp_dir(),
            'format'      => 'LETTER',
            'orientation' => 'P',
        ]);
        $mpdf->SetTitle('ERROR - Stock insuficiente');
        $mpdf->showImageErrors = false;
        $mpdf->setFooter("Página {PAGENO} de {nb}");
        $mpdf->WriteHTML($html, \Mpdf\HTMLParserMode::HTML_BODY);
        $mpdf->Output();
    }



    public function actualizarPxInformacionGeneral(Request $request)
    {
        $rules = [
            'px_firmas'        => 'required|integer|min:0',
            'px_observaciones' => 'required|integer|min:0',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return ['success' => 0];
        }

        try {
            $info = InformacionGeneral::find(1);

            if (!$info) {
                return ['success' => 0];
            }

            $info->px_firmas        = (int) $request->px_firmas;
            $info->px_observaciones = (int) $request->px_observaciones;
            $info->save();

            return ['success' => 1];

        } catch (\Throwable $e) {
            Log::error('actualizarPxInformacionGeneral: ' . $e->getMessage());
            return ['success' => 99];
        }
    }





    public function vistaReportePorPeriodos()
    {
        $proyectos = Tipoproyecto::orderBy('nombre')->get();
        $infoGeneral = InformacionGeneral::where('id', 1)->first();

        return view('backend.reportes.vistareporteporperiodos', compact('proyectos',
            'infoGeneral'));
    }


    /**
     * REPORTE DE SALDOS POR PERÍODOS — versión corregida
     *
     * CAMBIO PRINCIPAL
     * ----------------
     * Para un proyecto CERRADO el "sobrante" no es un movimiento con fecha:
     * es un SALDO de arranque. Por eso, en estado 'cerrado':
     *
     *   - ENTRADAS del período          -> SIEMPRE 0
     *   - SALDO / EXISTENCIA INICIAL    -> entradas originales del proyecto
     *                                      menos salidas operativas
     *                                      menos transferencias anteriores al período
     *   - SALIDAS del período           -> SOLO transferencias (es_transferencia = 1)
     *                                      dentro del rango [desde, hasta]
     *   - EXISTENCIA ACTUAL             -> inicial - salidas
     *
     * Así, en tu ejemplo: inicial 20, entradas 0, salidas 2, actual 18.
     *
     * Para un proyecto ACTIVO la lógica es la de siempre (entradas y salidas
     * operativas dentro/fuera del período).
     */


    public function vistaPDFReportePorPeriodos(Request $request)
    {
        $idproy      = $request->input('idproy');
        $estado      = $request->input('estado', 'activo');   // 'activo' | 'cerrado'
        $desde       = $request->input('desde');
        $hasta       = $request->input('hasta');
        $mostrarCero = $request->input('mostrar_cero', '0') === '1'; // ← NUEVO

        // Normalizar: solo se aceptan dos valores controlados
        $estado = ($estado === 'cerrado') ? 'cerrado' : 'activo';

        $start = \Carbon\Carbon::parse($desde)->startOfDay();
        $end   = \Carbon\Carbon::parse($hasta)->endOfDay();

        $desdeFormat = \Carbon\Carbon::parse($desde)->format('d/m/Y');
        $hastaFormat = \Carbon\Carbon::parse($hasta)->format('d/m/Y');

        $proyecto     = \App\Models\TipoProyecto::find($idproy);
        $logoalcaldia = 'images/logo.png';

        // ── Configuración según el estado del proyecto ─────────────────────
        if ($estado === 'cerrado') {
            $tituloReporte = 'REPORTE DE SALDOS DE MATERIALES SOBRANTES';
            $nombreCodigo  = "GEAD-003-REPO";
        } else {
            $tituloReporte = 'REPORTE DE SALDOS DE MATERIALES';
            $nombreCodigo  = "";
        }

        // ── Validar fecha de cierre solo si proyecto cerrado ─────────────
        if ($estado === 'cerrado') {
            $fechaCierre = Carbon::parse($proyecto->fecha_cierre)->startOfDay();
            if ($start->lt($fechaCierre) || $end->lt($fechaCierre)) {
                return 'El rango solicitado no puede ser menor a la fecha de cierre del proyecto: ' . $fechaCierre->format('d/m/Y');
            }
        }

        // ===================================================================
        //  HAVING dinámico según $mostrarCero
        //  - false (por defecto): oculta filas donde saldo_final_cant = 0
        //  - true:                muestra todo (comportamiento anterior)
        // ===================================================================
        //
        //  El HAVING base filtra filas sin ningún movimiento (todo en 0).
        //  Cuando $mostrarCero = false, añadimos la condición extra:
        //      AND SUM(saldo_final_cant) <> 0
        // ===================================================================
        $havingExtra = $mostrarCero
            ? ''
            : 'AND SUM(b.saldo_final_cant) <> 0';

        // ===================================================================
        //  CONSULTA
        // ===================================================================
        if ($estado === 'cerrado') {

            $rows = DB::select("
            WITH entradas AS (
                SELECT
                    ed.id               AS id_entradadetalle,
                    ed.id_material,
                    ed.precio,
                    ed.cantidad_inicial AS cantidad_entrada
                FROM entradas_detalle ed
                JOIN entradas e ON e.id = ed.id_entradas
                WHERE e.id_tipoproyecto = ?
            ),
            salidas_oper AS (
                SELECT
                    sd.id_entrada_detalle,
                    sd.cantidad_salida,
                    s.fecha AS fecha_salida
                FROM salidas_detalle sd
                JOIN salidas s ON s.id = sd.id_salida
                WHERE s.id_tipoproyecto = ?
                  AND (s.es_transferencia = 0 OR s.es_transferencia IS NULL)
            ),
            salidas_transf AS (
                SELECT
                    sd.id_entrada_detalle,
                    sd.cantidad_salida,
                    s.fecha AS fecha_salida
                FROM salidas_detalle sd
                JOIN salidas s ON s.id = sd.id_salida
                WHERE s.id_tipoproyecto = ?
                  AND s.es_transferencia = 1
            ),
            in_total AS (
                SELECT id_entradadetalle, SUM(cantidad_entrada) AS qty
                FROM entradas
                GROUP BY id_entradadetalle
            ),
            oper_before AS (
                SELECT id_entrada_detalle, SUM(cantidad_salida) AS qty
                FROM salidas_oper
                WHERE fecha_salida < ?
                GROUP BY id_entrada_detalle
            ),
            oper_period AS (
                SELECT id_entrada_detalle, SUM(cantidad_salida) AS qty
                FROM salidas_oper
                WHERE fecha_salida >= ? AND fecha_salida <= ?
                GROUP BY id_entrada_detalle
            ),
            transf_before AS (
                SELECT id_entrada_detalle, SUM(cantidad_salida) AS qty
                FROM salidas_transf
                WHERE fecha_salida < ?
                GROUP BY id_entrada_detalle
            ),
            transf_period AS (
                SELECT id_entrada_detalle, SUM(cantidad_salida) AS qty
                FROM salidas_transf
                WHERE fecha_salida >= ? AND fecha_salida <= ?
                GROUP BY id_entrada_detalle
            ),
            base AS (
                SELECT
                    en.id_entradadetalle,
                    en.id_material,
                    obj.codigo                           AS codigo,
                    COALESCE(m.nombre, en.id_material)   AS descripcion,
                    um.nombre                            AS unidad_medida,
                    en.precio,

                    (COALESCE(it.qty, 0)
                     - COALESCE(ob.qty, 0)
                     - COALESCE(tb.qty, 0))                       AS saldo_inicial_cant,

                    0                                             AS entradas_mes_cant,

                    (COALESCE(op.qty, 0)
                     + COALESCE(tp.qty, 0))                       AS salidas_mes_cant,

                    (COALESCE(it.qty, 0)
                     - COALESCE(ob.qty, 0)
                     - COALESCE(tb.qty, 0)
                     - COALESCE(op.qty, 0)
                     - COALESCE(tp.qty, 0))                       AS saldo_final_cant,

                    ((COALESCE(it.qty, 0)
                      - COALESCE(ob.qty, 0)
                      - COALESCE(tb.qty, 0)) * en.precio)         AS saldo_inicial_money,

                    0                                             AS entradas_mes_money,

                    ((COALESCE(op.qty, 0)
                      + COALESCE(tp.qty, 0)) * en.precio)         AS salidas_mes_money,

                    ((COALESCE(it.qty, 0)
                      - COALESCE(ob.qty, 0)
                      - COALESCE(tb.qty, 0)
                      - COALESCE(op.qty, 0)
                      - COALESCE(tp.qty, 0)) * en.precio)         AS saldo_final_money
                FROM entradas en
                LEFT JOIN materiales m          ON m.id  = en.id_material
                LEFT JOIN objeto_especifico obj ON obj.id = m.id_objespecifico
                LEFT JOIN unidadmedida um       ON um.id = m.id_medida
                LEFT JOIN in_total       it ON it.id_entradadetalle  = en.id_entradadetalle
                LEFT JOIN oper_before    ob ON ob.id_entrada_detalle = en.id_entradadetalle
                LEFT JOIN oper_period    op ON op.id_entrada_detalle = en.id_entradadetalle
                LEFT JOIN transf_before  tb ON tb.id_entrada_detalle = en.id_entradadetalle
                LEFT JOIN transf_period  tp ON tp.id_entrada_detalle = en.id_entradadetalle
            )
            SELECT
                b.id_material,
                MAX(b.codigo)        AS codigo,
                MAX(b.descripcion)   AS descripcion,
                MAX(b.unidad_medida) AS unidad_medida,
                b.precio,
                SUM(b.saldo_inicial_cant)  AS saldo_inicial_cant,
                SUM(b.entradas_mes_cant)   AS entradas_mes_cant,
                SUM(b.salidas_mes_cant)    AS salidas_mes_cant,
                SUM(b.saldo_final_cant)    AS saldo_final_cant,
                SUM(b.saldo_inicial_money) AS saldo_inicial_money,
                SUM(b.entradas_mes_money)  AS entradas_mes_money,
                SUM(b.salidas_mes_money)   AS salidas_mes_money,
                SUM(b.saldo_final_money)   AS saldo_final_money
            FROM base b
            GROUP BY b.id_material, b.precio
            HAVING (SUM(b.entradas_mes_cant)  <> 0
                 OR SUM(b.salidas_mes_cant)   <> 0
                 OR SUM(b.saldo_inicial_cant) <> 0
                 OR SUM(b.saldo_final_cant)   <> 0)
            {$havingExtra}
            ORDER BY MAX(b.codigo), MAX(b.descripcion)
        ", [
                $idproy,
                $idproy,
                $idproy,
                $start->toDateString(),
                $start->toDateString(),
                $end->toDateString(),
                $start->toDateString(),
                $start->toDateString(),
                $end->toDateString(),
            ]);

        } else {

            // ── ACTIVO ──────────────────────────────────────────────────────
            $filtroSalidas = " AND (s.es_transferencia = 0 OR s.es_transferencia IS NULL) ";

            $rows = DB::select("
            WITH entradas AS (
                SELECT
                    ed.id               AS id_entradadetalle,
                    ed.id_material,
                    ed.precio,
                    ed.cantidad_inicial AS cantidad_entrada,
                    e.fecha             AS fecha_entrada
                FROM entradas_detalle ed
                JOIN entradas e ON e.id = ed.id_entradas
                WHERE e.id_tipoproyecto = ?
            ),
            salidas AS (
                SELECT
                    sd.id_entrada_detalle,
                    sd.cantidad_salida,
                    s.fecha AS fecha_salida
                FROM salidas_detalle sd
                JOIN salidas s ON s.id = sd.id_salida
                WHERE s.id_tipoproyecto = ?
                  {$filtroSalidas}
            ),
            in_before AS (
                SELECT id_entradadetalle, SUM(cantidad_entrada) AS qty_in_before
                FROM entradas
                WHERE fecha_entrada < ?
                GROUP BY id_entradadetalle
            ),
            out_before AS (
                SELECT id_entrada_detalle, SUM(cantidad_salida) AS qty_out_before
                FROM salidas
                WHERE fecha_salida < ?
                GROUP BY id_entrada_detalle
            ),
            in_period AS (
                SELECT id_entradadetalle, SUM(cantidad_entrada) AS qty_in_period
                FROM entradas
                WHERE fecha_entrada >= ? AND fecha_entrada <= ?
                GROUP BY id_entradadetalle
            ),
            out_period AS (
                SELECT id_entrada_detalle, SUM(cantidad_salida) AS qty_out_period
                FROM salidas
                WHERE fecha_salida >= ? AND fecha_salida <= ?
                GROUP BY id_entrada_detalle
            ),
            base AS (
                SELECT
                    en.id_entradadetalle,
                    en.id_material,
                    obj.codigo AS codigo,
                    COALESCE(m.nombre, en.id_material) AS descripcion,
                    um.nombre AS unidad_medida,
                    en.precio,

                    COALESCE(ib.qty_in_before, 0) - COALESCE(ob.qty_out_before, 0) AS saldo_inicial_cant,
                    COALESCE(ip.qty_in_period,  0) AS entradas_mes_cant,
                    COALESCE(op.qty_out_period, 0) AS salidas_mes_cant,
                    (COALESCE(ib.qty_in_before, 0) - COALESCE(ob.qty_out_before, 0)
                     + COALESCE(ip.qty_in_period, 0)
                     - COALESCE(op.qty_out_period, 0)) AS saldo_final_cant,

                    ((COALESCE(ib.qty_in_before, 0) - COALESCE(ob.qty_out_before, 0)) * en.precio) AS saldo_inicial_money,
                    (COALESCE(ip.qty_in_period,  0) * en.precio) AS entradas_mes_money,
                    (COALESCE(op.qty_out_period, 0) * en.precio) AS salidas_mes_money,
                    ((COALESCE(ib.qty_in_before, 0) - COALESCE(ob.qty_out_before, 0)
                      + COALESCE(ip.qty_in_period, 0) - COALESCE(op.qty_out_period, 0)) * en.precio) AS saldo_final_money
                FROM entradas en
                LEFT JOIN materiales m          ON m.id  = en.id_material
                LEFT JOIN objeto_especifico obj ON obj.id = m.id_objespecifico
                LEFT JOIN unidadmedida um       ON um.id = m.id_medida
                LEFT JOIN in_before  ib ON ib.id_entradadetalle  = en.id_entradadetalle
                LEFT JOIN out_before ob ON ob.id_entrada_detalle = en.id_entradadetalle
                LEFT JOIN in_period  ip ON ip.id_entradadetalle  = en.id_entradadetalle
                LEFT JOIN out_period op ON op.id_entrada_detalle = en.id_entradadetalle
            )
            SELECT
                b.id_material,
                MAX(b.codigo)        AS codigo,
                MAX(b.descripcion)   AS descripcion,
                MAX(b.unidad_medida) AS unidad_medida,
                b.precio,
                SUM(b.saldo_inicial_cant)  AS saldo_inicial_cant,
                SUM(b.entradas_mes_cant)   AS entradas_mes_cant,
                SUM(b.salidas_mes_cant)    AS salidas_mes_cant,
                SUM(b.saldo_final_cant)    AS saldo_final_cant,
                SUM(b.saldo_inicial_money) AS saldo_inicial_money,
                SUM(b.entradas_mes_money)  AS entradas_mes_money,
                SUM(b.salidas_mes_money)   AS salidas_mes_money,
                SUM(b.saldo_final_money)   AS saldo_final_money
            FROM base b
            GROUP BY b.id_material, b.precio
            HAVING (SUM(b.entradas_mes_cant)  <> 0
                 OR SUM(b.salidas_mes_cant)   <> 0
                 OR SUM(b.saldo_inicial_cant) <> 0
                 OR SUM(b.saldo_final_cant)   <> 0)
            {$havingExtra}
            ORDER BY MAX(b.codigo), MAX(b.descripcion)
        ", [
                $idproy,
                $idproy,
                $start->toDateString(),
                $start->toDateString(),
                $start->toDateString(),
                $end->toDateString(),
                $start->toDateString(),
                $end->toDateString(),
            ]);
        }

        // ── Totales ───────────────────────────────────────────────────────
        $totales = [
            'inicial_cant'  => 0,   'entradas_cant'  => 0,
            'salidas_cant'  => 0,   'final_cant'     => 0,
            'inicial_money' => 0.0, 'entradas_money' => 0.0,
            'salidas_money' => 0.0, 'final_money'    => 0.0,
        ];

        $sumPorCodigo = [];

        foreach ($rows as $r) {
            $totales['inicial_cant']   += (int)($r->saldo_inicial_cant  ?? 0);
            $totales['entradas_cant']  += (int)($r->entradas_mes_cant   ?? 0);
            $totales['salidas_cant']   += (int)($r->salidas_mes_cant    ?? 0);
            $totales['final_cant']     += (int)($r->saldo_final_cant    ?? 0);
            $totales['inicial_money']  += (float)($r->saldo_inicial_money ?? 0);
            $totales['entradas_money'] += (float)($r->entradas_mes_money  ?? 0);
            $totales['salidas_money']  += (float)($r->salidas_mes_money   ?? 0);
            $totales['final_money']    += (float)($r->saldo_final_money   ?? 0);

            $codigo = $r->codigo ?? 'SIN-CODIGO';
            if (!isset($sumPorCodigo[$codigo])) {
                $sumPorCodigo[$codigo] = [
                    'codigo'        => $codigo,
                    'inicial_cant'  => 0,   'entradas_cant'  => 0,
                    'salidas_cant'  => 0,   'final_cant'     => 0,
                    'inicial_money' => 0.0, 'entradas_money' => 0.0,
                    'salidas_money' => 0.0, 'final_money'    => 0.0,
                ];
            }
            $sumPorCodigo[$codigo]['inicial_cant']   += (int)($r->saldo_inicial_cant  ?? 0);
            $sumPorCodigo[$codigo]['entradas_cant']  += (int)($r->entradas_mes_cant   ?? 0);
            $sumPorCodigo[$codigo]['salidas_cant']   += (int)($r->salidas_mes_cant    ?? 0);
            $sumPorCodigo[$codigo]['final_cant']     += (int)($r->saldo_final_cant    ?? 0);
            $sumPorCodigo[$codigo]['inicial_money']  += (float)($r->saldo_inicial_money ?? 0);
            $sumPorCodigo[$codigo]['entradas_money'] += (float)($r->entradas_mes_money  ?? 0);
            $sumPorCodigo[$codigo]['salidas_money']  += (float)($r->salidas_mes_money   ?? 0);
            $sumPorCodigo[$codigo]['final_money']    += (float)($r->saldo_final_money   ?? 0);
        }

        $fechaHoy = \Carbon\Carbon::now('America/El_Salvador')->format('d-m-Y');

        // ── Render PDF ─────────────────────────────────────────────────────
        $mpdf = new \Mpdf\Mpdf([
            'tempDir'     => sys_get_temp_dir(),
            'format'      => 'LETTER',
            'orientation' => 'L',
        ]);
        $mpdf->SetTitle('Reporte de Movimientos por Proyecto');
        $mpdf->showImageErrors = false;

        if (file_exists(public_path('css/cssbodega.css'))) {
            $mpdf->WriteHTML(
                file_get_contents(public_path('css/cssbodega.css')),
                \Mpdf\HTMLParserMode::HEADER_CSS
            );
        }

        // ── Cabecera del documento ─────────────────────────────────────────
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
                {$tituloReporte}
            </td>
            <td style='width:25%; border:0.8px solid #000; padding:0; vertical-align:top;'>
                <table width='100%' style='font-size:10px;'>
                    <tr>
                        <td width='40%' style='border-right:0.8px solid #000; border-bottom:0.8px solid #000; padding:4px 6px;'><strong>Código:</strong></td>
                        <td width='60%' style='border-bottom:0.8px solid #000; padding:4px 6px; text-align:center;'>{$nombreCodigo}</td>
                    </tr>
                    <tr>
                        <td style='border-right:0.8px solid #000; border-bottom:0.8px solid #000; padding:4px 6px;'><strong>Versión:</strong></td>
                        <td style='border-bottom:0.8px solid #000; padding:4px 6px; text-align:center;'>000</td>
                    </tr>
                    <tr>
                        <td style='border-right:0.8px solid #000; padding:4px 6px;'><strong>Fecha de vigencia:</strong></td>
                        <td style='padding:4px 6px; text-align:center;'>22/05/2026</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table><br>

    <table width='100%' style='border-collapse:collapse; font-family:Arial, sans-serif; margin-bottom:4px;'>
        <tr>
            <td style='width:22%; border:0.8px solid #ccc; padding:6px 8px; font-size:11px;
                       font-weight:bold; background:#f5f5f5; vertical-align:top;'>
                PROYECTO DE ORIGEN DE LOS MATERIALES
            </td>
            <td style='border:0.8px solid #ccc; padding:6px 8px; font-size:11px;'>
                " . e($proyecto->nombre ?? '') . "
            </td>
        </tr>
    </table>";

        // Estado del proyecto
        $estaCerrado = $proyecto->transferido == 1;
        $fechaCierreTexto = 'No aplica';

        if ($estaCerrado) {
            $cierre = Transferencia::where('id_tipoproyecto', $idproy)
                ->where('tipo_salida', 'snapshot')
                ->orderBy('id', 'desc')
                ->first();
            if ($cierre) {
                $fechaCierreTexto = Carbon::parse($cierre->fecha)->format('d-m-Y');
            }
        }

        $html .= "
    <table width='100%' style='border-collapse:collapse; font-family:Arial, sans-serif; margin-bottom:8px;'>
        <tr>
            <td style='width:22%; border:0.8px solid #ccc; padding:6px 8px; font-size:11px;
                       font-weight:bold; background:#f5f5f5;'>PERIODO</td>
            <td style='width:43%; border:0.8px solid #ccc; padding:6px 8px; font-size:11px;'>
                {$desdeFormat} AL {$hastaFormat}
            </td>
            <td style='width:20%;'></td>
            <td style='width:7%; border:0.8px solid #ccc; padding:6px 8px; font-size:11px;
                       font-weight:bold; background:#f5f5f5; text-align:center;'>FECHA</td>
            <td style='width:8%; border:0.8px solid #ccc; padding:6px 8px; font-size:11px; text-align:center;'>
                {$fechaHoy}
            </td>
        </tr>";

        if ($estaCerrado) {
            $html .= "
        <tr>
            <td style='border:0.8px solid #ccc; padding:6px 8px; font-size:11px;
                       font-weight:bold; background:#f5f5f5;'>FECHA DE CIERRE</td>
            <td style='border:0.8px solid #ccc; padding:6px 8px; font-size:11px;'>{$fechaCierreTexto}</td>
            <td colspan='3'></td>
        </tr>";
        }



        $html .= "</table>";

        // ── Tabla de materiales ────────────────────────────────────────────
        $html .= "
    <table width='100%' border='1' cellspacing='0' cellpadding='4'
           style='border-collapse:collapse; font-size:11px; margin-top:8px'>
        <thead style='background:#f2f4f8'>
            <tr>
                <th style='text-align:center; width:5%'>No.</th>
                <th style='text-align:center; width:8%'>COD PRESUP.</th>
                <th style='text-align:center; width:8%'>UNIDAD DE MEDIDA</th>
                <th style='text-align:center; width:14%'>DESCRIPCIÓN</th>
                <th style='text-align:center; width:8%'>PRECIO UNITARIO</th>
                <th style='text-align:center; width:9%'>EXISTENCIA INICIAL</th>
                <th style='text-align:center; width:8%'>SALDO INICIAL</th>
                <th style='text-align:center; width:8%'>ENTRADAS</th>
                <th style='text-align:center; width:8%'>SALDO ENTRADAS</th>
                <th style='text-align:center; width:7%'>SALIDAS</th>
                <th style='text-align:center; width:8%'>SALDO SALIDAS</th>
                <th style='text-align:center; width:9%'>EXISTENCIA ACTUAL</th>
                <th style='text-align:center; width:10%'>SALDO EXISTENCIA ACTUAL</th>
            </tr>
        </thead>
        <tbody>";

        $i = 1;
        foreach ($rows as $r) {
            // Resaltar en gris claro las filas con existencia final = 0
            $rowStyle = ((int)($r->saldo_final_cant ?? 0) === 0)
                ? "style='background:#f5f5f5; color:#999;'"
                : '';

            $html .= "
        <tr {$rowStyle}>
            <td style='text-align:center'>{$i}</td>
            <td style='text-align:center'>" . e($r->codigo ?? '') . "</td>
            <td style='text-align:center'>" . e($r->unidad_medida ?? '') . "</td>
            <td>" . e($r->descripcion) . "</td>
            <td style='text-align:right'>$" . number_format($r->precio ?? 0, 4) . "</td>
            <td style='text-align:right'>" . number_format($r->saldo_inicial_cant ?? 0) . "</td>
            <td style='text-align:right'>$" . number_format($r->saldo_inicial_money ?? 0, 2) . "</td>
            <td style='text-align:right'>" . number_format($r->entradas_mes_cant ?? 0) . "</td>
            <td style='text-align:right'>$" . number_format($r->entradas_mes_money ?? 0, 2) . "</td>
            <td style='text-align:right'>" . number_format($r->salidas_mes_cant ?? 0) . "</td>
            <td style='text-align:right'>$" . number_format($r->salidas_mes_money ?? 0, 2) . "</td>
            <td style='text-align:right'>" . number_format($r->saldo_final_cant ?? 0) . "</td>
            <td style='text-align:right'>$" . number_format($r->saldo_final_money ?? 0, 2) . "</td>
        </tr>";
            $i++;
        }

        if (!$rows) {
            $html .= "<tr><td colspan='13' style='text-align:center; color:#888;'>Sin movimientos en el rango seleccionado.</td></tr>";
        }

        $html .= "
        </tbody>
        <tfoot>
            <tr style='font-weight:bold; background:#f9fafb'>
                <td colspan='5' style='text-align:right'>Totales:</td>
                <td style='text-align:right'>" . number_format($totales['inicial_cant']) . "</td>
                <td style='text-align:right'>$" . number_format($totales['inicial_money'], 2) . "</td>
                <td style='text-align:right'>" . number_format($totales['entradas_cant']) . "</td>
                <td style='text-align:right'>$" . number_format($totales['entradas_money'], 2) . "</td>
                <td style='text-align:right'>" . number_format($totales['salidas_cant']) . "</td>
                <td style='text-align:right'>$" . number_format($totales['salidas_money'], 2) . "</td>
                <td style='text-align:right'>" . number_format($totales['final_cant']) . "</td>
                <td style='text-align:right'>$" . number_format($totales['final_money'], 2) . "</td>
            </tr>
        </tfoot>
    </table>";

        // ── Resumen ────────────────────────────────────────────────────────
        $html .= "
    <br>
    <table width='55%' border='1' cellspacing='0' cellpadding='6'
           style='border-collapse:collapse; font-size:12px'>
        <tr style='background:#eef3ff; font-weight:bold; text-align:center'>
            <td colspan='3'>Resumen del período {$desdeFormat} - {$hastaFormat}</td>
        </tr>
        <tr style='font-weight:bold; background:#f9fafb'>
            <td></td>
            <td style='text-align:right'>Cantidad</td>
            <td style='text-align:right'>Dinero (\$)</td>
        </tr>
        <tr>
            <td>Saldo Inicial</td>
            <td style='text-align:right'>" . number_format($totales['inicial_cant']) . "</td>
            <td style='text-align:right'>$" . number_format($totales['inicial_money'], 2) . "</td>
        </tr>
        <tr>
            <td>Entradas del período</td>
            <td style='text-align:right'>" . number_format($totales['entradas_cant']) . "</td>
            <td style='text-align:right'>$" . number_format($totales['entradas_money'], 2) . "</td>
        </tr>
        <tr>
            <td>Salidas del período</td>
            <td style='text-align:right'>" . number_format($totales['salidas_cant']) . "</td>
            <td style='text-align:right'>$" . number_format($totales['salidas_money'], 2) . "</td>
        </tr>
        <tr style='font-weight:bold'>
            <td>Saldo Final</td>
            <td style='text-align:right'>" . number_format($totales['final_cant']) . "</td>
            <td style='text-align:right'>$" . number_format($totales['final_money'], 2) . "</td>
        </tr>
    </table>";

        // ── Resumen por código presupuestario ─────────────────────────────
        if (!empty($sumPorCodigo)) {
            $totalSaldoFinalCodigos = 0;

            $html .= "
        <br><br>
        <span style='font-weight:bold; font-size:12px;'>Resumen por Código Presupuestario</span>
        <table width='100%' border='1' cellspacing='0' cellpadding='4'
               style='border-collapse:collapse; font-size:11px; margin-top:4px'>
            <thead style='background:#f2f4f8'>
                <tr>
                    <th style='width:4%'>#</th>
                    <th style='width:10%'>Código</th>
                    <th style='text-align:right; width:6%'>INICIAL</th>
                    <th style='text-align:right; width:10%'>\$ INICIAL</th>
                    <th style='text-align:right; width:6%'>ENTRADAS</th>
                    <th style='text-align:right; width:10%'>\$ ENTRADAS</th>
                    <th style='text-align:right; width:6%'>SALIDAS</th>
                    <th style='text-align:right; width:10%'>\$ SALIDAS</th>
                    <th style='text-align:right; width:6%'>SALDO</th>
                    <th style='text-align:right; width:10%'>\$ SALDO</th>
                </tr>
            </thead>
            <tbody>";

            $j = 1;
            foreach ($sumPorCodigo as $s) {
                $totalSaldoFinalCodigos += (float)$s['final_money'];
                $html .= "
                <tr>
                    <td>{$j}</td>
                    <td>" . e($s['codigo']) . "</td>
                    <td style='text-align:right'>" . number_format($s['inicial_cant']) . "</td>
                    <td style='text-align:right'>$" . number_format($s['inicial_money'], 2) . "</td>
                    <td style='text-align:right'>" . number_format($s['entradas_cant']) . "</td>
                    <td style='text-align:right'>$" . number_format($s['entradas_money'], 2) . "</td>
                    <td style='text-align:right'>" . number_format($s['salidas_cant']) . "</td>
                    <td style='text-align:right'>$" . number_format($s['salidas_money'], 2) . "</td>
                    <td style='text-align:right'>" . number_format($s['final_cant']) . "</td>
                    <td style='text-align:right'>$" . number_format($s['final_money'], 2) . "</td>
                </tr>";
                $j++;
            }

            $html .= "
                <tr style='font-weight:bold; background:#f9fafb'>
                    <td colspan='9' style='text-align:right'>TOTAL \$ SALDO</td>
                    <td style='text-align:right'>$" . number_format($totalSaldoFinalCodigos, 2) . "</td>
                </tr>
            </tbody>
        </table>";
        }

        $infoGeneral = InformacionGeneral::where('id', 1)->first();

        // ── Firmas ─────────────────────────────────────────────────────────
        $html .= "
    <table width='100%' style='border-collapse:collapse; font-family:Arial,sans-serif; font-size:12px;
                                margin-top:" . ($infoGeneral->px_firmas ?? 0) . "px;'>
        <tr>
            <td style='width:50%; padding-right:30px; vertical-align:top;'>
                <strong>ELABORADO POR:</strong><br><br><br>
                <table width='100%' style='border-collapse:collapse;'>
                    <tr>
                        <td style='width:18%; padding-bottom:6px;'>FIRMA:</td>
                        <td style='border-bottom:0.8px solid #000; width:82%;'>&nbsp;</td>
                    </tr>
                    <tr><td colspan='2' style='height:22px;'></td></tr>
                    <tr>
                        <td style='padding-bottom:6px;'>NOMBRE:</td>
                        <td style='border-bottom:0.8px solid #000;'>&nbsp;</td>
                    </tr>
                    <tr><td colspan='2' style='height:22px;'></td></tr>
                    <tr>
                        <td style='padding-bottom:6px;'>CARGO:</td>
                        <td style='border-bottom:0.8px solid #000;'>&nbsp;</td>
                    </tr>
                    <tr><td colspan='2' style='height:15px;'></td></tr>
                    <tr>
                        <td></td>
                        <td style='text-align:center; font-size:11px;'>
                            " . e($infoGeneral->p_nombre1 ?? '') . "
                        </td>
                    </tr>
                </table>
            </td>
            <td style='width:50%; padding-left:30px; vertical-align:top;'>
                <strong>REVISADO POR:</strong><br><br><br>
                <table width='100%' style='border-collapse:collapse;'>
                    <tr>
                        <td style='width:18%; padding-bottom:6px;'>FIRMA:</td>
                        <td style='border-bottom:0.8px solid #000; width:82%;'>&nbsp;</td>
                    </tr>
                    <tr><td colspan='2' style='height:22px;'></td></tr>
                    <tr>
                        <td style='padding-bottom:6px;'>NOMBRE:</td>
                        <td style='border-bottom:0.8px solid #000;'>&nbsp;</td>
                    </tr>
                    <tr><td colspan='2' style='height:22px;'></td></tr>
                    <tr>
                        <td style='padding-bottom:6px;'>CARGO:</td>
                        <td style='border-bottom:0.8px solid #000;'>&nbsp;</td>
                    </tr>
                    <tr><td colspan='2' style='height:15px;'></td></tr>
                    <tr>
                        <td></td>
                        <td style='text-align:center; font-size:11px;'>
                            " . e($infoGeneral->p_nombre2 ?? '') . "
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>";

        $mpdf->setFooter("Página {PAGENO} de {nb}");
        $mpdf->WriteHTML($html, \Mpdf\HTMLParserMode::HTML_BODY);
        $mpdf->Output();
    }

    public function actualizarFirmasReportePeriodos(Request $request)
    {
        try {

            InformacionGeneral::where('id', 1)->update([
                'p_nombre1' => $request->p_nombre1,
                'p_nombre2' => $request->p_nombre2,
            ]);

            return response()->json([
                'success' => 1
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'success' => 99
            ]);
        }
    }





}
