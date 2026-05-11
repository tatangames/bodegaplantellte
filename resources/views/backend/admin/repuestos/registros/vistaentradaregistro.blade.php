@extends('adminlte::page')

@section('title', 'Registro de Entradas')

@section('content_header')
    <h1>Registro de Entradas</h1>
@stop


{{-- Activa plugins que necesitas --}}
@section('plugins.Datatables', true)
@section('plugins.DatatablesPlugins', true)
@section('plugins.Sweetalert2', true)

@include('backend.urlglobal')

@section('content_top_nav_right')
    <link href="{{ asset('css/toastr.min.css') }}" type="text/css" rel="stylesheet" />
    <link href="{{ asset('css/select2.min.css') }}" type="text/css" rel="stylesheet">
    <link href="{{ asset('css/select2-bootstrap-5-theme.min.css') }}" type="text/css" rel="stylesheet">

    <li class="nav-item dropdown">
        <a href="#" class="nav-link" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
            <i class="fas fa-cogs"></i>
            <span class="d-none d-md-inline">
            {{ Auth::guard('admin')->user()->nombre }}
        </span>
        </a>

        <div class="dropdown-menu dropdown-menu-right">
            <a href="{{ route('admin.perfil') }}" class="dropdown-item">
                <i class="fas fa-user mr-2"></i>
                Editar Perfil
            </a>
        </div>
    </li>

    <li class="nav-item">
        <form action="{{ route('admin.logout') }}" method="POST" class="d-inline">
            @csrf

            <button type="submit" class="nav-link btn btn-link border-0 bg-transparent">
                <i class="fas fa-sign-out-alt"></i>
                <span class="d-none d-md-inline">Cerrar Sesión</span>
            </button>
        </form>
    </li>
@endsection

@section('content')

<style>
    table{
        /*Ajustar tablas*/
        table-layout:fixed;
    }
</style>

<div id="divcontenedor">

    <section class="content">
        <div class="container-fluid">
            <div class="row">

                <div class="col-md-8">

                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title">Información</h3>
                        </div>

                        <div class="card-body">

                            <div class="card-body">
                                <div class="row">
                                    <label>Fecha: <span style="color: red">*</span></label>
                                    <input style="width: 25%; margin-left: 25px;" type="date" class="form-control" id="fecha">
                                </div>
                            </div>

                            <div style="margin-left: 15px; margin-right: 15px; margin-top: 15px;">
                                <div class="form-group">
                                    <label>Asignar Proyecto: <span style="color: red">*</span></label>
                                    <select id="select-tipoproyecto" class="form-control w-100" onchange="borrarTabla(this)">
                                        <option value="">Seleccionar Proyecto</option>
                                        @foreach($tipoproyecto as $item)
                                            <option value="{{$item->id}}">{{ $item->nombre }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div style="margin-left: 15px; margin-right: 15px; margin-top: 25px;">
                                <div class="form-group">
                                    <label>Descripción (Opcional):</label>
                                    <input type="text" class="form-control" autocomplete="off" maxlength="800" id="descripcion">
                                </div>
                            </div>

                            <div class="form-group" style="float: right">
                                <br>
                                <button type="button" id="botonaddmaterial" onclick="abrirModal()" class="btn btn-primary btn-sm float-right" style="margin-top:10px; margin-right: 15px;">
                                    <i class="fas fa-plus" title="Agregar Material"></i> Agregar Material</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="modal fade" id="modalRepuesto" >
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Agregar Material</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">

                    <form id="formulario-repuesto">
                        <div class="card-body">

                            <div class="form-group">
                                <label class="control-label">Material</label>
                                <p>La busqueda regresa Material - Medida - Código</p>
                                <table class="table" id="matriz-busqueda" data-toggle="table">
                                    <tbody>
                                    <tr>
                                        <td>
                                            <input id="repuesto" data-info='0' autocomplete="off" class='form-control' style='width:100%' onkeyup='buscarMaterial(this)' maxlength='400'  type='text'>
                                            <div class='droplista' style='position: absolute; z-index: 9; width: 75% !important;'></div>
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="form-group" >
                                <label class="control-label">Cantidad <span style="color: red">*</span></label>
                                <div class="col-md-6">
                                    <input id="cantidad" class='form-control' autocomplete="off" placeholder="0">
                                </div>
                            </div>

                            <div class="form-group" >
                                <label class="control-label">Código (opcional)</label>
                                <div class="col-md-6">
                                    <input id="codigo" class='form-control' maxlength="100" autocomplete="off" placeholder="0">
                                </div>
                            </div>

                            <div class="form-group" >
                                <label class="control-label">Precio <span style="color: red">*</span></label>
                                <div class="col-md-6">
                                    <input id="precio" min="0" class='form-control' autocomplete="off" placeholder="0.0">
                                </div>
                            </div>

                        </div>
                    </form>

                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-primary" onclick="agregarFila()">Agregar</button>
                </div>
            </div>
        </div>
    </div>



    <section class="content-header">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h2>Detalle de Ingreso</h2>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">Información de Ingreso</h3>
                </div>

                <div style="margin: 15px;">
                    <table class="table" id="matriz" data-toggle="table">
                        <thead>
                        <tr>
                            <th style="width: 3%">#</th>
                            <th style="width: 10%">Descripción</th>
                            <th style="width: 6%">Cantidad</th>
                            <th style="width: 6%">Código</th>
                            <th style="width: 6%">Precio</th>
                            <th style="width: 5%">Opciones</th>
                        </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </section>

    <div class="modal-footer justify-content-between" style="margin-top: 25px;">
        <button type="button" class="btn btn-success" onclick="preguntaGuardar()">Guardar</button>
    </div>

</div>

@stop

@section('js')

    <script src="{{ asset('js/jquery.dataTables.js') }}" type="text/javascript"></script>
    <script src="{{ asset('js/dataTables.bootstrap4.js') }}" type="text/javascript"></script>

    <script src="{{ asset('js/toastr.min.js') }}" type="text/javascript"></script>
    <script src="{{ asset('js/axios.min.js') }}" type="text/javascript"></script>
    <script src="{{ asset('js/sweetalert2.all.min.js') }}"></script>
    <script src="{{ asset('js/alertaPersonalizada.js') }}"></script>
    <script src="{{ asset('js/select2.min.js') }}" type="text/javascript"></script>


    <script>
        $(function () {
            var fecha = new Date();
            document.getElementById('fecha').value = fecha.toJSON().slice(0,10);

            window.seguroBuscador = true;
            window.txtContenedorGlobal = this;

            $(document).click(function(){
                $(".droplista").hide();
            });

            $(document).ready(function() {
                $('[data-toggle="popover"]').popover({
                    placement: 'top',
                    trigger: 'hover'
                });
            });

            $('#select-tipoproyecto').select2({
                theme: "bootstrap-5",
                "language": {
                    "noResults": function(){
                        return "Búsqueda no encontrada";
                    }
                },
            });

            document.querySelector('#botonaddmaterial').disabled = true;
        });
    </script>

    <script>

        function abrirModal(){

            document.getElementById("formulario-repuesto").reset();
            $('#modalRepuesto').css('overflow-y', 'auto');
            $('#modalRepuesto').modal({backdrop: 'static', keyboard: false})
        }

        function agregarFila(){
            var repuesto = document.querySelector('#repuesto');
            var nomRepuesto = document.getElementById('repuesto').value;
            var cantidad = document.getElementById('cantidad').value;
            var codigo = document.getElementById('codigo').value;
            var precio = document.getElementById('precio').value;

            if(repuesto.dataset.info == 0){
                toastr.error("Repuesto es requerido");
                return;
            }

            var reglaNumeroDosDecimal = /^([0-9]+\.?[0-9]{0,2})$/;

            //*************

            if(cantidad === ''){
                toastr.error('Cantidad es requerida');
                return;
            }

            if(!cantidad.match(reglaNumeroDosDecimal)) {
                toastr.error('Cantidad debe ser número Decimal (2 decimales) y no Negativo');
                return;
            }

            if(cantidad <= 0){
                toastr.error('Cantidad no debe ser negativo o cero');
                return;
            }

            if(cantidad > 1000000){
                toastr.error('Cantidad máximo 1 millón');
                return;
            }

            //*************

            if(precio === ''){
                toastr.error('Precio es requerida');
                return;
            }

            if(!precio.match(reglaNumeroDosDecimal)) {
                toastr.error('Precio debe ser número Decimal (2 decimales) y no Negativo');
                return;
            }

            if(precio < 0){
                toastr.error('Precio no debe ser negativo');
                return;
            }

            if(precio > 1000000){
                toastr.error('Precio máximo 1 millón');
                return;
            }

            //**************

            var nFilas = $('#matriz >tbody >tr').length;
            nFilas += 1;

            var markup = "<tr>" +

                "<td>" +
                "<p id='fila" + (nFilas) + "' class='form-control' style='max-width: 65px'>" + (nFilas) + "</p>" +
                "</td>" +

                "<td>" +
                "<input name='descripcionArray[]' disabled data-info='" + repuesto.dataset.info + "' value='" + nomRepuesto + "' class='form-control' type='text'>" +
                "</td>" +

                "<td>" +
                "<input name='cantidadArray[]' disabled value='" + cantidad + "' class='form-control' type='number'>" +
                "</td>" +

                "<td>" +
                "<input name='codigoArray[]' disabled value='" + codigo + "' class='form-control' type='text'>" +
                "</td>" +

                "<td>" +
                "<input name='precioArray[]' disabled value='" + precio + "' class='form-control' type='number'>" +
                "</td>" +

                "<td>" +
                "<button type='button' class='btn btn-danger btn-sm' onclick='borrarFila(this)'>Borrar</button>" +
                "</td>" +

                "</tr>";

            $("#matriz tbody").append(markup);

            Swal.fire({
                position: 'center',
                icon: 'success',
                title: 'Agregado al Detalle',
                showConfirmButton: false,
                timer: 1500
            })

            $(txtContenedorGlobal).attr('data-info', '0');
            document.getElementById("formulario-repuesto").reset();
        }

        function borrarFila(elemento){
            var tabla = elemento.parentNode.parentNode;
            tabla.parentNode.removeChild(tabla);
            setearFila()
        }

        // cambiar # de fila cada vez que se borra la fila de
        // tabla nuevo material
        function setearFila(){

            var table = document.getElementById('matriz');
            var conteo = 0;
            for (var r = 1, n = table.rows.length; r < n; r++) {
                conteo +=1;
                var element = table.rows[r].cells[0].children[0];
                document.getElementById(element.id).innerHTML = ""+conteo;
            }
        }

        function buscarMaterial(e){

            // seguro para evitar errores de busqueda continua
            if(seguroBuscador){
                seguroBuscador = false;

                var row = $(e).closest('tr');
                txtContenedorGlobal = e;

                let texto = e.value;

                if(texto === ''){
                    // si se limpia el input, setear el atributo id
                    $(e).attr('data-info', 0);
                }

                axios.post(urlAdmin+'/admin/buscar/material', {
                    'query' : texto
                })
                    .then((response) => {

                        seguroBuscador = true;
                        $(row).each(function (index, element) {
                            $(this).find(".droplista").fadeIn();
                            $(this).find(".droplista").html(response.data);
                        });
                    })
                    .catch((error) => {
                        seguroBuscador = true;
                    });
            }
        }

        function modificarValor(edrop){

            // obtener texto del li
            let texto = $(edrop).text();
            // setear el input de la descripcion
            $(txtContenedorGlobal).val(texto);

            // agregar el id al atributo del input descripcion
            $(txtContenedorGlobal).attr('data-info', edrop.id);

            //$(txtContenedorGlobal).data("info");
        }

        function preguntaGuardar(){
            colorBlancoTabla();

            Swal.fire({
                title: 'Guardar Entrada?',
                text: "",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#d33',
                cancelButtonText: 'Cancelar',
                confirmButtonText: 'Si'
            }).then((result) => {
                if (result.isConfirmed) {
                    guardarEntrada();
                }
            })
        }

        function guardarEntrada(){

            var fecha = document.getElementById('fecha').value;
            var descripc = document.getElementById('descripcion').value; // max 800
            var tipoproyecto = document.getElementById('select-tipoproyecto').value;

            if(fecha === ''){
                toastr.error('Fecha es requerida');
                return;
            }

            if(tipoproyecto === ''){
                toastr.error('Seleccionar Proyecto');
                return;
            }

            if(descripc.length > 800){
                toastr.error('descripción máximo 800 caracteres');
                return;
            }

            var reglaNumeroDosDecimal = /^([0-9]+\.?[0-9]{0,2})$/;

            var nRegistro = $('#matriz > tbody >tr').length;
            let formData = new FormData();

            if (nRegistro <= 0){
                toastr.error('Registro Entrada son requeridos');
                return;
            }

            var descripcion = $("input[name='descripcionArray[]']").map(function(){return $(this).val();}).get();
            var descripcionAtributo = $("input[name='descripcionArray[]']").map(function(){return $(this).attr("data-info");}).get();
            var cantidad = $("input[name='cantidadArray[]']").map(function(){return $(this).val();}).get();
            var codigo = $("input[name='codigoArray[]']").map(function(){return $(this).val();}).get();
            var precio = $("input[name='precioArray[]']").map(function(){return $(this).val();}).get();

            for(var a = 0; a < cantidad.length; a++){

                let detalle = descripcionAtributo[a];
                let datoCantidad = cantidad[a];

                // identifica si el 0 es tipo number o texto
                if(detalle == 0){
                    colorRojoTabla(a);
                    alertaMensaje('info', 'No encontrado', 'En la Fila #' + (a+1) + " El material no se encuentra. Por favor buscar de nuevo el Material");
                    return;
                }

                if (datoCantidad === '') {
                    colorRojoTabla(a);
                    toastr.error('Fila #' + (a + 1) + ' Cantidad es requerida');
                    return;
                }

                if (!datoCantidad.match(reglaNumeroDosDecimal)) {
                    colorRojoTabla(a);
                    toastr.error('Fila #' + (a + 1) + ' Cantidad debe ser decimal (2 decimales) y no negativo');
                    return;
                }

                if (datoCantidad <= 0) {
                    colorRojoTabla(a);
                    toastr.error('Fila #' + (a + 1) + ' Cantidad no debe ser negativo');
                    return;
                }

                if (datoCantidad.length > 10) {
                    colorRojoTabla(a);
                    toastr.error('Fila #' + (a + 1) + ' Cantidad máximo 10 caracteres');
                    return;
                }
            }

            for(var b = 0; b < descripcion.length; b++){

                var datoDescripcion = descripcion[b];

                if(datoDescripcion === ''){
                    colorRojoTabla(b);
                    toastr.error('Fila #' + (b+1) + ' la descripción es requerida');
                    return;
                }

                if(datoDescripcion.length > 300){
                    colorRojoTabla(b);
                    toastr.error('Fila #' + (b+1) + ' la descripción tiene más de 300 caracteres');
                }
            }

            //*******************

            // como tienen la misma cantidad de filas, podemos recorrer
            // todas las filas de una vez
            for(var p = 0; p < cantidad.length; p++){

                formData.append('cantidad[]', cantidad[p]);
                formData.append('codigo[]', codigo[p]);
                formData.append('precio[]', precio[p]);
                formData.append('datainfo[]', descripcionAtributo[p]);
            }

            openLoading();

            formData.append('fecha', fecha);
            formData.append('descripcion', descripc);
            formData.append('tipoproyecto', tipoproyecto);

            axios.post(urlAdmin+'/admin/entrada/guardar', formData, {
            })
                .then((response) => {
                    closeLoading();

                    if(response.data.success === 1){
                        toastr.success('Registrado correctamente');
                        limpiar();
                    }
                    else{
                        toastr.error('error al guardar');
                    }
                })
                .catch((error) => {
                    toastr.error('error al guardar');
                    closeLoading();
                });
        }

        function colorRojoTabla(index){
            $("#matriz tr:eq("+(index+1)+")").css('background', '#F1948A');
        }

        function colorBlancoTabla(){
            $("#matriz tbody tr").css('background', 'white');
        }

        function limpiar(){
            document.getElementById('descripcion').value = '';

            $("#matriz tbody tr").remove();
        }

        function borrarTabla(e){
            let id = $(e).val();

            if(id == ''){
                $("#matriz tbody tr").remove();
                document.querySelector('#botonaddmaterial').disabled = true;
            }else{
                document.querySelector('#botonaddmaterial').disabled = false;
            }
        }

    </script>


@endsection
