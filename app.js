const initialCruds = [
  { name: 'Clientes', columns: 6, records: 842, orientation: 0, color: 'from-violet-500 to-indigo-500' },
  { name: 'Projetos', columns: 8, records: 128, orientation: 1, color: 'from-fuchsia-500 to-pink-500' },
  { name: 'Inventário', columns: 11, records: 1743, orientation: 0, color: 'from-cyan-500 to-blue-500' },
  { name: 'Fornecedores', columns: 5, records: 94, orientation: 0, color: 'from-emerald-500 to-teal-500' },
  { name: 'Solicitações', columns: 7, records: 40, orientation: 1, color: 'from-amber-500 to-orange-500' },
  { name: 'Equipe', columns: 9, records: 0, orientation: 0, color: 'from-rose-500 to-red-500' }
];
let cruds = [...initialCruds];
const list = document.querySelector('#crudList');
const format = new Intl.NumberFormat('pt-BR');
function render(items = cruds) {
  list.innerHTML = items.map((crud, i) => `<article class="group rounded-xl border border-line bg-panel p-5 transition hover:-translate-y-0.5 hover:border-slate-600"><div class="mb-6 flex justify-between"><div class="grid h-11 w-11 place-items-center rounded-xl bg-gradient-to-br ${crud.color}"><span class="text-xl">${crud.orientation ? '↕' : '↔'}</span></div><button class="rounded-md px-2 text-lg text-slate-500 opacity-0 hover:bg-slate-800 hover:text-white group-hover:opacity-100">···</button></div><h3 class="text-base font-semibold text-white">${crud.name}</h3><div class="mt-2 flex items-center gap-2 text-xs text-slate-500"><span class="rounded bg-slate-800 px-2 py-1">${crud.orientation ? 'Vertical' : 'Horizontal'}</span><span>Atualizado hoje</span></div><div class="mt-6 grid grid-cols-2 border-t border-line pt-4"><div><p class="text-xs text-slate-500">Colunas</p><p class="mt-1 text-sm font-semibold text-slate-200">${crud.columns}</p></div><div class="border-l border-line pl-4"><p class="text-xs text-slate-500">Registros</p><p class="mt-1 text-sm font-semibold text-slate-200">${format.format(crud.records)}</p></div></div><button data-index="${i}" class="open mt-5 w-full rounded-lg border border-line py-2 text-sm font-medium text-slate-300 hover:border-violet hover:bg-violet/10 hover:text-violet-300">Abrir CRUD →</button></article>`).join('');
}
render();
const modal = document.querySelector('#modal');
const showModal = () => modal.classList.replace('hidden', 'flex');
const hideModal = () => modal.classList.replace('flex', 'hidden');
document.querySelector('#newCrud').onclick = showModal;
document.querySelector('#closeModal').onclick = hideModal;
document.querySelector('#cancel').onclick = hideModal;
modal.onclick = e => { if (e.target === modal) hideModal(); };
document.querySelector('#crudForm').onsubmit = e => { e.preventDefault(); const name = document.querySelector('#crudName').value.trim(); const orientation = +document.querySelector('input[name="orientation"]:checked').value; if (!name) return; cruds.unshift({ name, columns: 0, records: 0, orientation, color: 'from-violet-500 to-indigo-500' }); render(); e.target.reset(); hideModal(); };
document.querySelector('#search').oninput = e => render(cruds.filter(c => c.name.toLowerCase().includes(e.target.value.toLowerCase())));
