<?php
// Penulis file .xlsx sederhana (tanpa library luar, hanya butuh ekstensi zip bawaan PHP).
class SimpleXlsx {
    private $sheets = [];
    // gaya: 0 biasa, 1 judul, 2 header, 3 teks, 4 tanggal, 5 angka, 6 rupiah, 7 persen, 8 teks kiri
    public function addSheet($name) { $this->sheets[] = ['name' => $name, 'cells' => [], 'merges' => [], 'widths' => [], 'heights' => [], 'freeze' => null]; return count($this->sheets) - 1; }
    public function set($sh, $row, $col, $val, $style = 0) { $this->sheets[$sh]['cells'][$row][$col] = [$val, $style]; }
    public function merge($sh, $range) { $this->sheets[$sh]['merges'][] = $range; }
    public function width($sh, $col, $w) { $this->sheets[$sh]['widths'][$col] = $w; }
    public function height($sh, $row, $h) { $this->sheets[$sh]['heights'][$row] = $h; }
    public function freeze($sh, $cell) { $this->sheets[$sh]['freeze'] = $cell; }

    public static function colName($n) { $s = ''; while ($n > 0) { $m = ($n - 1) % 26; $s = chr(65 + $m) . $s; $n = intdiv($n - 1, 26); } return $s; }
    public static function dateSerial($ymd) { $t = strtotime($ymd . ' 00:00:00 UTC'); return $t === false ? null : (int)floor($t / 86400) + 25569; }
    private static function x($v) { return htmlspecialchars((string)$v, ENT_XML1 | ENT_QUOTES, 'UTF-8'); }

    private function styles() {
        $b = '<border><left style="thin"><color auto="1"/></left><right style="thin"><color auto="1"/></right><top style="thin"><color auto="1"/></top><bottom style="thin"><color auto="1"/></bottom><diagonal/></border>';
        $xf = function ($font, $fill, $border, $num, $h, $wrap) {
            return '<xf numFmtId="' . $num . '" fontId="' . $font . '" fillId="' . $fill . '" borderId="' . $border . '" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="' . $h . '" vertical="center"' . ($wrap ? ' wrapText="1"' : '') . '/></xf>';
        };
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<numFmts count="2"><numFmt numFmtId="164" formatCode="dd\ mmmm\ yyyy"/><numFmt numFmtId="165" formatCode="[$Rp-421]#,##0.00"/></numFmts>'
            . '<fonts count="3"><font><sz val="10"/><name val="Arial"/></font><font><b/><sz val="25"/><name val="Arial"/></font><font><b/><sz val="12"/><name val="Arial"/></font></fonts>'
            . '<fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FFD9E1F2"/><bgColor indexed="64"/></patternFill></fill></fills>'
            . '<borders count="2"><border><left/><right/><top/><bottom/><diagonal/></border>' . $b . '</borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="9">'
            . $xf(0, 0, 0, 0, 'general', false)      // 0
            . $xf(1, 0, 0, 0, 'center', false)       // 1 judul
            . $xf(2, 2, 1, 0, 'center', true)        // 2 header
            . $xf(0, 0, 1, 0, 'center', false)       // 3 teks
            . $xf(0, 0, 1, 164, 'center', false)     // 4 tanggal
            . $xf(0, 0, 1, 0, 'center', false)       // 5 angka
            . $xf(0, 0, 1, 165, 'center', false)     // 6 rupiah
            . $xf(0, 0, 1, 10, 'center', false)      // 7 persen
            . $xf(0, 0, 1, 0, 'left', true)          // 8 teks kiri
            . '</cellXfs></styleSheet>';
    }

    public function save($path) {
        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) { return false; }
        $n = count($this->sheets);
        $ct = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>';
        $wb = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets>';
        $rel = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';
        foreach ($this->sheets as $i => $sh) {
            $k = $i + 1;
            $ct .= '<Override PartName="/xl/worksheets/sheet' . $k . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
            $wb .= '<sheet name="' . self::x(preg_replace('/[\\\\\/\?\*\[\]:]/', '-', $sh['name'])) . '" sheetId="' . $k . '" r:id="rId' . $k . '"/>';
            $rel .= '<Relationship Id="rId' . $k . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $k . '.xml"/>';
            $zip->addFromString('xl/worksheets/sheet' . $k . '.xml', $this->sheetXml($sh));
        }
        $rel .= '<Relationship Id="rId' . ($n + 1) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>';
        $zip->addFromString('[Content_Types].xml', $ct . '</Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
        $zip->addFromString('xl/workbook.xml', $wb . '</sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', $rel);
        $zip->addFromString('xl/styles.xml', $this->styles());
        return $zip->close();
    }

    private function sheetXml($sh) {
        $x = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';
        $x .= '<sheetViews><sheetView workbookViewId="0">';
        if ($sh['freeze'] && preg_match('/^([A-Z]+)(\d+)$/', $sh['freeze'], $m)) {
            $col = 0; foreach (str_split($m[1]) as $ch) { $col = $col * 26 + ord($ch) - 64; }
            $xs = $col - 1; $ys = (int)$m[2] - 1;
            $x .= '<pane' . ($xs ? ' xSplit="' . $xs . '"' : '') . ($ys ? ' ySplit="' . $ys . '"' : '') . ' topLeftCell="' . $sh['freeze'] . '" activePane="' . ($xs && $ys ? 'bottomRight' : ($ys ? 'bottomLeft' : 'topRight')) . '" state="frozen"/>';
        }
        $x .= '</sheetView></sheetViews><sheetFormatPr defaultRowHeight="15"/>';
        if ($sh['widths']) {
            $x .= '<cols>'; ksort($sh['widths']);
            foreach ($sh['widths'] as $c => $w) { $x .= '<col min="' . $c . '" max="' . $c . '" width="' . $w . '" customWidth="1"/>'; }
            $x .= '</cols>';
        }
        $x .= '<sheetData>';
        $rows = array_unique(array_merge(array_keys($sh['cells']), array_keys($sh['heights']))); sort($rows);
        foreach ($rows as $r) {
            $x .= '<row r="' . $r . '"' . (isset($sh['heights'][$r]) ? ' ht="' . $sh['heights'][$r] . '" customHeight="1"' : '') . '>';
            $cells = $sh['cells'][$r] ?? []; ksort($cells);
            foreach ($cells as $c => [$v, $st]) {
                $ref = self::colName($c) . $r;
                if ($v === null || $v === '') { $x .= '<c r="' . $ref . '" s="' . $st . '"/>'; }
                elseif (is_int($v) || is_float($v)) { $x .= '<c r="' . $ref . '" s="' . $st . '"><v>' . $v . '</v></c>'; }
                else { $x .= '<c r="' . $ref . '" s="' . $st . '" t="inlineStr"><is><t xml:space="preserve">' . self::x($v) . '</t></is></c>'; }
            }
            $x .= '</row>';
        }
        $x .= '</sheetData>';
        if ($sh['merges']) {
            $x .= '<mergeCells count="' . count($sh['merges']) . '">';
            foreach ($sh['merges'] as $m) { $x .= '<mergeCell ref="' . $m . '"/>'; }
            $x .= '</mergeCells>';
        }
        return $x . '</worksheet>';
    }
}
