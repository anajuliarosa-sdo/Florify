<?php
/* Página de compra — fica dentro de "Produtos".
   Uso: comprar.php?produto=<slug>  (os slugs estão em partes/dados_produtos.php)

   O pedido é validado e calculado AQUI no servidor (o preço nunca vem do navegador).
   Ainda não grava em banco nem cobra pagamento: ao confirmar, o cliente envia o
   pedido para o WhatsApp da loja. Quando houver banco/gateway, o ponto de integração
   está marcado com "INTEGRAÇÃO" mais abaixo. */

date_default_timezone_set('America/Sao_Paulo');
require_once __DIR__ . '/partes/dados_produtos.php';
require_once __DIR__ . '/config/db.php';
if (empty($_SESSION['id_usuario']) || ($_SESSION['tipo_perfil'] ?? '') !== 'CLIENTE') { header('Location: login.php'); exit; }
$idProduto = (int)($_POST['id_produto'] ?? $_GET['id'] ?? 0);
$stProduto = $pdo->prepare('SELECT * FROM produtos WHERE id_produto = ?'); $stProduto->execute([$idProduto]); $produtoDB = $stProduto->fetch();
if (!$produtoDB) { header('Location: itens.php'); exit; }
$p = ['nome'=>$produtoDB['nome_produto'], 'descricao'=>$produtoDB['descricao'], 'preco'=>(float)$produtoDB['preco'], 'imagem'=>$produtoDB['imagem']];

$ufs = ['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'];
$periodos = ['manha' => 'Manhã (08h às 12h)', 'tarde' => 'Tarde (13h às 18h)'];
$pagamentos = ['pix' => 'Pix', 'cartao' => 'Cartão de crédito', 'boleto' => 'Boleto bancário'];

$dados = [
    'quantidade' => '1', 'comprador' => '', 'email' => '', 'mensagem' => '',
    'destinatario' => '', 'telefone' => '', 'data' => '', 'periodo' => 'manha',
    'cep' => '', 'endereco' => '', 'numero' => '', 'complemento' => '',
    'bairro' => '', 'cidade' => '', 'uf' => 'MG', 'pagamento' => 'pix',
];
$erros = [];
$pedido = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($dados as $campo => $padrao) {
        $valor = $_POST[$campo] ?? $padrao;
        $dados[$campo] = is_string($valor) ? trim($valor) : $padrao;
    }

    $qtd = filter_var($dados['quantidade'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 20]]);
    if ($qtd === false) $erros['quantidade'] = 'Escolha de 1 a 20 unidades.';

    if ((int)$produtoDB['estoque'] < (int)$dados['quantidade'] || (int)$dados['quantidade'] < 1) $erros['quantidade'] = 'Estoque insuficiente para essa quantidade.';
    if (strlen($dados['comprador']) < 3) $erros['comprador'] = 'Informe seu nome completo.';
    if (!filter_var($dados['email'], FILTER_VALIDATE_EMAIL)) $erros['email'] = 'Informe um e-mail válido.';
    if (strlen($dados['mensagem']) > 200) $erros['mensagem'] = 'A mensagem pode ter até 200 caracteres.';

    if (strlen($dados['destinatario']) < 3) $erros['destinatario'] = 'Informe o nome de quem vai receber.';
    $telefone = preg_replace('/\D+/', '', $dados['telefone']);
    if (strlen($telefone) < 10 || strlen($telefone) > 11) $erros['telefone'] = 'Informe um telefone com DDD.';

    $data = DateTime::createFromFormat('!Y-m-d', $dados['data']);
    $amanha = new DateTime('tomorrow');
    $limite = (new DateTime('today'))->modify('+90 days');
    if (!$data || $data->format('Y-m-d') !== $dados['data']) {
        $erros['data'] = 'Escolha a data da entrega.';
    } elseif ($data < $amanha || $data > $limite) {
        $erros['data'] = 'Escolha uma data entre amanhã e os próximos 90 dias.';
    } elseif ($data->format('w') === '0') {
        $erros['data'] = 'Não fazemos entregas aos domingos.';
    }
    if (!isset($periodos[$dados['periodo']])) {
        $erros['periodo'] = 'Escolha o período.';
    } elseif ($data && $data->format('w') === '6' && $dados['periodo'] === 'tarde') {
        $erros['periodo'] = 'Aos sábados entregamos somente pela manhã.';
    }

    $cep = preg_replace('/\D+/', '', $dados['cep']);
    if (strlen($cep) !== 8) $erros['cep'] = 'Informe um CEP com 8 dígitos.';
    if ($dados['endereco'] === '') $erros['endereco'] = 'Informe a rua ou avenida.';
    if ($dados['numero'] === '') $erros['numero'] = 'Informe o número.';
    if ($dados['bairro'] === '') $erros['bairro'] = 'Informe o bairro.';
    if ($dados['cidade'] === '') $erros['cidade'] = 'Informe a cidade.';
    if (!in_array($dados['uf'], $ufs, true)) $erros['uf'] = 'Escolha o estado.';
    if (!isset($pagamentos[$dados['pagamento']])) $erros['pagamento'] = 'Escolha a forma de pagamento.';

    if (!$erros) {
        $subtotal = $p['preco'] * $qtd;
        $total = $subtotal + FRETE_PADRAO;
        $pedido = [
            'numero'   => 'FL' . date('ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5)),
            'qtd'      => $qtd,
            'subtotal' => $subtotal,
            'frete'    => FRETE_PADRAO,
            'total'    => $total,
        ];

        $enderecoCompleto = $dados['endereco'] . ', ' . $dados['numero'] . ($dados['complemento'] !== '' ? ' - '.$dados['complemento'] : '') . ' - '.$dados['bairro'].', '.$dados['cidade'].'/'.$dados['uf'].' - CEP '.$dados['cep'];
        try {
            $pdo->beginTransaction();
            $lock=$pdo->prepare('SELECT estoque FROM produtos WHERE id_produto=? FOR UPDATE'); $lock->execute([$idProduto]); $estoqueAtual=(int)$lock->fetchColumn();
            if ($estoqueAtual < $qtd) throw new RuntimeException('Estoque insuficiente.');
            $ins=$pdo->prepare("INSERT INTO pedido (quantidade,valor_total,endereco_entrega,data_entrega,mensagem_cartao,forma_pagamento,status_pedido,data_pedido,id_cliente,id_produto) VALUES (?,?,?,?,?,?,'Pendente',NOW(),?,?)");
            $ins->execute([$qtd,$total,$enderecoCompleto,$dados['data'],$dados['mensagem'],$pagamentos[$dados['pagamento']],$_SESSION['id_usuario'],$idProduto]);
            $pdo->prepare('UPDATE produtos SET estoque=estoque-? WHERE id_produto=?')->execute([$qtd,$idProduto]);
            $pedido['id_pedido']=(int)$pdo->lastInsertId(); $pdo->commit();
        } catch (Throwable $e) { if($pdo->inTransaction()) $pdo->rollBack(); $erros['quantidade']=$e->getMessage(); $pedido=null; }
        if (!$pedido) { /* A validação abaixo mantém o formulário aberto quando o estoque muda. */ } else {

        $linhas = [
            "Olá, Florify! Quero confirmar o pedido {$pedido['numero']}:",
            '',
            "{$qtd}x {$p['nome']} — " . preco_br($total) . ' (com entrega)',
            'Pagamento: ' . $pagamentos[$dados['pagamento']],
            '',
            'Comprador: ' . $dados['comprador'] . ' — ' . $dados['email'],
            'Entrega para: ' . $dados['destinatario'] . ' (' . $dados['telefone'] . ')',
            'Data: ' . $data->format('d/m/Y') . ' — ' . $periodos[$dados['periodo']],
            "Endereço: {$dados['endereco']}, {$dados['numero']}"
                . ($dados['complemento'] !== '' ? " ({$dados['complemento']})" : '')
                . " — {$dados['bairro']}, {$dados['cidade']}/{$dados['uf']} — CEP {$dados['cep']}",
        ];
        if ($dados['mensagem'] !== '') {
            $linhas[] = '';
            $linhas[] = 'Mensagem do cartão: ' . $dados['mensagem'];
        }
        if (WHATSAPP_LOJA !== '') {
            $pedido['whatsapp'] = 'https://wa.me/' . WHATSAPP_LOJA . '?text=' . rawurlencode(implode("\n", $linhas));
        }
        }
    }
}

/* helpers de formulário */
function campo_classe(array $erros, string $campo): string { return isset($erros[$campo]) ? ' is-invalid' : ''; }
function campo_erro(array $erros, string $campo): string {
    return isset($erros[$campo]) ? '<div class="invalid-feedback d-block">' . e($erros[$campo]) . '</div>' : '';
}

$titulo    = $p['nome'] . ' — Comprar | Florify';
$descricao = 'Compre ' . $p['nome'] . ' na Florify. ' . $p['descricao'];
$scriptsExtras = ['javascript/comprar.js'];

require __DIR__ . '/partes/head.php';
require __DIR__ . '/partes/header.php';
?>
<main class="secao container comprar">

    <nav aria-label="Você está em" class="mb-4">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="itens.php">Produtos</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= e($p['nome']) ?></li>
        </ol>
    </nav>

<?php if ($pedido): ?>
    <!-- ===================== CONFIRMAÇÃO ===================== -->
    <section class="confirmacao text-center mx-auto" aria-labelledby="tituloConfirmacao">
        <i class="bi bi-check-circle-fill confirmacao-icone" aria-hidden="true"></i>
        <h1 class="titulo-script grande" id="tituloConfirmacao">Pedido recebido!</h1>
        <p class="texto-corpo mb-1">Número do pedido: <strong><?= e($pedido['numero']) ?></strong></p>
        <p class="texto-corpo">Para finalizar, envie o pedido para o nosso WhatsApp. Nossa equipe confirma o pagamento
            (<?= e($pagamentos[$dados['pagamento']]) ?>) e agenda a entrega.</p>

        <div class="resumo-linhas text-start my-4">
            <div><span><?= (int) $pedido['qtd'] ?>x <?= e($p['nome']) ?></span><span><?= preco_br($pedido['subtotal']) ?></span></div>
            <div><span>Entrega</span><span><?= preco_br($pedido['frete']) ?></span></div>
            <div class="total"><span>Total</span><span><?= preco_br($pedido['total']) ?></span></div>
        </div>

        <?php if (!empty($pedido['whatsapp'])): ?>
            <a class="btn btn-florify btn-lg rounded-pill px-5" href="<?= e($pedido['whatsapp']) ?>" target="_blank" rel="noopener">
                <i class="bi bi-whatsapp me-2" aria-hidden="true"></i>Enviar pedido pelo WhatsApp
            </a>
        <?php else: ?>
            <p class="alert alert-info">Os canais de contato da loja ainda não foram configurados.</p>
        <?php endif; ?>
        <p class="mt-4"><a href="itens.php">← Continuar vendo produtos</a></p>
    </section>

<?php else: ?>
    <!-- ======================= COMPRA ======================= -->
    <div class="row g-4 g-lg-5">
        <div class="col-lg-7 order-2 order-lg-1">
            <h1 class="titulo-script mb-4">Finalizar compra</h1>

            <?php if ($erros): ?>
                <div class="alert alert-danger" role="alert" id="alertaErros">
                    Confira os campos destacados abaixo para continuar.
                </div>
            <?php endif; ?>

            <form id="formCompra" method="post" action="comprar.php?id=<?= (int)$idProduto ?>"
                  data-preco="<?= number_format($p['preco'], 2, '.', '') ?>"
                  data-frete="<?= number_format(FRETE_PADRAO, 2, '.', '') ?>">
                <input type="hidden" name="id_produto" value="<?= (int)$idProduto ?>">

                <fieldset class="passo">
                    <legend><span class="passo-num">1</span> Seu pedido</legend>
                    <div class="row g-3">
                        <div class="col-sm-4">
                            <label class="form-label" for="quantidade">Quantidade</label>
                            <div class="input-group stepper">
                                <button class="btn btn-outline-dark" type="button" data-passo="-1" aria-label="Diminuir quantidade">−</button>
                                <input class="form-control text-center<?= campo_classe($erros, 'quantidade') ?>" type="number" id="quantidade" name="quantidade"
                                       min="1" max="20" value="<?= e($dados['quantidade']) ?>" required inputmode="numeric">
                                <button class="btn btn-outline-dark" type="button" data-passo="1" aria-label="Aumentar quantidade">+</button>
                            </div>
                            <?= campo_erro($erros, 'quantidade') ?>
                        </div>
                        <div class="col-sm-8">
                            <label class="form-label" for="mensagem">Mensagem para o cartão <span class="text-muted">(opcional)</span></label>
                            <textarea class="form-control<?= campo_classe($erros, 'mensagem') ?>" id="mensagem" name="mensagem" rows="2"
                                      maxlength="200" placeholder="Ex.: Parabéns! Com carinho, Ana"><?= e($dados['mensagem']) ?></textarea>
                            <div class="form-text"><span id="contador">0</span>/200 caracteres</div>
                            <?= campo_erro($erros, 'mensagem') ?>
                        </div>
                    </div>
                </fieldset>

                <fieldset class="passo">
                    <legend><span class="passo-num">2</span> Seus dados</legend>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="comprador">Nome completo</label>
                            <input class="form-control<?= campo_classe($erros, 'comprador') ?>" id="comprador" name="comprador" autocomplete="name"
                                   value="<?= e($dados['comprador']) ?>" required minlength="3">
                            <?= campo_erro($erros, 'comprador') ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="email">E-mail</label>
                            <input class="form-control<?= campo_classe($erros, 'email') ?>" type="email" id="email" name="email" autocomplete="email"
                                   value="<?= e($dados['email']) ?>" required>
                            <?= campo_erro($erros, 'email') ?>
                        </div>
                    </div>
                </fieldset>

                <fieldset class="passo">
                    <legend><span class="passo-num">3</span> Entrega</legend>
                    <div class="row g-3">
                        <div class="col-md-7">
                            <label class="form-label" for="destinatario">Quem vai receber</label>
                            <input class="form-control<?= campo_classe($erros, 'destinatario') ?>" id="destinatario" name="destinatario"
                                   value="<?= e($dados['destinatario']) ?>" required minlength="3">
                            <?= campo_erro($erros, 'destinatario') ?>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label" for="telefone">Telefone de contato</label>
                            <input class="form-control<?= campo_classe($erros, 'telefone') ?>" type="tel" id="telefone" name="telefone" autocomplete="tel"
                                   placeholder="(35) 99999-9999" value="<?= e($dados['telefone']) ?>" required inputmode="tel">
                            <?= campo_erro($erros, 'telefone') ?>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="data">Data da entrega</label>
                            <input class="form-control<?= campo_classe($erros, 'data') ?>" type="date" id="data" name="data"
                                   min="<?= date('Y-m-d', strtotime('tomorrow')) ?>" max="<?= date('Y-m-d', strtotime('+90 days')) ?>"
                                   value="<?= e($dados['data']) ?>" required>
                            <div class="form-text">Entregamos de segunda a sábado.</div>
                            <?= campo_erro($erros, 'data') ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="periodo">Período</label>
                            <select class="form-select<?= campo_classe($erros, 'periodo') ?>" id="periodo" name="periodo" required>
                                <?php foreach ($periodos as $valor => $rotulo): ?>
                                    <option value="<?= $valor ?>"<?= $dados['periodo'] === $valor ? ' selected' : '' ?>><?= e($rotulo) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Aos sábados, apenas pela manhã.</div>
                            <?= campo_erro($erros, 'periodo') ?>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="cep">CEP</label>
                            <input class="form-control<?= campo_classe($erros, 'cep') ?>" id="cep" name="cep" autocomplete="postal-code"
                                   placeholder="00000-000" value="<?= e($dados['cep']) ?>" required inputmode="numeric">
                            <?= campo_erro($erros, 'cep') ?>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label" for="endereco">Rua / Avenida</label>
                            <input class="form-control<?= campo_classe($erros, 'endereco') ?>" id="endereco" name="endereco" autocomplete="address-line1"
                                   value="<?= e($dados['endereco']) ?>" required>
                            <?= campo_erro($erros, 'endereco') ?>
                        </div>
                        <div class="col-4 col-md-3">
                            <label class="form-label" for="numero">Número</label>
                            <input class="form-control<?= campo_classe($erros, 'numero') ?>" id="numero" name="numero"
                                   value="<?= e($dados['numero']) ?>" required>
                            <?= campo_erro($erros, 'numero') ?>
                        </div>
                        <div class="col-8 col-md-9">
                            <label class="form-label" for="complemento">Complemento <span class="text-muted">(opcional)</span></label>
                            <input class="form-control" id="complemento" name="complemento" placeholder="Apto, bloco, referência..."
                                   value="<?= e($dados['complemento']) ?>">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label" for="bairro">Bairro</label>
                            <input class="form-control<?= campo_classe($erros, 'bairro') ?>" id="bairro" name="bairro"
                                   value="<?= e($dados['bairro']) ?>" required>
                            <?= campo_erro($erros, 'bairro') ?>
                        </div>
                        <div class="col-8 col-md-5">
                            <label class="form-label" for="cidade">Cidade</label>
                            <input class="form-control<?= campo_classe($erros, 'cidade') ?>" id="cidade" name="cidade" autocomplete="address-level2"
                                   value="<?= e($dados['cidade']) ?>" required>
                            <?= campo_erro($erros, 'cidade') ?>
                        </div>
                        <div class="col-4 col-md-2">
                            <label class="form-label" for="uf">UF</label>
                            <select class="form-select<?= campo_classe($erros, 'uf') ?>" id="uf" name="uf" required>
                                <?php foreach ($ufs as $uf): ?>
                                    <option<?= $dados['uf'] === $uf ? ' selected' : '' ?>><?= $uf ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?= campo_erro($erros, 'uf') ?>
                        </div>
                    </div>
                </fieldset>

                <fieldset class="passo">
                    <legend><span class="passo-num">4</span> Pagamento</legend>
                    <div class="row g-3">
                        <?php foreach ($pagamentos as $valor => $rotulo): ?>
                            <div class="col-sm-4">
                                <input class="btn-check" type="radio" name="pagamento" id="pag-<?= $valor ?>" value="<?= $valor ?>"
                                       <?= $dados['pagamento'] === $valor ? 'checked' : '' ?> required>
                                <label class="opcao-pagamento" for="pag-<?= $valor ?>"><?= e($rotulo) ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?= campo_erro($erros, 'pagamento') ?>
                    <p class="form-text mt-3 mb-0">Nenhum dado de cartão é pedido aqui: o pagamento é combinado com a nossa equipe ao confirmar o pedido.</p>
                </fieldset>

                <button class="btn btn-florify btn-lg rounded-pill px-5 d-lg-none w-100" type="submit">Confirmar pedido</button>
            </form>
        </div>

        <!-- Resumo -->
        <aside class="col-lg-5 order-1 order-lg-2" aria-label="Resumo do pedido">
            <div class="resumo">
                <img src="imagens/<?= e($p['imagem']) ?>">
                <div class="resumo-corpo">
                    <h2 class="h4 mb-1"><?= e($p['nome']) ?></h2>
                    <p class="texto-corpo mb-3"><?= e($p['descricao']) ?></p>

                    <div class="resumo-linhas" aria-live="polite">
                        <div><span><span id="resumoQtd">1</span>x <?= preco_br($p['preco']) ?></span><span id="resumoSubtotal"><?= preco_br($p['preco']) ?></span></div>
                        <div><span>Entrega</span><span id="resumoFrete"><?= preco_br(FRETE_PADRAO) ?></span></div>
                        <div class="total"><span>Total</span><span id="resumoTotal"><?= preco_br($p['preco'] + FRETE_PADRAO) ?></span></div>
                    </div>

                    <button class="btn btn-florify btn-lg rounded-pill w-100 mt-3 d-none d-lg-block" type="submit" form="formCompra">Confirmar pedido</button>
                    <p class="form-text text-center mt-2 mb-0"><i class="bi bi-flower1" aria-hidden="true"></i> Garantia de frescor: trocamos sem custo se murchar antes do prazo.</p>
                </div>
            </div>
        </aside>
    </div>
<?php endif; ?>
</main>
<?php
require __DIR__ . '/partes/footer.php';
require __DIR__ . '/partes/scripts.php';
