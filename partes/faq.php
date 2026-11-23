<?php
$perguntas = [
    ['Quais são as formas de pagamento aceitas?',
     'Aceitamos todos os principais cartões de crédito (Visa, MasterCard, Elo, American Express), Pix e boleto bancário com compensação em até 48 horas.'],
    ['Como as flores são transportadas para garantir a máxima sustentabilidade e frescor?',
     'Nossos arranjos e buquês são transportados em veículos climatizados exclusivos e protegidos por embalagens térmicas estruturadas. Isso garante que a temperatura ideal seja mantida desde o nosso ateliê até o momento da entrega, preservando o frescor, o perfume e a integridade de cada pétala.'],
    ['É possível personalizar o vaso ou incluir um presente de luxo no arranjo?',
     'Com certeza. Oferecemos uma curadoria de vasos de cristal, cerâmica assinada e caixas cartonadas premium. Além disso, você pode complementar sua surpresa adicionando chocolates finos artesanais, velas aromáticas exclusivas ou champanhes selecionados diretamente em nosso catálogo de adicionais.'],
    ['Como devo cuidar do meu arranjo para que ele dure mais tempo?',
     'Junto com cada arranjo enviado, incluímos um guia de cuidados específicos e um sachê de nutrientes importados para a água. Recomendamos manter as flores em locais frescos, longe da luz solar direta e do ar-condicionado forte, além de trocar a água a cada dois dias cortando as hastes em diagonal.'],
    ['Desenvolvem projetos florais exclusivos para casamentos e outros eventos privados?',
     'Com certeza. Nosso departamento de design de eventos cria projetos botânicos sob medida para mini-weddings, jantares e recepções corporativas. O processo envolve uma consultoria com nosso florista-chefe para alinhar a paleta de cores, o estilo do mobiliário e a atmosfera desejada.'],
];
?>
<section class="faq secao secao-verde" id="faq">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">

                <div class="text-center mb-5">
                    <span class="eyebrow">FAQ</span>
                    <h2 class="titulo-script">Tire suas dúvidas</h2>
                </div>

                <div class="accordion" id="faqAccordion">
                    <?php foreach ($perguntas as $i => [$pergunta, $resposta]): $n = $i + 1; ?>
                        <div class="accordion-item mb-4 shadow">
                            <h3 class="accordion-header" id="pergunta<?= $n ?>">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                        data-bs-target="#resposta<?= $n ?>" aria-expanded="false"
                                        aria-controls="resposta<?= $n ?>">
                                    <?= htmlspecialchars($pergunta) ?>
                                </button>
                            </h3>
                            <div id="resposta<?= $n ?>" class="accordion-collapse collapse"
                                 aria-labelledby="pergunta<?= $n ?>" data-bs-parent="#faqAccordion">
                                <div class="accordion-body"><?= htmlspecialchars($resposta) ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

            </div>
        </div>
    </div>
</section>
