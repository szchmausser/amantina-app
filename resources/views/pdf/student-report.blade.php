<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Ficha Socioproductiva</title>
<style>
@page {
    size: 216mm 279mm;
    margin: 25.4mm 25.4mm 25.4mm 25.4mm;
}

* { margin: 0; padding: 0; box-sizing: border-box; }

body {
    font-family: DejaVu Sans, sans-serif;
    font-size: 9pt;
    color: #1c1c1c;
    background: #fff;
    margin: 25.4mm 25.4mm 25.4mm 25.4mm;
}

/* ─── UTILIDADES ─────────────────────────── */
.w100  { width: 100%; }
.bold  { font-weight: bold; }
.upper { text-transform: uppercase; }
.right { text-align: right; }
.center{ text-align: center; }

/* ─── CABECERA ───────────────────────────── */
table.header {
    width: 100%;
    border-collapse: collapse;
    border-bottom: 2.5pt solid #1a5c2e;
    padding-bottom: 6pt;
    margin-bottom: 10pt;
}
.inst-name {
    font-size: 11pt;
    font-weight: bold;
    color: #1a5c2e;
    text-transform: uppercase;
    letter-spacing: 0.6pt;
}
.inst-sub {
    font-size: 8pt;
    color: #555;
    margin-top: 2pt;
}
.rpt-title {
    font-size: 9.5pt;
    font-weight: bold;
    color: #1c1c1c;
    text-transform: uppercase;
    letter-spacing: 0.4pt;
}
.rpt-date {
    font-size: 7.5pt;
    color: #888;
    margin-top: 2pt;
}

/* ─── BANNER ESTUDIANTE ──────────────────── */
table.banner {
    width: 100%;
    border-collapse: collapse;
    background: #f2f8f4;
    border: 0.8pt solid #a8d5b5;
    border-left: 4pt solid #1a5c2e;
    margin-bottom: 10pt;
}
table.banner td {
    padding: 7pt 10pt;
    vertical-align: middle;
}
.stu-name {
    font-size: 13pt;
    font-weight: bold;
    color: #0f3d20;
}
.stu-meta {
    font-size: 8pt;
    color: #333;
    margin-top: 3pt;
}
.badge {
    display: inline;
    padding: 1pt 6pt;
    border-radius: 8pt;
    font-size: 7pt;
    font-weight: bold;
    text-transform: uppercase;
    letter-spacing: 0.3pt;
}
.badge-active   { background:#d1fae5; color:#065f46; }
.badge-inactive { background:#fee2e2; color:#991b1b; }
.badge-transfer { background:#fef3c7; color:#78350f; }
.badge-regular  { background:#dbeafe; color:#1e40af; }

/* ─── TÍTULO DE SECCIÓN ──────────────────── */
.sec-title {
    font-size: 7.5pt;
    font-weight: bold;
    text-transform: uppercase;
    letter-spacing: 0.8pt;
    color: #1a5c2e;
    border-bottom: 1pt solid #a8d5b5;
    padding-bottom: 2pt;
    margin-top: 10pt;
    margin-bottom: 6pt;
}

/* ─── GRILLA DE DATOS ────────────────────── */
table.data {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
}
table.data td {
    padding: 2.5pt 0;
    vertical-align: top;
    word-wrap: break-word;
}
table.data td.lbl {
    width: 16%;
    font-size: 7pt;
    font-weight: bold;
    text-transform: uppercase;
    letter-spacing: 0.4pt;
    color: #6b7280;
    padding-right: 4pt;
}
table.data td.val {
    width: 34%;
    font-size: 8.5pt;
    color: #1c1c1c;
    padding-right: 8pt;
}

/* ─── TARJETAS DE HORAS ──────────────────── */
table.hcard-wrap {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
    margin-top: 6pt;
    margin-bottom: 4pt;
}
table.hcard-wrap td.hcard {
    width: 50%;
    border: 0.8pt solid #d1d5db;
    border-radius: 4pt;
    padding: 9pt 10pt;
    vertical-align: top;
}
table.hcard-wrap td.hcard-sep {
    width: 10pt;
}
.hcard-title {
    font-size: 7pt;
    font-weight: bold;
    text-transform: uppercase;
    letter-spacing: 0.6pt;
    color: #6b7280;
    margin-bottom: 5pt;
}
table.hcard-inner {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
}
table.hcard-inner td { padding: 0; vertical-align: bottom; }
.h-num  { font-size: 22pt; font-weight: bold; line-height: 1; }
.h-req  { font-size: 7.5pt; color: #6b7280; margin-top: 1pt; }
.h-pct  { font-size: 17pt; font-weight: bold; text-align: right; }

.c-green { color: #16a34a; }
.c-blue  { color: #1d4ed8; }
.c-amber { color: #b45309; }
.c-red   { color: #b91c1c; }
.bg-green{ background-color: #16a34a; }
.bg-blue { background-color: #1d4ed8; }
.bg-amber{ background-color: #b45309; }
.bg-red  { background-color: #b91c1c; }

.pbar-track {
    background: #e5e7eb;
    height: 7pt;
    width: 100%;
    margin-top: 6pt;
    border-radius: 3pt;
}
.pbar-fill {
    height: 7pt;
    border-radius: 3pt;
}

/* ─── PIE DE PÁGINA ──────────────────────── */
.footer {
    margin-top: 16pt;
    border-top: 0.8pt solid #d1d5db;
    padding-top: 5pt;
    text-align: center;
    font-size: 7pt;
    color: #9ca3af;
}

/* ─── SALTO DE PÁGINA ────────────────────── */
.page-break { page-break-before: always; }

/* ─── CABECERA ANEXO ─────────────────────── */
table.annex-banner {
    width: 100%;
    border-collapse: collapse;
    background: #f2f8f4;
    border: 0.8pt solid #a8d5b5;
    border-left: 4pt solid #1a5c2e;
    margin-bottom: 10pt;
}
table.annex-banner td { padding: 7pt 10pt; vertical-align: middle; }
.annex-title { font-size: 11pt; font-weight: bold; color: #0f3d20; }
.annex-sub   { font-size: 8pt; color: #444; margin-top: 3pt; }

/* ─── TABLA HISTORIAL ────────────────────── */
table.hist {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
    font-size: 7.5pt;
}
table.hist thead tr {
    background: #1a5c2e;
    color: #fff;
}
table.hist thead th {
    padding: 4.5pt 5pt;
    text-align: left;
    font-size: 7pt;
    font-weight: bold;
    text-transform: uppercase;
    letter-spacing: 0.3pt;
    word-wrap: break-word;
}
table.hist thead th.tc { text-align: center; }
table.hist tbody tr:nth-child(even) { background: #f9fafb; }
table.hist tbody tr:nth-child(odd)  { background: #ffffff; }
table.hist tbody td {
    padding: 4pt 5pt;
    border-bottom: 0.5pt solid #e5e7eb;
    vertical-align: top;
    word-wrap: break-word;
    overflow-wrap: break-word;
    color: #374151;
}
table.hist tbody td.tc { text-align: center; }
.att-y { color: #166534; font-weight: bold; }
.att-n { color: #991b1b; font-weight: bold; }
.h-pos { color: #166534; font-weight: bold; }
.h-nil { color: #9ca3af; }
.act-row { font-size: 7pt; color: #4b5563; margin-top: 1pt; }

.total-row {
    text-align: right;
    font-size: 8pt;
    color: #1c1c1c;
    margin-top: 7pt;
}
.no-data {
    text-align: center;
    padding: 20pt;
    color: #9ca3af;
    font-style: italic;
    font-size: 9pt;
}
</style>
</head>
<body>

{{-- ═══════════════════════════════
     PÁGINA 1 — FICHA + HORAS
═══════════════════════════════ --}}

{{-- CABECERA --}}
<table class="header">
<tr>
    <td style="width:60%; vertical-align:middle;">
        <div class="inst-name">{{ $institution?->name ?? 'Institución Educativa' }}</div>
        <div class="inst-sub">Sistema de Bitácora Socioproductiva</div>
    </td>
    <td style="width:40%; vertical-align:middle; text-align:right;">
        <div class="rpt-title">Ficha del Estudiante</div>
        <div class="rpt-date">Generado el {{ $generatedAt }}</div>
    </td>
</tr>
</table>

{{-- BANNER ESTUDIANTE --}}
@php
    $roles = $user->roles ? $user->roles->pluck('name')->implode(', ') : '—';
@endphp
<table class="banner">
<tr>
    <td style="width:70%;">
        <div class="stu-name">{{ $user->name }}</div>
        <div class="stu-meta">
            <strong>Cédula:</strong> {{ $user->cedula ?? '—' }}
            &nbsp;&nbsp;
            <span class="badge {{ $user->is_active ? 'badge-active' : 'badge-inactive' }}">
                {{ $user->is_active ? 'Activo' : 'Inactivo' }}
            </span>
        </div>
    </td>
    @if($user->is_transfer)
    <td style="width:30%; text-align:right; vertical-align:middle;">
        <span class="badge badge-transfer">Transferido</span>
    </td>
    @else
    <td style="width:30%;"></td>
    @endif
</tr>
</table>

{{-- DATOS PERSONALES --}}
<div class="sec-title">Información Personal</div>
<table class="data">
<tr>
    <td class="lbl">Correo</td>
    <td class="val">{{ $user->email }}</td>
    <td class="lbl">Teléfono</td>
    <td class="val">{{ $user->phone ?? '—' }}</td>
</tr>
<tr>
    <td class="lbl">Dirección</td>
    <td class="val" colspan="3">{{ $user->address ?? '—' }}</td>
</tr>
@if($user->is_transfer && $user->institution_origin)
<tr>
    <td class="lbl">Inst. Origen</td>
    <td class="val" colspan="3">{{ $user->institution_origin }}</td>
</tr>
@endif
</table>

{{-- DATOS ACADÉMICOS --}}
@if($currentEnrollment)
<div class="sec-title">Información Académica &mdash; Año en Curso</div>
<table class="data">
<tr>
    <td class="lbl">Año Escolar</td>
    <td class="val">{{ $currentEnrollment->academicYear->name ?? '—' }}</td>
    <td class="lbl">Grado</td>
    <td class="val">{{ $currentEnrollment->grade->name ?? '—' }}</td>
</tr>
<tr>
    <td class="lbl">Sección</td>
    <td class="val">{{ $currentEnrollment->section->name ?? '—' }}</td>
    <td class="lbl"></td>
    <td class="val"></td>
</tr>
</table>
@endif

{{-- RESUMEN DE HORAS --}}
<div class="sec-title">Resumen de Horas Socioproductivas</div>

@php
    $cy  = $hourStats['current_year'];
    $tot = $hourStats['total'];

    $cyPct  = min((float) $cy['percentage'],  100);
    $totPct = min((float) $tot['percentage'], 100);

    $cyColor = $cy['percentage']  >= 100 ? 'green'
        : ($cy['percentage']  >= 75  ? 'blue'
        : ($cy['percentage']  >= 50  ? 'amber' : 'red'));

    $totColor = $tot['percentage'] >= 100 ? 'green'
        : ($tot['percentage'] >= 75  ? 'blue'
        : ($tot['percentage'] >= 50  ? 'amber' : 'red'));
@endphp

<table class="hcard-wrap">
<tr>
    {{-- Tarjeta año actual --}}
    <td class="hcard">
        <div class="hcard-title">{{ $cy['year_name'] }}</div>
        <table class="hcard-inner">
        <tr>
            <td style="width:60%;">
                <div class="h-num c-{{ $cyColor }}">{{ number_format($cy['hours'], 1) }}h</div>
                <div class="h-req">de {{ number_format($cy['required'], 0) }}h requeridas</div>
            </td>
            <td style="width:40%; text-align:right; vertical-align:bottom;">
                <div class="h-pct c-{{ $cyColor }}">{{ number_format($cy['percentage'], 0) }}%</div>
            </td>
        </tr>
        </table>
        <div class="pbar-track">
            <div class="pbar-fill bg-{{ $cyColor }}" style="width:{{ $cyPct }}%;"></div>
        </div>
    </td>

    <td class="hcard-sep"></td>

    {{-- Tarjeta acumulado general --}}
    <td class="hcard">
        <div class="hcard-title">Acumulado General</div>
        <table class="hcard-inner">
        <tr>
            <td style="width:60%;">
                <div class="h-num c-{{ $totColor }}">{{ number_format($tot['hours'], 1) }}h</div>
                <div class="h-req">de {{ number_format($tot['required'], 0) }}h totales</div>
            </td>
            <td style="width:40%; text-align:right; vertical-align:bottom;">
                <div class="h-pct c-{{ $totColor }}">{{ number_format($tot['percentage'], 0) }}%</div>
            </td>
        </tr>
        </table>
        <div class="pbar-track">
            <div class="pbar-fill bg-{{ $totColor }}" style="width:{{ $totPct }}%;"></div>
        </div>
    </td>
</tr>
</table>

{{-- Desglose por Lapso --}}
@if(!empty($hourStats['breakdown_by_term']) && count($hourStats['breakdown_by_term']) > 0)
<div style="margin-top: 15px;">
    <div class="sec-title">Desglose por Lapso ({{ $cy['year_name'] }})</div>
    <table class="hcard-wrap">
    <tr>
        @foreach($hourStats['breakdown_by_term'] as $index => $term)
            @php
                $termPct = (float) ($term['percentage'] ?? 0);
                $termColor = $termPct >= 100 ? 'green'
                    : ($termPct >= 75  ? 'blue'
                    : ($termPct >= 50  ? 'amber' : 'red'));
            @endphp
            @if($index > 0)
                <td class="hcard-sep"></td>
            @endif
            <td class="hcard" style="width: {{ 100 / count($hourStats['breakdown_by_term']) }}%;">
                <div class="hcard-title">{{ $term['termName'] }}</div>
                <div style="padding: 15px; text-align: center;">
                    <div class="h-num c-{{ $termColor }}" style="font-size: 28px;">{{ number_format($term['totalHours'], 1) }}h</div>
                    <div class="h-req" style="margin-top: 4pt;">de {{ number_format($term['quota'], 0) }}h ({{ number_format($termPct, 0) }}%)</div>
                </div>
            </td>
        @endforeach
    </tr>
    </table>
</div>
@endif

<div class="footer">
    {{ $institution?->name ?? 'Institución Educativa' }} &nbsp;&bull;&nbsp; Sistema de Bitácora Socioproductiva
    &nbsp;&bull;&nbsp; Generado el {{ $generatedAt }}
</div>

{{-- ═══════════════════════════════
     PÁGINA 2 — HISTORIAL
═══════════════════════════════ --}}
<div class="page-break"></div>

{{-- CABECERA PÁGINA 2 --}}
<table class="header">
<tr>
    <td style="width:60%; vertical-align:middle;">
        <div class="inst-name">{{ $institution?->name ?? 'Institución Educativa' }}</div>
        <div class="inst-sub">Sistema de Bitácora Socioproductiva</div>
    </td>
    <td style="width:40%; vertical-align:middle; text-align:right;">
        <div class="rpt-title">Anexo &mdash; Historial de Jornadas</div>
        <div class="rpt-date">{{ $user->name }} &bull; C.I. {{ $user->cedula ?? '—' }}</div>
    </td>
</tr>
</table>

{{-- BANNER ANEXO --}}
<table class="annex-banner">
<tr>
    <td>
        <div class="annex-title">Historial de Horas Socioproductivas</div>
        <div class="annex-sub">
            Registro detallado de jornadas e incidencia en el acumulado.
             &nbsp; Jornadas: <strong>{{ collect($hourHistoryGrouped)->flatten(1)->count() }}</strong>
            @if(count($externalHours) > 0)
                &nbsp;&bull;&nbsp; Registros externos: <strong>{{ count($externalHours) }}</strong>
            @endif
        </div>
    </td>
</tr>
</table>

{{-- TABLA HISTORIAL AGRUPADA POR AÑO --}}
@php $runningTotal = 0.0; $externalTotal = 0.0; @endphp
@if(!empty($hourHistoryGrouped) && is_array($hourHistoryGrouped) && count($hourHistoryGrouped) > 0)
@php $runningTotal = 0.0; @endphp
<table class="hist">
<thead>
<tr>
    <th style="width:5%"  class="tc">#</th>
    <th style="width:22%">Jornada</th>
    <th style="width:11%">Fecha</th>
    <th style="width:12%">Año Escolar</th>
    <th style="width:10%" class="tc">Asistencia</th>
    <th style="width:28%">Actividades</th>
    <th style="width:12%" class="tc">Hrs. Acred.</th>
</tr>
</thead>
<tbody>
@php $counter = 1; @endphp
@foreach($hourHistoryGrouped as $yearName => $items)
    {{-- Year Header Row --}}
    <tr style="background:#f2f8f4;">
        <td colspan="7" style="padding:4pt 5pt; font-weight:bold; color:#1a5c2e; font-size:7.5pt; text-transform:uppercase; letter-spacing:0.5pt;">
            {{ $yearName }}
        </td>
    </tr>
    {{-- Sessions for this year --}}
    @foreach($items as $item)
    @php
        $runningTotal += $item['total_hours'];
        $sName = $item['fieldSession']['name']           ?? 'Jornada sin nombre';
        $sDate = $item['fieldSession']['start_datetime'] ?? $item['created_at'];
        $sYear = $item['fieldSession']['academic_year_name'] ?? '—';
    @endphp
    <tr>
        <td class="tc" style="color:#9ca3af; font-size:7pt;">{{ $counter++ }}</td>
        <td>{{ $sName }}</td>
        <td>{{ $sDate }}</td>
        <td>{{ $sYear }}</td>
        <td class="tc">
            @if($item['attended'])
                <span class="att-y">&#10003;&nbsp;Asistió</span>
            @else
                <span class="att-n">&#10007;&nbsp;Ausente</span>
            @endif
        </td>
        <td>
            @if($item['attended'] && count($item['activities']) > 0)
                @foreach($item['activities'] as $act)
                <div class="act-row">
                    &bull; {{ $act['activity_category'] ?? 'Sin categoría' }}:
                    <strong>{{ number_format($act['hours'], 1) }}h</strong>
                    @if(!empty($act['notes']))
                        <span style="color:#9ca3af;">({{ $act['notes'] }})</span>
                    @endif
                </div>
                @endforeach
            @elseif(!$item['attended'])
                <span style="color:#9ca3af; font-style:italic;">Sin horas (ausencia)</span>
            @else
                <span style="color:#9ca3af; font-style:italic;">Sin actividades</span>
            @endif
            @if(!empty($item['notes']))
            <div style="font-size:7pt; color:#9ca3af; font-style:italic; margin-top:1pt;">
                Obs: {{ $item['notes'] }}
            </div>
            @endif
        </td>
        <td class="tc">
            @if($item['total_hours'] > 0)
                <span class="h-pos">+{{ number_format($item['total_hours'], 1) }}h</span>
            @else
                <span class="h-nil">—</span>
            @endif
        </td>
    </tr>
    @endforeach
@endforeach
</tbody>
</table>

<div class="total-row">
    Subtotal jornadas:&nbsp;
    <span class="h-pos" style="font-size:9.5pt;">{{ number_format($runningTotal, 1) }}h</span>
</div>

@else
<div class="no-data">Este estudiante no tiene jornadas registradas aún.</div>
@endif

{{-- ─── HORAS EXTERNAS ACREDITADAS ─── --}}
@if(count($externalHours) > 0)
@php $externalTotal = array_sum(array_column($externalHours, 'hours')); @endphp
{{-- externalTotal already set --}}

<div class="sec-title" style="margin-top:14pt;">Horas Externas Acreditadas</div>

<table class="hist">
<thead>
<tr>
    <th style="width:5%"  class="tc">#</th>
    <th style="width:16%">Período</th>
    <th style="width:35%">Institución de Origen</th>
    <th style="width:30%">Descripción</th>
    <th style="width:14%" class="tc">Hrs. Acred.</th>
</tr>
</thead>
<tbody>
@foreach($externalHours as $i => $ext)
<tr>
    <td class="tc" style="color:#9ca3af; font-size:7pt;">{{ $i + 1 }}</td>
    <td><strong>{{ $ext['period'] }}</strong></td>
    <td>{{ $ext['institution_name'] }}</td>
    <td>
        @if(!empty($ext['description']))
            <span style="color:#4b5563;">{{ $ext['description'] }}</span>
        @else
            <span style="color:#9ca3af; font-style:italic;">—</span>
        @endif
        @if(!empty($ext['admin_name']))
            <div style="font-size:7pt; color:#9ca3af; margin-top:1pt;">Registrado por: {{ $ext['admin_name'] }}</div>
        @endif
    </td>
    <td class="tc">
        <span class="h-pos">+{{ number_format($ext['hours'], 1) }}h</span>
    </td>
</tr>
@endforeach
</tbody>
</table>

<div class="total-row">
    Subtotal horas externas:&nbsp;
    <span class="h-pos" style="font-size:9.5pt;">{{ number_format($externalTotal, 1) }}h</span>
</div>
@endif

{{-- ─── GRAN TOTAL UNIFICADO ─── --}}
@php
    $grandTotal = $runningTotal + (count($externalHours) > 0 ? array_sum(array_column($externalHours, 'hours')) : 0);
@endphp
<div style="margin-top:10pt; padding:8pt 10pt; background:#f2f8f4; border:0.8pt solid #a8d5b5; border-left:4pt solid #1a5c2e; border-radius:3pt;">
    <table style="width:100%; border-collapse:collapse;">
    <tr>
        <td style="font-size:8.5pt; color:#0f3d20; font-weight:bold;">
            Total Acumulado General
            @if(count($externalHours) > 0)
                <span style="font-size:7.5pt; font-weight:normal; color:#4b5563;">
                    ({{ number_format($runningTotal, 1) }}h jornadas + {{ number_format($externalTotal, 1) }}h externas)
                </span>
            @endif
        </td>
        <td style="text-align:right; font-size:14pt; font-weight:bold; color:#1a5c2e;">
            {{ number_format($grandTotal, 1) }}h
        </td>
    </tr>
    </table>
</div>

<div class="footer">
    {{ $institution?->name ?? 'Institución Educativa' }} &nbsp;&bull;&nbsp; Sistema de Bitácora Socioproductiva
    &nbsp;&bull;&nbsp; Generado el {{ $generatedAt }}
</div>

<script type="text/php">
if (isset($pdf)) {
    $font       = $fontMetrics->getFont('DejaVu Sans');
    $size       = 7;
    $color      = [0.6, 0.6, 0.6];
    $pageWidth  = $pdf->get_width();
    $pageHeight = $pdf->get_height();
    $text       = "Página {PAGE_NUM} de {PAGE_COUNT}";
    $textWidth  = $fontMetrics->getTextWidth($text, $font, $size);
    $x          = ($pageWidth - $textWidth) / 2;
    $y          = $pageHeight - 22;
    $pdf->page_text($x, $y, $text, $font, $size, $color);
}
</script>

</body>
</html>
