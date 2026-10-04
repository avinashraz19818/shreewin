/**
 * Advanced Dynamic Admin AJAX Handlers & Chart Integrations
 */
$(document).ready(function() {
    // Globals
    const csrfToken = $('meta[name="csrf-token"]').attr('content');

    // Setup global AJAX headers
    $.ajaxSetup({
        headers: {
            'X-CSRF-Token': csrfToken
        }
    });

    // Mobile Sidebar Toggle Handlers
    function openMobileSidebar() {
        $('#app-sidebar').addClass('mobile-open');
        $('#sidebar-backdrop').addClass('show');
        $('body').css('overflow', 'hidden');
    }
    function closeMobileSidebar() {
        $('#app-sidebar').removeClass('mobile-open');
        $('#sidebar-backdrop').removeClass('show');
        $('body').css('overflow', '');
    }

    $(document).on('click', '#sidebar-toggle-btn', function(e) {
        e.preventDefault();
        openMobileSidebar();
    });

    $(document).on('click', '#sidebar-close-btn, #sidebar-backdrop', function(e) {
        e.preventDefault();
        closeMobileSidebar();
    });

    // Auto-close on link click on mobile
    $(document).on('click', '#app-sidebar a:not(.sidebar-dropdown-toggle)', function() {
        if ($(window).width() <= 992) {
            closeMobileSidebar();
        }
    });

    // Helper: Show Toast
    window.showToast = function(message, type = 'info') {
        const bgClass = type === 'success' ? 'bg-success' : (type === 'danger' ? 'bg-danger' : 'bg-primary');
        const toastHTML = `
            <div class="toast toast-premium align-items-center text-white ${bgClass} border-0" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        `;
        const $container = $('#toast-container');
        if ($container.length === 0) {
            $('body').append('<div id="toast-container" class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1050;"></div>');
        }
        $('#toast-container').append(toastHTML);
        const $toastEl = $('#toast-container .toast').last();
        const toast = new bootstrap.Toast($toastEl[0], { delay: 4000 });
        toast.show();
        $toastEl.on('hidden.bs.toast', function() {
            $(this).remove();
        });
    };

    // Load Dashboard Stats & Charts
    if ($('#dashboard-view').length > 0) {
        loadDashboardStats();
        loadDashboardCharts();
    }

    function loadDashboardStats() {
        $.getJSON('api.php', { action: 'get_stats' }, function(res) {
            if (res.success) {
                const s = res.data;
                $('#stat-total-users').text(s.users.total);
                $('#stat-today-users').html('<i class="fas fa-user-plus"></i> +' + s.users.today + ' today');
                
                $('#stat-user-wallet').text('₹' + s.user_wallet.total.toFixed(2));
                
                $('#stat-today-recharges').text('₹' + s.recharge.today.toFixed(2));
                $('[id="stat-total-recharges"]').html('<i class="fas fa-arrow-circle-down"></i> Total: ₹' + s.recharge.total.toFixed(2));
                
                $('#stat-today-withdrawals').text('₹' + s.withdraw.today.toFixed(2));
                $('[id="stat-total-withdrawals"]').html('<i class="fas fa-arrow-circle-up"></i> Total: ₹' + s.withdraw.total.toFixed(2));
                
                $('#stat-pending-recharges').text('₹' + s.recharge.pending.toFixed(2));
                $('#stat-pending-recharges-count').html('<i class="fas fa-clock"></i> ' + s.recharge.pending_count + ' requests pending');
                
                $('#stat-today-profit').text('₹' + s.profit.today.toFixed(2));
            }
        });
    }

    function loadDashboardCharts() {
        $.getJSON('api.php', { action: 'get_charts', days: 7 }, function(res) {
            if (res.success) {
                const data = res.data;
                
                // 1. User Growth Chart
                const ctxUsers = document.getElementById('chart-user-growth').getContext('2d');
                new Chart(ctxUsers, {
                    type: 'line',
                    data: {
                        labels: data.labels,
                        datasets: [{
                            label: 'New Registrations',
                            data: data.registrations,
                            borderColor: '#3b82f6',
                            backgroundColor: 'rgba(59, 130, 246, 0.15)',
                            tension: 0.4,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: { legend: { display: false } },
                        scales: { y: { beginAtZero: true } }
                    }
                });

                // 2. Transaction Volumes Chart
                const ctxFinance = document.getElementById('chart-finance').getContext('2d');
                new Chart(ctxFinance, {
                    type: 'bar',
                    data: {
                        labels: data.labels,
                        datasets: [
                            {
                                label: 'Deposits',
                                data: data.recharges,
                                backgroundColor: '#10b981'
                            },
                            {
                                label: 'Withdrawals',
                                data: data.withdrawals,
                                backgroundColor: '#ef4444'
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        scales: { y: { beginAtZero: true } }
                    }
                });
            }
        });
    }

    // Initialize Users DataTable
    if ($('#users-table').length > 0) {
        const usersTable = $('#users-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: 'api.php',
                data: function(d) {
                    d.action = 'list_users';
                }
            },
            columns: [
                { data: 'user_id' },
                { data: 'username' },
                { data: 'nickname' },
                { data: 'phone' },
                { data: 'wallet_balance', render: d => '₹' + d.toFixed(2) },
                { data: 'game_balance', render: d => '₹' + d.toFixed(2) },
                { 
                    data: 'can_bet',
                    render: function(d, t, row) {
                        const checked = d === 1 ? 'checked' : '';
                        return `<div class="form-check form-switch"><input class="form-check-input toggle-bet-switch" type="checkbox" data-id="${row.id}" ${checked}></div>`;
                    }
                },
                {
                    data: 'id',
                    render: function(d, t, row) {
                        const banBtn = row.can_bet === 1
                            ? `<button class="btn btn-sm btn-outline-danger ban-user-btn" data-id="${row.id}" data-username="${row.username}">Ban</button>`
                            : `<button class="btn btn-sm btn-outline-success unban-user-btn" data-id="${row.id}" data-username="${row.username}">Unban</button>`;
                        return `
                            <button class="btn btn-sm btn-outline-info adjust-balance-btn" data-id="${row.user_id}" data-nickname="${row.nickname}">Adjust Balance</button>
                            <button class="btn btn-sm btn-outline-warning control-user-btn" data-id="${row.user_id}" data-rate="${row.win_rate_percent}" data-status="${row.control_status}">Target Control</button>
                            ${banBtn}
                        `;
                    }
                }
            ]
        });

        // Toggle Bet switch handler
        $('#users-table').on('change', '.toggle-bet-switch', function() {
            const id = $(this).data('id');
            const canBet = $(this).is(':checked') ? 1 : 0;
            // Let's call save_user endpoint
            $.post('api.php', { action: 'save_user', id: id, can_bet: canBet }, function(res) {
                if (res.success) {
                    showToast('Betting permission updated', 'success');
                } else {
                    showToast(res.message || 'Error updating status', 'danger');
                }
            });
        });

        // Adjust Balance dialog launch
        $('#users-table').on('click', '.adjust-balance-btn', function() {
            const userId = $(this).data('id');
            const nickname = $(this).data('nickname');
            $('#adjust-balance-user-id').val(userId);
            $('#adjust-balance-username').text(nickname);
            const modal = new bootstrap.Modal(document.getElementById('modal-adjust-balance'));
            modal.show();
        });

        // Adjust Balance Submit
        $('#btn-submit-adjustment').click(function() {
            const form = $('#form-adjust-balance');
            $.post('api.php', form.serialize(), function(res) {
                if (res.success) {
                    showToast(res.message, 'success');
                    bootstrap.Modal.getInstance(document.getElementById('modal-adjust-balance')).hide();
                    usersTable.ajax.reload(null, false);
                } else {
                    showToast(res.message || 'Error adjusting balance', 'danger');
                }
            });
        });

        // Target Control launch
        $('#users-table').on('click', '.control-user-btn', function() {
            const userId = $(this).data('id');
            const rate = $(this).data('rate');
            const status = $(this).data('status');
            
            $('#control-user-id').val(userId);
            $('#control-win-rate').val(rate);
            $('#control-status').val(status);
            
            const modal = new bootstrap.Modal(document.getElementById('modal-target-control'));
            modal.show();
        });

        // Target Control Submit
        $('#btn-submit-control').click(function() {
            const form = $('#form-target-control');
            $.post('api.php', form.serialize(), function(res) {
                if (res.success) {
                    showToast(res.message, 'success');
                    bootstrap.Modal.getInstance(document.getElementById('modal-target-control')).hide();
                    usersTable.ajax.reload(null, false);
                } else {
                    showToast(res.message || 'Error updating target control', 'danger');
                }
            });
        });

        // Ban / Unban from the member directory
        $('#users-table').on('click', '.ban-user-btn', function() {
            const id = $(this).data('id');
            const username = $(this).data('username');
            const reason = prompt('Reason for banning ' + username + '?', 'Blocked by admin');
            if (reason === null) return;
            $.post('api.php', { action: 'set_user_ban', id: id, ban: 1, reason: reason }, function(res) {
                showToast(res.message || (res.success ? 'User banned' : 'Could not ban user'), res.success ? 'success' : 'danger');
                if (res.success) { usersTable.ajax.reload(null, false); }
            });
        });

        $('#users-table').on('click', '.unban-user-btn', function() {
            const id = $(this).data('id');
            $.post('api.php', { action: 'set_user_ban', id: id, ban: 0 }, function(res) {
                showToast(res.message || (res.success ? 'User unbanned' : 'Could not unban user'), res.success ? 'success' : 'danger');
                if (res.success) { usersTable.ajax.reload(null, false); }
            });
        });
    }

    // Initialize Recharges DataTable
    if ($('#recharges-table').length > 0) {
        const rechargesTable = $('#recharges-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: 'api.php',
                data: function(d) {
                    d.action = 'list_recharges';
                    d.status = $('#recharge-status-filter').val();
                }
            },
            columns: [
                { 
                    data: 'id',
                    render: function(d) {
                        return `<input type="checkbox" class="recharge-select-row" value="${d}">`;
                    }
                },
                { data: 'order_no' },
                { data: 'player_id' },
                { data: 'username' },
                { data: 'amount', render: d => '₹' + d.toFixed(2) },
                { data: 'payment_type' },
                { 
                    data: 'status',
                    render: function(d) {
                        const style = d === 'Approved' ? 'bg-success' : (d === 'Pending' ? 'bg-warning' : 'bg-danger');
                        return `<span class="badge ${style}">${d}</span>`;
                    }
                },
                { data: 'utr' },
                { 
                    data: 'screenshot_url',
                    render: function(d, t, row) {
                        if (d) {
                            return `<a href="#" class="view-screenshot" data-url="${d}">View</a>`;
                        }
                        return 'None';
                    }
                },
                {
                    data: 'id',
                    render: function(d, t, row) {
                        if (row.status === 'Pending' || row.status === 'PendingReview') {
                            return `
                                <button class="btn btn-sm btn-success approve-recharge-btn" data-id="${d}">Approve</button>
                                <button class="btn btn-sm btn-danger reject-recharge-btn" data-id="${d}">Reject</button>
                            `;
                        }
                        return 'Settled';
                    }
                }
            ]
        });

        // Filter recharges
        $('#recharge-status-filter').change(function() {
            rechargesTable.ajax.reload();
        });

        // Approve recharge
        $('#recharges-table').on('click', '.approve-recharge-btn', function() {
            const id = $(this).data('id');
            if (confirm('Approve this recharge deposit?')) {
                $.post('api.php', { action: 'update_recharge', id: id, status: 'Approved' }, function(res) {
                    if (res.success) {
                        showToast(res.message, 'success');
                        rechargesTable.ajax.reload(null, false);
                    } else {
                        showToast(res.message, 'danger');
                    }
                });
            }
        });

        // Reject recharge
        $('#recharges-table').on('click', '.reject-recharge-btn', function() {
            const id = $(this).data('id');
            const reason = prompt('Enter reason for rejection:');
            if (reason !== null) {
                $.post('api.php', { action: 'update_recharge', id: id, status: 'Rejected', remarks: reason }, function(res) {
                    if (res.success) {
                        showToast(res.message, 'success');
                        rechargesTable.ajax.reload(null, false);
                    } else {
                        showToast(res.message, 'danger');
                    }
                });
            }
        });

        // Bulk approval
        $('#btn-bulk-approve-recharge').click(function() {
            const selected = [];
            $('.recharge-select-row:checked').each(function() {
                selected.push($(this).val());
            });
            if (selected.length === 0) {
                showToast('Please select at least one order', 'danger');
                return;
            }
            if (confirm(`Approve ${selected.length} selected orders?`)) {
                $.post('api.php', { action: 'bulk_approve_recharges', ids: selected }, function(res) {
                    if (res.success) {
                        showToast(res.message, 'success');
                        rechargesTable.ajax.reload(null, false);
                    } else {
                        showToast(res.message, 'danger');
                    }
                });
            }
        });

        // View screenshot overlay
        $('#recharges-table').on('click', '.view-screenshot', function(e) {
            e.preventDefault();
            const url = $(this).data('url');
            $('#screenshot-image').attr('src', url);
            const modal = new bootstrap.Modal(document.getElementById('modal-screenshot'));
            modal.show();
        });
    }

    // Initialize Withdrawals DataTable
    if ($('#withdrawals-table').length > 0) {
        const withdrawalsTable = $('#withdrawals-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: 'api.php',
                data: function(d) {
                    d.action = 'list_withdrawals';
                    d.status = $('#withdrawal-status-filter').val();
                }
            },
            columns: [
                { data: 'order_no' },
                { data: 'player_id' },
                { data: 'username' },
                { data: 'amount', render: d => '₹' + d.toFixed(2) },
                { data: 'payment_type' },
                { 
                    data: 'status',
                    render: function(d) {
                        const style = d === 'Approved' ? 'bg-success' : (d === 'Pending' ? 'bg-warning' : 'bg-danger');
                        return `<span class="badge ${style}">${d}</span>`;
                    }
                },
                { 
                    data: 'account_json', 
                    render: function(d) {
                        try {
                            const acc = JSON.parse(d);
                            return acc.accountNo || acc.upiId || d;
                        } catch (e) {
                            return d;
                        }
                    }
                },
                { data: 'remarks' },
                {
                    data: 'id',
                    render: function(d, t, row) {
                        if (row.status === 'Pending') {
                            return `
                                <button class="btn btn-sm btn-success approve-withdraw-btn" data-id="${d}">Approve</button>
                                <button class="btn btn-sm btn-danger reject-withdraw-btn" data-id="${d}">Reject</button>
                            `;
                        }
                        return 'Settled';
                    }
                }
            ]
        });

        // Filter withdrawals
        $('#withdrawal-status-filter').change(function() {
            withdrawalsTable.ajax.reload();
        });

        // Approve withdraw
        $('#withdrawals-table').on('click', '.approve-withdraw-btn', function() {
            const id = $(this).data('id');
            if (confirm('Approve this withdrawal payouts?')) {
                $.post('api.php', { action: 'update_withdrawal', id: id, status: 'Approved' }, function(res) {
                    if (res.success) {
                        showToast(res.message, 'success');
                        withdrawalsTable.ajax.reload(null, false);
                    } else {
                        showToast(res.message, 'danger');
                    }
                });
            }
        });

        // Reject withdraw
        $('#withdrawals-table').on('click', '.reject-withdraw-btn', function() {
            const id = $(this).data('id');
            const reason = prompt('Enter rejection reason:');
            if (reason !== null) {
                $.post('api.php', { action: 'update_withdrawal', id: id, status: 'Rejected', remarks: reason }, function(res) {
                    if (res.success) {
                        showToast(res.message, 'success');
                        withdrawalsTable.ajax.reload(null, false);
                    } else {
                        showToast(res.message, 'danger');
                    }
                });
            }
        });
    }

    // Support Ticket reply system
    if ($('#support-view').length > 0) {
        const supportTable = $('#support-tickets-table').DataTable({
            ajax: {
                url: 'api.php',
                data: { action: 'list_tickets' }
            },
            columns: [
                { data: 'title' },
                { data: 'username' },
                { 
                    data: 'status',
                    render: function(d) {
                        const style = d === 'open' ? 'bg-warning' : (d === 'replied' ? 'bg-info' : 'bg-secondary');
                        return `<span class="badge ${style}">${d}</span>`;
                    }
                },
                { data: 'updated_at' },
                {
                    data: 'id',
                    render: function(d) {
                        return `<button class="btn btn-sm btn-primary view-ticket-btn" data-id="${d}">Chat</button>`;
                    }
                }
            ]
        });

        // Launch ticket chat
        $('#support-tickets-table').on('click', '.view-ticket-btn', function() {
            const ticketId = $(this).data('id');
            $('#chat-ticket-id').val(ticketId);
            
            // Fetch messages
            $.getJSON('api.php', { action: 'get_ticket', ticket_id: ticketId }, function(res) {
                if (res.success) {
                    const data = res.data;
                    $('#chat-title').text(data.ticket.title + ' (User ID: ' + data.ticket.user_id + ')');
                    
                    let chatHTML = '';
                    data.replies.forEach(function(msg) {
                        const side = msg.sender_type === 'admin' ? 'text-end text-primary' : 'text-start text-light';
                        const label = msg.sender_type === 'admin' ? 'Admin' : 'User';
                        chatHTML += `
                            <div class="mb-3 ${side}">
                                <strong>${label}:</strong>
                                <div class="p-2 rounded d-inline-block bg-dark border mt-1" style="max-width: 70%; text-align: left;">
                                    ${msg.message}
                                </div>
                                <div class="text-muted" style="font-size: 11px;">${msg.created_at}</div>
                            </div>
                        `;
                    });

                    $('#chat-box-body').html(chatHTML || '<p class="text-muted">No messages yet.</p>');
                    
                    const modal = new bootstrap.Modal(document.getElementById('modal-chat'));
                    modal.show();
                    
                    // Scroll to bottom
                    setTimeout(() => {
                        const chatBody = document.getElementById('chat-box-body');
                        chatBody.scrollTop = chatBody.scrollHeight;
                    }, 200);
                }
            });
        });

        // Submit Ticket Reply
        $('#btn-send-reply').click(function() {
            const ticketId = $('#chat-ticket-id').val();
            const message = $('#chat-input-message').val();
            if (message.trim() === '') return;

            $.post('api.php', { action: 'reply_ticket', ticket_id: ticketId, message: message }, function(res) {
                if (res.success) {
                    showToast('Reply sent', 'success');
                    $('#chat-input-message').val('');
                    // Close modal and reload
                    bootstrap.Modal.getInstance(document.getElementById('modal-chat')).hide();
                    supportTable.ajax.reload(null, false);
                } else {
                    showToast(res.message, 'danger');
                }
            });
        });
    }

    // Queue override
    if ($('#results-view').length > 0) {
        // Handle queue deletion
        $('.btn-delete-queue').click(function(e) {
            e.preventDefault();
            const id = $(this).data('id');
            if (confirm('Remove this result override from queue?')) {
                $.post('api.php', { action: 'delete_from_queue', id: id }, function(res) {
                    if (res.success) {
                        showToast(res.message, 'success');
                        location.reload();
                    } else {
                        showToast(res.message, 'danger');
                    }
                });
            }
        });
    }

    // --- COLLAPSIBLE SUITE AJAX CONTROLLERS ---

    // 1. Live Game Manager
    const $gameView = $('#game-manager-view');
    if ($gameView.length > 0) {
        const gameCode = $gameView.data('game-code');
        let currentIssueNumber = '';

        function refreshGameState() {
            $.getJSON('api.php', { action: 'get_game_state', game_code: gameCode }, function(res) {
                if (res.success) {
                    const data = res.data;
                    currentIssueNumber = data.issue.issueNumber;

                    // Update UI Labels
                    $('#game-active-issue').text(currentIssueNumber);
                    $('#game-forced-status').text(data.forced_outcome ? data.forced_outcome : 'None');
                    $('#game-total-pool').text('₹' + data.total_bets_pool.toFixed(2));
                    
                    // Update timer minutes/seconds
                    const countdown = parseInt(data.issue.countdown);
                    const minutes = Math.floor(countdown / 60);
                    const seconds = countdown % 60;
                    $('#game-timer').text(
                        String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0')
                    );

                    // Populate Active Bets
                    let betsHTML = '';
                    if (data.live_bets && data.live_bets.length > 0) {
                        data.live_bets.forEach(function(b) {
                            betsHTML += `
                                <tr>
                                    <td>${b.player_id}</td>
                                    <td><strong>${b.username}</strong></td>
                                    <td><span class="badge bg-secondary">${b.bet_content}</span></td>
                                    <td class="text-white">₹${b.stake_amount.toFixed(2)}</td>
                                </tr>
                            `;
                        });
                    } else {
                        betsHTML = '<tr><td colspan="4" class="text-center text-muted">No active stakes</td></tr>';
                    }
                    $('#game-bets-table tbody').html(betsHTML);

                    // Populate History
                    let historyHTML = '';
                    if (data.history && data.history.length > 0) {
                        data.history.forEach(function(h) {
                            historyHTML += `
                                <tr>
                                    <td><code>${h.issue_number}</code></td>
                                    <td><strong class="text-gold">${h.premium}</strong></td>
                                    <td><span class="text-muted small">${h.color || h.sum_value || ''}</span></td>
                                </tr>
                            `;
                        });
                    } else {
                        historyHTML = '<tr><td colspan="3" class="text-center text-muted">No history yet</td></tr>';
                    }
                    $('#game-history-table tbody').html(historyHTML);
                }
            });
        }

        // Initial load & poll every 2 seconds
        refreshGameState();
        const pollInterval = setInterval(refreshGameState, 2000);

        // Submit Override SET
        $('#form-game-override').submit(function(e) {
            e.preventDefault();
            const premium = $('#override-premium-value').val();
            if (!currentIssueNumber || !premium) return;

            $.post('api.php', {
                action: 'add_to_queue',
                game_code: gameCode,
                issue_number: currentIssueNumber,
                premium: premium
            }, function(res) {
                if (res.success) {
                    showToast(res.message, 'success');
                    $('#override-premium-value').val('');
                    refreshGameState();
                } else {
                    showToast(res.message, 'danger');
                }
            });
        });

        // Submit UNSET
        $('#btn-unset-override').click(function() {
            if (!currentIssueNumber) return;
            $.post('api.php', {
                action: 'unset_forced_outcome',
                game_code: gameCode,
                issue_number: currentIssueNumber
            }, function(res) {
                if (res.success) {
                    showToast(res.message, 'success');
                    refreshGameState();
                } else {
                    showToast(res.message, 'danger');
                }
            });
        });
    }

    // 2. Gift Code Manager
    const $giftView = $('#gift-code-view');
    if ($giftView.length > 0) {
        function loadGiftCodes() {
            $.getJSON('api.php', { action: 'list_gift_codes' }, function(res) {
                if (res.success) {
                    let html = '';
                    res.data.forEach(function(c) {
                        html += `
                            <tr>
                                <td><strong>${c.code}</strong></td>
                                <td>₹${parseFloat(c.prize_amount).toFixed(2)}</td>
                                <td>${c.max_redeem}</td>
                                <td>${c.redeemed_count}</td>
                                <td>
                                    <button class="btn btn-sm btn-outline-danger btn-delete-gift" data-id="${c.id}">Delete</button>
                                </td>
                            </tr>
                        `;
                    });
                    if (res.data.length === 0) {
                        html = '<tr><td colspan="5" class="text-center text-muted">No active gift codes</td></tr>';
                    }
                    $('#gift-codes-table tbody').html(html);
                }
            });
        }

        loadGiftCodes();

        // Create Gift Code
        $('#form-add-gift-code').submit(function(e) {
            e.preventDefault();
            $.post('api.php', $(this).serialize(), function(res) {
                if (res.success) {
                    showToast(res.message, 'success');
                    $('#form-add-gift-code')[0].reset();
                    bootstrap.Modal.getInstance(document.getElementById('modal-add-gift-code')).hide();
                    loadGiftCodes();
                } else {
                    showToast(res.message, 'danger');
                }
            });
        });

        // Delete Gift Code
        $('#gift-codes-table').on('click', '.btn-delete-gift', function() {
            const id = $(this).data('id');
            if (confirm('Delete this gift code?')) {
                $.post('api.php', { action: 'delete_gift_code', id: id }, function(res) {
                    if (res.success) {
                        showToast(res.message, 'success');
                        loadGiftCodes();
                    } else {
                        showToast(res.message, 'danger');
                    }
                });
            }
        });
    }

    // 3. Same IP Checker
    const $sameIpView = $('#check-same-ip-view');
    if ($sameIpView.length > 0) {
        $.getJSON('api.php', { action: 'check_same_ip' }, function(res) {
            if (res.success) {
                let html = '';
                res.data.forEach(function(row) {
                    let accountsHTML = '';
                    row.users.forEach(function(u) {
                        accountsHTML += `<span class="badge bg-secondary me-1" title="Username: ${u.username}">${u.nickname} (${u.user_id})</span>`;
                    });
                    html += `
                        <tr>
                            <td><code>${row.ip}</code></td>
                            <td><span class="badge bg-danger">${row.count} accounts</span></td>
                            <td>${accountsHTML}</td>
                        </tr>
                    `;
                });
                if (res.data.length === 0) {
                    html = '<tr><td colspan="3" class="text-center text-muted">All logins are unique. No same-IP account conflicts detected.</td></tr>';
                }
                $('#same-ip-table tbody').html(html);
            }
        });
    }

    // 4. Site Maintenance Control
    const $maintBtn = $('#btn-toggle-maintenance');
    if ($maintBtn.length > 0) {
        $maintBtn.click(function() {
            const currentEnabled = parseInt($(this).attr('data-enabled'));
            const nextEnabled = currentEnabled === 1 ? 0 : 1;
            const actionText = nextEnabled === 1 ? 'Activate Site Maintenance?' : 'Deactivate Site Maintenance?';
            
            if (confirm(actionText)) {
                $.post('api.php', { action: 'toggle_maintenance', enabled: nextEnabled }, function(res) {
                    if (res.success) {
                        showToast(res.message, 'success');
                        location.reload();
                    } else {
                        showToast(res.message, 'danger');
                    }
                });
            }
        });
    }

    // 5. Admin & Sub-Admin Role Management
    $('#create-admin-role-select').on('change', function() {
        if ($(this).val() === '1') {
            $('#create-perms-wrapper').slideUp(200);
        } else {
            $('#create-perms-wrapper').slideDown(200);
        }
    });

    // Permission Preset Buttons (Create Modal)
    $('.btn-preset').on('click', function(e) {
        e.preventDefault();
        const preset = $(this).data('preset');
        const $boxes = $('#create-perms-wrapper .perm-checkbox');
        $boxes.prop('checked', false);

        if (preset === 'finance') {
            $boxes.filter('[value="finance"], [value="dashboard"]').prop('checked', true);
        } else if (preset === 'support') {
            $boxes.filter('[value="support"], [value="user_management"], [value="dashboard"]').prop('checked', true);
        } else if (preset === 'game') {
            $boxes.filter('[value="game_control"], [value="dashboard"]').prop('checked', true);
        } else if (preset === 'all') {
            $boxes.prop('checked', true);
        }
    });

    // Permission Preset Buttons (Edit Modal)
    $('.btn-preset-edit').on('click', function(e) {
        e.preventDefault();
        const preset = $(this).data('preset');
        const $boxes = $('#editPermsModal .edit-perm-checkbox');
        $boxes.prop('checked', false);

        if (preset === 'finance') {
            $boxes.filter('[value="finance"], [value="dashboard"]').prop('checked', true);
        } else if (preset === 'support') {
            $boxes.filter('[value="support"], [value="user_management"], [value="dashboard"]').prop('checked', true);
        } else if (preset === 'game') {
            $boxes.filter('[value="game_control"], [value="dashboard"]').prop('checked', true);
        } else if (preset === 'all') {
            $boxes.prop('checked', true);
        }
    });

    // Create Admin Submit
    $('#form-create-admin').on('submit', function(e) {
        e.preventDefault();
        const formData = $(this).serialize() + '&action=create_admin';
        const $btn = $(this).find('button[type="submit"]');
        $btn.prop('disabled', true).html('Creating Sub-Admin...');

        $.post('api.php', formData, function(res) {
            $btn.prop('disabled', false).html('Create Sub-Admin Account <i class="fas fa-check ms-1"></i>');
            if (res.success) {
                showToast(res.message, 'success');
                $('#createAdminModal').modal('hide');
                setTimeout(() => location.reload(), 800);
            } else {
                showToast(res.message, 'danger');
            }
        }).fail(function(xhr) {
            $btn.prop('disabled', false).html('Create Sub-Admin Account <i class="fas fa-check ms-1"></i>');
            showToast(xhr.responseJSON?.message || 'Failed to create sub-admin', 'danger');
        });
    });

    // Open Edit Perms Modal
    $(document).on('click', '.btn-edit-perms', function() {
        const id = $(this).data('id');
        const username = $(this).data('username');
        const perms = $(this).data('perms') || [];

        $('#edit-perms-admin-id').val(id);
        $('#edit-perms-username').text(username);
        $('#editPermsModal .edit-perm-checkbox').prop('checked', false);

        if (Array.isArray(perms)) {
            perms.forEach(p => {
                $('#edit-perm-' + p).prop('checked', true);
            });
        }

        $('#editPermsModal').modal('show');
    });

    // Save Edit Perms Submit
    $('#form-edit-perms').on('submit', function(e) {
        e.preventDefault();
        const formData = $(this).serialize() + '&action=update_admin_permissions';
        const $btn = $(this).find('button[type="submit"]');
        $btn.prop('disabled', true).html('Saving Permissions...');

        $.post('api.php', formData, function(res) {
            $btn.prop('disabled', false).html('Save Permissions <i class="fas fa-save ms-1"></i>');
            if (res.success) {
                showToast(res.message, 'success');
                $('#editPermsModal').modal('hide');
                setTimeout(() => location.reload(), 800);
            } else {
                showToast(res.message, 'danger');
            }
        }).fail(function(xhr) {
            $btn.prop('disabled', false).html('Save Permissions <i class="fas fa-save ms-1"></i>');
            showToast(xhr.responseJSON?.message || 'Failed to save permissions', 'danger');
        });
    });

    // Open Reset Password Modal
    $(document).on('click', '.btn-reset-admin-pwd', function() {
        const id = $(this).data('id');
        const username = $(this).data('username');
        $('#reset-pwd-admin-id').val(id);
        $('#reset-pwd-username').text(username);
        $('#form-reset-admin-pwd input[name="new_password"]').val('');
        $('#resetPasswordModal').modal('show');
    });

    // Reset Password Submit
    $('#form-reset-admin-pwd').on('submit', function(e) {
        e.preventDefault();
        const formData = $(this).serialize() + '&action=reset_admin_password';
        const $btn = $(this).find('button[type="submit"]');
        $btn.prop('disabled', true).html('Updating...');

        $.post('api.php', formData, function(res) {
            $btn.prop('disabled', false).html('Update Password <i class="fas fa-check ms-1"></i>');
            if (res.success) {
                showToast(res.message, 'success');
                $('#resetPasswordModal').modal('hide');
            } else {
                showToast(res.message, 'danger');
            }
        }).fail(function(xhr) {
            $btn.prop('disabled', false).html('Update Password <i class="fas fa-check ms-1"></i>');
            showToast(xhr.responseJSON?.message || 'Failed to update password', 'danger');
        });
    });

    // Toggle Admin Status
    $(document).on('click', '.btn-toggle-admin-status', function() {
        const id = $(this).data('id');
        if (!confirm('Are you sure you want to change this staff admin status?')) return;

        $.post('api.php', { action: 'toggle_admin_status', admin_id: id }, function(res) {
            if (res.success) {
                showToast(res.message, 'success');
                location.reload();
            } else {
                showToast(res.message, 'danger');
            }
        });
    });

    // Delete Admin Account
    $(document).on('click', '.btn-delete-admin', function() {
        const id = $(this).data('id');
        const username = $(this).data('username');
        if (!confirm('Are you sure you want to permanently delete staff account "' + username + '"? This action cannot be undone.')) return;

        $.post('api.php', { action: 'delete_admin', admin_id: id }, function(res) {
            if (res.success) {
                showToast(res.message, 'success');
                location.reload();
            } else {
                showToast(res.message, 'danger');
            }
        });
    });

    // =====================================================================
    // ROUND 9: controllers for the previously dead admin tabs
    // =====================================================================

    const adminPost = function (payload, onSuccess, onFail) {
        $.post('api.php', payload, function (res) {
            if (res && res.success) {
                if (onSuccess) onSuccess(res);
            } else {
                showToast((res && res.message) ? res.message : 'Request failed', 'danger');
                if (onFail) onFail(res);
            }
        }).fail(function (xhr) {
            const msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Server error';
            showToast(msg, 'danger');
            if (onFail) onFail(xhr);
        });
    };

    const money = function (value) {
        const number = parseFloat(value) || 0;
        return '₹' + number.toFixed(2);
    };

    // --- Demo users -------------------------------------------------------
    if ($('#demo-user-view').length > 0) {
        const loadDemoUsers = function () {
            $.getJSON('api.php', { action: 'list_demo_users' }, function (res) {
                let html = '';
                if (res && res.success && res.data && res.data.length) {
                    res.data.forEach(function (row) {
                        const status = row.can_bet === 1
                            ? '<span class="badge bg-success">Active</span>'
                            : '<span class="badge bg-danger">Blocked</span>';
                        html += `<tr>
                            <td><div class="fw-bold text-white">${row.username}</div><div class="text-muted small">ID: ${row.user_id}</div></td>
                            <td>${row.nickname || '-'}</td>
                            <td>${row.phone || '-'}</td>
                            <td class="fw-bold text-gold">${money(row.balance)}</td>
                            <td>${status}</td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-info btn-reset-demo" data-id="${row.id}" data-username="${row.username}" data-balance="${row.balance}">Reset Balance</button>
                                <button class="btn btn-sm btn-outline-danger btn-delete-demo" data-id="${row.id}" data-username="${row.username}">Delete</button>
                            </td>
                        </tr>`;
                    });
                } else {
                    html = '<tr><td colspan="6" class="text-center text-muted">No demo user created yet. Use the form to create one.</td></tr>';
                }
                $('#demo-users-table tbody').html(html);
            });
        };
        loadDemoUsers();
        $('#btn-refresh-demo-users').click(loadDemoUsers);

        $('#demo-users-table').on('click', '.btn-reset-demo', function () {
            const id = $(this).data('id');
            const username = $(this).data('username');
            const value = prompt('New demo balance for ' + username + ' (₹):', '10000');
            if (value === null) return;
            adminPost({ action: 'reset_demo_balance', id: id, balance: value }, function (res) {
                showToast(res.message, 'success');
                loadDemoUsers();
            });
        });

        $('#demo-users-table').on('click', '.btn-delete-demo', function () {
            const id = $(this).data('id');
            if (!confirm('Delete this demo user?')) return;
            adminPost({ action: 'delete_demo_user', id: id }, function (res) {
                showToast(res.message, 'success');
                loadDemoUsers();
            });
        });
    }

    // --- Agent users ------------------------------------------------------
    if ($('#agent-user-view').length > 0) {
        const loadAgents = function () {
            $.getJSON('api.php', { action: 'list_agents' }, function (res) {
                let html = '';
                if (res && res.success && res.data && res.data.length) {
                    res.data.forEach(function (row) {
                        const status = row.status === 1
                            ? '<span class="badge bg-success">Active</span>'
                            : '<span class="badge bg-secondary">Disabled</span>';
                        html += `<tr>
                            <td><div class="fw-bold text-white">${row.username}</div><div class="text-muted small">ID: ${row.user_id} ${status}</div></td>
                            <td>${row.phone || '-'}</td>
                            <td><span class="badge bg-info text-dark">${parseFloat(row.agent_rate).toFixed(2)}%</span></td>
                            <td>${row.team_size}</td>
                            <td class="text-gold">${money(row.commission)}</td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-info btn-agent-rate" data-id="${row.id}" data-rate="${row.agent_rate}" data-username="${row.username}">Rate</button>
                                <button class="btn btn-sm btn-outline-warning btn-agent-toggle" data-id="${row.id}" data-status="${row.status}">${row.status === 1 ? 'Disable' : 'Enable'}</button>
                                <button class="btn btn-sm btn-outline-danger btn-agent-delete" data-id="${row.id}" data-username="${row.username}">Delete</button>
                            </td>
                        </tr>`;
                    });
                } else {
                    html = '<tr><td colspan="6" class="text-center text-muted">No agent account yet. Create one from the form.</td></tr>';
                }
                $('#agents-table tbody').html(html);
            });
        };
        loadAgents();
        $('#btn-refresh-agents').click(loadAgents);

        $('#agents-table').on('click', '.btn-agent-rate', function () {
            const id = $(this).data('id');
            const username = $(this).data('username');
            const value = prompt('Commission rate (%) for ' + username + ':', $(this).data('rate'));
            if (value === null) return;
            adminPost({ action: 'save_user', id: id, is_agent: 1, agent_rate: value }, function (res) {
                showToast(res.message, 'success');
                loadAgents();
            });
        });

        $('#agents-table').on('click', '.btn-agent-toggle', function () {
            const id = $(this).data('id');
            const nextStatus = parseInt($(this).data('status')) === 1 ? 0 : 1;
            adminPost({ action: 'save_user', id: id, status: nextStatus, can_bet: nextStatus }, function (res) {
                showToast(res.message, 'success');
                loadAgents();
            });
        });

        $('#agents-table').on('click', '.btn-agent-delete', function () {
            const id = $(this).data('id');
            if (!confirm('Delete this agent account? The member login will stop working.')) return;
            adminPost({ action: 'delete_user', id: id }, function (res) {
                showToast(res.message, 'success');
                loadAgents();
            });
        });
    }

    // --- Banned users -----------------------------------------------------
    if ($('#banned-users-view').length > 0) {
        const loadBanned = function () {
            $.getJSON('api.php', { action: 'list_banned_users' }, function (res) {
                let html = '';
                if (res && res.success && res.data && res.data.length) {
                    res.data.forEach(function (row) {
                        html += `<tr>
                            <td><div class="fw-bold text-white">${row.username}</div><div class="text-muted small">${row.nickname || ''}</div></td>
                            <td>${row.user_id}</td>
                            <td>${row.phone || '-'}</td>
                            <td>${money(row.balance)}</td>
                            <td class="text-muted small">${row.ban_reason || 'Blocked'}</td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-success btn-unban" data-id="${row.id}" data-username="${row.username}">Unban</button>
                            </td>
                        </tr>`;
                    });
                } else {
                    html = '<tr><td colspan="6" class="text-center text-muted">No banned user. Everyone can bet.</td></tr>';
                }
                $('#banned-users-table tbody').html(html);
            });
        };
        loadBanned();
        $('#btn-refresh-banned').click(loadBanned);

        $('#banned-users-table').on('click', '.btn-unban', function () {
            const id = $(this).data('id');
            adminPost({ action: 'set_user_ban', id: id, ban: 0 }, function (res) {
                showToast(res.message, 'success');
                loadBanned();
            });
        });
    }

    // --- Payout tabs (UPI withdraw / sent / rejected) ---------------------
    if ($('#payout-table').length > 0) {
        const $payoutTable = $('#payout-table');
        const initialStatus = $payoutTable.data('payout-status') || '';
        const initialType = $payoutTable.data('payout-type') || '';
        $('#payout-status-filter').val(initialStatus);

        const loadPayouts = function () {
            const status = $('#payout-status-filter').val() || '';
            $.getJSON('api.php', { action: 'list_withdrawals', status: status, withdraw_type: initialType, length: 200 }, function (res) {
                let html = '';
                const rows = (res && res.data) ? res.data : [];
                if (rows.length) {
                    rows.forEach(function (row) {
                        let account = row.account_json || '';
                        try {
                            const acc = JSON.parse(account);
                            account = acc.accountNo || acc.upiId || acc.account || account;
                        } catch (e) { /* keep raw */ }
                        const style = row.status === 'Approved' ? 'bg-success' : (row.status === 'Pending' ? 'bg-warning' : 'bg-danger');
                        let actions = '<span class="text-muted small">Settled</span>';
                        if (row.status === 'Pending') {
                            actions = `<button class="btn btn-sm btn-success approve-withdraw-btn" data-id="${row.id}">Approve</button>
                                       <button class="btn btn-sm btn-danger reject-withdraw-btn" data-id="${row.id}">Reject</button>`;
                        }
                        html += `<tr>
                            <td><code>${row.order_no}</code></td>
                            <td>${row.player_id}</td>
                            <td>${row.username}</td>
                            <td class="fw-bold text-gold">${money(row.amount)}</td>
                            <td>${row.payment_type || row.withdraw_type || 'UPI'}</td>
                            <td><span class="badge ${style}">${row.status}</span></td>
                            <td class="small">${account}</td>
                            <td class="small text-muted">${row.remarks || ''}</td>
                            <td class="text-end">${actions}</td>
                        </tr>`;
                    });
                } else {
                    html = '<tr><td colspan="9" class="text-center text-muted">No payout request found for this filter.</td></tr>';
                }
                $payoutTable.find('tbody').html(html);
            });
        };
        loadPayouts();
        $('#payout-status-filter').change(loadPayouts);

        $payoutTable.on('click', '.approve-withdraw-btn', function () {
            const id = $(this).data('id');
            if (!confirm('Approve this payout request?')) return;
            adminPost({ action: 'update_withdrawal', id: id, status: 'Approved' }, function (res) {
                showToast(res.message, 'success');
                loadPayouts();
            });
        });

        $payoutTable.on('click', '.reject-withdraw-btn', function () {
            const id = $(this).data('id');
            const reason = prompt('Reason for rejection (the amount is returned to the member):', 'Rejected by admin');
            if (reason === null) return;
            adminPost({ action: 'update_withdrawal', id: id, status: 'Rejected', remarks: reason }, function (res) {
                showToast(res.message, 'success');
                loadPayouts();
            });
        });
    }

    // --- Support category queues -----------------------------------------
    if ($('#support-category-view').length > 0) {
        const $supportCategory = $('#support-category-view');
        const category = $supportCategory.data('support-category');

        const loadCategoryTickets = function () {
            $.getJSON('api.php', {
                action: 'list_tickets',
                category: category,
                status: $('#support-status-filter').val() || '',
                length: 200
            }, function (res) {
                let html = '';
                const rows = (res && res.data) ? res.data : [];
                if (rows.length) {
                    rows.forEach(function (row) {
                        const style = row.status === 'open' ? 'bg-warning text-dark' : (row.status === 'replied' ? 'bg-info text-dark' : 'bg-secondary');
                        html += `<tr>
                            <td><div class="fw-bold text-white">${row.title}</div><div class="text-muted small">${row.replies} message(s)</div></td>
                            <td><div>${row.username}</div><div class="text-muted small">${row.phone || ''}</div></td>
                            <td>${row.order_no ? ('<code>' + row.order_no + '</code>') : '-'}</td>
                            <td><span class="badge ${style}">${row.status}</span></td>
                            <td class="small text-muted">${row.updated_at}</td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-primary view-ticket-btn" data-id="${row.id}">Chat</button>
                                <button class="btn btn-sm btn-outline-secondary close-ticket-btn" data-id="${row.id}">Close</button>
                            </td>
                        </tr>`;
                    });
                } else {
                    html = '<tr><td colspan="6" class="text-center text-muted">No ticket in this queue yet.</td></tr>';
                }
                $('#support-tickets-table tbody').html(html);
            });
        };
        loadCategoryTickets();
        $('#support-status-filter').change(loadCategoryTickets);

        $('#support-tickets-table').on('click', '.view-ticket-btn', function () {
            const ticketId = $(this).data('id');
            $('#chat-ticket-id').val(ticketId);
            $.getJSON('api.php', { action: 'get_ticket', ticket_id: ticketId }, function (res) {
                if (res.success) {
                    const data = res.data;
                    $('#chat-title').text(data.ticket.title + ' (User ID: ' + data.ticket.user_id + ')');
                    let chatHTML = '';
                    data.replies.forEach(function (msg) {
                        const side = msg.sender_type === 'admin' ? 'text-end text-primary' : 'text-start text-light';
                        const label = msg.sender_type === 'admin' ? 'Admin' : 'User';
                        chatHTML += `<div class="mb-3 ${side}"><strong>${label}:</strong>
                            <div class="p-2 rounded d-inline-block bg-dark border mt-1" style="max-width: 70%; text-align: left;">${msg.message}</div>
                            <div class="text-muted" style="font-size: 11px;">${msg.created_at}</div></div>`;
                    });
                    $('#chat-box-body').html(chatHTML || '<p class="text-muted">No messages yet.</p>');
                    new bootstrap.Modal(document.getElementById('modal-chat')).show();
                    setTimeout(function () {
                        const box = document.getElementById('chat-box-body');
                        box.scrollTop = box.scrollHeight;
                    }, 200);
                } else {
                    showToast(res.message || 'Could not open the ticket', 'danger');
                }
            });
        });

        $('#btn-send-reply').on('click', function () {
            const ticketId = $('#chat-ticket-id').val();
            const message = $('#chat-input-message').val();
            if (!ticketId || !message || message.trim() === '') return;
            adminPost({ action: 'reply_ticket', ticket_id: ticketId, message: message }, function (res) {
                showToast('Reply sent', 'success');
                $('#chat-input-message').val('');
                bootstrap.Modal.getInstance(document.getElementById('modal-chat')).hide();
                loadCategoryTickets();
            });
        });

        $('#support-tickets-table').on('click', '.close-ticket-btn', function () {
            const id = $(this).data('id');
            if (!confirm('Close this ticket?')) return;
            adminPost({ action: 'close_ticket', ticket_id: id }, function (res) {
                showToast(res.message, 'success');
                loadCategoryTickets();
            });
        });
    }

    // --- Close ticket from the chat modal (both support views) ------------
    $(document).on('click', '#btn-close-ticket', function () {
        const ticketId = $('#chat-ticket-id').val();
        if (!ticketId) return;
        if (!confirm('Close this ticket?')) return;
        const modalEl = document.getElementById('modal-chat');
        const modalInstance = bootstrap.Modal.getInstance(modalEl);
        adminPost({ action: 'close_ticket', ticket_id: ticketId }, function (res) {
            showToast(res.message, 'success');
            if (modalInstance) modalInstance.hide();
            if ($('#support-category-view').length > 0) {
                $('#support-status-filter').trigger('change');
            } else if ($('#support-view').length > 0) {
                $('#support-tickets-table').DataTable().ajax.reload(null, false);
            }
        });
    });

    // --- Gift code redemption log ----------------------------------------
    if ($('#gift-redemptions-table').length > 0) {
        $.getJSON('api.php', { action: 'list_gift_redemptions' }, function (res) {
            let html = '';
            const rows = (res && res.data) ? res.data : [];
            if (rows.length) {
                rows.forEach(function (row) {
                    html += `<tr>
                        <td><strong class="text-gold">${row.code}</strong></td>
                        <td>${row.username}${row.phone ? ' <span class="text-muted small">(' + row.phone + ')</span>' : ''}</td>
                        <td class="text-success fw-bold">+${money(row.amount)}</td>
                        <td class="small text-muted">${row.created_at}</td>
                    </tr>`;
                });
            } else {
                html = '<tr><td colspan="4" class="text-center text-muted">No code redeemed yet.</td></tr>';
            }
            $('#gift-redemptions-table tbody').html(html);
        });
    }

    // --- Bonus preview calculator ----------------------------------------
    if ($('#bonus-preview-amount').length > 0) {
        const $bonusBox = $('#bonus-preview-box');
        const bonusPercent = parseFloat($bonusBox.data('bonus-percent')) || 0;
        const bonusMax = parseFloat($bonusBox.data('bonus-max')) || 0;
        const updateBonusPreview = function () {
            const base = parseFloat($('#bonus-preview-amount').val()) || 0;
            const bonus = Math.min(base * bonusPercent / 100, bonusMax);
            $('#bonus-preview-base').text('₹' + base.toFixed(2));
            $('#bonus-preview-bonus').text('₹' + bonus.toFixed(2));
            $('#bonus-preview-total').text('₹' + (base + bonus).toFixed(2));
        };
        $('#bonus-preview-amount').on('input change', updateBonusPreview);
        updateBonusPreview();
    }

    // --- USDT converter ---------------------------------------------------
    if ($('#usdt-convert-input').length > 0) {
        const $usdtInput = $('#usdt-convert-input');
        const rate = parseFloat($usdtInput.data('rate')) || 0;
        const updateUsdt = function () {
            const usdt = parseFloat($usdtInput.val()) || 0;
            $('#usdt-convert-output').text('₹' + (usdt * rate).toFixed(2));
        };
        $usdtInput.on('input change', updateUsdt);
        updateUsdt();
    }

    // --- Own password confirmation ---------------------------------------
    $('#form-own-password').on('submit', function (e) {
        const newPassword = $('#own-new-password').val();
        const confirmPassword = $('#own-confirm-password').val();
        if (newPassword !== confirmPassword) {
            e.preventDefault();
            showToast('New password and confirmation do not match', 'danger');
        }
    });

    // --- Every plain <form action="api.php"> posts in the background --------
    // These forms used to submit as normal page navigations. On some hosts that
    // navigation ended on a dead "This site can't be reached" page (exactly what
    // happened after "Create Demo User") and a failed action left the admin with
    // no message at all. Posting through AJAX keeps the tab open, shows the real
    // result from the server and then reloads the same tab with the flash
    // message - the same end state the redirect used to produce, minus the dead
    // page.
    let lastSubmitter = null;
    $(document).on('click', 'form[action$="api.php"] button[type="submit"], form[action$="api.php"] input[type="submit"]', function () {
        lastSubmitter = this;
    });

    $(document).on('submit', 'form[action$="api.php"]', function (e) {
        if (e.isDefaultPrevented()) {
            return; // a tab specific handler already posted this form
        }
        const $form = $(this);
        e.preventDefault();

        const $btn = $form.find('button[type="submit"], button:not([type]), input[type="submit"]').first();
        const originalHtml = $btn.length ? $btn.html() : '';
        if ($btn.length) {
            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Please wait...');
        }
        const restoreButton = function () {
            if ($btn.length) {
                $btn.prop('disabled', false).html(originalHtml);
            }
        };

        const payload = $form.serializeArray();
        if (lastSubmitter && $form[0].contains(lastSubmitter) && lastSubmitter.name) {
            // a submit button can carry the action (formaction / name="action")
            payload.push({ name: lastSubmitter.name, value: $(lastSubmitter).val() });
        }
        lastSubmitter = null;

        const tab = $form.find('input[name="return_tab"]').val();
        const reloadUrl = window.location.pathname + (tab ? '?tab=' + encodeURIComponent(tab) : window.location.search);

        $.ajax({
            url: 'api.php',
            type: 'POST',
            data: $.param(payload),
            dataType: 'json',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }).done(function (res) {
            restoreButton();
            const ok = !!(res && res.success);
            const message = (res && res.message) ? res.message : (ok ? 'Saved successfully' : 'Request failed');
            if (typeof showToast === 'function') {
                showToast(message, ok ? 'success' : 'danger');
            }
            if (ok) {
                const flash = encodeURIComponent(message);
                const type = 'ok';
                setTimeout(function () {
                    window.location.replace(reloadUrl + (reloadUrl.indexOf('?') === -1 ? '?' : '&') + 'flash=' + flash + '&flash_type=' + type);
                }, 600);
            }
        }).fail(function (xhr) {
            restoreButton();
            const message = (xhr.responseJSON && xhr.responseJSON.message)
                ? xhr.responseJSON.message
                : ('Server error (' + (xhr.status || 0) + '). Please try again.');
            if (typeof showToast === 'function') {
                showToast(message, 'danger');
            }
        });
    });
});
