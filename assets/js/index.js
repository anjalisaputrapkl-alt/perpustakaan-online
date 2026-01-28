// Activity tabs functionality
document.querySelectorAll('.activity-tab').forEach(tab => {
  tab.addEventListener('click', () => {
    const tabName = tab.getAttribute('data-tab');

    // Remove active class from all tabs and contents
    document.querySelectorAll('.activity-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.activity-content').forEach(c => c.classList.remove('active'));

    // Add active class to clicked tab and its content
    tab.classList.add('active');
    document.getElementById(tabName + '-content').classList.add('active');
  });
});

// FAQ functionality
document.querySelectorAll('.faq-question').forEach(item => {
  item.addEventListener('click', () => {
    const parent = item.parentElement;
    parent.classList.toggle('active');
    item.querySelector('span').textContent =
      parent.classList.contains('active') ? '−' : '+';
  });
});

// Chart instances (global for potential updates)
let borrowChart = null;
let statusChart = null;

// Initialize charts with data
function initializeCharts(monthlyBorrows) {
  console.log('📊 initializeCharts() called with monthly data:', monthlyBorrows);
  const borrowChartEl = document.getElementById('borrowChart');
  console.log('   borrowChart element found:', !!borrowChartEl, borrowChartEl);
  
  if (borrowChartEl) {
    try {
      // Destroy existing chart if any
      if (borrowChart !== null) {
        console.log('   Destroying previous borrowChart...');
        borrowChart.destroy();
      }
      
      borrowChart = new Chart(borrowChartEl, {
        type: 'line',
        data: {
          labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'],
          datasets: [{
            data: monthlyBorrows,
            borderColor: '#2563eb',
            tension: 0.3
          }]
        },
        options: { plugins: { legend: { display: false } } }
      });
      console.log('   ✅ borrowChart initialized successfully');
    } catch (error) {
      console.error('   ❌ Error initializing borrowChart:', error);
    }
  } else {
    console.warn('   ⚠️ borrowChart element NOT found in DOM');
  }
}

function initializeStatusChart(totalAvailable, totalBorrowed, totalOverdue) {
  console.log('📊 initializeStatusChart() called with:', {totalAvailable, totalBorrowed, totalOverdue});
  const statusChartEl = document.getElementById('statusChart');
  console.log('   statusChart element found:', !!statusChartEl, statusChartEl);
  
  if (statusChartEl) {
    try {
      // Destroy existing chart if any
      if (statusChart !== null) {
        console.log('   Destroying previous chart...');
        statusChart.destroy();
      }
      
      statusChart = new Chart(statusChartEl, {
        type: 'doughnut',
        data: {
          labels: ['Tersedia', 'Dipinjam', 'Terlambat'],
          datasets: [{
            data: [
              totalAvailable,
              totalBorrowed,
              totalOverdue
            ],
            backgroundColor: ['#16a34a', '#2563eb', '#dc2626']
          }]
        },
        options: { 
          plugins: { legend: { position: 'bottom' } } 
        }
      });
      console.log('   ✅ statusChart initialized successfully');
    } catch (error) {
      console.error('   ❌ Error initializing statusChart:', error);
    }
  } else {
    console.warn('   ⚠️ statusChart element NOT found in DOM');
  }
}

// Auto-fetch dashboard statistics and initialize charts
async function loadDashboardStats() {
  try {
    console.log('🚀 loadDashboardStats() called!');
    const apiUrl = '/perpustakaan-online/public/api/dashboard-stats.php';
    console.log('📡 Fetching from:', apiUrl);
    const response = await fetch(apiUrl, {
      credentials: 'include',
      method: 'GET'
    });

    if (!response.ok) {
      console.error('❌ HTTP Error:', response.status, response.statusText);
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    const data = await response.json();
    console.log('✅ API Response received:', data);
    
    if (data.success) {
      console.log('📊 Dashboard stats loaded:', data.stats);
      
      // Update stat card values
      document.getElementById('stat-books').textContent = data.stats.total_books;
      document.getElementById('stat-members').textContent = data.stats.total_members;
      document.getElementById('stat-borrowed').textContent = data.stats.total_borrowed;
      document.getElementById('stat-overdue').textContent = data.stats.total_overdue;
      console.log('✅ Stat cards updated');
      
      // Initialize charts with fetched data
      initializeCharts(data.chart_data.monthly_chart);
      initializeStatusChart(
        data.stats.total_available,
        data.stats.total_borrowed,
        data.stats.total_overdue
      );
      console.log('✅ Charts initialized with API data');
    } else {
      console.error('⚠️ API returned success=false:', data.message);
    }
  } catch (error) {
    console.error('❌ Error loading dashboard stats:', error);
    // Fallback: Initialize with default values if API fails
    console.warn('⚠️ Initializing charts with default values');
    initializeCharts([0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0]);
    initializeStatusChart(0, 0, 0);
  }
}

// Load stats when DOM is ready (with immediate check for already-loaded DOM)
function initLoadDashboardStats() {
  console.log('Initializing loadDashboardStats...');
  loadDashboardStats();
}
