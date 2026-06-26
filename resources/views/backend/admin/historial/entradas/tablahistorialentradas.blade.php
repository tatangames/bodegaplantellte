<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <table id="tabla" class="table table-bordered table-striped">
                            <thead>
                            <tr>
                                <th style="width:12%">Tipo Entrada</th>
                                <th style="width:12%">Tipo Compra</th>
                                <th style="width:8%">Fecha</th>
                                <th style="width:8%">Factura</th>
                                <th style="width:10%">Proveedor</th>
                                <th style="width:10%">Total</th>
                                <th style="width:20%">Descripción</th>
                                <th style="width:16%">Opciones</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($arrayEntradas as $dato)
                                <tr>
                                    <td>{{ $dato->tipoEntrada->nombre ?? '' }}</td>
                                    <td>{{ $dato->tipoCompra->nombre ?? '' }}</td>
                                    <td>{{ $dato->fecha_fmt }}</td>
                                    <td>{{ $dato->factura ?? '' }}</td>
                                    <td>{{ $dato->proveedor->nombre ?? '' }}</td>
                                    <td class="text-right font-weight-bold text-success">
                                        ${{ number_format($dato->totalEntrada, 4) }}
                                    </td>
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
