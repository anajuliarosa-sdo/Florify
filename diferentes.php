<?php
$titulo    = 'Nossos Diferenciais — Florify';
$descricao = 'Veja por que escolher a Florify: curadoria de espécies raras, arranjos autorais e atendimento sob medida.';



/* [imagem, título, texto resumido, detalhado]  — conteúdo provisório: troque pelos diferenciais reais */
$blocos = [
    [   'compostagem',
        'Retorno Consiente',
        'Beleza que se renova, flores que continuam.',
        'Acreditamos que a beleza das flores não precisa ter um fim. Por isso, a Florify desenvolveu uma iniciativa sustentável que incentiva o reaproveitamento de flores utilizadas em eventos, celebrações e projetos de decoração, transformando o que seria descartado em novas possibilidades de florescimento.',
        'Após a utilização, as flores podem ser devolvidas à nossa floricultura, onde passam por um processo de reaproveitamento e transformação em adubo orgânico, utilizado no cultivo de novas espécies. Como forma de incentivo, oferecemos ao cliente um desconto proporcional à quantidade de flores devolvidas. Assim, além de contribuir para a redução do desperdício, cada cliente participa de um ciclo de cuidado com a natureza, no qual a beleza de hoje ajuda a cultivar as flores de amanhã.',
    ],
    [   'eternizacao',
        'Eternização de Flores',
        'Algumas memórias merecem florescer para sempre.',
        'Existem flores que carregam histórias, sentimentos e lembranças que merecem ser preservadas. Pensando nisso, a Florify oferece um serviço exclusivo de eternização floral, transformando flores de momentos especiais em peças decorativas permanentes, capazes de conservar a delicadeza e o significado de cada recordação.',
        'Por meio de técnicas de preservação e conservação, eternizamos flores inteiras ou pétalas de buquês especiais, utilizando diferentes formatos e materiais, como quadros, peças em acrílico e composições em resina. Cada criação é desenvolvida com atenção aos detalhes e à história por trás das flores, resultando em uma peça personalizada e única. Seja para preservar as flores de um casamento, aniversário, pedido especial ou qualquer outra ocasião significativa, oferecemos uma maneira elegante de transformar momentos passageiros em lembranças duradouras.',
    ],
    [   'iafoto',
        'Visualização com I.A.',
        'Antes de florescer no ambiente, visualize cada detalhe.',
        'Escolher o arranjo floral ideal também significa imaginar como ele irá compor o espaço. Para tornar essa experiência ainda mais exclusiva e personalizada, a Florify utiliza inteligência artificial para proporcionar uma pré-visualização dos arranjos e decorações florais diretamente no ambiente desejado, aproximando a escolha do resultado final.',
        'Através de uma fotografia do espaço, o cliente poderá selecionar o arranjo, a composição floral ou o estilo de decoração que deseja visualizar. Nossa tecnologia utilizará essas informações para gerar uma simulação digital, inserindo a proposta escolhida no ambiente fotografado. Dessa maneira, será possível observar como as flores se harmonizam com os móveis, as cores, a iluminação e os demais elementos do espaço antes de realizar a compra ou contratar a decoração. A ferramenta proporciona maior segurança na escolha, facilita a tomada de decisões e torna a experiência de compra mais interativa, moderna e personalizada.',
    ],
];

$scriptsExtras = ['javascript/diferenciais.js'];

require __DIR__ . '/partes/head.php';
require __DIR__ . '/partes/header.php';
?>
<main class="secao container">
    <div class="text-center mb-5">
        <span class="eyebrow">Nossos Diferenciais</span>
        <h1 class="titulo-script grande">Por que escolher a Florify?</h1>
    </div>

    <?php foreach ($blocos as $i => [$imagem, $nomeBloco, $subtitulo, $texto, $detalhe]): ?>
        <div class="row align-items-center justify-content-center g-4 g-md-5 linha-destaque<?= $i % 2 ? ' invertida' : '' ?>">
            <div class="col-md-6 col-texto">
                <h2 class="eyebrow"><?= htmlspecialchars($nomeBloco) ?></h2>
                <p class="texto-corpo"><?= htmlspecialchars($texto) ?></p>
                <button type="button" class="btn btn-florify rounded-pill px-4 abrirDiferencial"
                        data-titulo="<?= htmlspecialchars($subtitulo) ?>"
                        data-texto="<?= htmlspecialchars($detalhe) ?>"
                        data-imagem="imagens/<?= $imagem ?>.jpeg">Saiba mais</button>
                        
            </div>
            <div class="col-md-6 col-imagem text-center">
                <img src="imagens/<?= $imagem ?>.jpeg" alt="<?= htmlspecialchars($nomeBloco) ?>" class="img-destaque" loading="lazy">
            </div>
        </div>
    <?php endforeach; ?>
</main>

<div class="overlayDiferencial" id="overlayDiferencial" role="dialog" aria-modal="true" aria-labelledby="modalTitulo" hidden>
    <div class="modalDiferencial">
        <button type="button" class="fecharModal" aria-label="Fechar">&times;</button>
        <img id="modalImagem" src="" alt="">
        <h2 id="modalTitulo"></h2>
        <p id="modalTexto"></p>
    </div>
</div>
<?php
require __DIR__ . '/partes/footer.php';
require __DIR__ . '/partes/scripts.php';
