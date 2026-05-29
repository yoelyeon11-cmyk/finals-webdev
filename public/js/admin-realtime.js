(function (window) {
    'use strict';

    const LIVE_CHECK_MS = 1000;
    let ws = null;
    let pollTimer = null;
    let knownFingerprints = {};
    const listeners = new Set();

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function dispatch(event) {
        listeners.forEach(function (listener) {
            try {
                listener(event);
            } catch (e) {
                // ignore listener errors
            }
        });
        window.dispatchEvent(new CustomEvent('admin:realtime', { detail: event }));
    }

    function connectWebSocket(url) {
        if (!url || ws) {
            return;
        }
        try {
            ws = new WebSocket(url);
            ws.onmessage = function (message) {
                try {
                    const event = JSON.parse(message.data || '{}');
                    if (event?.type && event.type !== 'hello') {
                        dispatch(event);
                    }
                } catch (e) {
                    // ignore malformed payloads
                }
            };
            ws.onclose = function () {
                ws = null;
            };
        } catch (e) {
            ws = null;
        }
    }

    async function pollUpdates(updatesUrl) {
        try {
            const res = await fetch(updatesUrl, {
                credentials: 'include',
                headers: { Accept: 'application/json' },
            });
            if (!res.ok) {
                return;
            }
            const payload = await res.json();
            const data = payload?.data || {};
            Object.keys(data).forEach(function (key) {
                if (!key.endsWith('Fingerprint') && !key.startsWith('latest')) {
                    return;
                }
                if (key.endsWith('Fingerprint')) {
                    const previous = knownFingerprints[key];
                    if (previous && previous !== data[key]) {
                        dispatch({ type: 'fingerprint.changed', payload: { key, value: data[key] } });
                    }
                    knownFingerprints[key] = data[key];
                }
            });
        } catch (e) {
            // silent fallback
        }
    }

    function initHub(config) {
        const updatesUrl = config?.updatesUrl;
        const websocketUrl = config?.websocketUrl || '';
        if (config?.fingerprints) {
            knownFingerprints = Object.assign({}, config.fingerprints);
        }
        if (pollTimer) {
            clearInterval(pollTimer);
        }
        connectWebSocket(websocketUrl);
        if (updatesUrl) {
            pollUpdates(updatesUrl);
            pollTimer = setInterval(function () {
                if (!document.hidden) {
                    pollUpdates(updatesUrl);
                }
            }, LIVE_CHECK_MS);
        }
    }

    function subscribe(listener) {
        if (typeof listener !== 'function') {
            return function () {};
        }
        listeners.add(listener);
        return function () {
            listeners.delete(listener);
        };
    }

    function matchesEvent(event, types) {
        return types.includes(event?.type);
    }

    function highlightRow(node) {
        if (!node) {
            return;
        }
        node.classList.add('admin-live-highlight');
        window.setTimeout(function () {
            node.classList.remove('admin-live-highlight');
        }, 2200);
    }

    async function syncDataTable(options) {
        const table = options.table;
        const syncUrl = options.syncUrl;
        const fingerprintKey = options.fingerprintKey;
        const buildRows = options.buildRows;
        const eventTypes = options.eventTypes || [];
        let knownFingerprint = options.initialFingerprint || '';

        async function refresh() {
            try {
                const res = await fetch(syncUrl, {
                    credentials: 'include',
                    headers: { Accept: 'application/json' },
                });
                if (!res.ok) {
                    return;
                }
                const payload = await res.json();
                const data = payload?.data;
                if (!payload?.success || !data) {
                    return;
                }
                knownFingerprint = data.fingerprint || knownFingerprint;
                table.clear();
                (buildRows(data.rows || data.cards || []) || []).forEach(function (row) {
                    table.row.add(row);
                });
                table.draw(false);
            } catch (e) {
                // silent fallback
            }
        }

        function onRealtimeEvent(event) {
            if (matchesEvent(event, eventTypes)) {
                refresh();
                return;
            }
            if (event?.type === 'fingerprint.changed' && event?.payload?.key === fingerprintKey) {
                refresh();
            }
        }

        const unsubscribe = subscribe(onRealtimeEvent);
        window.addEventListener('admin:realtime', function (e) {
            onRealtimeEvent(e.detail);
        });

        return {
            refresh: refresh,
            destroy: unsubscribe,
        };
    }

    window.AdminRealtime = {
        initHub: initHub,
        subscribe: subscribe,
        syncDataTable: syncDataTable,
        escapeHtml: escapeHtml,
        highlightRow: highlightRow,
        matchesEvent: matchesEvent,
    };
})(window);
