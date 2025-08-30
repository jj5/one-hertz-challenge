(function () {
  const table = document.getElementById('dataTable');
  if (!table) return console.log( 'Table element not found' );

  const tbody = table.tBodies[0];
  if (!tbody) return console.log( 'Table body not found' );

  function fix_rank() {
    const rows = Array.from(tbody.rows);
    rows.forEach((row, idx) => {
      const rankCell = row.querySelector('.row-rank');
      console.log(rankCell);
      if (rankCell) {
        rankCell.value = String(idx + 1);
      }
    });
  }

  // create action cell with buttons for a row
  function createActionCell() {
    const td = document.createElement('td');
    td.className = 'actions actions-cell';

    const wrapper = document.createElement('div');
    wrapper.className = 'controls';

    const top = document.createElement('button');
    top.type = 'button';
    top.className = 'move-btn move-top';
    top.title = 'Move row to top';
    top.setAttribute('aria-label', 'Move row to top');
    top.innerHTML = '⤒';

    const up = document.createElement('button');
    up.type = 'button';
    up.className = 'move-btn move-up';
    up.title = 'Move row up';
    up.setAttribute('aria-label', 'Move row up');
    up.innerHTML = '▲';

    const down = document.createElement('button');
    down.type = 'button';
    down.className = 'move-btn move-down';
    down.title = 'Move row down';
    down.setAttribute('aria-label', 'Move row down');
    down.innerHTML = '▼';

    const bottom = document.createElement('button');
    bottom.type = 'button';
    bottom.className = 'move-btn move-bottom';
    bottom.title = 'Move row to bottom';
    bottom.setAttribute('aria-label', 'Move row to bottom');
    bottom.innerHTML = '⤓';

    wrapper.appendChild(top);
    wrapper.appendChild(up);
    wrapper.appendChild(down);
    wrapper.appendChild(bottom);
    td.appendChild(wrapper);
    return td;
  }

  // inject actions into every data row (if not present)
  function ensureActionCells() {
    Array.from(tbody.rows).forEach(row => {
      // skip if already has an actions cell
      if (row.querySelector('td.actions')) return;
      const actionCell = createActionCell();
      // 2025-08-30 jj5 - NEW:
      row.insertBefore(actionCell, row.firstElementChild);
      // 2025-08-30 jj5 - OLD:
      //row.appendChild(actionCell);
    });
  }

  // update disabled state for the first/last rows
  function updateButtons() {
    const rows = Array.from(tbody.rows);
    rows.forEach((row, idx) => {
      const top = row.querySelector('button.move-top');
      const up = row.querySelector('button.move-up');
      const down = row.querySelector('button.move-down');
      const bottom = row.querySelector('button.move-bottom');
      if (!top || !up || !down || !bottom) return;
      const isFirst = (idx === 0);
      const isLast = (idx === rows.length - 1);
      top.disabled = isFirst;
      up.disabled = isFirst;
      down.disabled = isLast;
      bottom.disabled = isLast;
    });

    // optional: update numeric index in first column if numeric
    rows.forEach((row, idx) => {
      const firstCell = row.cells[0];
      if (firstCell && /^\d+$/.test(firstCell.textContent.trim())) {
        firstCell.textContent = String(idx + 1);
      }
    });

    // 2025-08-30 jj5 - John was here...
    fix_rank();

  }

  // move the row: direction = -1 (up), 1 (down), 'top', 'bottom'
  function moveRow(row, direction) {
    if (!row || !row.parentElement) return;
    const parent = row.parentElement; // tbody
    const rows = Array.from(parent.children).filter(n => n.nodeName === 'TR');

    if (direction === -1) {
      const prev = row.previousElementSibling;
      if (!prev) return;
      parent.insertBefore(row, prev); // swap with previous
    } else if (direction === 1) {
      const next = row.nextElementSibling;
      if (!next) return;
      const afterNext = next.nextElementSibling; // may be null
      parent.insertBefore(row, afterNext); // insert after next (or append if null)
    } else if (direction === 'top') {
      const first = parent.firstElementChild;
      if (!first || first === row) return;
      parent.insertBefore(row, first);
    } else if (direction === 'bottom') {
      const last = parent.lastElementChild;
      if (!last || last === row) return;
      parent.appendChild(row); // moves row to end
    } else {
      // unsupported direction - do nothing
      return;
    }

    updateButtons();
  }

  // Event delegation for clicks on move buttons
  table.addEventListener('click', (ev) => {
    const btn = ev.target.closest('button.move-btn');
    if (!btn) return;
    const row = btn.closest('tr');
    if (!row) return;

    if (btn.classList.contains('move-up')) {
      moveRow(row, -1);
      // focus the button in its new position
      const newUp = row.querySelector('button.move-up');
      if (newUp) newUp.focus();
    } else if (btn.classList.contains('move-down')) {
      moveRow(row, +1);
      const newDown = row.querySelector('button.move-down');
      if (newDown) newDown.focus();
    } else if (btn.classList.contains('move-top')) {
      moveRow(row, 'top');
      const newTop = row.querySelector('button.move-top');
      if (newTop) newTop.focus();
    } else if (btn.classList.contains('move-bottom')) {
      moveRow(row, 'bottom');
      const newBottom = row.querySelector('button.move-bottom');
      if (newBottom) newBottom.focus();
    }
  });

  // Initialize
  ensureActionCells();
  updateButtons();

  // Optional: expose functions to window for debugging
  window.tableRowMover = {
    ensureActionCells, updateButtons, moveRow
  };
})();