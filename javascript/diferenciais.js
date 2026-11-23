/* Modal da página "Diferenciais" */
document.addEventListener('DOMContentLoaded', () => {
    const overlay = document.getElementById('overlayDiferencial');
    if (!overlay) return;

    const imagem = document.getElementById('modalImagem');
    const titulo = document.getElementById('modalTitulo');
    const texto = document.getElementById('modalTexto');
    const fechar = overlay.querySelector('.fecharModal');
    let ultimoBotao = null;

    function abrir(botao) {
        ultimoBotao = botao;
        titulo.textContent = botao.dataset.titulo || '';
        texto.textContent = botao.dataset.texto || '';

        if (botao.dataset.imagem) {
            imagem.src = botao.dataset.imagem;
            imagem.alt = botao.dataset.titulo || '';
            imagem.classList.add('mostrar');
        } else {
            imagem.removeAttribute('src');
            imagem.classList.remove('mostrar');
        }

        overlay.hidden = false;
        overlay.classList.add('ativo');
        document.body.classList.add('modalAberto');
        fechar.focus();
    }

    function fecharModal() {
        overlay.classList.remove('ativo');
        overlay.hidden = true;
        document.body.classList.remove('modalAberto');
        if (ultimoBotao) ultimoBotao.focus();
    }

    document.querySelectorAll('.abrirDiferencial').forEach((b) => b.addEventListener('click', () => abrir(b)));
    fechar.addEventListener('click', fecharModal);
    overlay.addEventListener('click', (e) => { if (e.target === overlay) fecharModal(); });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && overlay.classList.contains('ativo')) fecharModal(); });
});
