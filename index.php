<?php
// Simple upload UI for pdf-union
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>PDF Union - Upload</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: Arial, sans-serif; max-width:900px; margin:40px auto; }
        .file-list { list-style:none; padding:0; }
        .file-item { padding:8px; border:1px solid #ddd; margin-bottom:6px; display:flex; align-items:center; justify-content:space-between; }
        button { margin-left:6px; }
        .controls { margin-top:12px; }
    </style>
</head>
<body>
    <h1>PDF Union - Upload e Ordenação</h1>

    <p>Selecione arquivos PDF ou imagens (jpg, png). Depois arraste para ordenar e clique em "Gerar PDF Unificado".</p>

    <input id="fileInput" type="file" multiple accept="application/pdf,image/jpeg,image/png" />

    <ul id="fileList" class="file-list"></ul>

    <div class="controls">
        <button id="generateBtn">Gerar PDF Unificado</button>
    </div>

    <div id="status"></div>

    <!-- Sortable.js CDN (integrity removed because CDN integrity can block if hashes mismatch) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js"></script>

    <style>
        .drag-handle { cursor:grab; margin-right:10px; }
        .progress { height: 8px; background:#eee; border-radius:4px; overflow:hidden; margin-top:8px; }
        .progress > i { display:block; height:100%; width:0%; background:linear-gradient(90deg,#4caf50,#8bc34a); }
        .spinner { display:inline-block; width:18px; height:18px; border:2px solid #ccc; border-top-color:#333; border-radius:50%; animation:spin 1s linear infinite; vertical-align:middle; margin-left:8px; }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>

    <script>
        const fileInput = document.getElementById('fileInput');
        const fileList = document.getElementById('fileList');
        const generateBtn = document.getElementById('generateBtn');
        let files = [];

        function renderList() {
            fileList.innerHTML = '';
            files.forEach((f, idx) => {
                const li = document.createElement('li');
                li.className = 'file-item';
                li.dataset.index = idx;
                li.innerHTML = `
                    <span><span class="drag-handle">☰</span>${idx+1} - ${f.name}</span>
                    <span>
                        <button class="remove">✖</button>
                    </span>`;
                fileList.appendChild(li);
            });
        }

        fileInput.addEventListener('change', (e) => {
            const selected = Array.from(e.target.files);
            // append to existing
            files = files.concat(selected);
            renderList();
            fileInput.value = '';
        });

        fileList.addEventListener('click', (e) => {
            const li = e.target.closest('.file-item');
            if (!li) return;
            const idx = Number(li.dataset.index);
            if (e.target.classList.contains('remove')) {
                files.splice(idx, 1);
                renderList();
            }
        });

        // make list sortable via Sortable.js
        const sortable = new Sortable(fileList, {
            handle: '.drag-handle',
            animation: 150,
            onEnd: function (evt) {
                // reorder files array using event indices (more robust)
                const oldIndex = evt.oldIndex;
                const newIndex = evt.newIndex;
                if (oldIndex === newIndex) return;
                const moved = files.splice(oldIndex, 1)[0];
                files.splice(newIndex, 0, moved);
                renderList();
            }
        });

        generateBtn.addEventListener('click', async () => {
            if (files.length === 0) {
                alert('Selecione ao menos um arquivo.');
                return;
            }
            generateBtn.disabled = true;
            // show progress + spinner
            document.getElementById('status').innerHTML = '<div class="progress"><i></i></div><span id="statusText">Enviando e processando...</span><span class="spinner"></span>';
            const progressBar = document.querySelector('.progress > i');
            function setProgress(p) { progressBar.style.width = Math.max(2, Math.min(100, p)) + '%'; }
            setProgress(5);

            const fd = new FormData();
            files.forEach((f) => fd.append('files[]', f));

            try {
                // Use fetch with progress by splitting upload into chunks is complex; rely on client-side steps to approximate progress
                const resp = await fetch('process.php', { method: 'POST', body: fd });
                // simulate progress to 80% while waiting
                setProgress(80);
                const text = await resp.text();
                setProgress(100);
                // replace page with response (link/status)
                document.body.innerHTML = text;
            } catch (err) {
                alert('Erro no envio: ' + err.message);
                generateBtn.disabled = false;
                document.getElementById('status').textContent = '';
            }
        });
    </script>
</body>
</html>
