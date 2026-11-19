/**
 * Kecilkan foto sebelum diupload. Foto kamera HP biasanya 3–5 MB, sedangkan batas upload 2 MB.
 * Berlaku untuk <input type="file" data-kecilkan>. Foto diubah ke JPEG dengan sisi terpanjang 1600px.
 */
const BATAS_BYTE = 1.5 * 1024 * 1024;
const SISI_MAKS = 1600;

async function kecilkan(file) {
    if (!file.type.startsWith('image/') || file.size <= BATAS_BYTE) {
        return file;
    }

    const gambar = await createImageBitmap(file);
    const skala = Math.min(1, SISI_MAKS / Math.max(gambar.width, gambar.height));
    const kanvas = document.createElement('canvas');
    kanvas.width = Math.round(gambar.width * skala);
    kanvas.height = Math.round(gambar.height * skala);
    kanvas.getContext('2d').drawImage(gambar, 0, 0, kanvas.width, kanvas.height);

    const blob = await new Promise((selesai) => kanvas.toBlob(selesai, 'image/jpeg', 0.82));

    return blob ? new File([blob], file.name.replace(/\.\w+$/, '') + '.jpg', { type: 'image/jpeg' }) : file;
}

document.querySelectorAll('input[type="file"][data-kecilkan]').forEach((input) => {
    input.addEventListener('change', async () => {
        if (!input.files.length || typeof DataTransfer === 'undefined') {
            return;
        }

        const hasil = new DataTransfer();
        for (const file of input.files) {
            try {
                hasil.items.add(await kecilkan(file));
            } catch {
                hasil.items.add(file);
            }
        }
        input.files = hasil.files;
    });
});
