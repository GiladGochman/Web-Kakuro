<?php

require_once __DIR__ . '/src/CombinationTable.php';
require_once __DIR__ . '/src/Board.php';
require_once __DIR__ . '/src/Run.php';

// Test Run 2 after the assignment
$run2 = new Run(8, [['row' => 2, 'col' => 1], ['row' => 2, 'col' => 2]],
                [[1, 7], [2, 6], [3, 5]]);

echo "Run 2 (horizontal, sum=8) BEFORE any assignment:\n";
echo "Cell candidates:\n";
echo "  Cell 0 (2,1): [1, 2, 5]\n";
echo "  Cell 1 (2,2): [1, 2, 6, 3, 5]\n";

$cellCandidates1 = [[1, 2, 5], [1, 2, 6, 3, 5]];
echo "\nCombos:\n";
$reflection = new ReflectionClass('Run');
$canAssignCombo = $reflection->getMethod('canAssignCombo');

foreach ($run2->getCombinations() as $combo) {
    $isValid = $canAssignCombo->invoke($run2, $combo, $cellCandidates1);
    echo "  [" . implode(", ", $combo) . "]: " . ($isValid ? "VALID" : "INVALID") . "\n";
}

// Now test after (1,1)=1, which means (2,1) loses 1 from candidates
echo "\n\nRun 2 AFTER (1,1)=1 assignment:\n";
echo "Cell candidates after removing 1 from (2,1):\n";
echo "  Cell 0 (2,1): [2, 5]\n";
echo "  Cell 1 (2,2): [1, 2, 6, 3, 5]\n";

$cellCandidates2 = [[2, 5], [1, 2, 6, 3, 5]];
echo "\nCombos:\n";

foreach ($run2->getCombinations() as $combo) {
    $isValid = $canAssignCombo->invoke($run2, $combo, $cellCandidates2);
    echo "  [" . implode(", ", $combo) . "]: " . ($isValid ? "VALID" : "INVALID") . "\n";
    if ($combo === [2, 6]) {
        echo "    Manual check: 2→cell0 [2,5]? YES, 6→cell1 [1,2,6,3,5]? YES ✓\n";
    } elseif ($combo === [3, 5]) {
        echo "    Manual check: 3→cell0 [2,5]? NO, 3→cell1? YES, 5→cell0? YES ✓\n";
    }
}

// Check what happens after pruning Run 2
echo "\n\nAfter pruning with narrowed candidates, Run 2 should still have both [2,6] and [3,5].\n";
