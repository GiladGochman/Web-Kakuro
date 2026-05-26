<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kakuro Solver — Step 2: Enter Clues</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="page page--clue-editor">

    <div class="page-header">
        <h1>Kakuro Solver</h1>
        <p class="step-label">Step 2 of 3 — Enter the clue numbers</p>
    </div>

    <div class="instructions">
        <p>
            For each diagonal cell, enter the <strong>across sum</strong> (top-right) and/or
            the <strong>down sum</strong> (bottom-left). Leave a field at 0 if there is no
            run in that direction.
        </p>
    </div>

    <div class="grid-wrapper">
        <!--
            The grid is rendered entirely by clue-editor.js from the layout stored in
            sessionStorage. This keeps the PHP file free of presentation logic.
        -->
        <table class="kakuro-grid" id="grid"></table>
    </div>

    <div class="actions">
        <button class="btn btn--secondary" id="back-btn">&larr; Back</button>
        <button class="btn btn--primary"   id="solve-btn">Solve &rarr;</button>
    </div>

    <script src="js/clue-editor.js"></script>
</body>
</html>
