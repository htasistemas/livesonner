// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

define(['core/ajax', 'core/notification'], function(Ajax, Notification) {
    const SELECTORS = {
        panel: type => `.aulasaovivo__panel[data-panel="${type}"]`,
        cards: '[data-region="cards"]',
        agenda: '[data-region="agenda"]',
        refresh: '[data-action="refresh"]',
        carousel: '[data-region="carousel"]',
        nav: '[data-action="scroll"]',
        feedback: '[data-region="feedback"]',
        notice: '[data-region="fallback-notice"]'
    };

    const countdownRegistry = new Map();
    let toastTimeout = null;
    let state = {
        root: null,
        config: null,
        fallback: false,
        formatters: {}
    };

    /**
     * Normalises locale strings into a BCP 47 compatible format.
     *
     * @param {String} locale
     * @returns {String}
     */
    const normaliseLocale = locale => {
        if (!locale) {
            return 'pt-BR';
        }

        const segments = locale.replace(/_/g, '-').split('-').filter(Boolean);
        if (segments.length === 0) {
            return 'pt-BR';
        }

        const [language, region, ...rest] = segments;
        const parts = [language.toLowerCase()];

        if (region) {
            parts.push(region.toUpperCase());
        }

        if (rest.length) {
            parts.push(...rest);
        }

        return parts.join('-');
    };

    const COUNTDOWN_INTERVAL = setInterval(() => {
        countdownRegistry.forEach(updateCountdown);
    }, 1000);

    /**
     * Initialises the dashboard.
     *
     * @param {Object} config
     */
    const getConfigFromRoot = root => {
        const raw = root.dataset.config;
        if (!raw) {
            return null;
        }

        try {
            return JSON.parse(raw);
        } catch (error) {
            Notification.exception(error);
            return null;
        }
    };

    const init = rootid => {
        if (!rootid) {
            return;
        }

        const root = document.getElementById(rootid);
        if (!root) {
            return;
        }

        const config = getConfigFromRoot(root);
        if (!config) {
            return;
        }

        state = {
            root,
            config,
            fallback: false,
            formatters: buildFormatters(config)
        };

        setupNavigation();
        setupRefresh();
        refreshPanels();
    };

    /**
     * Creates Intl formatters for dates and times.
     *
     * @param {Object} config
     * @returns {Object}
     */
    const buildFormatters = config => {
        const locale = normaliseLocale(config.locale);
        const timezone = config.timezone && config.timezone !== '99' ? config.timezone : undefined;
        return {
            date: new Intl.DateTimeFormat(locale, { day: '2-digit', month: '2-digit', year: 'numeric', timeZone: timezone }),
            shortDate: new Intl.DateTimeFormat(locale, { day: '2-digit', month: '2-digit', timeZone: timezone }),
            time: new Intl.DateTimeFormat(locale, { hour: '2-digit', minute: '2-digit', timeZone: timezone })
        };
    };

    /**
     * Calculates the amount of pixels required to move to the next card.
     *
     * @param {HTMLElement} list
     * @returns {Number}
     */
    const getScrollAmount = list => {
        if (!list) {
            return 0;
        }

        const card = list.querySelector('.aulasaovivo__card');
        if (!card) {
            return list.clientWidth;
        }

        const style = window.getComputedStyle(list);
        const gap = parseFloat(style.columnGap || style.gap || '0') || 0;
        return card.getBoundingClientRect().width + gap;
    };

    /**
     * Sets up navigation controls for carousels.
     */
    const setupNavigation = () => {
        state.root.querySelectorAll(SELECTORS.carousel).forEach(container => {
            const list = container.querySelector(SELECTORS.cards);
            if (!list) {
                return;
            }

            const previous = container.querySelector('.aulasaovivo__nav--prev');
            const next = container.querySelector('.aulasaovivo__nav--next');

            const updateButtons = () => {
                const maxScroll = Math.max(0, list.scrollWidth - list.clientWidth);
                const tolerance = 4;
                const hasOverflow = maxScroll > tolerance;

                if (previous) {
                    previous.hidden = !hasOverflow || list.scrollLeft <= tolerance;
                }

                if (next) {
                    next.hidden = !hasOverflow || list.scrollLeft >= (maxScroll - tolerance);
                }
            };

            const scrollToCard = direction => {
                const amount = getScrollAmount(list);
                if (!amount) {
                    return;
                }

                const maxScroll = Math.max(0, list.scrollWidth - list.clientWidth);
                if (maxScroll <= 0) {
                    return;
                }

                const currentIndex = Math.round(list.scrollLeft / amount);
                const maxIndex = Math.ceil(maxScroll / amount);
                const targetIndex = Math.max(0, Math.min(currentIndex + direction, maxIndex));
                const target = targetIndex * amount;

                list.scrollTo({ left: target, behavior: 'smooth' });
            };

            if (previous) {
                previous.addEventListener('click', () => scrollToCard(-1));
            }

            if (next) {
                next.addEventListener('click', () => scrollToCard(1));
            }

            list.addEventListener('scroll', () => window.requestAnimationFrame(updateButtons));
            window.addEventListener('resize', () => window.requestAnimationFrame(updateButtons));

            container._aulasaovivoUpdateNav = updateButtons;
            container._aulasaovivoScrollToStart = () => {
                list.scrollTo({ left: 0, behavior: 'auto' });
                updateButtons();
            };

            updateButtons();
        });
    };

    /**
     * Resets scroll position and navigation state for a carousel.
     *
     * @param {HTMLElement} cards
     */
    const updateCarouselState = cards => {
        if (!cards) {
            return;
        }

        const container = cards.closest(SELECTORS.carousel);
        if (!container) {
            return;
        }

        if (typeof container._aulasaovivoScrollToStart === 'function') {
            container._aulasaovivoScrollToStart();
        } else if (typeof container._aulasaovivoUpdateNav === 'function') {
            container._aulasaovivoUpdateNav();
        }
    };

    /**
     * Sets up refresh buttons.
     */
    const setupRefresh = () => {
        state.root.querySelectorAll(SELECTORS.refresh).forEach(button => {
            button.addEventListener('click', () => {
                const target = button.dataset.target;
                button.disabled = true;
                refreshPanels(target)
                    .catch(Notification.exception)
                    .finally(() => {
                        window.setTimeout(() => {
                            button.disabled = false;
                        }, 400);
                    });
            });
        });
    };

    /**
     * Refreshes the requested panels.
     *
     * @param {String} [target]
     * @returns {Promise}
     */
    const refreshPanels = target => {
        const panels = target ? [target] : ['catalog', 'enrolled'];
        if (!target) {
            state.fallback = false;
        }

        panels.forEach(type => setLoading(type, true));

        const requests = panels.map(type => loadPanel(type));

        return Promise.allSettled(requests)
            .then(results => {
                results.forEach(result => {
                    if (result.status === 'rejected') {
                        Notification.exception(result.reason);
                    }
                });
            })
            .finally(() => {
                panels.forEach(type => setLoading(type, false));
                updateFallbackNotice();
            });
    };

    /**
     * Marks a panel as loading.
     *
     * @param {String} type
     * @param {Boolean} isLoading
     */
    const setLoading = (type, isLoading) => {
        const panel = findPanel(type);
        if (!panel) {
            return;
        }
        if (isLoading) {
            panel.setAttribute('data-loading', 'true');
        } else {
            panel.removeAttribute('data-loading');
        }
    };

    /**
     * Loads data for a panel via AJAX.
     *
     * @param {String} type
     * @returns {Promise}
     */
    const loadPanel = type => {
        const method = type === 'catalog' ? state.config.services.catalog : state.config.services.enrolled;
        const [request] = Ajax.call([{ methodname: method, args: {} }]);

        return request.then(response => {
            if (response.usingfallback) {
                state.fallback = true;
            }
            const sessions = normaliseSessions(response.sessions);
            renderPanel(type, sessions);
            return response;
        }).catch(error => {
            renderPanel(type, []);
            throw error;
        });
    };

    /**
     * Ensures the sessions payload is an array.
     *
     * @param {Array|Object} data
     * @returns {Array}
     */
    const normaliseSessions = data => {
        if (Array.isArray(data)) {
            return data;
        }
        if (!data || typeof data !== 'object') {
            return [];
        }
        return Object.values(data);
    };

    /**
     * Finds a panel element.
     *
     * @param {String} type
     * @returns {HTMLElement|null}
     */
    const findPanel = type => state.root.querySelector(SELECTORS.panel(type));

    /**
     * Renders sessions within a panel.
     *
     * @param {String} type
     * @param {Array} sessions
     */
    const renderPanel = (type, sessions) => {
        const panel = findPanel(type);
        if (!panel) {
            return;
        }

        const cards = panel.querySelector(SELECTORS.cards);
        const agenda = panel.querySelector(SELECTORS.agenda);
        if (!cards || !agenda) {
            return;
        }

        resetCountdowns(type);
        cards.innerHTML = '';
        agenda.innerHTML = '';
        cards.scrollLeft = 0;

        const sorted = sessions.slice().sort((a, b) => (a.starttime || 0) - (b.starttime || 0));

        if (!sorted.length) {
            const empty = document.createElement('div');
            empty.className = 'aulasaovivo__empty';
            empty.textContent = type === 'catalog' ? state.config.strings.emptycatalog : state.config.strings.emptyenrolled;
            cards.appendChild(empty);
            updateCarouselState(cards);
            return;
        }

        sorted.forEach(session => {
            const entry = createCardEntry(session, type);
            cards.appendChild(entry.element);

            const agendaItem = createAgendaItem(session);
            entry.agendaItem = agendaItem;
            agenda.appendChild(agendaItem);

            registerCountdown(entry);
        });

        updateCarouselState(cards);
    };

    /**
     * Clears countdown registry for a panel.
     *
     * @param {String} type
     */
    const resetCountdowns = type => {
        Array.from(countdownRegistry.entries()).forEach(([element, entry]) => {
            if (entry.panel === type) {
                countdownRegistry.delete(element);
            }
        });
    };

    /**
     * Creates a card entry for a session.
     *
     * @param {Object} session
     * @param {String} panelType
     * @returns {Object}
     */
    const createCardEntry = (session, panelType) => {
        const article = document.createElement('article');
        article.className = 'aulasaovivo__card';
        article.dataset.panel = panelType;
        article.dataset.sessionId = session.id;

        const imageWrapper = document.createElement('div');
        imageWrapper.className = 'aulasaovivo__card-image';

        if (session.imageurl) {
            const img = document.createElement('img');
            img.src = session.imageurl;
            img.alt = session.name || '';
            imageWrapper.appendChild(img);
        }

        const overlay = document.createElement('div');
        overlay.className = 'aulasaovivo__card-overlay';
        imageWrapper.appendChild(overlay);

        const countdown = document.createElement('div');
        countdown.className = 'aulasaovivo__card-countdown';
        imageWrapper.appendChild(countdown);

        const info = document.createElement('div');
        info.className = 'aulasaovivo__card-info';

        const title = document.createElement('h3');
        title.className = 'aulasaovivo__card-title';
        title.textContent = session.name;
        info.appendChild(title);

        const meta = document.createElement('div');
        meta.className = 'aulasaovivo__card-meta';
        appendMeta(meta, state.config.strings.startslabel, buildDateTime(session));
        appendMeta(meta, state.config.strings.locationlabel, session.location);
        appendMeta(meta, state.config.strings.instructorlabel, session.instructor && session.instructor.name);
        info.appendChild(meta);

        if (Array.isArray(session.tags) && session.tags.length) {
            const tagsContainer = document.createElement('div');
            tagsContainer.className = 'aulasaovivo__card-meta';
            session.tags.forEach(tag => {
                const chip = document.createElement('span');
                chip.className = 'aulasaovivo__chip';
                chip.textContent = tag;
                tagsContainer.appendChild(chip);
            });
            info.appendChild(tagsContainer);
        }

        if (session.summary) {
            const description = document.createElement('div');
            description.className = 'aulasaovivo__card-description';
            description.innerHTML = session.summary;
            info.appendChild(description);
        }

        const footer = document.createElement('div');
        footer.className = 'aulasaovivo__card-footer';

        if (session.isenrolled && panelType === 'catalog') {
            const badge = document.createElement('span');
            badge.className = 'aulasaovivo__badge';
            badge.textContent = state.config.strings.enrolledbadge;
            footer.appendChild(badge);
        }

        if (panelType === 'enrolled') {
            const badge = document.createElement('span');
            badge.className = 'aulasaovivo__badge';
            badge.textContent = state.config.strings.confirmedbadge;
            footer.appendChild(badge);
        }

        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'aulasaovivo__button';
        footer.appendChild(button);

        info.appendChild(footer);

        article.appendChild(imageWrapper);
        article.appendChild(info);

        const entry = {
            session,
            panel: panelType,
            element: article,
            button,
            countdown,
            agendaItem: null,
            state: 'upcoming'
        };

        button.addEventListener('click', () => handleCardAction(entry));

        return entry;
    };

    /**
     * Adds a meta line to the card.
     *
     * @param {HTMLElement} container
     * @param {String} label
     * @param {String} value
     */
    const appendMeta = (container, label, value) => {
        if (!value) {
            return;
        }
        const item = document.createElement('span');
        item.className = 'aulasaovivo__card-meta-item';
        item.textContent = `${label}: ${value}`;
        container.appendChild(item);
    };

    /**
     * Creates an agenda item element.
     *
     * @param {Object} session
     * @returns {HTMLElement}
     */
    const createAgendaItem = session => {
        const item = document.createElement('div');
        item.className = 'aulasaovivo__agenda-item';

        const time = document.createElement('div');
        time.className = 'aulasaovivo__agenda-time';
        time.textContent = buildAgendaTime(session);

        const name = document.createElement('div');
        name.className = 'aulasaovivo__agenda-name';
        name.textContent = session.name;

        const status = document.createElement('div');
        status.className = 'aulasaovivo__agenda-status';

        item.appendChild(time);
        item.appendChild(name);
        item.appendChild(status);

        return item;
    };

    /**
     * Registers a countdown entry.
     *
     * @param {Object} entry
     */
    const registerCountdown = entry => {
        updateCountdown(entry);
        countdownRegistry.set(entry.element, entry);
    };

    /**
     * Updates countdown state for an entry.
     *
     * @param {Object} entry
     */
    const updateCountdown = entry => {
        const now = Date.now();
        const start = (entry.session.starttime || 0) * 1000;
        const duration = (entry.session.duration || 0) * 1000;
        const fallbackEnd = start && duration ? start + duration : start + 3600000;
        const end = (entry.session.endtime || 0) ? entry.session.endtime * 1000 : fallbackEnd;

        let stateLabel = state.config.strings.agendaunconfirmed;
        let entryState = 'past';

        if (!start || now < start) {
            entryState = 'upcoming';
            stateLabel = state.config.strings.agendaunconfirmed;
            const diff = (start || now) - now;
            entry.countdown.innerHTML = `<span class="aulasaovivo__card-countdown-label">${state.config.strings.countdownlabel}</span>${formatDuration(diff)}`;
        } else if (now >= start && now <= end) {
            entryState = 'live';
            stateLabel = state.config.strings.agendalive;
            const remaining = Math.max(0, end - now);
            entry.countdown.innerHTML = `<span class="aulasaovivo__card-countdown-label">${state.config.strings.countdownlive}</span>${formatDuration(remaining)}`;
        } else {
            entryState = 'past';
            stateLabel = state.config.strings.agendapast;
            entry.countdown.innerHTML = `<span class="aulasaovivo__card-countdown-label">${state.config.strings.countdownfinished}</span>${buildDate(start)}`;
        }

        entry.state = entryState;
        entry.element.dataset.state = entryState;

        if (entry.agendaItem) {
            entry.agendaItem.dataset.state = entryState;
            const status = entry.agendaItem.querySelector('.aulasaovivo__agenda-status');
            if (status) {
                status.textContent = stateLabel;
            }
        }

        updateButton(entry);
    };

    /**
     * Formats a duration.
     *
     * @param {Number} milliseconds
     * @returns {String}
     */
    const formatDuration = milliseconds => {
        let total = Math.max(0, Math.floor(milliseconds / 1000));
        const days = Math.floor(total / 86400);
        total -= days * 86400;
        const hours = Math.floor(total / 3600);
        total -= hours * 3600;
        const minutes = Math.floor(total / 60);

        const parts = [];
        if (days) {
            parts.push(`${days}d`);
        }
        if (hours || days) {
            parts.push(`${hours.toString().padStart(2, '0')}h`);
        }
        parts.push(`${minutes.toString().padStart(2, '0')}m`);
        return parts.join(' ');
    };

    /**
     * Builds a formatted date string.
     *
     * @param {Number} timestamp
     * @returns {String}
     */
    const buildDate = timestamp => {
        if (!timestamp) {
            return '';
        }
        const date = new Date(timestamp);
        return state.formatters.shortDate.format(date);
    };

    /**
     * Builds formatted date/time text for cards.
     *
     * @param {Object} session
     * @returns {String}
     */
    const buildDateTime = session => {
        if (!session.starttime) {
            return '';
        }
        const start = new Date(session.starttime * 1000);
        const date = state.formatters.date.format(start);
        const time = state.formatters.time.format(start);
        return `${date} • ${time}`;
    };

    /**
     * Builds agenda timestamp string.
     *
     * @param {Object} session
     * @returns {String}
     */
    const buildAgendaTime = session => {
        if (!session.starttime) {
            return '';
        }
        const start = new Date(session.starttime * 1000);
        const date = state.formatters.shortDate.format(start);
        const time = state.formatters.time.format(start);
        return `${date} • ${time}`;
    };

    /**
     * Updates the primary button state based on context.
     *
     * @param {Object} entry
     */
    const updateButton = entry => {
        if (!entry.button) {
            return;
        }
        const session = entry.session;
        const stateLabel = entry.state;
        const strings = state.config.strings;

        if (entry.panel === 'catalog') {
            if (!session.isenrolled) {
                if (stateLabel === 'past') {
                    applyButtonState(entry, strings.sessionclosed, 'secondary', true);
                } else {
                    applyButtonState(entry, strings.enrolsession, 'primary', false);
                }
                return;
            }

            if (stateLabel === 'live') {
                const hasUrl = Boolean(session.launchurl);
                applyButtonState(entry, strings.accesssession, hasUrl ? 'success' : 'secondary', !hasUrl);
            } else if (stateLabel === 'past') {
                applyButtonState(entry, strings.sessionclosed, 'secondary', true);
            } else {
                applyButtonState(entry, strings.enrolledbadge, 'secondary', true);
            }
            return;
        }

        if (stateLabel === 'live') {
            const hasUrl = Boolean(session.launchurl);
            applyButtonState(entry, strings.accesssession, hasUrl ? 'success' : 'secondary', !hasUrl);
        } else if (stateLabel === 'upcoming') {
            applyButtonState(entry, strings.accesssession, 'secondary', true);
        } else {
            applyButtonState(entry, strings.seemore, 'secondary', true);
        }
    };

    /**
     * Applies button state.
     *
     * @param {Object} entry
     * @param {String} text
     * @param {String} variant
     * @param {Boolean} disabled
     */
    const applyButtonState = (entry, text, variant, disabled) => {
        entry.button.textContent = text;
        entry.button.dataset.variant = variant;
        entry.button.disabled = disabled;
        entry.button.setAttribute('aria-disabled', disabled ? 'true' : 'false');
    };

    /**
     * Handles card button actions.
     *
     * @param {Object} entry
     */
    const handleCardAction = entry => {
        if (!entry.button || entry.button.disabled) {
            return;
        }

        if (entry.panel === 'catalog') {
            if (!entry.session.isenrolled && entry.state !== 'past') {
                enrolSession(entry);
                return;
            }
            if (entry.session.isenrolled && entry.session.launchurl && entry.state === 'live') {
                openSession(entry.session.launchurl);
            }
        } else if (entry.panel === 'enrolled' && entry.session.launchurl && entry.state === 'live') {
            openSession(entry.session.launchurl);
        }
    };

    /**
     * Opens a session link.
     *
     * @param {String} url
     */
    const openSession = url => {
        window.open(url, '_blank', 'noopener');
    };

    /**
     * Calls the enrolment endpoint and reloads the dashboard.
     *
     * @param {Object} entry
     */
    const enrolSession = entry => {
        const [request] = Ajax.call([
            {
                methodname: state.config.services.enrol,
                args: { sessionid: entry.session.id }
            }
        ]);

        applyButtonState(entry, state.config.strings.processing, 'secondary', true);

        request.then(response => {
            if (response.usingfallback) {
                state.fallback = true;
            }

            if (response.status) {
                showToast(state.config.strings.enrolsuccess);
                entry.session.isenrolled = true;
                return refreshPanels();
            }

            const message = response.message || state.config.strings.enrolfailure;
            showToast(message);
            entry.session.isenrolled = false;
            refreshPanels('catalog');
        }).catch(error => {
            Notification.exception(error);
            showToast(state.config.strings.enrolfailure);
            refreshPanels('catalog');
        });
    };

    /**
     * Updates the fallback notice visibility.
     */
    const updateFallbackNotice = () => {
        const notice = state.root.querySelector(SELECTORS.notice);
        if (!notice) {
            return;
        }
        if (state.fallback) {
            notice.hidden = false;
        } else {
            notice.hidden = true;
        }
    };

    /**
     * Displays a toast message.
     *
     * @param {String} message
     */
    const showToast = message => {
        const feedback = state.root.querySelector(SELECTORS.feedback);
        if (!feedback) {
            return;
        }
        feedback.textContent = message || state.config.strings.toastdefault;
        feedback.dataset.visible = 'true';
        clearTimeout(toastTimeout);
        toastTimeout = window.setTimeout(() => {
            feedback.dataset.visible = 'false';
        }, 4000);
    };

    return {
        init
    };
});
