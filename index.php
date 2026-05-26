<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kakuro Solver — Step 1: Draw the Grid</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="page page--grid-editor">

    <div class="page-header">
        <h1>Kakuro Solver</h1>
        <p class="step-label">Step 1 of 3 — Draw the grid</p>
    </div>

    <div class="instructions">
        <p>Click each cell to cycle through its type:</p>
        <ul>
            <li><span class="cell-demo cell-demo--white"></span> White — a cell to be filled with a digit</li>
            <li><span class="cell-demo cell-demo--clue"></span> Clue — a diagonal cell holding clue numbers</li>
            <li><span class="cell-demo cell-demo--black"></span> Black — a blocked cell</li>
        </ul>
    </div>

    <div class="grid-wrapper">
        <table class="kakuro-grid" id="grid">
            <?php for ($row = 0; $row < 9; $row++): ?>
            <tr>
                <?php for ($col = 0; $col < 9; $col++): ?>
                <td class="cell cell--white"
                    data-row="<?= $row ?>"
                    data-col="<?= $col ?>"
                    data-type="white"></td>
                <?php endfor; ?>
            </tr>
            <?php endfor; ?>
        </table>
    </div>

    <div class="actions">
        <button class="btn btn--primary" id="next-btn">Next: Enter Clues &rarr;</button>
    </div>

    <script src="js/grid-editor.js"></script>
    <script>
    function loadDebugPuzzle() {
        const puzzle = buildDebugPuzzle();
        sessionStorage.setItem('kakuro_puzzle', JSON.stringify(puzzle));
        window.location.href = 'solution.php';
    }

    function buildDebugPuzzle() {
        const rows = 8;
        const cols = 8;
        const layout = [
            ['black', 'clue', 'clue', 'clue', 'black', 'black', 'black', 'black'],
            ['clue',  'white', 'white', 'white', 'black', 'black', 'black', 'black'],
            ['clue',  'white', 'white', 'white', 'black', 'black', 'black', 'black'],
            ['clue',  'white', 'white', 'white', 'black', 'black', 'black', 'black'],
            ['black', 'black', 'black', 'black', 'black', 'black', 'black', 'black'],
            ['black', 'black', 'black', 'black', 'black', 'black', 'black', 'black'],
            ['black', 'black', 'black', 'black', 'black', 'black', 'black', 'black'],
            ['black', 'black', 'black', 'black', 'black', 'black', 'black', 'black'],
        ];

        const clues = {
            '0,1': { clueRight: null, clueDown: 6 },
            '0,2': { clueRight: null, clueDown: 6 },
            '0,3': { clueRight: null, clueDown: 6 },
            '1,0': { clueRight: 6, clueDown: null },
            '2,0': { clueRight: 6, clueDown: null },
            ['black', 'clue', 'clue', 'clue', 'clue', 'black', 'black', 'black'],
            ['clue',  'white', 'white', 'white', 'white', 'black', 'black', 'black'],
            ['clue',  'white', 'white', 'white', 'white', 'black', 'black', 'black'],
            ['clue',  'white', 'white', 'white', 'white', 'black', 'black', 'black'],
            ['clue',  'white', 'white', 'white', 'white', 'black', 'black', 'black'],
        };

        const cells = [];
        for (let row = 0; row < rows; row++) {
            for (let col = 0; col < cols; col++) {
                const type = layout[row][col];
            '0,1': { clueRight: null, clueDown: 10 },
            '0,2': { clueRight: null, clueDown: 10 },
            '0,3': { clueRight: null, clueDown: 10 },
            '0,4': { clueRight: null, clueDown: 10 },
            '1,0': { clueRight: 10, clueDown: null },
            '2,0': { clueRight: 10, clueDown: null },
            '3,0': { clueRight: 10, clueDown: null },
            '4,0': { clueRight: 10, clueDown: null },
                    cells.push({ row, col, type });
                }
            }
        }

        return { rows, cols, cells };
    }
    </script>
    <div style="position:fixed;bottom:12px;right:12px">
        <button onclick="loadDebugPuzzle()" style="background:#c84;color:#fff;border:none;padding:6px 12px;border-radius:4px;cursor:pointer;font-size:0.8rem">
            Debug: solve 8×8 puzzle
        </button>
    </div>
</body>
</html>
