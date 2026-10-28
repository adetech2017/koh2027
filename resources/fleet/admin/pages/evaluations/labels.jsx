import { Badge } from '../../../shared/ui';

export const TRUST = {
    verified_live: ['green', 'Verified live', 'Recorded in the app with its one-time code on screen and GPS on.'],
    gallery_ok: ['blue', 'Gallery: checks out', "Uploaded from the phone's gallery; the video's own data says it was recorded in this period, with a location."],
    unverified: ['amber', 'Unverified', "Couldn't confirm when or where this was recorded. Watch it closely."],
};

export const FLAGS = {
    no_location: 'No GPS location from the phone or the video.',
    outside_region: 'Recorded outside Lagos.',
    location_mismatch: "The phone's location and the video's location are more than 5 km apart.",
    no_valid_code: 'Recorded in the app without a valid one-time code.',
    no_recording_date: 'The video has no recording date (e.g. it was forwarded on WhatsApp or edited).',
    recorded_in_future: 'The recording date is in the future (wrong phone clock or edited file).',
    mileage_decreased: 'The mileage entered is lower than the last reading.',
};

export function TrustBadge({ level }) {
    const [tone, text] = TRUST[level] ?? ['slate', level];
    return <Badge tone={tone}>{text}</Badge>;
}

export const PROCESSING = { processing: ['slate', 'Processing'], ready: null, failed: ['red', 'Processing failed'] };
