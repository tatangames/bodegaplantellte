<?php

namespace App\Http\Controllers\Sistema;

use App\Http\Controllers\Controller;
use App\Models\Entradas;
use App\Models\Herramientas;
use App\Models\HistoHerramientaDescartada;
use App\Models\HistorialEntradas;
use App\Models\HistorialEntradasDeta;
use App\Models\Materiales;
use App\Models\TipoProyecto;
use App\Models\UnidadMedida;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class RepuestosController extends Controller
{

    public function index(){
        $lUnidad = UnidadMedida::orderBy('nombre', 'ASC')->get();
        return view('backend.admin.inventario.vistainventario', compact('lUnidad'));
    }

    public function tablaMateriales(){

        $lista = Materiales::orderBy('nombre', 'ASC')->get();

        foreach ($lista as $item) {
            $medida = '';
            if($dataUnidad = UnidadMedida::where('id', $item->id_medida)->first()){
                $medida = $dataUnidad->nombre;
            }

            $item->medida = $medida;

            // OBTENER CANTIDAD DE CADA MATERIAL, SUMANDO DE TODOS LOS PROYECTOS
            // EN VISTA DETALLE SE MOSTRARA DE QUE PROYECTO SON CADA UNO

            $arrayEntradas = Entradas::where('id_material', $item->id)->get();

            $sumatoria = 0;
            foreach ($arrayEntradas as $data){

                // SIEMPRE SUMARA TODOS, YA QUE PARA SACAR CANTIDAD LLEGARA HASTA 0
                $sumatoria += $data->cantidad;
            }

            $item->total = $sumatoria;
        }

        return view('backend.admin.inventario.tablainventario', compact('lista'));
    }

    public function nuevoMaterial(Request $request){

        $regla = array(
            'nombre' => 'required',
        );

        $validar = Validator::make($request->all(), $regla);

        if ($validar->fails()){ return ['success' => 0];}

        $dato = new Materiales();
        $dato->id_medida = $request->unidad;
        $dato->nombre = $request->nombre;
        $dato->codigo = $request->codigo;

        if($dato->save()){
            return ['success' => 1];
        }else{
            return ['success' => 2];
        }
    }

    public function informacionMaterial(Request $request){
        $regla = array(
            'id' => 'required',
        );

        $validar = Validator::make($request->all(), $regla);

        if ($validar->fails()){ return ['success' => 0];}

        if($lista = Materiales::where('id', $request->id)->first()){

            $arrayUnidad = UnidadMedida::orderBy('nombre', 'ASC')->get();

            return ['success' => 1, 'material' => $lista, 'unidad' => $arrayUnidad];
        }else{
            return ['success' => 2];
        }
    }

    public function editarMaterial(Request $request){

        $regla = array(
            'nombre' => 'required',
        );

        $validar = Validator::make($request->all(), $regla);

        if ($validar->fails()){ return ['success' => 0];}

        Materiales::where('id', $request->id)->update([
            'id_medida' => $request->unidad,
            'nombre' => $request->nombre,
            'codigo' => $request->codigo
        ]);

        return ['success' => 1];
    }


    public function infoHerramientaDescartar(Request $request){

        $regla = array(
            'id' => 'required',
        );

        $validar = Validator::make($request->all(), $regla);

        if ($validar->fails()){ return ['success' => 0];}

        $info = Herramientas::where('id', $request->id)->first();


        return ['success' => 1, 'info' => $info];
    }


    public function descartarHerramientaInventario(Request $request){


        $rules = array(
            'id' => 'required',
            'cantidad' => 'required',
            'descripcion' => 'required'
        );

        $validator = Validator::make($request->all(), $rules);
        if ( $validator->fails()){
            return ['success' => 0];
        }

        DB::beginTransaction();


        try {

            $infoHerra = Herramientas::where('id', $request->id)->first();

            if($request->cantidad > $infoHerra->cantidad){

                // cantidad a descartar es mayor a la del inventario
                return ['success' => 1];
            }


            //**************************
            // RESTAR CANTIDAD A HERRAMIENTAS

            $restado = $infoHerra->cantidad - $request->cantidad;
            $fechaCarbon = Carbon::parse(Carbon::now());

            Herramientas::where('id', $request->id)->update([
                'cantidad' => $restado
            ]);


            // guardar historial del descartado
            $datoDescarto = new HistoHerramientaDescartada();
            $datoDescarto->id_histo_herra_salida = null;
            $datoDescarto->id_herramienta = $infoHerra->id;
            $datoDescarto->fecha = $fechaCarbon;
            $datoDescarto->cantidad = $request->cantidad;
            $datoDescarto->descripcion = $request->descripcion;
            $datoDescarto->save();


            DB::commit();
            return ['success' => 2];

        }catch(\Throwable $e){
            Log::info('err ' . $e);
            DB::rollback();
            return ['success' => 99];
        }

    }



    //*******************************************************************

    public function indexRegistroEntrada(){

        $tipoproyecto = TipoProyecto::orderBy('nombre')->get();

        return view('backend.admin.repuestos.registros.vistaentradaregistro', compact('tipoproyecto'));
    }


    public function buscadorMaterial(Request $request){

        if($request->get('query')){
            $query = $request->get('query');
            $data = Materiales::where('nombre', 'LIKE', "%{$query}%")
                ->orWhere('codigo', 'LIKE', "%{$query}%")
                ->get();

            foreach ($data as $dd){
                if($info = UnidadMedida::where('id', $dd->id_medida)->first()){
                    $dd->medida = "- " . $info->nombre;
                }else{
                    $dd->medida = "";
                }

                if($dd->codigo != null){
                    $dd->code = "- " . $dd->codigo;
                }else{
                    $dd->code = "";
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
                 <li onclick="modificarValor(this)" id="'.$row->id.'"><a href="#" style="margin-left: 3px">'.$row->nombre . '  ' .$row->medida . ' ' .$row->code .'</a></li>
                ';
                    }
                }

                else{
                    if(!empty($row)){
                        $tiene = false;
                        $output .= '
                 <li onclick="modificarValor(this)" id="'.$row->id.'"><a href="#" style="margin-left: 3px">'.$row->nombre . ' ' .$row->medida . ' ' .$row->code .'</a></li>
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
    public function guardarEntrada(Request $request){

        $validator = Validator::make($request->all(), [
            'fecha'         => 'required',
            'tipoproyecto'  => 'required',
        ]);

        if ($validator->fails()){
            return ['success' => 0, 'errors' => $validator->errors()];
        }

        DB::beginTransaction();

        try {

            // Guardar historial principal
            $histoEntrada = HistorialEntradas::create([
                'fecha'           => $request->fecha,
                'descripcion'     => $request->descripcion,
                'id_tipoproyecto' => $request->tipoproyecto,
            ]);

            // Preparar detalle en masa (un solo insert)
            $detalles = collect($request->cantidad)->map(fn($cant, $i) => [
                'id_historial' => $histoEntrada->id,
                'id_material'  => $request->datainfo[$i],
                'cantidad'     => $cant,
                'codigo'       => $request->codigo[$i],
                'precio'       => $request->precio[$i],

            ])->values()->toArray();

            HistorialEntradasDeta::insert($detalles);

            // Actualizar o crear entradas de inventario
            foreach ($request->cantidad as $i => $cantidad) {
                $idMaterial = $request->datainfo[$i];

                $entrada = Entradas::where('id_tipoproyecto', $request->tipoproyecto)
                    ->where('id_material', $idMaterial)
                    ->first();

                if ($entrada) {
                    $entrada->increment('cantidad', $cantidad); // 👈 más limpio que sumar manual
                } else {
                    Entradas::create([
                        'id_material'     => $idMaterial,
                        'id_tipoproyecto' => $request->tipoproyecto,
                        'cantidad'        => $cantidad,
                    ]);
                }
            }

            DB::commit();
            return ['success' => 1];

        } catch (\Throwable $e) {
            DB::rollback();
            return ['success' => 2, 'error' => $e->getMessage()]; // quitar getMessage() en producción
        }
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
