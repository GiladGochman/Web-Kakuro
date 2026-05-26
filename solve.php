<?php

/**
 * Kakuro solver API endpoint.
 *
 * Accepts a POST request with a JSON body describing the puzzle, runs the
 * solver, and returns a JSON response.
 *
 * Request body shape: see Board::fromJson() in src/Board.php
 * Response shape:
 *   { "status": "solved",      "solution": [[int|null, ...], ...] }
 *   { "status": "unsolvable",  "message": "..." }
 *   { "status": "error",       "message": "..." }
 */

require_once __DIR__ . '/src/CombinationTable.php';
require_once __DIR__ . '/src/Run.php';
require_once __DIR__ . '/src/Board.php';
require_once __DIR__ . '/src/Solver.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed.']);
    exit;
}

$body = file_get_contents('php://input');
$data = json_decode($body, associative: true);

if ($data === null) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON body.']);
    exit;
}

try {
    $combTable = new CombinationTable();
    $board     = Board::fromJson($data, $combTable);
    $solver    = new Solver($board);
    $result    = $solver->solve();

    if ($result === null) {
        echo json_encode(['status' => 'unsolvable', 'message' => 'No solution exists for this puzzle.']);
    } else {
        echo json_encode(['status' => 'solved', 'solution' => $result->toSolutionGrid()]);
    }
} catch (InvalidArgumentException $e) {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Internal server error: ' . $e->getMessage()]);
}
