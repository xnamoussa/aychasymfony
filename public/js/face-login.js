const MODEL_URL = 'https://cdn.jsdelivr.net/npm/@vladmandic/face-api/model/';
let modelsLoaded = false;
let stream = null;

async function loadFaceModels() {
    if (modelsLoaded) return;
    try {
        await faceapi.nets.ssdMobilenetv1.loadFromUri(MODEL_URL);
        await faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL);
        await faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL);
        modelsLoaded = true;
        console.log('Face-api models loaded.');
    } catch (e) {
        console.error('Failed to load Face-api models:', e);
        throw e;
    }
}

async function startWebcam(videoElement) {
    stream = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
    videoElement.srcObject = stream;
    return new Promise(resolve => {
        videoElement.onloadedmetadata = () => {
            resolve(videoElement);
        };
    });
}

function stopWebcam() {
    if (stream) {
        stream.getTracks().forEach(track => track.stop());
        stream = null;
    }
}

async function startFaceLogin() {
    const errorContainer = document.getElementById('face-error-msg');
    const actionsContainer = document.getElementById('face-actions');
    const enrollInputContainer = document.getElementById('face-enroll-container');
    const video = document.getElementById('face-video');
    const loadingText = document.getElementById('face-loading');
    
    errorContainer.innerText = '';
    actionsContainer.style.display = 'none';
    enrollInputContainer.style.display = 'none';
    loadingText.style.display = 'block';
    video.style.opacity = '0.5';

    try {
        await loadFaceModels();
        await startWebcam(video);
        
        loadingText.innerText = "Please look at the camera...";
        video.style.opacity = '1';
        
        // Give the camera a moment to focus and faceapi to initialize capture
        setTimeout(async () => {
            try {
                const detection = await faceapi.detectSingleFace(video).withFaceLandmarks().withFaceDescriptor();
                
                if (!detection) {
                    throw new Error("No face detected. Please ensure your face is clearly visible.");
                }

                loadingText.innerText = "Authenticating...";
                
                // Convert Float32Array to standard array
                const descriptor = Array.from(detection.descriptor);
                const csrfInput = document.querySelector('input[name="_csrf_token"]');
                const csrfToken = csrfInput ? csrfInput.value : '';
                
                // Send descriptor to backend to match
                const response = await fetch('/face/login', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ descriptor, _csrf_token: csrfToken })
                });
                
                const data = await response.json();
                stopWebcam();
                
                if (data.success && data.redirect) {
                    loadingText.innerText = "Success! Redirecting...";
                    window.location.href = data.redirect;
                } else {
                    loadingText.style.display = 'none';
                    errorContainer.innerText = data.error || 'Face not recognized. Retry or enroll your face.';
                    actionsContainer.style.display = 'flex';
                }
            } catch (e) {
                loadingText.style.display = 'none';
                errorContainer.innerText = e.message;
                actionsContainer.style.display = 'flex';
            }
        }, 1500);

    } catch (e) {
        loadingText.style.display = 'none';
        errorContainer.innerText = "Camera or model loading error: " + e.message;
        actionsContainer.style.display = 'flex';
    }
}

function showEnrollForm() {
    document.getElementById('face-actions').style.display = 'none';
    document.getElementById('face-enroll-container').style.display = 'block';
}

async function enrollFaceSubmit() {
    const emailInput = document.getElementById('face-enroll-email');
    const email = emailInput ? emailInput.value : '';
    if (!email) {
        alert('Please enter your email.');
        return;
    }
    
    const video = document.getElementById('face-video');
    const loadingText = document.getElementById('face-loading');
    const errorContainer = document.getElementById('face-error-msg');
    const enrollInputContainer = document.getElementById('face-enroll-container');
    
    errorContainer.innerText = '';
    enrollInputContainer.style.display = 'none';
    loadingText.style.display = 'block';
    loadingText.innerText = "Capturing face for enrollment...";
    
    try {
        if (!stream) {
            await startWebcam(video);
        }
        
        // Give the camera a moment to focus
        setTimeout(async () => {
            try {
                const detection = await faceapi.detectSingleFace(video).withFaceLandmarks().withFaceDescriptor();
                
                if (!detection) {
                    throw new Error("No face detected during enrollment. Please try again.");
                }
                
                loadingText.innerText = "Saving face data...";
                const descriptor = Array.from(detection.descriptor);
                const csrfInput = document.querySelector('input[name="_csrf_token"]');
                const csrfToken = csrfInput ? csrfInput.value : '';
                
                const response = await fetch('/face/enroll', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email, descriptor, _csrf_token: csrfToken })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert('Face enrolled successfully! You can now login with Face ID.');
                    closeFaceModal();
                } else {
                    throw new Error(data.error || 'Enrollment failed.');
                }
            } catch (e) {
                loadingText.style.display = 'none';
                errorContainer.innerText = e.message;
                document.getElementById('face-actions').style.display = 'flex';
                stopWebcam();
            }
        }, 1500);

    } catch (e) {
        loadingText.style.display = 'none';
        errorContainer.innerText = e.message;
        document.getElementById('face-actions').style.display = 'flex';
    }
}

function openFaceModal() {
    document.getElementById('faceModal').classList.add('open');
    startFaceLogin();
}

function closeFaceModal() {
    stopWebcam();
    document.getElementById('faceModal').classList.remove('open');
}
