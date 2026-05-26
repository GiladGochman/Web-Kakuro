/**
 * Step 3: Solution display.
 *
 * Reads the puzzle definition from sessionStorage, POSTs it to solve.php,
 * and renders the solved board (or an error message).
 */

const spinner     = document.getElementById('spinner');
const gridWrapper = document.getElementById('grid-wrapper');
const gridEl      = document.getElementById('grid');
const actionsEl   = document.getElementById('actions');
const statusArea  = document.getElementById('status-area');

const puzzle = loadPuzzle();
if (!puzzle) {
    window.location.href = 'index.php';
}

solvePuzzle(puzzle);

document.getElementById('back-btn').addEventListener('click', () => {
    window.location.href = 'clues.php';
});

document.getElementById('new-btn').addEventListener('click', () => {
    sessionStorage.removeItem('kakuro_grid');
    sessionStorage.removeItem('kakuro_puzzle');
    window.location.href = 'index.php';
});

// -----------------------------------------------------------------------------
// Solver API call
// -----------------------------------------------------------------------------

async function solvePuzzle(puzzle) {
    try {
        const response = await fetch('solve.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify(puzzle),
        });

        const data = await response.json();

        spinner.hidden = true;

        if (data.status === 'solved') {
            renderSolution(puzzle, data.solution);
        } else {
            showError(data.message ?? 'An unknown error occurred.');
        }
    } catch (err) {
        spinner.hidden = true;
        showError('Could not reach the server. Make sure PHP is running.');
    }
}

// -----------------------------------------------------------------------------
// Grid rendering
// -----------------------------------------------------------------------------

function renderSolution(puzzle, solution) {
    const { rows, cols, cells } = puzzle;

    // Index cell definitions for quick lookup.
    const cellMap = {};
    for (const cell of cells) {
        cellMap[`${cell.row},${cell.col}`] = cell;
    }

    for (let r = 0; r < rows; r++) {
        const tr = document.createElement('tr');

        for (let c = 0; c < cols; c++) {
            const def  = cellMap[`${r},${c}`] ?? { type: 'black' };
            const td   = document.createElement('td');
            td.className    = `cell cell--${def.type}`;
            td.dataset.type = def.type;

            if (def.type === 'clue') {
                td.appendChild(buildClueDisplay(def.clueRight, def.clueDown));

            } else if (def.type === 'white') {
                const digit = solution[r]?.[c];
                td.textContent = digit ?? '?';
                td.classList.add('cell--solved');
            }

            tr.appendChild(td);
        }

        gridEl.appendChild(tr);
    }

    gridWrapper.hidden = false;
    actionsEl.hidden   = false;
}

/**
 * Renders a clue cell with its across and down values in the traditional
 * diagonal layout (across top-right, down bottom-left).
 */
function buildClueDisplay(acrossSum, downSum) {
    const wrapper = document.createElement('div');
    wrapper.className = 'clue-cell';

    const acrossSpan = document.createElement('span');
    acrossSpan.className   = 'clue-value clue-value--across';
    acrossSpan.textContent = acrossSum ?? '';

    const downSpan = document.createElement('span');
    downSpan.className   = 'clue-value clue-value--down';
    downSpan.textContent = downSum ?? '';

    wrapper.appendChild(acrossSpan);
    wrapper.appendChild(downSpan);
    return wrapper;
}

// -----------------------------------------------------------------------------
// Error display
// -----------------------------------------------------------------------------

function showError(message) {
    const errorBox = document.createElement('div');
    errorBox.className   = 'error-box';
    errorBox.textContent = message;
    statusArea.appendChild(errorBox);
    actionsEl.hidden = false;
}

// -----------------------------------------------------------------------------
// Session helper
// -----------------------------------------------------------------------------

function loadPuzzle() {
    const saved = sessionStorage.getItem('kakuro_puzzle');
    if (!saved) return null;

    try {
        return JSON.parse(saved);
    } catch {
        return null;
    }
}
