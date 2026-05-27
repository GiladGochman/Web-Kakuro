<?php

require_once __DIR__ . '/src/CombinationTable.php';
require_once __DIR__ . '/src/Board.php';
require_once __DIR__ . '/src/Solver.php';

/**
 * Diagnostic test for debug puzzle 2 solver issue
 */

// Debug puzzle 2 data
$puzzle = [
    'rows' => 9,
    'cols' => 9,
    'cells' => [
        // Row 0
        ['row' => 0, 'col' => 0, 'type' => 'black'],
        ['row' => 0, 'col' => 1, 'type' => 'clue', 'clueRight' => null, 'clueDown' => 6],
        ['row' => 0, 'col' => 2, 'type' => 'clue', 'clueRight' => null, 'clueDown' => 7],
        ['row' => 0, 'col' => 3, 'type' => 'black'],
        ['row' => 0, 'col' => 4, 'type' => 'black'],
        ['row' => 0, 'col' => 5, 'type' => 'black'],
        ['row' => 0, 'col' => 6, 'type' => 'black'],
        ['row' => 0, 'col' => 7, 'type' => 'black'],
        ['row' => 0, 'col' => 8, 'type' => 'black'],
        // Row 1
        ['row' => 1, 'col' => 0, 'type' => 'clue', 'clueRight' => 5, 'clueDown' => null],
        ['row' => 1, 'col' => 1, 'type' => 'white'],
        ['row' => 1, 'col' => 2, 'type' => 'white'],
        ['row' => 1, 'col' => 3, 'type' => 'black'],
        ['row' => 1, 'col' => 4, 'type' => 'black'],
        ['row' => 1, 'col' => 5, 'type' => 'black'],
        ['row' => 1, 'col' => 6, 'type' => 'black'],
        ['row' => 1, 'col' => 7, 'type' => 'black'],
        ['row' => 1, 'col' => 8, 'type' => 'black'],
        // Row 2
        ['row' => 2, 'col' => 0, 'type' => 'clue', 'clueRight' => 8, 'clueDown' => null],
        ['row' => 2, 'col' => 1, 'type' => 'white'],
        ['row' => 2, 'col' => 2, 'type' => 'white'],
        ['row' => 2, 'col' => 3, 'type' => 'black'],
        ['row' => 2, 'col' => 4, 'type' => 'black'],
        ['row' => 2, 'col' => 5, 'type' => 'black'],
        ['row' => 2, 'col' => 6, 'type' => 'black'],
        ['row' => 2, 'col' => 7, 'type' => 'black'],
        ['row' => 2, 'col' => 8, 'type' => 'black'],
        // Rows 3-8: all black
        ...array_merge(
            ...array_map(fn($r) => array_map(fn($c) => ['row' => $r, 'col' => $c, 'type' => 'black'], range(0, 8)), range(3, 8))
        )
    ]
];

echo "=== DEBUG PUZZLE 2 DIAGNOSTIC TEST ===\n\n";

// Step 1: Build the board
echo "STEP 1: Building Board...\n";
try {
    $combTable = new CombinationTable();
    $board = Board::fromJson($puzzle, $combTable);
    echo "✓ Board created successfully\n\n";
} catch (InvalidArgumentException $e) {
    echo "✗ Board creation failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Step 2: Print board structure
echo "STEP 2: Board Structure\n";
echo "Dimensions: " . $board->getRows() . " × " . $board->getCols() . "\n";
$runs = $board->getRuns();
echo "Runs created: " . count($runs) . "\n\n";

foreach ($runs as $i => $run) {
    echo "Run " . ($i + 1) . ":\n";
    echo "  Sum: " . $run->sum . "\n";
    echo "  Cells: ";
    foreach ($run->getCells() as $cell) {
        echo "({$cell['row']},{$cell['col']}) ";
    }
    echo "\n";
    echo "  Combinations: " . count($run->getCombinations()) . "\n";
    foreach ($run->getCombinations() as $combo) {
        echo "    [" . implode(', ', $combo) . "]\n";
    }
    echo "\n";
}

// Step 3: Print initial candidates
echo "STEP 3: Initial Candidates\n";
$whiteFound = false;
for ($r = 0; $r < $board->getRows(); $r++) {
    for ($c = 0; $c < $board->getCols(); $c++) {
        if ($board->getCellType($r, $c) === 'white') {
            $whiteFound = true;
            $candidates = $board->getCandidates($r, $c);
            echo "Cell ({$r},{$c}): [" . implode(', ', $candidates) . "]\n";
        }
    }
}
if (!$whiteFound) {
    echo "✗ No white cells found!\n";
}
echo "\n";

// Step 4: Run the solver
echo "STEP 4: Running Solver\n";
$solver = new Solver($board);

$assignmentCount = 0;
$solver->setOnAssignCallback(function(int $row, int $col, int $digit) use (&$assignmentCount) {
    $assignmentCount++;
    echo "  Assignment #{$assignmentCount}: ({$row},{$col}) = {$digit}\n";
});

echo "Starting solve...\n";
$result = $solver->solve();

if ($result === null) {
    echo "\n✗ SOLVER DECLARED UNSOLVABLE\n";
    echo "Total assignments made: {$assignmentCount}\n";
    echo "\nThis is the bug! The puzzle is solvable: (1,1)=1, (1,2)=4, (2,1)=5, (2,2)=3\n";
} else {
    echo "\n✓ SOLVER FOUND SOLUTION\n";
    echo "Total assignments made: {$assignmentCount}\n";
    $grid = $result->toSolutionGrid();
    echo "\nSolution grid:\n";
    for ($r = 0; $r < 3; $r++) {
        for ($c = 0; $c < 3; $c++) {
            if ($board->getCellType($r, $c) === 'white') {
                echo $grid[$r][$c] . " ";
            } else {
                echo ". ";
            }
        }
        echo "\n";
    }
}

echo "\n=== END OF TEST ===\n";
