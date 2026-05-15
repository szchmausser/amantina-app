<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Ficha Socioproductiva - {{ $user->cedula }}</title>

    <style>
        @page {
            size: letter;
            margin: 42pt 38pt 50pt 38pt;
        }

        /* ─── BASE ─── */

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 9pt;
            color: #000000;
            line-height: 1.45;
            margin: 0;
            padding: 0;
            background: #ffffff;
        }

        table {
            border-collapse: collapse;
        }

        .w100 {
            width: 100%;
        }

        .right {
            text-align: right;
        }

        .center {
            text-align: center;
        }

        .bold {
            font-weight: bold;
        }

        .mt-1 {
            margin-top: 4pt;
        }

        .mt-2 {
            margin-top: 8pt;
        }

        .mt-3 {
            margin-top: 12pt;
        }

        .mt-4 {
            margin-top: 18pt;
        }

        .page-break {
            page-break-before: always;
        }


        /* ─── HEADER ─── */

        .report-header {
            width: 100%;
            padding-bottom: 14pt;
            margin-bottom: 0;
            /* Se eliminó el borde inferior para evitar la doble línea con el divisor de sección */
        }

        .report-header table {
            width: 100%;
        }

        .logo-cell {
            width: 58pt;
            vertical-align: middle;
        }

        .logo-img {
            width: 50pt;
            height: auto;
        }

        .logo-placeholder {
            width: 46pt;
            height: 46pt;
            border: 1pt dashed #000000;
            border-radius: 4pt;
            line-height: 46pt;
            text-align: center;
            font-size: 7pt;
            color: #000000;
            font-weight: 700;
        }

        .institution-cell {
            vertical-align: middle;
        }

        .institution-name {
            font-size: 11pt;
            font-weight: 700;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.4pt;
        }

        .system-name {
            margin-top: 2pt;
            font-size: 7.5pt;
            color: #000000;
            font-weight: 700;
        }

        .meta-cell {
            width: 170pt;
            text-align: right;
            vertical-align: middle;
        }

        .report-title {
            font-size: 11pt;
            font-weight: 700;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.4pt;
        }

        .report-date {
            margin-top: 3pt;
            font-size: 7.5pt;
            color: #000000;
            font-weight: 700;
        }

        /* ─── SECTION DIVIDER (double line) ─── */

        .section-divider {
            text-align: center;
            margin: 20pt 0 12pt 0;
            border-top: 1pt solid #1e4e8c;
            border-bottom: 1pt solid #1e4e8c;
            padding: 4pt 0;
        }

        .section-divider-inner {
            font-size: 8pt;
            font-weight: 700;
            letter-spacing: 0.8pt;
            text-transform: uppercase;
            color: #1e4e8c;
        }

        /* ─── STUDENT PROFILE ─── */

        .student-profile {
            padding-top: 6pt;
            padding-bottom: 6pt;
            margin-bottom: 16pt;
        }

        .student-profile table {
            width: 100%;
        }

        .student-main {
            width: 52%;
            vertical-align: top;
            padding-right: 20pt;
        }

        .student-contact {
            width: 48%;
            vertical-align: top;
            border-left: 0.5pt solid #e2e8f0;
            padding-left: 20pt;
        }

        .student-name {
            font-size: 22pt;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 5pt;
        }

        .student-meta {
            font-size: 9.5pt;
            color: #000000;
        }

        .student-academic {
            margin-top: 6pt;
            font-size: 9.5pt;
            color: #000000;
        }

        .status-badge {
            display: inline-block;
            margin-left: 8pt;
            padding: 2.5pt 7pt;
            border-radius: 10pt;
            font-size: 6.5pt;
            font-weight: 700;
            text-transform: uppercase;
        }

        .status-active {
            background: #dcfce7;
            color: #15803d;
        }

        .status-inactive {
            background: #fee2e2;
            color: #991b1b;
        }

        .c-brand {
            color: #1e4e8c;
        }

        .bg-brand {
            background: #1e4e8c;
        }

        .c-red {
            color: #991b1b;
        }

        .bg-red {
            background: #991b1b;
        }

        .c-green {
            color: #15803d;
        }

        .bg-green {
            background: #15803d;
        }

        .c-amber {
            color: #b45309;
        }

        .bg-amber {
            background: #b45309;
        }

        /* contact rows with icon */
        .contact-row {
            margin-bottom: 9pt;
        }

        .contact-icon-cell {
            width: 16pt;
            vertical-align: top;
            padding-top: 1pt;
        }

        .contact-icon {
            display: inline-block;
            color: #1e4e8c;
            font-size: 12pt;
            text-align: center;
        }

        .contact-label {
            font-size: 6.5pt;
            text-transform: uppercase;
            color: #1e4e8c;
            font-weight: 700;
            margin-bottom: 2pt;
            letter-spacing: 0.3pt;
        }

        .contact-value {
            font-size: 8.5pt;
            color: #000000;
            font-weight: 700;
        }

        /* ─── HOUR CARDS ─── */

        .hour-card-container {
            vertical-align: top;
        }

        .hour-card {
            background: #eff6ff;
            border: 1pt solid #bfdbfe;
            border-radius: 6pt;
            padding: 12pt 14pt;
        }

        .hour-card-title {
            font-size: 7pt;
            text-transform: uppercase;
            color: #000000;
            font-weight: 700;
            margin-bottom: 10pt;
            letter-spacing: 0.4pt;
        }

        .hour-value {
            font-size: 22pt;
            font-weight: 700;
        }

        .hour-target {
            margin-top: 2pt;
            font-size: 7.5pt;
            color: #000000;
            font-weight: 700;
        }

        .hour-pct {
            text-align: right;
            font-size: 16pt;
            font-weight: 700;
        }

        .progress-track {
            width: 100%;
            height: 5pt;
            border-radius: 3pt;
            background: #e5e7eb;
            margin-top: 10pt;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
        }

        /* ─── SEMANTIC COLORS ─── */

        .c-green {
            color: #15803d;
        }

        .bg-green {
            background: #15803d;
        }

        .c-blue {
            color: #1e4e8c;
        }

        .bg-blue {
            background: #1e4e8c;
        }

        .c-amber {
            color: #d97706;
        }

        .bg-amber {
            background: #d97706;
        }

        .c-red {
            color: #dc2626;
        }

        .bg-red {
            background: #dc2626;
        }

        /* ─── TABLES ─── */

        .history-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8pt;
        }

        .history-table thead th {
            background: #1e4e8c;
            color: #ffffff;
            text-align: left;
            font-size: 7pt;
            font-weight: 700;
            padding: 7pt 8pt;
            border-bottom: 1pt solid #163d6e;
            letter-spacing: 0.3pt;
            text-transform: uppercase;
        }

        .history-table td {
            padding: 6pt 8pt;
            border-bottom: 0.5pt solid #edf2f7;
            vertical-align: top;
        }

        .year-row td {
            background: #edf5fb;
            color: #1e4e8c;
            font-weight: 700;
            font-size: 8pt;
            border-bottom: 1pt solid #bfdbfe;
            padding: 5pt 8pt;
        }

        /* ─── TOTAL BOX ─── */

        .total-box {
            margin-top: 20pt;
            background: #1e4e8c;
            padding: 7pt 16pt;
            border-radius: 4pt;
        }

        .total-box table {
            width: 100%;
        }

        .total-title {
            color: #ffffff;
            font-size: 9pt;
            font-weight: 700;
            letter-spacing: 0.6pt;
            text-transform: uppercase;
            vertical-align: middle;
        }

        .total-value {
            color: #ffffff;
            font-size: 14pt;
            font-weight: 700;
            text-align: right;
            vertical-align: middle;
        }

        /* ─── FOOTER ─── */

        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            width: 100%;
            font-size: 7pt;
            color: #000000;
            font-weight: 700;
            border-top: 0.5pt solid #000000;
            padding-top: 7pt;
        }

        .footer table {
            width: 100%;
        }
    </style>
</head>

<body>

    {{-- ═══════════════════════════════════════
    PÁGINA 1 — HEADER
    ════════════════════════════════════════ --}}

    <div class="report-header">
        <table>
            <tr>

                <td class="logo-cell">
                    @if($logoBase64)
                        <img src="{{ $logoBase64 }}" class="logo-img">
                    @else
                        <div class="logo-placeholder">LOGO</div>
                    @endif
                </td>

                <td class="institution-cell">
                    <div class="institution-name">
                        {{ $institution?->name ?? 'Institución Educativa' }}
                    </div>
                    <div class="system-name">
                        Sistema de Bitácora Socioproductiva
                    </div>
                </td>

                <td class="meta-cell">
                    <div class="report-title">Ficha del Estudiante</div>
                    <div class="report-date">Generado el {{ $generatedAt }}</div>
                </td>

            </tr>
        </table>
    </div>

    {{-- DATOS PERSONALES --}}

    <div class="section-divider">
        <span class="section-divider-inner">Datos Personales</span>
    </div>

    <div class="student-profile">
        <table>
            <tr>

                {{-- Columna izquierda: nombre, CI, sección --}}
                <td class="student-main">

                    @if($currentEnrollment)
                        <div class="student-academic" style="margin-top:0; margin-bottom:6pt;">
                            <span class="bold">Año:</span> {{ $currentEnrollment->academicYear->name }}
                            &nbsp;&nbsp;·&nbsp;&nbsp;
                            <span class="bold">Grado:</span> {{ $currentEnrollment->grade->name }}
                            &nbsp;&nbsp;·&nbsp;&nbsp;
                            <span class="bold">Sección:</span> {{ $currentEnrollment->section->name }}
                        </div>
                    @endif

                    <div class="student-name">{{ $user->name }}</div>

                    <div class="student-meta">
                        <span class="bold">C.I. {{ $user->cedula ?? '—' }}</span>
                    </div>

                </td>

                {{-- Columna derecha: contacto con iconos --}}
                <td class="student-contact">

                    {{-- Email --}}
                    <table class="contact-row">
                        <tr>
                            <td class="contact-icon-cell">
                                <div class="contact-icon">&#x2709;</div>
                            </td>
                            <td>
                                <div class="contact-label">Correo Electrónico</div>
                                <div class="contact-value">{{ $user->email ?? '—' }}</div>
                            </td>
                        </tr>
                    </table>

                    {{-- Teléfono --}}
                    <table class="contact-row">
                        <tr>
                            <td class="contact-icon-cell">
                                <div class="contact-icon">&#x2706;</div>
                            </td>
                            <td>
                                <div class="contact-label">Teléfono</div>
                                <div class="contact-value">{{ $user->phone ?? '—' }}</div>
                            </td>
                        </tr>
                    </table>

                    {{-- Dirección --}}
                    <table class="contact-row">
                        <tr>
                            <td class="contact-icon-cell">
                                <div class="contact-icon">&#x27A4;</div>
                            </td>
                            <td>
                                <div class="contact-label">Dirección</div>
                                <div class="contact-value">{{ $user->address ?? '—' }}</div>
                            </td>
                        </tr>
                    </table>

                    @if($user->is_transfer && $user->institution_origin)
                        <table class="contact-row">
                            <tr>
                                <td class="contact-icon-cell">
                                    <div class="contact-icon">&#x21BA;</div>
                                </td>
                                <td>
                                    <div class="contact-label">Institución de Origen</div>
                                    <div class="contact-value">{{ $user->institution_origin }}</div>
                                </td>
                            </tr>
                        </table>
                    @endif

                </td>

            </tr>
        </table>
    </div>

    {{-- ═══════════════════════════════════════
    RESUMEN DE HORAS
    ════════════════════════════════════════ --}}

    <div class="section-divider">
        <span class="section-divider-inner">Resumen de Horas Socioproductivas</span>
    </div>

    @php
        $cy = $hourStats['current_year'];
        $tot = $hourStats['total'];

        $cyPct = min((float) $cy['percentage'], 100);
        $totPct = min((float) $tot['percentage'], 100);

        $cyColor = 'brand';
        $totColor = 'brand';
    @endphp

    <table class="w100">
        <tr>

            <td class="hour-card-container" style="width:48%;">
                <div class="hour-card">
                    <div class="hour-card-title">{{ $cy['year_name'] }}</div>
                    <table class="w100">
                        <tr>
                            <td style="width:65%;">
                                <div class="hour-value c-{{ $cyColor }}">{{ number_format($cy['hours'], 1) }}h</div>
                                <div class="hour-target">de {{ number_format($cy['required'], 0) }}h requeridas</div>
                            </td>
                            <td style="width:35%; vertical-align:bottom;">
                                <div class="hour-pct c-{{ $cyColor }}">{{ number_format($cy['percentage'], 0) }}%</div>
                            </td>
                        </tr>
                    </table>
                    <div class="progress-track">
                        <div class="progress-fill bg-{{ $cyColor }}" style="width:{{ $cyPct }}%;"></div>
                    </div>
                </div>
            </td>

            <td style="width:4%;"></td>

            <td class="hour-card-container" style="width:48%;">
                <div class="hour-card">
                    <div class="hour-card-title">Acumulado General</div>
                    <table class="w100">
                        <tr>
                            <td style="width:65%;">
                                <div class="hour-value c-{{ $totColor }}">{{ number_format($tot['hours'], 1) }}h</div>
                                <div class="hour-target">de {{ number_format($tot['required'], 0) }}h totales</div>
                            </td>
                            <td style="width:35%; vertical-align:bottom;">
                                <div class="hour-pct c-{{ $totColor }}">{{ number_format($tot['percentage'], 0) }}%
                                </div>
                            </td>
                        </tr>
                    </table>
                    <div class="progress-track">
                        <div class="progress-fill bg-{{ $totColor }}" style="width:{{ $totPct }}%;"></div>
                    </div>
                </div>
            </td>

        </tr>
    </table>

    {{-- ═══════════════════════════════════════
    DESGLOSE POR LAPSO
    ════════════════════════════════════════ --}}

    @if(!empty($hourStats['breakdown_by_term']) && count($hourStats['breakdown_by_term']) > 0)

        <div class="section-divider">
            <span class="section-divider-inner">Desglose por Lapso ({{ $cy['year_name'] }})</span>
        </div>

        <table class="w100">
            <tr>
                @foreach($hourStats['breakdown_by_term'] as $index => $term)

                    @php
                        $termPct = (float) ($term['percentage'] ?? 0);
                        $termColor = 'brand';
                        $termFill = min($termPct, 100);
                    @endphp

                    @if($index > 0)
                        <td style="width:2%;"></td>
                    @endif

                    <td class="hour-card-container">
                        <div class="hour-card" style="padding:10pt;">
                            <div class="hour-card-title">{{ $term['termName'] }}</div>

                            <div class="hour-value c-{{ $termColor }}" style="font-size:18pt;">
                                {{ number_format($term['totalHours'], 1) }}h
                            </div>

                            <div class="hour-target">
                                {{ number_format($termPct, 0) }}% de {{ number_format($term['quota'], 0) }}h
                            </div>

                            <div class="progress-track">
                                <div class="progress-fill bg-{{ $termColor }}" style="width:{{ $termFill }}%;"></div>
                            </div>
                        </div>
                    </td>

                @endforeach
            </tr>
        </table>

    @endif

    {{-- ═══════════════════════════════════════
    PAGE BREAK + HEADER PÁGINA 2
    ════════════════════════════════════════ --}}

    <div class="page-break"></div>

    <div class="report-header">
        <table>
            <tr>

                <td class="logo-cell">
                    @if($logoBase64)
                        <img src="{{ $logoBase64 }}" class="logo-img">
                    @else
                        <div class="logo-placeholder">LOGO</div>
                    @endif
                </td>

                <td class="institution-cell">
                    <div class="institution-name">
                        {{ $institution?->name ?? 'Institución Educativa' }}
                    </div>
                    <div class="system-name">
                        Sistema de Bitácora Socioproductiva
                    </div>
                </td>

                <td class="meta-cell">
                    <div class="report-title">Historial Socioproductivo</div>
                    <div class="report-date">
                        {{ $user->name }} &nbsp;·&nbsp; C.I. {{ $user->cedula ?? '—' }}
                    </div>
                </td>

            </tr>
        </table>
    </div>

    {{-- ═══════════════════════════════════════
    HISTORIAL DE JORNADAS
    ════════════════════════════════════════ --}}

    <div class="section-divider">
        <span class="section-divider-inner">Detalle de Jornadas Socioproductivas</span>
    </div>

    @php $runningTotal = 0.0; @endphp

    @if(!empty($hourHistoryGrouped) && count($hourHistoryGrouped) > 0)

        <table class="history-table">

            <thead>
                <tr>
                    <th style="width:5%;" class="center">#</th>
                    <th style="width:24%;">Jornada</th>
                    <th style="width:12%;">Fecha</th>
                    <th style="width:9%;" class="center">Asist.</th>
                    <th style="width:38%;">Actividades</th>
                    <th style="width:12%;" class="right">Horas</th>
                </tr>
            </thead>

            <tbody>

                @php $counter = 1; @endphp

                @foreach($hourHistoryGrouped as $yearName => $items)

                    <tr class="year-row">
                        <td colspan="6">{{ $yearName }}</td>
                    </tr>

                    @foreach($items as $item)

                        @php
                            $runningTotal += $item['total_hours'];
                            $sName = $item['fieldSession']['name'] ?? 'Jornada sin nombre';
                            $sDate = $item['fieldSession']['start_datetime'] ?? $item['created_at'];
                        @endphp

                        <tr>

                            <td class="center" style="font-size:7pt; color:#000000; font-weight:bold;">{{ $counter++ }}</td>

                            <td class="bold">{{ $sName }}</td>

                            <td>{{ $sDate }}</td>

                            <td class="center">
                                @if($item['attended'])
                                    <span class="c-green bold">Sí</span>
                                @else
                                    <span class="c-red bold">No</span>
                                @endif
                            </td>

                            <td>
                                @if($item['attended'] && count($item['activities']) > 0)
                                    @foreach($item['activities'] as $act)
                                        <div style="margin-bottom:2pt; font-size:7.5pt; color:#000000; font-weight:bold;">
                                            &#x2022; {{ $act['activity_category'] ?? 'Sin categoría' }}:
                                            <strong>{{ number_format($act['hours'], 1) }}h</strong>
                                        </div>
                                    @endforeach
                                @elseif(!$item['attended'])
                                    <span style="color:#000000; font-weight:bold;">Ausente</span>
                                @else
                                    <span style="color:#000000; font-weight:bold;">Sin actividades</span>
                                @endif
                            </td>

                            <td class="right bold">
                                @if($item['total_hours'] > 0)
                                    <span class="c-green">+{{ number_format($item['total_hours'], 1) }}h</span>
                                @else
                                    <span style="color:#000000; font-weight:bold;">—</span>
                                @endif
                            </td>

                        </tr>

                    @endforeach

                @endforeach

            </tbody>

        </table>

    @else
        <div class="center mt-4" style="color:#000000; font-weight:bold;">No hay jornadas registradas.</div>
    @endif

    {{-- ═══════════════════════════════════════
    HORAS EXTERNAS
    ════════════════════════════════════════ --}}

    @if(count($externalHours) > 0)

        @php $externalTotal = array_sum(array_column($externalHours, 'hours')); @endphp

        <div class="section-divider">
            <span class="section-divider-inner">Horas Externas Acreditadas</span>
        </div>

        <table class="history-table">

            <thead>
                <tr>
                    <th style="width:5%;" class="center">#</th>
                    <th style="width:13%;">Período</th>
                    <th style="width:32%;">Institución</th>
                    <th style="width:38%;">Descripción</th>
                    <th style="width:12%;" class="right">Horas</th>
                </tr>
            </thead>

            <tbody>
                @foreach($externalHours as $i => $ext)
                    <tr>
                        <td class="center" style="font-size:7pt; color:#000000; font-weight:bold;">{{ $i + 1 }}</td>
                        <td class="bold">{{ $ext['period'] }}</td>
                        <td>{{ $ext['institution_name'] }}</td>
                        <td style="font-size:7.5pt; color:#000000; font-weight:bold;">{{ $ext['description'] ?? '—' }}</td>
                        <td class="right bold c-green">+{{ number_format($ext['hours'], 1) }}h</td>
                    </tr>
                @endforeach
            </tbody>

        </table>

    @endif

    {{-- ═══════════════════════════════════════
    TOTAL GENERAL
    ════════════════════════════════════════ --}}

    @php $grandTotal = $runningTotal + ($externalTotal ?? 0); @endphp

    <div class="total-box">
        <table>
            <tr>
                <td class="total-title">TOTAL ACUMULADO GENERAL</td>
                <td class="total-value">{{ number_format($grandTotal, 1) }}h</td>
            </tr>
        </table>
    </div>

    {{-- ═══════════════════════════════════════
    FOOTER FIJO (todas las páginas)
    ════════════════════════════════════════ --}}

    <div class="footer">
        <table>
            <tr>
                <td style="text-align:left; color:#94a3b8; font-size:7pt;">
                    {{ $institution?->name ?? 'Institución Educativa' }}
                    &nbsp;·&nbsp;
                    Generado el {{ $generatedAt }}
                </td>
                <td style="text-align:right; width:80pt; font-size:7pt; color:#94a3b8;">
                    {{-- El número de página lo inyecta el script PHP de abajo --}}
                </td>
            </tr>
        </table>
    </div>

    {{-- Script Dompdf: número de página a la derecha del footer --}}
    <script type="text/php">
if (isset($pdf)) {
    $font      = $fontMetrics->getFont('DejaVu Sans');
    $size      = 7;
    $color     = [0.60, 0.60, 0.60];
    $pageWidth = $pdf->get_width();
    $margin    = 38;   /* mismo margen derecho del @page */
    $text      = "Página {PAGE_NUM} de {PAGE_COUNT}";
    $textWidth = $fontMetrics->getTextWidth($text, $font, $size);
    $x         = $pageWidth - $margin - $textWidth;
    $y         = $pdf->get_height() - 22;
    $pdf->page_text($x, $y, $text, $font, $size, $color);
}
</script>

</body>

</html>