/* Página de compra: total ao vivo, contador, máscaras, CEP e regras de entrega.
   (O servidor valida tudo de novo em comprar.php — isto é só conforto para o cliente.) */
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('formCompra');
    if (!form) return;

    const brl = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' });
    const preco = parseFloat(form.dataset.preco);
    const frete = parseFloat(form.dataset.frete);

    const qtd = form.querySelector('#quantidade');
    const resumoQtd = document.getElementById('resumoQtd');
    const resumoSubtotal = document.getElementById('resumoSubtotal');
    const resumoTotal = document.getElementById('resumoTotal');

    function limitarQtd() {
        let n = parseInt(qtd.value, 10);
        if (isNaN(n) || n < 1) n = 1;
        if (n > 20) n = 20;
        return n;
    }

    function atualizarResumo() {
        const n = limitarQtd();
        resumoQtd.textContent = n;
        resumoSubtotal.textContent = brl.format(preco * n);
        resumoTotal.textContent = brl.format(preco * n + frete);
    }

    form.querySelectorAll('[data-passo]').forEach((botao) => {
        botao.addEventListener('click', () => {
            qtd.value = Math.min(20, Math.max(1, limitarQtd() + parseInt(botao.dataset.passo, 10)));
            atualizarResumo();
        });
    });
    qtd.addEventListener('input', atualizarResumo);
    qtd.addEventListener('blur', () => { qtd.value = limitarQtd(); atualizarResumo(); });
    atualizarResumo();

    /* contador da mensagem */
    const msg = form.querySelector('#mensagem');
    const contador = document.getElementById('contador');
    const contar = () => { contador.textContent = msg.value.length; };
    msg.addEventListener('input', contar);
    contar();

    /* máscaras */
    const soDigitos = (v) => v.replace(/\D+/g, '');

    const tel = form.querySelector('#telefone');
    tel.addEventListener('input', () => {
        const d = soDigitos(tel.value).slice(0, 11);
        if (d.length > 6) {
            const meio = d.length > 10 ? 7 : 6;
            tel.value = `(${d.slice(0, 2)}) ${d.slice(2, meio)}-${d.slice(meio)}`;
        } else if (d.length > 2) {
            tel.value = `(${d.slice(0, 2)}) ${d.slice(2)}`;
        } else {
            tel.value = d;
        }
    });

    const cep = form.querySelector('#cep');
    cep.addEventListener('input', () => {
        const d = soDigitos(cep.value).slice(0, 8);
        cep.value = d.length > 5 ? `${d.slice(0, 5)}-${d.slice(5)}` : d;
        if (d.length === 8) buscarCep(d);
    });

    /* preenche o endereço pelo CEP (ViaCEP). Se falhar, o cliente digita normalmente. */
    async function buscarCep(d) {
        try {
            const r = await fetch(`https://viacep.com.br/ws/${d}/json/`);
            if (!r.ok) return;
            const j = await r.json();
            if (j.erro) return;
            const set = (id, v) => { const el = form.querySelector(id); if (el && v && !el.value) el.value = v; };
            set('#endereco', j.logradouro);
            set('#bairro', j.bairro);
            set('#cidade', j.localidade);
            if (j.uf) form.querySelector('#uf').value = j.uf;
            form.querySelector(j.logradouro ? '#numero' : '#endereco').focus();
        } catch (_) { /* sem internet ou bloqueado: segue o preenchimento manual */ }
    }

    /* regras de entrega: sem domingo; sábado só de manhã */
    const data = form.querySelector('#data');
    const periodo = form.querySelector('#periodo');
    const tarde = periodo.querySelector('option[value="tarde"]');

    function validarData() {
        data.setCustomValidity('');
        tarde.disabled = false;
        if (!data.value) return;
        const dia = new Date(data.value + 'T12:00:00').getDay();
        if (dia === 0) {
            data.setCustomValidity('Não fazemos entregas aos domingos.');
        } else if (dia === 6) {
            tarde.disabled = true;
            if (periodo.value === 'tarde') periodo.value = 'manha';
        }
    }
    data.addEventListener('change', () => { validarData(); data.reportValidity(); });
    validarData();

    /* ao falhar a validação do servidor, leva o foco ao primeiro campo com erro */
    const primeiroErro = form.querySelector('.is-invalid');
    if (primeiroErro) primeiroErro.focus();
});
