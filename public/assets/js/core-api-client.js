(function (window, document) {
    'use strict';

    function csrfKey() {
        return window.coreAppCsrfKey || 'fuel_csrf_token';
    }

    function csrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"], meta[name="fuel-csrf-token"]');
        var input = document.querySelector('input[name="' + csrfKey() + '"]');

        return (meta && meta.getAttribute('content')) ||
            window.coreAppCsrfToken ||
            window.csrfToken ||
            (typeof window.fuel_csrf_token === 'function' ? window.fuel_csrf_token() : '') ||
            (input && input.value) ||
            '';
    }

    function updateCsrf(payload) {
        if (payload && payload.csrf_token) {
            window.coreAppCsrfToken = payload.csrf_token;
        }
    }

    function isJsonResponse(response) {
        var type = response && response.headers ? response.headers.get('Content-Type') || '' : '';
        return type.toLowerCase().indexOf('application/json') !== -1 || type.toLowerCase().indexOf('+json') !== -1;
    }

    function errorCodeForStatus(status) {
        if (status === 401) return 'auth_required';
        if (status === 403) return 'permission_denied';
        if (status === 404) return 'endpoint_not_found';
        if (status >= 500) return 'server_error';
        return 'request_error';
    }

    function messageForCode(code) {
        var messages = {
            auth_required: 'Tu sesión expiró. Vuelve a iniciar sesión.',
            permission_denied: 'No tienes permiso para realizar esta acción.',
            endpoint_not_found: 'Endpoint no encontrado.',
            non_json_response: 'El servidor devolvió una respuesta inválida.',
            server_error: 'Error del servidor.',
            network_error: 'No se pudo conectar con el servidor.',
            request_error: 'No se pudo completar la solicitud.'
        };

        return messages[code] || messages.request_error;
    }

    function normalizePayload(response, payload) {
        payload = payload && typeof payload === 'object' ? payload : {};
        updateCsrf(payload);

        var status = response ? response.status : 0;
        var code = '';
        var ok = !!(response && response.ok);
        var errors = Array.isArray(payload.errors) ? payload.errors.slice() : [];

        if (payload.success === false) {
            ok = false;
        }

        if (!ok) {
            code = status === 401 || status === 403 || status === 404 || status >= 500
                ? errorCodeForStatus(status)
                : (errors.length ? String(errors[0]) : errorCodeForStatus(status));
        }

        return {
            ok: ok,
            status: status,
            payload: payload,
            message: code && (status === 401 || status === 403 || status === 404 || status >= 500)
                ? messageForCode(code)
                : safeMessage(payload, code ? messageForCode(code) : ''),
            errors: errors,
            code: code
        };
    }

    function parseResponse(response) {
        if (!isJsonResponse(response)) {
            return response.text().then(function () {
                var code = errorCodeForStatus(response.status);
                if (response.status >= 200 && response.status < 300) {
                    code = 'non_json_response';
                }

                return {
                    ok: false,
                    status: response.status,
                    payload: {},
                    message: messageForCode(code),
                    errors: [code],
                    code: code
                };
            });
        }

        return response.text().then(function (text) {
            var payload = {};
            try {
                payload = text ? JSON.parse(text) : {};
            } catch (error) {
                return {
                    ok: false,
                    status: response.status,
                    payload: {},
                    message: messageForCode('non_json_response'),
                    errors: ['non_json_response'],
                    code: 'non_json_response'
                };
            }

            return normalizePayload(response, payload);
        });
    }

    function normalizeError(error) {
        return {
            ok: false,
            status: 0,
            payload: {},
            message: error && error.message ? error.message : messageForCode('network_error'),
            errors: ['network_error'],
            code: 'network_error'
        };
    }

    function withCsrf(data) {
        var key = csrfKey();
        var token = csrfToken();

        if (data instanceof FormData) {
            if (token && !data.has(key)) {
                data.append(key, token);
            }
            return data;
        }

        data = data && typeof data === 'object' ? Object.assign({}, data) : {};
        if (token) {
            data[key] = token;
        }
        return data;
    }

    function request(url, options) {
        options = options || {};
        var fetchOptions = Object.assign({
            method: 'GET',
            credentials: 'same-origin',
            headers: {}
        }, options);

        fetchOptions.headers = Object.assign({ 'Accept': 'application/json' }, fetchOptions.headers || {});

        return window.fetch(url, fetchOptions)
            .then(parseResponse)
            .catch(function (error) {
                return normalizeError(error);
            });
    }

    function get(url, options) {
        return request(url, Object.assign({}, options || {}, { method: 'GET' }));
    }

    function post(url, data, options) {
        options = options || {};
        data = withCsrf(data || {});
        var fetchOptions = Object.assign({}, options, { method: 'POST' });

        if (data instanceof FormData) {
            fetchOptions.body = data;
            fetchOptions.headers = Object.assign({ 'Accept': 'application/json', 'X-CSRF-Token': csrfToken() }, options.headers || {});
        } else {
            fetchOptions.body = JSON.stringify(data);
            fetchOptions.headers = Object.assign({
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-Token': data[csrfKey()] || csrfToken()
            }, options.headers || {});
        }

        return request(url, fetchOptions);
    }

    function isAuthError(responseOrPayload) {
        var code = responseOrPayload && (responseOrPayload.code ||
            (Array.isArray(responseOrPayload.errors) && responseOrPayload.errors[0]));
        var status = responseOrPayload && responseOrPayload.status;
        return status === 401 || code === 'auth_required' || code === 'password_change_required';
    }

    function redirectToLoginIfNeeded(responseOrPayload, loginUrl) {
        if (isAuthError(responseOrPayload) && loginUrl) {
            window.location.href = loginUrl;
            return true;
        }
        return false;
    }

    function safeMessage(payload, fallback) {
        if (payload && typeof payload.message === 'string' && payload.message.trim() !== '') {
            return payload.message;
        }
        if (payload && typeof payload.error === 'string' && payload.error.trim() !== '') {
            return payload.error;
        }
        if (typeof fallback === 'string') {
            return fallback;
        }
        return messageForCode('request_error');
    }

    window.CoreApiClient = {
        request: request,
        get: get,
        post: post,
        parseResponse: parseResponse,
        normalizeError: normalizeError,
        csrfToken: csrfToken,
        withCsrf: withCsrf,
        isAuthError: isAuthError,
        isJsonResponse: isJsonResponse,
        redirectToLoginIfNeeded: redirectToLoginIfNeeded,
        safeMessage: safeMessage
    };
})(window, document);
