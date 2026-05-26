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
    // DEBUG: loads a simple known-solvable 8×8 puzzle and reloads.
    // The puzzle uses a 4×4 white block in the top-left corner and black
    // filler cells everywhere else so the rendered board is a full 8×8 grid.
    function loadDebugPuzzle() {
        const puzzle = buildDebugPuzzle();
        sessionStorage.setItem('kakuro_puzzle', JSON.stringify(puzzle));
        location.reload();
    }

    function buildDebugPuzzle() {
        const rows = 8;
        const cols = 8;
        const layout = [
            ['black', 'clue', 'clue', 'black', 'black', 'black', 'black', 'black'],
            ['clue',  'white', 'white', 'black', 'black', 'black', 'black', 'black'],
            ['clue',  'white', 'white', 'black', 'black', 'black', 'black', 'black'],
            ['black', 'clue', 'clue', 'clue', 'clue', 'black', 'black', 'black'],
            ['clue',  'white', 'white', 'white', 'white', 'black', 'black', 'black'],
            ['clue',  'white', 'white', 'white', 'white', 'black', 'black', 'black'],
            ['clue',  'white', 'white', 'white', 'white', 'black', 'black', 'black'],
            ['clue',  'white', 'white', 'white', 'white', 'black', 'black', 'black'],
            ['black', 'black', 'black', 'black', 'black', 'black', 'black', 'black'],
            ['black', 'black', 'black', 'black', 'black', 'black', 'black', 'black'],
            ['black', 'black', 'black', 'black', 'black', 'black', 'black', 'black'],
            ['black', 'black', 'black', 'black', 'black', 'black', 'black', 'black'],
        ];

            '0,1': { clueRight: null, clueDown: 10 },
            '0,2': { clueRight: null, clueDown: 10 },
            '0,3': { clueRight: null, clueDown: 10 },
            '0,4': { clueRight: null, clueDown: 10 },
            '1,0': { clueRight: 10, clueDown: null },
            '2,0': { clueRight: 10, clueDown: null },
            '3,0': { clueRight: 10, clueDown: null },
            '4,0': { clueRight: 10, clueDown: null },
            ['clue',  'white', 'white', 'white', 'black', 'black', 'black', 'black'],
            ['clue',  'white', 'white', 'white', 'black', 'black', 'black', 'black'],
            ['clue',  'white', 'white', 'white', 'black', 'black', 'black', 'black'],

        const cells = [];
        for (let row = 0; row < rows; row++) {
            for (let col = 0; col < cols; col++) {
                const type = layout[row][col];

                if (type === 'clue') {
            '0,1': { clueRight: null, clueDown: 6 },
            '0,2': { clueRight: null, clueDown: 6 },
            '0,3': { clueRight: null, clueDown: 6 },
            '1,0': { clueRight: 6, clueDown: null },
            '2,0': { clueRight: 6, clueDown: null },
            '3,0': { clueRight: 6, clueDown: null },
            }
        }

        return { rows, cols, cells };
    }
    </script>
    <div style="position:fixed;bottom:12px;right:12px">
        <button onclick="loadDebugPuzzle()" style="background:#c84;color:#fff;border:none;padding:6px 12px;border-radius:4px;cursor:pointer;font-size:0.8rem">
            Debug: load 8×8 puzzle
        </button>
    </div>
</body>
</html>
