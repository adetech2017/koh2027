import { useEffect, useRef, useState } from 'react';

/**
 * Small hand-built SVG charts for the admin app. Colours are validated categorical slots
 * (blue #2a78d6, orange #eb6834 pass colour-blind separation as a pair); text never wears
 * the series colour; gridlines are hairline and recessive.
 */
const C = {
    series1: '#2a78d6',
    series2: '#eb6834',
    surface: '#ffffff',
    grid: '#e2e8f0',
    text: '#64748b',
};

function useWidth() {
    const ref = useRef(null);
    const [width, setWidth] = useState(0);

    useEffect(() => {
        const observer = new ResizeObserver(([entry]) => setWidth(entry.contentRect.width));
        observer.observe(ref.current);
        return () => observer.disconnect();
    }, []);

    return [ref, width];
}

/**
 * Round tick values from 0 (or min) to at least max: 0, 5, 10... or 0, 2K, 4K...
 */
function niceTicks(min, max, count = 4) {
    if (max <= min) max = min + 1;
    const raw = (max - min) / count;
    const magnitude = 10 ** Math.floor(Math.log10(raw));
    const step = [1, 2, 2.5, 5, 10].map((m) => m * magnitude).find((s) => s >= raw);
    const start = Math.floor(min / step) * step;
    const ticks = [];
    for (let v = start; v < max + step; v += step) ticks.push(v);
    return ticks;
}

const compact = (n) => (Math.abs(n) >= 1000 ? `${+(n / 1000).toFixed(1)}K` : String(n));
const shortDate = (iso) => new Date(iso.length === 10 ? `${iso}T00:00:00` : iso).toLocaleDateString('en-NG', { day: 'numeric', month: 'short' });

function Tooltip({ x, y, width, children }) {
    // Keep it inside the chart horizontally
    const left = Math.min(Math.max(x, 80), width - 80);
    return (
        <div
            className="pointer-events-none absolute z-10 -translate-x-1/2 -translate-y-full rounded-lg bg-slate-900 px-3 py-2 text-xs whitespace-nowrap text-white shadow-lg"
            style={{ left, top: y - 8 }}
        >
            {children}
        </div>
    );
}

function Swatch({ color }) {
    return <span className="inline-block size-2.5 rounded-sm" style={{ background: color }} aria-hidden="true" />;
}

/**
 * Windows ending each week: on time (video sent) stacked under missed.
 */
export function WeeklyChart({ weeks }) {
    const [ref, width] = useWidth();
    const [hover, setHover] = useState(null);
    const height = 200;
    const pad = { top: 20, right: 8, bottom: 26, left: 32 };
    const plotW = Math.max(0, width - pad.left - pad.right);
    const plotH = height - pad.top - pad.bottom;
    const ticks = niceTicks(0, Math.max(...weeks.map((w) => w.on_time + w.missed), 4));
    const top = ticks[ticks.length - 1];
    const y = (v) => pad.top + plotH - (v / top) * plotH;
    const band = plotW / weeks.length;
    const barW = Math.min(24, band * 0.6);
    const empty = weeks.every((w) => w.on_time + w.missed === 0);

    return (
        <div>
            <div className="mb-2 flex gap-4 text-xs text-slate-600">
                <span className="flex items-center gap-1.5"><Swatch color={C.series1} /> On time</span>
                <span className="flex items-center gap-1.5"><Swatch color={C.series2} /> Missed</span>
            </div>
            <div ref={ref} className="relative" onMouseLeave={() => setHover(null)}>
                {width > 0 && (
                    <svg width={width} height={height} role="img" aria-label="Evaluations due each week: on time versus missed. Table follows.">
                        {ticks.map((t) => (
                            <g key={t}>
                                <line x1={pad.left} x2={width - pad.right} y1={y(t)} y2={y(t)} stroke={C.grid} strokeWidth="1" />
                                <text x={pad.left - 6} y={y(t)} dy="0.32em" textAnchor="end" fontSize="11" fill={C.text}>{compact(t)}</text>
                            </g>
                        ))}
                        {weeks.map((w, i) => {
                            const cx = pad.left + band * i + band / 2;
                            const x = cx - barW / 2;
                            const total = w.on_time + w.missed;
                            // 2px surface gap between the two segments
                            const gap = w.on_time && w.missed ? 2 : 0;
                            const onTimeTop = y(w.on_time);
                            return (
                                <g key={w.week_of}>
                                    {w.on_time > 0 && (
                                        <Column x={x} y={onTimeTop} w={barW} h={y(0) - onTimeTop} color={C.series1} roundTop={!w.missed} />
                                    )}
                                    {w.missed > 0 && (
                                        <Column x={x} y={y(total)} w={barW} h={onTimeTop - y(total) - gap} color={C.series2} roundTop />
                                    )}
                                    {total > 0 && <text x={cx} y={y(total) - 5} textAnchor="middle" fontSize="11" fill={C.text}>{total}</text>}
                                    <text x={cx} y={height - 8} textAnchor="middle" fontSize="11" fill={C.text}>{shortDate(w.week_of)}</text>
                                    {/* Hit target: the whole band, not just the bar */}
                                    <rect x={pad.left + band * i} y={pad.top} width={band} height={plotH} fill="transparent" onMouseEnter={() => setHover(i)} onFocus={() => setHover(i)} tabIndex={0} aria-label={`Week of ${shortDate(w.week_of)}: ${w.on_time} on time, ${w.missed} missed`} />
                                </g>
                            );
                        })}
                    </svg>
                )}
                {empty && width > 0 && <p className="absolute inset-0 flex items-center justify-center text-sm text-slate-500">No windows have ended yet.</p>}
                {hover !== null && (
                    <Tooltip x={pad.left + band * hover + band / 2} y={y(weeks[hover].on_time + weeks[hover].missed)} width={width}>
                        <div className="font-semibold">Week of {shortDate(weeks[hover].week_of)}</div>
                        <div className="mt-1 flex items-center gap-1.5"><Swatch color={C.series1} /> On time: {weeks[hover].on_time}</div>
                        <div className="flex items-center gap-1.5"><Swatch color={C.series2} /> Missed: {weeks[hover].missed}</div>
                    </Tooltip>
                )}
            </div>
            <table className="sr-only">
                <caption>Evaluations due each week</caption>
                <thead><tr><th>Week of</th><th>On time</th><th>Missed</th></tr></thead>
                <tbody>{weeks.map((w) => <tr key={w.week_of}><td>{w.week_of}</td><td>{w.on_time}</td><td>{w.missed}</td></tr>)}</tbody>
            </table>
        </div>
    );
}

/**
 * A column with a 4px rounded data end and a square base.
 */
function Column({ x, y, w, h, color, roundTop }) {
    if (h <= 0) return null;
    const r = roundTop ? Math.min(4, h, w / 2) : 0;
    const d = `M${x},${y + h} V${y + r} Q${x},${y} ${x + r},${y} H${x + w - r} Q${x + w},${y} ${x + w},${y + r} V${y + h} Z`;
    return <path d={d} fill={color} />;
}

/**
 * Odometer readings over time for one vehicle (single series: the card title names it).
 */
export function MileageChart({ readings, labelFor }) {
    const [ref, width] = useWidth();
    const [hover, setHover] = useState(null);
    const height = 220;
    const pad = { top: 16, right: 76, bottom: 26, left: 44 };
    const plotW = Math.max(0, width - pad.left - pad.right);
    const plotH = height - pad.top - pad.bottom;

    const points = readings.map((r) => ({ ...r, t: new Date(r.date).getTime() }));
    const t0 = points[0].t;
    const t1 = Math.max(points[points.length - 1].t, t0 + 86400000);
    const kms = points.map((p) => p.km);
    const ticks = niceTicks(Math.min(...kms), Math.max(...kms));
    const [lo, hi] = [ticks[0], ticks[ticks.length - 1]];
    const x = (t) => pad.left + ((t - t0) / (t1 - t0)) * plotW;
    const y = (km) => pad.top + plotH - ((km - lo) / (hi - lo)) * plotH;
    const path = points.map((p, i) => `${i ? 'L' : 'M'}${x(p.t)},${y(p.km)}`).join(' ');
    const last = points[points.length - 1];

    // Nearest point to the pointer (crosshair follows it)
    function move(event) {
        const px = event.clientX - ref.current.getBoundingClientRect().left;
        let best = 0;
        points.forEach((p, i) => {
            if (Math.abs(x(p.t) - px) < Math.abs(x(points[best].t) - px)) best = i;
        });
        setHover(best);
    }

    const xTicks = [t0, t0 + (t1 - t0) / 2, t1];

    return (
        <div ref={ref} className="relative" onMouseMove={move} onMouseLeave={() => setHover(null)}>
            {width > 0 && (
                <svg width={width} height={height} role="img" aria-label="Odometer readings over time. Table follows.">
                    {ticks.map((t) => (
                        <g key={t}>
                            <line x1={pad.left} x2={pad.left + plotW} y1={y(t)} y2={y(t)} stroke={C.grid} strokeWidth="1" />
                            <text x={pad.left - 6} y={y(t)} dy="0.32em" textAnchor="end" fontSize="11" fill={C.text}>{compact(t)}</text>
                        </g>
                    ))}
                    {xTicks.map((t, i) => (
                        <text key={i} x={x(t)} y={height - 8} textAnchor={i === 0 ? 'start' : i === 2 ? 'end' : 'middle'} fontSize="11" fill={C.text}>{shortDate(new Date(t).toISOString())}</text>
                    ))}
                    {hover !== null && <line x1={x(points[hover].t)} x2={x(points[hover].t)} y1={pad.top} y2={pad.top + plotH} stroke={C.text} strokeWidth="1" />}
                    <path d={path} fill="none" stroke={C.series1} strokeWidth="2" strokeLinejoin="round" strokeLinecap="round" />
                    {points.map((p, i) => (
                        <circle key={i} cx={x(p.t)} cy={y(p.km)} r={hover === i ? 5 : 4} fill={C.series1} stroke={C.surface} strokeWidth="2" />
                    ))}
                    {/* Value at the end of the line */}
                    <text x={x(last.t) + 8} y={y(last.km)} dy="0.32em" fontSize="11" fontWeight="600" fill="#0f172a">{last.km.toLocaleString()} km</text>
                </svg>
            )}
            {hover !== null && (
                <Tooltip x={x(points[hover].t)} y={y(points[hover].km)} width={width}>
                    <div className="font-semibold">{points[hover].km.toLocaleString()} km</div>
                    <div className="text-slate-300">{shortDate(points[hover].date)} · {labelFor(points[hover])}</div>
                </Tooltip>
            )}
        </div>
    );
}
