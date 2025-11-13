/**
 * Authentication Page JavaScript
 */

// Redirect if already authenticated
requireGuest();

// Tab switching
document.querySelectorAll('.tk-auth-tab').forEach(tab => {
    tab.addEventListener('click', () => {
        const targetTab = tab.dataset.tab;

        // Update tab buttons
        document.querySelectorAll('.tk-auth-tab').forEach(t => t.classList.remove('active'));
        tab.classList.add('active');

        // Update forms
        document.querySelectorAll('.tk-auth-form').forEach(form => form.classList.remove('active'));
        document.getElementById(`${targetTab}-form`).classList.add('active');
    });
});

// Load countries for registration
async function loadCountries() {
    try {
        const data = await API.getCountries();
        const select = document.getElementById('register-country');

        data.countries.forEach(country => {
            const option = document.createElement('option');
            option.value = country.country_code;
            option.textContent = `${country.flag_emoji} ${country.name}`;
            select.appendChild(option);
        });
    } catch (error) {
        console.error('Failed to load countries:', error);
    }
}

// Login form
document.getElementById('form-login').addEventListener('submit', async (e) => {
    e.preventDefault();

    const errorDiv = document.getElementById('login-error');
    const successDiv = document.getElementById('login-success');
    const submitBtn = e.target.querySelector('button[type="submit"]');

    errorDiv.style.display = 'none';
    successDiv.style.display = 'none';
    submitBtn.disabled = true;
    submitBtn.textContent = 'Entrando...';

    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData);

    try {
        const response = await API.login(data);

        if (response.success) {
            setAuthToken(response.token);

            successDiv.textContent = 'Login realizado com sucesso! Redirecionando...';
            successDiv.style.display = 'block';

            setTimeout(() => {
                window.location.href = 'dashboard.html';
            }, 1000);
        }
    } catch (error) {
        errorDiv.textContent = error.message || 'Erro ao fazer login. Verifique suas credenciais.';
        errorDiv.style.display = 'block';
        submitBtn.disabled = false;
        submitBtn.textContent = 'Entrar';
    }
});

// Register form
document.getElementById('form-register').addEventListener('submit', async (e) => {
    e.preventDefault();

    const errorDiv = document.getElementById('register-error');
    const successDiv = document.getElementById('register-success');
    const submitBtn = e.target.querySelector('button[type="submit"]');

    errorDiv.style.display = 'none';
    successDiv.style.display = 'none';
    submitBtn.disabled = true;
    submitBtn.textContent = 'Criando conta...';

    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData);

    // Remove empty optional fields
    Object.keys(data).forEach(key => {
        if (!data[key]) delete data[key];
    });

    try {
        const response = await API.register(data);

        if (response.success) {
            setAuthToken(response.token);

            successDiv.textContent = 'Conta criada com sucesso! Redirecionando...';
            successDiv.style.display = 'block';

            setTimeout(() => {
                window.location.href = 'dashboard.html';
            }, 1000);
        }
    } catch (error) {
        errorDiv.textContent = error.message || 'Erro ao criar conta. Tente novamente.';
        errorDiv.style.display = 'block';
        submitBtn.disabled = false;
        submitBtn.textContent = 'Criar Conta';
    }
});

// Load countries on page load
loadCountries();
