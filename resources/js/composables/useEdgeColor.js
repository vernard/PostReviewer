import { ref, watch } from 'vue';

/**
 * Samples edge pixels from an image and returns the average color
 * This creates a more natural letterbox effect similar to Meta's approach
 */

/**
 * Sample pixels from image edges and compute average color
 * @param {HTMLImageElement} img - The image element to sample from
 * @returns {string} - CSS color string (rgb format)
 */
function sampleEdgeColor(img) {
    const canvas = document.createElement('canvas');
    const ctx = canvas.getContext('2d');

    // Use a small sample size for performance
    const sampleWidth = Math.min(img.naturalWidth, 100);
    const sampleHeight = Math.min(img.naturalHeight, 100);

    canvas.width = sampleWidth;
    canvas.height = sampleHeight;

    try {
        ctx.drawImage(img, 0, 0, sampleWidth, sampleHeight);
        const imageData = ctx.getImageData(0, 0, sampleWidth, sampleHeight);
        const data = imageData.data;

        let r = 0, g = 0, b = 0, count = 0;

        // Sample edge thickness (in pixels of the scaled image)
        const edgeThickness = 3;

        for (let y = 0; y < sampleHeight; y++) {
            for (let x = 0; x < sampleWidth; x++) {
                // Check if pixel is on any edge
                const isOnEdge = (
                    x < edgeThickness ||
                    x >= sampleWidth - edgeThickness ||
                    y < edgeThickness ||
                    y >= sampleHeight - edgeThickness
                );

                if (isOnEdge) {
                    const idx = (y * sampleWidth + x) * 4;
                    r += data[idx];
                    g += data[idx + 1];
                    b += data[idx + 2];
                    count++;
                }
            }
        }

        if (count > 0) {
            r = Math.round(r / count);
            g = Math.round(g / count);
            b = Math.round(b / count);

            // Slightly darken the color to ensure text readability
            const darkenFactor = 0.7;
            r = Math.round(r * darkenFactor);
            g = Math.round(g * darkenFactor);
            b = Math.round(b * darkenFactor);

            return `rgb(${r}, ${g}, ${b})`;
        }
    } catch (e) {
        // CORS or other errors - fall back to black
        console.warn('Could not sample edge color:', e);
    }

    return 'rgb(0, 0, 0)';
}

/**
 * Composable for extracting edge color from media
 * @param {Ref} mediaUrl - Reactive reference to the media URL
 * @param {Ref} thumbnailUrl - Optional thumbnail URL for videos
 * @returns {{ edgeColor: Ref<string>, isLoading: Ref<boolean> }}
 */
export function useEdgeColor(mediaUrl, thumbnailUrl = null) {
    const edgeColor = ref('rgb(0, 0, 0)');
    const isLoading = ref(false);

    const extractColor = (url) => {
        if (!url) {
            edgeColor.value = 'rgb(0, 0, 0)';
            return;
        }

        isLoading.value = true;

        const img = new Image();
        img.crossOrigin = 'anonymous';

        img.onload = () => {
            edgeColor.value = sampleEdgeColor(img);
            isLoading.value = false;
        };

        img.onerror = () => {
            edgeColor.value = 'rgb(0, 0, 0)';
            isLoading.value = false;
        };

        img.src = url;
    };

    // Watch for URL changes
    watch(
        () => thumbnailUrl?.value || mediaUrl?.value,
        (url) => extractColor(url),
        { immediate: true }
    );

    return { edgeColor, isLoading };
}

/**
 * Direct function to extract edge color from a URL
 * Returns a promise that resolves to the color
 * @param {string} url - Image URL to sample
 * @returns {Promise<string>} - CSS color string
 */
export function extractEdgeColor(url) {
    return new Promise((resolve) => {
        if (!url) {
            resolve('rgb(0, 0, 0)');
            return;
        }

        const img = new Image();
        img.crossOrigin = 'anonymous';

        img.onload = () => {
            resolve(sampleEdgeColor(img));
        };

        img.onerror = () => {
            resolve('rgb(0, 0, 0)');
        };

        img.src = url;
    });
}
