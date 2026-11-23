<?php
/*
    visualizar.php — Visualização com I.A.
    O cliente envia a foto do espaço + vibe/descrição e recebe a imagem com
    as flores sugeridas. Configuração da API em apif/config.php.
*/

if (session_status() !== PHP_SESSION_ACTIVE) session_start();

$config = require __DIR__ . '/apif/config.php';
set_time_limit(240);

$titulo    = 'Visualização com I.A. — Florify';
$descricao = 'Envie uma foto do seu espaço e veja como ficaria com os arranjos da Florify.';

const VIZ_LIMITE_POR_HORA = 5;

$vibes = [
    'Romântico'   => ['🌹', 'Delicado, tons de rosa e branco',     'romântico, delicado, tons de rosa e branco'],
    'Rústico'     => ['🌾', 'Flores do campo, texturas naturais',  'rústico, campestre, flores do campo, texturas naturais'],
    'Tropical'    => ['🌺', 'Vibrante, folhagens e cores quentes', 'tropical, vibrante, folhagens grandes e cores quentes'],
    'Minimalista' => ['🤍', 'Poucas peças, linhas limpas',         'minimalista, poucas peças, linhas limpas, tons neutros'],
    'Boho'        => ['🪻', 'Flores secas, pampas, tons terrosos', 'boho, silvestre, flores secas e pampas, tons terrosos'],
    'Clássico'    => ['💐', 'Elegante: rosas e hortênsias',        'clássico e elegante, rosas, hortênsias, tons suaves'],
];

$erro = '';
$sugestoes = null;
$imagemResultado = '';
$fotoOriginal = '';

/* ---------- Funções auxiliares (prefixo viz_ para não colidir com o site) ---------- */

function viz_e($s): string { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); }

function viz_chamarOpenAI(string $url, $corpo, array $headers, int $timeout = 60): array
{
    $certificado = getenv('FLORIFY_CA_BUNDLE') ?: '';

    if ($certificado !== '' && !is_readable($certificado)) {
        throw new RuntimeException('O arquivo de certificados configurado não está acessível.');
    }

    $opcoes = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $corpo,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_TIMEOUT        => $timeout,
        CURLOPT_CONNECTTIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ];

    if ($certificado !== '') {
        $opcoes[CURLOPT_CAINFO] = $certificado;
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, $opcoes);

    $retorno = curl_exec($ch);

    $erro = curl_error($ch);
    $codigoErro = curl_errno($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    if ($retorno === false) {
        throw new RuntimeException(
            "Falha na conexão com o serviço de IA. Código: $codigoErro. HTTP: $http."
        );
    }

    $json = json_decode($retorno, true);

    if ($http !== 200) {
        $msg = $json['error']['message'] ?? 'Erro desconhecido.';
        throw new RuntimeException("Erro da API (HTTP $http): $msg");
    }

    return $json;
}
function viz_prepararImagem(array $arquivo, int $maxMb): string
{
    if ($arquivo['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Não foi possível receber a foto. Tente novamente.');
    }
    if ($arquivo['size'] > $maxMb * 1024 * 1024) {
        throw new RuntimeException("A foto deve ter no máximo {$maxMb} MB.");
    }

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($arquivo['tmp_name']);

    switch ($mime) {
        case 'image/jpeg': $img = imagecreatefromjpeg($arquivo['tmp_name']); break;
        case 'image/png':  $img = imagecreatefrompng($arquivo['tmp_name']);  break;
        case 'image/webp': $img = imagecreatefromwebp($arquivo['tmp_name']); break;
        default: throw new RuntimeException('Envie uma imagem JPG, PNG ou WEBP.');
    }
    if (!$img) {
        throw new RuntimeException('Imagem inválida.');
    }

    if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
        $exif  = @exif_read_data($arquivo['tmp_name']);
        $o     = $exif['Orientation'] ?? 1;
        $graus = [3 => 180, 6 => -90, 8 => 90][$o] ?? 0;
        if ($graus !== 0) {
            $img = imagerotate($img, $graus, 0);
        }
    }

    $w = imagesx($img);
    $h = imagesy($img);
    $max = 1536;
    if (max($w, $h) > $max) {
        $escala = $max / max($w, $h);
        $img = imagescale($img, (int) round($w * $escala), (int) round($h * $escala));
    }

    $tmp = tempnam(sys_get_temp_dir(), 'flor_') . '.jpg';
    imagejpeg($img, $tmp, 88);
    imagedestroy($img);

    return $tmp;
}

function viz_dentroDoLimite(): bool
{
    $agora = time();
    $lista = array_filter($_SESSION['viz_geracoes'] ?? [], fn($t) => $t > $agora - 3600);
    $_SESSION['viz_geracoes'] = $lista;
    return count($lista) < VIZ_LIMITE_POR_HORA;
}

/* ---------- Processamento ---------- */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vibe      = $_POST['vibe'] ?? '';
    $descricaoCliente = trim($_POST['descricao'] ?? '');
    $tmpImg    = '';

    try {
        if (empty($config['api_key'])) {
            throw new RuntimeException('A chave da API não está configurada no servidor. Defina a variável OPENAI_API_KEY.');
        }
        if (!isset($vibes[$vibe])) {
            throw new RuntimeException('Escolha uma vibe.');
        }
        if (empty($_FILES['foto']['tmp_name'])) {
            throw new RuntimeException('Envie a foto do espaço.');
        }
        if (mb_strlen($descricaoCliente) > 500) {
            throw new RuntimeException('A descrição deve ter no máximo 500 caracteres.');
        }
        if (!viz_dentroDoLimite()) {
            throw new RuntimeException('Você atingiu o limite de ' . VIZ_LIMITE_POR_HORA . ' visualizações por hora. Tente novamente mais tarde.');
        }

        $tmpImg = viz_prepararImagem($_FILES['foto'], (int) $config['max_upload_mb']);
        $fotoOriginal = 'data:image/jpeg;base64,' . base64_encode(file_get_contents($tmpImg));

        $auth = 'Authorization: Bearer ' . $config['api_key'];

        /* Etapa 1: análise do espaço e sugestões */
        $instrucao = "Você é uma floricultora e decoradora experiente. Analise a foto do espaço "
            . "e sugira flores e arranjos que combinem com ele.\n"
            . "Vibe escolhida: {$vibe} ({$vibes[$vibe][2]}).\n"
            . "Pedido do cliente: " . ($descricaoCliente !== '' ? $descricaoCliente : 'sem observações') . "\n\n"
            . "Responda SOMENTE com JSON neste formato:\n"
            . '{"resumo_espaco":"1 frase sobre o espaço",'
            . '"sugestoes":[{"nome":"nome do arranjo/flor","onde":"onde posicionar no espaço","motivo":"por que combina"}],'
            . '"descricao_visual":"descrição em inglês, detalhada, das flores e vasos a serem adicionados e onde, para guiar um editor de imagens"}'
            . "\nForneça de 3 a 4 sugestões, em português do Brasil (exceto descricao_visual, em inglês).";

        $payloadTexto = [
            'model' => $config['modelo_texto'],
            'response_format' => ['type' => 'json_object'],
            'temperature' => 0.7,
            'max_tokens' => 800,
            'messages' => [[
                'role' => 'user',
                'content' => [
                    ['type' => 'text', 'text' => $instrucao],
                    ['type' => 'image_url', 'image_url' => ['url' => $fotoOriginal, 'detail' => 'low']],
                ],
            ]],
        ];

        $resp1 = viz_chamarOpenAI(
            'https://api.openai.com/v1/chat/completions',
            json_encode($payloadTexto, JSON_UNESCAPED_UNICODE),
            ['Content-Type: application/json', $auth]
        );

        $sugestoes = json_decode($resp1['choices'][0]['message']['content'] ?? '', true);
        if (!is_array($sugestoes) || empty($sugestoes['sugestoes'])) {
            throw new RuntimeException('Não consegui gerar as sugestões. Tente com outra foto.');
        }

        /* Etapa 2: edição da foto com as flores */
        $promptImagem = "Edit this photo of a real space by adding fresh flowers and floral arrangements. "
            . "Style: {$vibe}. Add the following: " . ($sugestoes['descricao_visual'] ?? '') . " "
            . "IMPORTANT: keep the room, walls, furniture, perspective, camera angle and lighting exactly as in the original photo. "
            . "Only add the flowers, vases and plants. Photorealistic result, natural shadows, realistic scale.";

        $campos = [
            'model'          => $config['modelo_imagem'],
            'prompt'         => $promptImagem,
            'image'          => new CURLFile($tmpImg, 'image/jpeg', 'espaco.jpg'),
            'size'           => 'auto',
            'quality'        => $config['qualidade'],
            'n'              => 1,
        ];

        $resp2 = viz_chamarOpenAI('https://api.openai.com/v1/images/edits', $campos, [$auth], 200);

        $b64 = $resp2['data'][0]['b64_json'] ?? '';
        if ($b64 === '') {
            throw new RuntimeException('A API não retornou a imagem.');
        }
        $imagemResultado = 'data:image/png;base64,' . $b64;

        $_SESSION['viz_geracoes'][] = time();

    } catch (Throwable $ex) {
        $erro = $ex->getMessage();
    } finally {
        if ($tmpImg !== '' && file_exists($tmpImg)) {
            @unlink($tmpImg);
        }
    }
}

$vibeSelecionada = $_POST['vibe'] ?? 'Romântico';

$scriptsExtras = ['javascript/visualizar.js'];

require __DIR__ . '/partes/head.php';
require __DIR__ . '/partes/header.php';
?>
<style>
    /* Estilos exclusivos desta página (não alteram o estilo.css do site) */
    .viz-card { background:#fff; border-radius:var(--raio-lg); padding:1.6rem; box-shadow:var(--sombra-suave); margin-bottom:1.4rem; }
    .viz-card h2 { font-size:1.15rem; font-weight:700; margin:0 0 .25rem; }
    .viz-dica { margin:0 0 1rem; font-size:.9rem; color:var(--texto-suave); }
    .viz-drop { display:block; border:2px dashed var(--rosa-escuro); border-radius:var(--raio-md); padding:2rem 1rem; text-align:center; cursor:pointer; background:var(--amarelo-claro); transition:background .2s; }
    .viz-drop.hover { background:var(--verde-folha); }
    .viz-drop input { display:none; }
    .viz-drop .viz-icone { font-size:2.2rem; }
    #vizPreview { display:none; max-width:100%; max-height:320px; border-radius:var(--raio-md); margin-top:1rem; }
    .viz-vibes { display:grid; grid-template-columns:repeat(auto-fill,minmax(240px,1fr)); gap:.7rem; }
    .viz-vibe { position:relative; margin:0; }
    .viz-vibe input { position:absolute; opacity:0; }
    .viz-vibe .viz-opcao { display:flex; gap:.8rem; align-items:center; border:2px solid #e6e6e6; border-radius:var(--raio-md); padding:.8rem 1rem; cursor:pointer; transition:all .15s; height:100%; }
    .viz-vibe .viz-emoji { font-size:1.8rem; }
    .viz-vibe .viz-opcao b { display:block; }
    .viz-vibe .viz-opcao small { color:var(--texto-suave); }
    .viz-vibe input:checked + .viz-opcao { border-color:var(--rosa-escuro); background:var(--amarelo-claro); }
    .viz-vibe input:focus-visible + .viz-opcao { outline:3px solid var(--rosa-pink); }
    .viz-comparacao { display:grid; grid-template-columns:1fr 1fr; gap:.8rem; }
    .viz-comparacao figure { margin:0; }
    .viz-comparacao img { width:100%; border-radius:var(--raio-md); display:block; }
    .viz-comparacao figcaption { text-align:center; font-size:.85rem; color:var(--texto-suave); margin-top:.4rem; }
    @media (max-width:640px) { .viz-comparacao { grid-template-columns:1fr; } }
    .viz-sug { background:var(--amarelo-claro); border-radius:var(--raio-md); padding:.8rem 1rem; margin:.6rem 0; }
    .viz-sug small { color:var(--texto-suave); }
    .viz-erro { background:#fdecee; border:1px solid #f3b8c0; color:#a1172b; padding:.8rem 1rem; border-radius:var(--raio-md); margin-bottom:1.2rem; font-weight:600; }
    #vizLoading { display:none; position:fixed; inset:0; z-index:2000; background:rgba(255,255,224,.94); align-items:center; justify-content:center; flex-direction:column; text-align:center; padding:1rem; }
    #vizLoading.ativo { display:flex; }
    #vizLoading .viz-flor { font-size:3.4rem; animation:vizGirar 2.2s linear infinite; }
    @keyframes vizGirar { to { transform:rotate(360deg); } }
    @media (prefers-reduced-motion: reduce) { #vizLoading .viz-flor { animation:none; } }
</style>

<main class="secao container" style="max-width:880px">
    <div class="text-center mb-5">
        <span class="eyebrow">Visualização com I.A.</span>
        <h1 class="titulo-script grande">Veja como ficaria com flores</h1>
        <p class="texto-corpo">Envie uma foto do seu espaço, escolha a vibe e veja a nossa sugestão em instantes.</p>
    </div>

    <?php if ($erro !== ''): ?>
        <div class="viz-erro" role="alert"><?= viz_e($erro) ?></div>
    <?php endif; ?>

    <?php if ($imagemResultado !== ''): ?>
        <section class="viz-card" id="vizResultado">
            <h2>Como ficaria</h2>
            <p class="viz-dica">Vibe escolhida: <?= viz_e($vibeSelecionada) ?></p>
            <div class="viz-comparacao">
                <figure>
                    <img src="<?= $fotoOriginal ?>" alt="Foto original do espaço">
                    <figcaption>Antes</figcaption>
                </figure>
                <figure>
                    <img src="<?= $imagemResultado ?>" alt="Espaço com as flores sugeridas">
                    <figcaption>Depois</figcaption>
                </figure>
            </div>
            <div class="mt-3">
                <a class="btn btn-florify rounded-pill px-4" href="<?= $imagemResultado ?>" download="meu-espaco-com-flores.png">
                    <i class="bi bi-download me-1"></i> Baixar imagem
                </a>
            </div>
        </section>

        <section class="viz-card">
            <h2>Nossas sugestões</h2>
            <p class="viz-dica"><?= viz_e($sugestoes['resumo_espaco'] ?? '') ?></p>
            <?php foreach ($sugestoes['sugestoes'] as $s): ?>
                <div class="viz-sug">
                    <strong><?= viz_e($s['nome'] ?? '') ?></strong><br>
                    <small><i class="bi bi-geo-alt"></i> <?= viz_e($s['onde'] ?? '') ?></small><br>
                    <?= viz_e($s['motivo'] ?? '') ?>
                </div>
            <?php endforeach; ?>
            <p class="viz-dica mb-0">Imagem ilustrativa gerada por I.A.; o arranjo final pode variar.</p>
            <div class="mt-3">
                <a class="btn btnVerde rounded-pill px-4" href="itens.php">Ver nossos produtos</a>
            </div>
        </section>

        <h2 class="titulo-script text-center mb-4">Quer testar outra combinação?</h2>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" id="vizForm">
        <section class="viz-card">
            <h2>1. Foto do espaço</h2>
            <p class="viz-dica">JPG, PNG ou WEBP, até <?= (int) $config['max_upload_mb'] ?> MB.</p>
            <label class="viz-drop" id="vizDrop">
                <input type="file" name="foto" id="vizFoto" accept="image/jpeg,image/png,image/webp" required>
                <div class="viz-icone">📷</div>
                <div><strong>Clique para escolher</strong> ou arraste a foto aqui</div>
                <img id="vizPreview" alt="Pré-visualização da foto">
            </label>
        </section>

        <section class="viz-card">
            <h2>2. Qual vibe você quer?</h2>
            <p class="viz-dica">Escolha o estilo das flores.</p>
            <div class="viz-vibes">
                <?php foreach ($vibes as $nome => [$emoji, $desc]): ?>
                    <label class="viz-vibe">
                        <input type="radio" name="vibe" value="<?= viz_e($nome) ?>" <?= $vibeSelecionada === $nome ? 'checked' : '' ?>>
                        <span class="viz-opcao">
                            <span class="viz-emoji"><?= $emoji ?></span>
                            <span><b><?= viz_e($nome) ?></b><small><?= viz_e($desc) ?></small></span>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="viz-card">
            <h2>3. Quer contar mais? <small class="fw-normal" style="color:var(--texto-suave)">(opcional)</small></h2>
            <p class="viz-dica">Flores preferidas, cores, onde quer os arranjos...</p>
            <textarea class="form-control" name="descricao" id="vizDescricao" rows="3" maxlength="500"
                placeholder="Ex.: gosto de rosas e lavanda, quero algo para a mesa de centro."><?= viz_e($_POST['descricao'] ?? '') ?></textarea>
            <div class="text-end small" style="color:var(--texto-suave)"><span id="vizQtd">0</span>/500</div>
        </section>

        <button type="submit" class="btn btn-florify rounded-pill w-100 py-3" id="vizBtn">
            <i class="bi bi-flower1 me-1"></i> Gerar visualização
        </button>
    </form>
</main>

<div id="vizLoading" role="status" aria-live="polite">
    <div class="viz-flor">🌸</div>
    <h2 class="titulo-script">Montando o seu arranjo...</h2>
    <p class="texto-corpo">Isso pode levar até 1 minuto. Não feche esta página.</p>
</div>
<?php
require __DIR__ . '/partes/footer.php';
require __DIR__ . '/partes/scripts.php';
