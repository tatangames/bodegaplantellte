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
        $fechaFmt   = $fecha ? date('d/m/Y', strtotime($fecha)) : '';
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
            <strong>DESCRIPCIÓN:</strong> &nbsp; " . e($nombreRecibe) . "
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

            // Intentar obtener nombre desde BD si viene el id
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
