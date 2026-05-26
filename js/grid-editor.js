/**
 * Step 1: Grid editor.
 *
 * Manages the cell-type toggle (white → clue → black → white) and persists
 * the grid layout to sessionStorage before navigating to clues.php.
 */

const CELL_TYPES = ['white', 'clue', 'black'];

const grid = document.getElementById('grid');

// Restore any previously saved grid layout so the user can come back to edit.
restoreGridFromSession();

grid.addEventListener('click', (event) => {
    const cell = event.target.closest('[data-type]');
    if (!cell) return;
    cycleCell(cell);
});

document.getElementById('next-btn').addEventListener('click', () => {
    const layout = buildGridLayout();

    if (!hasAtLeastOneWhiteCell(layout)) {
        alert('Please add at least one white cell before continuing.');
        return;
    }

    sessionStorage.setItem('kakuro_grid', JSON.stringify(layout));
    window.location.href = 'clues.php';
});

// -----------------------------------------------------------------------------
// Cell state cycling
// -----------------------------------------------------------------------------

function cycleCell(cell) {
    const currentType = cell.dataset.type;
    const nextType    = CELL_TYPES[(CELL_TYPES.indexOf(currentType) + 1) % CELL_TYPES.length];

    cell.dataset.type = nextType;
    cell.className    = `cell cell--${nextType}`;
}

// -----------------------------------------------------------------------------
// Grid serialisation / deserialisation
// -----------------------------------------------------------------------------

function buildGridLayout() {
    const rows = [];
    for (const tr of grid.querySelectorAll('tr')) {
        const row = [];
        for (const td of tr.querySelectorAll('[data-type]')) {
            row.push(td.dataset.type);
        }
        rows.push(row);
    }
    return rows;
}

function restoreGridFromSession() {
    const saved = sessionStorage.getItem('kakuro_grid');
    if (!saved) return;

    let layout;
    try {
        layout = JSON.parse(saved);
    } catch {
        return;
    }

    for (const td of grid.querySelectorAll('[data-type]')) {
        const row  = parseInt(td.dataset.row, 10);
        const col  = parseInt(td.dataset.col, 10);
        const type = layout[row]?.[col] ?? 'white';

        td.dataset.type = type;
        td.className    = `cell cell--${type}`;
    }
}

function hasAtLeastOneWhiteCell(layout) {
    return layout.some(row => row.includes('white'));
}
