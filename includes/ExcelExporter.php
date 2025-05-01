<?php
// includes/ExcelExporter.php
require 'vendor/autoload.php'; // Make sure you've installed PhpSpreadsheet via Composer

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class ExcelExporter {
    private $conn;

    // Constructor
    public function __construct($db) {
        $this->conn = $db;
    }

    // Generate Excel file for a project budget matching the UI Template
    public function exportBudget($projectId) {
        // --- 1. Setup ---
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Detailed Budget');

        // --- 2. Get Data ---
        require_once 'Project.php';
        $project = new Project($this->conn);
        $project->id = $projectId;
        if (!$project->readOne()) {
            throw new Exception("Project not found.");
        }

        require_once 'BudgetItem.php';
        $budgetItem = new BudgetItem($this->conn);
        $budgetItem->project_id = $projectId;
        $budgetItemsStmt = $budgetItem->readByProject();

        // --- 3. Define Structure & Rates (from image) ---
        $maxYears = $project->duration_years > 5 ? 5 : $project->duration_years; // Limit to 5 years for this template
        $years = range(1, $maxYears);
        $lastYearColIndex = 5 + $maxYears; // E=5, F=6(Y1).. J=10(Y5) -> 5+5=10
        $totalColIndex = $lastYearColIndex + 1; // K=11
        $notesColIndex = $totalColIndex + 1;   // L=12

        $lastYearColLetter = Coordinate::stringFromColumnIndex($lastYearColIndex); // e.g., 'J' for 5 years
        $totalColLetter = Coordinate::stringFromColumnIndex($totalColIndex);       // e.g., 'K'
        $notesColLetter = Coordinate::stringFromColumnIndex($notesColIndex);       // e.g., 'L'

        // Define rows exactly matching the image structure
        // Key: Row Number, Value: [Label, Type (category_header, item, fringe_rate, calc_header, calculation), Style (bold, italic, etc.), Merge Columns ('A:E')]
        // ** FIX: Added explicit definitions for rows 22, 29, 43 to prevent undefined key warnings **
         $layout = [
             // Headers
             1 => ['Title:', 'header_label', ['bold' => true], null],
             2 => ['Funding source:', 'header_label', ['bold' => true], null],
             3 => ['PI:', 'header_label', ['bold' => true], null],
             4 => ['Project Start and End Dates:', 'header_label', ['bold' => true], null],
             // Column Headers
             5 => ['Personnel Compensation', 'col_header_group', ['bold' => true, 'bg' => 'FFFFFF'], 'A:L'], // White Background for main header
             6 => [null, 'col_header', ['bold' => true, 'bg' => 'D9D9D9'], null], // Will populate A6:L6 below
             // Personnel Items
             7 => ['PI', 'item', [], 'A:E'],
             8 => ['Co-PI', 'item', [], 'A:E'],
             9 => ['Other personnel', 'item', [], 'A:E'],
             10 => ['UI proffesional staff & Post Docs', 'item', [], 'A:E'],
             11 => ['GRAs / UGrads', 'item', [], 'A:E'],
             12 => ['Temp Help', 'item', [], 'A:E'],
             13 => [null, 'spacer', [], null],
             // Fringe Header & Rates
             14 => ['Fringe', 'category_header', ['bold' => true, 'bg' => 'FFFFCC'], 'A:E'], // Light Yellow BG
             15 => ['FY23-24 Fringe Rates', 'fringe_rate_header', ['bold' => true, 'italic' => true], 'D:E'],
             16 => ['Faculty', 'fringe_rate_item', ['italic' => true], 'A:D'],
             17 => ['UI proffesional staff & Post Docs', 'fringe_rate_item', ['italic' => true], 'A:D'],
             18 => ['GRAs / UGrads', 'fringe_rate_item', ['italic' => true], 'A:D'],
             19 => ['Temp Help', 'fringe_rate_item', ['italic' => true], 'A:D'],
             20 => [null, 'spacer', [], null],
             // Equipment Header & Items
             21 => ['Equipment > $5000.00', 'category_header', ['bold' => true, 'bg' => 'D9D9D9'], 'A:L'], // Grey BG
             22 => ['Equipment Placeholder', 'item', [], 'A:E'], // **FIX: Added placeholder for equipment aggregation**
             23 => [null, 'spacer', [], null], // Spacer after equipment
             // Travel Header & Items
             24 => ['Travel', 'category_header', ['bold' => true, 'bg' => 'D9D9D9'], 'A:L'],
             25 => ['Domestic', 'item', [], 'A:E'],
             26 => ['International', 'item', [], 'A:E'],
             27 => [null, 'spacer', [], null],
             // Participant Support Header & Items
             28 => ['Participant support costs (NSF only)', 'category_header', ['bold' => true, 'bg' => 'D9D9D9'], 'A:L'],
             29 => ['Participant Support Placeholder', 'item', [], 'A:E'], // **FIX: Added placeholder for participant support aggregation**
             30 => [null, 'spacer', [], null], // Spacer after participant support
             // Other Direct Costs Header & Items
             31 => ['Other Direct Costs', 'category_header', ['bold' => true, 'bg' => 'D9D9D9'], 'A:L'],
             32 => ['Materials and supplies', 'item', [], 'A:E'],
             33 => ['<$5K small equipment', 'item', [], 'A:E'],
             34 => ['Publication costs', 'item', [], 'A:E'],
             35 => ['Computer services', 'item', [], 'A:E'],
             36 => ['Software', 'item', [], 'A:E'],
             37 => ['Facility useage fees', 'item', [], 'A:E'], // Note: Corrected spelling 'usage'
             38 => ['Conference Registration', 'item', [], 'A:E'],
             39 => ['Other', 'item', [], 'A:E'],
             40 => ['Grad Student Tuition & Health Insurance', 'item', [], 'A:E'],
             41 => [null, 'spacer', [], null],
             // Subawards Header & Items
             42 => ['Consortia / Subawards', 'category_header', ['bold' => true, 'bg' => 'D9D9D9'], 'A:L'],
             43 => ['Subaward Placeholder', 'item', [], 'A:E'], // **FIX: Added placeholder for subaward aggregation**
             // Add more rows 44-47 if needed for multiple subawards (would require dynamic logic)
             48 => [null, 'spacer', [], null], // Spacer after subawards
             // Calculation Headers & Items
             49 => ['Total Direct Cost', 'calc_header', ['bold' => true], 'A:E'],
             50 => [null, 'spacer', [], null], // Spacer
             51 => ['Back out GSA T&F', 'calc_item', [], 'A:E'],
             52 => ['Back out capital EQ', 'calc_item', [], 'A:E'],
             53 => ['Back out subawards totals', 'calc_item', [], 'A:E'], // Total subaward amount to subtract
             54 => ['Sub award 1st $25k', 'calc_item', ['italic' => true], 'A:E'], // First 25k to add back
             55 => ['Needs to be customized to subaward', 'calc_note', ['italic' => true], 'L'], // Note in L col
             56 => ['Modified Total Direct Costs', 'calc_header', ['bold' => true], 'A:E'],
             57 => ['Indirect Costs', 'calc_item', ['bold' => true], 'A:D'],
             58 => ['Total Project Cost', 'calc_header', ['bold' => true, 'bg' => 'D0CECE'], 'A:E'], // Darker Grey BG
         ];

         // Define Fringe Rates & Indirect Rate (from image)
         $fringeRates = [
             'Faculty' => 0.310, // 31.0% Row 16
             'UI proffesional staff & Post Docs' => 0.413, // 41.3% Row 17
             'GRAs / UGrads' => 0.025, // 2.5% Row 18
             'Temp Help' => 0.083 // 8.3% Row 19
         ];
         $indirectRate = 0.50; // 50.0% Row 57

        // --- 4. Process Database Items ---
        $processedItems = []; // [Year][RowNumber] => amount
        $personnelTotals = []; // [Year][PersonnelItemRow] => amount (for fringe calc)
        $equipmentTotals = []; // [Year] => total amount (for MTDC backout)
        $tuitionTotals = [];   // [Year] => total amount (for MTDC backout)
        $subawardTotals = [];  // [Year] => total amount (for MTDC backout)
        $subawardFirst25k = []; // [Year] => total first 25k of subawards (for MTDC backout)

        // Initialize arrays using keys from the final $layout
        // **FIX: Initialize based on the final layout keys to ensure all rows exist**
        foreach ($years as $year) {
            $equipmentTotals[$year] = 0;
            $tuitionTotals[$year] = 0;
            $subawardTotals[$year] = 0;
            $subawardFirst25k[$year] = 0;
            foreach (array_keys($layout) as $rowNum) {
                 $processedItems[$year][$rowNum] = 0; // Initialize all defined rows to 0
            }
             foreach ([7, 8, 9, 10, 11, 12] as $pRow) { // Personnel rows
                 $personnelTotals[$year][$pRow] = 0;
             }
        }

        // Loop through database items and aggregate into the layout structure
        while ($item = $budgetItemsStmt->fetch(PDO::FETCH_ASSOC)) {
            $year = (int)$item['year'];
            if ($year < 1 || $year > $maxYears) continue; // Skip items outside the year range

            $category = trim(strtolower($item['category']));
            // Normalize common category variations
            if ($category === 'personnel compensation' || $category === 'personnel') $category = 'personnel';
            if ($category === 'fringe benefits' || $category === 'fringe') $category = 'fringe'; // AI might generate this
            if ($category === 'equipment > $5000.00' || $category === 'equipment') $category = 'equipment';
            if ($category === 'participant support costs (nsf only)') $category = 'participant support';
            if ($category === 'other direct costs') $category = 'other direct';
            if ($category === 'consortia / subawards' || $category === 'subawards') $category = 'subaward';


            $itemName = trim($item['item_name']);
            $amount = (float)$item['amount'] * (int)$item['quantity'];
            $targetRow = null;

            // Skip processing for Fringe category from DB - it will be calculated later
            if ($category === 'fringe') {
                continue;
            }

            // ** Mapping Logic (Needs customization based on your actual item_name values) **
            if ($category == 'personnel') {
                if (stripos($itemName, 'Principal Investigator') !== false || stripos($itemName, 'PI') !== false) $targetRow = 7;
                elseif (stripos($itemName, 'Co-PI') !== false || stripos($itemName, 'Co PI') !== false) $targetRow = 8;
                elseif (stripos($itemName, 'Postdoc') !== false || stripos($itemName, 'Professional Staff') !== false) $targetRow = 10;
                elseif (stripos($itemName, 'Graduate Student') !== false || stripos($itemName, 'GRA') !== false || stripos($itemName, 'Undergraduate') !== false) $targetRow = 11;
                elseif (stripos($itemName, 'Temp') !== false || stripos($itemName, 'Temporary') !== false) $targetRow = 12;
                else $targetRow = 9; // Default to 'Other personnel'

                 // Store for fringe calculation
                 if($targetRow) $personnelTotals[$year][$targetRow] += $amount;

            } elseif ($category == 'equipment') {
                // Aggregate all equipment onto the placeholder row
                 $targetRow = 22;
                 $equipmentTotals[$year] += $amount; // Track for MTDC backout
                 // ** Note: If multiple distinct equipment items are needed, dynamic row logic is required here. **
                 // You could potentially change the label of row 22 if only one item exists.
                 // $layout[22][0] = $itemName; // Example: Update label if only one

            } elseif ($category == 'travel') {
                if (stripos($itemName, 'Domestic') !== false) $targetRow = 25;
                elseif (stripos($itemName, 'International') !== false) $targetRow = 26;
                // Add else logic if needed for generic travel items

            } elseif ($category == 'participant support') {
                 $targetRow = 29; // Aggregate onto placeholder
                 // ** Note: Dynamic row logic needed for multiple items. **
                 // Participant support costs are typically excluded from MTDC base - handle in formulas

            } elseif ($category == 'subaward') {
                 $targetRow = 43; // Aggregate onto placeholder
                 $subawardTotals[$year] += $amount; // Track total for backout
                 $subawardFirst25k[$year] += min($amount, 25000); // Track first 25k per item for add-back
                 // ** Note: Dynamic row logic needed for multiple items. **

            } else { // Map to Other Direct Costs or default
                 if (stripos($itemName, 'Materials') !== false || stripos($itemName, 'Supplies') !== false) $targetRow = 32;
                 elseif (stripos($itemName, 'Small Equipment') !== false) $targetRow = 33; // Ensure this maps correctly
                 elseif (stripos($itemName, 'Publication') !== false) $targetRow = 34;
                 elseif (stripos($itemName, 'Computer services') !== false) $targetRow = 35;
                 elseif (stripos($itemName, 'Software') !== false) $targetRow = 36;
                 elseif (stripos($itemName, 'Facility') !== false || stripos($itemName, 'Usage') !== false) $targetRow = 37;
                 elseif (stripos($itemName, 'Conference') !== false || stripos($itemName, 'Registration') !== false) $targetRow = 38;
                 elseif (stripos($itemName, 'Tuition') !== false || stripos($itemName, 'Health Insurance') !== false || stripos($itemName, 'GSA') !== false) {
                      $targetRow = 40;
                      $tuitionTotals[$year] += $amount; // Track tuition for MTDC backout
                 }
                 else {
                      // Default to 'Other' in ODC if no specific match
                      $targetRow = 39;
                      // Or map based on category 'other direct', 'materials', etc. if available
                      if ($category === 'other direct' || $category === 'materials' || $category === 'supplies') $targetRow = 32;
                      // Add more mappings if needed
                 }
            }

            // Add amount to the target row for the specific year
            if ($targetRow !== null && isset($processedItems[$year][$targetRow])) { // Check if target row exists
                $processedItems[$year][$targetRow] += $amount;
            } else if ($targetRow !== null) {
                 // Log if a target row was identified but not found in layout/processedItems
                 error_log("Warning: Target row {$targetRow} not found for item '{$itemName}' in year {$year}.");
            }
        }


         // --- Calculate Fringe ---
         // Row 16 (Faculty Fringe): Apply rate to rows 7 (PI) + 8 (Co-PI)
         // Row 17 (Staff/Postdoc Fringe): Apply rate to row 10
         // Row 18 (GRA/UGrad Fringe): Apply rate to row 11
         // Row 19 (Temp Fringe): Apply rate to row 12
         foreach ($years as $year) {
            // Check if personnel totals exist before calculating fringe
             $processedItems[$year][16] = (($personnelTotals[$year][7] ?? 0) + ($personnelTotals[$year][8] ?? 0)) * $fringeRates['Faculty'];
             $processedItems[$year][17] = ($personnelTotals[$year][10] ?? 0) * $fringeRates['UI proffesional staff & Post Docs'];
             $processedItems[$year][18] = ($personnelTotals[$year][11] ?? 0) * $fringeRates['GRAs / UGrads'];
             $processedItems[$year][19] = ($personnelTotals[$year][12] ?? 0) * $fringeRates['Temp Help'];
         }

        // --- 5. Populate Sheet ---

        // Set Column Headers (Row 5/6)
         $sheet->setCellValue('A6', 'Personnel Compensation'); // Label for first column group
         $sheet->setCellValue('B6', 'Y1 Hours');
         $sheet->setCellValue('C6', 'Avg rate'); // As per image
         $sheet->setCellValue('D6', ''); // Empty in image
         $sheet->setCellValue('E6', ''); // Empty in image
         $colIndex = 6; // Start Year 1 in Column F
         foreach ($years as $year) {
             $sheet->setCellValue(Coordinate::stringFromColumnIndex($colIndex) . '6', 'Y' . $year);
             $colIndex++;
         }
         $sheet->setCellValue($totalColLetter . '6', 'Total');
         $sheet->setCellValue($notesColLetter . '6', 'notes');

        // Populate Header Info
        $sheet->setCellValue('B1', $project->title);
        // $sheet->setCellValue('B2', 'Funding Source Placeholder'); // Add if available
        // $sheet->setCellValue('B3', 'PI Name Placeholder'); // Add if available
        // $sheet->setCellValue('B4', 'Dates Placeholder'); // Add if available

        // Populate Main Layout
        foreach ($layout as $rowNum => $rowData) {
            // Ensure rowData is an array with expected keys/indices
            if (!is_array($rowData) || count($rowData) < 4) {
                error_log("Warning: Invalid layout data for row {$rowNum}. Skipping.");
                continue;
            }
            list($label, $type, $style, $merge) = $rowData;

             // Set Label
             if ($label !== null && $type !== 'calc_note') { // Exclude calc notes from A column
                 $sheet->setCellValue('A' . $rowNum, $label);
             } elseif ($type === 'calc_note' && $label !== null) {
                  // Put calc notes in the Notes column
                  $sheet->setCellValue($notesColLetter . $rowNum, $label);
             }


             // Merge Cells - ** FIX: Add check for valid range format **
             if ($merge !== null && is_string($merge) && strpos($merge, ':') !== false) {
                 // Construct the full range string like 'A5:L5'
                 $mergeRange = $merge . (string)$rowNum;
                 // Basic check for valid range format (e.g., A5:L5)
                 if (preg_match('/^[A-Z]+[1-9][0-9]*:[A-Z]+[1-9][0-9]*$/i', $mergeRange)) {
                      try {
                           $sheet->mergeCells($mergeRange);
                      } catch (\PhpOffice\PhpSpreadsheet\Exception $e) {
                           error_log("Error merging cells {$mergeRange}: " . $e->getMessage());
                           // Optionally, handle the error, e.g., skip merging for this row
                      }
                 } else {
                     error_log("Warning: Invalid merge range format calculated: {$mergeRange} for row {$rowNum}. Skipping merge.");
                 }
             } elseif ($merge !== null) {
                 // Log if merge format is unexpected (e.g., single column 'L' in calc_note)
                 // error_log("Notice: Merge value '{$merge}' for row {$rowNum} is not a range (e.g., 'A:E'). No merge performed by default.");
             }


             // Apply Basic Style to Label Cell (A or merged range)
             $styleArray = [];
             if (!empty($style['bold'])) $styleArray['font']['bold'] = true;
             if (!empty($style['italic'])) $styleArray['font']['italic'] = true;
             if (!empty($style['bg'])) $styleArray['fill'] = ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $style['bg']]];

             $applyTarget = 'A' . $rowNum; // Default target is column A
              if ($merge !== null && is_string($merge) && strpos($merge, ':') !== false) {
                   $mergeRange = $merge . (string)$rowNum;
                   if (preg_match('/^[A-Z]+[1-9][0-9]*:[A-Z]+[1-9][0-9]*$/i', $mergeRange)) {
                        $applyTarget = $mergeRange; // Target is the merged range
                   }
              } elseif ($type === 'calc_note' && $merge === 'L') {
                   $applyTarget = $notesColLetter . $rowNum; // Target is the notes column
              }

             if (!empty($styleArray)) {
                   try {
                        $sheet->getStyle($applyTarget)->applyFromArray($styleArray);
                         // Center align specific merged headers
                         if (($type === 'category_header' || $type === 'col_header_group') && $applyTarget !== 'A' . $rowNum) {
                              $sheet->getStyle($applyTarget)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                         }
                   } catch (\PhpOffice\PhpSpreadsheet\Exception $e) {
                        error_log("Error applying style to {$applyTarget}: " . $e->getMessage());
                   }
             }


             // Populate Yearly Data and Totals for 'item' and 'fringe_rate_item' rows
             if ($type === 'item' || $type === 'fringe_rate_item') {
                 $colIndex = 6; // Start Year 1 in Column F
                 $rowSumFormulaParts = [];
                 foreach ($years as $year) {
                      $cellCoord = Coordinate::stringFromColumnIndex($colIndex) . $rowNum;
                      $value = $processedItems[$year][$rowNum] ?? 0;
                      $sheet->setCellValue($cellCoord, $value);
                      $sheet->getStyle($cellCoord)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_CURRENCY_USD_SIMPLE); // Basic $ format
                      $rowSumFormulaParts[] = $cellCoord;
                      $colIndex++;
                 }
                 // Create SUM formula only if there are parts to sum
                 if (!empty($rowSumFormulaParts)) {
                     $rowSumFormula = '=SUM(' . implode(',', $rowSumFormulaParts) . ')';
                     $sheet->setCellValue($totalColLetter . $rowNum, $rowSumFormula);
                     $sheet->getStyle($totalColLetter . $rowNum)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_CURRENCY_USD_SIMPLE);
                     $sheet->getStyle($totalColLetter . $rowNum)->getFont()->setBold(true); // Bold totals
                 } else {
                      $sheet->setCellValue($totalColLetter . $rowNum, 0); // Set total to 0 if no years
                      $sheet->getStyle($totalColLetter . $rowNum)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_CURRENCY_USD_SIMPLE);
                      $sheet->getStyle($totalColLetter . $rowNum)->getFont()->setBold(true);
                 }

             }

             // Populate Fringe Rate %
             if ($type === 'fringe_rate_item') {
                  $rate = 0;
                  if ($rowNum == 16) $rate = $fringeRates['Faculty'];
                  elseif ($rowNum == 17) $rate = $fringeRates['UI proffesional staff & Post Docs'];
                  elseif ($rowNum == 18) $rate = $fringeRates['GRAs / UGrads'];
                  elseif ($rowNum == 19) $rate = $fringeRates['Temp Help'];
                  $sheet->setCellValue('E' . $rowNum, $rate);
                  $sheet->getStyle('E' . $rowNum)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_PERCENTAGE_00);
             }
        }

        // --- 6. Add Calculation Formulas ---
        // ** FIX: Define direct cost rows based on the FINAL layout structure, including placeholders **
         $directCostRows = array_merge(
             range(7, 12),  // Personnel
             range(16, 19), // Fringe
             [22],          // Equipment Placeholder
             range(25, 26), // Travel
             [29],          // Participant Support Placeholder
             range(32, 40), // Other Direct Costs
             [43]           // Subaward Placeholder
         );
         $yearStartCol = 6; // F

        // Row 49: Total Direct Cost
         for ($i = 0; $i < $maxYears; $i++) {
             $colLetter = Coordinate::stringFromColumnIndex($yearStartCol + $i);
             $sumFormulaParts = [];
              // Ensure row exists in layout before adding to sum
             foreach ($directCostRows as $r) {
                  if (isset($layout[$r])) $sumFormulaParts[] = $colLetter . $r;
             }
             if(!empty($sumFormulaParts)) {
                 $sumFormula = '=SUM(' . implode(',', $sumFormulaParts) . ')';
                 $sheet->setCellValue($colLetter . '49', $sumFormula);
             } else {
                  $sheet->setCellValue($colLetter . '49', 0);
             }
         }
         $sheet->setCellValue($totalColLetter . '49', "=SUM(F49:" . $lastYearColLetter . "49)");

        // Row 51: Back out GSA T&F (Tuition) - Row 40
         for ($i = 0; $i < $maxYears; $i++) {
             $colLetter = Coordinate::stringFromColumnIndex($yearStartCol + $i);
             $sheet->setCellValue($colLetter . '51', "=" . $colLetter . "40");
         }
         $sheet->setCellValue($totalColLetter . '51', "=SUM(F51:" . $lastYearColLetter . "51)");

        // Row 52: Back out capital EQ - Row 22
         for ($i = 0; $i < $maxYears; $i++) {
             $colLetter = Coordinate::stringFromColumnIndex($yearStartCol + $i);
             $sheet->setCellValue($colLetter . '52', "=" . $colLetter . "22");
         }
         $sheet->setCellValue($totalColLetter . '52', "=SUM(F52:" . $lastYearColLetter . "52)");

         // Row 53: Back out subawards totals - Row 43
         for ($i = 0; $i < $maxYears; $i++) {
             $colLetter = Coordinate::stringFromColumnIndex($yearStartCol + $i);
             $sheet->setCellValue($colLetter . '53', "=" . $colLetter . "43");
         }
          $sheet->setCellValue($totalColLetter . '53', "=SUM(F53:" . $lastYearColLetter . "53)");

          // Row 54: Sub award 1st $25k (PHP calculated value)
         for ($i = 0; $i < $maxYears; $i++) {
             $colLetter = Coordinate::stringFromColumnIndex($yearStartCol + $i);
             $sheet->setCellValue($colLetter . '54', $subawardFirst25k[$years[$i]] ?? 0);
         }
          $sheet->setCellValue($totalColLetter . '54', "=SUM(F54:" . $lastYearColLetter . "54)");


        // Row 56: Modified Total Direct Costs
         for ($i = 0; $i < $maxYears; $i++) {
             $colLetter = Coordinate::stringFromColumnIndex($yearStartCol + $i);
              // Formula: Total Direct (49) - Tuition (51) - Equipment (52) - Total Subawards(53) + First 25k Subawards (54)
              // Also subtract Participant Support (Row 29) as it's typically excluded from MTDC
             $sheet->setCellValue($colLetter . '56', "=" . $colLetter . "49-" . $colLetter . "51-" . $colLetter . "52-" . $colLetter . "53+" . $colLetter . "54-" . $colLetter . "29");
         }
         $sheet->setCellValue($totalColLetter . '56', "=SUM(F56:" . $lastYearColLetter . "56)");

        // Row 57: Indirect Costs
         $sheet->setCellValue('E57', $indirectRate); // Show rate in Col E
         $sheet->getStyle('E57')->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_PERCENTAGE_00);
         for ($i = 0; $i < $maxYears; $i++) {
             $colLetter = Coordinate::stringFromColumnIndex($yearStartCol + $i);
             $sheet->setCellValue($colLetter . '57', "=" . $colLetter . "56*E57"); // MTDC * Rate
         }
         $sheet->setCellValue($totalColLetter . '57', "=SUM(F57:" . $lastYearColLetter . "57)");

        // Row 58: Total Project Cost
         for ($i = 0; $i < $maxYears; $i++) {
             $colLetter = Coordinate::stringFromColumnIndex($yearStartCol + $i);
             $sheet->setCellValue($colLetter . '58', "=" . $colLetter . "49+" . $colLetter . "57"); // Total Direct + Indirect
         }
         $sheet->setCellValue($totalColLetter . '58', "=SUM(F58:" . $lastYearColLetter . "58)");


        // --- 7. Apply Final Styling & Formatting ---

         // Borders for the main table area (adjust range as needed)
         $maxRow = 58; // Last row of data/calculations
         $sheet->getStyle('A6:' . $notesColLetter . $maxRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
         $sheet->getStyle('A6:' . $notesColLetter . '6')->getBorders()->getBottom()->setBorderStyle(Border::BORDER_MEDIUM); // Thicker border under headers

         // Number formatting for calculation rows
         foreach (range(49, 58) as $calcRow) {
              if($calcRow == 55) continue; // Skip note row
              $sheet->getStyle('F' . $calcRow . ':' . $totalColLetter . $calcRow)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_CURRENCY_USD_SIMPLE);
              // Bold totals column for calculations
              $sheet->getStyle($totalColLetter . $calcRow)->getFont()->setBold(true);
         }

         // Specific formatting from image
         $sheet->getStyle('A1:E4')->getFont()->setBold(true); // Header labels bold
         $sheet->getStyle('A6:L6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER); // Center headers
         $sheet->getStyle('A6:L6')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('D9D9D9'); // Header BG grey
         // ** FIX: Apply white BG to the correct merged range A5:L5 **
         $sheet->getStyle('A5:L5')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFFFFF');


         // Apply background colors to category headers based on $layout array
         foreach ($layout as $rowNum => $rowData) {
            if (!is_array($rowData) || count($rowData) < 4) continue; // Skip invalid layout data
            list($label, $type, $style, $merge) = $rowData;
             if (isset($style['bg']) && $merge !== null && is_string($merge) && strpos($merge, ':') !== false) {
                  $mergeRange = $merge . (string)$rowNum;
                  if (preg_match('/^[A-Z]+[1-9][0-9]*:[A-Z]+[1-9][0-9]*$/i', $mergeRange)) {
                      try {
                           $sheet->getStyle($mergeRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($style['bg']);
                      } catch (\PhpOffice\PhpSpreadsheet\Exception $e) {
                          error_log("Error applying background to {$mergeRange}: " . $e->getMessage());
                      }
                  }
             }
         }
         // Specific background for Total Project Cost row A58:K58 (Adjust if notes column shouldn't have BG)
         $sheet->getStyle('A58:' . $totalColLetter . '58')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('D0CECE');


         // Align amounts to the right
         $sheet->getStyle('F7:' . $totalColLetter . $maxRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
         // Align fringe % center
         $sheet->getStyle('E16:E19')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
         // Align indirect % center
         $sheet->getStyle('E57')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // --- 8. Set Column Widths (Approximate) ---
        $sheet->getColumnDimension('A')->setWidth(35); // Category/Item Name
        $sheet->getColumnDimension('B')->setWidth(10); // Y1 Hours
        $sheet->getColumnDimension('C')->setWidth(10); // Avg rate
        $sheet->getColumnDimension('D')->setWidth(1);  // Spacer
        $sheet->getColumnDimension('E')->setWidth(10); // Fringe Rate % / IDC %
        $yearColWidth = 15;
        for ($i = 0; $i < $maxYears; $i++) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($yearStartCol + $i))->setWidth($yearColWidth);
        }
        $sheet->getColumnDimension($totalColLetter)->setWidth($yearColWidth + 2); // Total slightly wider
        $sheet->getColumnDimension($notesColLetter)->setWidth(40); // Notes

        // --- 9. Save File ---
        $writer = new Xlsx($spreadsheet);
        $filename = preg_replace('/[^a-z0-9]+/', '-', strtolower($project->title)) . '-ui-budget.xlsx';
        $tempDir = '../temp';

        // Create temp directory if it doesn't exist
        if (!file_exists($tempDir)) {
            if (!mkdir($tempDir, 0777, true) && !is_dir($tempDir)) {
                 throw new RuntimeException(sprintf('Directory "%s" was not created', $tempDir));
             }
        }
        $filepath = $tempDir . '/' . $filename;

        try {
             $writer->save($filepath);
        } catch (\PhpOffice\PhpSpreadsheet\Writer\Exception $e) {
             // Log the error
             error_log("Error saving spreadsheet: " . $e->getMessage());
             throw new Exception("Error creating the Excel file. Please check server logs.");
        }

        // Check if file was actually created
        if (!file_exists($filepath)) {
             throw new Exception("Failed to save the Excel file to the temporary directory.");
        }


        return $filename; // Return filename for download link
    }
}
?>