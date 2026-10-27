<?php

namespace App\Services;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

class SimpleXlsxExporter
{
    /**
     * Export dataset directly as a genuine Microsoft Excel OpenXML (.xlsx) file.
     *
     * @param  string  $filename  Filename including .xlsx extension
     * @param  string  $sheetTitle  Sheet tab title (max 31 characters)
     * @param  array<int, string>  $headers  List of table column headers
     * @param  array<int, array<int, mixed>>  $rows  List of rows
     * @param  array<int, mixed>|null  $summaryRow  Optional total/summary row at the bottom
     */
    public static function download(
        string $filename,
        string $sheetTitle,
        array $headers,
        array $rows,
        ?array $summaryRow = null
    ): BinaryFileResponse {
        $cleanTitle = substr(preg_replace('/[\\\\\/*\?:\[\]]/', '', $sheetTitle), 0, 31) ?: 'Laporan';

        $tempDir = sys_get_temp_dir().'/xlsx_'.uniqid('pos_', true);
        mkdir($tempDir, 0777, true);
        mkdir($tempDir.'/_rels', 0777, true);
        mkdir($tempDir.'/xl', 0777, true);
        mkdir($tempDir.'/xl/_rels', 0777, true);
        mkdir($tempDir.'/xl/worksheets', 0777, true);

        // [Content_Types].xml
        file_put_contents($tempDir.'/[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
  <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
  <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
</Types>');

        // _rels/.rels
        file_put_contents($tempDir.'/_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>');

        // xl/_rels/workbook.xml.rels
        file_put_contents($tempDir.'/xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
  <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
</Relationships>');

        // xl/workbook.xml
        file_put_contents($tempDir.'/xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
  <sheets>
    <sheet name="'.htmlspecialchars($cleanTitle, ENT_QUOTES | ENT_XML1, 'UTF-8').'" sheetId="1" r:id="rId1"/>
  </sheets>
</workbook>');

        // xl/styles.xml (Professional Modern Classic Theme: Dark Emerald #1B4332 header & Sand gold #F4ECE0 summary)
        file_put_contents($tempDir.'/xl/styles.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <fonts count="3">
    <font><sz val="10.5"/><name val="Segoe UI"/></font>
    <font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Segoe UI"/></font>
    <font><b/><sz val="11"/><color rgb="FF262017"/><name val="Segoe UI"/></font>
  </fonts>
  <fills count="4">
    <fill><patternFill patternType="none"/></fill>
    <fill><patternFill patternType="gray125"/></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FF1B4332"/></patternFill></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FFF4ECE0"/></patternFill></fill>
  </fills>
  <borders count="3">
    <border><left/><right/><top/><bottom/></border>
    <border>
      <left style="thin"><color rgb="FFD4CEBF"/></left>
      <right style="thin"><color rgb="FFD4CEBF"/></right>
      <top style="thin"><color rgb="FFD4CEBF"/></top>
      <bottom style="thin"><color rgb="FFD4CEBF"/></bottom>
    </border>
    <border>
      <left style="thin"><color rgb="FFC59B27"/></left>
      <right style="thin"><color rgb="FFC59B27"/></right>
      <top style="medium"><color rgb="FF1B4332"/></top>
      <bottom style="double"><color rgb="FF1B4332"/></bottom>
    </border>
  </borders>
  <cellStyleXfs count="1">
    <xf numFmtId="0" fontId="0" fillId="0" borderId="0"/>
  </cellStyleXfs>
  <cellXfs count="4">
    <!-- 0: Standard Body Cell -->
    <xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0"/>
    <!-- 1: Header Row Cell (Emerald Background, White Bold) -->
    <xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"/>
    <!-- 2: Summary/Total Row Cell (Gold-Sand Background, Dark Bold) -->
    <xf numFmtId="0" fontId="2" fillId="3" borderId="2" xfId="0" applyFont="1" applyFill="1" applyBorder="1"/>
    <!-- 3: Numeric Body Cell -->
    <xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0"/>
  </cellXfs>
</styleSheet>');

        // Calculate dynamic column widths
        $colWidths = [];
        foreach ($headers as $colIdx => $h) {
            $colWidths[$colIdx] = max(12, mb_strlen((string) $h) + 4);
        }

        // Build Sheet Data XML
        $sheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'."\n";
        $sheetXml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'."\n";

        $rowsXml = "<sheetData>\n";
        $rowNum = 1;

        // Render Header Row
        $rowsXml .= '  <row r="'.$rowNum.'">'."\n";
        foreach ($headers as $cIdx => $headerText) {
            $colLetter = self::columnLetter($cIdx);
            $cellRef = $colLetter.$rowNum;
            $safeText = htmlspecialchars((string) $headerText, ENT_QUOTES | ENT_XML1, 'UTF-8');
            $rowsXml .= '    <c r="'.$cellRef.'" t="inlineStr" s="1"><is><t>'.$safeText.'</t></is></c>'."\n";
        }
        $rowsXml .= "  </row>\n";
        $rowNum++;

        // Render Data Rows
        foreach ($rows as $row) {
            $rowsXml .= '  <row r="'.$rowNum.'">'."\n";
            foreach ($row as $cIdx => $val) {
                $colLetter = self::columnLetter($cIdx);
                $cellRef = $colLetter.$rowNum;

                // Track max column width
                $len = mb_strlen((string) $val);
                if (isset($colWidths[$cIdx]) && $len + 3 > $colWidths[$cIdx]) {
                    $colWidths[$cIdx] = min(60, $len + 3);
                }

                if (is_null($val) || $val === '') {
                    $rowsXml .= '    <c r="'.$cellRef.'" s="0"/>'."\n";
                } elseif (is_int($val) || is_float($val)) {
                    $rowsXml .= '    <c r="'.$cellRef.'" s="3"><v>'.$val.'</v></c>'."\n";
                } elseif (is_numeric($val) && ! str_starts_with((string) $val, '0') && ! str_contains((string) $val, '-')) {
                    $rowsXml .= '    <c r="'.$cellRef.'" s="3"><v>'.$val.'</v></c>'."\n";
                } else {
                    $safeText = htmlspecialchars((string) $val, ENT_QUOTES | ENT_XML1, 'UTF-8');
                    $rowsXml .= '    <c r="'.$cellRef.'" t="inlineStr" s="0"><is><t>'.$safeText.'</t></is></c>'."\n";
                }
            }
            $rowsXml .= "  </row>\n";
            $rowNum++;
        }

        // Render Summary Row if provided
        if (! empty($summaryRow)) {
            $rowsXml .= '  <row r="'.$rowNum.'">'."\n";
            foreach ($summaryRow as $cIdx => $val) {
                $colLetter = self::columnLetter($cIdx);
                $cellRef = $colLetter.$rowNum;

                if (is_null($val) || $val === '') {
                    $rowsXml .= '    <c r="'.$cellRef.'" s="2"/>'."\n";
                } elseif (is_int($val) || is_float($val) || (is_numeric($val) && ! str_starts_with((string) $val, '0'))) {
                    $rowsXml .= '    <c r="'.$cellRef.'" s="2"><v>'.$val.'</v></c>'."\n";
                } else {
                    $safeText = htmlspecialchars((string) $val, ENT_QUOTES | ENT_XML1, 'UTF-8');
                    $rowsXml .= '    <c r="'.$cellRef.'" t="inlineStr" s="2"><is><t>'.$safeText.'</t></is></c>'."\n";
                }
            }
            $rowsXml .= "  </row>\n";
        }

        $rowsXml .= "</sheetData>\n";

        // Cols definition for auto width
        $colsXml = "<cols>\n";
        foreach ($colWidths as $cIdx => $w) {
            $colNum = $cIdx + 1;
            $colsXml .= '  <col min="'.$colNum.'" max="'.$colNum.'" width="'.number_format($w, 1, '.', '').'" customWidth="1"/>'."\n";
        }
        $colsXml .= "</cols>\n";

        $sheetXml .= $colsXml.$rowsXml."</worksheet>\n";
        file_put_contents($tempDir.'/xl/worksheets/sheet1.xml', $sheetXml);

        // Package into genuine .xlsx zip archive
        $zipFilePath = sys_get_temp_dir().'/'.uniqid('export_', true).'.xlsx';
        if (file_exists($zipFilePath)) {
            unlink($zipFilePath);
        }

        $zip = new ZipArchive;
        $zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($tempDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            if (! $file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($tempDir) + 1);
                $relativePath = str_replace('\\', '/', $relativePath);
                $zip->addFile($filePath, $relativePath);
            }
        }

        $zip->close();

        // Clean up temporary XML directory
        self::deleteDir($tempDir);

        return response()->download(
            $zipFilePath,
            $filename,
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Cache-Control' => 'max-age=0, no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0',
            ]
        )->deleteFileAfterSend(true);
    }

    /**
     * Convert 0-indexed column integer to Excel column letters (A, B, ..., Z, AA, AB, ...).
     */
    public static function columnLetter(int $colIndex): string
    {
        $letter = '';
        $colIndex++;
        while ($colIndex > 0) {
            $modulo = ($colIndex - 1) % 26;
            $letter = chr(65 + $modulo).$letter;
            $colIndex = (int) (($colIndex - $modulo) / 26);
        }

        return $letter;
    }

    /**
     * Recursively delete temporary working directory.
     */
    private static function deleteDir(string $dirPath): void
    {
        if (! is_dir($dirPath)) {
            return;
        }

        $items = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dirPath, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($items as $item) {
            if ($item->isDir()) {
                rmdir($item->getRealPath());
            } else {
                unlink($item->getRealPath());
            }
        }

        rmdir($dirPath);
    }
}
