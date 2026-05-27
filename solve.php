<?php

/**
 * Kakuro solver API endpoint — streams real-time updates.
 *
 * Uses Server-Sent Events (SSE) to emit each cell assignment as it happens,
 * allowing the frontend to render the solution in real-time.
 *
 * Events:
 *   "assign" { "row": int, "col": int, "digit": int }
 *   "solved" or "error" { "message": string }
 */

require_once __DIR__ . '/src/CombinationTable.php';
require_once __DIR__ . '/src/Run.php';
require_once __DIR__ . '/src/Board.php';
require_once __DIR__ . '/src/Solver.php';

// Set headers for Server-Sent Events
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no'); // Disable nginx buffering
flush();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    sendEvent('error', ['message' => 'Method not allowed.']);
    exit;
}

$body = file_get_contents('php://input');
$data = json_decode($body, associative: true);

if ($data === null) {
    http_response_code(400);
    sendEvent('error', ['message' => 'Invalid JSON body.']);
    exit;
}

try {
    $combTable = new CombinationTable();
    $board     = Board::fromJson($data, $combTable);
    $solver    = new Solver($board);

    // Set callback to emit events on each cell assignment
    $solver->setOnAssignCallback(function(int $row, int $col, int $digit) {
        sendEvent('assign', compact('row', 'col', 'digit'));
    });

    $result = $solver->solve();

    if ($result === null) {
        sendEvent('solved', ['status' => 'unsolvable', 'solution' => null]);
    } else {
        sendEvent('solved', ['status' => 'solved', 'solution' => $result->toSolutionGrid()]);
    }
} catch (InvalidArgumentException $e) {
    http_response_code(422);
    sendEvent('error', ['message' => $e->getMessage()]);
} catch (Throwable $e) {
    http_response_code(500);
    sendEvent('error', ['message' => 'Internal server error: ' . $e->getMessage()]);
}

// ============================================================================

/**
 * Sends an SSE event with the given type and data.
 */
function sendEvent(string $event, array $data): void
{
    echo "event: $event\n";
    echo 'data: ' . json_encode($data) . "\n\n";
    flush();
    ob_flush();
}
