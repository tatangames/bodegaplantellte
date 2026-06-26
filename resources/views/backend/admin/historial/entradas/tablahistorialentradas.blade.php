<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <table id="tabla" class="table table-bordered table-striped">
                            <thead>
                            <tr>
                                <th style="width:4%">ID</th>
                                <th style="width:14%">Tipo Entrada</th>
                                <th style="width:14%">Tipo Compra</th>
                                <th style="width:10%">Fecha</th>
                                <th style="width:10%">Factura</th>
                                <th style="width:10%">Proveedor</th>
                                <th style="width:22%">Descripción</th>
                                <th style="width:16%">Opciones</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($arrayEntradas as $dato)
                                <tr>
                                    <td>{{ $dato->id }}</td>
                                    <td>{{ $dato->tipoEntrada->nombre ?? '' }}</td>
                                    <td>{{ $dato->tipoCompra->nombre ?? '' }}</td>
                                    <td>{{ $dato->fecha_fmt }}</td>
                                    <td>{{ $dato->factura ?? '' }}</td>
                                    <td>{{ $dato->proveedor->nombre ?? '' }}</td>
                                    <td>{{ $dato->descripcion ?? '' }}</td>
                                    <td class="text-center">
                                        <button type="button"
                                                class="btn btn-info btn-xs"
                                                style="margin:2px"
                                                onclick="verDetalle({{ $dato->id }}, 'Entrada #{{ $dato->id }} — {{ $dato->fecha_fmt }}')">
                                            <i class="fas fa-list"></i> Detalle
                                        </button>
                                        <button type="button"
                                                class="btn btn-warning btn-xs"
                                                style="margin:2px"
                                                onclick="modalEditar({{ $dato->id }})">
                                            <i class="fas fa-edit"></i> Editar
                                        </button>
                                        <button type="button"
                                                class="btn btn-danger btn-xs"
                                                style="margin:2px"
                                                onclick="eliminar({{ $dato->id }})">
                                            <i class="fas fa-trash"></i> Borrar
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
