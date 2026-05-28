<?php

namespace App\Http\Controllers\Sistema;

use App\Http\Controllers\Controller;
use App\Models\Entradas;
use App\Models\EntradasDetalle;
use App\Models\Herramientas;
use App\Models\HistoHerramientaDescartada;
use App\Models\HistorialEntradas;
use App\Models\HistorialEntradasDeta;
use App\Models\Materiales;
use App\Models\ObjetoEspecifico;
use App\Models\SalidasDetalle;
use App\Models\TipoProyecto;
use App\Models\UnidadMedida;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class RepuestosController extends Controller
{

    public function index()
    {
        $lUnidad           = UnidadMedida::orderBy('nombre', 'ASC')->get();
        $lObjetoEspecifico = ObjetoEspecifico::with('cuenta')
            ->orderBy('nombre', 'ASC')->get();

        return view('backend.admin.inventario.vistainventario',
            compact('lUnidad', 'lObjetoEspecifico'));
    }

    public function tablaMateriales()
    {
        $filtro = request('filtro', 'todos'); // 'todos' | 'sin_objeto'

        $query = Materiales::with('objetoEspecifico')
            ->orderBy('nombre', 'ASC');

        if ($filtro === 'sin_objeto') {
            $query->whereNull('id_objespecifico');
        }

        $lista = $query->get();

        foreach ($lista as $item) {
            $medida = '';
            if ($dataUnidad = UnidadMedida::where('id', $item->id_medida)->first()) {
                $medida = $dataUnidad->nombre;
            }
            $item->medida = $medida;

            $entradas = DB::table('entradas_detalle')
                ->where('id_material', $item->id)
                ->sum('cantidad_inicial');

            $salidas = DB::table('salidas_detalle as sd')
                ->join('entradas_detalle as ed', 'ed.id', '=', 'sd.id_entrada_detalle')
                ->where('ed.id_material', $item->id)
                ->sum('sd.cantidad_salida');

            $item->total    = $entradas - $salidas;
            $item->entradas = $entradas;
            $item->salidas  = $salidas;

            // Relación ya cargada con with()
            $item->objeto_especifico = $item->objetoEspecifico;
        }

        return view('backend.admin.inventario.tablainventario', compact('lista'));
    }

    public function nuevoMaterial(Request $request)
    {
        $validar = Validator::make($request->all(), [
            'nombre'           => 'required',
            'id_objespecifico' => 'required|exists:objeto_especifico,id',
        ]);
        if ($validar->fails()) { return ['success' => 0]; }

        $dato = new Materiales();
        $dato->nombre           = $request->nombre;
        $dato->id_medida        = $request->unidad ?: null;
        $dato->id_objespecifico = $request->id_objespecifico;
        $dato->codigo           = $request->codigo;

        return $dato->save() ? ['success' => 1] : ['success' => 2];
    }

    public function informacionMaterial(Request $request)
    {
        $validar = Validator::make($request->all(), ['id' => 'required']);
        if ($validar->fails()) { return ['success' => 0]; }

        if ($lista = Materiales::where('id', $request->id)->first()) {
            $arrayUnidad         = UnidadMedida::orderBy('nombre', 'ASC')->get();
            $arrayObjetoEspecifico = ObjetoEspecifico::with('cuenta.rubro')
                ->orderBy('nombre', 'ASC')->get();

            return [
                'success'           => 1,
                'material'          => $lista,
                'unidad'            => $arrayUnidad,
                'objeto_especifico' => $arrayObjetoEspecifico,
            ];
        }

        return ['success' => 2];
    }

    public function editarMaterial(Request $request)
    {
        $validar = Validator::make($request->all(), [
            'id'               => 'required',
            'nombre'           => 'required',
            'id_objespecifico' => 'required|exists:objeto_especifico,id',
        ]);
        if ($validar->fails()) { return ['success' => 0]; }

        Materiales::where('id', $request->id)->update([
            'id_medida'        => $request->unidad ?: null,
            'id_objespecifico' => $request->id_objespecifico,
            'nombre'           => $request->nombre,
            'codigo'           => $request->codigo,
        ]);

        return ['success' => 1];
    }



    //*******************************************************************

    public function indexRegistroEntrada(){

        $arrayProyecto = TipoProyecto::where('transferido', 0)
            ->orderBy('nombre')
            ->get();

        return view('backend.admin.repuestos.registros.vistaentradaregistro', compact('arrayProyecto'));
    }


    public function buscadorMaterial(Request $request){

        if($request->get('query')){
            $query = $request->get('query');
            $data = Materiales::where('nombre', 'LIKE', "%{$query}%")
                ->get();

            foreach ($data as $dd){
                if($info = UnidadMedida::where('id', $dd->id_medida)->first()){
                    $dd->medida = "- " . $info->nombre;
                }else{
                    $dd->medida = "";
                }
            }

            $output = '<ul class="dropdown-menu" style="display:block; position:relative;">';
            $tiene = true;
            foreach($data as $row){

                // si solo hay 1 fila, No mostrara el hr, salto de linea
                if(count($data) == 1){
                    if(!empty($row)){
                        $tiene = false;
                        $output .= '
                 <li onclick="modificarValor(this)" id="'.$row->id.'"><a href="#" style="margin-left: 3px">'.$row->nombre . '  ' .$row->medida .'</a></li>
                ';
                    }
                }

                else{
                    if(!empty($row)){
                        $tiene = false;
                        $output .= '
                 <li onclick="modificarValor(this)" id="'.$row->id.'"><a href="#" style="margin-left: 3px">'.$row->nombre . ' ' .$row->medida .'</a></li>
                   <hr>
                ';
                    }
                }
            }
            $output .= '</ul>';
            if($tiene){
                $output = '';
            }
            echo $output;
        }
    }

    // GUARDAR ENTRADAS
    public function guardarEntrada(Request $request)
    {



        $rules = [
            'fecha'     => 'required',
            'tipoproyecto' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return ['success' => 0];
        }

        // ── Validar que el proyecto no esté cerrado ──
        $proyecto = Tipoproyecto::find($request->tipoproyecto);
        if (!$proyecto || $proyecto->transferido == 1) {
            return ['success' => 2]; // proyecto cerrado o no existe
        }

        DB::beginTransaction();

        try {
            $datosContenedor = json_decode($request->contenedorArray, true);

            // ── Cabecera ──
            $registro = new Entradas();
            $registro->id_tipoproyecto = $request->tipoproyecto;
            $registro->fecha = Carbon::parse($request->fecha);
            $registro->descripcion = $request->descripcion;
            $registro->factura = $request->lote;
            $registro->es_transferencia = 0;
            $registro->id_tipoproyecto_transferencia = null;
            $registro->save();

            // ── Detalle ──
            foreach ($datosContenedor as $fila) {
                $detalle = new EntradasDetalle();
                $detalle->id_entradas        = $registro->id;
                $detalle->id_material        = $fila['idMaterial'];
                $detalle->cantidad_inicial   = $fila['infoCantidad'];
                $detalle->precio             = $fila['infoPrecio'];
                $detalle->codigo             = $fila['infoCodigo'];
                $detalle->save();
            }

            DB::commit();
            return ['success' => 1];

        } catch (\Throwable $e) {
            Log::error('guardarEntrada: ' . $e);
            DB::rollback();
            return ['success' => 99];
        }
    }


    public function proyectosPorMaterial(Request $request)
    {
        $idMaterial = $request->id;

        // IDs de entradas_detalle de este material
        $idsEntradasDetalle = EntradasDetalle::where('id_material', $idMaterial)
            ->pluck('id');

        // Entradas por proyecto
        $entradas = EntradasDetalle::where('id_material', $idMaterial)
            ->with('entrada.tipoproyecto')
            ->get()
            ->groupBy(fn($item) => $item->entrada->id_tipoproyecto)
            ->map(function ($grupo) {

                // Total de unidades que entraron al proyecto (real + transferencia)
                $entradasTotal = $grupo->sum('cantidad_inicial');

                // Solo las que son ingreso REAL (no transferencia) — cuentan al total general
                $entradasReales = $grupo
                    ->filter(fn($item) => ! $item->entrada->es_transferencia)
                    ->sum('cantidad_inicial');

                return [
                    'proyecto'        => $grupo->first()->entrada->tipoproyecto->nombre ?? '—',
                    'entradas'        => $entradasTotal,
                    'entradas_reales' => $entradasReales,
                ];
            });

        // Salidas por proyecto usando whereIn
        $salidas = SalidasDetalle::whereIn('id_entrada_detalle', $idsEntradasDetalle)
            ->with('salida.tipoproyecto')
            ->get()
            ->groupBy(fn($item) => $item->salida->id_tipoproyecto)
            ->map(fn($grupo) => $grupo->sum('cantidad_salida'));

        // Combinar
        $proyectos = $entradas->map(function ($dato, $idProyecto) use ($salidas) {
            $sal = $salidas[$idProyecto] ?? 0;
            return [
                'proyecto'        => $dato['proyecto'],
                'entradas'        => $dato['entradas'],
                'entradas_reales' => $dato['entradas_reales'],
                'salidas'         => $sal,
                'disponible'      => $dato['entradas'] - $sal,
            ];
        })
            ->filter(fn($p) => $p['disponible'] != 0)  // ← solo los que tienen disponible
            ->values();

        // ── Totales ───────────────────────────────────────────────
        // El total general usa SOLO las entradas reales (el material físico
        // que de verdad ingresó). Las transferencias no suman: es el mismo
        // material moviéndose entre proyectos.
        $totalEntradas   = $proyectos->sum('entradas_reales');
        $totalSalidas    = $proyectos->sum('salidas');

        // Salidas por transferencia (las que salieron hacia otro proyecto)
        // no son consumo real, así que el disponible global es entradas
        // reales menos las salidas que NO son transferencia.
        $salidasReales = SalidasDetalle::whereIn('id_entrada_detalle', $idsEntradasDetalle)
            ->whereHas('salida', fn($q) => $q->where('es_transferencia', 0))
            ->get()
            ->sum('cantidad_salida');

        $totalDisponible = $totalEntradas - $salidasReales;

        return response()->json([
            'success'   => 1,
            'proyectos' => $proyectos,
            'totales'   => [
                'entradas'   => $totalEntradas,
                'salidas'    => $totalSalidas,
                'disponible' => $totalDisponible,
            ],
        ]);
    }





    //*******************************************

    public function vistaDetalleMaterial($id){

        $infomaterial = Materiales::where('id', $id)->first();
        $medida = '';
        if($infoMedida = UnidadMedida::where('id', $infomaterial->id_medida)->first()){
            $medida = $infoMedida->nombre;
        }

        return view('backend.admin.inventario.detalle.vistadetalle', compact('id', 'infomaterial', 'medida'));
    }


    public function tablaDetalleMaterial($id){

        // SOLO HABRA 1 MATERIAL POR CADA PROYECTO
        $arrayEntradas =  Entradas::where('id_material', $id)->get();

        $pilaArrayEntrada = array();


        foreach ($arrayEntradas as $data){

            // VERIFICAR QUE LA CANTIDAD SEA MAYOR A 0 PARA PODER
            // MOSTRARLO
            if($data->cantidad > 0){
                array_push($pilaArrayEntrada, $data->id);
            }
        }

        $lista = Entradas::whereIn('id', $pilaArrayEntrada)
            ->orderBy('id_tipoproyecto', 'ASC')
            ->get();

        foreach ($lista as $info){
            // OBTENER NOMBRE DE PROYECTO

            $infoProyecto = TipoProyecto::where('id', $info->id_tipoproyecto)->first();
            $info->nombrepro = $infoProyecto->nombre;
        }

        return view('backend.admin.inventario.detalle.tabladetallematerial', compact('lista'));
    }



}
