/* SIAMS: small page helpers. Everything still works if JavaScript is turned off. */

// Show / hide password: adds an eye button to every password field.
document.querySelectorAll('input[type="password"]').forEach(function (input) {
    var group = document.createElement('div');
    group.className = 'input-group';
    input.parentNode.insertBefore(group, input);
    group.appendChild(input);

    var button = document.createElement('button');
    button.type = 'button';
    button.className = 'btn btn-outline-secondary password-toggle';
    button.setAttribute('aria-label', 'Show password');
    button.setAttribute('aria-pressed', 'false');
    button.title = 'Show password';
    button.innerHTML = '<i class="bi bi-eye" aria-hidden="true"></i>';
    group.appendChild(button);

    button.addEventListener('click', function () {
        var show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        button.innerHTML = '<i class="bi ' + (show ? 'bi-eye-slash' : 'bi-eye') + '" aria-hidden="true"></i>';
        button.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
        button.setAttribute('aria-pressed', show ? 'true' : 'false');
        button.title = show ? 'Hide password' : 'Show password';
        input.focus();
    });

    // Hide the password again before the form is sent, so the browser never saves it as plain text.
    if (input.form) {
        input.form.addEventListener('submit', function () { input.type = 'password'; });
    }
});
