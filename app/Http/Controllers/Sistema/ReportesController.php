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



    public function pdfQueHaSalidoProyectos($desde, $hasta, $tipo = 2)
    {
        $fechaHoy     = Carbon::now('America/El_Salvador')->format('d-m-Y');
        $logoalcaldia = 'images/logo.png';
        $sinFecha     = ($desde === 'null' || $desde === '' || $hasta === 'null' || $hasta === '');

        if (!$sinFecha) {
            $start      = date('Y-m-d 00:00:00', strtotime($desde));
            $end        = date('Y-m-d 23:59:59', strtotime($hasta));
            $fechaLabel = date('d-m-Y', strtotime($desde)) . '  —  ' . date('d-m-Y', strtotime($hasta));
        } else {
            $fechaLabel = 'Todas las fechas';
        }

        $encabezado = "
<table width='100%' style='border-collapse:collapse; font-family:Arial, sans-serif;'>
    <tr>
        <td style='width:25%; border:0.8px solid #000; padding:6px 8px;'>
            <table width='100%'><tr>
                <td style='width:30%; text-align:left;'><img src='{$logoalcaldia}' style='height:38px'></td>
                <td style='width:70%; text-align:left; color:#104e8c; font-size:13px; font-weight:bold; line-height:1.3;'>SANTA ANA NORTE<br>EL SALVADOR</td>
            </tr></table>
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
<table width='100%' style='border-collapse:collapse; font-family:Arial, sans-serif; margin-bottom:8px;'>
    <tr>
        <td style='width:15%; border:0.8px solid #ccc; padding:6px 8px; font-size:11px; font-weight:bold; background:#f5f5f5;'>PERIODO</td>
        <td style='width:50%; border:0.8px solid #ccc; padding:6px 8px; font-size:11px;'>$fechaLabel</td>
        <td style='width:15%; border:0.8px solid #ccc; padding:6px 8px; font-size:11px; font-weight:bold; background:#f5f5f5; text-align:center;'>FECHA</td>
        <td style='width:20%; border:0.8px solid #ccc; padding:6px 8px; font-size:11px; text-align:center;'>$fechaHoy</td>
    </tr>
</table>";

        $granTotal         = 0;
        $sumaTotalCantidad = 0;
        $tabla             = $encabezado;

        // ── Acumulador para el resumen de Objetos Específicos ─────────────────
        $resumenObjEsp = []; // ['codigo' => ['cantidad' => x, 'total' => y]]

        // ── CABECERA COLUMNAS ─────────────────────────────────────────────
        $theadSalidas = "
<table width='100%' style='border-collapse:collapse; font-family:Arial, sans-serif; margin-bottom:8px; border:0.8px solid #aaa;'>
    <thead>
        <tr style='background:#6c757d;'>
            <td style='font-weight:bold; width:11%; font-size:11px; color:#fff; padding:5px 6px; border:0.8px solid #888;'>Cod. Presu.</td>
            <td style='font-weight:bold; width:32%; font-size:11px; color:#fff; padding:5px 6px; border:0.8px solid #888;'>Material</td>
            <td style='font-weight:bold; width:12%; font-size:11px; color:#fff; padding:5px 6px; border:0.8px solid #888;'>Medida</td>
            <td style='font-weight:bold; width:10%; font-size:11px; color:#fff; padding:5px 6px; border:0.8px solid #888;'>Cantidad</td>
            <td style='font-weight:bold; width:14%; font-size:11px; color:#fff; padding:5px 6px; border:0.8px solid #888;'>Precio Unit.</td>
            <td style='font-weight:bold; width:14%; font-size:11px; color:#fff; padding:5px 6px; border:0.8px solid #888;'>Total (\$)</td>
        </tr>
    </thead>
    <tbody>";

        // ════════════════════════════════════════════════════════════════
        // TIPO 1: JUNTOS
        // ════════════════════════════════════════════════════════════════
        if ($tipo == 1) {

            $query = Salidas::with([
                'detalle.entradaDetalle.material.unidadMedida',
                'detalle.entradaDetalle.material.objetoEspecifico',
            ]);
            if (!$sinFecha) $query->whereBetween('fecha', [$start, $end]);
            $arraySalidas = $query->orderBy('fecha', 'ASC')->get();

            $dataArray = [];

            foreach ($arraySalidas as $salida) {
                foreach ($salida->detalle as $det) {
                    $entDet = $det->entradaDetalle;
                    if (!$entDet || !$entDet->material) continue;

                    $idMat  = $entDet->id_material;
                    $precio = (float) ($entDet->precio ?? 0);
                    $clave  = $idMat . '|' . number_format($precio, 4, '.', '');

                    if (!isset($dataArray[$clave])) {
                        $dataArray[$clave] = [
                            'objespec' => $entDet->material->objetoEspecifico->codigo ?? '—',
                            'nombre'   => $entDet->material->nombre ?? '',
                            'medida'   => $entDet->material->unidadMedida->nombre ?? '',
                            'cantidad' => 0,
                            'total'    => 0,
                            'precio'   => $precio,
                        ];
                    }
                    $dataArray[$clave]['cantidad'] += $det->cantidad_salida;
                    $dataArray[$clave]['total']    += ($precio * $det->cantidad_salida);
                }
            }

            usort($dataArray, function ($a, $b) {
                $cmp = strcmp($a['objespec'], $b['objespec']);
                return $cmp !== 0 ? $cmp : strcmp($a['nombre'], $b['nombre']);
            });

            $tabla .= $theadSalidas;

            $codigoActual    = null;
            $subtotalCodigo  = 0;
            $subtotalCantCod = 0;

            foreach ($dataArray as $info) {
                if ($codigoActual !== null && $info['objespec'] !== $codigoActual) {
                    $cantFmt  = number_format($subtotalCantCod, 2);
                    $montoFmt = number_format($subtotalCodigo, 4);
                    $tabla .= "
        <tr style='background:#e9ecef;'>
            <td colspan='3' style='font-weight:bold; font-size:11px; text-align:right; padding:4px 6px; border:0.8px solid #bbb;'>SUBTOTAL [{$codigoActual}]</td>
            <td style='font-weight:bold; font-size:11px; padding:4px 6px; border:0.8px solid #bbb;'>$cantFmt</td>
            <td style='background:#e9ecef; border:0.8px solid #bbb;'></td>
            <td style='font-weight:bold; font-size:11px; padding:4px 6px; border:0.8px solid #bbb;'>\$ $montoFmt</td>
        </tr>";
                    $subtotalCodigo  = 0;
                    $subtotalCantCod = 0;
                }

                $codigoActual       = $info['objespec'];
                $subtotalCodigo    += $info['total'];
                $subtotalCantCod   += $info['cantidad'];
                $granTotal         += $info['total'];
                $sumaTotalCantidad += $info['cantidad'];

                // ── Acumular en resumen ──
                if (!isset($resumenObjEsp[$info['objespec']])) {
                    $resumenObjEsp[$info['objespec']] = ['cantidad' => 0, 'total' => 0];
                }
                $resumenObjEsp[$info['objespec']]['cantidad'] += $info['cantidad'];
                $resumenObjEsp[$info['objespec']]['total']    += $info['total'];

                $precioFmt = number_format($info['precio'], 4);
                $totalFmt  = number_format($info['total'], 4);

                $tabla .= "
        <tr>
            <td style='font-size:11px; padding:4px 6px; border:0.8px solid #ccc;'>{$info['objespec']}</td>
            <td style='font-size:11px; padding:4px 6px; border:0.8px solid #ccc;'>{$info['nombre']}</td>
            <td style='font-size:11px; padding:4px 6px; border:0.8px solid #ccc;'>{$info['medida']}</td>
            <td style='font-size:11px; padding:4px 6px; border:0.8px solid #ccc;'>{$info['cantidad']}</td>
            <td style='font-size:11px; padding:4px 6px; border:0.8px solid #ccc;'>\$ $precioFmt</td>
            <td style='font-size:11px; padding:4px 6px; border:0.8px solid #ccc;'>\$ $totalFmt</td>
        </tr>";
            }

            // Último subtotal
            if ($codigoActual !== null) {
                $cantFmt  = number_format($subtotalCantCod, 2);
                $montoFmt = number_format($subtotalCodigo, 4);
                $tabla .= "
        <tr style='background:#e9ecef;'>
            <td colspan='3' style='font-weight:bold; font-size:11px; text-align:right; padding:4px 6px; border:0.8px solid #bbb;'>SUBTOTAL [{$codigoActual}]</td>
            <td style='font-weight:bold; font-size:11px; padding:4px 6px; border:0.8px solid #bbb;'>$cantFmt</td>
            <td style='background:#e9ecef; border:0.8px solid #bbb;'></td>
            <td style='font-weight:bold; font-size:11px; padding:4px 6px; border:0.8px solid #bbb;'>\$ $montoFmt</td>
        </tr>";
            }

            $tabla .= "
    </tbody>
</table>";

            // ════════════════════════════════════════════════════════════════
            // TIPO 2: SEPARADO
            // ════════════════════════════════════════════════════════════════
        } else {

            $query = Salidas::with([
                'equipo',
                'detalle.entradaDetalle.material.unidadMedida',
                'detalle.entradaDetalle.material.objetoEspecifico',
            ]);
            if (!$sinFecha) $query->whereBetween('fecha', [$start, $end]);
            $arraySalidas = $query->orderBy('fecha', 'ASC')->get();

            foreach ($arraySalidas as $salida) {
                $fechaFmt    = date('d-m-Y', strtotime($salida->fecha));
                $descripcion = $salida->descripcion ?? '';
                $fichaNombre = $salida->ficha_nombre    ?? '';
                $fichaTalon  = $salida->ficha_talonario ?? '';
                $equipo      = $salida->equipo->nombre  ?? '';

                $tabla .= "
<table width='100%' style='border-collapse:collapse; font-family:Arial, sans-serif; margin-bottom:2px; border:0.8px solid #ccc;'>

    <tr>
        <td style='width:15%; border:0.8px solid #ccc; padding:5px 7px; font-size:11px; font-weight:bold; background:#f5f5f5;'>
            Equipo
        </td>
        <td colspan='3' style='border:0.8px solid #ccc; padding:5px 7px; font-size:11px;'>
            $equipo
        </td>
    </tr>

    <tr>
        <td style='width:15%; border:0.8px solid #ccc; padding:5px 7px; font-size:11px; font-weight:bold; background:#f5f5f5;'>
            Ficha
        </td>
        <td style='width:35%; border:0.8px solid #ccc; padding:5px 7px; font-size:11px;'>
            $fichaNombre
        </td>

        <td style='width:15%; border:0.8px solid #ccc; padding:5px 7px; font-size:11px; font-weight:bold; background:#f5f5f5;'>
            Talonario
        </td>
        <td style='width:35%; border:0.8px solid #ccc; padding:5px 7px; font-size:11px;'>
            $fichaTalon
        </td>
    </tr>

    <tr>
        <td style='border:0.8px solid #ccc; padding:5px 7px; font-size:11px; font-weight:bold; background:#f5f5f5;'>
            Descripción
        </td>
        <td colspan='3' style='border:0.8px solid #ccc; padding:5px 7px; font-size:11px;'>
            $descripcion
        </td>
    </tr>

</table>";

                $tabla .= $theadSalidas;

                $subtotal         = 0;
                $subtotalCantidad = 0;

                foreach ($salida->detalle as $det) {
                    $entDet = $det->entradaDetalle;
                    if (!$entDet || !$entDet->material) continue;

                    $objEsp    = $entDet->material->objetoEspecifico->codigo ?? '—';
                    $nombreMat = $entDet->material->nombre ?? '';
                    $medida    = $entDet->material->unidadMedida->nombre ?? '';
                    $cantidad  = $det->cantidad_salida;
                    $precio    = (float) ($entDet->precio ?? 0);
                    $total     = $cantidad * $precio;

                    $granTotal         += $total;
                    $subtotal          += $total;
                    $sumaTotalCantidad += $cantidad;
                    $subtotalCantidad  += $cantidad;

                    // ── Acumular en resumen ──
                    if (!isset($resumenObjEsp[$objEsp])) {
                        $resumenObjEsp[$objEsp] = ['cantidad' => 0, 'total' => 0];
                    }
                    $resumenObjEsp[$objEsp]['cantidad'] += $cantidad;
                    $resumenObjEsp[$objEsp]['total']    += $total;

                    $precioFmt = number_format($precio, 4);
                    $totalFmt  = number_format($total, 4);

                    $tabla .= "
        <tr>
            <td style='font-size:11px; padding:4px 6px; border:0.8px solid #ccc;'>$objEsp</td>
            <td style='font-size:11px; padding:4px 6px; border:0.8px solid #ccc;'>$nombreMat</td>
            <td style='font-size:11px; padding:4px 6px; border:0.8px solid #ccc;'>$medida</td>
            <td style='font-size:11px; padding:4px 6px; border:0.8px solid #ccc;'>$cantidad</td>
            <td style='font-size:11px; padding:4px 6px; border:0.8px solid #ccc;'>\$ $precioFmt</td>
            <td style='font-size:11px; padding:4px 6px; border:0.8px solid #ccc;'>\$ $totalFmt</td>
        </tr>";
                }

                $subtotalFmt         = number_format($subtotal, 4);
                $subtotalCantidadFmt = number_format($subtotalCantidad, 2);

                $tabla .= "
        <tr style='background:#e9ecef;'>
            <td colspan='3' style='font-weight:bold; font-size:11px; text-align:right; padding:4px 6px; border:0.8px solid #bbb;'>Subtotal cantidad:</td>
            <td style='font-weight:bold; font-size:11px; padding:4px 6px; border:0.8px solid #bbb;'>$subtotalCantidadFmt</td>
            <td style='font-weight:bold; font-size:11px; text-align:right; padding:4px 6px; border:0.8px solid #bbb;'>Subtotal:</td>
            <td style='font-weight:bold; font-size:11px; padding:4px 6px; border:0.8px solid #bbb;'>\$ $subtotalFmt</td>
        </tr>
    </tbody>
</table><br>";
            }
        }

        // ── GRAN TOTAL ────────────────────────────────────────────────────
        $granTotalFmt         = number_format($granTotal, 4);
        $sumaTotalCantidadFmt = number_format($sumaTotalCantidad, 2);

        $tabla .= "
<table width='100%' style='margin-top:10px; border-collapse:collapse;'>
    <tr>
        <td style='font-weight:bold; font-size:13px; text-align:right; border-top:2px solid #000; padding-top:6px;'>TOTAL CANTIDAD:&nbsp;&nbsp;</td>
        <td style='font-weight:bold; font-size:13px; width:12%; border-top:2px solid #000; padding-top:6px;'>$sumaTotalCantidadFmt</td>
        <td style='font-weight:bold; font-size:13px; text-align:right; border-top:2px solid #000; padding-top:6px;'>TOTAL GENERAL:&nbsp;&nbsp;</td>
        <td style='font-weight:bold; font-size:13px; width:18%; border-top:2px solid #000; padding-top:6px;'>\$ $granTotalFmt</td>
    </tr>
</table>";

        // ════════════════════════════════════════════════════════════════
        // CUADRO RESUMEN POR OBJETO ESPECÍFICO
        // ════════════════════════════════════════════════════════════════
        ksort($resumenObjEsp); // Ordenar por código de Obj. Esp.

        $tabla .= "
<div style='height:18px; line-height:18px; font-size:1px;'>&nbsp;</div>
<table width='100%' style='border-collapse:collapse; font-family:Arial, sans-serif;'>
    <thead>
        <tr style='background:#6c757d;'>
            <td colspan='3' style='color:#fff; font-weight:bold; font-size:12px; padding:6px 8px; border:0.8px solid #888; text-align:center; letter-spacing:0.5px;'>
                RESUMEN POR CODIGO PRESUPUESTARIO
            </td>
        </tr>
        <tr style='background:#6c757d;'>
            <td style='color:#fff; font-weight:bold; font-size:11px; padding:5px 8px; border:0.8px solid #888; width:20%;'>Cod. Presu.</td>
            <td style='color:#fff; font-weight:bold; font-size:11px; padding:5px 8px; border:0.8px solid #888; width:40%; text-align:right;'>Cantidad Total</td>
            <td style='color:#fff; font-weight:bold; font-size:11px; padding:5px 8px; border:0.8px solid #888; width:40%; text-align:right;'>Monto Total (\$)</td>
        </tr>
    </thead>
    <tbody>";

        $filaIndex = 0;
        foreach ($resumenObjEsp as $codigo => $datos) {
            $bgFila    = ($filaIndex % 2 === 0) ? '#ffffff' : '#f0f4fa';
            $cantFmt   = number_format($datos['cantidad'], 2);
            $montoFmt  = number_format($datos['total'], 4);

            $tabla .= "
        <tr style='background:{$bgFila};'>
            <td style='font-size:11px; font-weight:bold; padding:5px 8px; border:0.8px solid #ccc;'>{$codigo}</td>
            <td style='font-size:11px; padding:5px 8px; border:0.8px solid #ccc; text-align:right;'>{$cantFmt}</td>
            <td style='font-size:11px; padding:5px 8px; border:0.8px solid #ccc; text-align:right;'>\$ {$montoFmt}</td>
        </tr>";
            $filaIndex++;
        }

        // Fila de totales del resumen
        $tabla .= "
        <tr style='background:#e9ecef;'>
            <td style='font-weight:bold; font-size:11px; padding:5px 8px; border:0.8px solid #bbb;'>TOTAL</td>
            <td style='font-weight:bold; font-size:11px; padding:5px 8px; border:0.8px solid #bbb; text-align:right;'>{$sumaTotalCantidadFmt}</td>
            <td style='font-weight:bold; font-size:11px; padding:5px 8px; border:0.8px solid #bbb; text-align:right;'>\$ {$granTotalFmt}</td>
        </tr>
    </tbody>
</table>";

        $mpdf = new \Mpdf\Mpdf([
            'tempDir'       => sys_get_temp_dir(),
            'format'        => 'LETTER',
            'margin_top'    => 15,
            'margin_bottom' => 15,
            'margin_left'   => 15,
            'margin_right'  => 15,
        ]);
        $mpdf->SetTitle('Reporte de Materiales Entregados');
        $mpdf->showImageErrors = false;
        $stylesheet = file_get_contents('css/cssregistro.css');
        $mpdf->WriteHTML($stylesheet, 1);
        $mpdf->setFooter('Página: {PAGENO}/{nb}');
        $mpdf->WriteHTML($tabla, 2);
        $mpdf->Output('salidas_' . date('Ymd_His') . '.pdf', 'I');
    }


    public function vistaReporteGenerales()
    {
        $materiales = Materiales::orderBy('nombre')->get();
        $informacionGeneral = InformacionGeneral::where('id', 1)->first();

        return view('backend.reportes.vistageneralreportes', compact('materiales', 'informacionGeneral'));
    }

    public function pdfQueHaEntradoProyectos($desde, $hasta, $tipo = 2)
    {
        $sinFecha     = ($desde === 'null' || $desde === '' || $hasta === 'null' || $hasta === '');
        $fechaHoy     = Carbon::now('America/El_Salvador')->format('d-m-Y');
        $logoalcaldia = 'images/logo.png';

        if (!$sinFecha) {
            $start      = date('Y-m-d 00:00:00', strtotime($desde));
            $end        = date('Y-m-d 23:59:59', strtotime($hasta));
            $fechaLabel = date('d-m-Y', strtotime($desde)) . '  —  ' . date('d-m-Y', strtotime($hasta));
        } else {
            $fechaLabel = 'Todas las fechas';
        }

        $encabezado = "
<table width='100%' style='border-collapse:collapse; font-family:Arial, sans-serif;'>
    <tr>
        <td style='width:25%; border:0.8px solid #000; padding:6px 8px;'>
            <table width='100%'><tr>
                <td style='width:30%; text-align:left;'><img src='{$logoalcaldia}' style='height:38px'></td>
                <td style='width:70%; text-align:left; color:#104e8c; font-size:13px; font-weight:bold; line-height:1.3;'>SANTA ANA NORTE<br>EL SALVADOR</td>
            </tr></table>
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
<table width='100%' style='border-collapse:collapse; font-family:Arial, sans-serif; margin-bottom:8px;'>
    <tr>
        <td style='width:15%; border:0.8px solid #ccc; padding:6px 8px; font-size:11px; font-weight:bold; background:#f5f5f5;'>PERIODO</td>
        <td style='width:50%; border:0.8px solid #ccc; padding:6px 8px; font-size:11px;'>$fechaLabel</td>
        <td style='width:15%; border:0.8px solid #ccc; padding:6px 8px; font-size:11px; font-weight:bold; background:#f5f5f5; text-align:center;'>FECHA</td>
        <td style='width:20%; border:0.8px solid #ccc; padding:6px 8px; font-size:11px; text-align:center;'>$fechaHoy</td>
    </tr>
</table>";

        $granTotal         = 0;
        $sumaTotalCantidad = 0;
        $tabla             = $encabezado;

        // ── Acumulador para el resumen de Objetos Específicos ─────────────────
        $resumenObjEsp = [];

        // ── CABECERA COLUMNAS ─────────────────────────────────────────────
        $theadEntradas = "
<table width='100%' style='border-collapse:collapse; font-family:Arial, sans-serif; margin-bottom:8px; border:0.8px solid #aaa;'>
    <thead>
        <tr style='background:#6c757d;'>
            <td style='font-weight:bold; width:11%; font-size:11px; color:#fff; padding:5px 6px; border:0.8px solid #888;'>Cod. Presu.</td>
            <td style='font-weight:bold; width:32%; font-size:11px; color:#fff; padding:5px 6px; border:0.8px solid #888;'>Material</td>
            <td style='font-weight:bold; width:12%; font-size:11px; color:#fff; padding:5px 6px; border:0.8px solid #888;'>Medida</td>
            <td style='font-weight:bold; width:10%; font-size:11px; color:#fff; padding:5px 6px; border:0.8px solid #888;'>Cantidad</td>
            <td style='font-weight:bold; width:14%; font-size:11px; color:#fff; padding:5px 6px; border:0.8px solid #888;'>Precio Unit.</td>
            <td style='font-weight:bold; width:14%; font-size:11px; color:#fff; padding:5px 6px; border:0.8px solid #888;'>Total (\$)</td>
        </tr>
    </thead>
    <tbody>";

        // ════════════════════════════════════════════════════════════════
        // TIPO 1: JUNTOS
        // ════════════════════════════════════════════════════════════════
        if ($tipo == 1) {

            $query = Entradas::with([
                'detalle.material.unidadMedida',
                'detalle.material.objetoEspecifico',
            ]);
            if (!$sinFecha) $query->whereBetween('fecha', [$start, $end]);
            $arrayEntradas = $query->orderBy('fecha', 'ASC')->get();

            $dataArray = [];

            foreach ($arrayEntradas as $entrada) {
                foreach ($entrada->detalle as $det) {
                    $idMat  = $det->id_material;
                    $precio = (float) $det->precio;
                    $clave  = $idMat . '|' . number_format($precio, 4, '.', '');

                    if (!isset($dataArray[$clave])) {
                        $dataArray[$clave] = [
                            'objespec' => $det->material->objetoEspecifico->codigo ?? '—',
                            'nombre'   => $det->material->nombre ?? '',
                            'medida'   => $det->material->unidadMedida->nombre ?? '',
                            'cantidad' => 0,
                            'total'    => 0,
                            'precio'   => $precio,
                        ];
                    }
                    $dataArray[$clave]['cantidad'] += $det->cantidad_inicial;
                    $dataArray[$clave]['total']    += ($precio * $det->cantidad_inicial);
                }
            }

            usort($dataArray, function ($a, $b) {
                $cmp = strcmp($a['objespec'], $b['objespec']);
                return $cmp !== 0 ? $cmp : strcmp($a['nombre'], $b['nombre']);
            });

            $tabla .= $theadEntradas;

            $codigoActual    = null;
            $subtotalCodigo  = 0;
            $subtotalCantCod = 0;

            foreach ($dataArray as $info) {
                if ($codigoActual !== null && $info['objespec'] !== $codigoActual) {
                    $cantFmt  = number_format($subtotalCantCod, 2);
                    $montoFmt = number_format($subtotalCodigo, 4);
                    $tabla .= "
        <tr style='background:#e9ecef;'>
            <td colspan='3' style='font-weight:bold; font-size:11px; text-align:right; padding:4px 6px; border:0.8px solid #bbb;'>SUBTOTAL [{$codigoActual}]</td>
            <td style='font-weight:bold; font-size:11px; padding:4px 6px; border:0.8px solid #bbb;'>$cantFmt</td>
            <td style='background:#e9ecef; border:0.8px solid #bbb;'></td>
            <td style='font-weight:bold; font-size:11px; padding:4px 6px; border:0.8px solid #bbb;'>\$ $montoFmt</td>
        </tr>";
                    $subtotalCodigo  = 0;
                    $subtotalCantCod = 0;
                }

                $codigoActual      = $info['objespec'];
                $subtotalCodigo   += $info['total'];
                $subtotalCantCod  += $info['cantidad'];
                $granTotal        += $info['total'];
                $sumaTotalCantidad += $info['cantidad'];

                // ── Acumular en resumen ──
                if (!isset($resumenObjEsp[$info['objespec']])) {
                    $resumenObjEsp[$info['objespec']] = ['cantidad' => 0, 'total' => 0];
                }
                $resumenObjEsp[$info['objespec']]['cantidad'] += $info['cantidad'];
                $resumenObjEsp[$info['objespec']]['total']    += $info['total'];

                $precioFmt = number_format($info['precio'], 4);
                $totalFmt  = number_format($info['total'], 4);

                $tabla .= "
        <tr>
            <td style='font-size:11px; padding:4px 6px; border:0.8px solid #ccc;'>{$info['objespec']}</td>
            <td style='font-size:11px; padding:4px 6px; border:0.8px solid #ccc;'>{$info['nombre']}</td>
            <td style='font-size:11px; padding:4px 6px; border:0.8px solid #ccc;'>{$info['medida']}</td>
            <td style='font-size:11px; padding:4px 6px; border:0.8px solid #ccc;'>{$info['cantidad']}</td>
            <td style='font-size:11px; padding:4px 6px; border:0.8px solid #ccc;'>\$ $precioFmt</td>
            <td style='font-size:11px; padding:4px 6px; border:0.8px solid #ccc;'>\$ $totalFmt</td>
        </tr>";
            }

            if ($codigoActual !== null) {
                $cantFmt  = number_format($subtotalCantCod, 2);
                $montoFmt = number_format($subtotalCodigo, 4);
                $tabla .= "
        <tr style='background:#e9ecef;'>
            <td colspan='3' style='font-weight:bold; font-size:11px; text-align:right; padding:4px 6px; border:0.8px solid #bbb;'>SUBTOTAL [{$codigoActual}]</td>
            <td style='font-weight:bold; font-size:11px; padding:4px 6px; border:0.8px solid #bbb;'>$cantFmt</td>
            <td style='background:#e9ecef; border:0.8px solid #bbb;'></td>
            <td style='font-weight:bold; font-size:11px; padding:4px 6px; border:0.8px solid #bbb;'>\$ $montoFmt</td>
        </tr>";
            }

            $tabla .= "
    </tbody>
</table>";

            // ════════════════════════════════════════════════════════════════
            // TIPO 2: SEPARADO
            // ════════════════════════════════════════════════════════════════
        } else {

            $query = Entradas::with([
                'tipoEntrada',
                'tipoCompra',
                'proveedor',                               // ← eager load proveedor
                'detalle.material.unidadMedida',
                'detalle.material.objetoEspecifico',
            ]);
            if (!$sinFecha) $query->whereBetween('fecha', [$start, $end]);
            $arrayEntradas = $query->orderBy('fecha', 'ASC')->get();

            foreach ($arrayEntradas as $entrada) {
                $fechaFmt    = date('d-m-Y', strtotime($entrada->fecha));
                $tipoEntrada = $entrada->tipoEntrada->nombre   ?? '';
                $tipoCompra  = $entrada->tipoCompra->nombre    ?? '';
                $proveedor   = $entrada->proveedor->nombre     ?? '—';   // ← nuevo
                $factura     = $entrada->factura               ?? '';
                $descripcion = $entrada->descripcion           ?? '';

                $tabla .= "
<table width='100%' style='border-collapse:collapse; font-family:Arial, sans-serif; margin-bottom:2px; border:0.8px solid #ccc;'>
    <tr>
        <td style='width:13%; border:0.8px solid #ccc; padding:5px 7px; font-size:11px; font-weight:bold; background:#f5f5f5;'>Fecha</td>
        <td style='width:20%; border:0.8px solid #ccc; padding:5px 7px; font-size:11px;'>$fechaFmt</td>
        <td style='width:15%; border:0.8px solid #ccc; padding:5px 7px; font-size:11px; font-weight:bold; background:#f5f5f5;'>Tipo Entrada</td>
        <td style='width:20%; border:0.8px solid #ccc; padding:5px 7px; font-size:11px;'>$tipoEntrada</td>
        <td style='width:12%; border:0.8px solid #ccc; padding:5px 7px; font-size:11px; font-weight:bold; background:#f5f5f5;'>Tipo Compra</td>
        <td style='width:20%; border:0.8px solid #ccc; padding:5px 7px; font-size:11px;'>$tipoCompra</td>
    </tr>
    <tr>
        <td style='border:0.8px solid #ccc; padding:5px 7px; font-size:11px; font-weight:bold; background:#f5f5f5;'>Factura</td>
        <td style='border:0.8px solid #ccc; padding:5px 7px; font-size:11px;'>$factura</td>
        <td style='border:0.8px solid #ccc; padding:5px 7px; font-size:11px; font-weight:bold; background:#f5f5f5;'>Proveedor</td>
        <td style='border:0.8px solid #ccc; padding:5px 7px; font-size:11px;'>$proveedor</td>
        <td colspan='2' style='border:0.8px solid #ccc; padding:5px 7px; font-size:11px;'>$descripcion</td>
    </tr>
</table>";

                $tabla .= $theadEntradas;

                $subtotal         = 0;
                $subtotalCantidad = 0;

                foreach ($entrada->detalle as $det) {
                    $objEsp    = $det->material->objetoEspecifico->codigo ?? '—';
                    $nombreMat = $det->material->nombre ?? '';
                    $medida    = $det->material->unidadMedida->nombre ?? '';
                    $cantidad  = $det->cantidad_inicial;
                    $precio    = (float) $det->precio;
                    $total     = $cantidad * $precio;

                    $granTotal         += $total;
                    $subtotal          += $total;
                    $sumaTotalCantidad += $cantidad;
                    $subtotalCantidad  += $cantidad;

                    // ── Acumular en resumen ──
                    if (!isset($resumenObjEsp[$objEsp])) {
                        $resumenObjEsp[$objEsp] = ['cantidad' => 0, 'total' => 0];
                    }
                    $resumenObjEsp[$objEsp]['cantidad'] += $cantidad;
                    $resumenObjEsp[$objEsp]['total']    += $total;

                    $precioFmt = number_format($precio, 4);
                    $totalFmt  = number_format($total, 4);

                    $tabla .= "
        <tr>
            <td style='font-size:11px; padding:4px 6px; border:0.8px solid #ccc;'>$objEsp</td>
            <td style='font-size:11px; padding:4px 6px; border:0.8px solid #ccc;'>$nombreMat</td>
            <td style='font-size:11px; padding:4px 6px; border:0.8px solid #ccc;'>$medida</td>
            <td style='font-size:11px; padding:4px 6px; border:0.8px solid #ccc;'>$cantidad</td>
            <td style='font-size:11px; padding:4px 6px; border:0.8px solid #ccc;'>\$ $precioFmt</td>
            <td style='font-size:11px; padding:4px 6px; border:0.8px solid #ccc;'>\$ $totalFmt</td>
        </tr>";
                }

                $subtotalFmt         = number_format($subtotal, 4);
                $subtotalCantidadFmt = number_format($subtotalCantidad, 2);

                $tabla .= "
        <tr style='background:#e9ecef;'>
            <td colspan='3' style='font-weight:bold; font-size:11px; text-align:right; padding:4px 6px; border:0.8px solid #bbb;'>Subtotal cantidad:</td>
            <td style='font-weight:bold; font-size:11px; padding:4px 6px; border:0.8px solid #bbb;'>$subtotalCantidadFmt</td>
            <td style='font-weight:bold; font-size:11px; text-align:right; padding:4px 6px; border:0.8px solid #bbb;'>Subtotal:</td>
            <td style='font-weight:bold; font-size:11px; padding:4px 6px; border:0.8px solid #bbb;'>\$ $subtotalFmt</td>
        </tr>
    </tbody>
</table><br>";
            }
        }

        // ── GRAN TOTAL ────────────────────────────────────────────────────
        $granTotalFmt         = number_format($granTotal, 4);
        $sumaTotalCantidadFmt = number_format($sumaTotalCantidad, 2);

        $tabla .= "
<table width='100%' style='margin-top:10px; border-collapse:collapse;'>
    <tr>
        <td style='font-weight:bold; font-size:13px; text-align:right; border-top:2px solid #000; padding-top:6px;'>TOTAL CANTIDAD:&nbsp;&nbsp;</td>
        <td style='font-weight:bold; font-size:13px; width:12%; border-top:2px solid #000; padding-top:6px;'>$sumaTotalCantidadFmt</td>
        <td style='font-weight:bold; font-size:13px; text-align:right; border-top:2px solid #000; padding-top:6px;'>TOTAL GENERAL:&nbsp;&nbsp;</td>
        <td style='font-weight:bold; font-size:13px; width:18%; border-top:2px solid #000; padding-top:6px;'>\$ $granTotalFmt</td>
    </tr>
</table>";

        // ════════════════════════════════════════════════════════════════
        // CUADRO RESUMEN POR OBJETO ESPECÍFICO
        // ════════════════════════════════════════════════════════════════
        ksort($resumenObjEsp);

        $tabla .= "
<div style='height:18px; line-height:18px; font-size:1px;'>&nbsp;</div>
<table width='100%' style='border-collapse:collapse; font-family:Arial, sans-serif;'>
    <thead>
        <tr style='background:#6c757d;'>
            <td colspan='3' style='color:#fff; font-weight:bold; font-size:12px; padding:6px 8px; border:0.8px solid #888; text-align:center; letter-spacing:0.5px;'>
                RESUMEN POR CODIGO PRESUPUESTARIO
            </td>
        </tr>
        <tr style='background:#6c757d;'>
            <td style='color:#fff; font-weight:bold; font-size:11px; padding:5px 8px; border:0.8px solid #888; width:20%;'>Cod. Presu.</td>
            <td style='color:#fff; font-weight:bold; font-size:11px; padding:5px 8px; border:0.8px solid #888; width:40%; text-align:right;'>Cantidad Total</td>
            <td style='color:#fff; font-weight:bold; font-size:11px; padding:5px 8px; border:0.8px solid #888; width:40%; text-align:right;'>Monto Total (\$)</td>
        </tr>
    </thead>
    <tbody>";

        $filaIndex = 0;
        foreach ($resumenObjEsp as $codigo => $datos) {
            $bgFila   = ($filaIndex % 2 === 0) ? '#ffffff' : '#f0f0f0';
            $cantFmt  = number_format($datos['cantidad'], 2);
            $montoFmt = number_format($datos['total'], 4);

            $tabla .= "
        <tr style='background:{$bgFila};'>
            <td style='font-size:11px; font-weight:bold; padding:5px 8px; border:0.8px solid #ccc;'>{$codigo}</td>
            <td style='font-size:11px; padding:5px 8px; border:0.8px solid #ccc; text-align:right;'>{$cantFmt}</td>
            <td style='font-size:11px; padding:5px 8px; border:0.8px solid #ccc; text-align:right;'>\$ {$montoFmt}</td>
        </tr>";
            $filaIndex++;
        }

        $tabla .= "
        <tr style='background:#e9ecef;'>
            <td style='font-weight:bold; font-size:11px; padding:5px 8px; border:0.8px solid #bbb;'>TOTAL</td>
            <td style='font-weight:bold; font-size:11px; padding:5px 8px; border:0.8px solid #bbb; text-align:right;'>{$sumaTotalCantidadFmt}</td>
            <td style='font-weight:bold; font-size:11px; padding:5px 8px; border:0.8px solid #bbb; text-align:right;'>\$ {$granTotalFmt}</td>
        </tr>
    </tbody>
</table>";

        $mpdf = new \Mpdf\Mpdf([
            'tempDir'       => sys_get_temp_dir(),
            'format'        => 'LETTER',
            'margin_top'    => 15,
            'margin_bottom' => 15,
            'margin_left'   => 15,
            'margin_right'  => 15,
        ]);
        $mpdf->SetTitle('Reporte de Materiales Recibidos');
        $mpdf->showImageErrors = false;
        $stylesheet = file_get_contents('css/cssregistro.css');
        $mpdf->WriteHTML($stylesheet, 1);
        $mpdf->setFooter('Página: {PAGENO}/{nb}');
        $mpdf->WriteHTML($tabla, 2);
        $mpdf->Output('entradas_' . date('Ymd_His') . '.pdf', 'I');
    }









    public function actualizarPxInformacionGeneral(Request $request)
    {
        $rules = [
            'nombre_reporte'  => 'nullable|string|max:100',
            'nombre_reporte2' => 'nullable|string|max:100',
            'nombre_reporte3' => 'nullable|string|max:100',
            'salto_pagina'    => 'required|boolean',
            'px_firmas'       => 'required|numeric',
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

            $info->nombre_reporte  = $request->nombre_reporte;
            $info->nombre_reporte2 = $request->nombre_reporte2;
            $info->nombre_reporte3 = $request->nombre_reporte3;
            $info->salto_pagina    = (int) $request->salto_pagina;
            $info->px_firmas       = (int) $request->px_firmas;

            $info->save();

            return ['success' => 1];

        } catch (\Throwable $e) {

            Log::error(
                'actualizarPxInformacionGeneral: ' . $e->getMessage()
            );

            return ['success' => 99];
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



    public function pdfInventarioActual($idMaterial = 0)
    {
        $fechaHoy     = Carbon::now('America/El_Salvador')->format('d-m-Y');
        $logoalcaldia = 'images/logo.png';

        // ── Calcular stock: SUM(entradas) - SUM(salidas) por material+precio ──
        $queryEntradas = \DB::table('entradas_detalle as ed')
            ->join('materiales as m', 'm.id', '=', 'ed.id_material')
            ->leftJoin('objeto_especifico as oe', 'oe.id', '=', 'm.id_objespecifico')
            ->leftJoin('unidadmedida as um', 'um.id', '=', 'm.id_medida')
            ->select(
                'ed.id_material',
                'ed.precio',
                'm.nombre as nombre',
                'm.codigo as codigo_mat',
                'um.nombre as medida',
                'oe.codigo as objespec',
                \DB::raw('SUM(ed.cantidad_inicial) as total_entradas')
            )
            ->groupBy('ed.id_material', 'ed.precio', 'm.nombre', 'm.codigo', 'um.nombre', 'oe.codigo');

        if ($idMaterial && $idMaterial != 0) {
            $queryEntradas->where('ed.id_material', $idMaterial);
        }

        $entradas = $queryEntradas->get()->keyBy(function ($row) {
            return $row->id_material . '|' . number_format((float)$row->precio, 4, '.', '');
        });

        $querySalidas = \DB::table('salidas_detalle as sd')
            ->join('entradas_detalle as ed', 'ed.id', '=', 'sd.id_entrada_detalle')
            ->select(
                'ed.id_material',
                'ed.precio',
                \DB::raw('SUM(sd.cantidad_salida) as total_salidas')
            )
            ->groupBy('ed.id_material', 'ed.precio');

        if ($idMaterial && $idMaterial != 0) {
            $querySalidas->where('ed.id_material', $idMaterial);
        }

        $salidas = $querySalidas->get()->keyBy(function ($row) {
            return $row->id_material . '|' . number_format((float)$row->precio, 4, '.', '');
        });

        // ── Construir array de stock ──────────────────────────────────────
        $stock = [];
        foreach ($entradas as $clave => $ent) {
            $totalSalidas = isset($salidas[$clave]) ? (float)$salidas[$clave]->total_salidas : 0;
            $disponible   = (float)$ent->total_entradas - $totalSalidas;

            if ($disponible <= 0) continue; // solo cantidad > 0

            $stock[] = [
                'objespec'  => $ent->objespec  ?? '—',
                'nombre'    => $ent->nombre    ?? '',
                'medida'    => $ent->medida    ?? '',
                'codigo'    => $ent->codigo_mat ?? '',
                'precio'    => (float)$ent->precio,
                'cantidad'  => $disponible,
                'total'     => $disponible * (float)$ent->precio,
            ];
        }

        // Ordenar por objeto específico → nombre
        usort($stock, function ($a, $b) {
            $cmp = strcmp($a['objespec'], $b['objespec']);
            return $cmp !== 0 ? $cmp : strcmp($a['nombre'], $b['nombre']);
        });

        // ── Título dinámico ───────────────────────────────────────────────
        $tituloMaterial = 'Todos los materiales';
        if ($idMaterial && $idMaterial != 0) {
            $mat = \App\Models\Materiales::find($idMaterial);
            $tituloMaterial = $mat ? $mat->nombre : 'Material #' . $idMaterial;
        }

        // ── Encabezado ────────────────────────────────────────────────────
        $encabezado = "
<table width='100%' style='border-collapse:collapse; font-family:Arial, sans-serif;'>
    <tr>
        <td style='width:25%; border:0.8px solid #000; padding:6px 8px;'>
            <table width='100%'><tr>
                <td style='width:30%; text-align:left;'><img src='{$logoalcaldia}' style='height:38px'></td>
                <td style='width:70%; text-align:left; color:#104e8c; font-size:13px; font-weight:bold; line-height:1.3;'>SANTA ANA NORTE<br>EL SALVADOR</td>
            </tr></table>
        </td>
        <td style='width:50%; border-top:0.8px solid #000; border-bottom:0.8px solid #000; padding:6px 8px; text-align:center; font-size:15px; font-weight:bold;'>
            INVENTARIO ACTUAL DE MATERIALES
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
<table width='100%' style='border-collapse:collapse; font-family:Arial, sans-serif; margin-bottom:8px;'>
    <tr>
        <td style='width:15%; border:0.8px solid #ccc; padding:6px 8px; font-size:11px; font-weight:bold; background:#f5f5f5;'>MATERIAL</td>
        <td style='width:50%; border:0.8px solid #ccc; padding:6px 8px; font-size:11px;'>$tituloMaterial</td>
        <td style='width:15%; border:0.8px solid #ccc; padding:6px 8px; font-size:11px; font-weight:bold; background:#f5f5f5; text-align:center;'>FECHA</td>
        <td style='width:20%; border:0.8px solid #ccc; padding:6px 8px; font-size:11px; text-align:center;'>$fechaHoy</td>
    </tr>
</table>";

        // ── Tabla de datos ────────────────────────────────────────────────
        $tabla = $encabezado . "
<table width='100%' style='border-collapse:collapse; font-family:Arial, sans-serif; margin-bottom:8px; border:0.8px solid #D1D5DB;'>

<thead>
    <tr style='background:#4A5568;'>
        <td style='font-weight:bold; width:11%; font-size:11px; color:#fff; padding:5px 6px; border:0.8px solid #2D3748;'>Obj. Espec.</td>
        <td style='font-weight:bold; width:33%; font-size:11px; color:#fff; padding:5px 6px; border:0.8px solid #2D3748;'>Material</td>
        <td style='font-weight:bold; width:12%; font-size:11px; color:#fff; padding:5px 6px; border:0.8px solid #2D3748;'>Medida</td>
        <td style='font-weight:bold; width:10%; font-size:11px; color:#fff; padding:5px 6px; border:0.8px solid #2D3748;'>Disponible</td>
        <td style='font-weight:bold; width:14%; font-size:11px; color:#fff; padding:5px 6px; border:0.8px solid #2D3748;'>Precio Unit.</td>
        <td style='font-weight:bold; width:14%; font-size:11px; color:#fff; padding:5px 6px; border:0.8px solid #2D3748;'>Valor (\$)</td>
    </tr>
</thead>
    <tbody>";

        $granTotal         = 0;
        $sumaTotalCantidad = 0;
        $codigoActual      = null;
        $subtotalCodigo    = 0;
        $subtotalCantCod   = 0;

        foreach ($stock as $info) {
            // ── Subtotal al cambiar de objeto específico ──────────────────
            if ($codigoActual !== null && $info['objespec'] !== $codigoActual) {
                $cantFmt  = number_format($subtotalCantCod, 2);
                $montoFmt = number_format($subtotalCodigo, 4);
                $tabla .= "
        <tr style='background:#dce8f5;'>
            <td colspan='3' style='font-weight:bold; font-size:11px; text-align:right; padding:4px 6px; border:0.8px solid #bbb;'>SUBTOTAL [{$codigoActual}]</td>
            <td style='font-weight:bold; font-size:11px; padding:4px 6px; border:0.8px solid #bbb;'>$cantFmt</td>
            <td style='background:#dce8f5; border:0.8px solid #bbb;'></td>
            <td style='font-weight:bold; font-size:11px; padding:4px 6px; border:0.8px solid #bbb;'>\$ $montoFmt</td>
        </tr>";
                $subtotalCodigo  = 0;
                $subtotalCantCod = 0;
            }

            $codigoActual       = $info['objespec'];
            $subtotalCodigo    += $info['total'];
            $subtotalCantCod   += $info['cantidad'];
            $granTotal         += $info['total'];
            $sumaTotalCantidad += $info['cantidad'];

            $precioFmt = number_format($info['precio'], 4);
            $totalFmt  = number_format($info['total'], 4);
            $cantFmt   = number_format($info['cantidad'], 2);

            $tabla .= "
        <tr>
            <td style='font-size:11px; padding:4px 6px; border:0.8px solid #ccc;'>{$info['objespec']}</td>
            <td style='font-size:11px; padding:4px 6px; border:0.8px solid #ccc;'>{$info['nombre']}</td>
            <td style='font-size:11px; padding:4px 6px; border:0.8px solid #ccc;'>{$info['medida']}</td>
            <td style='font-size:11px; padding:4px 6px; border:0.8px solid #ccc;'>$cantFmt</td>
            <td style='font-size:11px; padding:4px 6px; border:0.8px solid #ccc;'>\$ $precioFmt</td>
            <td style='font-size:11px; padding:4px 6px; border:0.8px solid #ccc;'>\$ $totalFmt</td>
        </tr>";
        }

        // Último subtotal
        if ($codigoActual !== null) {
            $cantFmt  = number_format($subtotalCantCod, 2);
            $montoFmt = number_format($subtotalCodigo, 4);
            $tabla .= "
        <tr style='background:#dce8f5;'>
            <td colspan='3' style='font-weight:bold; font-size:11px; text-align:right; padding:4px 6px; border:0.8px solid #bbb;'>SUBTOTAL [{$codigoActual}]</td>
            <td style='font-weight:bold; font-size:11px; padding:4px 6px; border:0.8px solid #bbb;'>$cantFmt</td>
            <td style='background:#dce8f5; border:0.8px solid #bbb;'></td>
            <td style='font-weight:bold; font-size:11px; padding:4px 6px; border:0.8px solid #bbb;'>\$ $montoFmt</td>
        </tr>";
        }

        // Sin resultados
        if (empty($stock)) {
            $tabla .= "
        <tr>
            <td colspan='6' style='text-align:center; font-size:12px; padding:12px; color:#888;'>
                No hay materiales con existencias disponibles.
            </td>
        </tr>";
        }

        $tabla .= "
    </tbody>
</table>";

        // ── Gran total ────────────────────────────────────────────────────
        $granTotalFmt         = number_format($granTotal, 4);
        $sumaTotalCantidadFmt = number_format($sumaTotalCantidad, 2);

        $tabla .= "
<table width='100%' style='margin-top:10px; border-collapse:collapse;'>
    <tr>
        <td style='font-weight:bold; font-size:13px; text-align:right; border-top:2px solid #000; padding-top:6px;'>TOTAL UNIDADES:&nbsp;&nbsp;</td>
        <td style='font-weight:bold; font-size:13px; width:12%; border-top:2px solid #000; padding-top:6px;'>$sumaTotalCantidadFmt</td>
        <td style='font-weight:bold; font-size:13px; text-align:right; border-top:2px solid #000; padding-top:6px;'>VALOR TOTAL:&nbsp;&nbsp;</td>
        <td style='font-weight:bold; font-size:13px; width:18%; border-top:2px solid #000; padding-top:6px;'>\$ $granTotalFmt</td>
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
        $mpdf->SetTitle('Inventario Actual de Materiales');
        $mpdf->showImageErrors = false;
        $stylesheet = file_get_contents('css/cssregistro.css');
        $mpdf->WriteHTML($stylesheet, 1);
        $mpdf->setFooter('Página: {PAGENO}/{nb}');
        $mpdf->WriteHTML($tabla, 2);
        $mpdf->Output('inventario_' . date('Ymd_His') . '.pdf', 'I');
    }





    public function reportePDFInicialPorPeriodos($desde, $hasta)
    {
        $start = Carbon::parse($desde)->startOfDay();
        $end   = Carbon::parse($hasta)->endOfDay();

        $desdeFormat = Carbon::parse($desde)->format('d/m/Y');
        $hastaFormat = Carbon::parse($hasta)->format('d/m/Y');

        // ── Consulta: ahora se agrupa también por precio, para poder separar
        //    los "lotes" de un mismo material que tengan distinto precio ────────
        $rows = DB::select("
    WITH movimientos AS (

    SELECT
        ed.id_material,
        COALESCE(NULLIF(oe.codigo, ''), 'SIN-CODIGO') AS codigo,
        m.nombre AS descripcion,
        ed.precio,
        e.fecha AS fecha_movimiento,
        ed.cantidad_inicial AS entrada,
        0 AS salida,
        (ed.cantidad_inicial * ed.precio) AS monto_entrada,
        0 AS monto_salida
    FROM entradas_detalle ed
    INNER JOIN entradas e ON e.id = ed.id_entradas
    INNER JOIN materiales m ON m.id = ed.id_material
    LEFT JOIN objeto_especifico oe ON oe.id = m.id_objespecifico

    UNION ALL

    SELECT
        ed.id_material,
        COALESCE(NULLIF(oe.codigo, ''), 'SIN-CODIGO') AS codigo,
        m.nombre AS descripcion,
        ed.precio,
        s.fecha AS fecha_movimiento,
        0 AS entrada,
        sd.cantidad_salida AS salida,
        0 AS monto_entrada,
        (sd.cantidad_salida * ed.precio) AS monto_salida
    FROM salidas_detalle sd
    INNER JOIN salidas s ON s.id = sd.id_salida
    INNER JOIN entradas_detalle ed ON ed.id = sd.id_entrada_detalle
    INNER JOIN materiales m ON m.id = ed.id_material
    LEFT JOIN objeto_especifico oe ON oe.id = m.id_objespecifico
)

    SELECT
        id_material,
        codigo,
        descripcion,
        precio,

        SUM(CASE WHEN fecha_movimiento < ? THEN entrada - salida ELSE 0 END) AS saldo_inicial_cant,

        SUM(CASE WHEN fecha_movimiento >= ? AND fecha_movimiento <= ? THEN entrada ELSE 0 END) AS entradas_mes_cant,

        SUM(CASE WHEN fecha_movimiento >= ? AND fecha_movimiento <= ? THEN salida ELSE 0 END) AS salidas_mes_cant,

        (
            SUM(CASE WHEN fecha_movimiento < ? THEN entrada - salida ELSE 0 END)
            + SUM(CASE WHEN fecha_movimiento >= ? AND fecha_movimiento <= ? THEN entrada ELSE 0 END)
            - SUM(CASE WHEN fecha_movimiento >= ? AND fecha_movimiento <= ? THEN salida ELSE 0 END)
        ) AS saldo_final_cant,

        SUM(CASE WHEN fecha_movimiento < ? THEN monto_entrada - monto_salida ELSE 0 END) AS saldo_inicial_money,

        SUM(CASE WHEN fecha_movimiento >= ? AND fecha_movimiento <= ? THEN monto_entrada ELSE 0 END) AS entradas_mes_money,

        SUM(CASE WHEN fecha_movimiento >= ? AND fecha_movimiento <= ? THEN monto_salida ELSE 0 END) AS salidas_mes_money,

        (
            SUM(CASE WHEN fecha_movimiento < ? THEN monto_entrada - monto_salida ELSE 0 END)
            + SUM(CASE WHEN fecha_movimiento >= ? AND fecha_movimiento <= ? THEN monto_entrada ELSE 0 END)
            - SUM(CASE WHEN fecha_movimiento >= ? AND fecha_movimiento <= ? THEN monto_salida ELSE 0 END)
        ) AS saldo_final_money

    FROM movimientos
    GROUP BY id_material, codigo, descripcion, precio
    ORDER BY codigo, descripcion, precio
", [
            // saldo_inicial_cant
            $start->toDateString(),

            // entradas_mes_cant
            $start->toDateString(), $end->toDateString(),

            // salidas_mes_cant
            $start->toDateString(), $end->toDateString(),

            // saldo_final_cant
            $start->toDateString(),
            $start->toDateString(), $end->toDateString(),
            $start->toDateString(), $end->toDateString(),

            // saldo_inicial_money
            $start->toDateString(),

            // entradas_mes_money
            $start->toDateString(), $end->toDateString(),

            // salidas_mes_money
            $start->toDateString(), $end->toDateString(),

            // saldo_final_money
            $start->toDateString(),
            $start->toDateString(), $end->toDateString(),
            $start->toDateString(), $end->toDateString(),
        ]);

        $rows = array_values(array_filter($rows, function ($r) {
            $inicial  = (float) ($r->saldo_inicial_cant ?? 0);
            $entradas = (float) ($r->entradas_mes_cant ?? 0);
            $salidas  = (float) ($r->salidas_mes_cant ?? 0);
            $final    = (float) ($r->saldo_final_cant ?? 0);

            return !($inicial == 0 && $entradas == 0 && $salidas == 0 && $final == 0);
        }));

        // ── Totales generales (sin cambios: la suma total no varía por separar
        //    los lotes por precio) ──────────────────────────────────────────────
        $totales = [
            'inicial_cant'   => 0,
            'entradas_cant'  => 0,
            'salidas_cant'   => 0,
            'final_cant'     => 0,
            'inicial_money'  => 0.0,
            'entradas_money' => 0.0,
            'salidas_money'  => 0.0,
            'final_money'    => 0.0,
        ];

        $sumPorCodigo = [];

        foreach ($rows as $r) {
            $totales['inicial_cant']   += (int)   ($r->saldo_inicial_cant  ?? 0);
            $totales['entradas_cant']  += (int)   ($r->entradas_mes_cant   ?? 0);
            $totales['salidas_cant']   += (int)   ($r->salidas_mes_cant    ?? 0);
            $totales['final_cant']     += (int)   ($r->saldo_final_cant    ?? 0);
            $totales['inicial_money']  += (float) ($r->saldo_inicial_money ?? 0);
            $totales['entradas_money'] += (float) ($r->entradas_mes_money  ?? 0);
            $totales['salidas_money']  += (float) ($r->salidas_mes_money   ?? 0);
            $totales['final_money']    += (float) ($r->saldo_final_money   ?? 0);

            $codigo = $r->codigo ?? 'SIN-CODIGO';

            if (!isset($sumPorCodigo[$codigo])) {
                $sumPorCodigo[$codigo] = [
                    'codigo'         => $codigo,
                    'inicial_cant'   => 0,
                    'entradas_cant'  => 0,
                    'salidas_cant'   => 0,
                    'final_cant'     => 0,
                    'inicial_money'  => 0.0,
                    'entradas_money' => 0.0,
                    'salidas_money'  => 0.0,
                    'final_money'    => 0.0,
                ];
            }

            $sumPorCodigo[$codigo]['inicial_cant']   += (int)   ($r->saldo_inicial_cant  ?? 0);
            $sumPorCodigo[$codigo]['entradas_cant']  += (int)   ($r->entradas_mes_cant   ?? 0);
            $sumPorCodigo[$codigo]['salidas_cant']   += (int)   ($r->salidas_mes_cant    ?? 0);
            $sumPorCodigo[$codigo]['final_cant']     += (int)   ($r->saldo_final_cant    ?? 0);
            $sumPorCodigo[$codigo]['inicial_money']  += (float) ($r->saldo_inicial_money ?? 0);
            $sumPorCodigo[$codigo]['entradas_money'] += (float) ($r->entradas_mes_money  ?? 0);
            $sumPorCodigo[$codigo]['salidas_money']  += (float) ($r->salidas_mes_money   ?? 0);
            $sumPorCodigo[$codigo]['final_money']    += (float) ($r->saldo_final_money   ?? 0);
        }

        // ── Agrupar filas por material, para poder ocultar código/descripción
        //    repetidos y calcular subtotal cuando hay más de un lote ────────────
        $porMaterial = [];

        foreach ($rows as $r) {
            $idMat = $r->id_material;

            if (!isset($porMaterial[$idMat])) {
                $porMaterial[$idMat] = [
                    'codigo'      => $r->codigo,
                    'descripcion' => $r->descripcion,
                    'lotes'       => [],
                ];
            }

            $porMaterial[$idMat]['lotes'][] = $r;
        }

        // ── mPDF ──────────────────────────────────────────────────────────────
        $mpdf = new \Mpdf\Mpdf([
            'tempDir'     => sys_get_temp_dir(),
            'format'      => 'LETTER',
            'orientation' => 'L',
        ]);

        $mpdf->SetTitle('Reporte Mensual de Inventario');
        $mpdf->showImageErrors = false;

        $logoalcaldia = 'images/gobiernologo.jpg';

        $encabezado = "
<table width='100%' style='border-collapse:collapse; font-family: Arial, sans-serif;'>
<tr>
    <td style='width:25%; border:0.8px solid #000; padding:6px 8px;'>
        <table width='100%'>
            <tr>
                <td style='width:30%; text-align:left;'>
                    <img src='{$logoalcaldia}' style='height:38px'>
                </td>
                <td style='width:70%; text-align:left; color:#104e8c; font-size:13px; font-weight:bold; line-height:1.3;'>
                    REPORTE DE MOVIMIENTO DE INVENTARIO
                </td>
            </tr>
        </table>
    </td>
    <td style='width:50%; border-top:0.8px solid #000; border-bottom:0.8px solid #000; padding:6px 8px; text-align:center; font-size:15px; font-weight:bold;'>
        CONTROL DE ENTRADAS / SALIDAS
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
";

        $encabezado .= "<span style='font-weight:bold;'>Del {$desdeFormat} al {$hastaFormat}</span><br>";

        if (file_exists(public_path('css/cssbodega.css'))) {
            $stylesheet = file_get_contents(public_path('css/cssbodega.css'));
            $mpdf->WriteHTML($stylesheet, \Mpdf\HTMLParserMode::HEADER_CSS);
        }

        // ── Estilos para filas de lote repetido (mismo material) ────────────────
        $tdLote = "background:#f7f9fd;";

        // ── Tabla principal ───────────────────────────────────────────────────
        $html = $encabezado;

        $html .= "
<table width='100%' border='1' cellspacing='0' cellpadding='4' style='border-collapse:collapse; font-size:11px; margin-top:8px'>
<thead style='background:#f2f4f8'>
    <tr>
        <th>#</th>
        <th>COD</th>
        <th>DESCRIPCIÓN</th>
        <th style='text-align:right; width:8%'>PRECIO</th>
        <th style='text-align:right; width:6%'>INICIAL</th>
        <th style='text-align:right; width:7%'>$ INICIAL</th>
        <th style='text-align:right; width:8%'>ENTRADAS</th>
        <th style='text-align:right; width:9%'>$ ENTRADAS</th>
        <th style='text-align:right; width:8%'>SALIDAS</th>
        <th style='text-align:right; width:8%'>$ SALIDAS</th>
        <th style='text-align:right; width:6%'>SALDO</th>
        <th style='text-align:right; width:7%'>$ SALDO</th>
    </tr>
</thead>
<tbody>
";

        $i = 1;

        foreach ($porMaterial as $grupo) {

            $lotes      = $grupo['lotes'];
            $totalLotes = count($lotes);
            $esPrimero  = true;

            // acumuladores para el subtotal del material
            $sub = [
                'inicial_cant'   => 0,
                'entradas_cant'  => 0,
                'salidas_cant'   => 0,
                'final_cant'     => 0,
                'inicial_money'  => 0.0,
                'entradas_money' => 0.0,
                'salidas_money'  => 0.0,
                'final_money'    => 0.0,
            ];

            foreach ($lotes as $r) {

                $sub['inicial_cant']   += (int)   ($r->saldo_inicial_cant  ?? 0);
                $sub['entradas_cant']  += (int)   ($r->entradas_mes_cant   ?? 0);
                $sub['salidas_cant']   += (int)   ($r->salidas_mes_cant    ?? 0);
                $sub['final_cant']     += (int)   ($r->saldo_final_cant    ?? 0);
                $sub['inicial_money']  += (float) ($r->saldo_inicial_money ?? 0);
                $sub['entradas_money'] += (float) ($r->entradas_mes_money  ?? 0);
                $sub['salidas_money']  += (float) ($r->salidas_mes_money   ?? 0);
                $sub['final_money']    += (float) ($r->saldo_final_money   ?? 0);

                $esLoteExtra = !$esPrimero;
                $bg          = $esLoteExtra ? "style='{$tdLote}'" : '';

                if (!$esLoteExtra) {
                    $celdaNum  = "<td>{$i}</td>";
                    $celdaCod  = "<td>" . e($grupo['codigo']) . "</td>";
                    $celdaDesc = "<td>" . e($grupo['descripcion']) . "</td>";
                } else {
                    $celdaNum  = "<td {$bg}></td>";
                    $celdaCod  = "<td {$bg}></td>";
                    $celdaDesc = "<td {$bg}></td>";
                }

                $html .= "
<tr>
    {$celdaNum}
    {$celdaCod}
    {$celdaDesc}
    <td {$bg} style='" . ($esLoteExtra ? "{$tdLote} " : '') . "text-align:right'>$" . number_format($r->precio ?? 0, 4) . "</td>
    <td {$bg} style='" . ($esLoteExtra ? "{$tdLote} " : '') . "text-align:right'>" . number_format($r->saldo_inicial_cant ?? 0) . "</td>
    <td {$bg} style='" . ($esLoteExtra ? "{$tdLote} " : '') . "text-align:right'>$" . number_format($r->saldo_inicial_money ?? 0, 2) . "</td>
    <td {$bg} style='" . ($esLoteExtra ? "{$tdLote} " : '') . "text-align:right'>" . number_format($r->entradas_mes_cant ?? 0) . "</td>
    <td {$bg} style='" . ($esLoteExtra ? "{$tdLote} " : '') . "text-align:right'>$" . number_format($r->entradas_mes_money ?? 0, 2) . "</td>
    <td {$bg} style='" . ($esLoteExtra ? "{$tdLote} " : '') . "text-align:right'>" . number_format($r->salidas_mes_cant ?? 0) . "</td>
    <td {$bg} style='" . ($esLoteExtra ? "{$tdLote} " : '') . "text-align:right'>$" . number_format($r->salidas_mes_money ?? 0, 2) . "</td>
    <td {$bg} style='" . ($esLoteExtra ? "{$tdLote} " : '') . "text-align:right'>" . number_format($r->saldo_final_cant ?? 0) . "</td>
    <td {$bg} style='" . ($esLoteExtra ? "{$tdLote} " : '') . "text-align:right'>$" . number_format($r->saldo_final_money ?? 0, 2) . "</td>
</tr>
";

                $esPrimero = false;
            }

            // ── Subtotal del material (solo si tiene más de un lote/precio) ─────
            if ($totalLotes > 1) {
                $html .= "
<tr>
    <td colspan='3' style='text-align:right; font-style:italic; background:#eef2fb;'>
        Subtotal: " . e($grupo['descripcion']) . "
    </td>
    <td style='background:#eef2fb;'></td>
    <td style='text-align:right; font-weight:bold; background:#eef2fb;'>" . number_format($sub['inicial_cant']) . "</td>
    <td style='text-align:right; font-weight:bold; background:#eef2fb;'>$" . number_format($sub['inicial_money'], 2) . "</td>
    <td style='text-align:right; font-weight:bold; background:#eef2fb;'>" . number_format($sub['entradas_cant']) . "</td>
    <td style='text-align:right; font-weight:bold; background:#eef2fb;'>$" . number_format($sub['entradas_money'], 2) . "</td>
    <td style='text-align:right; font-weight:bold; background:#eef2fb;'>" . number_format($sub['salidas_cant']) . "</td>
    <td style='text-align:right; font-weight:bold; background:#eef2fb;'>$" . number_format($sub['salidas_money'], 2) . "</td>
    <td style='text-align:right; font-weight:bold; background:#eef2fb;'>" . number_format($sub['final_cant']) . "</td>
    <td style='text-align:right; font-weight:bold; background:#eef2fb;'>$" . number_format($sub['final_money'], 2) . "</td>
</tr>
";
            }

            $i++;
        }

        if (!$rows) {
            $html .= "<tr><td colspan='12' style='text-align:center; color:#888;'>Sin registros en el rango seleccionado.</td></tr>";
        }

        $html .= "
</tbody>
<tfoot>
    <tr style='font-weight:bold; background:#f9fafb'>
        <td colspan='4' style='text-align:right'>Totales:</td>
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
</table>
";

        // ── Resumen del período ───────────────────────────────────────────────
        $html .= "
<br>
<table width='60%' border='1' cellspacing='0' cellpadding='6' style='border-collapse:collapse; font-size:12px'>
<tr style='background:#eef3ff; font-weight:bold; text-align:center'>
    <td colspan='3'>Resumen del período {$desdeFormat} - {$hastaFormat}</td>
</tr>
<tr style='font-weight:bold; background:#f9fafb'>
    <td></td>
    <td style='text-align:right'>Cantidad</td>
    <td style='text-align:right'>Dinero ($)</td>
</tr>
<tr>
    <td>Ingresó (Entradas del mes)</td>
    <td style='text-align:right'>" . number_format($totales['entradas_cant']) . "</td>
    <td style='text-align:right'>$" . number_format($totales['entradas_money'], 2) . "</td>
</tr>
<tr>
    <td>Salió (Salidas del mes)</td>
    <td style='text-align:right'>" . number_format($totales['salidas_cant']) . "</td>
    <td style='text-align:right'>$" . number_format($totales['salidas_money'], 2) . "</td>
</tr>
<tr>
    <td>Disponible al cierre (Saldo final)</td>
    <td style='text-align:right'>" . number_format($totales['final_cant']) . "</td>
    <td style='text-align:right'>$" . number_format($totales['final_money'], 2) . "</td>
</tr>
</table>
";

        // ── Tabla resumen por código ──────────────────────────────────────────
        if (!empty($sumPorCodigo)) {
            $totalSaldoFinalCodigos = 0;

            $html .= "
<br><br>
<table width='100%' border='1' cellspacing='0' cellpadding='4' style='border-collapse:collapse; font-size:11px'>
<thead style='background:#f2f4f8'>
    <tr>
        <th style='width:4%'>#</th>
        <th style='width:10%'>Código</th>
        <th style='text-align:right; width:6%'>INICIAL</th>
        <th style='text-align:right; width:10%'>$ INICIAL</th>
        <th style='text-align:right; width:6%'>ENTRADAS</th>
        <th style='text-align:right; width:10%'>$ ENTRADAS</th>
        <th style='text-align:right; width:6%'>SALIDAS</th>
        <th style='text-align:right; width:10%'>$ SALIDAS</th>
        <th style='text-align:right; width:6%'>SALDO</th>
        <th style='text-align:right; width:10%'>$ SALDO</th>
    </tr>
</thead>
<tbody>
";

            $j = 1;
            foreach ($sumPorCodigo as $s) {
                $totalSaldoFinalCodigos += (float) $s['final_money'];

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
    </tr>
";
                $j++;
            }

            $html .= "
    <tr style='font-weight:bold; background:#f9fafb'>
        <td colspan='9' style='text-align:right'>TOTAL</td>
        <td style='text-align:right'>$" . number_format($totalSaldoFinalCodigos, 2) . "</td>
    </tr>
</tbody>
</table>
";
        }

        // ── Firma (3 bloques) ─────────────────────────────────────────────────
        $informacionGeneral = InformacionGeneral::where('id', 1)->first();
        $margenFirma  = (int)  ($informacionGeneral->px_firmas ?? 40);
        $saltoPagina  = (bool) ($informacionGeneral->salto_pagina ?? false);

        $estiloSalto = $saltoPagina ? "page-break-before: always;" : "";

        $html .= "
            <div style='{$estiloSalto} padding-top:{$margenFirma}px;'>
        <table width='100%' style='font-size:12px; text-align:center;'>
            <tr>
                <td style='width:33%; text-align:center;'>
        F._____________________________
                    <div style='display:block; margin-top:12px; font-weight:bold; font-size:12px;'>
                        {$informacionGeneral->nombre_reporte}
                    </div>
                </td>
                <td style='width:33%; text-align:center;'>
        F._____________________________
                    <div style='display:block; margin-top:12px; font-weight:bold; font-size:12px;'>
                        {$informacionGeneral->nombre_reporte2}
                    </div>
                </td>
                <td style='width:33%; text-align:center;'>
        F._____________________________
                    <div style='display:block; margin-top:12px; font-weight:bold; font-size:12px;'>
                        {$informacionGeneral->nombre_reporte3}
                    </div>
                </td>
            </tr>
        </table>
    </div>
    ";

        $mpdf->setFooter('Página {PAGENO} de {nb}');
        $mpdf->WriteHTML($html, \Mpdf\HTMLParserMode::HTML_BODY);
        $mpdf->Output();
    }


}
