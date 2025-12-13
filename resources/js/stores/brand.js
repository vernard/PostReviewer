import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { brandApi } from '@/services/api';

export const useBrandStore = defineStore('brand', () => {
    const brands = ref([]);
    const activeBrandId = ref(localStorage.getItem('active_brand_id') ? parseInt(localStorage.getItem('active_brand_id')) : null);
    const loading = ref(false);
    const initialized = ref(false);
    let echoChannel = null;

    const activeBrand = computed(() => {
        if (!activeBrandId.value || brands.value.length === 0) return null;
        return brands.value.find(b => b.id === activeBrandId.value) || null;
    });

    const hasBrands = computed(() => brands.value.length > 0);

    async function fetchBrands() {
        try {
            loading.value = true;
            const response = await brandApi.list();
            brands.value = response.data.brands || response.data.data || response.data || [];

            // Auto-select brand if needed
            if (brands.value.length > 0 && !activeBrand.value) {
                // Check if saved brand is still valid
                const savedBrandId = localStorage.getItem('active_brand_id');
                if (savedBrandId && brands.value.some(b => b.id === parseInt(savedBrandId))) {
                    activeBrandId.value = parseInt(savedBrandId);
                } else {
                    // Default to first brand
                    setActiveBrand(brands.value[0]);
                }
            }

            initialized.value = true;
        } catch (err) {
            console.error('Failed to fetch brands:', err);
            brands.value = [];
        } finally {
            loading.value = false;
        }
    }

    function setActiveBrand(brand) {
        if (!brand) return;
        activeBrandId.value = brand.id;
        localStorage.setItem('active_brand_id', brand.id.toString());
    }

    function clearActiveBrand() {
        activeBrandId.value = null;
        localStorage.removeItem('active_brand_id');
    }

    function reset() {
        brands.value = [];
        activeBrandId.value = null;
        initialized.value = false;
        localStorage.removeItem('active_brand_id');
        unsubscribeFromChannel();
    }

    function subscribeToChannel(agencyId) {
        if (!window.Echo || !agencyId) return;

        // Unsubscribe from any existing channel first
        unsubscribeFromChannel();

        try {
            echoChannel = window.Echo.private(`agency.${agencyId}`);
            echoChannel.listen('.brand.changed', (event) => {
                handleBrandChanged(event);
            });
        } catch (e) {
            console.warn('Failed to subscribe to brand channel:', e);
        }
    }

    function unsubscribeFromChannel() {
        if (echoChannel && window.Echo) {
            try {
                window.Echo.leave(echoChannel.name);
            } catch (e) {
                // Ignore errors when leaving channel
            }
            echoChannel = null;
        }
    }

    function handleBrandChanged(event) {
        const { action, brandId, brand } = event;

        if (action === 'created' && brand) {
            // Add the new brand to the list
            brands.value.push(brand);
        } else if (action === 'updated' && brand) {
            // Update the brand in the list
            const index = brands.value.findIndex(b => b.id === brandId);
            if (index !== -1) {
                brands.value[index] = brand;
            }
        } else if (action === 'deleted') {
            // Remove the brand from the list
            brands.value = brands.value.filter(b => b.id !== brandId);

            // If deleted brand was active, select another one
            if (activeBrandId.value === brandId) {
                if (brands.value.length > 0) {
                    setActiveBrand(brands.value[0]);
                } else {
                    clearActiveBrand();
                }
            }
        }
    }

    return {
        brands,
        activeBrandId,
        activeBrand,
        loading,
        initialized,
        hasBrands,
        fetchBrands,
        setActiveBrand,
        clearActiveBrand,
        reset,
        subscribeToChannel,
        unsubscribeFromChannel,
    };
});
