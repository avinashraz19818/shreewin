<?php
/** Shared live Big/Small widget for every lottery manager. */
$adminGameType = isset($adminGameType) ? (string)$adminGameType : '';
$adminHasBetDetails = !empty($adminHasBetDetails);
?>
<style>
.admin-bs-filter{border:2px solid transparent;border-radius:16px;padding:18px 22px;width:100%;background:var(--bs-paper-bg,#fff);box-shadow:0 3px 12px rgba(0,0,0,.06);transition:.18s ease;text-align:left}
.admin-bs-filter:hover{transform:translateY(-1px)}.admin-bs-filter.active{border-color:var(--bs-primary);box-shadow:0 5px 16px rgba(115,103,240,.18)}
.admin-bs-filter .label{font-size:15px;font-weight:600}.admin-bs-filter .amount{font-size:25px;font-weight:700;margin-top:4px}.admin-bs-filter.big .amount{color:#ff8a3d}.admin-bs-filter.small .amount{color:#4f8cff}.admin-bs-filter .hint{font-size:12px;opacity:.7}
</style>
<div class="row g-4 mb-5 admin-big-small-widget" data-game-type="<?= htmlspecialchars($adminGameType, ENT_QUOTES, 'UTF-8') ?>">
 <div class="col-6"><button type="button" class="admin-bs-filter big" id="admin-filter-big"><div class="label">BIG BET</div><div class="amount" id="admin-big-total">₹ 0.00</div><div class="hint">Tap to show Big bets</div></button></div>
 <div class="col-6"><button type="button" class="admin-bs-filter small" id="admin-filter-small"><div class="label">SMALL BET</div><div class="amount" id="admin-small-total">₹ 0.00</div><div class="hint">Tap to show Small bets</div></button></div>
</div>
<?php if (!$adminHasBetDetails): ?>
<div class="card mb-5"><h4 class="m-5">BET Details</h4><div class="table-responsive text-nowrap"><table class="table"><thead><tr><th>Result</th><th>Bet</th><th>No. of User</th><th>Amount to Pay</th></tr></thead><tbody id="betdetail"><tr><td colspan="4" class="text-center">Loading...</td></tr></tbody></table></div></div>
<?php endif; ?>
<script>
(function(){
 const gameType=<?= json_encode($adminGameType) ?>; let items=[]; window.adminBigSmallFilter='';
 function pretty(v){v=String(v||'');if(v.toLowerCase()==='bigsmall_big')return 'Big';if(v.toLowerCase()==='bigsmall_small')return 'Small';return v.replace(/^Num_/i,'Number ').replace(/^Color_/i,'');}
 function render(){let big=0,small=0;items.forEach(x=>{const b=String(x.bet||'').toLowerCase();if(b==='bigsmall_big')big+=Number(x.stake||0);if(b==='bigsmall_small')small+=Number(x.stake||0)});document.getElementById('admin-big-total').textContent='₹ '+big.toFixed(2);document.getElementById('admin-small-total').textContent='₹ '+small.toFixed(2);const shown=items.filter(x=>!window.adminBigSmallFilter||String(x.bet||'').toLowerCase()==='bigsmall_'+window.adminBigSmallFilter);const body=document.getElementById('betdetail');if(body)body.innerHTML=shown.map(x=>'<tr><td>'+pretty(x.bet)+'</td><td>₹ '+Number(x.stake||0).toFixed(2)+'</td><td>'+Number(x.users||0)+'</td><td>₹ '+Number(x.potentialPayout||0).toFixed(2)+'</td></tr>').join('')||'<tr><td colspan="4" class="text-center">No '+(window.adminBigSmallFilter?pretty('bigsmall_'+window.adminBigSmallFilter)+' ':'')+'bets in this period</td></tr>';}
 function choose(v){window.adminBigSmallFilter=window.adminBigSmallFilter===v?'':v;document.getElementById('admin-filter-big').classList.toggle('active',window.adminBigSmallFilter==='big');document.getElementById('admin-filter-small').classList.toggle('active',window.adminBigSmallFilter==='small');render();if(typeof window.refreshLiveBets==='function')window.refreshLiveBets(true);}
 async function load(){const el=document.getElementById('curr-period');if(!el)return;const p=el.textContent.trim();if(!/^\d{17}$/.test(p))return;try{const r=await fetch('api/game-bet-summary.php?type='+encodeURIComponent(gameType)+'&periodid='+encodeURIComponent(p),{cache:'no-store'});const j=await r.json();items=Array.isArray(j.data)?j.data:[];render()}catch(e){}}
 document.getElementById('admin-filter-big').addEventListener('click',()=>choose('big'));document.getElementById('admin-filter-small').addEventListener('click',()=>choose('small'));setTimeout(load,500);setInterval(load,2000);
})();
</script>
