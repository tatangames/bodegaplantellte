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
        // TIPO 1: JUNTOS  (agrupa todos los materiales, subtotales por Obj. Espec.)
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
            // TIPO 2: SEPARADO (una viñeta por salida con su ficha)
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

                // ── Viñeta de encabezado de la salida ────────────────────
                $tabla .= "
<table width='100%' style='border-collapse:collapse; font-family:Arial, sans-serif; margin-bottom:2px; border:0.8px solid #ccc;'>
    <tr>
        <td style='width:13%; border:0.8px solid #ccc; padding:5px 7px; font-size:11px; font-weight:bold; background:#f5f5f5;'>Fecha</td>
        <td style='width:20%; border:0.8px solid #ccc; padding:5px 7px; font-size:11px;'>$fechaFmt</td>
        <td style='width:15%; border:0.8px solid #ccc; padding:5px 7px; font-size:11px; font-weight:bold; background:#f5f5f5;'>Equipo</td>
        <td style='width:52%; border:0.8px solid #ccc; padding:5px 7px; font-size:11px;'>$equipo</td>
    </tr>
    <tr>
        <td style='border:0.8px solid #ccc; padding:5px 7px; font-size:11px; font-weight:bold; background:#f5f5f5;'>Ficha</td>
        <td style='border:0.8px solid #ccc; padding:5px 7px; font-size:11px;'>$fichaNombre</td>
        <td style='border:0.8px solid #ccc; padding:5px 7px; font-size:11px; font-weight:bold; background:#f5f5f5;'>Talonario</td>
        <td style='border:0.8px solid #ccc; padding:5px 7px; font-size:11px;'>$fichaTalon</td>
    </tr>
    <tr>
        <td style='border:0.8px solid #ccc; padding:5px 7px; font-size:11px; font-weight:bold; background:#f5f5f5;'>Descripción</td>
        <td colspan='3' style='border:0.8px solid #ccc; padding:5px 7px; font-size:11px;'>$descripcion</td>
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


    public function vistaQueHaEntrado()
    {
        $materiales = Materiales::orderBy('nombre')->get();

        return view('backend.reportes.vistaquehaentrado', compact('materiales'));
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
                'detalle.material.unidadMedida',
                'detalle.material.objetoEspecifico',
            ]);
            if (!$sinFecha) $query->whereBetween('fecha', [$start, $end]);
            $arrayEntradas = $query->orderBy('fecha', 'ASC')->get();

            foreach ($arrayEntradas as $entrada) {
                $fechaFmt    = date('d-m-Y', strtotime($entrada->fecha));
                $tipoEntrada = $entrada->tipoEntrada->nombre ?? '';
                $tipoCompra  = $entrada->tipoCompra->nombre  ?? '';
                $factura     = $entrada->factura     ?? '';
                $descripcion = $entrada->descripcion ?? '';

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
        <td colspan='4' style='border:0.8px solid #ccc; padding:5px 7px; font-size:11px;'>$descripcion</td>
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





}
