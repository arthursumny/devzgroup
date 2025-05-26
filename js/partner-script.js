document.addEventListener('DOMContentLoaded', function() {
    const requestAccessLink = document.getElementById('requestAccessLink');
    const modal = document.getElementById('requestAccessModal');
    const closeButton = modal.querySelector('.close-button');
    const accessRequestForm = document.getElementById('accessRequestForm');
    const partnerLoginForm = document.getElementById('partnerLoginForm');

    // Show modal
    if (requestAccessLink) {
        requestAccessLink.addEventListener('click', function(event) {
            event.preventDefault();
            modal.style.display = 'block';
        });
    }

    // Close modal with X button
    if (closeButton) {
        closeButton.addEventListener('click', function() {
            modal.style.display = 'none';
        });
    }

    // Close modal if clicked outside of modal-content
    window.addEventListener('click', function(event) {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });

    // Handle Access Request Form Submission
    if (accessRequestForm) {
        accessRequestForm.addEventListener('submit', async function(event) {
            event.preventDefault(); // Prevent default form submission

            const name = document.getElementById('reqName').value;
            const phone = document.getElementById('reqPhone').value;
            const email = document.getElementById('reqEmail').value;
            const city = document.getElementById('reqCity').value;
            const submitButton = accessRequestForm.querySelector('button[type="submit"]');
            const originalButtonText = submitButton.textContent;

            submitButton.disabled = true;
            submitButton.textContent = 'Enviando...';

            const formData = {
                name: name,
                phone: phone,
                email: email,
                city: city,
                requestType: 'partnerAccess' 
            };

            try {
                const response = await fetch('api/send_email.php', { // Certifique-se que este é o endpoint correto para solicitação de acesso
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(formData),
                });

                const result = await response.json();

                if (response.ok) {
                    alert(result.message || 'Solicitação enviada com sucesso! Entraremos em contato em breve.');
                    modal.style.display = 'none'; 
                    accessRequestForm.reset(); 
                } else {
                    alert(result.message || 'Erro ao enviar a solicitação. Por favor, tente novamente.');
                }
            } catch (error) {
                console.error('Erro ao enviar formulário de acesso:', error);
                alert('Ocorreu um erro de comunicação. Por favor, tente novamente mais tarde.');
            } finally {
                submitButton.disabled = false;
                submitButton.textContent = originalButtonText;
            }
        });
    }

    // Handle Partner Login Form
    if (partnerLoginForm) {
        partnerLoginForm.addEventListener('submit', async function(event) {
            event.preventDefault();
            
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;
            const submitButton = partnerLoginForm.querySelector('button[type="submit"]');
            const originalButtonText = submitButton.textContent;
            const loginMessageElement = document.getElementById('loginMessage'); // Elemento para exibir mensagens de erro/sucesso

            if(loginMessageElement) loginMessageElement.textContent = ''; // Limpa mensagens anteriores
            submitButton.disabled = true;
            submitButton.textContent = 'Autenticando...';

            try {
                const response = await fetch('api/login_handler.php', { // Endpoint para o login
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ username: username, password: password }),
                    credentials: 'include' // Essencial para sessões PHP com fetch
                });

                const result = await response.json();

                if (response.ok && result.user_type) {
                    // Login bem-sucedido
                    if(loginMessageElement) {
                        loginMessageElement.textContent = result.message || 'Login bem-sucedido! Redirecionando...';
                        loginMessageElement.style.color = 'green';
                    } else {
                        alert(result.message || 'Login bem-sucedido! Redirecionando...');
                    }
                    
                    // Redirecionar com base no tipo de usuário
                    if (result.user_type === 'parceiro') {
                        window.location.href = 'parceiro-dashboard.php';
                    } else if (result.user_type === 'indicador') {
                        window.location.href = 'indicador-dashboard.php';
                    } else {
                        // Tipo de usuário desconhecido
                        const unknownUserMessage = 'Tipo de usuário desconhecido. Contate o suporte.';
                        if(loginMessageElement) {
                            loginMessageElement.textContent = unknownUserMessage;
                            loginMessageElement.style.color = 'red';
                        } else {
                            alert(unknownUserMessage);
                        }
                    }
                } else {
                    // Falha no login
                    const errorMessage = result.message || 'Usuário ou senha inválidos.';
                    if(loginMessageElement) {
                        loginMessageElement.textContent = errorMessage;
                        loginMessageElement.style.color = 'red';
                    } else {
                        alert(errorMessage);
                    }
                }

            } catch (error) {
                console.error('Erro na requisição de login:', error);
                const networkErrorMessage = 'Ocorreu um erro de comunicação. Tente novamente.';
                if(loginMessageElement) {
                    loginMessageElement.textContent = networkErrorMessage;
                    loginMessageElement.style.color = 'red';
                } else {
                    alert(networkErrorMessage);
                }
            } finally {
                submitButton.disabled = false;
                submitButton.textContent = originalButtonText;
            }
        });
    }
});