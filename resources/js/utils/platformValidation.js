/**
 * Platform validation utilities
 * Validates posts against social media platform constraints
 */

// Platform-specific limits
export const PLATFORM_LIMITS = {
    instagram_feed: {
        name: 'Instagram Feed',
        maxCaptionLength: 2200,
        maxHashtags: 30,
        recommendedAspectRatios: ['1:1', '4:5', '1.91:1'],
    },
    instagram_story: {
        name: 'Instagram Story',
        maxDuration: 15,
        recommendedAspectRatios: ['9:16'],
    },
    instagram_reel: {
        name: 'Instagram Reel',
        minDuration: 3,
        maxDuration: 90,
        recommendedAspectRatios: ['9:16'],
    },
    facebook_feed: {
        name: 'Facebook Feed',
        maxCaptionLength: 63206,
        recommendedAspectRatios: ['1.91:1', '1:1', '4:5'],
    },
    facebook_story: {
        name: 'Facebook Story',
        maxDuration: 20,
        recommendedAspectRatios: ['9:16'],
    },
};

/**
 * Count hashtags in a caption
 */
export function countHashtags(caption) {
    if (!caption) return 0;
    const matches = caption.match(/#[\w\u0080-\uFFFF]+/g);
    return matches ? matches.length : 0;
}

/**
 * Calculate aspect ratio string from dimensions
 */
export function getAspectRatio(width, height) {
    if (!width || !height) return null;

    const ratio = width / height;

    // Common aspect ratios with tolerance
    if (Math.abs(ratio - 1) < 0.05) return '1:1';
    if (Math.abs(ratio - 0.8) < 0.05) return '4:5';
    if (Math.abs(ratio - 0.5625) < 0.05) return '9:16';
    if (Math.abs(ratio - 1.91) < 0.1) return '1.91:1';
    if (Math.abs(ratio - 1.78) < 0.1) return '16:9';

    return `${(ratio).toFixed(2)}:1`;
}

/**
 * Check if aspect ratio matches platform recommendations
 */
function isAspectRatioCompatible(mediaRatio, platformRatios) {
    if (!mediaRatio || !platformRatios) return true;
    return platformRatios.includes(mediaRatio);
}

/**
 * Validate a post against platform constraints
 * @param {string[]} platforms - Array of platform IDs (e.g., ['instagram_feed', 'instagram_story'])
 * @param {object|null} media - Media object with type, duration, width, height
 * @param {string} caption - Post caption text
 * @returns {object} - { errors: [], warnings: [], hasErrors: boolean }
 */
export function validatePost(platforms, media, caption) {
    const errors = [];
    const warnings = [];

    if (!platforms || platforms.length === 0) {
        return { errors, warnings, hasErrors: false };
    }

    const hashtagCount = countHashtags(caption);
    const captionLength = caption?.length || 0;
    const mediaRatio = media ? getAspectRatio(media.width, media.height) : null;
    const isVideo = media?.type === 'video';
    const videoDuration = isVideo ? media.duration : null;

    for (const platformId of platforms) {
        const limits = PLATFORM_LIMITS[platformId];
        if (!limits) continue;

        const platformName = limits.name;

        // Video duration checks
        if (isVideo && videoDuration !== null) {
            if (limits.maxDuration && videoDuration > limits.maxDuration) {
                errors.push({
                    type: 'error',
                    platform: platformId,
                    platformName,
                    field: 'duration',
                    message: `Video is ${videoDuration}s, but ${platformName} allows max ${limits.maxDuration}s`,
                });
            }

            if (limits.minDuration && videoDuration < limits.minDuration) {
                errors.push({
                    type: 'error',
                    platform: platformId,
                    platformName,
                    field: 'duration',
                    message: `Video is ${videoDuration}s, but ${platformName} requires min ${limits.minDuration}s`,
                });
            }
        }

        // Caption length checks
        if (limits.maxCaptionLength && captionLength > limits.maxCaptionLength) {
            errors.push({
                type: 'error',
                platform: platformId,
                platformName,
                field: 'caption',
                message: `Caption is ${captionLength.toLocaleString()} characters, but ${platformName} allows max ${limits.maxCaptionLength.toLocaleString()}`,
            });
        } else if (limits.maxCaptionLength && captionLength > limits.maxCaptionLength * 0.9) {
            warnings.push({
                type: 'warning',
                platform: platformId,
                platformName,
                field: 'caption',
                message: `Caption is approaching ${platformName} limit (${captionLength.toLocaleString()} / ${limits.maxCaptionLength.toLocaleString()})`,
            });
        }

        // Hashtag checks (Instagram only)
        if (limits.maxHashtags) {
            if (hashtagCount > limits.maxHashtags) {
                errors.push({
                    type: 'error',
                    platform: platformId,
                    platformName,
                    field: 'hashtags',
                    message: `Caption has ${hashtagCount} hashtags, but ${platformName} allows max ${limits.maxHashtags}`,
                });
            } else if (hashtagCount > 25) {
                warnings.push({
                    type: 'warning',
                    platform: platformId,
                    platformName,
                    field: 'hashtags',
                    message: `Caption has ${hashtagCount} hashtags (max ${limits.maxHashtags})`,
                });
            }
        }

        // Aspect ratio checks (soft warning only)
        if (media && limits.recommendedAspectRatios && !isAspectRatioCompatible(mediaRatio, limits.recommendedAspectRatios)) {
            warnings.push({
                type: 'warning',
                platform: platformId,
                platformName,
                field: 'aspectRatio',
                message: `${platformName} recommends ${limits.recommendedAspectRatios.join(' or ')} aspect ratio for best display`,
            });
        }
    }

    return {
        errors,
        warnings,
        hasErrors: errors.length > 0,
    };
}

/**
 * Get the most restrictive caption limit from selected platforms
 */
export function getCaptionLimit(platforms) {
    if (!platforms || platforms.length === 0) return null;

    let minLimit = null;

    for (const platformId of platforms) {
        const limits = PLATFORM_LIMITS[platformId];
        if (limits?.maxCaptionLength) {
            if (minLimit === null || limits.maxCaptionLength < minLimit) {
                minLimit = limits.maxCaptionLength;
            }
        }
    }

    return minLimit;
}

/**
 * Get caption limit status for UI display
 */
export function getCaptionStatus(platforms, captionLength) {
    const limit = getCaptionLimit(platforms);

    if (!limit) {
        return { limit: null, percentage: 0, status: 'ok' };
    }

    const percentage = (captionLength / limit) * 100;
    let status = 'ok';

    if (percentage >= 100) {
        status = 'error';
    } else if (percentage >= 90) {
        status = 'warning';
    }

    return { limit, percentage, status };
}
