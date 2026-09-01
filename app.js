let cruds = [];
let databaseAvailable = false;

const list = document.querySelector('#crudList');
const status = document.querySelector('#databaseStatus');
const format = new Intl.NumberFormat('pt-BR');

function escapeHtml(value) {
  return String(value).replace(/[&<>'"]/g, character => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;'
  })[character]);
}

function updateStats() {
  document.querySelector('#crudCount').textContent = format.format(cruds.length);
  document.querySelector('#recordCount').textContent = format.format(cruds.reduce((total, crud) => total + Number(crud.records), 0));
  document.querySelector('#columnCount').textContent = format.format(cruds.reduce((total, crud) => total + Number(crud.columns), 0));
}

function render(items = cruds) {
  updateStats();
  if (!items.length) {
    list.innerHTML = `<div class="col-span-full rounded-xl border border-dashed border-line bg-panel p-10 text-center"><p class="text-base font-semibold text-white">Nenhum CRUD encontrado</p><p class="mt-2 text-sm text-slate-500">Crie a primeira estrutura para ela aparecer aqui.</p></div>`;
    return;
  }

  list.innerHTML = items.map(crud => `<article class="rounded-xl border border-line bg-panel p-5"><div class="mb-6 flex justify-between"><div class="grid h-11 w-11 place-items-center rounded-xl bg-gradient-to-br from-violet-500 to-indigo-500"><span class="text-xl">${crud.orientation ? '↕' : '↔'}</span></div></div><h3 class="text-base font-semibold text-white">${escapeHtml(crud.name)}</h3><div class="mt-2 flex items-center gap-2 text-xs text-slate-500"><span class="rounded bg-slate-800 px-2 py-1">${crud.orientation ? 'Vertical' : 'Horizontal'}</span></div><div class="mt-6 grid grid-cols-2 border-t border-line pt-4"><div><p class="text-xs text-slate-500">Colunas</p><p class="mt-1 text-sm font-semibold text-slate-200">${format.format(crud.columns)}</p></div><div class="border-l border-line pl-4"><p class="text-xs text-slate-500">Registros</p><p class="mt-1 text-sm font-semibold text-slate-200">${format.format(crud.records)}</p></div></div></article>`).join('');
}

function setConnectionStatus(connected) {
  databaseAvailable = connected;
  status.innerHTML = connected
    ? '<span class="h-2 w-2 rounded-full bg-emerald-400"></span> MySQL conectado'
    : '<span class="h-2 w-2 rounded-full bg-slate-500"></span> MySQL indisponível';
  document.querySelector('#newCrud').disabled = !connected;
  document.querySelector('#newCrud').title = connected ? '' : 'Configure o MySQL e inicie o servidor PHP para criar CRUDs.';
}

async function loadCruds() {
  try {
    const response = await fetch('api.php/cruds');
    const payload = await response.json();
    if (!response.ok) throw new Error(payload.error);
    cruds = payload.cruds.map(crud => ({
      ...crud,
      orientation: Number(crud.orientation),
      columns: Number(crud.columns),
      records: Number(crud.records)
    }));
    setConnectionStatus(true);
  } catch (error) {
    cruds = [];
    setConnectionStatus(false);
  }
  render();
}

const modal = document.querySelector('#modal');
const showModal = () => modal.classList.replace('hidden', 'flex');
const hideModal = () => modal.classList.replace('flex', 'hidden');
document.querySelector('#newCrud').onclick = showModal;
document.querySelector('#closeModal').onclick = hideModal;
document.querySelector('#cancel').onclick = hideModal;
modal.onclick = event => { if (event.target === modal) hideModal(); };

document.querySelector('#crudForm').onsubmit = async event => {
  event.preventDefault();
  const name = document.querySelector('#crudName').value.trim();
  const orientation = Number(document.querySelector('input[name="orientation"]:checked').value);
  if (!name || !databaseAvailable) return;

  const submit = event.submitter;
  submit.disabled = true;
  try {
    const response = await fetch('api.php/cruds', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ name, orientation }) });
    const payload = await response.json();
    if (!response.ok) throw new Error(payload.error);
    cruds.unshift(payload.crud);
    render();
    event.target.reset();
    hideModal();
  } catch (error) {
    alert(`Não foi possível criar o CRUD: ${error.message || 'erro de conexão.'}`);
    await loadCruds();
  } finally {
    submit.disabled = false;
  }
};

document.querySelector('#search').oninput = event => render(cruds.filter(crud => crud.name.toLowerCase().includes(event.target.value.toLowerCase())));
loadCruds();
