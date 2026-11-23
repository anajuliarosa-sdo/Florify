/* Visualização com I.A.: pré-visualização da foto, contador e tela de carregamento */
document.addEventListener('DOMContentLoaded', () => {
    const MAX_MB = 8;
    const foto = document.getElementById('vizFoto');
    if (!foto) return;

    const preview = document.getElementById('vizPreview');
    const drop = document.getElementById('vizDrop');
    const desc = document.getElementById('vizDescricao');
    const qtd = document.getElementById('vizQtd');

    function mostrarPreview() {
        const arq = foto.files[0];
        if (!arq) return;
        if (arq.size > MAX_MB * 1024 * 1024) {
            alert('A foto deve ter no máximo ' + MAX_MB + ' MB.');
            foto.value = '';
            preview.style.display = 'none';
            return;
        }
        preview.src = URL.createObjectURL(arq);
        preview.style.display = 'inline-block';
    }
    foto.addEventListener('change', mostrarPreview);

    ['dragenter', 'dragover'].forEach(ev => drop.addEventListener(ev, e => {
        e.preventDefault(); drop.classList.add('hover');
    }));
    ['dragleave', 'drop'].forEach(ev => drop.addEventListener(ev, e => {
        e.preventDefault(); drop.classList.remove('hover');
    }));
    drop.addEventListener('drop', e => {
        if (e.dataTransfer.files.length) {
            foto.files = e.dataTransfer.files;
            mostrarPreview();
        }
    });

    desc.addEventListener('input', () => qtd.textContent = desc.value.length);
    qtd.textContent = desc.value.length;

    document.getElementById('vizForm').addEventListener('submit', () => {
        document.getElementById('vizBtn').disabled = true;
        document.getElementById('vizLoading').classList.add('ativo');
    });

    const resultado = document.getElementById('vizResultado');
    if (resultado) resultado.scrollIntoView({ behavior: 'smooth' });
});
