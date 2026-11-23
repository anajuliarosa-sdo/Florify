<?php
$paginaAtual = basename($_SERVER['SCRIPT_NAME'] ?? 'index.php');
if ($paginaAtual === 'comprar.php') $paginaAtual = 'itens.php';   // a compra fica dentro de Produtos
$menu = [
    'index.php'       => 'Início',
    'itens.php'       => 'Produtos',
    'diferentes.php'  => 'Diferenciais',
    'visualizar.php'  => 'Visualizar com I.A.',
];
?>
<header class="cabecalho sticky-top">
    <div class="cabecalhoTopo">
        <div class="container-fluid">
            <div class="d-flex align-items-center gap-3">
                <a class="marca" href="index.php" aria-label="Florify — página inicial">
                    <img src="imagens/logo4.png" alt="Florify">
                </a>

                <nav class="d-none d-md-block ms-3" aria-label="Principal">
                    <ul class="navbar-nav flex-row gap-4">
                        <?php foreach ($menu as $arquivo => $rotulo): ?>
                            <li class="nav-item">
                                <a class="nav-link<?= $paginaAtual === $arquivo ? ' active' : '' ?>"
                                   href="<?= $arquivo ?>"<?= $paginaAtual === $arquivo ? ' aria-current="page"' : '' ?>><?= $rotulo ?></a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </nav>

                <div class="pesquisar" role="search">
                    <i class="bi bi-search" aria-hidden="true"></i>
                    <input type="search" placeholder="Buscar" aria-label="Buscar no site">
                </div>

                <?php if (!empty($_SESSION['id_usuario'])): ?>
                    <div class="d-flex align-items-center gap-2">
                        <span class="small">Olá, <?= htmlspecialchars(explode(' ', trim($_SESSION['nome']))[0], ENT_QUOTES, 'UTF-8') ?></span>
                        <?php if (($_SESSION['tipo_perfil'] ?? '') === 'ADMIN'): ?><a href="admin.php" class="loginIcon" aria-label="Administrador"><i class="bi bi-gear"></i></a><?php else: ?><a href="pedidos.php" class="loginIcon" aria-label="Meus pedidos"><i class="bi bi-bag"></i></a><?php endif; ?>
                        <a href="logout.php" class="loginIcon" aria-label="Sair"><i class="bi bi-box-arrow-right"></i></a>
                    </div>
                <?php else: ?>
                    <a href="login.php" class="loginIcon d-none d-md-inline" aria-label="Entrar na sua conta"><i class="bi bi-person-circle"></i></a>
                <?php endif; ?>

                <button class="navbar-toggler d-md-none" type="button" data-bs-toggle="collapse"
                        data-bs-target="#menuMobile" aria-controls="menuMobile" aria-expanded="false"
                        aria-label="Abrir menu">
                    <span class="navbar-toggler-icon"></span>
                </button>
            </div>

            <!-- Menu do celular: abre abaixo do cabeçalho, em largura total -->
            <nav class="collapse d-md-none menuMobile" id="menuMobile" aria-label="Menu">
                <ul class="navbar-nav">
                    <?php foreach ($menu as $arquivo => $rotulo): ?>
                        <li class="nav-item">
                            <a class="nav-link<?= $paginaAtual === $arquivo ? ' active' : '' ?>"
                               href="<?= $arquivo ?>"<?= $paginaAtual === $arquivo ? ' aria-current="page"' : '' ?>><?= $rotulo ?></a>
                        </li>
                    <?php endforeach; ?>
                    <?php if (empty($_SESSION['id_usuario'])): ?><li class="nav-item"><a class="nav-link" href="login.php">Entrar / Cadastrar</a></li><?php else: ?><li class="nav-item"><a class="nav-link" href="<?= ($_SESSION['tipo_perfil'] ?? '') === 'ADMIN' ? 'admin.php' : 'pedidos.php' ?>">Minha área</a></li><li class="nav-item"><a class="nav-link" href="logout.php">Sair</a></li><?php endif; ?>
                </ul>
            </nav>
        </div>
    </div>
</header>
