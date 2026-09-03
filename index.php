<?php
declare(strict_types=1);

// Ensure each visit validates the HTML document, so deployments are not hidden by a stale page cache.
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
$appVersion = hash_file('sha256', __DIR__ . '/app.js');
?>
<!doctype html>
<html lang="pt-BR" class="dark">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Crudly — CRUD de CRUDs</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
      tailwind.config = { darkMode: 'class', theme: { extend: { colors: { ink: '#0b1020', panel: '#11182a', line: '#24304a', violet: '#8b5cf6' } } } }
    </script>
  </head>
  <body class="min-h-screen bg-ink text-slate-200 antialiased">
    <div class="flex min-h-screen">
      <aside class="hidden w-64 flex-col border-r border-line bg-[#0d1424] p-5 lg:flex">
        <a class="mb-10 flex items-center gap-3 text-xl font-bold tracking-tight" href="#">
          <span class="grid h-9 w-9 place-items-center rounded-xl bg-violet text-lg shadow-lg shadow-violet/20">⌘</span> crudly
        </a>
        <p class="mb-3 px-3 text-xs font-semibold uppercase tracking-[.16em] text-slate-500">Workspace</p>
        <nav class="space-y-1 text-sm">
          <a class="flex items-center gap-3 rounded-lg bg-violet/15 px-3 py-2.5 font-medium text-violet-300" href="#dashboard">▦ <span>Meus CRUDs</span></a>
          <a class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-slate-400 hover:bg-slate-800" href="#colunas">▤ <span>Colunas compartilhadas</span></a>
        </nav>
        <div class="mt-auto rounded-xl border border-line bg-panel p-4">
          <div id="databaseStatus" class="mb-2 flex items-center gap-2 text-sm font-medium"><span class="h-2 w-2 rounded-full bg-slate-500"></span> Verificando MySQL…</div>
          <p class="text-xs leading-5 text-slate-500">O status é verificado no servidor. As credenciais ficam no arquivo <code>.env</code>.</p>
        </div>
      </aside>

      <main class="min-w-0 flex-1">
        <header class="flex h-20 items-center justify-between border-b border-line px-5 md:px-9">
          <div class="flex items-center gap-4"><button class="text-xl lg:hidden">☰</button><div><p class="text-xs text-slate-500">Workspace / <span class="text-slate-400">Meus CRUDs</span></p><h1 class="text-lg font-semibold text-white">Seus CRUDs</h1></div></div>
          <div class="flex items-center gap-3"><button class="hidden rounded-lg border border-line p-2 text-slate-400 sm:block">⌕</button><button class="relative rounded-lg border border-line p-2 text-slate-400">♧<span class="absolute right-1.5 top-1.5 h-1.5 w-1.5 rounded-full bg-violet"></span></button><button id="newCrud" class="rounded-lg bg-violet px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-violet/20 hover:bg-violet-500">+ Novo CRUD</button></div>
        </header>

        <section id="dashboard" class="mx-auto max-w-7xl p-5 md:p-9">
          <div class="mb-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-end"><div><h2 class="text-2xl font-bold tracking-tight text-white">Gerencie suas estruturas</h2><p class="mt-1 text-sm text-slate-500">Crie, organize e registre dados com total flexibilidade.</p></div><div class="flex gap-2"><div class="relative"><span class="absolute left-3 top-2.5 text-slate-500">⌕</span><input id="search" class="w-full rounded-lg border border-line bg-panel py-2 pl-9 pr-3 text-sm outline-none placeholder:text-slate-600 focus:border-violet sm:w-56" placeholder="Buscar CRUD..." /></div><button class="rounded-lg border border-line bg-panel px-3 text-sm text-slate-400">≡ Filtros</button></div></div>
          <div class="mb-7 grid gap-4 sm:grid-cols-3"><div class="rounded-xl border border-line bg-panel p-4"><p class="text-xs font-medium uppercase tracking-wider text-slate-500">CRUDs ativos</p><p id="crudCount" class="mt-2 text-2xl font-bold text-white">0</p></div><div class="rounded-xl border border-line bg-panel p-4"><p class="text-xs font-medium uppercase tracking-wider text-slate-500">Registros totais</p><p id="recordCount" class="mt-2 text-2xl font-bold text-white">0</p></div><div class="rounded-xl border border-line bg-panel p-4"><p class="text-xs font-medium uppercase tracking-wider text-slate-500">Colunas compartilhadas</p><p id="columnCount" class="mt-2 text-2xl font-bold text-white">0</p></div></div>
          <div id="crudList" class="grid gap-4 md:grid-cols-2 xl:grid-cols-3"></div>
        </section>
        <section id="recordsDetail" class="hidden w-full p-5 pb-12 md:p-9 md:pb-14">
          <button id="backToDashboard" class="mb-6 text-sm font-medium text-violet-300 hover:text-violet-200">← Voltar para meus CRUDs</button>
          <div class="mb-7 flex flex-col justify-between gap-4 sm:flex-row sm:items-end"><div><p class="text-xs text-slate-500">Registros do CRUD</p><h2 id="recordsTitle" class="mt-1 text-2xl font-bold text-white"></h2><p id="recordsDescription" class="mt-1 text-sm text-slate-500"></p></div><div class="flex gap-3"><button id="openStructureFromRecords" class="rounded-lg border border-line px-4 py-2.5 text-sm font-semibold text-violet-200 hover:bg-violet/10">Gerenciar colunas</button><button id="newRecord" class="rounded-lg bg-violet px-4 py-2.5 text-sm font-semibold text-white">+ Novo registro</button></div></div>
          <div class="overflow-hidden rounded-xl border border-line bg-panel"><div id="recordsTableViewport" class="overflow-x-auto"><table id="recordsTable" class="min-w-full border-collapse"></table></div></div>
        </section>
        <section id="structureDetail" class="hidden mx-auto max-w-7xl p-5 md:p-9">
          <button class="backToDashboard mb-6 text-sm font-medium text-violet-300 hover:text-violet-200">← Voltar para meus CRUDs</button>
          <div class="mb-7 flex flex-col justify-between gap-4 sm:flex-row sm:items-end"><div><p class="text-xs text-slate-500">Estrutura do CRUD</p><h2 id="structureTitle" class="mt-1 text-2xl font-bold text-white"></h2><p class="mt-1 text-sm text-slate-500">Adicione, edite ou remova as colunas desta estrutura.</p></div><button id="openRecordsFromStructure" class="rounded-lg border border-line px-4 py-2.5 text-sm font-semibold text-violet-200 hover:bg-violet/10">Ver registros</button></div>
          <div class="grid gap-6 lg:grid-cols-[1fr_360px]"><div class="rounded-xl border border-line bg-panel p-4"><h3 class="text-base font-semibold text-white">Colunas deste CRUD</h3><div id="columnsList" class="mt-4 space-y-2"></div></div><aside class="rounded-xl border border-line bg-panel p-4"><form id="columnForm" class="space-y-3"><h3 id="columnFormTitle" class="text-base font-semibold text-white">Adicionar coluna</h3><input required name="name" class="input" placeholder="Nome da coluna" /><select name="type" class="input"><option value="0">Texto</option><option value="1">Número inteiro</option><option value="2">Seleção</option></select><input required name="position" type="number" value="0" class="input" placeholder="Ordem" /><div class="flex gap-2"><button type="button" id="cancelColumnEdit" class="hidden rounded-lg px-3 py-2 text-sm text-slate-400 hover:text-white">Cancelar</button><button id="saveColumn" class="flex-1 rounded-lg border border-violet/60 px-3 py-2 text-sm font-semibold text-violet-200 hover:bg-violet/10">Adicionar coluna</button></div></form></aside></div>
        </section>
        <section id="optionsDetail" class="hidden mx-auto max-w-7xl p-5 md:p-9">
          <button id="backToStructure" class="mb-6 text-sm font-medium text-violet-300 hover:text-violet-200">← Voltar para as colunas</button>
          <div class="mb-7"><p class="text-xs text-slate-500">Opções da coluna</p><h2 id="optionsTitle" class="mt-1 text-2xl font-bold text-white"></h2><p class="mt-1 text-sm text-slate-500">Adicione, edite ou remova os itens exibidos na seleção.</p></div>
          <div class="grid gap-6 lg:grid-cols-[1fr_360px]"><div class="rounded-xl border border-line bg-panel p-4"><h3 class="text-base font-semibold text-white">Opções disponíveis</h3><div id="optionsList" class="mt-4 space-y-2"></div></div><aside class="rounded-xl border border-line bg-panel p-4"><form id="optionForm" class="space-y-3"><h3 id="optionFormTitle" class="text-base font-semibold text-white">Adicionar opção</h3><input required name="value" class="input" placeholder="Valor da opção" /><input required name="position" type="number" value="0" class="input" placeholder="Ordem" /><div class="flex gap-2"><button type="button" id="cancelOptionEdit" class="hidden rounded-lg px-3 py-2 text-sm text-slate-400 hover:text-white">Cancelar</button><button id="saveOption" class="flex-1 rounded-lg border border-violet/60 px-3 py-2 text-sm font-semibold text-violet-200 hover:bg-violet/10">Adicionar opção</button></div></form></aside></div>
        </section>
      </main>
    </div>

    <div id="recordsHorizontalScrollbar" class="fixed inset-x-0 bottom-0 z-10 hidden border-t border-line bg-panel/95 px-5 pb-[env(safe-area-inset-bottom)] shadow-[0_-8px_24px_rgba(0,0,0,.18)] backdrop-blur md:px-9" role="region" aria-label="Barra de rolagem horizontal fixa da tabela" aria-controls="recordsTableViewport">
      <div id="recordsHorizontalScrollbarTrack" class="h-5 overflow-x-scroll overflow-y-hidden"><div id="recordsHorizontalScrollbarContent" class="h-px"></div></div>
    </div>

    <div id="modal" class="fixed inset-0 z-20 hidden items-center justify-center bg-slate-950/75 p-4 backdrop-blur-sm">
      <form id="crudForm" class="w-full max-w-lg rounded-2xl border border-line bg-[#11182a] p-6 shadow-2xl"><div class="mb-6 flex items-start justify-between"><div><h2 class="text-xl font-bold text-white">Criar novo CRUD</h2><p class="mt-1 text-sm text-slate-500">Configure a estrutura inicial do seu cadastro.</p></div><button type="button" id="closeModal" class="text-xl text-slate-500 hover:text-white">×</button></div><label class="block text-sm font-medium text-slate-300">Nome do CRUD<input required id="crudName" class="mt-2 w-full rounded-lg border border-line bg-[#0b1020] px-3 py-2.5 outline-none focus:border-violet" placeholder="Ex.: Fornecedores" /></label><fieldset class="mt-5"><legend class="text-sm font-medium text-slate-300">Orientação das colunas</legend><div class="mt-2 grid grid-cols-2 gap-3"><label class="cursor-pointer rounded-lg border border-violet bg-violet/10 p-3"><input checked type="radio" name="orientation" value="0" class="accent-violet" /> <span class="ml-1 text-sm font-medium">Horizontal</span><small class="mt-1 block text-xs text-slate-500">Registros em linhas</small></label><label class="cursor-pointer rounded-lg border border-line p-3"><input type="radio" name="orientation" value="1" class="accent-violet" /> <span class="ml-1 text-sm font-medium">Vertical</span><small class="mt-1 block text-xs text-slate-500">Registros em colunas</small></label></div></fieldset><div class="mt-6 flex justify-end gap-3"><button type="button" id="cancel" class="rounded-lg px-4 py-2 text-sm text-slate-400">Cancelar</button><button class="rounded-lg bg-violet px-4 py-2 text-sm font-semibold text-white">Criar estrutura</button></div></form>
    </div>
    <div id="recordModal" class="fixed inset-0 z-30 hidden items-center justify-center bg-slate-950/75 p-4 backdrop-blur-sm"><div class="w-full max-w-lg rounded-2xl border border-line bg-panel p-6"><h2 id="recordFormTitle" class="mb-5 text-xl font-bold text-white"></h2><form id="recordForm" class="space-y-4"></form></div></div>
    <style>.input{margin-top:.45rem;width:100%;border-radius:.5rem;border:1px solid #24304a;background:#0b1020;padding:.6rem .75rem;color:#e2e8f0;outline:none}.input:focus{border-color:#8b5cf6}</style>
    <script src="app.js?v=<?= rawurlencode($appVersion) ?>"></script>
  </body>
</html>
