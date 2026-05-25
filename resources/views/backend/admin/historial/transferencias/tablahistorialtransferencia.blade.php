<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <table id="tabla" class="table table-bordered table-striped">
                            <thead>
                            <tr>
                                <th style="width: 5%">ID</th>
                                <th style="width: 18%">Origen</th>
                                <th style="width: 18%">Destino</th>
                                <th style="width: 11%">Tipo</th>
                                <th style="width: 9%">Fecha</th>
                                <th style="width: 17%">Descripción</th>
                                <th style="width: 22%">Opciones</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($arrayTransferencias as $dato)
                                <tr>
                                    <td>{{ $dato->id }}</td>
                                    <td>{{ $dato->nombre_origen }}</td>
                                    <td>{{ $dato->nombre_destino }}</td>
                                    <td class="text-center">
                                        @if($dato->tipo_salida === 'general')
                                            <span class="badge badge-warning">
                                                <i class="fas fa-warehouse"></i> Salida General
                                            </span>
                                        @else
                                            <span class="badge badge-success">
                                                <i class="fas fa-exchange-alt"></i> Proyecto
                                            </span>
                                        @endif
                                    </td>
                                    <td>{{ $dato->fecha_fmt }}</td>
                                    <td>{{ $dato->descripcion ?? '' }}</td>
                                    <td class="text-center">

                                        <button type="button"
                                                class="btn btn-info btn-xs"
                                                style="margin: 3px"
                                                onclick="verDetalle(
                                                    {{ $dato->id }},
                                                    '{{ addslashes($dato->nombre_origen) }}',
                                                    '{{ $dato->fecha_fmt }}',
                                                    '',
                                                    '{{ addslashes($dato->descripcion ?? '') }}'
                                                )">
                                            <i class="fas fa-list"></i> Detalle
                                        </button>

                                        <button type="button"
                                                class="btn btn-danger btn-xs"
                                                style="margin: 3px"
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

<script>
    $('[data-toggle="tooltip"]').tooltip();
</script>
