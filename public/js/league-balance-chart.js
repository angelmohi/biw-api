/**
 * League Balance Chart Management
 * Handles the balance evolution chart for league users
 */

class LeagueBalanceChart {
    constructor() {
        this.charts = {
            desktop: null,
            mobile: null
        };
        this.chartData = null;
        this.leagueId = null;
        this.imageCache = new Map();
        
        this.init();
        this.registerProfilePicturesPlugin();
    }

    registerProfilePicturesPlugin() {
        // Register custom plugin for drawing profile pictures
        if (typeof Chart !== 'undefined') {
            Chart.register({
                id: 'profilePictures',
                afterDatasetsDraw: (chart) => {
                    this.drawProfilePictures(chart);
                }
            });
        }
    }

    init() {
        // Extract league ID from URL
        const pathSegments = window.location.pathname.split('/');
        const leagueIndex = pathSegments.indexOf('leagues');
        if (leagueIndex !== -1 && pathSegments[leagueIndex + 1]) {
            this.leagueId = pathSegments[leagueIndex + 1];
        }
        this.bindEvents();
    }

    bindEvents() {
        // Desktop tab activation
        const desktopTab = document.getElementById('balance-chart-tab');
        if (desktopTab) {
            desktopTab.addEventListener('click', () => {
                setTimeout(() => {
                    this.loadChartData('desktop');
                }, 100); // Small delay to ensure tab is visible
            });
        }

        // Mobile tab activation
        const mobileTab = document.getElementById('mobile-balance-chart-tab');
        if (mobileTab) {
            mobileTab.addEventListener('click', () => {
                setTimeout(() => {
                    this.loadChartData('mobile');
                }, 100); // Small delay to ensure tab is visible
            });
        }

        // Legend toggle buttons
        const toggleLegend = document.getElementById('toggleLegend');
        if (toggleLegend) {
            toggleLegend.addEventListener('click', () => {
                this.toggleLegend('desktop');
            });
        }

        const toggleLegendMobile = document.getElementById('toggleLegendMobile');
        if (toggleLegendMobile) {
            toggleLegendMobile.addEventListener('click', () => {
                this.toggleLegend('mobile');
            });
        }

        // Removed resize handler to prevent any sizing issues
    }

    async loadChartData(chartType) {
        if (!this.leagueId) {
            console.error('No league ID available');
            this.showError(chartType);
            return;
        }

        // If we already have data and chart is initialized, just show it
        if (this.chartData && this.charts[chartType]) {
            this.showChart(chartType);
            return;
        }

        try {
            const url = `/leagues/${this.leagueId}/balance-chart`;
            const response = await fetch(url);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            this.chartData = data;

            if (!data.labels || data.labels.length === 0 || !data.datasets || data.datasets.length === 0) {
                this.showError(chartType);
                return;
            }

            await this.createChart(chartType);

        } catch (error) {
            console.error('Error loading chart data:', error);
            this.showError(chartType);
        }
    }

    async createChart(chartType) {
        if (typeof Chart === 'undefined') {
            console.error('Chart.js not available');
            this.showError(chartType);
            return;
        }
        
        const canvasId = chartType === 'desktop' ? 'balanceChart' : 'balanceChartMobile';
        const canvas = document.getElementById(canvasId);
        
        if (!canvas) {
            console.error('Canvas not found:', canvasId);
            this.showError(chartType);
            return;
        }

        try {
            // Destroy existing chart
            if (this.charts[chartType]) {
                this.charts[chartType].destroy();
            }

            // Create custom legend with profile pictures
            this.createCustomLegend(chartType, this.chartData.datasets);

            // Preload all profile images before creating the chart
            await this.preloadAllProfileImages();

            const ctx = canvas.getContext('2d');
            const options = this.getChartOptions(chartType);
            
            this.charts[chartType] = new Chart(ctx, {
                type: 'line',
                data: this.chartData,
                options: options
            });
            this.showChart(chartType);

        } catch (error) {
            console.error('Error creating chart:', error);
            this.showError(chartType);
        }
    }

    createCustomLegend(chartType, datasets) {
        const legendContainerId = chartType === 'desktop' ? 'customLegend' : 'customLegendMobile';
        let legendContainer = document.getElementById(legendContainerId);
        
        if (!legendContainer) {
            // Create legend container if it doesn't exist
            const chartContainer = document.getElementById(chartType === 'desktop' ? 'balanceChart' : 'balanceChartMobile').parentNode;
            legendContainer = document.createElement('div');
            legendContainer.id = legendContainerId;
            legendContainer.className = 'custom-chart-legend';
            
            // Insert BEFORE the chart container, not inside it
            chartContainer.parentNode.insertBefore(legendContainer, chartContainer);
        }

        // Clear existing content
        legendContainer.innerHTML = '';

        // Generate legend HTML
        let legendHtml = '<div class="row g-2">';
        
        datasets.forEach((dataset, index) => {
            const userIcon = dataset.userIcon || '/assets/img/default-avatar.png';
            legendHtml += `
                <div class="col-6 col-md-3 col-lg-2">
                    <div class="legend-item d-flex align-items-center" 
                         data-dataset-index="${index}" 
                         data-chart-type="${chartType}"
                         onclick="window.leagueBalanceChart.toggleDataset(${index}, '${chartType}')"
                         style="cursor: pointer;"
                         title="Haz clic para mostrar/ocultar esta línea">
                        <img src="${userIcon}" 
                             alt="${dataset.label}" 
                             class="rounded-circle me-2" 
                             onerror="this.src='/assets/img/default-avatar.png'">
                        <div class="legend-color-box me-2" 
                             style="background-color: ${dataset.borderColor};"></div>
                        <span class="legend-text">${dataset.label}</span>
                    </div>
                </div>
            `;
        });
        
        legendHtml += '</div>';
        legendContainer.innerHTML = legendHtml;

        // Initialize all legend items as visible
        datasets.forEach((dataset, index) => {
            this.updateLegendItemAppearance(index, chartType, false);
        });
    }

    async preloadAllProfileImages() {
        if (!this.chartData || !this.chartData.datasets) return;
        
        const promises = this.chartData.datasets.map(async (dataset) => {
            if (dataset.userIcon && !this.imageCache.has(dataset.userIcon)) {
                try {
                    const img = await this.loadSingleImage(dataset.userIcon);
                    this.imageCache.set(dataset.userIcon, img);
                } catch (error) {
                    if (!dataset.userIcon.includes('/assets/img/default-avatar.png')) {
                        try {
                            const defaultImg = await this.loadSingleImage('/assets/img/default-avatar.png');
                            this.imageCache.set(dataset.userIcon, defaultImg);
                        } catch (defaultError) {
                            this.imageCache.set(dataset.userIcon, null);
                        }
                    } else {
                        this.imageCache.set(dataset.userIcon, null);
                    }
                }
            }
        });
        
        await Promise.all(promises);
    }

    loadSingleImage(src) {
        return new Promise((resolve, reject) => {
            const img = new Image();
            
            // No usar crossOrigin para dominios de Biwenger
            if (!src.includes('cdn.biwenger.com')) {
                img.crossOrigin = 'anonymous';
            }
            
            img.onload = () => {
                if (img.naturalWidth > 0 && img.naturalHeight > 0) {
                    resolve(img);
                } else {
                    reject(new Error('Invalid image dimensions'));
                }
            };
            
            img.onerror = (error) => {
                reject(new Error('Failed to load image'));
            };
            
            img.src = src;
        });
    }

    drawProfilePictures(chart) {
        if (!chart.data || !chart.data.datasets) return;
        
        const ctx = chart.ctx;
        
        chart.data.datasets.forEach((dataset, datasetIndex) => {
            // Skip if dataset is hidden
            const meta = chart.getDatasetMeta(datasetIndex);
            if (meta.hidden) return;

            const data = dataset.data;
            const userIcon = dataset.userIcon;
            
            if (!userIcon || !data || data.length === 0 || !meta.data) return;

            // Find the last non-null data point
            let lastPointIndex = -1;
            for (let i = data.length - 1; i >= 0; i--) {
                if (data[i] !== null && data[i] !== undefined) {
                    lastPointIndex = i;
                    break;
                }
            }

            if (lastPointIndex === -1 || !meta.data[lastPointIndex]) return;

            const point = meta.data[lastPointIndex];
            const imageSize = 24;
            
            // Use cached image
            const cachedImg = this.imageCache.get(userIcon);
            
            if (cachedImg) {
                // Draw the cached image
                try {
                    ctx.save();
                    
                    // Draw white background
                    ctx.beginPath();
                    ctx.arc(point.x, point.y, imageSize / 2 + 2, 0, 2 * Math.PI);
                    ctx.fillStyle = '#ffffff';
                    ctx.fill();
                    
                    // Create clipping path
                    ctx.beginPath();
                    ctx.arc(point.x, point.y, imageSize / 2, 0, 2 * Math.PI);
                    ctx.clip();
                    
                    // Draw image
                    const x = point.x - imageSize / 2;
                    const y = point.y - imageSize / 2;
                    ctx.drawImage(cachedImg, x, y, imageSize, imageSize);
                    
                    ctx.restore();
                    
                    // Draw border
                    ctx.beginPath();
                    ctx.arc(point.x, point.y, imageSize / 2, 0, 2 * Math.PI);
                    ctx.strokeStyle = dataset.borderColor || '#333';
                    ctx.lineWidth = 2;
                    ctx.stroke();
                    
                } catch (error) {
                    this.drawFallbackProfileImage(ctx, point, dataset, imageSize);
                }
            } else {
                // Draw fallback (initials)
                this.drawFallbackProfileImage(ctx, point, dataset, imageSize);
            }
        });
    }

    toggleDataset(datasetIndex, chartType) {
        const chart = this.charts[chartType];
        if (!chart) return;

        // Get the dataset meta information
        const meta = chart.getDatasetMeta(datasetIndex);
        
        // Toggle the visibility
        meta.hidden = meta.hidden === null ? !chart.data.datasets[datasetIndex].hidden : null;
        
        // Update the chart (this will automatically trigger the plugin to redraw profile pictures)
        chart.update();

        // Update legend item appearance
        this.updateLegendItemAppearance(datasetIndex, chartType, meta.hidden);
    }

    updateLegendItemAppearance(datasetIndex, chartType, isHidden) {
        const legendContainerId = chartType === 'desktop' ? 'customLegend' : 'customLegendMobile';
        const legendContainer = document.getElementById(legendContainerId);
        
        if (!legendContainer) return;

        const legendItem = legendContainer.querySelector(`[data-dataset-index="${datasetIndex}"]`);
        if (!legendItem) return;

        if (isHidden) {
            legendItem.style.opacity = '0.5';
            legendItem.classList.add('legend-item-hidden');
        } else {
            legendItem.style.opacity = '1';
            legendItem.classList.remove('legend-item-hidden');
        }
    }

    drawFallbackProfileImage(ctx, point, dataset, imageSize) {
        try {
            ctx.save();
            
            // Draw background circle
            ctx.beginPath();
            ctx.arc(point.x, point.y, imageSize / 2, 0, 2 * Math.PI);
            ctx.fillStyle = dataset.borderColor || '#6c757d';
            ctx.fill();
            
            // Draw border
            ctx.beginPath();
            ctx.arc(point.x, point.y, imageSize / 2, 0, 2 * Math.PI);
            ctx.strokeStyle = '#ffffff';
            ctx.lineWidth = 2;
            ctx.stroke();
            
            // Draw initials
            const initials = dataset.label.split(' ').map(word => word[0]).join('').substring(0, 2).toUpperCase();
            ctx.fillStyle = '#ffffff';
            ctx.font = 'bold 10px Arial';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillText(initials, point.x, point.y);
            
            ctx.restore();
        } catch (error) {
            // Silent fallback - just don't draw anything if error
        }
    }

    getChartOptions(chartType) {
        // Calculate Y-axis range from data for better control
        let minValue = null;
        let maxValue = null;
        
        if (this.chartData && this.chartData.datasets) {
            this.chartData.datasets.forEach(dataset => {
                dataset.data.forEach(value => {
                    if (value !== null && value !== undefined) {
                        if (minValue === null || value < minValue) minValue = value;
                        if (maxValue === null || value > maxValue) maxValue = value;
                    }
                });
            });
        }

        // Calculate nice min/max values with 1M stepSize for clean intervals
        const stepSize = 1000000; // Cada 1 millón para intervalos limpios
        const roundToStep = (value, step) => Math.floor(value / step) * step;
        
        // Padding aumentado significativamente para más espacio entre líneas
        const padding = stepSize; // 6M de padding (era 2M)
        
        const yMin = minValue !== null ? roundToStep(minValue - padding, stepSize) : 0;
        const yMax = maxValue !== null ? roundToStep(maxValue + padding, stepSize) + stepSize : 10000000;
        
        return {
            responsive: true,
            maintainAspectRatio: false,
            layout: {
                padding: {
                    top: 30,
                    right: 40,
                    bottom: 20,
                    left: 20
                }
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const value = context.parsed.y;
                            if (value !== null && value !== undefined) {
                                return `${context.dataset.label}: ${new Intl.NumberFormat('es-ES').format(value)}€`;
                            }
                            return `${context.dataset.label}: Sin datos`;
                        }
                    }
                },
                // Custom plugin to draw profile pictures
                profilePictures: {
                    afterDatasetsDraw: (chart) => {
                        this.drawProfilePictures(chart, chartType);
                    }
                }
            },
            scales: {
                x: {
                    display: true,
                    grid: {
                        display: true,
                        color: 'rgba(0,0,0,0.1)',
                        lineWidth: 1
                    },
                    ticks: {
                        padding: 10
                    }
                },
                y: {
                    type: 'linear',
                    display: true,
                    beginAtZero: false,
                    min: yMin,
                    max: yMax,
                    grid: {
                        display: true,
                        color: 'rgba(0,0,0,0.1)',
                        lineWidth: 1
                    },
                    ticks: {
                        display: true,
                        stepSize: 2000000,
                        maxTicksLimit: 50,
                        includeBounds: false,
                        padding: 15,
                        callback: function(value) {
                            return new Intl.NumberFormat('es-ES', {
                                style: 'currency',
                                currency: 'EUR',
                                minimumFractionDigits: 0,
                                maximumFractionDigits: 0
                            }).format(value);
                        }
                    },
                    border: {
                        display: true
                    },
                    afterBuildTicks: function(scale) {
                        const ticks = [];
                        for (let i = yMin; i <= yMax; i += 2000000) {
                            ticks.push({ value: i });
                        }
                        scale.ticks = ticks;
                    }
                }
            }
        };
    }

    showChart(chartType) {
        const canvasId = chartType === 'desktop' ? 'balanceChart' : 'balanceChartMobile';
        const errorId = chartType === 'desktop' ? 'chartError' : 'chartErrorMobile';
        
        const canvas = document.getElementById(canvasId);
        const error = document.getElementById(errorId);
        
        if (canvas) canvas.style.display = 'block';
        if (error) error.style.display = 'none';
    }

    showError(chartType) {
        const canvasId = chartType === 'desktop' ? 'balanceChart' : 'balanceChartMobile';
        const errorId = chartType === 'desktop' ? 'chartError' : 'chartErrorMobile';
        
        const canvas = document.getElementById(canvasId);
        const error = document.getElementById(errorId);
        
        if (canvas) canvas.style.display = 'none';
        if (error) error.style.display = 'block';
    }

    toggleLegend(chartType) {
        const legendContainerId = chartType === 'desktop' ? 'customLegend' : 'customLegendMobile';
        const legendContainer = document.getElementById(legendContainerId);
        
        if (!legendContainer) return;

        const isHidden = legendContainer.style.display === 'none';
        legendContainer.style.display = isHidden ? 'block' : 'none';

        const buttonId = chartType === 'desktop' ? 'toggleLegend' : 'toggleLegendMobile';
        const button = document.getElementById(buttonId);
        if (button) {
            if (isHidden) {
                button.innerHTML = '<i class="fas fa-eye-slash me-1"></i>Ocultar Leyenda';
                button.classList.remove('btn-outline-primary');
                button.classList.add('btn-primary');
            } else {
                button.innerHTML = '<i class="fas fa-list me-1"></i>Mostrar Leyenda';
                button.classList.remove('btn-primary');
                button.classList.add('btn-outline-primary');
            }
        }
    }

    destroy() {
        Object.values(this.charts).forEach(chart => {
            if (chart) {
                chart.profilePicturesAdded = false;
                chart.destroy();
            }
        });
        this.charts = { desktop: null, mobile: null };
        this.chartData = null;
    }
}

// Initialize when DOM is ready and Chart.js is available
document.addEventListener('DOMContentLoaded', function() {
    function initializeChart() {
        if (typeof Chart !== 'undefined') {
            window.leagueBalanceChart = new LeagueBalanceChart();
        } else {
            setTimeout(initializeChart, 100);
        }
    }
    
    initializeChart();
});

// Clean up when leaving the page
window.addEventListener('beforeunload', function() {
    if (window.leagueBalanceChart) {
        window.leagueBalanceChart.destroy();
    }
});
