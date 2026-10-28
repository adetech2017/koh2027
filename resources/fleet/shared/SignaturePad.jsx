import { useEffect, useRef, useState } from 'react';

/**
 * Draw-to-sign box for finger, stylus or mouse. Calls onChange with a PNG data URI after each
 * stroke, or null when cleared.
 */
export default function SignaturePad({ label, onChange, error }) {
    const canvasRef = useRef(null);
    const drawing = useRef(false);
    const [empty, setEmpty] = useState(true);

    useEffect(() => {
        const canvas = canvasRef.current;
        // Draw at the screen's real pixel density so the signature isn't blurry
        const ratio = Math.min(window.devicePixelRatio || 1, 2);
        canvas.width = canvas.offsetWidth * ratio;
        canvas.height = canvas.offsetHeight * ratio;
        const ctx = canvas.getContext('2d');
        ctx.scale(ratio, ratio);
        ctx.lineWidth = 2.2;
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';
        ctx.strokeStyle = '#0f172a';
    }, []);

    function point(event) {
        const rect = canvasRef.current.getBoundingClientRect();
        return [event.clientX - rect.left, event.clientY - rect.top];
    }

    function start(event) {
        event.preventDefault();
        canvasRef.current.setPointerCapture(event.pointerId);
        drawing.current = true;
        const ctx = canvasRef.current.getContext('2d');
        const [x, y] = point(event);
        ctx.beginPath();
        ctx.moveTo(x, y);
        // A dot for a tap
        ctx.lineTo(x + 0.1, y + 0.1);
        ctx.stroke();
    }

    function move(event) {
        if (!drawing.current) return;
        const ctx = canvasRef.current.getContext('2d');
        const [x, y] = point(event);
        ctx.lineTo(x, y);
        ctx.stroke();
    }

    function end() {
        if (!drawing.current) return;
        drawing.current = false;
        setEmpty(false);
        onChange(canvasRef.current.toDataURL('image/png'));
    }

    function clear() {
        const canvas = canvasRef.current;
        canvas.getContext('2d').clearRect(0, 0, canvas.width, canvas.height);
        setEmpty(true);
        onChange(null);
    }

    return (
        <div>
            <div className="mb-1.5 flex items-center justify-between">
                <span className="text-sm font-medium text-slate-700">{label}</span>
                {!empty && (
                    <button type="button" onClick={clear} className="text-sm font-medium text-primary hover:underline">
                        Clear
                    </button>
                )}
            </div>
            <div className={`relative rounded-lg border-2 border-dashed bg-white ${error ? 'border-red-400' : 'border-slate-300'}`}>
                <canvas
                    ref={canvasRef}
                    className="block h-40 w-full touch-none"
                    onPointerDown={start}
                    onPointerMove={move}
                    onPointerUp={end}
                    onPointerCancel={end}
                    aria-label={label}
                />
                {empty && (
                    <span className="pointer-events-none absolute inset-0 flex items-center justify-center text-sm text-slate-400">
                        Sign here
                    </span>
                )}
                <div className="pointer-events-none absolute right-4 bottom-8 left-4 border-b border-slate-200" />
            </div>
            {error && <p className="mt-1.5 text-sm text-red-600">{error}</p>}
        </div>
    );
}
