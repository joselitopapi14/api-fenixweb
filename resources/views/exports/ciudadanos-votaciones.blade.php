<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Fecha Registro</th>
            <th>Cédula Ciudadano</th>
            <th>Nombre Completo</th>
            <th>Teléfono</th>
            <th>Dirección</th>
            <th>Departamento</th>
            <th>Municipio</th>
            <th>Comuna</th>
            <th>Barrio</th>
            <th>Lugar Votación</th>
            <th>Mesa</th>
            <th>Tipo Votación</th>
            <th>Candidatos Apoyando</th>
            <th>Líder Responsable</th>
            <th>Cédula Líder</th>
            <th>Teléfono Líder</th>
        </tr>
    </thead>
    <tbody>
        @foreach($ciudadanosVotaciones as $votacion)
        <tr>
            <td>{{ $votacion->id }}</td>
            <td>{{ $votacion->created_at ? \Carbon\Carbon::parse($votacion->created_at)->format('d/m/Y H:i:s') : '' }}</td>
            <td>{{ $votacion->cedula ?? '' }}</td>
            <td>{{ $votacion->nombre_completo ?? '' }}</td>
            <td>{{ $votacion->telefono ?? '' }}</td>
            <td>{{ $votacion->direccion ?? '' }}</td>
            <td>{{ $votacion->departamento_nombre ?? '' }}</td>
            <td>{{ $votacion->municipio_nombre ?? '' }}</td>
            <td>{{ $votacion->comuna_nombre ?? '' }}</td>
            <td>{{ $votacion->barrio_nombre ?? '' }}</td>
            <td>{{ $votacion->lugar_votacion ?? '' }}</td>
            <td>{{ $votacion->mesa ?? '' }}</td>
            <td>{{ $votacion->tipos_votacion ?? '' }}</td>
            <td>{{ $votacion->candidatos_apoyando ?? 'N/A' }}</td>
            <td>{{ $votacion->lider_nombre ?? '' }}</td>
            <td>{{ $votacion->lider_cedula ?? '' }}</td>
            <td>{{ $votacion->lider_telefono ?? '' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
