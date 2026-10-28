import { useEffect, useRef, useState } from 'react';
import { Alert, Button } from '../../shared/ui';

const AUTO_STOP_SECONDS = 120;
const TYPES = ['video/mp4;codecs=avc1', 'video/mp4', 'video/webm;codecs=vp9,opus', 'video/webm;codecs=vp8,opus', 'video/webm'];

const SHOTS = ['Front', 'Driver side', 'Back', 'Passenger side', 'Dashboard (odometer)'];

/**
 * Records from the back camera. Frames are drawn onto a canvas with the session's code, the
 * date and time and the GPS position burned into the bottom of the picture, and the canvas is
 * what gets recorded, so the code is part of the video itself. Browsers that can't record a
 * canvas record the camera directly (the code is still on screen to be read aloud).
 *
 * onDone({ file, duration }) when the beneficiary accepts a recording.
 */
export default function LiveRecorder({ code, coords, minSeconds, onDone, onCancel }) {
    const videoRef = useRef(null);
    const canvasRef = useRef(null);
    const streamRef = useRef(null);
    const recorderRef = useRef(null);
    const chunksRef = useRef([]);
    const startedAtRef = useRef(0);
    const coordsRef = useRef(coords);
    coordsRef.current = coords;

    const [status, setStatus] = useState('starting'); // starting, ready, recording, review, error
    const [error, setError] = useState(null);
    const [elapsed, setElapsed] = useState(0);
    const [recording, setRecording] = useState(null); // { file, url, duration }

    // Camera on
    useEffect(() => {
        let cancelled = false;

        if (!navigator.mediaDevices?.getUserMedia) {
            setError('This browser cannot use the camera. Upload a video from your gallery instead.');
            setStatus('error');
            return;
        }

        navigator.mediaDevices
            .getUserMedia({
                video: { facingMode: { ideal: 'environment' }, width: { ideal: 1280 }, height: { ideal: 720 } },
                audio: true,
            })
            .then((stream) => {
                if (cancelled) return stream.getTracks().forEach((t) => t.stop());
                streamRef.current = stream;
                videoRef.current.srcObject = stream;
                return videoRef.current.play().then(() => setStatus('ready'));
            })
            .catch((e) => {
                setError(
                    e?.name === 'NotAllowedError'
                        ? 'Camera access was blocked. Allow the camera and microphone for this site in your browser settings, or upload a video from your gallery instead.'
                        : 'The camera could not be started. You can upload a video from your gallery instead.',
                );
                setStatus('error');
            });

        return () => {
            cancelled = true;
            streamRef.current?.getTracks().forEach((t) => t.stop());
        };
    }, []);

    // Draw camera + stamp onto the canvas continuously (it's also what the preview shows)
    useEffect(() => {
        if (status === 'starting' || status === 'error' || status === 'review') return;
        let frame;
        const draw = () => {
            const video = videoRef.current;
            const canvas = canvasRef.current;
            if (video?.videoWidth) {
                const scale = Math.min(1, 1280 / Math.max(video.videoWidth, video.videoHeight));
                const w = Math.round(video.videoWidth * scale);
                const h = Math.round(video.videoHeight * scale);
                if (canvas.width !== w) canvas.width = w;
                if (canvas.height !== h) canvas.height = h;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(video, 0, 0, w, h);
                stamp(ctx, w, h, code, coordsRef.current);
            }
            frame = requestAnimationFrame(draw);
        };
        draw();
        return () => cancelAnimationFrame(frame);
    }, [status, code]);

    // Timer, with an automatic stop
    useEffect(() => {
        if (status !== 'recording') return;
        const timer = setInterval(() => {
            const seconds = (Date.now() - startedAtRef.current) / 1000;
            setElapsed(seconds);
            if (seconds >= AUTO_STOP_SECONDS) stop();
        }, 250);
        return () => clearInterval(timer);
    }, [status]);

    // Free the preview when leaving
    useEffect(() => () => recording && URL.revokeObjectURL(recording.url), [recording]);

    function start() {
        const canvas = canvasRef.current;
        const camera = streamRef.current;
        let stream = camera;
        if (canvas.captureStream) {
            stream = canvas.captureStream(24);
            camera.getAudioTracks().forEach((track) => stream.addTrack(track));
        }

        const mimeType = TYPES.find((type) => window.MediaRecorder?.isTypeSupported?.(type)) ?? '';
        let recorder;
        try {
            recorder = new MediaRecorder(stream, { ...(mimeType && { mimeType }), videoBitsPerSecond: 1_500_000 });
        } catch {
            setError('This browser cannot record video. Upload a video from your gallery instead.');
            setStatus('error');
            return;
        }

        chunksRef.current = [];
        recorder.ondataavailable = (e) => e.data.size && chunksRef.current.push(e.data);
        recorder.onstop = () => {
            const type = recorder.mimeType || mimeType || 'video/webm';
            const extension = type.includes('mp4') ? 'mp4' : 'webm';
            const duration = (Date.now() - startedAtRef.current) / 1000;
            const file = new File(chunksRef.current, `evaluation-${Date.now()}.${extension}`, { type: type.split(';')[0] });
            setRecording({ file, url: URL.createObjectURL(file), duration });
            setStatus('review');
        };

        recorderRef.current = recorder;
        startedAtRef.current = Date.now();
        setElapsed(0);
        recorder.start(1000);
        setStatus('recording');
    }

    function stop() {
        if (recorderRef.current?.state === 'recording') recorderRef.current.stop();
    }

    function retake() {
        URL.revokeObjectURL(recording.url);
        setRecording(null);
        setStatus('ready');
    }

    if (status === 'error') {
        return (
            <div className="space-y-4">
                <Alert>{error}</Alert>
                <Button variant="secondary" onClick={onCancel} className="w-full">Back</Button>
            </div>
        );
    }

    const tooShort = elapsed < minSeconds;

    return (
        <div className="space-y-4">
            <div className="relative overflow-hidden rounded-xl bg-black">
                <video ref={videoRef} muted playsInline className="hidden" />
                {status === 'review' ? (
                    <video src={recording.url} controls playsInline className="aspect-video w-full bg-black" />
                ) : (
                    <canvas ref={canvasRef} className="aspect-video w-full" />
                )}
                {status === 'recording' && (
                    <div className="absolute top-3 left-3 flex items-center gap-2 rounded-full bg-black/60 px-3 py-1 text-sm font-semibold text-white">
                        <span className="size-2.5 animate-pulse rounded-full bg-red-500" />
                        {formatSeconds(elapsed)}
                    </div>
                )}
            </div>

            {status !== 'review' && (
                <div className="rounded-xl bg-white p-4 text-sm shadow-sm ring-1 ring-slate-200">
                    <p className="font-medium">
                        Say the code <span className="rounded bg-gold/30 px-1.5 font-mono text-base tracking-widest">{code}</span> out loud, then film:
                    </p>
                    <ol className="mt-2 flex flex-wrap gap-x-2 gap-y-1 text-slate-600">
                        {SHOTS.map((shot, i) => <li key={shot}>{i + 1}. {shot}</li>)}
                    </ol>
                    <p className="mt-2 text-slate-500">Aim for 60–90 seconds.</p>
                </div>
            )}

            {status === 'starting' && <p className="text-center text-sm text-slate-600">Starting camera…</p>}
            {status === 'ready' && (
                <div className="flex gap-3">
                    <Button variant="secondary" onClick={onCancel} className="flex-1">Back</Button>
                    <Button onClick={start} className="flex-[2]">Start recording</Button>
                </div>
            )}
            {status === 'recording' && (
                <Button onClick={stop} disabled={tooShort} className="w-full bg-red-600 hover:bg-red-700">
                    {tooShort ? `Keep going (${Math.ceil(minSeconds - elapsed)}s)` : 'Stop recording'}
                </Button>
            )}
            {status === 'review' && (
                <div className="flex gap-3">
                    <Button variant="secondary" onClick={retake} className="flex-1">Record again</Button>
                    <Button onClick={() => onDone({ file: recording.file, duration: recording.duration })} className="flex-[2]">Use this video</Button>
                </div>
            )}
        </div>
    );
}

function stamp(ctx, w, h, code, coords) {
    const size = Math.max(14, Math.round(h / 24));
    const now = new Date();
    const time = now.toLocaleString('en-GB', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' });
    const place = coords ? `${coords.latitude.toFixed(5)}, ${coords.longitude.toFixed(5)}` : 'No GPS';
    const text = `KOH FLEET · CODE ${code} · ${time} · ${place}`;

    ctx.font = `600 ${size}px system-ui, sans-serif`;
    const pad = Math.round(size * 0.5);
    ctx.fillStyle = 'rgba(0, 0, 0, 0.6)';
    ctx.fillRect(0, h - size - pad * 2, w, size + pad * 2);
    ctx.fillStyle = '#FFB81C';
    ctx.textBaseline = 'middle';
    ctx.fillText(text, pad, h - pad - size / 2, w - pad * 2);
}

function formatSeconds(seconds) {
    const s = Math.floor(seconds);
    return `${Math.floor(s / 60)}:${String(s % 60).padStart(2, '0')}`;
}
