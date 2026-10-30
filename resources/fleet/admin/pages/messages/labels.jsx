import { Badge } from '../../../shared/ui';

export const MESSAGE_TYPES = {
    evaluation_due: 'Evaluation reminder',
    evaluation_missed: 'Missed evaluation',
    evaluation_rejected: 'Evaluation rejected',
    vehicle_recalled: 'Vehicle recalled',
    custom: 'Sent by an admin',
};

export const MESSAGE_STATUS = { pending: ['slate', 'Sending'], sent: ['green', 'Sent'], failed: ['red', 'Failed'], skipped: ['slate', 'Not sent'] };

export function MessageStatus({ status }) {
    const [tone, text] = MESSAGE_STATUS[status] ?? ['slate', status];
    return <Badge tone={tone}>{text}</Badge>;
}

// Meta's verdict on a template
export const TEMPLATE_STATUS = {
    not_checked: ['slate', 'Not checked with Meta'],
    not_submitted: ['amber', 'Not in Meta yet'],
    pending: ['amber', 'Waiting for Meta'],
    approved: ['green', 'Approved by Meta'],
    rejected: ['red', 'Rejected by Meta'],
    paused: ['amber', 'Paused by Meta'],
    disabled: ['red', 'Disabled by Meta'],
    mismatch: ['red', "Blanks don't match"],
};

export function TemplateStatus({ status }) {
    const [tone, text] = TEMPLATE_STATUS[status] ?? ['slate', status];
    return <Badge tone={tone}>{text}</Badge>;
}

/**
 * The wording as a WhatsApp bubble, with the blanks filled from `values` (or their examples)
 * and highlighted so it's clear which parts change per message.
 */
export function MessageBubble({ body, variables = [], values = [] }) {
    if (!body) return <p className="text-sm text-slate-500">No wording yet.</p>;

    const parts = body.split(/(\{\{\d+\}\})/);

    return (
        <div className="rounded-xl bg-[#e7f6dc] p-3.5">
            <p className="max-w-prose rounded-lg rounded-tl-none bg-white px-3.5 py-2.5 text-sm leading-relaxed whitespace-pre-wrap text-slate-900 shadow-sm">
                {parts.map((part, i) => {
                    const match = part.match(/^\{\{(\d+)\}\}$/);
                    if (!match) return <span key={i}>{part}</span>;
                    const n = Number(match[1]);
                    const variable = variables.find((v) => v.number === n);
                    const value = values[n - 1]?.trim() || variable?.example || `{{${n}}}`;
                    return (
                        <mark key={i} title={variable?.label} className="rounded bg-gold/30 px-0.5 text-slate-900">
                            {value}
                        </mark>
                    );
                })}
            </p>
        </div>
    );
}
