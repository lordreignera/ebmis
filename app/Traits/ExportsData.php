<?php

namespace App\Traits;

use Illuminate\Http\Response;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Dompdf\Dompdf;
use Dompdf\Options;

trait ExportsData
{
    /**
     * Export data to Excel format
     */
    public function exportToExcel($data, $headers, $filename, $title = null)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Set title if provided
        if ($title) {
            $sheet->setCellValue('A1', $title);
            $sheet->mergeCells('A1:' . $this->getColumnLetter(count($headers)) . '1');
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
            $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getRowDimension(1)->setRowHeight(25);
            
            // Add export date
            $sheet->setCellValue('A2', 'Export Date: ' . now()->format('d/m/Y H:i'));
            $sheet->mergeCells('A2:' . $this->getColumnLetter(count($headers)) . '2');
            $sheet->getStyle('A2')->getFont()->setItalic(true);
            
            $headerRow = 4;
        } else {
            $headerRow = 1;
        }
        
        // Set headers
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . $headerRow, $header);
            $sheet->getStyle($col . $headerRow)->getFont()->setBold(true);
            $sheet->getStyle($col . $headerRow)->getFill()
                  ->setFillType(Fill::FILL_SOLID)
                  ->getStartColor()->setRGB('E2E8F0');
            $col++;
        }
        
        // Set data
        $row = $headerRow + 1;
        foreach ($data as $item) {
            $col = 'A';
            foreach ($item as $value) {
                $sheet->setCellValue($col . $row, $value);
                $col++;
            }
            $row++;
        }
        
        // Auto size columns
        foreach (range('A', $this->getColumnLetter(count($headers) - 1)) as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
        
        // Create writer and return response
        $writer = new Xlsx($spreadsheet);
        
        $response = new Response();
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '.xlsx"');
        
        ob_start();
        $writer->save('php://output');
        $content = ob_get_clean();
        
        $response->setContent($content);
        return $response;
    }
    
    /**
     * Export data to PDF format
     */
    public function exportToPdf($data, $headers, $filename, $title = null, $orientation = 'portrait')
    {
        $html = $this->generatePdfHtml($data, $headers, $title);
        
        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', true);
        
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', $orientation);
        $dompdf->render();
        
        return response($dompdf->output())
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '.pdf"');
    }
    
    /**
     * Export data to CSV format
     */
    public function exportToCsv($data, $headers, $filename)
    {
        $callback = function() use ($data, $headers) {
            $file = fopen('php://output', 'w');
            
            // Add headers
            fputcsv($file, $headers);
            
            // Add data rows
            foreach ($data as $row) {
                fputcsv($file, $row);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '.csv"',
        ]);
    }
    
    /**
     * Generate HTML for PDF
     */
    private function generatePdfHtml($data, $headers, $title)
    {
        $html = '<html><head>';
        $html .= '<style>';
        $html .= 'body { font-family: Arial, sans-serif; font-size: 12px; margin: 20px; }';
        $html .= 'h1 { color: #333; text-align: center; margin-bottom: 5px; }';
        $html .= '.export-date { text-align: center; font-style: italic; margin-bottom: 20px; color: #666; }';
        $html .= 'table { width: 100%; border-collapse: collapse; margin-top: 20px; }';
        $html .= 'th { background-color: #f8f9fa; padding: 8px; text-align: left; border: 1px solid #ddd; font-weight: bold; }';
        $html .= 'td { padding: 8px; text-align: left; border: 1px solid #ddd; }';
        $html .= 'tr:nth-child(even) { background-color: #f9f9f9; }';
        $html .= '.text-right { text-align: right; }';
        $html .= '.text-center { text-align: center; }';
        $html .= '</style>';
        $html .= '</head><body>';
        
        if ($title) {
            $html .= '<h1>' . $title . '</h1>';
            $html .= '<div class="export-date">Export Date: ' . now()->format('d/m/Y H:i') . '</div>';
        }
        
        $html .= '<table>';
        $html .= '<thead><tr>';
        foreach ($headers as $header) {
            $html .= '<th>' . $header . '</th>';
        }
        $html .= '</tr></thead>';
        
        $html .= '<tbody>';
        foreach ($data as $row) {
            $html .= '<tr>';
            foreach ($row as $cell) {
                $html .= '<td>' . htmlspecialchars($cell ?? '') . '</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</tbody>';
        $html .= '</table>';
        $html .= '</body></html>';
        
        return $html;
    }
    
    /**
     * Convert column number to Excel column letter
     */
    private function getColumnLetter($columnNumber)
    {
        $letter = '';
        while ($columnNumber > 0) {
            $columnNumber--;
            $letter = chr($columnNumber % 26 + 65) . $letter;
            $columnNumber = intval($columnNumber / 26);
        }
        return $letter;
    }
}