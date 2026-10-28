/**
 * Shrinks a phone photo to at most `maxSize` px on its longest side and re-encodes it as JPEG
 * before upload. A 4–8 MB camera photo becomes ~200–400 KB, which matters on mobile data.
 * Falls back to the original file if the browser can't decode it.
 */
export async function shrinkImage(file, maxSize = 1600, quality = 0.82) {
    try {
        const bitmap = await createImageBitmap(file, { imageOrientation: 'from-image' });
        const scale = Math.min(1, maxSize / Math.max(bitmap.width, bitmap.height));
        const canvas = document.createElement('canvas');
        canvas.width = Math.round(bitmap.width * scale);
        canvas.height = Math.round(bitmap.height * scale);
        canvas.getContext('2d').drawImage(bitmap, 0, 0, canvas.width, canvas.height);
        bitmap.close();

        const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', quality));
        if (!blob) return file;

        return new File([blob], file.name.replace(/\.[^.]+$/, '') + '.jpg', { type: 'image/jpeg' });
    } catch {
        return file;
    }
}
