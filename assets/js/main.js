// assets/js/main.js
// Main client-side script for CommunityHub

document.addEventListener('DOMContentLoaded', function () {

    // ═══════════════════════════════════════════════════════════
    // 1. Dark Mode Toggle
    // ═══════════════════════════════════════════════════════════
    const themeToggleBtn = document.getElementById('theme-toggle');
    if (themeToggleBtn) {
        themeToggleBtn.addEventListener('click', function () {
            const isDark = document.documentElement.classList.contains('dark');
            document.documentElement.classList.toggle('dark');
            localStorage.setItem('theme', isDark ? 'light' : 'dark');
        });
    }

    // ═══════════════════════════════════════════════════════════
    // 2. Mobile Navigation Toggle (hamburger menu)
    // ═══════════════════════════════════════════════════════════
    const navLinks  = document.querySelector('.nav-links');
    const navToggle = document.getElementById('nav-toggle');
    if (navToggle && navLinks) {
        navToggle.addEventListener('click', function () {
            navLinks.classList.toggle('open');
            navToggle.setAttribute('aria-expanded', navLinks.classList.contains('open'));
        });
        // Close on outside click
        document.addEventListener('click', function (e) {
            if (!navToggle.contains(e.target) && !navLinks.contains(e.target)) {
                navLinks.classList.remove('open');
            }
        });
    }

    // ═══════════════════════════════════════════════════════════
    // 3. Profile Picture Live Preview & Validation
    // ═══════════════════════════════════════════════════════════
    const profilePicInput   = document.getElementById('foto_profil');
    const previewImg        = document.getElementById('preview-image');
    const previewPlaceholder = document.getElementById('preview-placeholder');

    if (profilePicInput) {
        profilePicInput.addEventListener('change', function () {
            const file = this.files[0];
            if (!file) return;

            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
            const maxSize      = 2 * 1024 * 1024; // 2MB

            if (!allowedTypes.includes(file.type)) {
                showToast('Hanya diperbolehkan file gambar (.jpg, .jpeg, .png)!', 'danger');
                this.value = '';
                return;
            }
            if (file.size > maxSize) {
                showToast('Ukuran file terlalu besar! Maks. 2MB.', 'danger');
                this.value = '';
                return;
            }

            const reader = new FileReader();
            reader.onload = function (e) {
                if (previewImg) {
                    previewImg.src = e.target.result;
                    previewImg.style.display = 'block';
                    if (previewPlaceholder) previewPlaceholder.style.display = 'none';
                } else if (previewPlaceholder) {
                    previewPlaceholder.innerHTML = `<img src="${e.target.result}" alt="Preview" class="profile-pic-preview" id="preview-image">`;
                }
            };
            reader.readAsDataURL(file);
        });
    }

    // ═══════════════════════════════════════════════════════════
    // 4. Alert Auto-dismiss + Close Button
    // ═══════════════════════════════════════════════════════════
    document.querySelectorAll('.alert').forEach(function (alert) {
        // Add a close button
        const closeBtn = document.createElement('button');
        closeBtn.innerHTML = '&times;';
        closeBtn.className = 'alert-close-btn';
        closeBtn.style.cssText = 'float:right; background:none; border:none; font-size:18px; cursor:pointer; opacity:0.6; line-height:1; padding:0 0 0 10px;';
        closeBtn.addEventListener('click', function () { fadeOut(alert); });
        alert.insertBefore(closeBtn, alert.firstChild);

        // Auto-dismiss success alerts after 5 seconds
        if (alert.classList.contains('alert-success')) {
            setTimeout(function () { fadeOut(alert); }, 5000);
        }
    });

    function fadeOut(el) {
        el.style.transition = 'opacity 0.5s ease, max-height 0.5s ease, padding 0.5s ease, margin 0.5s ease';
        el.style.opacity    = '0';
        el.style.maxHeight  = '0';
        el.style.padding    = '0';
        el.style.margin     = '0';
        setTimeout(function () { if (el.parentNode) el.parentNode.removeChild(el); }, 500);
    }

    // ═══════════════════════════════════════════════════════════
    // 5. Character Counter for Textareas
    // ═══════════════════════════════════════════════════════════
    document.querySelectorAll('textarea').forEach(function (ta) {
        const maxLen = ta.getAttribute('maxlength');
        if (!maxLen) return;

        const counter = document.createElement('div');
        counter.style.cssText = 'text-align:right; font-size:11px; color:var(--text-muted); margin-top:3px;';
        counter.textContent = '0 / ' + maxLen;
        ta.parentNode.insertBefore(counter, ta.nextSibling);

        ta.addEventListener('input', function () {
            const len = this.value.length;
            counter.textContent = len + ' / ' + maxLen;
            counter.style.color = len > maxLen * 0.9 ? 'var(--warning)' : 'var(--text-muted)';
            if (len >= maxLen) counter.style.color = 'var(--danger)';
        });
    });

    // ═══════════════════════════════════════════════════════════
    // 6. Auto-resize Textarea
    // ═══════════════════════════════════════════════════════════
    document.querySelectorAll('textarea').forEach(function (ta) {
        ta.style.overflow = 'hidden';
        ta.style.resize   = 'vertical';
        ta.addEventListener('input', function () {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });
    });

    // ═══════════════════════════════════════════════════════════
    // 7. Password Visibility Toggle
    // ═══════════════════════════════════════════════════════════
    document.querySelectorAll('input[type="password"]').forEach(function (input) {
        const wrapper = document.createElement('div');
        wrapper.style.position = 'relative';
        input.parentNode.insertBefore(wrapper, input);
        wrapper.appendChild(input);

        const toggleBtn = document.createElement('button');
        toggleBtn.type  = 'button';
        toggleBtn.innerHTML = '👁';
        toggleBtn.style.cssText = 'position:absolute; right:12px; top:50%; transform:translateY(-50%); background:none; border:none; cursor:pointer; font-size:16px; opacity:0.5; padding:0;';
        toggleBtn.setAttribute('aria-label', 'Toggle password visibility');
        toggleBtn.addEventListener('click', function () {
            input.type   = input.type === 'password' ? 'text' : 'password';
            this.opacity = 1;
        });
        wrapper.appendChild(toggleBtn);
    });

    // ═══════════════════════════════════════════════════════════
    // 8. Upvote Button Animation
    // ═══════════════════════════════════════════════════════════
    document.querySelectorAll('.upvote-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            this.style.transform = 'scale(1.3)';
            setTimeout(function () { btn.style.transform = 'scale(1)'; }, 200);
        });
    });

    // ═══════════════════════════════════════════════════════════
    // 9. Active Nav Link Highlight
    // ═══════════════════════════════════════════════════════════
    const currentPath = window.location.pathname.split('/').pop();
    document.querySelectorAll('nav .nav-item').forEach(function (link) {
        const href = link.getAttribute('href');
        if (href && href.split('?')[0] === currentPath) {
            link.style.color = 'var(--primary)';
            link.style.borderBottom = '2px solid var(--primary)';
        }
    });

    // ═══════════════════════════════════════════════════════════
    // 10. Smooth Scroll to #comments anchor
    // ═══════════════════════════════════════════════════════════
    document.querySelectorAll('a[href="#comments"]').forEach(function (a) {
        a.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.getElementById('comments');
            if (target) target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    });

    // ═══════════════════════════════════════════════════════════
    // 11. Stat Counter Animations (Homepage)
    // ═══════════════════════════════════════════════════════════
    document.querySelectorAll('.stat-num').forEach(function (el) {
        const target = parseInt(el.textContent, 10);
        if (isNaN(target) || target === 0) return;

        let current  = 0;
        const step   = Math.max(1, Math.floor(target / 60));
        const timer  = setInterval(function () {
            current += step;
            if (current >= target) {
                current = target;
                clearInterval(timer);
            }
            el.textContent = current;
        }, 20);
    });

    // ═══════════════════════════════════════════════════════════
    // 12. Fade-in cards on scroll (Intersection Observer)
    // ═══════════════════════════════════════════════════════════
    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.style.opacity    = '1';
                    entry.target.style.transform  = 'translateY(0)';
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });

        document.querySelectorAll('.thread-card, .course-card, .comment-card, .card').forEach(function (el) {
            el.style.opacity    = '0';
            el.style.transform  = 'translateY(16px)';
            el.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
            observer.observe(el);
        });
    }

    // ═══════════════════════════════════════════════════════════
    // 13. Back-to-Top Button
    // ═══════════════════════════════════════════════════════════
    const backTop = document.createElement('button');
    backTop.id    = 'back-to-top';
    backTop.innerHTML = '↑';
    backTop.title     = 'Kembali ke atas';
    backTop.style.cssText = 'position:fixed; bottom:30px; right:30px; width:44px; height:44px; background:var(--primary); color:white; border:none; border-radius:50%; font-size:20px; cursor:pointer; display:none; align-items:center; justify-content:center; z-index:999; box-shadow:var(--shadow-lg); transition: opacity 0.3s;';
    document.body.appendChild(backTop);

    window.addEventListener('scroll', function () {
        backTop.style.display = window.scrollY > 400 ? 'flex' : 'none';
    });
    backTop.addEventListener('click', function () {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });

});

// ═══════════════════════════════════════════════════════════════
// Global Toast Notification Function
// ═══════════════════════════════════════════════════════════════
function showToast(message, type) {
    type = type || 'info';
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.style.cssText = 'position:fixed; bottom:80px; right:20px; z-index:9999; display:flex; flex-direction:column; gap:10px;';
        document.body.appendChild(container);
    }

    const colors = {
        success : { bg: '#10b981', text: 'white' },
        danger  : { bg: '#ef4444', text: 'white' },
        warning : { bg: '#f59e0b', text: 'white' },
        info    : { bg: '#2563eb', text: 'white' }
    };
    const color = colors[type] || colors.info;

    const toast = document.createElement('div');
    toast.style.cssText = `background:${color.bg}; color:${color.text}; padding:12px 18px; border-radius:8px; font-size:14px; font-weight:500; max-width:320px; box-shadow:0 4px 12px rgba(0,0,0,0.2); opacity:0; transform:translateX(40px); transition:all 0.3s ease;`;
    toast.textContent = message;
    container.appendChild(toast);

    requestAnimationFrame(function () {
        toast.style.opacity   = '1';
        toast.style.transform = 'translateX(0)';
    });

    setTimeout(function () {
        toast.style.opacity   = '0';
        toast.style.transform = 'translateX(40px)';
        setTimeout(function () { if (toast.parentNode) toast.parentNode.removeChild(toast); }, 300);
    }, 4000);
}
