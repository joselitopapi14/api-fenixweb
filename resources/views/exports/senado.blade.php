<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Senado</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
            vertical-align: top;
        }
        th {
            background-color: #f0f0f0;
            font-weight: bold;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .info {
            margin-bottom: 20px;
            font-size: 11px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>REPORTE DE CIUDADANOS - {{ strtoupper($tipoVotacion) }}</h1>
        <p>Fecha de generación: {{ now()->format('d/m/Y H:i:s') }}</p>
    </div>

    <div class="info">
        <p><strong>Total de registros:</strong> {{ $ciudadanosVotaciones->count() }}</p>
        <p><strong>Tipo de votación:</strong> {{ $tipoVotacion }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Cédula</th>
                <th>Nombre Completo</th>
                <th>Teléfono</th>
                <th>Dirección</th>
                <th>Fecha de Nacimiento</th>
                <th>Lugar de Votación</th>
                <th>Mesa</th>
                <th>Departamento</th>
                <th>Municipio</th>
                <th>Comuna</th>
                <th>Barrio</th>
                <th>Líder</th>
                <th>Cédula Líder</th>
                <th>Teléfono Líder</th>
                <th>Candidatos Apoyando</th>
                <th>Tipo de Votación</th>
                <th>Fecha de Registro</th>
            </tr>
        </thead>
        <tbody>
            @foreach($ciudadanosVotaciones as $ciudadano)
                <tr>
                    <td>{{ $ciudadano->cedula ?? '' }}</td>
                    <td>{{ $ciudadano->nombre_completo ?? '' }}</td>
                    <td>{{ $ciudadano->telefono ?? '' }}</td>
                    <td>{{ $ciudadano->direccion ?? '' }}</td>
                    <td>{{ $ciudadano->fecha_nacimiento ? \Carbon\Carbon::parse($ciudadano->fecha_nacimiento)->format('d/m/Y') : '' }}</td>
                    <td>{{ $ciudadano->lugar_votacion ?? '' }}</td>
                    <td>{{ $ciudadano->mesa ?? '' }}</td>
                    <td>{{ $ciudadano->departamento_nombre ?? '' }}</td>
                    <td>{{ $ciudadano->municipio_nombre ?? '' }}</td>
                    <td>{{ $ciudadano->comuna_nombre ?? '' }}</td>
                    <td>{{ $ciudadano->barrio_nombre ?? '' }}</td>
                    <td>{{ $ciudadano->lider_nombre ?? '' }}</td>
                    <td>{{ $ciudadano->lider_cedula ?? '' }}</td>
                    <td>{{ $ciudadano->lider_telefono ?? '' }}</td>
                    <td>{{ $ciudadano->candidatos_apoyando ?? '' }}</td>
                    <td>{{ $ciudadano->tipo_votacion ?? '' }}</td>
                    <td>{{ $ciudadano->created_at ? \Carbon\Carbon::parse($ciudadano->created_at)->format('d/m/Y H:i') : '' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div style="margin-top: 30px; font-size: 10px; color: #666;">
        <p>Reporte generado automáticamente por el Sistema de Gestión Electoral</p>
        <p>Total de ciudadanos exportados: {{ $ciudadanosVotaciones->count() }}</p>
    </div>
</body>
</html>
