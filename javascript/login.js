/* Login e cadastro conectados aos endpoints PHP e ao banco de dados. */
document.addEventListener('DOMContentLoaded', () => {
    const conteudoLogin = document.getElementById('conteudoLogin');
    const conteudoCadastro = document.getElementById('conteudoCadastro');
    const login = document.getElementById('login');
    const cadastro = document.getElementById('cadastro');
    const btnEntrar = document.getElementById('btnEntrar');
    const btnCadastrar = document.getElementById('btnCadastrar');
    const btnAcessoAdmin = document.getElementById('btnAcessoAdmin');
    const modoLogin = document.getElementById('modoLogin');
    const tituloLogin = document.getElementById('tituloLogin');
    const descricaoLogin = document.getElementById('descricaoLogin');
    const linkCadastro = document.getElementById('linkCadastro');

    /* Alterna entre acesso de cliente e acesso administrativo. */
    function definirModoAdmin(ativar) {
        modoLogin.value = ativar ? 'admin' : 'cliente';
        tituloLogin.textContent = ativar ? 'Acesso do administrador' : 'Bem-vindo(a) de volta';
        descricaoLogin.textContent = ativar
            ? 'Entre com o e-mail e a senha de uma conta administradora.'
            : 'Entre para acompanhar seus pedidos e comprar mais rápido.';
        btnEntrar.textContent = ativar ? 'Entrar no painel administrativo' : 'Entrar';
        btnAcessoAdmin.innerHTML = ativar
            ? '<i class="bi bi-person" aria-hidden="true"></i> Voltar ao acesso de cliente'
            : '<i class="bi bi-shield-lock" aria-hidden="true"></i> Entrar como administrador';
        linkCadastro.hidden = ativar;
        login.querySelectorAll('.login-status').forEach((area) => {
            area.textContent = '';
            area.className = 'login-status';
        });
    }
    btnAcessoAdmin.addEventListener('click', () => {
        definirModoAdmin(modoLogin.value !== 'admin');
    });
    if (new URLSearchParams(location.search).get('modo') === 'admin') definirModoAdmin(true);

    /* alterna entre login e cadastro */
    function mostrar(qual) {
        const irParaCadastro = qual === 'cadastro';
        conteudoLogin.hidden = irParaCadastro;
        conteudoCadastro.hidden = !irParaCadastro;
        if (irParaCadastro) definirModoAdmin(false);
        (irParaCadastro ? document.getElementById('novoUsuario') : document.getElementById('usuario')).focus();
        history.replaceState(null, '', irParaCadastro ? '#cadastro' : location.pathname);
    }
    document.getElementById('irParaCadastro').addEventListener('click', (e) => { e.preventDefault(); mostrar('cadastro'); });
    document.getElementById('irParaLogin').addEventListener('click', (e) => { e.preventDefault(); mostrar('login'); });
    if (location.hash === '#cadastro') mostrar('cadastro');

    /* mostrar / ocultar senha */
    document.querySelectorAll('.mostrar-senha').forEach((botao) => {
        botao.addEventListener('click', () => {
            const campo = document.getElementById(botao.dataset.alvo);
            const visivel = campo.type === 'text';
            campo.type = visivel ? 'password' : 'text';
            botao.setAttribute('aria-pressed', String(!visivel));
            botao.setAttribute('aria-label', visivel ? 'Mostrar senha' : 'Ocultar senha');
            botao.firstElementChild.className = visivel ? 'bi bi-eye' : 'bi bi-eye-slash';
        });
    });

    /* confirmação de senha */
    const novaSenha = document.getElementById('novaSenha');
    const confirma = document.getElementById('confirmaSenha');
    function checarConfirmacao() {
        confirma.setCustomValidity(confirma.value && confirma.value !== novaSenha.value ? 'diferente' : '');
    }
    novaSenha.addEventListener('input', checarConfirmacao);
    confirma.addEventListener('input', checarConfirmacao);

    async function enviar(form, botao, url, textoOcupado, textoNormal) {
        botao.disabled = true;
        botao.textContent = textoOcupado;
        try {
            const resposta = await fetch(url, { method: 'POST', body: new FormData(form), headers: {'X-Requested-With':'XMLHttpRequest'} });
            const resultado = await resposta.json();
            status(form, resultado.mensagem || 'Não foi possível concluir a operação.', resultado.sucesso ? 'success' : 'danger');
            if (resultado.sucesso) {
                if (resultado.redirecionar) window.location.href = resultado.redirecionar;
                else if (url.includes('cadastrar')) { form.reset(); mostrar('login'); }
            }
        } catch (erro) { status(form, 'Erro ao comunicar com o servidor. Tente novamente.', 'danger'); }
        finally { botao.disabled = false; botao.textContent = textoNormal; }
    }

    function status(form, mensagem, tipo) {
        const area = form.querySelector('.login-status');
        area.className = `login-status alert alert-${tipo} py-2`;
        area.textContent = mensagem;
    }
    function validar(form) {
        form.classList.add('was-validated');
        if (!form.checkValidity()) { form.querySelector(':invalid').focus(); return false; }
        return true;
    }
    login.addEventListener('submit', (e) => {
        e.preventDefault();
        if (!validar(login)) return;
        enviar(login, btnEntrar, 'auth/login.php', 'Verificando...', modoLogin.value === 'admin' ? 'Entrar no painel administrativo' : 'Entrar');
    });
    cadastro.addEventListener('submit', (e) => {
        e.preventDefault(); checarConfirmacao();
        if (!validar(cadastro)) return;
        enviar(cadastro, btnCadastrar, 'auth/cadastrar.php', 'Criando conta...', 'Cadastrar');
    });
});
