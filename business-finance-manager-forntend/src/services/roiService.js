// src/services/roiService.js
import api from './api'

export default {
    // Get ROI for current month
    getCurrentMonth() {
        return api.get('/roi/current-month')
    },
}

// Composable for Vue 3 (Optional)
export const useRoiService = () => {
    return {
        getCurrentMonth: () => api.get('/roi/current-month'),
    }
}
