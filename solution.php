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
</body>
</html>
