function toggleSenha(botao) {
    const input = document.getElementById('senhaInput');
    const icone = botao.querySelector('#iconeSenha');
    const mostrando = input.type === 'password';

    input.type = mostrando ? 'text' : 'password';
    icone.textContent = mostrando ? 'visibility_off' : 'visibility';
}

// Esta função é chamada sempre que o usuário digita algo no campo de senha
function verificarSenha() {
    const input = document.getElementById('senhaInput');
    const botao = document.getElementById('toggle');
    if (!input || !botao) return;
    botao.style.display = input.value.length > 0 ? 'inline-block' : 'none';
}
function abrirModalRecuperacaoSenha() {
    Swal.fire({
        title: 'Recuperar Senha',
        html: `
            <input type="email" id="emailRecuperacao" class="swal2-input" placeholder="Digite seu e-mail">
        `,
        confirmButtonText: 'Enviar',
        focusConfirm: false,
        preConfirm: () => {
            const email = document.getElementById('emailRecuperacao').value;
            if (!email) {
                Swal.showValidationMessage('Informe um e-mail válido');
                return false;
            }

            return fetch('enviar_token_recuperacao.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'email=' + encodeURIComponent(email)
            })
            .then(response => response.text())
            .then(res => {
                Swal.fire('Sucesso', res, 'success');
            })
            .catch(() => {
                Swal.fire('Erro', 'Erro ao enviar e-mail. Tente novamente mais tarde.', 'error');
            });

        }
    });
}