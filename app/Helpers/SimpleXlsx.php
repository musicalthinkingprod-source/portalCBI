<?php

namespace App\Helpers;

/**
 * Generador XLSX mínimo usando ZipArchive (extensión nativa de PHP).
 * No requiere paquetes externos.
 */
class SimpleXlsx
{
    private array $rows = [];

    public function addRow(array $values): void
    {
        $this->rows[] = $values;
    }

    public function save(string $path): void
    {
        $zip = new \ZipArchive();
        $zip->open($path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        $zip->addFromString('[Content_Types].xml',          $this->contentTypes());
        $zip->addFromString('_rels/.rels',                  $this->rels());
        $zip->addFromString('xl/workbook.xml',              $this->workbook());
        $zip->addFromString('xl/_rels/workbook.xml.rels',   $this->workbookRels());
        $zip->addFromString('xl/styles.xml',                $this->styles());
        $zip->addFromString('xl/worksheets/sheet1.xml',     $this->sheet());

        $zip->close();
    }

    private function sheet(): string
    {
        $xml  = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $xml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"'
              . ' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
              . '<sheetData>';

        foreach ($this->rows as $ri => $row) {
            $rn   = $ri + 1;
            $xml .= "<row r=\"{$rn}\">";
            foreach ($row as $ci => $val) {
                $col  = $this->colLetter($ci + 1);
                $ref  = "{$col}{$rn}";
                if (is_int($val) || is_float($val)) {
                    $xml .= "<c r=\"{$ref}\"><v>" . $val . "</v></c>";
                } else {
                    $esc  = htmlspecialchars((string) $val, ENT_XML1 | ENT_QUOTES, 'UTF-8');
                    $xml .= "<c r=\"{$ref}\" t=\"inlineStr\"><is><t>{$esc}</t></is></c>";
                }
            }
            $xml .= '</row>';
        }

        $xml .= '</sheetData></worksheet>';
        return $xml;
    }

    private function colLetter(int $n): string
    {
        $s = '';
        while ($n > 0) {
            $n--;
            $s  = chr(65 + ($n % 26)) . $s;
            $n  = (int) ($n / 26);
        }
        return $s;
    }

    private function contentTypes(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml"  ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml"'
            . '  ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml"'
            . '  ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml"'
            . '  ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '</Types>';
    }

    private function rels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument"'
            . '  Target="xl/workbook.xml"/>'
            . '</Relationships>';
    }

    private function workbook(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"'
            . '  xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="Hoja1" sheetId="1" r:id="rId1"/></sheets>'
            . '</workbook>';
    }

    private function workbookRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet"'
            . '  Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles"'
            . '  Target="styles.xml"/>'
            . '</Relationships>';
    }

    private function styles(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts><font><sz val="11"/><name val="Calibri"/></font></fonts>'
            . '<fills><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill></fills>'
            . '<borders><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/></cellXfs>'
            . '</styleSheet>';
    }
}
