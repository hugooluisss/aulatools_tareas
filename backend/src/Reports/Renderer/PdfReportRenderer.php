<?php

declare(strict_types=1);

namespace App\Reports\Renderer;

use App\Reports\Model\RenderedReport;
use App\Reports\Model\ReportViewModel;
use Dompdf\Dompdf;
use Dompdf\Options;

final class PdfReportRenderer implements ReportRenderer
{
    public function render(ReportViewModel $viewModel): RenderedReport
    {
        $html = $viewModel->title === 'Lista de asistencia'
            ? $this->attendanceHtml($viewModel)
            : $this->taskReportCardHtml($viewModel);
        $options = new Options();
        $options->setIsRemoteEnabled(false);
        $pdf = new Dompdf($options);
        $pdf->setPaper('letter', 'portrait');
        $pdf->loadHtml($html, 'UTF-8');
        $pdf->render();
        return new RenderedReport($pdf->output(), 'application/pdf', $viewModel->header['filename']);
    }

    private function attendanceHtml(ReportViewModel $viewModel): string
    {
        $escape = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $header = $viewModel->header;
        $logo = '';
        $logoPath = $header['logo_path'] ?? null;
        if (is_string($logoPath) && preg_match('#^/uploads/school/[a-zA-Z0-9._-]+$#D', $logoPath)) {
            $file = dirname(__DIR__, 3) . '/public' . $logoPath;
            $bytes = @file_get_contents($file);
            $mime = is_string($bytes) ? (new \finfo(FILEINFO_MIME_TYPE))->buffer($bytes) : false;
            if (is_string($bytes) && is_string($mime) && in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
                $logo = '<img class="logo" src="data:' . $mime . ';base64,' . base64_encode($bytes) . '">';
            }
        }
        $title = '<div class="heading">' . $logo . '<div><h1>' . $escape($viewModel->title) . '</h1><p>'
            . $escape($header['school']) . ' · ' . $escape($header['cycle']) . ' · ' . $escape($header['group']) . '</p>';
        if (isset($header['subject'])) {
            $title .= '<p>' . $escape($header['subject']) . ' · ' . $escape($header['teacher']) . '</p>';
        }
        $title .= '<p>Periodo: ' . $escape($header['period']) . '</p></div></div>';
        $html = '<!doctype html><html><head><meta charset="UTF-8"><style>
            @page { margin: 20px; } body { font-family: DejaVu Sans, sans-serif; font-size: 10px; }
            .heading { display: table; width: 100%; } .heading > * { display: table-cell; vertical-align: middle; }
            .logo { width: auto; max-width: 100px; max-height: 50px; margin-right: 12px; }
            h1 { font-size: 16px; margin: 0 0 6px; } p { margin: 3px 0; }
            table { width: 100%; border-collapse: collapse; margin-top: 12px; }
            thead { display: table-header-group; } th, td { border: 1px solid #555; padding: 3px; }
            th { background: #eee; } .enrollment { width: 55px; text-align: center; font-size: 8px; }
            .student { width: auto; } .date { width: 26px; text-align: center; font-size: 7px; } .check { height: 23px; }
            </style></head><body>' . $title . '<table><thead><tr>';
        foreach ($viewModel->tables[0]['headers'] as $index => $label) {
            $class = $index < 2 ? ($index === 0 ? 'enrollment' : 'student') : 'date';
            $html .= '<th class="' . $class . '">' . $escape($label) . '</th>';
        }
        $html .= '</tr></thead><tbody>';
        foreach ($viewModel->tables[0]['rows'] as $row) {
            $html .= '<tr>';
            foreach ($row as $index => $value) {
                $class = $index < 2 ? ($index === 0 ? 'enrollment' : 'student') : 'check';
                $html .= '<td class="' . $class . '">' . $escape($value) . '</td>';
            }
            $html .= '</tr>';
        }
        return $html . '</tbody></table></body></html>';
    }

    private function taskReportCardHtml(ReportViewModel $viewModel): string
    {
        $escape = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $html = '<!doctype html><html><head><meta charset="UTF-8"><style>
            @page { margin: 28px; } body { font-family: DejaVu Sans, sans-serif; font-size: 11px; }
            h1 { font-size: 18px; margin-bottom: 8px; } p { margin: 4px 0; }
            table { width: 100%; border-collapse: collapse; margin-top: 16px; }
            th, td { border: 1px solid #555; padding: 6px; } th { background: #eee; }
            .student-card { page-break-after: always; } .student-card:last-child { page-break-after: auto; }
            </style></head><body>';
        foreach ($viewModel->sections as $section) {
            $html .= '<section class="student-card"><h1>' . $escape($viewModel->title) . '</h1>';
            foreach ($section['header'] as $label => $value) {
                $html .= '<p><strong>' . $escape($label) . ':</strong> ' . $escape($value) . '</p>';
            }
            $html .= '<table><thead><tr>';
            foreach ($section['table']['headers'] as $label) {
                $html .= '<th>' . $escape($label) . '</th>';
            }
            $html .= '</tr></thead><tbody>';
            foreach ($section['table']['rows'] as $row) {
                $html .= '<tr>';
                foreach ($row as $value) {
                    $html .= '<td>' . $escape($value) . '</td>';
                }
                $html .= '</tr>';
            }
            $html .= '</tbody></table></section>';
        }
        return $html . '</body></html>';
    }
}
