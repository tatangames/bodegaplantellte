<?php

namespace App\Http\Controllers\Sistema;

use App\Http\Controllers\Controller;
use App\Models\Cuenta;
use App\Models\Departamentos;
use App\Models\EntradasDetalle;
use App\Models\Equipos;
use App\Models\ObjetoEspecifico;
use App\Models\Proveedor;
use App\Models\Rubro;
use App\Models\Ubicaciones;
use App\Models\UnidadMedida;
use Database\Seeders\EquiposSeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ConfiguracionController extends Controller
{

    public function indexUnidadMedida(){
        return view('backend.admin.configuracion.unidadmedida.vistaunidadmedida');
    }

    public function tablaUnidadMedida(){

        $lista = UnidadMedida::orderBy('nombre', 'ASC')->get();
        return view('backend.admin.configuracion.unidadmedida.tablaunidadmedida', compact('lista'));
    }

    public function nuevaUnidadMedida(Request $request){
        $regla = array(
            'medida' => 'required',
        );

        $validar = Validator::make($request->all(), $regla);

        if ($validar->fails()){ return ['success' => 0];}

        $dato = new UnidadMedida();
        $dato->nombre = $request->medida;

        if($dato->save()){
            return ['success' => 1];
        }else{
            return ['success' => 2];
        }
    }

    public function informacionUnidadMedida(Request $request){
        $regla = array(
            'id' => 'required',
        );

        $validar = Validator::make($request->all(), $regla);

        if ($validar->fails()){ return ['success' => 0];}

        if($lista = UnidadMedida::where('id', $request->id)->first()){

            return ['success' => 1, 'medida' => $lista];
        }else{
            return ['success' => 2];
        }
    }

    public function editarUnidadMedida(Request $request){

        $regla = array(
            'id' => 'required',
            'medida' => 'required'
        );

        $validar = Validator::make($request->all(), $regla);

        if ($validar->fails()){ return ['success' => 0];}

        if(UnidadMedida::where('id', $request->id)->first()){

            UnidadMedida::where('id', $request->id)->update([
                'nombre' => $request->medida
            ]);

            return ['success' => 1];
        }else{
            return ['success' => 2];
        }
    }


    //********* DEPARTAMENTOS **************************************************************


    public function indexEquipos(){
        return view('backend.admin.configuracion.equipos.vistaequipos');
    }

    public function tablaEquipos(){

        $lista = Equipos::orderBy('nombre', 'ASC')->get();
        return view('backend.admin.configuracion.equipos.tablaequipos', compact('lista'));
    }

    public function nuevaEquipos(Request $request){
        $regla = array(
            'nombre' => 'required',
        );

        $validar = Validator::make($request->all(), $regla);

        if ($validar->fails()){ return ['success' => 0];}

        $dato = new Equipos();
        $dato->nombre = $request->nombre;

        if($dato->save()){
            return ['success' => 1];
        }else{
            return ['success' => 2];
        }
    }

    public function informacionEquipos(Request $request){
        $regla = array(
            'id' => 'required',
        );

        $validar = Validator::make($request->all(), $regla);

        if ($validar->fails()){ return ['success' => 0];}

        if($lista = Equipos::where('id', $request->id)->first()){

            return ['success' => 1, 'info' => $lista];
        }else{
            return ['success' => 2];
        }
    }

    public function editarEquipos(Request $request){

        $regla = array(
            'id' => 'required',
            'nombre' => 'required'
        );

        $validar = Validator::make($request->all(), $regla);

        if ($validar->fails()){ return ['success' => 0];}

        if(Equipos::where('id', $request->id)->first()){

            Equipos::where('id', $request->id)->update([
                'nombre' => $request->nombre
            ]);

            return ['success' => 1];
        }else{
            return ['success' => 2];
        }
    }




    //********* UBICACIONES **************************************************************


    public function indexUbicaciones(){
        return view('backend.admin.configuracion.ubicaciones.vistaubicaciones');
    }

    public function tablaUbicaciones(){

        $lista = Ubicaciones::orderBy('nombre', 'ASC')->get();
        return view('backend.admin.configuracion.ubicaciones.tablaubicaciones', compact('lista'));
    }

    public function nuevaUbicaciones(Request $request){
        $regla = array(
            'nombre' => 'required',
        );

        $validar = Validator::make($request->all(), $regla);

        if ($validar->fails()){ return ['success' => 0];}

        $dato = new Ubicaciones();
        $dato->nombre = $request->nombre;

        if($dato->save()){
            return ['success' => 1];
        }else{
            return ['success' => 2];
        }
    }

    public function informacionUbicaciones(Request $request){
        $regla = array(
            'id' => 'required',
        );

        $validar = Validator::make($request->all(), $regla);

        if ($validar->fails()){ return ['success' => 0];}

        if($lista = Ubicaciones::where('id', $request->id)->first()){

            return ['success' => 1, 'info' => $lista];
        }else{
            return ['success' => 2];
        }
    }

    public function editarUbicaciones(Request $request){

        $regla = array(
            'id' => 'required',
            'nombre' => 'required'
        );

        $validar = Validator::make($request->all(), $regla);

        if ($validar->fails()){ return ['success' => 0];}

        if(Ubicaciones::where('id', $request->id)->first()){

            Ubicaciones::where('id', $request->id)->update([
                'nombre' => $request->nombre
            ]);

            return ['success' => 1];
        }else{
            return ['success' => 2];
        }
    }


    public function borrarUbicaciones(Request $request)
    {
        $regla = array(
            'id' => 'required',
        );

        $validar = Validator::make($request->all(), $regla);

        if ($validar->fails()) { return ['success' => 0]; }

        $ubicacion = Ubicaciones::find($request->id);

        if (!$ubicacion) {
            return ['success' => 2, 'msg' => 'La ubicación no existe.'];
        }

        // Verificar si tiene relación con entradas_detalle
        $tieneRelacion = EntradasDetalle::where('id_ubicaciones', $ubicacion->id)->exists();

        if ($tieneRelacion) {
            return [
                'success' => 3,
                'msg' => 'No se puede eliminar: esta ubicación tiene materiales registrados en entradas.',
            ];
        }

        if ($ubicacion->delete()) {
            return ['success' => 1];
        }

        return ['success' => 4, 'msg' => 'Error al eliminar.'];
    }


    //********* RUBRO **************************************************************

    public function indexRubro(){
        return view('backend.admin.codigos.rubro.vistarubro');
    }

    public function tablaRubro(){

        $lista = Rubro::orderBy('nombre', 'ASC')->get();
        return view('backend.admin.codigos.rubro.tablarubro', compact('lista'));
    }

    public function nuevaRubro(Request $request){
        $regla = array(
            'codigo' => 'required',
            'nombre' => 'required',
        );

        $validar = Validator::make($request->all(), $regla);

        if ($validar->fails()){ return ['success' => 0];}

        $dato = new Rubro();
        $dato->nombre = $request->nombre;
        $dato->codigo = $request->codigo;

        if($dato->save()){
            return ['success' => 1];
        }else{
            return ['success' => 2];
        }
    }

    public function informacionRubro(Request $request){
        $regla = array(
            'id' => 'required',
        );

        $validar = Validator::make($request->all(), $regla);

        if ($validar->fails()){ return ['success' => 0];}

        if($lista = Rubro::where('id', $request->id)->first()){

            return ['success' => 1, 'info' => $lista];
        }else{
            return ['success' => 2];
        }
    }

    public function editarRubro(Request $request){

        $regla = array(
            'id' => 'required',
            'nombre' => 'required',
            'codigo' => 'required',
        );

        $validar = Validator::make($request->all(), $regla);

        if ($validar->fails()){ return ['success' => 0];}

        if(Rubro::where('id', $request->id)->first()){

            Rubro::where('id', $request->id)->update([
                'nombre' => $request->nombre,
                'codigo' => $request->codigo,
            ]);

            return ['success' => 1];
        }else{
            return ['success' => 2];
        }
    }

    //*********************** CUENTA ****************************************************************


    public function indexCuenta()
    {
        $rubros = Rubro::orderBy('nombre', 'ASC')->get();
        return view('backend.admin.codigos.cuenta.vistacuenta', compact('rubros'));
    }

    public function tablaCuenta()
    {
        $lista = Cuenta::with('rubro')->orderBy('nombre', 'ASC')->get();
        return view('backend.admin.codigos.cuenta.tablacuenta', compact('lista'));
    }

    public function nuevaCuenta(Request $request)
    {
        $validar = Validator::make($request->all(), [
            'id_rubro' => 'required|exists:rubro,id',
            'nombre'   => 'required',
        ]);

        if ($validar->fails()) { return ['success' => 0]; }

        $dato = new Cuenta();
        $dato->id_rubro = $request->id_rubro;
        $dato->codigo   = $request->codigo;
        $dato->nombre   = $request->nombre;

        return $dato->save() ? ['success' => 1] : ['success' => 2];
    }

    public function informacionCuenta(Request $request)
    {
        $validar = Validator::make($request->all(), [
            'id' => 'required',
        ]);

        if ($validar->fails()) { return ['success' => 0]; }

        $dato = Cuenta::find($request->id);

        return $dato
            ? ['success' => 1, 'info' => $dato]
            : ['success' => 2];
    }

    public function editarCuenta(Request $request)
    {
        $validar = Validator::make($request->all(), [
            'id'       => 'required',
            'id_rubro' => 'required|exists:rubro,id',
            'nombre'   => 'required',
        ]);

        if ($validar->fails()) { return ['success' => 0]; }

        $dato = Cuenta::find($request->id);

        if (!$dato) { return ['success' => 2]; }

        $dato->id_rubro = $request->id_rubro;
        $dato->codigo   = $request->codigo;
        $dato->nombre   = $request->nombre;

        return $dato->save() ? ['success' => 1] : ['success' => 2];
    }


    public function indexObjetoEspecifico()
    {
        // Cargamos cuentas con su rubro para el select
        $cuentas = Cuenta::with('rubro')->orderBy('nombre', 'ASC')->get();
        return view('backend.admin.codigos.objetoespecifico.vistaobjetoespecifico', compact('cuentas'));
    }

    public function tablaObjetoEspecifico()
    {
        $lista = ObjetoEspecifico::with('cuenta.rubro')->orderBy('nombre', 'ASC')->get();
        return view('backend.admin.codigos.objetoespecifico.tablaobjetoespecifico', compact('lista'));
    }

    public function nuevaObjetoEspecifico(Request $request)
    {
        $validar = Validator::make($request->all(), [
            'id_cuenta' => 'required|exists:cuenta,id',
            'codigo'    => 'required',
            'nombre'    => 'required',
        ]);

        if ($validar->fails()) { return ['success' => 0]; }

        $dato = new ObjetoEspecifico();
        $dato->id_cuenta = $request->id_cuenta;
        $dato->codigo    = $request->codigo;
        $dato->nombre    = $request->nombre;

        return $dato->save() ? ['success' => 1] : ['success' => 2];
    }

    public function informacionObjetoEspecifico(Request $request)
    {
        $validar = Validator::make($request->all(), ['id' => 'required']);
        if ($validar->fails()) { return ['success' => 0]; }

        $dato = ObjetoEspecifico::find($request->id);
        return $dato ? ['success' => 1, 'info' => $dato] : ['success' => 2];
    }

    public function editarObjetoEspecifico(Request $request)
    {
        $validar = Validator::make($request->all(), [
            'id'        => 'required',
            'id_cuenta' => 'required|exists:cuenta,id',
            'codigo'    => 'required',
            'nombre'    => 'required',
        ]);

        if ($validar->fails()) { return ['success' => 0]; }

        $dato = ObjetoEspecifico::find($request->id);
        if (!$dato) { return ['success' => 2]; }

        $dato->id_cuenta = $request->id_cuenta;
        $dato->codigo    = $request->codigo;
        $dato->nombre    = $request->nombre;

        return $dato->save() ? ['success' => 1] : ['success' => 2];
    }





    //******************** PROVEEDOR *************************************************************


    public function vistaProveedor()
    {
        return view('backend.admin.configuracion.proveedor.vistaproveedor');
    }

    public function tablaProveedor()
    {
        $lista = Proveedor::orderBy('nombre', 'ASC')->get();

        return view('backend.admin.configuracion.proveedor.tablaproveedor', compact('lista'));
    }


    public function nuevoProveedor(Request $request)
    {
        $regla = array(
            'nombre' => 'required'
        );

        $validar = Validator::make($request->all(), $regla);

        if ($validar->fails()) {
            return ['success' => 0];
        }
        DB::beginTransaction();

        try {
            $dato = new Proveedor();
            $dato->nombre = $request->nombre;
            $dato->telefono = $request->telefono;
            $dato->save();

            DB::commit();
            return ['success' => 1];
        } catch (\Throwable $e) {
            Log::info('error ' . $e);
            DB::rollback();
            return ['success' => 99];
        }
    }


    public function infoProveedor(Request $request)
    {
        $regla = array(
            'id' => 'required'
        );

        $validar = Validator::make($request->all(), $regla);

        if ($validar->fails()) {
            return ['success' => 0];
        }

        $info = Proveedor::where('id', $request->id)->first();

        return ['success' => 1, 'info' => $info];
    }

    public function actualizarProveedor(Request $request)
    {
        $regla = array(
            'id' => 'required',
            'nombre' => 'required'
        );

        $validar = Validator::make($request->all(), $regla);

        if ($validar->fails()) {
            return ['success' => 0];
        }

        Proveedor::where('id', $request->id)->update([
            'nombre' => $request->nombre,
            'telefono' => $request->telefono
        ]);

        return ['success' => 1];
    }





}
