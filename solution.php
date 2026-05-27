<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kakuro Solver — Solution</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="page page--solution">

    <div class="page-header">
        <h1>Kakuro Solver</h1>
        <p class="step-label">Step 3 of 3 — Solution</p>
    </div>

    <div id="status-area">
        <div class="spinner" id="spinner" aria-label="Solving…">
            <div class="spinner__ring"></div>
            <p>Solving…</p>
        </div>
    </div>

    <div class="grid-wrapper" id="grid-wrapper" hidden>
        <table class="kakuro-grid" id="grid"></table>
    </div>

    <div class="actions" id="actions" hidden>
        <button class="btn btn--secondary" id="back-btn">&larr; Edit Clues</button>
        <button class="btn btn--primary"   id="new-btn">New Puzzle</button>
    </div>

    <script src="js/solution.js"></script>
    <script>
    function loadDebugPuzzle() {
        const puzzle = buildDebugPuzzle();
        sessionStorage.setItem('kakuro_puzzle', JSON.stringify(puzzle));
        location.reload();
    }

    function loadDebugPuzzle2() {
        const puzzle = buildDebugPuzzle2();
        sessionStorage.setItem('kakuro_puzzle', JSON.stringify(puzzle));
        location.reload();
    }

    function buildDebugPuzzle() {
        return {
            rows: 8,
            cols: 8,
            cells: [
                { row: 0, col: 0, type: "black" },
                { row: 0, col: 1, type: "clue", clueRight: null, clueDown: 6 },
                { row: 0, col: 2, type: "clue", clueRight: null, clueDown: 7 },
                { row: 0, col: 3, type: "clue", clueRight: null, clueDown: 9 },
                { row: 0, col: 4, type: "black" },
                { row: 0, col: 5, type: "black" },
                { row: 0, col: 6, type: "black" },
                { row: 0, col: 7, type: "black" },
                { row: 1, col: 0, type: "clue", clueRight: 6, clueDown: null },
                { row: 1, col: 1, type: "white" },
                { row: 1, col: 2, type: "white" },
                { row: 1, col: 3, type: "white" },
                { row: 1, col: 4, type: "black" },
                { row: 1, col: 5, type: "black" },
                { row: 1, col: 6, type: "black" },
                { row: 1, col: 7, type: "black" },
                { row: 2, col: 0, type: "clue", clueRight: 7, clueDown: null },
                { row: 2, col: 1, type: "white" },
                { row: 2, col: 2, type: "white" },
                { row: 2, col: 3, type: "white" },
                { row: 2, col: 4, type: "black" },
                { row: 2, col: 5, type: "black" },
                { row: 2, col: 6, type: "black" },
                { row: 2, col: 7, type: "black" },
                { row: 3, col: 0, type: "clue", clueRight: 9, clueDown: null },
                { row: 3, col: 1, type: "white" },
                { row: 3, col: 2, type: "white" },
                { row: 3, col: 3, type: "white" },
                { row: 3, col: 4, type: "black" },
                { row: 3, col: 5, type: "black" },
                { row: 3, col: 6, type: "black" },
                { row: 3, col: 7, type: "black" },
                { row: 4, col: 0, type: "black" },
                { row: 4, col: 1, type: "black" },
                { row: 4, col: 2, type: "black" },
                { row: 4, col: 3, type: "black" },
                { row: 4, col: 4, type: "black" },
                { row: 4, col: 5, type: "black" },
                { row: 4, col: 6, type: "black" },
                { row: 4, col: 7, type: "black" },
                { row: 5, col: 0, type: "black" },
                { row: 5, col: 1, type: "black" },
                { row: 5, col: 2, type: "black" },
                { row: 5, col: 3, type: "black" },
                { row: 5, col: 4, type: "black" },
                { row: 5, col: 5, type: "black" },
                { row: 5, col: 6, type: "black" },
                { row: 5, col: 7, type: "black" },
                { row: 6, col: 0, type: "black" },
                { row: 6, col: 1, type: "black" },
                { row: 6, col: 2, type: "black" },
                { row: 6, col: 3, type: "black" },
                { row: 6, col: 4, type: "black" },
                { row: 6, col: 5, type: "black" },
                { row: 6, col: 6, type: "black" },
                { row: 6, col: 7, type: "black" },
                { row: 7, col: 0, type: "black" },
                { row: 7, col: 1, type: "black" },
                { row: 7, col: 2, type: "black" },
                { row: 7, col: 3, type: "black" },
                { row: 7, col: 4, type: "black" },
                { row: 7, col: 5, type: "black" },
                { row: 7, col: 6, type: "black" },
                { row: 7, col: 7, type: "black" }
            ]
        };
    }

    function buildDebugPuzzle2() {
        return {
            rows: 9,
            cols: 9,
            cells: [
                { row: 0, col: 0, type: "black" },
                { row: 0, col: 1, type: "clue", clueRight: null, clueDown: 6 },
                { row: 0, col: 2, type: "clue", clueRight: null, clueDown: 7 },
                { row: 0, col: 3, type: "black" },
                { row: 0, col: 4, type: "black" },
                { row: 0, col: 5, type: "black" },
                { row: 0, col: 6, type: "black" },
                { row: 0, col: 7, type: "black" },
                { row: 0, col: 8, type: "black" },
                { row: 1, col: 0, type: "clue", clueRight: 6, clueDown: null },
                { row: 1, col: 1, type: "white" },
                { row: 1, col: 2, type: "white" },
                { row: 1, col: 3, type: "black" },
                { row: 1, col: 4, type: "black" },
                { row: 1, col: 5, type: "black" },
                { row: 1, col: 6, type: "black" },
                { row: 1, col: 7, type: "black" },
                { row: 1, col: 8, type: "black" },
                { row: 2, col: 0, type: "clue", clueRight: 7, clueDown: null },
                { row: 2, col: 1, type: "white" },
                { row: 2, col: 2, type: "white" },
                { row: 2, col: 3, type: "black" },
                { row: 2, col: 4, type: "black" },
                { row: 2, col: 5, type: "black" },
                { row: 2, col: 6, type: "black" },
                { row: 2, col: 7, type: "black" },
                { row: 2, col: 8, type: "black" },
                { row: 3, col: 0, type: "black" },
                { row: 3, col: 1, type: "black" },
                { row: 3, col: 2, type: "black" },
                { row: 3, col: 3, type: "black" },
                { row: 3, col: 4, type: "black" },
                { row: 3, col: 5, type: "black" },
                { row: 3, col: 6, type: "black" },
                { row: 3, col: 7, type: "black" },
                { row: 3, col: 8, type: "black" },
                { row: 4, col: 0, type: "black" },
                { row: 4, col: 1, type: "black" },
                { row: 4, col: 2, type: "black" },
                { row: 4, col: 3, type: "black" },
                { row: 4, col: 4, type: "black" },
                { row: 4, col: 5, type: "black" },
                { row: 4, col: 6, type: "black" },
                { row: 4, col: 7, type: "black" },
                { row: 4, col: 8, type: "black" },
                { row: 5, col: 0, type: "black" },
                { row: 5, col: 1, type: "black" },
                { row: 5, col: 2, type: "black" },
                { row: 5, col: 3, type: "black" },
                { row: 5, col: 4, type: "black" },
                { row: 5, col: 5, type: "black" },
                { row: 5, col: 6, type: "black" },
                { row: 5, col: 7, type: "black" },
                { row: 5, col: 8, type: "black" },
                { row: 6, col: 0, type: "black" },
                { row: 6, col: 1, type: "black" },
                { row: 6, col: 2, type: "black" },
                { row: 6, col: 3, type: "black" },
                { row: 6, col: 4, type: "black" },
                { row: 6, col: 5, type: "black" },
                { row: 6, col: 6, type: "black" },
                { row: 6, col: 7, type: "black" },
                { row: 6, col: 8, type: "black" },
                { row: 7, col: 0, type: "black" },
                { row: 7, col: 1, type: "black" },
                { row: 7, col: 2, type: "black" },
                { row: 7, col: 3, type: "black" },
                { row: 7, col: 4, type: "black" },
                { row: 7, col: 5, type: "black" },
                { row: 7, col: 6, type: "black" },
                { row: 7, col: 7, type: "black" },
                { row: 7, col: 8, type: "black" },
                { row: 8, col: 0, type: "black" },
                { row: 8, col: 1, type: "black" },
                { row: 8, col: 2, type: "black" },
                { row: 8, col: 3, type: "black" },
                { row: 8, col: 4, type: "black" },
                { row: 8, col: 5, type: "black" },
                { row: 8, col: 6, type: "black" },
                { row: 8, col: 7, type: "black" },
                { row: 8, col: 8, type: "black" }
            ]
        };
    }
    </script>
    <div style="position:fixed;bottom:12px;right:12px;display:flex;gap:8px;flex-direction:column;align-items:flex-end">
        <button onclick="loadDebugPuzzle()" style="background:#c84;color:#fff;border:none;padding:6px 12px;border-radius:4px;cursor:pointer;font-size:0.8rem">
            Debug: Puzzle 1
        </button>
        <button onclick="loadDebugPuzzle2()" style="background:#884;color:#fff;border:none;padding:6px 12px;border-radius:4px;cursor:pointer;font-size:0.8rem">
            Debug: Puzzle 2
        </button>
    </div>
</body>
</html>
