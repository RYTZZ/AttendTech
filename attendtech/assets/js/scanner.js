(function () {
    'use strict';

    var html5QrCode   = null;
    var scanning      = false;
    var lastLRN       = '';
    var lastScanTime  = 0;
    var cameraStream  = null;
    var COOLDOWN_MS   = 3000;

    function el(id) { return document.getElementById(id); }

    function escHtml(str) {
        var d = document.createElement('div');
        d.textContent = String(str == null ? '' : str);
        return d.innerHTML;
    }

    function setStatus(text, cls) {
        var s = el('scannerStatus');
        if (!s) return;
        s.textContent = text;
        s.className   = cls || 'text-muted';
    }

    function setBtns(started) {
        var startBtn = el('startBtn');
        var stopBtn  = el('stopBtn');
        if (startBtn) startBtn.disabled = started;
        if (stopBtn)  stopBtn.disabled  = !started;
    }

    function showIdleState() {
        var c = el('scanResultArea');
        if (!c) return;
        c.innerHTML =
            '<div class="scanner-idle">' +
                '<i class="bi bi-qr-code"></i>' +
                '<p>Scanner ready. Click <strong>Start Camera</strong> to begin scanning.</p>' +
            '</div>';
    }

    function showScanError(msg) {
        var c = el('scanResultArea');
        if (!c) return;
        c.innerHTML =
            '<div class="scan-result">' +
                '<div class="d-flex align-items-center gap-3">' +
                    '<div style="font-size:36px;color:#f87171;"><i class="bi bi-x-circle-fill"></i></div>' +
                    '<div style="font-size:14px;font-weight:600;color:#f87171;">' + escHtml(msg) + '</div>' +
                '</div>' +
            '</div>';
    }

    function showCameraError(title, detail) {
        var c = el('scanResultArea');
        if (!c) return;
        c.innerHTML =
            '<div class="scan-result">' +
                '<div style="text-align:center;padding:8px 0;">' +
                    '<i class="bi bi-camera-video-off" style="font-size:40px;color:#f87171;display:block;margin-bottom:10px;"></i>' +
                    '<div style="font-size:14px;font-weight:700;color:#f87171;margin-bottom:6px;">' + escHtml(title) + '</div>' +
                    '<div style="font-size:12px;color:rgba(255,255,255,0.5);">' + escHtml(detail) + '</div>' +
                    '<button onclick="startScanner()" class="btn btn-sm btn-success mt-3" style="min-width:120px;">' +
                        '<i class="bi bi-arrow-clockwise me-1"></i>Try Again' +
                    '</button>' +
                '</div>' +
            '</div>';
    }

    function classifyCameraError(err) {
        var msg = (err && (err.name || err.message || String(err))).toLowerCase();

        if (msg.indexOf('notallowederror') !== -1 || msg.indexOf('permission denied') !== -1) {
            return {
                title:  'Camera Permission Denied',
                detail: 'Please allow camera access in your browser settings, then try again. ' +
                        '(Chrome: Click the camera icon in the address bar → Allow)'
            };
        }
        if (msg.indexOf('notfounderror') !== -1 || msg.indexOf('devicenotfound') !== -1) {
            return {
                title:  'No Camera Found',
                detail: 'No camera device was detected. Please connect a camera and try again.'
            };
        }
        if (msg.indexOf('notreadableerror') !== -1 || msg.indexOf('could not start') !== -1) {
            return {
                title:  'Camera In Use',
                detail: 'The camera is being used by another application. Close other apps and try again.'
            };
        }
        if (msg.indexOf('aborterror') !== -1) {
            return {
                title:  'Camera Aborted',
                detail: 'Camera access was interrupted. Please try again.'
            };
        }
        if (msg.indexOf('https') !== -1 || msg.indexOf('insecure') !== -1 || msg.indexOf('secure') !== -1) {
            return {
                title:  'HTTPS Required',
                detail: 'Camera access requires HTTPS or localhost. Please use a secure connection.'
            };
        }
        return {
            title:  'Camera Error',
            detail: 'Could not access camera. ' + (err && err.message ? err.message : String(err))
        };
    }

    function isSecureContext() {
        return window.isSecureContext === true ||
               location.protocol === 'https:' ||
               location.hostname === 'localhost' ||
               location.hostname === '127.0.0.1' ||
               location.hostname === '::1';
    }

    function checkMediaDevicesSupport() {
        return !!(navigator.mediaDevices && navigator.mediaDevices.getUserMedia);
    }

    function initScanner() {
        if (typeof Html5Qrcode === 'undefined') {
            setStatus('QR scanner library not loaded. Please refresh.', 'text-danger fw-600');
            return;
        }
        html5QrCode = new Html5Qrcode('qr-reader', { verbose: false });
        showIdleState();
    }

    function startScanner() {
        if (scanning) return;

        if (!isSecureContext()) {
            showCameraError(
                'HTTPS Required',
                'Camera access requires HTTPS or localhost. You are on: ' + location.protocol + '//' + location.hostname
            );
            setStatus('Requires HTTPS or localhost', 'text-danger fw-600');
            return;
        }

        if (!checkMediaDevicesSupport()) {
            showCameraError(
                'Browser Not Supported',
                'Your browser does not support camera access. Please use Chrome, Firefox, or Edge.'
            );
            setStatus('Browser not supported', 'text-danger fw-600');
            return;
        }

        setStatus('Requesting camera permission…', 'text-warning fw-600');
        setBtns(true);

        navigator.mediaDevices.enumerateDevices()
            .then(function (devices) {
                var hasCamera = devices.some(function (d) { return d.kind === 'videoinput'; });
                if (!hasCamera) {
                    setBtns(false);
                    showCameraError('No Camera Found', 'No video input device was detected on this device.');
                    setStatus('No camera detected', 'text-danger fw-600');
                    return;
                }
                startHtml5QrCode();
            })
            .catch(function () {
                startHtml5QrCode();
            });
    }

    function startHtml5QrCode() {
        if (!html5QrCode) {
            initScanner();
        }

        var config = {
            fps: 12,
            qrbox: function (w, h) {
                var size = Math.min(w, h, 280);
                return { width: size, height: size };
            },
            rememberLastUsedCamera: true,
            aspectRatio: 1.0
        };

        html5QrCode.start(
            { facingMode: 'environment' },
            config,
            onScanSuccess,
            function () {}
        ).then(function () {
            scanning = true;
            setBtns(true);
            setStatus('Camera active — Point at student QR code', 'text-success fw-600');
            showIdleState();
        }).catch(function (err) {
            scanning = false;
            setBtns(false);
            var info = classifyCameraError(err);
            showCameraError(info.title, info.detail);
            setStatus(info.title, 'text-danger fw-600');
        });
    }

    function stopScanner() {
        if (!scanning || !html5QrCode) return;
        html5QrCode.stop().then(function () {
            scanning = false;
            setBtns(false);
            setStatus('Camera stopped', 'text-muted');
            showIdleState();
        }).catch(function () {
            scanning = false;
            setBtns(false);
            setStatus('Camera stopped', 'text-muted');
            showIdleState();
        });
    }

    function onScanSuccess(decodedText) {
        var now = Date.now();
        if (decodedText === lastLRN && (now - lastScanTime) < COOLDOWN_MS) return;
        lastLRN      = decodedText;
        lastScanTime = now;

        setStatus('Processing scan…', 'text-warning fw-600');

        fetch(BASE_URL + '/ajax/scan_qr.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'lrn=' + encodeURIComponent(decodedText)
        })
        .then(function (r) {
            if (!r.ok) throw new Error('HTTP ' + r.status);
            return r.json();
        })
        .then(function (data) {
            displayScanResult(data);
            if (data.success) {
                playBeep();
                setStatus('Scan successful — Ready for next student', 'text-success fw-600');
            } else {
                setStatus('Scan rejected — ' + data.message, 'text-warning fw-600');
            }
        })
        .catch(function (err) {
            showScanError('Server error. Please check your connection and try again.');
            setStatus('Server connection error', 'text-danger fw-600');
        });
    }

    function displayScanResult(data) {
        var c = el('scanResultArea');
        if (!c) return;

        if (!data.success) {
            c.innerHTML =
                '<div class="scan-result">' +
                    '<div class="d-flex align-items-center gap-3">' +
                        '<div style="font-size:36px;color:#f87171;"><i class="bi bi-x-circle-fill"></i></div>' +
                        '<div>' +
                            '<div style="font-size:15px;font-weight:700;color:#f87171;">' + escHtml(data.message) + '</div>' +
                            '<div style="font-size:12px;color:rgba(255,255,255,0.4);margin-top:4px;">LRN: ' + escHtml(data.lrn || '—') + '</div>' +
                        '</div>' +
                    '</div>' +
                '</div>';
            return;
        }

        var s         = data.student;
        var photoHtml = s.photo
            ? '<img src="' + BASE_URL + '/' + escHtml(s.photo) + '" alt="" style="width:100%;height:100%;object-fit:cover;">'
            : '<i class="bi bi-person-fill"></i>';

        var typeClass = data.type === 'time_in' ? 'in' : (data.type === 'time_out' ? 'out' : 'rejected');
        var typeLabel = data.type === 'time_in' ? '✓ Time In' : (data.type === 'time_out' ? '↑ Time Out' : data.type);
        var lateTag   = data.status === 'Late'
            ? '<span style="opacity:.75;margin-left:4px;">· Late</span>'
            : '';

        c.innerHTML =
            '<div class="scan-result">' +
                '<div class="student-card">' +
                    '<div class="s-photo">' + photoHtml + '</div>' +
                    '<div>' +
                        '<div class="s-name">'   + escHtml(s.full_name)                           + '</div>' +
                        '<div class="s-detail">' + escHtml(s.grade_name) + ' — ' + escHtml(s.section_name) + ' &nbsp;|&nbsp; LRN: ' + escHtml(s.lrn) + '</div>' +
                        '<div class="s-time">'   + escHtml(data.time)                             + '</div>' +
                        '<span class="s-status-badge ' + typeClass + '">' + typeLabel + lateTag + '</span>' +
                    '</div>' +
                '</div>' +
            '</div>';
    }

    function playBeep() {
        try {
            var AC = window.AudioContext || window.webkitAudioContext;
            if (!AC) return;
            var ctx  = new AC();
            var osc  = ctx.createOscillator();
            var gain = ctx.createGain();
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.type = 'sine';
            osc.frequency.setValueAtTime(880, ctx.currentTime);
            gain.gain.setValueAtTime(0.25, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.35);
            osc.start(ctx.currentTime);
            osc.stop(ctx.currentTime + 0.35);
        } catch (e) {}
    }

    window.startScanner = startScanner;
    window.stopScanner  = stopScanner;

    document.addEventListener('DOMContentLoaded', function () {
        initScanner();

        var startBtn = el('startBtn');
        var stopBtn  = el('stopBtn');
        if (startBtn) startBtn.addEventListener('click', startScanner);
        if (stopBtn)  stopBtn.addEventListener('click', stopScanner);

        window.addEventListener('beforeunload', function () {
            if (scanning && html5QrCode) {
                try { html5QrCode.stop(); } catch (e) {}
            }
        });

        document.addEventListener('visibilitychange', function () {
            if (document.hidden && scanning) {
                stopScanner();
            }
        });
    });
}());
