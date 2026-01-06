<?php
/**
 * Simple Excel Parser
 * Supports:
 * 1. XML Spreadsheet 2003 (.xls key)
 * 2. Office Open XML (.xlsx)
 */

class SimpleXLSXParser {
    
    public function parse($filePath) {
        $content = file_get_contents($filePath);
        
        // 1. XML Spreadsheet 2003
        if (strpos($content, '<?xml') !== false && strpos($content, '<Workbook') !== false) {
            return $this->parseXMLSpreadsheet($content);
        }
        
        // 2. XLSX (Zip)
        if (substr($content, 0, 4) === "PK\x03\x04") { 
            // Basic magic number check for zip to avoid warnings if not zip
            $zip = new ZipArchive;
            if ($zip->open($filePath) === TRUE) {
                $result = $this->parseXLSX($zip);
                $zip->close();
                return $result;
            }
        }

        throw new Exception("Unsupported file format. Please use the provided Template (.xls) or .xlsx");
    }

    private function parseXMLSpreadsheet($content) {
        $xml = simplexml_load_string($content);
        $rows = [];
        
        // Register namespace for XPath and attributes
        $xml->registerXPathNamespace('ss', 'urn:schemas-microsoft-com:office:spreadsheet');

        // Locate Table
        $worksheets = $xml->xpath('//ss:Worksheet');
        if (empty($worksheets)) return [];
        
        $table = $worksheets[0]->Table;
        if (!$table || !isset($table->Row)) return [];

        foreach ($table->Row as $row) {
            $rowData = [];
            $currentIndex = 0; // 0-based tracking
            
            foreach ($row->Cell as $cell) {
                // Check for ss:Index
                $attrs = $cell->attributes('urn:schemas-microsoft-com:office:spreadsheet');
                if (isset($attrs['Index'])) {
                    $targetIndex = (int)$attrs['Index'] - 1; // Excel is 1-based
                    // Fill gaps
                    while ($currentIndex < $targetIndex) {
                        $rowData[$currentIndex] = '';
                        $currentIndex++;
                    }
                }
                
                // Get Data
                $data = (string)$cell->Data;
                $rowData[$currentIndex] = $data;
                
                // Check for MergeAcross (Merged cells impl)
                // If merged, we might need to replicate/skip? 
                // For results upload, we generally expect 1-to-1. 
                // But template header has Merge. Header parsing might need this.
                if (isset($attrs['MergeAcross'])) {
                    $mergeCount = (int)$attrs['MergeAcross'];
                    for ($k=0; $k<$mergeCount; $k++) {
                        $currentIndex++;
                        $rowData[$currentIndex] = ''; // Fill merged slots
                    }
                }
                
                $currentIndex++;
            }
            // Sort by key to ensure array order matches columns? 
            // PHP arrays are ordered by insertion, so filling gaps ensures indices are correct.
            // But if gaps were filled out of order? No, loop is sequential.
            // However, $rowData might be sparse if we assigned by index? 
            // My loop fills $rowData[$currentIndex] sequentially.
            
            $rows[] = $rowData;
        }
        return $rows;
    }

    private function parseXLSX($zip) {
        $sharedStrings = [];
        if ($zip->locateName('xl/sharedStrings.xml') !== false) {
            $xml = simplexml_load_string($zip->getFromName('xl/sharedStrings.xml'));
            foreach ($xml->si as $si) {
                $sharedStrings[] = (string)$si->t;
            }
        }

        if ($zip->locateName('xl/worksheets/sheet1.xml') === false) {
            throw new Exception("No worksheet found in XLSX.");
        }
        
        $sheetXml = simplexml_load_string($zip->getFromName('xl/worksheets/sheet1.xml'));
        $rows = [];

        foreach ($sheetXml->sheetData->row as $row) {
            $rowData = [];
            $colIndex = 0;
            
            foreach ($row->c as $cell) {
                // Parse Column Reference (e.g. "A1", "B1") to find index
                $r = (string)$cell['r'];
                $colLetter = preg_replace('/[0-9]+/', '', $r);
                $targetIndex = $this->colToIndex($colLetter);
                
                // Fill gaps
                while ($colIndex < $targetIndex) {
                    $rowData[$colIndex] = '';
                    $colIndex++;
                }
                
                $val = (string)$cell->v;
                $t = (string)$cell['t'];
                
                if ($t === 's') {
                    $val = $sharedStrings[(int)$val] ?? '';
                }
                $rowData[$colIndex] = $val;
                $colIndex++;
            }
            $rows[] = $rowData;
        }
        return $rows;
    }
    
    // Helper: A => 0, B => 1, AA => 26
    private function colToIndex($col) {
        $col = strtoupper($col);
        $len = strlen($col);
        $index = 0;
        for ($i = 0; $i < $len; $i++) {
            $index *= 26;
            $index += ord($col[$i]) - 64;
        }
        return $index - 1;
    }
}
