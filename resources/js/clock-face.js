/**
 * Clock face UI: camera stream, capture from video, send to Livewire, show preview modal.
 */

let videoStream = null;

export function initializeClockFace() {
  const container = document.querySelector('.clockinout');
  const video = document.getElementById('video');
  const canvas = document.getElementById('canvas');

  if (!container || !video || !canvas) return;

  // Prevent attaching multiple listeners if Livewire / scripts re-init this view
  if (container.dataset.clockFaceInitialized === '1') {
    return;
  }
  container.dataset.clockFaceInitialized = '1';

  // Request location early so the browser prompt appears before capture (user can allow once).
  getLocation().catch(() => {});

  // Start camera and stream to video
  startCamera(video, container).catch((err) => {
    console.error('Clock face: camera error', err);
    showCameraError(container, err);
  });

  // Capture & Proceed
  container.addEventListener('click', (e) => {
    const captureBtn = e.target.closest('.clock-process');
    if (captureBtn) {
      e.preventDefault();
      captureAndOpenModal(video, canvas, false);
      return;
    }
    const forcedBtn = e.target.closest('.clock-process-forced');
    if (forcedBtn) {
      e.preventDefault();
      captureAndOpenModal(video, canvas, true);
      return;
    }
    const retakeBtn = e.target.closest('.retakeButton');
    if (retakeBtn) {
      e.preventDefault();
      retake();
      return;
    }
  });
}

function startCamera(video, container) {
  return navigator.mediaDevices
    .getUserMedia({ video: { facingMode: 'user' }, audio: false })
    .then((stream) => {
      videoStream = stream;
      video.srcObject = stream;
      // Clear any previous camera error once we have a stream
      if (container) {
        const alertEl = container.querySelector('.alert-container');
        if (alertEl) {
          alertEl.innerHTML = '';
        }
      }
      return video.play();
    });
}

function showCameraError(container, err) {
  const alertEl = container.querySelector('.alert-container');
  if (!alertEl) return;
  const msg =
    err.name === 'NotAllowedError'
      ? 'Camera access was denied. Please allow camera and refresh.'
      : err.name === 'NotFoundError'
        ? 'No camera found.'
        : 'Could not start camera. Please check permissions.';
  alertEl.innerHTML = `<div class="alert alert-warning mb-0">${msg}</div>`;
}

function captureAndOpenModal(video, canvas, isForcedOut) {
  if (!video.srcObject || video.readyState < 2) {
    if (window.Livewire) {
      window.Livewire.dispatch('alert', [
        {
          showAlert: true,
          status: 'info',
          title: 'Camera not ready',
          message: 'Please wait for the camera to load, or allow camera access.',
        },
      ]);
    }
    return;
  }

  const ctx = canvas.getContext('2d');
  const w = video.videoWidth;
  const h = video.videoHeight;
  if (!w || !h) return;

  canvas.width = w;
  canvas.height = h;
  // Un-mirror: video is displayed with scaleX(-1), so flip when drawing to canvas
  ctx.save();
  ctx.translate(w, 0);
  ctx.scale(-1, 1);
  ctx.drawImage(video, 0, 0, w, h);
  ctx.restore();

  const imageData = canvas.toDataURL('image/jpeg', 0.9);

  // Get location (optional; don't block capture)
  getLocation()
    .then(({ location: locationStr, error: locationError }) => {
      sendCaptureToLivewire(canvas, imageData, isForcedOut, locationStr, locationError);
    })
    .catch(() => {
      sendCaptureToLivewire(canvas, imageData, isForcedOut, null, 'getLocation_failed');
    });
}

function getLocation() {
  return new Promise((resolve) => {
    if (!navigator.geolocation) {
      resolve({ location: null, error: 'not_supported' });
      return;
    }
    const timeoutMs = 10000;
    const timeout = setTimeout(() => {
      resolve({ location: null, error: 'timeout' });
    }, timeoutMs);
    navigator.geolocation.getCurrentPosition(
      (pos) => {
        clearTimeout(timeout);
        const { latitude, longitude } = pos.coords;
        resolve({
          location: `${latitude.toFixed(6)}, ${longitude.toFixed(6)}`,
          error: null,
        });
      },
      (err) => {
        clearTimeout(timeout);
        const codeMap = {
          1: 'permission_denied',
          2: 'position_unavailable',
          3: 'timeout',
        };
        resolve({
          location: null,
          error: codeMap[err.code] || 'unknown',
        });
      },
      { enableHighAccuracy: false, timeout: timeoutMs, maximumAge: 120000 }
    );
  });
}

function sendCaptureToLivewire(canvas, imageData, isForcedOut, location, locationError) {
  const container = document.querySelector('.clockinout');
  const uploadUrl = container?.dataset?.captureUploadUrl;

  if (uploadUrl) {
    // Upload image via multipart so Livewire update payload stays small (avoids "Unexpected token '<'" from HTML error response).
    canvas.toBlob(
      (blob) => {
        const formData = new FormData();
        formData.append('image', blob, 'capture.jpg');
        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const headers = {};
        if (csrf) headers['X-CSRF-TOKEN'] = csrf;
        fetch(uploadUrl, { method: 'POST', body: formData, headers, credentials: 'same-origin' })
          .then((res) => {
            if (!res.ok) throw new Error(res.statusText);
            return res.json();
          })
          .then((data) => {
            if (window.Livewire && data.path) {
              window.Livewire.dispatch('imageCaptured', [
                data.path,
                true,
                isForcedOut,
                location,
                locationError ?? null,
              ]);
            }
          })
          .catch(() => {
            if (window.Livewire) {
              window.Livewire.dispatch('imageCaptured', [
                imageData,
                true,
                isForcedOut,
                location,
                locationError ?? null,
              ]);
            }
          })
          .finally(() => {
            showPreviewModal(imageData, location);
          });
      },
      'image/jpeg',
      0.9
    );
  } else {
    if (window.Livewire) {
      window.Livewire.dispatch('imageCaptured', [
        imageData,
        true,
        isForcedOut,
        location,
        locationError ?? null,
      ]);
    }
    showPreviewModal(imageData, location);
  }
}

function formatCapturedAt(date) {
  const d = date || new Date();
  const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
  const h = d.getHours();
  const m = d.getMinutes();
  const am = h < 12;
  const h12 = h % 12 || 12;
  const time = `${h12}:${String(m).padStart(2, '0')} ${am ? 'AM' : 'PM'}`;
  return `${months[d.getMonth()]} ${String(d.getDate()).padStart(2, '0')}, ${d.getFullYear()} • ${time}`;
}

function showPreviewModal(imageData, location) {
  const previewImg = document.getElementById('clockInPreviewImage');
  if (previewImg) previewImg.src = imageData;

  const capturedEl = document.getElementById('clockInModalCaptured');
  const locationEl = document.getElementById('clockInModalLocation');
  if (capturedEl) capturedEl.textContent = formatCapturedAt(new Date());
  // Show placeholder until Livewire updates with resolved address (geocoding runs server-side).
  if (locationEl) {
    const raw = location && String(location).trim();
    const looksLikeCoords = raw && /^-?\d+\.?\d*,\s*-?\d+\.?\d*$/.test(raw.replace(/\s/g, ''));
    locationEl.textContent = looksLikeCoords ? 'Resolving address…' : (raw || '—');
  }

  const modalEl = document.getElementById('clockInModal');
  if (modalEl && typeof bootstrap !== 'undefined') {
    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    modal.show();
  }
}

function retake() {
  const modalEl = document.getElementById('clockInModal');
  if (modalEl && typeof bootstrap !== 'undefined') {
    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    modal.hide();
  }
  const previewImg = document.getElementById('clockInPreviewImage');
  if (previewImg) previewImg.removeAttribute('src');
  if (window.Livewire) {
    window.Livewire.dispatch('resetCapture');
  }
}
