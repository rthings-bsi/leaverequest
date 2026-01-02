// Simple Echo listeners to help debug realtime broadcasts.
// This file logs incoming events and updates minimal DOM elements if present.

if (typeof window !== 'undefined' && window.Echo) {
    // Global approvals channel
    window.Echo.channel('approvals')
        .listen('.LeaveCreated', (e) => {
            console.info('[Echo] LeaveCreated', e);
        })
        .listen('.LeaveUpdated', (e) => {
            console.info('[Echo] LeaveUpdated', e);
            // If there is a global dashboard stats element, update simple counts
            try {
                if (e.total !== undefined) document.querySelectorAll('.js-total-count').forEach(el => el.textContent = e.total);
                if (e.pending !== undefined) document.querySelectorAll('.js-pending-count').forEach(el => el.textContent = e.pending);
                if (e.accepted !== undefined) document.querySelectorAll('.js-accepted-count').forEach(el => el.textContent = e.accepted);
                if (e.rejected !== undefined) document.querySelectorAll('.js-rejected-count').forEach(el => el.textContent = e.rejected);
            } catch (err) {
                // ignore
            }
        })
        .listen('.ApprovalCreated', (e) => {
            console.info('[Echo] ApprovalCreated', e);
        });

    // Private leave channel: require server to render window.__LEAVE_ID__ on detail pages
    const subscribeToPrivate = (leaveId) => {
        try {
            window.Echo.private(`leave.${leaveId}`)
                .listen('.LeaveUpdated', (e) => {
                    console.info('[Echo][private] LeaveUpdated', e);
                    // Update any timestamp elements on the page if they exist (data-leave-ts or data-field)
                    try {
                        const fields = ['supervisor_approved_at','manager_approved_at','hr_notified_at','created_at','updated_at'];
                        fields.forEach(f => {
                            const sel = document.querySelector(`[data-leave-ts="${f}"], [data-field="${f}"]`);
                            if (sel && e.leave && e.leave[f]) sel.textContent = e.leave[f];
                        });
                    } catch (err) {
                        // ignore
                    }
                })
                .listen('.ApprovalCreated', (e) => {
                    console.info('[Echo][private] ApprovalCreated', e);
                });
        } catch (err) {
            console.info('[Echo][private] failed to subscribe', err);
        }
    };

    const leaveId = window.__LEAVE_ID__ || null;
    if (leaveId) {
        subscribeToPrivate(leaveId);
    } else {
        // If the page sets the leave id later (inline script), poll briefly and subscribe when available
        let attempts = 0;
        const maxAttempts = 12; // 12 * 500ms = 6s
        const interval = setInterval(() => {
            attempts++;
            if (window.__LEAVE_ID__) {
                subscribeToPrivate(window.__LEAVE_ID__);
                clearInterval(interval);
            }
            if (attempts >= maxAttempts) clearInterval(interval);
        }, 500);
    }
} else {
    console.info('Echo not available - install laravel-echo and pusher-js and rebuild assets.');
}
