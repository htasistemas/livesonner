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

define(['core/notification'], function(Notification) {
    const formatInterval = (seconds) => {
        seconds = Math.max(0, seconds);
        const days = Math.floor(seconds / 86400);
        const hours = Math.floor((seconds % 86400) / 3600);
        const minutes = Math.floor((seconds % 3600) / 60);

        const parts = [];
        if (days) {
            parts.push(days === 1 ? '1 dia' : `${days} dias`);
        }
        if (hours) {
            parts.push(hours === 1 ? '1 hora' : `${hours} horas`);
        }
        if (!days && !hours) {
            parts.push(minutes <= 1 ? '1 minuto' : `${minutes} minutos`);
        }

        return parts.join(' ');
    };

    const init = (startTime, selector) => {
        const element = document.querySelector(selector);
        if (!element) {
            return;
        }

        const update = () => {
            const diff = Math.floor(startTime - (Date.now() / 1000));
            if (diff <= 0) {
                element.textContent = '';
                element.classList.add('d-none');
                clearInterval(interval);
                return;
            }

            try {
                element.textContent = M.util.get_string('countdownmessage', 'mod_livesonner', formatInterval(diff));
                element.classList.remove('d-none');
            } catch (error) {
                Notification.exception(error);
            }
        };

        update();
        var interval = setInterval(update, 60000);
    };

    return {
        init: init,
    };
});
