<?php
$titulo    = 'Entrar — Florify';
$descricao = 'Entre na sua conta Florify ou crie um cadastro para acompanhar seus pedidos.';
$scriptsExtras = ['javascript/login.js'];

require __DIR__ . '/partes/head.php';
require __DIR__ . '/partes/header.php';
?>
<main class="login-pagina">
    <div class="login-card" id="cartaoLogin">
        <a href="index.php" class="login-logo" aria-label="Florify — página inicial">
            <img src="imagens/logo4.png" alt="Florify">
        </a>

        <!-- ============ ENTRAR ============ -->
        <section id="conteudoLogin" aria-labelledby="tituloLogin">
            <h1 class="titulo-script" id="tituloLogin">Bem-vindo(a) de volta</h1>
            <p class="texto-corpo mb-4" id="descricaoLogin">Entre para acompanhar seus pedidos e comprar mais rápido.</p>

            <form id="login" novalidate>
                <input type="hidden" id="modoLogin" name="modo" value="cliente">
                <div class="form-floating mb-3">
                    <input type="text" class="form-control" id="usuario" name="usuario" placeholder="E-mail ou usuário"
                           autocomplete="username" required>
                    <label for="usuario">E-mail ou usuário</label>
                    <div class="invalid-feedback">Informe seu e-mail ou usuário.</div>
                </div>

                <div class="input-group mb-3 campo-senha">
                    <div class="form-floating flex-grow-1">
                        <input type="password" class="form-control" id="senha" name="senha" placeholder="Senha"
                               autocomplete="current-password" required>
                        <label for="senha">Senha</label>
                    </div>
                    <button class="btn btn-outline-dark mostrar-senha" type="button" data-alvo="senha" aria-label="Mostrar senha" aria-pressed="false">
                        <i class="bi bi-eye" aria-hidden="true"></i>
                    </button>
                    <div class="invalid-feedback">Informe sua senha.</div>
                </div>

                <div class="login-status" role="status" aria-live="polite"></div>
                <button type="submit" id="btnEntrar" class="btn btn-florify btn-lg rounded-pill w-100">Entrar</button>
                <button type="button" id="btnAcessoAdmin" class="btn btn-outline-dark rounded-pill w-100 mt-2">
                    <i class="bi bi-shield-lock" aria-hidden="true"></i> Entrar como administrador
                </button>
            </form>

            <p class="text-center mt-4 mb-0" id="linkCadastro">Ainda não tem conta? <a href="#" id="irParaCadastro" style="color: #ff7519;">Cadastre-se</a></p>
        </section>

        <!-- ============ CADASTRAR ============ -->
        <section id="conteudoCadastro" aria-labelledby="tituloCadastro" hidden>
            <h1 class="titulo-script" id="tituloCadastro">Criar conta</h1>
            <p class="texto-corpo mb-4">Leva menos de um minuto.</p>

            <form id="cadastro" novalidate>
                <div class="form-floating mb-3">
                    <input type="text" class="form-control" id="novoUsuario" name="nome" placeholder="Nome" autocomplete="name" required minlength="3">
                    <label for="novoUsuario">Nome completo</label>
                    <div class="invalid-feedback">Informe seu nome (mínimo 3 letras).</div>
                </div>

                <div class="form-floating mb-3">
                    <input type="email" class="form-control" id="novoEmail" name="email" placeholder="E-mail" autocomplete="email" required>
                    <label for="novoEmail">E-mail</label>
                    <div class="invalid-feedback">Informe um e-mail válido.</div>
                </div>

                <div class="input-group mb-3 campo-senha">
                    <div class="form-floating flex-grow-1">
                        <input type="password" class="form-control" id="novaSenha" name="senha" placeholder="Senha"
                               autocomplete="new-password" required minlength="8">
                        <label for="novaSenha">Senha (mínimo 8 caracteres)</label>
                    </div>
                    <button class="btn btn-outline-dark mostrar-senha" type="button" data-alvo="novaSenha" aria-label="Mostrar senha" aria-pressed="false">
                        <i class="bi bi-eye" aria-hidden="true"></i>
                    </button>
                    <div class="invalid-feedback">A senha precisa ter pelo menos 8 caracteres.</div>
                </div>

                <div class="form-floating mb-3">
                    <input type="password" class="form-control" id="confirmaSenha" name="confirma" placeholder="Confirmar senha"
                           autocomplete="new-password" required>
                    <label for="confirmaSenha">Confirmar senha</label>
                    <div class="invalid-feedback">As senhas não coincidem.</div>
                </div>

                <div class="login-status" role="status" aria-live="polite"></div>
                <button type="submit" id="btnCadastrar" class="btn btn-florify btn-lg rounded-pill w-100">Cadastrar</button>
            </form>

            <p class="text-center mt-4 mb-0">Já tem conta? <a href="#" id="irParaLogin">Entrar</a></p>
        </section>
    </div>
</main>
<?php
require __DIR__ . '/partes/footer.php';
require __DIR__ . '/partes/scripts.php';
