let paginaAtual = 1;
let statusAtual = '';
let buscaAtual = '';
let meusTrabalhos = false;

function carregarPedidos(pagina = 1) {
    paginaAtual = pagina;

    const formData = new FormData();
    formData.append('pagina', pagina);
    formData.append('busca', buscaAtual);
    if (statusAtual) formData.append('status', statusAtual);
    if (meusTrabalhos) formData.append('meus', '1');

    //const overlay = document.getElementById('loading-overlay');
    //if (overlay) overlay.style.display = 'flex'; // Mostra

    fetch('ajax_carregar_pedidos.php', {
        method: 'POST',
        body: formData
    })
    .then(async response => {
        const texto = await response.text();
        // Tenta interpretar JSON mesmo em caso de erro HTTP
        let dados = {};
        try {
            dados = JSON.parse(texto);
        } catch (e) {
            throw new Error("Resposta não é JSON válido: " + texto);
        }

        if (!response.ok) {
            throw new Error("Erro HTTP " + response.status + ": " + (dados?.html || ''));
        }

        if (dados?.html && typeof dados.total_paginas !== 'undefined') {
            document.getElementById('resultado').innerHTML = dados.html;
            atualizarPaginacao(dados.total_paginas);
        } else {
            throw new Error("Resposta incompleta.");
        }
    })
    .catch(err => {
        console.error('Erro ao carregar pedidos:', err.message);
        document.getElementById('resultado').innerHTML = `
            <div class="no-results">
                <img src="../img/error.svg" alt="Erro" style="width:200px;height:300px;">
                <p>${err.message}</p>
            </div>`;
    })
    .finally(() => {
        //if (overlay) overlay.style.display = 'none';
    });
}


function atualizarPaginacao(totalPaginas) {
    let paginacao = document.querySelector('.paginacao');
    if (!paginacao) return;
    let html = '';

    if (paginaAtual > 1) {
        html += `<a href="#" onclick="carregarPedidos(${paginaAtual - 1})"><span class="material-symbols-outlined">arrow_back_ios</span></a>`;
    }

    for (let i = 1; i <= totalPaginas; i++) {
        html += `<a href="#" onclick="carregarPedidos(${i})" ${i === paginaAtual ? 'class="ativa"' : ''}>${i}</a>`;
    }

    if (paginaAtual < totalPaginas) {
        html += `<a href="#" onclick="carregarPedidos(${paginaAtual + 1})"><span class="material-symbols-outlined">arrow_forward_ios</span></a>`;
    }

    paginacao.innerHTML = html;
}

// Atualiza busca
document.getElementById('buscaInput').addEventListener('input', function () {
    buscaAtual = this.value.trim();
    carregarPedidos(1);
});

// Atualiza filtros dos botões
document.querySelectorAll('.status-btn').forEach(btn => {
    btn.addEventListener('click', e => {
        e.preventDefault();

        const status = btn.dataset.status;
        const meus = btn.dataset.meus;

        if (meus !== undefined) {
            meusTrabalhos = !meusTrabalhos;
            statusAtual = '';
        } else {
            meusTrabalhos = false;
            statusAtual = (statusAtual === status) ? '' : status;
        }

        // Atualiza visual dos botões ativos
        document.querySelectorAll('.status-btn').forEach(b => b.classList.remove('active'));
        if (statusAtual && btn.dataset.status === statusAtual) {
            btn.classList.add('active');
        }
        if (meusTrabalhos && btn.dataset.meus !== undefined) {
            btn.classList.add('active');
        }

        carregarPedidos(1);
    });
});

// Atualiza cor das letras dos avatares (dinâmico)
document.querySelectorAll('.avatar').forEach(function(avatar) {
    const letra = avatar.textContent.trim().charAt(0).toLowerCase();
    if (letra.match(/[a-z]/i)) {
        avatar.classList.add(letra);
    }
});

//Executa ao carregar a página
window.addEventListener('DOMContentLoaded', () => {
    carregarPedidos();
});