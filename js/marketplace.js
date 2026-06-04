/* ============================================
   PawMarket — Client-side Logic
   ============================================ */
document.addEventListener('DOMContentLoaded', () => {

  // ---------- Mobile Hamburger Toggle ----------
  const hamburger = document.querySelector('.mp-hamburger');
  const navLinks  = document.querySelector('.mp-nav-links');

  if (hamburger && navLinks) {
    hamburger.addEventListener('click', () => {
      navLinks.classList.toggle('open');
      hamburger.classList.toggle('active');
    });

    // Close menu when a link is clicked
    navLinks.querySelectorAll('a').forEach(link => {
      link.addEventListener('click', () => {
        navLinks.classList.remove('open');
        hamburger.classList.remove('active');
      });
    });
  }

  // ---------- City Filter Pills ----------
  const pills = document.querySelectorAll('.mp-pill');
  const cards  = document.querySelectorAll('.mp-card[data-city]');

  pills.forEach(pill => {
    pill.addEventListener('click', () => {
      // Toggle active state
      pills.forEach(p => p.classList.remove('active'));
      pill.classList.add('active');

      const city = pill.dataset.city || '';

      cards.forEach(card => {
        if (!city || card.dataset.city === city) {
          card.style.display = '';
        } else {
          card.style.display = 'none';
        }
      });

      // Show/hide empty state
      const grid = document.querySelector('.mp-grid');
      if (grid) {
        const visibleCards = grid.querySelectorAll('.mp-card:not([style*="display: none"])');
        let emptyEl = grid.querySelector('.mp-empty');
        if (visibleCards.length === 0) {
          if (!emptyEl) {
            emptyEl = document.createElement('div');
            emptyEl.className = 'mp-empty';
            emptyEl.innerHTML = '<span class="mp-empty-icon">🔍</span><p>No dogs found in this city.</p>';
            grid.appendChild(emptyEl);
          }
          emptyEl.style.display = '';
        } else if (emptyEl) {
          emptyEl.style.display = 'none';
        }
      }
    });
  });

  // ---------- Photo Gallery (Pet Detail) ----------
  const mainImg = document.getElementById('galleryMain');
  const thumbs  = document.querySelectorAll('.mp-thumb');

  thumbs.forEach(thumb => {
    thumb.addEventListener('click', () => {
      if (!mainImg) return;

      // Swap main image
      mainImg.src = thumb.dataset.full;
      mainImg.alt = thumb.querySelector('img').alt || '';

      // Update active thumb
      thumbs.forEach(t => t.classList.remove('active'));
      thumb.classList.add('active');
    });
  });

  // ---------- Image Upload Preview ----------
  const fileInput   = document.getElementById('petPhotos');
  const previewGrid = document.getElementById('previewGrid');
  const uploadZone  = document.querySelector('.mp-upload-zone');

  if (fileInput && previewGrid) {
    fileInput.addEventListener('change', renderPreviews);

    // Drag & drop visual feedback
    if (uploadZone) {
      uploadZone.addEventListener('dragover', e => {
        e.preventDefault();
        uploadZone.classList.add('dragover');
      });
      uploadZone.addEventListener('dragleave', () => {
        uploadZone.classList.remove('dragover');
      });
      uploadZone.addEventListener('drop', () => {
        uploadZone.classList.remove('dragover');
      });
    }
  }

  function renderPreviews() {
    previewGrid.innerHTML = '';
    const files = fileInput.files;

    if (files.length > 5) {
      alert('You can upload a maximum of 5 photos.');
      fileInput.value = '';
      return;
    }

    Array.from(files).forEach((file, i) => {
      const reader = new FileReader();
      reader.onload = e => {
        const item = document.createElement('div');
        item.className = 'mp-preview-item';

        const img = document.createElement('img');
        img.src = e.target.result;
        img.alt = file.name;
        item.appendChild(img);

        // Badge on first image
        if (i === 0) {
          const badge = document.createElement('span');
          badge.className = 'mp-preview-badge';
          badge.textContent = 'Primary';
          item.appendChild(badge);
        }

        previewGrid.appendChild(item);
      };
      reader.readAsDataURL(file);
    });
  }

  // ---------- Report Form Toggle ----------
  const reportToggle = document.getElementById('reportToggle');
  const reportForm   = document.getElementById('reportForm');

  if (reportToggle && reportForm) {
    reportToggle.addEventListener('click', e => {
      e.preventDefault();
      reportForm.classList.toggle('visible');

      if (reportForm.classList.contains('visible')) {
        reportForm.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        const textarea = reportForm.querySelector('textarea');
        if (textarea) textarea.focus();
      }
    });
  }

  // ---------- Auto-dismiss Alerts ----------
  const alerts = document.querySelectorAll('.mp-alert');
  alerts.forEach(alert => {
    setTimeout(() => {
      alert.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
      alert.style.opacity = '0';
      alert.style.transform = 'translateY(-8px)';
      setTimeout(() => alert.remove(), 400);
    }, 4000);
  });

});
