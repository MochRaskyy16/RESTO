/**
 * premium-dashboard.js
 * Dashboard Restoran Premium - Animations & Interactions
 */

// ============================================
// LOADING SPINNER
// ============================================

window.addEventListener('load', function() {
    const spinner = document.getElementById('loadingSpinner');
    if (spinner) {
        setTimeout(() => {
            spinner.classList.remove('show');
        }, 500);
    }
    
    // Start counter animation
    animateNumbers();
    
    // Start particle effect
    createParticles();
});

// ============================================
// COUNTER NUMBER ANIMATION
// ============================================

function animateNumbers() {
    const counters = document.querySelectorAll('.counter');
    
    counters.forEach(counter => {
        const target = parseInt(counter.getAttribute('data-target'));
        const duration = 2000;
        const step = target / (duration / 16);
        let current = 0;
        
        const updateCounter = () => {
            current += step;
            if (current < target) {
                counter.innerText = Math.ceil(current);
                requestAnimationFrame(updateCounter);
            } else {
                counter.innerText = target;
            }
        };
        
        updateCounter();
    });
}

// ============================================
// PARTICLE EFFECT FOR HERO
// ============================================

function createParticles() {
    const hero = document.querySelector('.hero-banner');
    if (!hero) return;
    
    for (let i = 0; i < 30; i++) {
        const particle = document.createElement('div');
        particle.classList.add('particle');
        particle.style.width = Math.random() * 8 + 2 + 'px';
        particle.style.height = particle.style.width;
        particle.style.left = Math.random() * 100 + '%';
        particle.style.top = Math.random() * 100 + '%';
        particle.style.animationDelay = Math.random() * 4 + 's';
        particle.style.animationDuration = Math.random() * 3 + 2 + 's';
        hero.appendChild(particle);
        
        // Remove particle after animation
        setTimeout(() => {
            particle.remove();
        }, 4000);
    }
    
    setInterval(createParticles, 3000);
}

// ============================================
// MOBILE SIDEBAR TOGGLE
// ============================================

function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    sidebar.classList.toggle('show');
}

// Add toggle button to body
document.addEventListener('DOMContentLoaded', function() {
    const toggleBtn = document.createElement('button');
    toggleBtn.className = 'menu-toggle';
    toggleBtn.innerHTML = '<i class="fas fa-bars"></i>';
    toggleBtn.onclick = toggleSidebar;
    document.body.appendChild(toggleBtn);
    
    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(e) {
        const sidebar = document.querySelector('.sidebar');
        const toggle = document.querySelector('.menu-toggle');
        if (window.innerWidth <= 992) {
            if (!sidebar.contains(e.target) && !toggle.contains(e.target)) {
                sidebar.classList.remove('show');
            }
        }
    });
});

// ============================================
// RIPPLE EFFECT ON BUTTONS
// ============================================

document.querySelectorAll('.btn-premium, .btn-edit, .btn-delete').forEach(btn => {
    btn.classList.add('ripple');
});

// ============================================
// TABLE ROW HOVER EFFECT
// ============================================

document.querySelectorAll('.table-premium tbody tr').forEach(row => {
    row.addEventListener('mouseenter', function() {
        this.style.transition = 'all 0.3s ease';
    });
});

// ============================================
// IMAGE ERROR HANDLER
// ============================================

document.querySelectorAll('.food-img').forEach(img => {
    img.addEventListener('error', function() {
        this.src = 'assets/img/food-placeholder.png';
        this.style.objectFit = 'cover';
    });
});

// ============================================
// SEARCH FORM SUBMIT
// ============================================

const searchForm = document.getElementById('searchForm');
if (searchForm) {
    searchForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const search = document.getElementById('searchInput').value;
        const kategori = document.getElementById('kategoriFilter').value;
        
        // Show loading
        const spinner = document.getElementById('loadingSpinner');
        spinner.classList.add('show');
        
        // Redirect with params
        setTimeout(() => {
            window.location.href = `index.php?search=${encodeURIComponent(search)}&kategori=${kategori}`;
        }, 500);
    });
}

// ============================================
// DELETE CONFIRMATION WITH SWEETALERT
// ============================================

function confirmDelete(id, nama) {
    Swal.fire({
        title: 'Hapus Menu?',
        text: `Apakah Anda yakin ingin menghapus "${nama}"?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal',
        background: 'white',
        backdrop: true
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `hapus.php?id=${id}`;
        }
    });
}

// ============================================
// SMOOTH SCROLLING
// ============================================

document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

// ============================================
// TYPING ANIMATION FOR HERO
// ============================================

const typingText = "Kelola Menu Restoran Dengan Mudah & Cepat";
const typingElement = document.getElementById('typingText');

if (typingElement) {
    let i = 0;
    function typeWriter() {
        if (i < typingText.length) {
            typingElement.innerHTML += typingText.charAt(i);
            i++;
            setTimeout(typeWriter, 50);
        }
    }
    typeWriter();
}

// ============================================
// TOOLTIP INITIALIZATION (Bootstrap)
// ============================================

const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
});