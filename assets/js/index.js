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

    // Clear search when switching tabs
    const searchInput = document.getElementById('searchActivityList');
    if (searchInput && searchInput.value !== '') {
      searchInput.value = '';
      searchInput.dispatchEvent(new Event('input'));
    }
  });
});

// --- Search Activity Logic ---
document.addEventListener('DOMContentLoaded', () => {
  const searchInput = document.getElementById('searchActivityList');
  const clearBtn = document.getElementById('clearActivitySearch');

  if (searchInput) {
    searchInput.addEventListener('input', function () {
      const query = this.value.toLowerCase().trim();
      const activeContent = document.querySelector('.activity-content.active');
      if (!activeContent) return;

      const items = activeContent.querySelectorAll('.activity-item');
      const listContainer = activeContent.querySelector('.activity-list');
      let hasResults = false;

      // Clear any existing no-results message
      const oldNoResults = activeContent.querySelector('.no-results-activity');
      if (oldNoResults) oldNoResults.remove();

      items.forEach(item => {
        const title = item.querySelector('.book-title').textContent.toLowerCase();
        const name = item.querySelector('.member-name').textContent.toLowerCase();
        const isMatch = title.includes(query) || name.includes(query);

        if (isMatch) {
          item.style.display = 'flex';
          item.classList.remove('search-fade-out');
          item.classList.add('search-fade-in');
          hasResults = true;
        } else {
          item.classList.add('search-fade-out');
          setTimeout(() => {
            if (item.classList.contains('search-fade-out')) {
              item.style.display = 'none';
            }
          }, 300);
        }
      });

      // Show/Hide Clear Button
      if (clearBtn) clearBtn.style.display = query.length > 0 ? 'flex' : 'none';

      // No results handling
      if (!hasResults && query.length > 0) {
        const noResults = document.createElement('div');
        noResults.className = 'no-results-activity';
        noResults.style.cssText = 'padding: 40px 20px; text-align: center; color: var(--muted); background: var(--border); border-radius: 12px; margin: 10px; opacity: 0; transform: translateY(10px); transition: all 0.3s ease;';
        noResults.innerHTML = `
                    <iconify-icon icon="mdi:magnify-close" style="font-size: 32px; margin-bottom: 8px; opacity: 0.5;"></iconify-icon>
                    <div style="font-weight: 600;">Tidak ada hasil ditemukan</div>
                    <div style="font-size: 13px;">Coba gunakan kata kunci lain</div>
                `;
        listContainer.appendChild(noResults);
        setTimeout(() => {
          noResults.style.opacity = '1';
          noResults.style.transform = 'translateY(0)';
        }, 10);
      }
    });

    if (clearBtn) {
      clearBtn.addEventListener('click', () => {
        searchInput.value = '';
        searchInput.dispatchEvent(new Event('input'));
        searchInput.focus();
      });
    }
  }
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
let weeklyChart = null;
let topBooksChart = null;

// Initialize charts with data
function initializeCharts(monthlyBorrows) {
  const borrowChartEl = document.getElementById('borrowChart');
  if (borrowChartEl) {
    try {
      if (borrowChart !== null) borrowChart.destroy();
      const ctx = borrowChartEl.getContext('2d');
      const gradient = ctx.createLinearGradient(0, 0, 0, 200);
      gradient.addColorStop(0, 'rgba(59, 130, 246, 0.4)');
      gradient.addColorStop(1, 'rgba(59, 130, 246, 0)');

      borrowChart = new Chart(borrowChartEl, {
        type: 'line',
        data: {
          labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'],
          datasets: [{
            label: 'Peminjaman',
            data: monthlyBorrows,
            borderColor: '#3b82f6',
            backgroundColor: gradient,
            fill: true,
            borderWidth: 3,
            pointBackgroundColor: '#fff',
            pointBorderColor: '#3b82f6',
            pointBorderWidth: 2,
            pointRadius: 4,
            pointHoverRadius: 6,
            tension: 0.4
          }]
        },
        options: {
          responsive: true, maintainAspectRatio: false,
          plugins: { legend: { display: false } },
          scales: { y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)', drawBorder: false }, ticks: { color: '#64748b', font: { family: 'Inter', size: 11 } } }, x: { grid: { display: false, drawBorder: false }, ticks: { color: '#64748b', font: { family: 'Inter', size: 11 } } } }
        }
      });
    } catch (error) { console.error('Error borrowChart:', error); }
  }
}

function initializeStatusChart(totalAvailable, totalBorrowed, totalOverdue) {
  const statusChartEl = document.getElementById('statusChart');
  if (statusChartEl) {
    try {
      if (statusChart !== null) statusChart.destroy();
      statusChart = new Chart(statusChartEl, {
        type: 'doughnut',
        data: {
          labels: ['Tersedia', 'Dipinjam', 'Terlambat'],
          datasets: [{
            data: [totalAvailable, totalBorrowed, totalOverdue],
            backgroundColor: ['#10b981', '#3b82f6', '#ef4444'],
            borderWidth: 0,
            hoverOffset: 15
          }]
        },
        options: {
          responsive: true, maintainAspectRatio: false, cutout: '75%',
          plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, padding: 20, font: { family: 'Inter', size: 12, weight: 500 }, color: '#64748b' } }, tooltip: { backgroundColor: '#0f172a', padding: 12, cornerRadius: 8 } }
        }
      });
    } catch (error) { console.error('Error statusChart:', error); }
  }
}

function initializeWeeklyTrendChart(weeklyTrend) {
  const weeklyChartEl = document.getElementById('weeklyChart');
  if (weeklyChartEl) {
    try {
      if (weeklyChart !== null) weeklyChart.destroy();
      const labels = weeklyTrend.map(d => d.label);
      const data = weeklyTrend.map(d => d.count);

      weeklyChart = new Chart(weeklyChartEl, {
        type: 'line',
        data: {
          labels: labels,
          datasets: [{
            label: 'Peminjaman',
            data: data,
            borderColor: '#10b981',
            backgroundColor: 'rgba(16, 185, 129, 0.1)',
            fill: true,
            borderWidth: 3,
            tension: 0.4,
            pointBackgroundColor: '#10b981'
          }]
        },
        options: {
          responsive: true, maintainAspectRatio: false,
          plugins: { legend: { display: false } },
          scales: { y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' } }, x: { grid: { display: false } } }
        }
      });
    } catch (e) { console.error(e); }
  }
}

function initializeTopBooksChart(topBooks) {
  const topBooksChartEl = document.getElementById('topBooksChart');
  if (topBooksChartEl) {
    try {
      if (topBooksChart !== null) topBooksChart.destroy();
      const labels = topBooks.map(b => b.title.length > 20 ? b.title.substring(0, 20) + '...' : b.title);
      const data = topBooks.map(b => b.borrow_count);

      topBooksChart = new Chart(topBooksChartEl, {
        type: 'bar',
        data: {
          labels: labels,
          datasets: [{
            label: 'Total Pinjam',
            data: data,
            backgroundColor: 'rgba(59, 130, 246, 0.8)',
            borderRadius: 8,
            hoverBackgroundColor: '#3b82f6'
          }]
        },
        options: {
          indexAxis: 'y',
          responsive: true, maintainAspectRatio: false,
          plugins: { legend: { display: false } },
          scales: { x: { beginAtZero: true, grid: { display: false } }, y: { grid: { display: false } } }
        }
      });
    } catch (e) { console.error(e); }
  }
}

function renderTopMembers(topMembers) {
  const container = document.getElementById('top-members-list');
  if (!container) return;

  if (topMembers.length === 0) {
    container.innerHTML = '<div class="empty-activity">Belum ada data</div>';
    return;
  }

  container.innerHTML = topMembers.map((m, index) => `
        <div class="top-item">
            <div class="top-rank">${index + 1}</div>
            <div class="top-info">
                <div class="top-name">${m.name}</div>
                <div class="top-meta">Anggota Perpustakaan</div>
            </div>
            <div class="top-count">${m.borrow_count} <span>Buku</span></div>
        </div>
    `).join('');
}

// Auto-fetch dashboard statistics and initialize charts
async function loadDashboardStats() {
  try {
    const apiUrl = '/perpustakaan-online/public/api/dashboard-stats.php';
    const response = await fetch(apiUrl, { credentials: 'include', method: 'GET' });
    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
    const data = await response.json();

    if (data.success) {
      // Update stat card values
      document.getElementById('stat-books').textContent = data.stats.total_books;
      document.getElementById('stat-members').textContent = data.stats.total_members;
      document.getElementById('stat-borrowed').textContent = data.stats.total_borrowed;
      document.getElementById('stat-overdue').textContent = data.stats.total_overdue;

      // Initialize all charts
      initializeCharts(data.chart_data.monthly_chart);
      initializeStatusChart(data.stats.total_available, data.stats.total_borrowed, data.stats.total_overdue);
      initializeWeeklyTrendChart(data.chart_data.weekly_trend);
      initializeTopBooksChart(data.chart_data.top_books);
      renderTopMembers(data.chart_data.top_members);
    }
  } catch (error) {
    console.error('Error loading dashboard stats:', error);
    // Silent fallbacks for empty charts
    initializeCharts(Array(12).fill(0));
    initializeStatusChart(0, 0, 0);
    initializeWeeklyTrendChart([]);
    initializeTopBooksChart([]);
    renderTopMembers([]);
  }
}

// Load stats when DOM is ready (with immediate check for already-loaded DOM)
function initLoadDashboardStats() {
  console.log('Initializing loadDashboardStats...');
  loadDashboardStats();
}
