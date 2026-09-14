// UI v2: no floating migration banner. Backend preview/activation rules are unchanged.
// Keep the read-only archive accessible below history instead of covering the game.
let observer=null, archiveLink=null;
function update() {
  observer?.disconnect(); observer=null;
  archiveLink?.remove(); archiveLink=null;
  if (!location.hash.startsWith('#/saasLottery/WinGo')) return;
  function attach() {
    const history=document.querySelector('.winGo3 .history');
    if (!history) return false;
    archiveLink=document.createElement('a');
    archiveLink.href='/veegame-legacy-history.html';
    archiveLink.textContent='Legacy history';
    archiveLink.dataset.veegameArchive='true';
    archiveLink.style.cssText='display:block;text-align:center;padding:12px;color:#b9b9b9;font:12px/1.4 sans-serif;text-decoration:underline;';
    history.append(archiveLink);
    observer?.disconnect(); observer=null;
    return true;
  }
  if (!attach()) {
    observer=new MutationObserver(attach);
    observer.observe(document.body,{childList:true,subtree:true});
  }
}
window.addEventListener('hashchange',update);
setTimeout(update,0);
