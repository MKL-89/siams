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

// Keep tabs that share a login session in step.
// When someone logs in or out in one tab, other tabs that use the same session refresh at once,
// so they show the login page or the new account instead of an old page that would be refused.
(function () {
    var body = document.body;
    if (!body || !('BroadcastChannel' in window)) {
        return;
    }
    var uid = body.dataset.uid || '0';
    var channel = new BroadcastChannel('siams-state-' + body.dataset.slot);
    channel.onmessage = function (ev) {
        if (ev.data && ev.data.type === 'state' && ev.data.uid !== uid) {
            location.reload();
        }
    };
    channel.postMessage({ type: 'state', uid: uid });
})();

// One account per tab.
// A browser shares its login cookies between tabs, so a tab opened by typing the address (or from a
// bookmark) would join the account already logged in in another tab, and logging out in either tab
// would log out both. Each tab asks the other open tabs whether one of them is already using the
// account at this address; if so, this tab is given its own fresh login session (new_session.php).
// Links followed from a SIAMS page keep the same account, as users expect.
(function () {
    var body = document.body;
    if (!body || !body.dataset.auth || !('BroadcastChannel' in window)) {
        return;
    }
    var base = body.dataset.base;
    var slot = body.dataset.slot;
    var tab, claimed;
    try {
        tab = sessionStorage.getItem('siams_tab');
        if (!tab) {
            tab = Math.random().toString(36).slice(2) + Date.now().toString(36);
            sessionStorage.setItem('siams_tab', tab);
        }
        claimed = sessionStorage.getItem('siams_slot_' + slot) === '1';
    } catch (e) {
        return;   // storage blocked: keep the normal behaviour
    }

    var channel = new BroadcastChannel('siams-slot-' + slot);
    var fromSiams = document.referrer.indexOf(location.origin + base + '/') === 0;

    // This tab uses the account at this address: answer other tabs that ask.
    function keep() {
        try { sessionStorage.setItem('siams_slot_' + slot, '1'); } catch (e) { /* ignore */ }
        channel.onmessage = function (ev) {
            if (ev.data && ev.data.type === 'who' && ev.data.tab !== tab) {
                channel.postMessage({ type: 'mine', tab: tab });
            }
        };
    }

    if (claimed || fromSiams) {
        keep();
        return;
    }

    // A tab opened directly: is another open tab already using this account?
    var taken = false;
    channel.onmessage = function (ev) {
        if (ev.data && ev.data.type === 'mine' && ev.data.tab !== tab) {
            taken = true;
        }
    };
    channel.postMessage({ type: 'who', tab: tab });
    setTimeout(function () {
        if (taken) {
            location.replace(base + '/new_session.php');
        } else {
            keep();
        }
    }, 250);
})();
