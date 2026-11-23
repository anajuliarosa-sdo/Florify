<?php
/*
    index.php
    Página inicial: o cliente envia a foto do espaço, escolhe a vibe e recebe
    a imagem com as flores + sugestões.
*/

session_start();
$config = require __DIR__ . '/config.php';
set_time_limit(240);

$vibes = [
    'Romântico'   => ['🌹', 'Delicado, tons de rosa e branco',        'romântico, delicado, tons de rosa e branco'],
    'Rústico'     => ['🌾', 'Flores do campo, texturas naturais',     'rústico, campestre, flores do campo, texturas naturais'],
    'Tropical'    => ['🌺', 'Vibrante, folhagens e cores quentes',    'tropical, vibrante, folhagens grandes e cores quentes'],
    'Minimalista' => ['🤍', 'Poucas peças, linhas limpas',            'minimalista, poucas peças, linhas limpas, tons neutros'],
    'Boho'        => ['🪻', 'Flores secas, pampas, tons terrosos',    'boho, silvestre, flores secas e pampas, tons terrosos'],
    'Clássico'    => ['💐', 'Elegante: rosas e hortênsias',           'clássico e elegante, rosas, hortênsias, tons suaves'],
];

const LIMITE_POR_HORA = 5;

$erro = '';
$sugestoes = null;
$imagemResultado = '';
$fotoOriginal = '';

/* ---------- Funções auxiliares ---------- */

function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

function chamarOpenAI(string $url, $corpo, array $headers, int $timeout = 60): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $corpo,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_TIMEOUT        => $timeout,
        CURLOPT_CONNECTTIMEOUT => 15,
    ]);

    $retorno  = curl_exec($ch);
    $erroCurl = curl_error($ch);
    $http     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($retorno === false) {
        throw new RuntimeException('Falha de conexão: ' . $erroCurl);
    }

    $json = json_decode($retorno, true);

    if ($http !== 200) {
        $msg = $json['error']['message'] ?? 'Erro desconhecido.';
        throw new RuntimeException("Erro da API (HTTP $http): $msg");
    }

    return $json;
}

function prepararImagem(array $arquivo, int $maxMb): string
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

function dentroDoLimite(): bool
{
    $agora = time();
    $lista = array_filter($_SESSION['geracoes'] ?? [], fn($t) => $t > $agora - 3600);
    $_SESSION['geracoes'] = $lista;
    return count($lista) < LIMITE_POR_HORA;
}

/* ---------- Processamento ---------- */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vibe      = $_POST['vibe'] ?? '';
    $descricao = trim($_POST['descricao'] ?? '');
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
        if (mb_strlen($descricao) > 500) {
            throw new RuntimeException('A descrição deve ter no máximo 500 caracteres.');
        }
        if (!dentroDoLimite()) {
            throw new RuntimeException('Você atingiu o limite de ' . LIMITE_POR_HORA . ' visualizações por hora. Tente novamente mais tarde.');
        }

        $tmpImg = prepararImagem($_FILES['foto'], $config['max_upload_mb']);
        $fotoOriginal = 'data:image/jpeg;base64,' . base64_encode(file_get_contents($tmpImg));

        $auth = 'Authorization: Bearer ' . $config['api_key'];

        /* Etapa 1: análise do espaço e sugestões */
        $instrucao = "Você é uma floricultora e decoradora experiente. Analise a foto do espaço "
            . "e sugira flores e arranjos que combinem com ele.\n"
            . "Vibe escolhida: {$vibe} ({$vibes[$vibe][2]}).\n"
            . "Pedido do cliente: " . ($descricao !== '' ? $descricao : 'sem observações') . "\n\n"
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

        $resp1 = chamarOpenAI(
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

        $resp2 = chamarOpenAI('https://api.openai.com/v1/images/edits', $campos, [$auth], 200);

        $b64 = $resp2['data'][0]['b64_json'] ?? '';
        if ($b64 === '') {
            throw new RuntimeException('A API não retornou a imagem.');
        }
        $imagemResultado = 'data:image/png;base64,' . $b64;

        $_SESSION['geracoes'][] = time();

    } catch (Throwable $ex) {
        $erro = $ex->getMessage();
    } finally {
        if ($tmpImg !== '' && file_exists($tmpImg)) {
            @unlink($tmpImg);
        }
    }
}

$vibeSelecionada = $_POST['vibe'] ?? 'Romântico';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Veja como ficaria com flores | Floricultura</title>
    <style>
        :root {
            --rosa: #b04a6e;
            --rosa-escuro: #8f3556;
            --rosa-claro: #fbf0f4;
            --borda: #ecd3dc;
            --texto: #3a2c31;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: system-ui, -apple-system, "Segoe UI", sans-serif;
            color: var(--texto);
            background: linear-gradient(180deg, var(--rosa-claro), #fff 380px);
        }
        .container { max-width: 880px; margin: 0 auto; padding: 32px 16px 60px; }
        header { text-align: center; margin-bottom: 28px; }
        header h1 { margin: 0 0 8px; color: var(--rosa); font-size: 2rem; }
        header p { margin: 0; color: #6d5a61; }

        .card {
            background: #fff;
            border: 1px solid var(--borda);
            border-radius: 16px;
            padding: 22px;
            margin-bottom: 18px;
            box-shadow: 0 2px 10px rgba(176, 74, 110, .06);
        }
        .card h2 { margin: 0 0 4px; font-size: 1.1rem; color: var(--rosa); }
        .card .dica { margin: 0 0 14px; font-size: .9rem; color: #7d6b72; }

        /* Upload */
        .dropzone {
            border: 2px dashed var(--rosa);
            border-radius: 14px;
            padding: 28px 16px;
            text-align: center;
            cursor: pointer;
            background: var(--rosa-claro);
            transition: background .2s;
        }
        .dropzone.hover { background: #f6dde7; }
        .dropzone input { display: none; }
        .dropzone .icone { font-size: 2.2rem; }
        .dropzone strong { color: var(--rosa-escuro); }
        #preview { display: none; max-width: 100%; max-height: 320px; border-radius: 12px; margin-top: 14px; }

        /* Vibes */
        .vibes { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 10px; }
        .vibe input { position: absolute; opacity: 0; }
        .vibe .opcao {
            display: flex; gap: 12px; align-items: center;
            border: 2px solid var(--borda);
            border-radius: 12px;
            padding: 12px 14px;
            cursor: pointer;
            transition: all .15s;
        }
        .vibe .opcao .emoji { font-size: 1.8rem; }
        .vibe .opcao b { display: block; }
        .vibe .opcao small { color: #7d6b72; }
        .vibe input:checked + .opcao { border-color: var(--rosa); background: var(--rosa-claro); }
        .vibe input:focus-visible + .opcao { outline: 3px solid #e7a9be; }

        textarea {
            width: 100%; border: 1px solid var(--borda); border-radius: 12px;
            padding: 12px; font: inherit; resize: vertical;
        }
        textarea:focus { outline: 2px solid #e7a9be; }
        .contador { text-align: right; font-size: .8rem; color: #8a777e; }

        .btn {
            display: block; width: 100%;
            background: var(--rosa); color: #fff; border: 0;
            padding: 16px; border-radius: 12px;
            font-size: 1.05rem; font-weight: 600; cursor: pointer;
        }
        .btn:hover { background: var(--rosa-escuro); }
        .btn:disabled { opacity: .6; cursor: wait; }

        .erro {
            background: #fdecee; border: 1px solid #f3b8c0; color: #a1172b;
            padding: 12px 16px; border-radius: 12px; margin-bottom: 18px; font-weight: 600;
        }

        /* Resultado */
        .comparacao { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .comparacao figure { margin: 0; }
        .comparacao img { width: 100%; border-radius: 12px; display: block; }
        .comparacao figcaption { text-align: center; font-size: .85rem; color: #7d6b72; margin-top: 6px; }
        @media (max-width: 640px) { .comparacao { grid-template-columns: 1fr; } }
        .sug { background: var(--rosa-claro); border-radius: 12px; padding: 12px 16px; margin: 10px 0; }
        .sug small { color: #7d6b72; }
        .acoes { margin-top: 14px; }
        .acoes a {
            display: inline-block; margin-right: 8px; padding: 10px 16px;
            border: 1px solid var(--rosa); color: var(--rosa); border-radius: 10px;
            text-decoration: none; font-weight: 600;
        }
        .aviso { font-size: .8rem; color: #8a777e; }

        /* Loading */
        #loading {
            display: none; position: fixed; inset: 0; z-index: 10;
            background: rgba(255, 255, 255, .92);
            align-items: center; justify-content: center; flex-direction: column; text-align: center;
        }
        #loading.ativo { display: flex; }
        #loading .flor { font-size: 3.4rem; animation: girar 2.2s linear infinite; }
        @keyframes girar { to { transform: rotate(360deg); } }
    </style>
</head>
<body>
<div class="container">
    <header>
        <h1>🌸 Veja como ficaria com flores</h1>
        <p>Envie uma foto do seu espaço, escolha a vibe e veja a nossa sugestão em segundos.</p>
    </header>

    <?php if ($erro !== ''): ?>
        <div class="erro"><?= e($erro) ?></div>
    <?php endif; ?>

    <?php if ($imagemResultado !== ''): ?>
        <section class="card" id="resultado">
            <h2>Como ficaria</h2>
            <p class="dica">Vibe escolhida: <?= e($vibeSelecionada) ?></p>
            <div class="comparacao">
                <figure>
                    <img src="<?= $fotoOriginal ?>" alt="Foto original do espaço">
                    <figcaption>Antes</figcaption>
                </figure>
                <figure>
                    <img src="<?= $imagemResultado ?>" alt="Espaço com as flores sugeridas">
                    <figcaption>Depois</figcaption>
                </figure>
            </div>
            <div class="acoes">
                <a href="<?= $imagemResultado ?>" download="meu-espaco-com-flores.png">⬇ Baixar imagem</a>
            </div>
        </section>

        <section class="card">
            <h2>Nossas sugestões</h2>
            <p class="dica"><?= e($sugestoes['resumo_espaco'] ?? '') ?></p>
            <?php foreach ($sugestoes['sugestoes'] as $s): ?>
                <div class="sug">
                    <strong><?= e($s['nome'] ?? '') ?></strong><br>
                    <small>📍 <?= e($s['onde'] ?? '') ?></small><br>
                    <?= e($s['motivo'] ?? '') ?>
                </div>
            <?php endforeach; ?>
            <p class="aviso">Imagem ilustrativa gerada por IA; o arranjo final pode variar.</p>
        </section>

        <h2 style="text-align:center;color:var(--rosa)">Quer testar outra combinação?</h2>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" id="form">
        <section class="card">
            <h2>1. Foto do espaço</h2>
            <p class="dica">JPG, PNG ou WEBP, até <?= (int) $config['max_upload_mb'] ?> MB.</p>
            <label class="dropzone" id="dropzone">
                <input type="file" name="foto" id="foto" accept="image/jpeg,image/png,image/webp" required>
                <div class="icone">📷</div>
                <div><strong>Clique para escolher</strong> ou arraste a foto aqui</div>
                <img id="preview" alt="Pré-visualização da foto">
            </label>
        </section>

        <section class="card">
            <h2>2. Qual vibe você quer?</h2>
            <p class="dica">Escolha o estilo das flores.</p>
            <div class="vibes">
                <?php foreach ($vibes as $nome => [$emoji, $desc]): ?>
                    <label class="vibe">
                        <input type="radio" name="vibe" value="<?= e($nome) ?>" <?= $vibeSelecionada === $nome ? 'checked' : '' ?>>
                        <span class="opcao">
                            <span class="emoji"><?= $emoji ?></span>
                            <span><b><?= e($nome) ?></b><small><?= e($desc) ?></small></span>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="card">
            <h2>3. Quer contar mais? <small style="font-weight:400;color:#8a777e">(opcional)</small></h2>
            <p class="dica">Flores preferidas, cores, onde quer os arranjos...</p>
            <textarea name="descricao" id="descricao" rows="3" maxlength="500"
                placeholder="Ex.: gosto de rosas e lavanda, quero algo para a mesa de centro."><?= e($_POST['descricao'] ?? '') ?></textarea>
            <div class="contador"><span id="qtd">0</span>/500</div>
        </section>

        <button type="submit" class="btn" id="btn">🌷 Gerar visualização</button>
    </form>
</div>

<div id="loading">
    <div class="flor">🌸</div>
    <h2 style="color:var(--rosa)">Montando o seu arranjo...</h2>
    <p>Isso pode levar até 1 minuto. Não feche esta página.</p>
</div>

<script>
    const MAX_MB = <?= (int) $config['max_upload_mb'] ?>;
    const foto = document.getElementById('foto');
    const preview = document.getElementById('preview');
    const dropzone = document.getElementById('dropzone');
    const descricao = document.getElementById('descricao');
    const qtd = document.getElementById('qtd');

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

    ['dragenter', 'dragover'].forEach(ev => dropzone.addEventListener(ev, e => {
        e.preventDefault(); dropzone.classList.add('hover');
    }));
    ['dragleave', 'drop'].forEach(ev => dropzone.addEventListener(ev, e => {
        e.preventDefault(); dropzone.classList.remove('hover');
    }));
    dropzone.addEventListener('drop', e => {
        if (e.dataTransfer.files.length) {
            foto.files = e.dataTransfer.files;
            mostrarPreview();
        }
    });

    descricao.addEventListener('input', () => qtd.textContent = descricao.value.length);
    qtd.textContent = descricao.value.length;

    document.getElementById('form').addEventListener('submit', () => {
        document.getElementById('btn').disabled = true;
        document.getElementById('loading').classList.add('ativo');
    });

    <?php if ($imagemResultado !== ''): ?>
    document.getElementById('resultado').scrollIntoView({ behavior: 'smooth' });
    <?php endif; ?>
</script>
</body>
</html>
