<?php
declare(strict_types=1);
/**
 * Shared support chat modal. Rendered by the main support tab and by every
 * support category tab (support_deposit, support_withdraw, support_ifsc,
 * support_bank, support_game) so the reply flow works on all of them.
 */
?>
<!-- Modal: Ticket Chat dialogue -->
<div class="modal fade" id="modal-chat" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content modal-content-premium text-white">
      <div class="modal-header modal-header-premium">
        <h5 class="modal-title fw-bold" id="chat-title">Ticket Chat</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">
        <div id="chat-box-body" style="height: 350px; overflow-y: auto; background: rgba(7,9,19,0.3); padding: 15px; border-radius: 12px; border: 1px solid var(--border-light);">
            <!-- Chat logs load dynamically -->
        </div>
        <input type="hidden" id="chat-ticket-id">
        <div class="input-group mt-3">
            <input id="chat-input-message" class="form-control form-control-premium" placeholder="Type administrator reply here...">
            <button type="button" id="btn-send-reply" class="btn btn-premium">Send Reply</button>
        </div>
        <div class="text-end mt-2">
            <button type="button" class="btn btn-sm btn-outline-danger" id="btn-close-ticket"><i class="fas fa-check me-1"></i> Close Ticket</button>
        </div>
      </div>
    </div>
  </div>
</div>
