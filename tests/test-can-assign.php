<?php

require_once __DIR__ . '/src/Run.php';

// Test the canAssignCombo logic
$run = new Run(8, [['row' => 2, 'col' => 1], ['row' => 2, 'col' => 2]],
               [[1, 7], [2, 6], [3, 5]]);

$testCases = [
    (object)['name' => '[1,7] with full candidates', 'combo' => [1,7], 'cellCands' => [[1,2,5,7], [1,2,6,3,5,7]], 'expected' => true],
    (object)['name' => '[1,7] with missing 7', 'combo' => [1,7], 'cellCands' => [[1,2,5], [1,2,6,3,5]], 'expected' => false],
    (object)['name' => '[2,6] with full candidates', 'combo' => [2,6], 'cellCands' => [[1,2,5], [1,2,6,3,5]], 'expected' => true],
    (object)['name' => '[3,5] with full candidates', 'combo' => [3,5], 'cellCands' => [[1,2,5], [1,2,6,3,5]], 'expected' => true],
    (object)['name' => '[3,5] with 5 and 3 only in different positions', 'combo' => [3,5], 'cellCands' => [[1,2,5], [6,3,5]], 'expected' => true],
    (object)['name' => '[3,5] with both in one cell only', 'combo' => [3,5], 'cellCands' => [[1,2], [3,5,6]], 'expected' => false],
];

echo "Testing canAssignCombo() with reflection:\n\n";

$reflection = new ReflectionClass('Run');
$canAssignCombo = $reflection->getMethod('canAssignCombo');

foreach ($testCases as $test) {
    $result = $canAssignCombo->invoke($run, $test->combo, $test->cellCands);
    $status = ($result === $test->expected) ? '✓' : '✗';
    echo "{$status} {$test->name}\n";
    echo "   Combo: [" . implode(',', $test->combo) . "]\n";
    echo "   Cell 0 cands: [" . implode(',', $test->cellCands[0]) . "]\n";
    echo "   Cell 1 cands: [" . implode(',', $test->cellCands[1]) . "]\n";
    echo "   Expected: " . ($test->expected ? 'true' : 'false') . ", Got: " . ($result ? 'true' : 'false') . "\n\n";
}
