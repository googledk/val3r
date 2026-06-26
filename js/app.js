document.addEventListener('click', function (e) {
    const btn = e.target.closest('[data-menu-toggle]');
    if (btn) {
        const menu = document.querySelector('[data-menu]');
        if (menu) menu.classList.toggle('open');
    }

    const thumb = e.target.closest('[data-thumb]');
    if (thumb) {
        const main = document.querySelector('.main-product-image');
        const modalImg = document.querySelector('[data-zoom-modal] img');
        if (main) main.src = thumb.src;
        if (modalImg) modalImg.src = thumb.src;
    }

    if (e.target.closest('[data-open-zoom]')) {
        const modal = document.querySelector('[data-zoom-modal]');
        if (modal) modal.classList.add('open');
    }

    if (e.target.closest('[data-close-zoom]') || e.target.matches('[data-zoom-modal]')) {
        const modal = document.querySelector('[data-zoom-modal]');
        if (modal) modal.classList.remove('open');
    }
});

const slides = document.querySelectorAll('.slide');
let slideIndex = 0;
if (slides.length > 1) {
    setInterval(() => {
        slides[slideIndex].classList.remove('active');
        slideIndex = (slideIndex + 1) % slides.length;
        slides[slideIndex].classList.add('active');
    }, 4500);
}

const header = document.querySelector('[data-header]');
window.addEventListener('scroll', () => {
    if (!header) return;
    header.classList.toggle('scrolled', window.scrollY > 20);
});






// v2.6 Cart API client
(function () {
    function updateHeaderCart(count) {
        const headerCart = document.querySelector('.header-cart span');
        if (headerCart) headerCart.textContent = count;
    }

    function updateCartUi(data) {
        if (!data || !data.ok) return;

        document.querySelectorAll('[data-cart-count]').forEach(el => el.textContent = data.cart_count);
        document.querySelectorAll('[data-summary-count]').forEach(el => el.textContent = data.cart_count);
        document.querySelectorAll('[data-cart-total]').forEach(el => el.textContent = data.total);
        updateHeaderCart(data.cart_count);

        Object.keys(data.rows || {}).forEach(productId => {
            const rowTotal = document.querySelector(`[data-row-total="${productId}"]`);
            if (rowTotal) rowTotal.textContent = data.rows[productId].row_total;
        });

        document.querySelectorAll('[data-cart-row]').forEach(row => {
            const productId = row.getAttribute('data-cart-row');
            if (!data.rows || !data.rows[productId]) {
                row.remove();
            }
        });

        if (data.is_empty) {
            const area = document.querySelector('[data-cart-area]');
            if (area) {
                area.outerHTML = `
                    <div class="empty-box premium-empty-cart">
                        <strong>سبد خرید شما خالی است.</strong>
                        <p>برای شروع خرید، محصولات VaL3R را مشاهده کنید.</p>
                        <a class="btn btn-dark" href="?page=products">مشاهده محصولات</a>
                    </div>
                `;
            }
        }
    }

    const form = document.querySelector('[data-auto-cart-form]');
    let timer = null;
    let controller = null;

    function setSaving(isSaving) {
        if (form) form.classList.toggle('is-saving', isSaving);
    }

    function sendCartUpdate() {
        if (!form) return;

        if (controller) controller.abort();
        controller = new AbortController();

        setSaving(true);

        fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            signal: controller.signal,
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        })
            .then(response => response.json())
            .then(updateCartUi)
            .catch(error => {
                if (error.name !== 'AbortError') console.error('Cart update failed:', error);
            })
            .finally(() => setSaving(false));
    }

    function submitCartSoon() {
        clearTimeout(timer);
        timer = setTimeout(sendCartUpdate, 450);
    }

    document.addEventListener('click', function (e) {
        const minus = e.target.closest('[data-qty-minus]');
        const plus = e.target.closest('[data-qty-plus]');
        const remove = e.target.closest('[data-cart-remove]');

        if (remove) {
            e.preventDefault();
            if (!confirm('این محصول از سبد حذف شود؟')) return;

            fetch(remove.href, {
                headers: {'X-Requested-With': 'XMLHttpRequest'}
            })
                .then(response => response.json())
                .then(updateCartUi)
                .catch(error => console.error('Cart remove failed:', error));
            return;
        }

        if (!minus && !plus) return;

        const wrap = e.target.closest('.qty-control');
        const input = wrap ? wrap.querySelector('[data-auto-qty]') : null;
        if (!input) return;

        let val = parseInt(input.value || '0', 10);
        const min = parseInt(input.min || '0', 10);
        const max = parseInt(input.max || '999', 10);

        if (plus) val = Math.min(max, val + 1);
        if (minus) val = Math.max(min, val - 1);

        input.value = val;
        submitCartSoon();
    });

    document.addEventListener('input', function (e) {
        if (e.target.matches('[data-auto-qty]')) submitCartSoon();
    });
})();

// v2.6 Product page AJAX add-to-cart
(function () {
    function updateHeaderCart(count) {
        const headerCart = document.querySelector('.header-cart span');
        if (headerCart) headerCart.textContent = count;
    }

    function sendProductCart(form) {
        const btn = form.querySelector('[data-product-add-btn]');
        const qtyBox = form.querySelector('[data-product-qty-box]');
        const setQty = form.querySelector('[data-set-qty]');

        if (btn) {
            btn.disabled = true;
            btn.classList.add('is-loading');
        }

        fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        })
            .then(res => res.json())
            .then(data => {
                if (!data || !data.ok) return;

                updateHeaderCart(data.cart_count);

                if (qtyBox) qtyBox.hidden = false;

                const removeBtn = form.querySelector('[data-product-remove-btn]');
                if (removeBtn) removeBtn.hidden = false;

                if (setQty) setQty.value = '1';

                if (btn) {
                    btn.textContent = 'ادامه خرید';
                    btn.dataset.added = '1';
                }
            })
            .catch(err => console.error('Product cart update failed:', err))
            .finally(() => {
                if (btn) {
                    btn.disabled = false;
                    btn.classList.remove('is-loading');
                }
            });
    }

    document.addEventListener('submit', function (e) {
        const form = e.target.closest('[data-product-cart-form]');
        if (!form) return;

        e.preventDefault();

        const btn = form.querySelector('[data-product-add-btn]');
        if (btn && btn.dataset.added === '1') {
            window.location.href = form.dataset.cartUrl || '?page=cart';
            return;
        }

        sendProductCart(form);
    });

    document.addEventListener('click', function (e) {
        const minus = e.target.closest('[data-product-qty-minus]');
        const plus = e.target.closest('[data-product-qty-plus]');
        if (!minus && !plus) return;

        const form = e.target.closest('[data-product-cart-form]');
        const input = form ? form.querySelector('[data-product-qty]') : null;
        if (!input) return;

        let val = parseInt(input.value || '1', 10);
        const min = parseInt(input.min || '1', 10);
        const max = parseInt(input.max || '999', 10);

        if (plus) val = Math.min(max, val + 1);
        if (minus) val = Math.max(min, val - 1);

        input.value = val;

        const setQty = form.querySelector('[data-set-qty]');
        if (setQty) setQty.value = '1';

        sendProductCart(form);
    });

    document.addEventListener('change', function (e) {
        if (!e.target.matches('[data-product-qty]')) return;

        const form = e.target.closest('[data-product-cart-form]');
        if (!form) return;

        const setQty = form.querySelector('[data-set-qty]');
        if (setQty) setQty.value = '1';

        sendProductCart(form);
    });
})();


// v2.8 Product page remove-from-cart button
(function () {
    document.addEventListener('click', function (e) {
        const removeBtn = e.target.closest('[data-product-remove-btn]');
        if (!removeBtn) return;

        const form = removeBtn.closest('[data-product-cart-form]');
        if (!form) return;

        const productIdInput = form.querySelector('input[name="product_id"]');
        const qtyInput = form.querySelector('[data-product-qty]');
        const qtyBox = form.querySelector('[data-product-qty-box]');
        const setQty = form.querySelector('[data-set-qty]');
        const addBtn = form.querySelector('[data-product-add-btn]');

        const productId = productIdInput ? productIdInput.value : '';
        if (!productId) return;

        removeBtn.disabled = true;

        fetch(`ajax/cart.php?action=remove&id=${encodeURIComponent(productId)}`, {
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        })
            .then(res => res.json())
            .then(data => {
                if (!data || !data.ok) return;

                const headerCart = document.querySelector('.header-cart span');
                if (headerCart) headerCart.textContent = data.cart_count;

                if (qtyInput) qtyInput.value = 1;
                if (qtyBox) qtyBox.hidden = true;
                if (setQty) setQty.value = '0';

                removeBtn.hidden = true;

                if (addBtn) {
                    addBtn.textContent = 'افزودن به سبد خرید';
                    delete addBtn.dataset.added;
                }
            })
            .catch(err => console.error('Product remove failed:', err))
            .finally(() => {
                removeBtn.disabled = false;
            });
    });
})();


// v2.9 AJAX products category filter
(function () {
    const grid = document.querySelector('[data-products-grid]');
    const searchForm = document.querySelector('[data-products-search]');
    const filterLinks = document.querySelectorAll('[data-category-filter]');

    if (!grid || !filterLinks.length) return;

    let currentCategory = new URLSearchParams(window.location.search).get('category') || '0';
    let currentQuery = new URLSearchParams(window.location.search).get('q') || '';

    function setLoading(isLoading) {
        grid.classList.toggle('is-loading-products', isLoading);
    }

    function setActive(category) {
        filterLinks.forEach(link => {
            link.classList.toggle('active', String(link.dataset.categoryFilter) === String(category));
        });
    }

    function updateUrl(category, q) {
        const url = new URL(window.location.href);
        url.searchParams.set('page', 'products');

        if (category && String(category) !== '0') {
            url.searchParams.set('category', category);
        } else {
            url.searchParams.delete('category');
        }

        if (q) {
            url.searchParams.set('q', q);
        } else {
            url.searchParams.delete('q');
        }

        window.history.pushState({}, '', url.toString());
    }

    function loadProducts(category, q, pushUrl = true) {
        currentCategory = String(category || '0');
        currentQuery = q || '';

        setActive(currentCategory);
        setLoading(true);

        const params = new URLSearchParams();
        params.set('category', currentCategory);
        if (currentQuery) params.set('q', currentQuery);

        fetch(`ajax/products.php?${params.toString()}`, {
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        })
            .then(res => res.json())
            .then(data => {
                if (!data || !data.ok) return;
                grid.innerHTML = data.html;
                if (pushUrl) updateUrl(currentCategory, currentQuery);
            })
            .catch(err => console.error('Products load failed:', err))
            .finally(() => setLoading(false));
    }

    filterLinks.forEach(link => {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            const category = this.dataset.categoryFilter || '0';
            const qInput = searchForm ? searchForm.querySelector('input[name="q"]') : null;
            const q = qInput ? qInput.value.trim() : currentQuery;
            loadProducts(category, q);
        });
    });

    if (searchForm) {
        searchForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const qInput = searchForm.querySelector('input[name="q"]');
            loadProducts(currentCategory, qInput ? qInput.value.trim() : '');
        });
    }

    window.addEventListener('popstate', function () {
        const params = new URLSearchParams(window.location.search);
        const category = params.get('category') || '0';
        const q = params.get('q') || '';
        const qInput = searchForm ? searchForm.querySelector('input[name="q"]') : null;
        if (qInput) qInput.value = q;
        loadProducts(category, q, false);
    });
})();


// v3.1 user panel AJAX navigation
(function () {
    const content = document.querySelector('[data-account-content]');
    const menu = document.querySelector('[data-account-menu]');
    if (!content || !menu) return;

    function setActive(section) {
        menu.querySelectorAll('[data-account-link]').forEach(link => {
            link.classList.toggle('active', link.dataset.section === section);
        });
    }

    function setLoading(isLoading) {
        content.classList.toggle('is-account-loading', isLoading);
    }

    function updateUrl(section) {
        const url = new URL(window.location.href);
        url.searchParams.set('page', 'account');
        if (section && section !== 'dashboard') {
            url.searchParams.set('section', section);
        } else {
            url.searchParams.delete('section');
        }
        window.history.pushState({}, '', url.toString());
    }

    function loadSection(section, pushUrl = true) {
        setActive(section);
        setLoading(true);

        fetch(`ajax/account.php?section=${encodeURIComponent(section)}`, {
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        })
            .then(res => res.json())
            .then(data => {
                if (!data || !data.ok) {
                    if (data && data.redirect) window.location.href = data.redirect;
                    return;
                }
                content.innerHTML = data.html;
                if (pushUrl) updateUrl(section);
            })
            .catch(err => {
                console.error('Account section load failed:', err);
                content.innerHTML = '<div class="alert error">بارگذاری پنل انجام نشد.</div>';
            })
            .finally(() => setLoading(false));
    }

    document.addEventListener('click', function (e) {
        const link = e.target.closest('[data-account-link]');
        if (!link) return;

        e.preventDefault();
        loadSection(link.dataset.section || 'dashboard');
    });

    document.addEventListener('submit', function (e) {
        const form = e.target.closest('[data-account-profile-form]');
        if (!form) return;

        e.preventDefault();
        setLoading(true);

        fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        })
            .then(res => res.json())
            .then(data => {
                if (!data || !data.ok) {
                    if (data && data.redirect) window.location.href = data.redirect;
                    content.insertAdjacentHTML('afterbegin', '<div class="alert error">ذخیره اطلاعات انجام نشد.</div>');
                    return;
                }

                content.innerHTML = data.html;

                const toast = document.createElement('div');
                toast.className = 'save-toast';
                toast.textContent = 'اطلاعات با موفقیت ذخیره شد';
                document.body.appendChild(toast);

                setTimeout(() => toast.classList.add('show'), 30);
                setTimeout(() => {
                    toast.classList.remove('show');
                    setTimeout(() => toast.remove(), 300);
                }, 2600);
            })
            .catch(() => {
                content.insertAdjacentHTML('afterbegin', '<div class="alert error">ارتباط با سرور برقرار نشد.</div>');
            })
            .finally(() => setLoading(false));
    });

    window.addEventListener('popstate', function () {
        const params = new URLSearchParams(window.location.search);
        loadSection(params.get('section') || 'dashboard', false);
    });

    loadSection(content.dataset.initialSection || 'dashboard', false);
})();


// v3.5 contact form ajax
(function () {
    const form = document.querySelector('[data-contact-form]');
    if (!form) return;

    const result = form.querySelector('[data-contact-result]');
    const btn = form.querySelector('button[type="submit"]');

    form.addEventListener('submit', function (e) {
        e.preventDefault();

        if (btn) {
            btn.disabled = true;
            btn.textContent = 'در حال ارسال...';
        }

        if (result) {
            result.hidden = true;
            result.className = 'contact-result';
            result.textContent = '';
        }

        fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        })
            .then(res => res.json())
            .then(data => {
                if (!result) return;

                result.hidden = false;
                result.classList.add(data.ok ? 'success' : 'error');
                result.innerHTML = data.ok
                    ? '<strong>✓</strong><span>' + data.message + '</span>'
                    : '<strong>!</strong><span>' + data.message + '</span>';

                if (data.ok) {
                    setTimeout(() => form.reset(), 400);
                }
            })
            .catch(() => {
                if (result) {
                    result.hidden = false;
                    result.classList.add('error');
                    result.innerHTML = '<strong>!</strong><span>ارتباط با سرور برقرار نشد.</span>';
                }
            })
            .finally(() => {
                if (btn) {
                    btn.disabled = false;
                    btn.textContent = 'ارسال پیام';
                }
            });
    });
})();


// v5 UX layer
document.addEventListener('click', function(e){
    const btn = e.target.closest('[data-menu-toggle]');
    if(btn){
        const menu = document.querySelector('[data-menu]');
        if(menu) menu.classList.toggle('open');
    }
});

(function(){
    const topBtn = document.querySelector('[data-back-to-top]');
    if(!topBtn) return;
    const toggle = () => topBtn.classList.toggle('show', window.scrollY > 420);
    window.addEventListener('scroll', toggle, {passive:true});
    toggle();
    topBtn.addEventListener('click', () => window.scrollTo({top:0, behavior:'smooth'}));
})();

document.addEventListener('submit', function(e){
    const form = e.target.closest('[data-quick-add]');
    if(!form) return;
    e.preventDefault();
    const btn = form.querySelector('button');
    const old = btn ? btn.textContent : '';
    if(btn){ btn.disabled = true; btn.textContent = 'افزوده شد'; }
    fetch(form.action, {
        method:'POST',
        body:new FormData(form),
        headers:{'X-Requested-With':'XMLHttpRequest'}
    }).then(r=>r.json()).then(data=>{
        const count = document.querySelector('.v5-cart-link b');
        if(count && data && data.cart_count !== undefined) count.textContent = data.cart_count;
    }).finally(()=>{
        setTimeout(()=>{ if(btn){btn.disabled=false;btn.textContent=old;} }, 900);
    });
});


// v6 SMS.ir OTP auth flow
(function(){
    const page = document.querySelector('[data-auth-page]');
    if(!page) return;

    const sendForm = page.querySelector('[data-send-otp-form]');
    const verifyForm = page.querySelector('[data-verify-otp-form]');
    const messageBox = page.querySelector('[data-auth-message]');
    const subtitle = page.querySelector('[data-auth-subtitle]');
    const mobileHidden = page.querySelector('[data-otp-mobile]');
    const mobilePreview = page.querySelector('[data-mobile-preview]');
    const resendBtn = page.querySelector('[data-resend-otp]');
    const timerEl = page.querySelector('[data-otp-timer]');
    const changeMobileBtn = page.querySelector('[data-change-mobile]');
    const otpInputs = [...page.querySelectorAll('[data-otp-boxes] input')];

    let currentMobile = '';
    let timerId = null;
    let remain = 60;

    function showMessage(text, type='success'){
        if(!messageBox) return;
        messageBox.hidden = false;
        messageBox.textContent = text;
        messageBox.className = 'v6-auth-message ' + type;
    }

    function clearMessage(){
        if(!messageBox) return;
        messageBox.hidden = true;
        messageBox.textContent = '';
    }

    function setButtonLoading(btn, loading, text){
        if(!btn) return;
        if(loading){
            btn.dataset.oldText = btn.textContent;
            btn.textContent = text || 'در حال انجام...';
            btn.disabled = true;
        }else{
            btn.textContent = btn.dataset.oldText || btn.textContent;
            btn.disabled = false;
        }
    }

    function startTimer(seconds){
        remain = seconds || 60;
        if(timerId) clearInterval(timerId);
        if(resendBtn) resendBtn.disabled = true;

        const tick = () => {
            if(timerEl) timerEl.textContent = remain;
            if(remain <= 0){
                clearInterval(timerId);
                if(resendBtn) resendBtn.disabled = false;
                if(timerEl) timerEl.textContent = '0';
                return;
            }
            remain--;
        };

        tick();
        timerId = setInterval(tick, 1000);
    }

    function showVerify(mobile, seconds){
        currentMobile = mobile;
        if(mobileHidden) mobileHidden.value = mobile;
        if(mobilePreview) mobilePreview.textContent = mobile;
        sendForm.hidden = true;
        verifyForm.hidden = false;
        if(subtitle) subtitle.textContent = 'کد تایید پیامک شده را وارد کنید.';
        otpInputs.forEach(i => i.value = '');
        setTimeout(() => otpInputs[0]?.focus(), 100);
        startTimer(seconds || 60);
    }

    function showSend(){
        verifyForm.hidden = true;
        sendForm.hidden = false;
        if(subtitle) subtitle.textContent = 'شماره موبایل خود را وارد کنید تا کد تایید برایتان ارسال شود.';
        clearMessage();
        if(timerId) clearInterval(timerId);
    }

    async function sendOtp(mobile, btn){
        clearMessage();
        setButtonLoading(btn, true, 'در حال ارسال...');
        try{
            const fd = new FormData();
            fd.append('mobile', mobile);

            const res = await fetch('ajax/send_otp.php', {
                method:'POST',
                body:fd,
                headers:{'X-Requested-With':'XMLHttpRequest'}
            });
            const data = await res.json();

            if(!data.ok){
                showMessage(data.message || 'ارسال کد انجام نشد.', 'error');
                return;
            }

            showMessage(data.message || 'کد تایید ارسال شد.', 'success');
            showVerify(data.mobile || mobile, data.remain || 60);

            if(data.debug_code){
                showMessage('کد تست: ' + data.debug_code, 'success');
            }
        }catch(e){
            showMessage('ارتباط با سرور برقرار نشد.', 'error');
        }finally{
            setButtonLoading(btn, false);
        }
    }

    async function verifyOtp(code, btn){
        clearMessage();
        setButtonLoading(btn, true, 'در حال بررسی...');
        try{
            const fd = new FormData();
            fd.append('mobile', currentMobile || mobileHidden.value);
            fd.append('code', code);

            const res = await fetch('ajax/verify_otp.php', {
                method:'POST',
                body:fd,
                headers:{'X-Requested-With':'XMLHttpRequest'}
            });
            const data = await res.json();

            if(!data.ok){
                showMessage(data.message || 'کد تایید درست نیست.', 'error');
                return;
            }

            showMessage(data.message || 'ورود موفق بود.', 'success');
            setTimeout(() => window.location.href = data.redirect || '?page=account', 450);
        }catch(e){
            showMessage('ارتباط با سرور برقرار نشد.', 'error');
        }finally{
            setButtonLoading(btn, false);
        }
    }

    sendForm.addEventListener('submit', function(e){
        e.preventDefault();
        const mobile = (sendForm.querySelector('input[name="mobile"]').value || '').trim();
        sendOtp(mobile, sendForm.querySelector('button'));
    });

    verifyForm.addEventListener('submit', function(e){
        e.preventDefault();
        const code = otpInputs.map(i => i.value).join('');
        verifyOtp(code, verifyForm.querySelector('button[type="submit"]'));
    });

    resendBtn?.addEventListener('click', function(){
        if(currentMobile) sendOtp(currentMobile, resendBtn);
    });

    changeMobileBtn?.addEventListener('click', showSend);

    otpInputs.forEach((input, index) => {
        input.addEventListener('input', function(){
            this.value = this.value.replace(/\D/g,'').slice(0,1);
            if(this.value && otpInputs[index+1]) otpInputs[index+1].focus();

            const code = otpInputs.map(i => i.value).join('');
            if(code.length === 6) verifyOtp(code, verifyForm.querySelector('button[type="submit"]'));
        });

        input.addEventListener('keydown', function(e){
            if(e.key === 'Backspace' && !this.value && otpInputs[index-1]){
                otpInputs[index-1].focus();
            }
        });

        input.addEventListener('paste', function(e){
            e.preventDefault();
            const pasted = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g,'').slice(0,6);
            if(!pasted) return;
            otpInputs.forEach((i, idx) => i.value = pasted[idx] || '');
            const target = otpInputs[Math.min(pasted.length, 5)];
            target?.focus();
            if(pasted.length === 6) verifyOtp(pasted, verifyForm.querySelector('button[type="submit"]'));
        });
    });
})();

// v8.4 Premium home slider
(function(){const slider=document.querySelector('[data-v84-slider]');if(!slider)return;const slides=[...slider.querySelectorAll('[data-v84-slide]')],dots=[...slider.querySelectorAll('[data-v84-dot]')],prev=slider.querySelector('[data-v84-prev]'),next=slider.querySelector('[data-v84-next]'),progress=slider.querySelector('[data-v84-progress]');if(!slides.length)return;let index=0,timer=null;const delay=5500;function prog(){if(!progress)return;progress.classList.remove('is-running');void progress.offsetWidth;progress.classList.add('is-running')}function go(to){slides[index].classList.remove('active');if(dots[index])dots[index].classList.remove('active');index=(to+slides.length)%slides.length;slides[index].classList.add('active');if(dots[index])dots[index].classList.add('active');prog()}function stop(){if(timer)clearInterval(timer);timer=null}function start(){stop();prog();if(slides.length>1)timer=setInterval(()=>go(index+1),delay)}if(next)next.addEventListener('click',()=>{go(index+1);start()});if(prev)prev.addEventListener('click',()=>{go(index-1);start()});dots.forEach(dot=>dot.addEventListener('click',()=>{go(Number(dot.dataset.v84Dot||0));start()}));slider.addEventListener('mouseenter',stop);slider.addEventListener('mouseleave',start);let x=null;slider.addEventListener('touchstart',e=>{x=e.touches[0].clientX},{passive:true});slider.addEventListener('touchend',e=>{if(x===null)return;const d=e.changedTouches[0].clientX-x;if(Math.abs(d)>45){d>0?go(index-1):go(index+1);start()}x=null},{passive:true});start()})();
