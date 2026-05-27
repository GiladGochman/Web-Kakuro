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
        const layout = buildDebugLayout();
        sessionStorage.setItem('kakuro_grid', JSON.stringify(layout));
        window.location.href = 'clues.php';
    }

    function buildDebugLayout() {
        return [
            ['black', 'clue', 'clue', 'clue', 'black', 'black', 'black', 'black'],
            ['clue',  'white', 'white', 'white', 'black', 'black', 'black', 'black'],
            ['clue',  'white', 'white', 'white', 'black', 'black', 'black', 'black'],
            ['clue',  'white', 'white', 'white', 'black', 'black', 'black', 'black'],
            ['black', 'black', 'black', 'black', 'black', 'black', 'black', 'black'],
            ['black', 'black', 'black', 'black', 'black', 'black', 'black', 'black'],
            ['black', 'black', 'black', 'black', 'black', 'black', 'black', 'black'],
            ['black', 'black', 'black', 'black', 'black', 'black', 'black', 'black'],
        ];
    }
    </script>
    <div style="position:fixed;bottom:12px;right:12px">
        <button onclick="loadDebugPuzzle()" style="background:#c84;color:#fff;border:none;padding:6px 12px;border-radius:4px;cursor:pointer;font-size:0.8rem">
            Debug: solve 8×8 puzzle
        </button>
    </div>
</body>
</html>
