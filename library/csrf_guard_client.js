/* JSからの送信にCSRFトークンを付ける（library/csrf_guard.php が <head> にインラインで差し込む） */
(function () {
    if (window.__readnestCsrfGuard) return;
    window.__readnestCsrfGuard = true;

    var token = window.READNEST_CSRF_TOKEN;
    if (!token) return;

    var SAFE_METHODS = ['GET', 'HEAD', 'OPTIONS'];

    function isSameOrigin(url) {
        try {
            return new URL(url, location.href).origin === location.origin;
        } catch (e) {
            return false;
        }
    }

    function needsToken(method, url) {
        return SAFE_METHODS.indexOf(String(method || 'GET').toUpperCase()) === -1 && isSameOrigin(url);
    }

    // fetch
    if (window.fetch) {
        var originalFetch = window.fetch;
        window.fetch = function (input, init) {
            var isRequest = typeof Request !== 'undefined' && input instanceof Request;
            var url = isRequest ? input.url : String(input);
            var method = (init && init.method) || (isRequest ? input.method : 'GET');
            if (needsToken(method, url)) {
                var headers = new Headers((init && init.headers) || (isRequest ? input.headers : undefined));
                if (!headers.has('X-CSRF-Token')) {
                    headers.set('X-CSRF-Token', token);
                }
                init = Object.assign({}, init || {}, { headers: headers });
            }
            return originalFetch.call(this, input, init);
        };
    }

    // XMLHttpRequest（jQuery.ajax 等もこれを使う）
    if (window.XMLHttpRequest) {
        var originalOpen = XMLHttpRequest.prototype.open;
        var originalSend = XMLHttpRequest.prototype.send;
        var originalSetHeader = XMLHttpRequest.prototype.setRequestHeader;
        XMLHttpRequest.prototype.open = function (method, url) {
            this.__csrfNeeded = needsToken(method, url);
            this.__csrfSet = false;
            return originalOpen.apply(this, arguments);
        };
        XMLHttpRequest.prototype.setRequestHeader = function (name) {
            if (String(name).toLowerCase() === 'x-csrf-token') this.__csrfSet = true;
            return originalSetHeader.apply(this, arguments);
        };
        XMLHttpRequest.prototype.send = function () {
            if (this.__csrfNeeded && !this.__csrfSet) {
                originalSetHeader.call(this, 'X-CSRF-Token', token);
            }
            return originalSend.apply(this, arguments);
        };
    }

    // フォーム（サーバーで差し込めないJS生成のフォームや、後から書き換えたフォーム用）
    function addTokenField(form) {
        if (!form || String(form.method || '').toUpperCase() !== 'POST') return;
        if (!isSameOrigin(form.getAttribute('action') || location.href)) return;
        if (form.querySelector('input[name="csrf_token"]')) return;
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'csrf_token';
        input.value = token;
        form.appendChild(input);
    }

    document.addEventListener('submit', function (e) {
        addTokenField(e.target);
    }, true);

    // form.submit() は submit イベントが発火しない
    var originalSubmit = HTMLFormElement.prototype.submit;
    HTMLFormElement.prototype.submit = function () {
        addTokenField(this);
        return originalSubmit.apply(this, arguments);
    };
})();
