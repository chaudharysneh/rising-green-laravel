(function () {
    const cardEl = document.getElementById('chatbotCard');
    if (!cardEl) return;

    const toggleBtn = document.getElementById('chatbotToggleBtn');
    const dismissBtn = document.getElementById('chatbotDismissBtn');
    const closeBtn = document.getElementById('chatbotCloseBtn');
    const sendBtn = document.getElementById('chatbotSendBtn');
    const inputEl = document.getElementById('chatbotInput');
    const messagesEl = document.getElementById('chatbotMessages');
    const promptButtons = document.querySelectorAll('.chatbot-prompt-btn');
    const inputWrapper = document.getElementById('chatbotInputWrapper');

    const menuData = {
        main: [
            { label: 'Leads', target: 'leads' },
            { label: 'Customers', target: 'customers' },
            { label: 'Followups', target: 'followups' },
            { label: 'Tasks', target: 'tasks' },
            { label: 'Projects', target: 'projects' },
            { label: 'Meetings', target: 'meetings' },
            { label: 'Deals', target: 'deals' },
            { label: 'More', target: 'more' }
        ],
        leads: [
            { label: 'Today', value: 'fetch_leads_today' },
            { label: 'This Week', value: 'fetch_leads_this_week' },
            { label: 'This Month', value: 'fetch_leads_this_month' },
            { label: 'All', action: 'show_index', url: '/leads', category: 'Leads' }
        ],
        customers: [
            { label: 'Today', value: 'fetch_customers_today' },
            { label: 'This Week', value: 'fetch_customers_this_week' },
            { label: 'This Month', value: 'fetch_customers_this_month' },
            { label: 'All', action: 'show_index', url: '/masters/customers', category: 'Customers' }
        ],
        followups: [
            { label: 'Today', value: 'fetch_followups_today' },
            { label: 'This Week', value: 'fetch_followups_this_week' },
            { label: 'This Month', value: 'fetch_followups_this_month' },
            { label: 'All', action: 'show_index', url: '/follow-ups', category: 'Followups' }
        ],
        tasks: [
            { label: 'Today', value: 'fetch_tasks_today' },
            { label: 'This Week', value: 'fetch_tasks_this_week' },
            { label: 'This Month', value: 'fetch_tasks_this_month' },
            { label: 'All', action: 'show_index', url: '/tasks', category: 'Tasks' }
        ],
        projects: [
            { label: 'Today', value: 'fetch_projects_today' },
            { label: 'This Week', value: 'fetch_projects_this_week' },
            { label: 'This Month', value: 'fetch_projects_this_month' },
            { label: 'All', action: 'show_index', url: '/projects', category: 'Projects' }
        ],
        meetings: [
            { label: 'Today', value: 'fetch_meetings_today' },
            { label: 'This Week', value: 'fetch_meetings_this_week' },
            { label: 'This Month', value: 'fetch_meetings_this_month' },
            { label: 'All', action: 'show_index', url: '/meetings', category: 'Meetings' }
        ],
        deals: [
            { label: 'Today', value: 'fetch_deals_today' },
            { label: 'This Week', value: 'fetch_deals_this_week' },
            { label: 'This Month', value: 'fetch_deals_this_month' },
            { label: 'All', action: 'show_index', url: '/deals', category: 'Deals' }
        ],
        more: [
            { label: 'Invoices', target: 'invoices' },
            { label: 'Tickets', target: 'tickets' },
            { label: 'Staff', target: 'staff' },
            { label: 'Products', target: 'products' },
            { label: 'Services', target: 'services' },
            { label: 'Pipeline', target: 'pipeline' }
        ],
        invoices: [
            { label: 'Today', value: 'fetch_invoices_today' },
            { label: 'This Week', value: 'fetch_invoices_this_week' },
            { label: 'This Month', value: 'fetch_invoices_this_month' },
            { label: 'All', action: 'show_index', url: '/invoices', category: 'Invoices' }
        ],
        tickets: [
            { label: 'Today', value: 'fetch_tickets_today' },
            { label: 'This Week', value: 'fetch_tickets_this_week' },
            { label: 'This Month', value: 'fetch_tickets_this_month' },
            { label: 'All', action: 'show_index', url: '/tickets', category: 'Tickets' }
        ],
        staff: [
            { label: 'Today', value: 'fetch_staff_today' },
            { label: 'This Week', value: 'fetch_staff_this_week' },
            { label: 'This Month', value: 'fetch_staff_this_month' },
            { label: 'All', action: 'show_index', url: '/users', category: 'Staff' }
        ],
        pipeline: [
            { label: 'Today', value: 'fetch_pipeline_today' },
            { label: 'This Week', value: 'fetch_pipeline_this_week' },
            { label: 'This Month', value: 'fetch_pipeline_this_month' },
            { label: 'All', action: 'show_index', url: '/pipeline', category: 'Pipeline' }
        ],
        products: [
            { label: 'Today', value: 'fetch_products_today' },
            { label: 'This Week', value: 'fetch_products_this_week' },
            { label: 'This Month', value: 'fetch_products_this_month' },
            { label: 'All', action: 'show_index', url: '/products', category: 'Products' }
        ],
        services: [
            { label: 'Today', value: 'fetch_services_today' },
            { label: 'This Week', value: 'fetch_services_this_week' },
            { label: 'This Month', value: 'fetch_services_this_month' },
            { label: 'All', action: 'show_index', url: '/services', category: 'Services' }
        ]
    };

    menuData.main.push({ label: 'Other', action: 'create_ticket', fullWidth: true });

    if (!toggleBtn || !closeBtn || !sendBtn || !inputEl || !messagesEl) return;

    let hasWelcomeMessage = false;
    let awaitingTicketMessage = false;

    function scrollToBottom() {
        messagesEl.scrollTop = messagesEl.scrollHeight;
    }

    function hideInput() {
        if (inputWrapper) {
            inputWrapper.classList.add('d-none');
        }
        if (inputEl) {
            inputEl.value = '';
        }
    }

    function showInput() {
        if (inputWrapper) {
            inputWrapper.classList.remove('d-none');
        }
        if (inputEl) {
            inputEl.focus();
        }
    }

    function createOptionButtons(options, isSubMenu = false, parentKey = 'main', currentKey = null) {
        const wrapper = document.createElement('div');
        wrapper.className = 'chatbot-options-wrapper';

        options.forEach(opt => {
            const btn = document.createElement('div');
            btn.className = 'chatbot-option-btn';
            btn.textContent = opt.label;
            if (options.length === 1 || opt.fullWidth) btn.classList.add('full-width');
            
            btn.onclick = () => {
                if (opt.action === 'create_ticket') {
                    appendMessage(opt.label, true);
                    showTicketPrompt();
                } else if (opt.action === 'show_index') {
                    appendMessage(opt.label, true);
                    showIndexLink(opt.category, opt.url);
                } else if (opt.target) {
                    appendMessage(opt.label, true);
                    showMenu(opt.target, currentKey || 'main');
                } else if (opt.value) {
                    appendMessage(opt.label, true);
                    sendRequest(opt.value);
                }
            };
            wrapper.appendChild(btn);
        });

        if (isSubMenu) {
            const backBtn = document.createElement('div');
            backBtn.className = 'chatbot-option-btn full-width back-btn';
            backBtn.textContent = 'Back';
            backBtn.onclick = () => showMenu(parentKey);
            wrapper.appendChild(backBtn);
        }

        return wrapper;
    }

    function showIndexLink(category, url) {
        const container = document.createElement('div');
        container.className = 'chatbot-message bot';
        container.innerHTML = `<div class="chatbot-avatar">Bot</div>`;

        const content = document.createElement('div');
        content.style.width = '100%';

        const wrapper = document.createElement('div');
        wrapper.className = 'chatbot-options-wrapper';

        const linkBtn = document.createElement('a');
        linkBtn.className = 'chatbot-option-btn full-width';
        linkBtn.textContent = 'View all ' + category;
        linkBtn.href = url;
        linkBtn.style.textDecoration = 'none';
        linkBtn.style.color = 'inherit';
        linkBtn.style.display = 'block';
        linkBtn.style.textAlign = 'center';

        wrapper.appendChild(linkBtn);
        content.appendChild(wrapper);
        container.appendChild(content);
        messagesEl.appendChild(container);
        scrollToBottom();

        renderBackButton(() => {
            removeBackButton();
            showMenu('main');
        });
    }

    function showMenu(menuKey, parentKey = 'main') {
        const options = menuData[menuKey];
        if (!options) return;

        const container = document.createElement('div');
        container.className = 'chatbot-message bot';
        container.innerHTML = `<div class="chatbot-avatar">Bot</div>`;
        
        const content = document.createElement('div');
        content.style.width = '100%';
        content.appendChild(createOptionButtons(options, menuKey !== 'main', parentKey, menuKey));
        
        container.appendChild(content);
        messagesEl.appendChild(container);
        scrollToBottom();
    }

    function removeBackButton() {
        const existing = messagesEl.querySelector('.chatbot-ticket-back-wrapper');
        if (existing) {
            existing.remove();
        }
    }

    function renderBackButton(onClick) {
        removeBackButton();

        const backBtnWrapper = document.createElement('div');
        backBtnWrapper.className = 'chatbot-ticket-back-wrapper';
        backBtnWrapper.style.width = '100%';
        backBtnWrapper.style.marginTop = '10px';

        const optionsWrapper = document.createElement('div');
        optionsWrapper.className = 'chatbot-options-wrapper';

        const backBtn = document.createElement('div');
        backBtn.className = 'chatbot-option-btn full-width back-btn';
        backBtn.textContent = 'Back';
        backBtn.onclick = onClick || (() => {
            removeBackButton();
            showMenu('main');
        });

        optionsWrapper.appendChild(backBtn);
        backBtnWrapper.appendChild(optionsWrapper);
        messagesEl.appendChild(backBtnWrapper);
        scrollToBottom();
    }

    async function sendRequest(val) {
        const loader = showBotTyping();
        const bot = await getBotResponseFromServer(val);
        loader.remove();
        appendMessage(bot.reply, false);
        if (bot.data && Array.isArray(bot.data)) renderData(bot.data);

        // Always add Back button after the response
        const backBtnWrapper = document.createElement('div');
        backBtnWrapper.style.width = '100%';
        const optionsWrapper = document.createElement('div');
        optionsWrapper.className = 'chatbot-options-wrapper';
        const backBtn = document.createElement('div');
        backBtn.className = 'chatbot-option-btn full-width back-btn';
        backBtn.textContent = 'Back';
        backBtn.onclick = () => showMenu('main');
        optionsWrapper.appendChild(backBtn);
        backBtnWrapper.appendChild(optionsWrapper);
        messagesEl.appendChild(backBtnWrapper);

        scrollToBottom();
    }

    function createMessageBubble(text, isUser) {
        const wrapper = document.createElement('div');
        wrapper.className = 'chatbot-message ' + (isUser ? 'user' : 'bot');

        const avatar = document.createElement('div');
        avatar.className = 'chatbot-avatar';
        avatar.textContent = isUser ? 'You' : 'Bot';

        const bubble = document.createElement('div');
        bubble.className = 'chatbot-bubble';
        bubble.textContent = text;

        if (isUser) {
            wrapper.appendChild(bubble);
            wrapper.appendChild(avatar);
        } else {
            wrapper.appendChild(avatar);
            wrapper.appendChild(bubble);
        }

        return wrapper;
    }

    function appendMessage(text, isUser) {
        const placeholder = messagesEl.querySelector('.chatbot-empty-state');
        if (placeholder) placeholder.remove();

        const message = createMessageBubble(text, isUser);
        messagesEl.appendChild(message);
        scrollToBottom();
    }

    // --- NEW: Loader bubble ---
    function showBotTyping() {
        const loader = document.createElement('div');
        loader.className = 'chatbot-message bot typing-bubble';
        loader.innerHTML = `
            <div class="chatbot-avatar">Bot</div>
            <div class="chatbot-bubble">...</div>
        `;
        messagesEl.appendChild(loader);
        scrollToBottom();
        return loader;
    }

    // --- UPDATED: Send message to Laravel AI assistant ---   
    async function getBotResponseFromServer(userText, options = {}) {
        try {
            const payload = { message: userText, ...options };
            const response = await fetch("/crm-assistant", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify(payload)
            });

            return await response.json();
        } catch (error) {
            return { reply: "⚠ Server error — please try again.", data: null };
        }
    }

    async function handleSend() {
        const text = inputEl.value.trim();
        if (!text) {
            inputEl.focus();
            return;
        }

        const wasTicketMessage = awaitingTicketMessage;
        appendMessage(text, true);
        inputEl.value = '';
        inputEl.focus();

        const loader = showBotTyping();

        let bot;
        if (awaitingTicketMessage) {
            bot = await createTicket(text);
            awaitingTicketMessage = false;
        } else {
            bot = await getBotResponseFromServer(text);
        }

        loader.remove();

        appendMessage(bot.reply, false);

        if (bot.data) {
            renderData(bot.data);
        }

        if (wasTicketMessage) {
            hideInput();
            renderBackButton(() => {
                awaitingTicketMessage = false;
                hideInput();
                removeBackButton();
                showMenu('main');
            });
        } else {
            renderBackButton(() => {
                removeBackButton();
                showMenu('main');
            });
        }

        scrollToBottom();
    }

    function renderData(data) {
        if (!Array.isArray(data)) {
            // Fallback for non-array data
            const formatted = document.createElement('pre');
            formatted.className = "chatbot-data";
            formatted.textContent = JSON.stringify(data, null, 2);
            messagesEl.appendChild(formatted);
            return;
        }

        if (data.length === 0) {
            appendMessage("I couldn't find any matching records.", false);
            return;
        }

        const container = document.createElement('div');
        container.className = 'chatbot-message bot';
        container.innerHTML = `<div class="chatbot-avatar">Bot</div>`;

        const content = document.createElement('div');
        content.style.width = '100%';
        content.innerHTML = renderRecordList(data);
        
        container.appendChild(content);
        messagesEl.appendChild(container);
    }

    function renderRecordList(records) {
        let html = `<div class="chatbot-records-list">`;
        records.forEach(record => {
            const fields = Array.isArray(record.fields) ? record.fields : [];
            const fieldPriority = { Phone: 1, Mobile: 1, 'Created by': 2, Address: 3 };
            const orderedFields = [...fields].sort((a, b) => (fieldPriority[a.label] || 10) - (fieldPriority[b.label] || 10));
            const price = record.price !== null && record.price !== undefined && record.price !== ''
                ? new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR', maximumFractionDigits: 2 }).format(Number(record.price))
                : null;
            html += `
                <div class="chatbot-record-card">
                    <div class="chatbot-record-header">
                        <span class="chatbot-record-icon"><i class="bi ${getRecordIcon(record.type)}"></i></span>
                        <div class="chatbot-record-title"><strong>${escapeHtml(record.name || 'Record')}</strong><small>#${escapeHtml(record.id || '--')}</small></div>
                        <span class="badge ${getStatusBadgeClass(record.status)}">${escapeHtml(record.status || 'Not set')}</span>
                    </div>
                    <div class="chatbot-record-details">
                        ${orderedFields.map(field => `<div class="chatbot-record-field ${field.label === 'Address' ? 'chatbot-record-field-full' : ''}"><span>${escapeHtml(field.label)}</span><strong>${escapeHtml(field.value)}</strong></div>`).join('')}
                        ${price ? `<div class="chatbot-record-field"><span>Price</span><strong>${escapeHtml(price)}</strong></div>` : ''}
                    </div>
                    ${record.url && record.url !== '#' ? `<a href="${escapeHtml(record.url)}" class="chatbot-view-link">View details <i class="bi bi-arrow-up-right"></i></a>` : ''}
                </div>
            `;
        });
        html += `</div>`;
        return html;
    }

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>'"]/g, char => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' }[char]));
    }

    function getRecordIcon(type) {
        return ({ leads: 'bi-megaphone', customers: 'bi-people', followups: 'bi-person-check', tasks: 'bi-list-check',
            projects: 'bi-kanban', meetings: 'bi-calendar-event', deals: 'bi-handshake', invoices: 'bi-receipt',
            tickets: 'bi-ticket-perforated', products: 'bi-box-seam', services: 'bi-tools', staff: 'bi-person-badge',
            pipeline: 'bi-diagram-3' })[type] || 'bi-file-earmark-text';
    }

    function getStatusBadgeClass(status) {
        if (!status) return 'bg-secondary';
        const s = status.toLowerCase();
        if (s.includes('active') || s.includes('won') || s.includes('success') || s.includes('won')) return 'bg-success text-white';
        if (s.includes('pending') || s.includes('progress') || s.includes('medium')) return 'bg-warning text-dark';
        if (s.includes('inactive') || s.includes('lost') || s.includes('close') || s.includes('danger')) return 'bg-danger text-white';
        return 'bg-secondary text-white';
    }

    function showTicketPrompt() {
        awaitingTicketMessage = true;
        showInput();
        appendMessage('Please type the message you want to save as a ticket. I will create it with priority Medium and status In Progress.', false);
        renderBackButton(() => {
            awaitingTicketMessage = false;
            hideInput();
            removeBackButton();
            showMenu('main');
        });
    }

    function showWelcomeMessage() {
        if (hasWelcomeMessage) return;

        appendMessage(
            'Hi! I’m your Bot. Ask me about customers, leads, tickets, reports, or perform updates like “update lead 12 to won”.',
            false
        );

        hasWelcomeMessage = true;
    }

    function openChatbot() {
        // Clear previous messages and restore empty state
        messagesEl.innerHTML = `
            <div class="chatbot-empty-state pt-0">
                👋 Hi there!<br>
                Ask me anything about <strong>leads, customers, tickets, reports,</strong> or CRM settings.
            </div>
        `;
        hasWelcomeMessage = false;
        awaitingTicketMessage = false;

        cardEl.classList.add('open');
        cardEl.removeAttribute('aria-hidden');
        showMenu('main');
        hideInput();
        removeBackButton();
    }

    function closeChatbot() {
        cardEl.classList.remove('open');
        cardEl.setAttribute('aria-hidden', 'true');
        hideInput();
        removeBackButton();
    }

    function dismissChatbot() {
        closeChatbot();
        document.body.classList.add('chatbot-is-dismissed');
    }

    function toggleChatbot() {
        if (cardEl.classList.contains('open')) {
            closeChatbot();
        } else {
            openChatbot();
        }
    }

    async function createTicket(message) {
        return await getBotResponseFromServer(message, { create_ticket: true });
    }

    toggleBtn.addEventListener('click', toggleChatbot);
    if (dismissBtn) {
        dismissBtn.addEventListener('click', dismissChatbot);
    }
    closeBtn.addEventListener('click', closeChatbot);

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && cardEl.classList.contains('open')) {
            closeChatbot();
        }
    });

    sendBtn.addEventListener('click', handleSend);

    function submitQuickPrompt(promptText) {
        inputEl.value = promptText;
        handleSend();
    }

    if (promptButtons.length) {
        promptButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                submitQuickPrompt(button.dataset.prompt);
            });
        });
    }

    inputEl.addEventListener('keydown', function (event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            handleSend();
        }
    });
})();
