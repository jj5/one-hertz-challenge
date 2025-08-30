(function(){
  //const addRowBtn = document.getElementById('addRowBtn');
  const saveBtn = document.getElementById('saveBtn');
  const loadBtn = document.getElementById('loadBtn');
  const dataTable = document.getElementById('dataTable').querySelector('tbody');
  const msgEl = document.getElementById('msg');

  function showMessage(text, ok = true) {
    msgEl.style.display = 'block';
    msgEl.textContent = text;
    msgEl.className = 'message ' + (ok ? 'success' : 'error');
    setTimeout(()=>{ msgEl.style.display = 'none'; }, 4000);
  }

  function getNextId() {
    // find max id in table and +1
    let max = 0;
    const ids = dataTable.querySelectorAll('.row-id');
    ids.forEach(i => {
      const v = parseInt(i.value, 10);
      if (!isNaN(v) && v > max) max = v;
    });
    return max + 1;
  }

  function createRow(data = {}) {
    const id = (typeof data.id !== 'undefined') ? data.id : getNextId();
    const name = data.name || '';
    const rank = (typeof data.rank !== 'undefined') ? data.rank : 1;
    const is_enabled = !!data.is_enabled;
    const notes = data.notes || '';

    const tr = document.createElement('tr');

    tr.innerHTML = `
      <td class="id-cell"><input type="number" class="row-id" value="${id}" readonly /></td>
      <td><input type="text" class="row-name" value="${escapeHtml(name)}" /></td>
      <td class="rank-cell">
        ${createRankSelect(rank)}
      </td>
      <td class="enabled-cell"><input type="checkbox" class="row-enabled" ${is_enabled ? 'checked' : ''} /></td>
      <td><textarea class="row-notes" rows="1">${escapeHtml(notes)}</textarea></td>
      <td class="actions-cell"><button type="button" class="deleteRowBtn">Delete</button></td>
    `;
    dataTable.appendChild(tr);
    attachRowListeners(tr);
    return tr;
  }

  function createRankSelect(selected) {
    let s = '<select class="row-rank">';
    for (let r = 1; r <= 10; r++) {
      s += `<option value="${r}" ${r === Number(selected) ? 'selected' : ''}>${r}</option>`;
    }
    s += '</select>';
    return s;
  }

  function escapeHtml(s) {
    return ('' + s)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function attachRowListeners(tr) {
    const del = tr.querySelector('.deleteRowBtn');
    del.addEventListener('click', () => {
      if (confirm('Delete this row?')) tr.remove();
    });
  }

  // attach to existing delete buttons
  Array.from(document.querySelectorAll('.deleteRowBtn')).forEach(btn => {
    btn.addEventListener('click', function(){
      const tr = this.closest('tr');
      if (confirm('Delete this row?')) tr.remove();
    });
  });

  /*
  addRowBtn.addEventListener('click', function(){
    createRow({});
  });
  */

  saveBtn.addEventListener('click', function(){
    const data = gatherData();

    // 2025-08-30 jj5 - TEMP:
    console.log( data );

    if (!Array.isArray(data)) return;
    // Basic client-side validation: id numeric and unique
    const ids = data.map(r => r.id);
    const dup = ids.some((v,i) => ids.indexOf(v) !== i);
    if (dup) {
      showMessage('Duplicate IDs detected. Each row must have a unique numeric ID.', false);
      return;
    }

    // POST to server
    const form = new FormData();
    form.append('action', 'save');
    form.append('data', JSON.stringify(data));

    fetch(window.location.pathname, { method: 'POST', body: form })
      .then(r => r.json())
      .then(o => {
        if (o && o.success) {
          showMessage('Saved successfully.');
          // optionally refresh link to file or reload
        } else {
          showMessage('Save failed: ' + (o && o.message ? o.message : 'Unknown'), false);
        }
      })
      .catch(err => {
        console.error(err);
        showMessage('Save failed: ' + err.message, false);
      });
  });

  loadBtn.addEventListener('click', function(){
    if (!confirm('Reload page to re-load data from the JSON file? Unsaved changes will be lost.')) return;
    location.reload(true);
  });

  function gatherData() {
    const rows = [];
    const trs = dataTable.querySelectorAll('tr');
    for (const tr of trs) {
      const idEl = tr.querySelector('.row-id');
      const nameEl = tr.querySelector('.row-name');

      const projectEl = tr.querySelector('.project-cell');
      const url = projectEl.querySelector('a') ? projectEl.querySelector('a').href : '';
      const project_name = projectEl.innerText;

      const rankEl = tr.querySelector('.row-rank');
      const timelordsEl = tr.querySelector('.row-timelords');
      const ridiculousEl = tr.querySelector('.row-ridiculous');
      const clockworkEl = tr.querySelector('.row-clockwork');
      const couldHaveUsed555El = tr.querySelector('.row-could-have-used-a-555');

      const notesEl = tr.querySelector('.row-notes');

      if (!idEl) {
        console.log( 'missing ID element' );
        continue;
      }
      const id = parseInt(idEl.innerText, 10);
      if (Number.isNaN(id)) {
        showMessage('Invalid ID found; ensure IDs are numeric.', false);
        return null;
      }

      let rank = parseInt(rankEl.value);
      if (Number.isNaN(rank) || rank < 1) rank = 0;

      rows.push({
        id: id,
        url: url,
        project_name: project_name,
        rank: rank,
        timelords: !!(timelordsEl && timelordsEl.checked),
        ridiculous: !!(ridiculousEl && ridiculousEl.checked),
        clockwork: !!(clockworkEl && clockworkEl.checked),
        could_have_used_a_555: !!(couldHaveUsed555El && couldHaveUsed555El.checked),
        notes: notesEl ? notesEl.value.trim() : ''
      });
    }
    return rows;
  }

  // If user double-clicks ID, allow editing (optional). By default IDs are readonly.
  // Provide keyboard shortcut: Ctrl+S to save
  window.addEventListener('keydown', function(e){
    if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's') {
      e.preventDefault();
      saveBtn.click();
    }
  });
})();