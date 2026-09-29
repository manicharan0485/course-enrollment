/* ===================================
   Admin Dashboard Charts
   Chart.js Visualizations
   =================================== */

document.addEventListener('DOMContentLoaded', function() {
    
    // ===================================
    // APPLICATIONS CHART
    // ===================================
    const applicationsCanvas = document.getElementById('applicationsChart');
    
    if (applicationsCanvas) {
        const ctx = applicationsCanvas.getContext('2d');
        
        const gradient = ctx.createLinearGradient(0, 0, 0, 400);
        gradient.addColorStop(0, 'rgba(255, 107, 61, 0.3)');
        gradient.addColorStop(1, 'rgba(255, 107, 61, 0.05)');
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                datasets: [{
                    label: 'Applications',
                    data: [12, 19, 15, 25, 22, 30, 28],
                    borderColor: '#FF6B3D',
                    backgroundColor: gradient,
                    borderWidth: 3,
                    tension: 0.4,
                    fill: true,
                    pointRadius: 4,
                    pointBackgroundColor: '#FF6B3D',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointHoverRadius: 6,
                    pointHoverBackgroundColor: '#FF6B3D',
                    pointHoverBorderColor: '#fff',
                    pointHoverBorderWidth: 3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.9)',
                        titleColor: '#F1F5F9',
                        bodyColor: '#CBD5E1',
                        borderColor: 'rgba(255, 107, 61, 0.3)',
                        borderWidth: 1,
                        padding: 12,
                        displayColors: false,
                        callbacks: {
                            title: function(tooltipItems) {
                                return tooltipItems[0].label;
                            },
                            label: function(context) {
                                return context.parsed.y + ' applications';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false,
                            drawBorder: false
                        },
                        ticks: {
                            color: '#94A3B8',
                            font: {
                                size: 12
                            }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(255, 255, 255, 0.05)',
                            drawBorder: false
                        },
                        ticks: {
                            color: '#94A3B8',
                            font: {
                                size: 12
                            },
                            stepSize: 10
                        }
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index'
                }
            }
        });
    }
    
    // ===================================
    // REVENUE CHART (if exists)
    // ===================================
    const revenueCanvas = document.getElementById('revenueChart');
    
    if (revenueCanvas) {
        const ctx = revenueCanvas.getContext('2d');
        
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [{
                    label: 'Revenue',
                    data: [12000, 19000, 15000, 25000, 22000, 30000],
                    backgroundColor: 'rgba(255, 107, 61, 0.8)',
                    borderColor: '#FF6B3D',
                    borderWidth: 2,
                    borderRadius: 8,
                    borderSkipped: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.9)',
                        titleColor: '#F1F5F9',
                        bodyColor: '#CBD5E1',
                        borderColor: 'rgba(255, 107, 61, 0.3)',
                        borderWidth: 1,
                        padding: 12,
                        displayColors: false,
                        callbacks: {
                            label: function(context) {
                                return '$' + context.parsed.y.toLocaleString();
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false,
                            drawBorder: false
                        },
                        ticks: {
                            color: '#94A3B8',
                            font: {
                                size: 12
                            }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(255, 255, 255, 0.05)',
                            drawBorder: false
                        },
                        ticks: {
                            color: '#94A3B8',
                            font: {
                                size: 12
                            },
                            callback: function(value) {
                                return '$' + (value / 1000) + 'k';
                            }
                        }
                    }
                }
            }
        });
    }
    
    // ===================================
    // USER GROWTH CHART (if exists)
    // ===================================
    const userGrowthCanvas = document.getElementById('userGrowthChart');
    
    if (userGrowthCanvas) {
        const ctx = userGrowthCanvas.getContext('2d');
        
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Active Users', 'Inactive Users', 'New Users'],
                datasets: [{
                    data: [65, 25, 10],
                    backgroundColor: [
                        '#10B981',
                        '#F59E0B',
                        '#FF6B3D'
                    ],
                    borderWidth: 0,
                    hoverOffset: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: '#CBD5E1',
                            padding: 15,
                            font: {
                                size: 12
                            },
                            usePointStyle: true
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.9)',
                        titleColor: '#F1F5F9',
                        bodyColor: '#CBD5E1',
                        borderColor: 'rgba(255, 107, 61, 0.3)',
                        borderWidth: 1,
                        padding: 12,
                        callbacks: {
                            label: function(context) {
                                return context.label + ': ' + context.parsed + '%';
                            }
                        }
                    }
                },
                cutout: '70%'
            }
        });
    }
    
    // ===================================
    // COURSE ENROLLMENT CHART (if exists)
    // ===================================
    const enrollmentCanvas = document.getElementById('enrollmentChart');
    
    if (enrollmentCanvas) {
        const ctx = enrollmentCanvas.getContext('2d');
        
        new Chart(ctx, {
            type: 'radar',
            data: {
                labels: ['Web Dev', 'Data Science', 'Marketing', 'Design', 'Business'],
                datasets: [{
                    label: 'Enrollments',
                    data: [85, 70, 60, 75, 65],
                    backgroundColor: 'rgba(255, 107, 61, 0.2)',
                    borderColor: '#FF6B3D',
                    borderWidth: 2,
                    pointBackgroundColor: '#FF6B3D',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.9)',
                        titleColor: '#F1F5F9',
                        bodyColor: '#CBD5E1',
                        borderColor: 'rgba(255, 107, 61, 0.3)',
                        borderWidth: 1,
                        padding: 12
                    }
                },
                scales: {
                    r: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            stepSize: 20,
                            color: '#94A3B8',
                            backdropColor: 'transparent'
                        },
                        grid: {
                            color: 'rgba(255, 255, 255, 0.05)'
                        },
                        pointLabels: {
                            color: '#CBD5E1',
                            font: {
                                size: 12
                            }
                        }
                    }
                }
            }
        });
    }
    
    // ===================================
    // UPDATE CHARTS ON TIME FILTER CHANGE
    // ===================================
    const timeFilters = document.querySelectorAll('.time-filter');
    
    timeFilters.forEach(filter => {
        filter.addEventListener('change', function() {
            const period = this.value;
            updateCharts(period);
        });
    });
    
    function updateCharts(period) {
        console.log('Updating charts for period:', period);
        
        // Here you would make an AJAX call to fetch new data
        // and update the charts
        
        // Example:
        // fetch(`/api/dashboard/stats?period=${period}`)
        //     .then(response => response.json())
        //     .then(data => {
        //         // Update chart data
        //         applicationsChart.data.datasets[0].data = data.applications;
        //         applicationsChart.update();
        //     });
    }
    
    // ===================================
    // CHART ANIMATIONS
    // ===================================
    function animateCharts() {
        // Trigger chart animations
        Chart.helpers.each(Chart.instances, function(instance) {
            instance.update();
        });
    }
    
    // Animate charts on scroll into view
    const chartCards = document.querySelectorAll('.chart-card');
    
    const chartObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                animateCharts();
                chartObserver.unobserve(entry.target);
            }
        });
    }, {
        threshold: 0.3
    });
    
    chartCards.forEach(card => {
        chartObserver.observe(card);
    });
    
    // ===================================
    // EXPORT CHART FUNCTIONALITY
    // ===================================
    window.exportChart = function(chartId, filename) {
        const canvas = document.getElementById(chartId);
        if (canvas) {
            const url = canvas.toDataURL('image/png');
            const link = document.createElement('a');
            link.download = filename || 'chart.png';
            link.href = url;
            link.click();
        }
    };
    
    // ===================================
    // PRINT CHART
    // ===================================
    window.printChart = function(chartId) {
        const canvas = document.getElementById(chartId);
        if (canvas) {
            const dataUrl = canvas.toDataURL();
            const windowContent = '<!DOCTYPE html>';
            windowContent += '<html>';
            windowContent += '<head><title>Print Chart</title></head>';
            windowContent += '<body>';
            windowContent += '<img src="' + dataUrl + '">';
            windowContent += '</body>';
            windowContent += '</html>';
            
            const printWin = window.open('', '', 'width=800,height=600');
            printWin.document.open();
            printWin.document.write(windowContent);
            printWin.document.close();
            printWin.focus();
            printWin.print();
            printWin.close();
        }
    };
    
    console.log('✨ Admin charts initialized successfully!');
});

// ===================================
// CHART CONFIGURATION DEFAULTS
// ===================================
Chart.defaults.font.family = "'DM Sans', sans-serif";
Chart.defaults.color = '#CBD5E1';
Chart.defaults.plugins.legend.labels.boxWidth = 12;
Chart.defaults.plugins.legend.labels.boxHeight = 12;

// ===================================
// CUSTOM CHART ANIMATIONS
// ===================================
const originalLineDraw = Chart.controllers.line.prototype.draw;
Chart.helpers.extend(Chart.controllers.line.prototype, {
    draw: function() {
        originalLineDraw.apply(this, arguments);
    }
});