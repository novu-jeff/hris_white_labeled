export function initializeClockFace() {
    $(function () {

        let isFaceDetected = false;
        let stream = null;
        let isCapturing = false;

        const $video = $('#video');
        const $canvas = $('#canvas');
        const canvas = $canvas[0];
        const context = canvas.getContext('2d');
        const captureElement = document.querySelector('.camera');
        const $alertContainer = $('.alert-container');
        const $clockPreview = $('#clockInPreviewImage');
        const $clockModal = $('#clockInModal');
        const clockModal = new bootstrap.Modal($clockModal[0], { backdrop: 'static', keyboard: false });

        startCamera();

        Livewire.on('loadDefaults', () => {
            startCamera();
        });

        $(document).on('click', '.clock-process', async function () {
            const status = $(this).data('status');

            if (!captureElement) return console.error('Camera element not found.');
            if (isCapturing) return;
            isCapturing = true;

            try {
                if (status === 'Done') {
                    showAlert('Please be informed', 'You\'ve completed today’s work.');
                    return;
                }
                if (!isFaceDetected) {
                    showAlert('No Face Detected', 'Please ensure your face is visible to the camera.');
                    return;
                }

                const imageData = await captureSnapshot();
            
            // ✅ Check actual latitude and longitude
           /* if (latitude === null || longitude === null) {
                return showAlert('No Location Detected', 'Please enable your GPS/location services.');
            }*/

                if (imageData) {
                    $clockPreview.attr('src', imageData);
                    showCountdownModal(imageData, isFaceDetected, false, null);
                    stopCamera();
                }
            } finally {
                isCapturing = false;
            }
        });

        $(document).on('click', '.clock-process-forced', async function () {
            if (!captureElement) return console.error('Camera element not found.');
            if (isCapturing) return;
            isCapturing = true;

            try {
                if (!isFaceDetected) {
                    showAlert('No Face Detected', 'Please ensure your face is visible to the camera.');
                    return;
                }

                const imageData = await captureSnapshot();

                if (imageData) {
                    $clockPreview.attr('src', imageData);
                    showCountdownModal(imageData, isFaceDetected, true, null);
                    stopCamera();
                }
            } finally {
                isCapturing = false;
            }
        });

        $(document).on('click', '.retakeButton', () => {
            // "Retake" = close modal + reset preview + resume camera.
            try { clockModal.hide(); } catch (_) {}
            $clockPreview.attr('src', '');
            Livewire.dispatch('resetCapture');
            startCamera();
        });

        // If user closes the modal, resume camera for the next capture.
        $clockModal.on('hidden.bs.modal', () => {
            $clockPreview.attr('src', '');
            Livewire.dispatch('resetCapture');
            startCamera();
        });

        async function captureSnapshot() {
            // Prefer a real video frame snapshot (prevents distortion vs html2canvas on a video element).
            const videoEl = $video[0];
            const w = videoEl?.videoWidth ?? 0;
            const h = videoEl?.videoHeight ?? 0;

            if (w > 0 && h > 0) {
                const snapCanvas = document.createElement('canvas');
                snapCanvas.width = w;
                snapCanvas.height = h;

                const ctx = snapCanvas.getContext('2d');
                // Don't mirror the captured image since video preview is already mirrored
                // This prevents double mirroring
                ctx.drawImage(videoEl, 0, 0, w, h);

                // Optional watermark draw (same-origin logo). If it fails, we still keep the photo.
                try {
                    const logo = document.querySelector('.watermark img');
                    if (logo?.complete && logo.naturalWidth > 0 && logo.naturalHeight > 0) {
                        const pad = Math.round(w * 0.02);
                        const maxLogoW = Math.min(180, Math.round(w * 0.28));
                        const ratio = logo.naturalHeight / logo.naturalWidth;
                        const logoW = maxLogoW;
                        const logoH = Math.round(logoW * ratio);
                        ctx.globalAlpha = 0.9;
                        ctx.drawImage(logo, w - logoW - pad, pad, logoW, logoH);
                        ctx.globalAlpha = 1;
                    }
                } catch (e) {
                    console.warn('Watermark draw skipped:', e);
                }

                return snapCanvas.toDataURL('image/png');
            }

            // Fallback (rare): html2canvas of the container.
            const canvas = await html2canvas(captureElement, {
                useCORS: true,
                allowTaint: true,
                scale: window.devicePixelRatio,
            });
            return canvas.toDataURL('image/png');
        }

        function showAlert(title, text) {
            Swal.fire({ title, text, icon: 'info' });
        }

        async function showCountdownModal(imageData, faceStatus, forced = false) {
            clockModal.show();
            const proceedBtn = $clockModal.find('button[type="submit"]')[0];
            const proceedLabel = proceedBtn.querySelector('span');
            let countdown = 5;

           // proceedBtn.disabled = true;
           // proceedLabel.textContent = `Proceed (${countdown})`;
           proceedLabel.textContent = `Proceed`;
            proceedBtn.disabled = false;

          /*  const interval = setInterval(() => {
                countdown--;
                proceedLabel.textContent = countdown > 0 ? `Proceed (${countdown})` : 'Proceed';
                if (countdown <= 0) {
                    clearInterval(interval);
                    proceedBtn.disabled = false;
                }
            }, 1000);*/

            let locationPayload = null;
            try {
                if (window.getGPSCoordinates) {
                    const coords = await window.getGPSCoordinates();
                    locationPayload = {
                        coordinates: {
                            lat: coords.lat,
                            lng: coords.lng,
                        },
                        provider: 'browser-geolocation',
                    };
                }
            } catch (e) {
                console.warn('GPS not available or denied:', e);
            }

            Livewire.dispatch('imageCaptured', [imageData, faceStatus, forced, locationPayload]);
        }

        async function startCamera() {
            try {
                if (stream) return; // prevent double-start
                stream = await navigator.mediaDevices.getUserMedia({ video: true });
                $video[0].srcObject = stream;
                
                // Apply mirror transform to video preview
                $video[0].style.transform = 'scaleX(-1)';
                $video[0].style.webkitTransform = 'scaleX(-1)';

                $video[0].onloadeddata = async () => {
                    await loadFaceApiModels();
                    await $video[0].play();
                    detectFacesLoop();
                };
            } catch (err) {
                showAlert('Please be informed', 'Camera and location access are required to continue.');
            }
        }

        function stopCamera() {
            if (stream) {
                stream.getTracks().forEach(track => track.stop());
                stream = null;
                $video[0].srcObject = null;
            }
        }

        async function loadFaceApiModels() {
            try {
                await faceapi.nets.tinyFaceDetector.loadFromUri('/faceapi');
                console.log('Face API model loaded');
            } catch (error) {
                console.error('Failed to load Face API model:', error);
            }
        }

        async function detectFacesLoop() {
            const options = new faceapi.TinyFaceDetectorOptions({ inputSize: 160, scoreThreshold: 0.5 });

            async function detect() {
                try {
                    if ($video[0].readyState >= 2) {
                        const results = await faceapi.detectAllFaces($video[0], options);
                        if (results.length === 0) {
                            $alertContainer.html(`
                                <div class="alert-no-face">
                                    <div>Face Is Not Detected</div>
                                </div>
                            `);
                            isFaceDetected = false;
                        } else {
                            $alertContainer.empty();
                            isFaceDetected = true;
                        }
                    }
                } catch (err) {
                    console.error('Face detection error:', err);
                }
                requestAnimationFrame(detect);
            }

            detect();
        }

        $video.on('loadedmetadata', () => {
            canvas.width = $video[0].videoWidth;
            canvas.height = $video[0].videoHeight;
        });
    });
}
